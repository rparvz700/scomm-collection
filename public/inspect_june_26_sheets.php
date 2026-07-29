<?php
$excelFile = "D:\\D\\Automation-Innovation\\scomm-collection\\KPI_ENGINE_FILES\\13KPI_ENGINE_LIVE_&_Discontinued_ISPs_June'26_30th_June'26_Updated.xlsx";
header('Content-Type: text/plain; charset=utf-8');

if (!file_exists($excelFile)) {
    die("File not found: $excelFile\n");
}

$zip = new ZipArchive();
if ($zip->open($excelFile) !== TRUE) {
    die("Could not open ZIP archive.\n");
}

// 1. Load Shared Strings
$sharedStrings = [];
$ssContent = $zip->getFromName('xl/sharedStrings.xml');
if ($ssContent !== false) {
    $xml = simplexml_load_string($ssContent);
    foreach ($xml->si as $si) {
        $text = '';
        if (isset($si->t)) {
            $text = (string)$si->t;
        } else {
            foreach ($si->r as $r) {
                if (isset($r->t)) {
                    $text .= (string)$r->t;
                }
            }
        }
        $sharedStrings[] = trim($text);
    }
}

// 2. Load Workbook Relationship Map
$wbContent = $zip->getFromName('xl/workbook.xml');
$wbXml = simplexml_load_string($wbContent);
$relsContent = $zip->getFromName('xl/_rels/workbook.xml.rels');
$relsXml = simplexml_load_string($relsContent);
$sheetMap = [];
foreach ($relsXml->Relationship as $rel) {
    $sheetMap[(string)$rel['Id']] = (string)$rel['Target'];
}

$sheets = [];
foreach ($wbXml->sheets->sheet as $sheet) {
    $rId = '';
    $attrs = $sheet->attributes('r', true);
    if (isset($attrs['id'])) {
        $rId = (string)$attrs['id'];
    }
    $sheets[] = [
        'name' => (string)$sheet['name'],
        'target' => isset($sheetMap[$rId]) ? $sheetMap[$rId] : null
    ];
}

// 3. Flexible Worksheet Recognition
$targetPath = null;
foreach ($sheets as $s) {
    $normalized = strtolower(str_replace([' ', '_', '-', '+'], '', $s['name']));
    if (strpos($normalized, 'postpaidprepaid') !== false || strpos($normalized, 'postprepaid') !== false) {
        $targetPath = $s['target'];
        echo "Found sheet: {$s['name']} -> {$s['target']}\n";
        break;
    }
}

if (!$targetPath) {
    die("Post-Paid+Pre-Paid sheet not found.\n");
}

// Helper: Convert column letter to 0-based index
$colLetterToIdx = function($col) {
    $len = strlen($col);
    $idx = 0;
    for ($i = 0; $i < $len; $i++) {
        $idx = ($idx * 26) + (ord($col[$i]) - ord('A') + 1);
    }
    return $idx - 1;
};

// Helper: Parse cell reference
$parseCellRef = function($ref) {
    if (preg_match('/^([A-Z]+)([0-9]+)$/', $ref, $matches)) {
        return [
            'col' => $matches[1],
            'row' => (int)$matches[2]
        ];
    }
    return null;
};

$sheetPath = 'xl/' . $targetPath;
$sheetContent = $zip->getFromName($sheetPath);
if ($sheetContent === false) {
    die("Sheet content empty.\n");
}

$wsXml = simplexml_load_string($sheetContent);
$data = [];

// Print first 5 rows
foreach ($wsXml->sheetData->row as $row) {
    $rowNum = (int)$row['r'];
    if ($rowNum > 5) break;
    
    $rowData = [];
    foreach ($row->c as $c) {
        $ref = (string)$c['r'];
        $parsed = $parseCellRef($ref);
        if (!$parsed) continue;
        $colIdx = $colLetterToIdx($parsed['col']);
        
        $val = '';
        if (isset($c->v)) {
            $val = (string)$c->v;
            $t = (string)$c['t'];
            if ($t === 's') {
                $idx = (int)$val;
                if (isset($sharedStrings[$idx])) {
                    $val = $sharedStrings[$idx];
                }
            }
        }
        $rowData[$colIdx] = trim($val);
    }
    ksort($rowData);
    $data[$rowNum] = $rowData;
}

echo "\n--- First 5 Rows of Post-Paid+Pre-Paid Sheet ---\n";
foreach ($data as $rowNum => $rowData) {
    echo "Row $rowNum:\n";
    foreach ($rowData as $colIdx => $val) {
        // convert colIdx to letters
        $letters = '';
        $temp = $colIdx + 1;
        while ($temp > 0) {
            $m = ($temp - 1) % 26;
            $letters = chr(65 + $m) . $letters;
            $temp = (int)(($temp - $m) / 26);
        }
        echo "  $letters ($colIdx): $val\n";
    }
}

$zip->close();
