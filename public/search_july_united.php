<?php
$excelFile = "D:\\D\\Automation-Innovation\\scomm-collection\\KPI_ENGINE_FILES\\14KPI_ENGINE_LIVE_&_Discontinued_ISPs_July'26_31st_July'26_Final.xlsx";
header('Content-Type: text/plain; charset=utf-8');

$zip = new ZipArchive();
if ($zip->open($excelFile) !== TRUE) {
    die("Could not open ZIP archive.\n");
}

// Load Shared Strings
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

// Load Workbook Relationship Map
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

$colLetterToIdx = function($col) {
    $len = strlen($col);
    $idx = 0;
    for ($i = 0; $i < $len; $i++) {
        $idx = ($idx * 26) + (ord($col[$i]) - ord('A') + 1);
    }
    return $idx - 1;
};

$parseCellRef = function($ref) {
    if (preg_match('/^([A-Z]+)([0-9]+)$/', $ref, $matches)) {
        return [
            'col' => $matches[1],
            'row' => (int)$matches[2]
        ];
    }
    return null;
};

$readSheet = function($zip, $targetPath, $sharedStrings) use ($colLetterToIdx, $parseCellRef) {
    $sheetPath = 'xl/' . $targetPath;
    $sheetContent = $zip->getFromName($sheetPath);
    if ($sheetContent === false) {
        return [];
    }
    
    $wsXml = simplexml_load_string($sheetContent);
    $data = [];
    
    foreach ($wsXml->sheetData->row as $row) {
        $rowNum = (int)$row['r'];
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
        $data[$rowNum] = $rowData;
    }
    return $data;
};

echo "=== Searching July 26 Excel File for 'United' ===\n\n";

foreach ($sheets as $s) {
    echo "Sheet: {$s['name']} ({$s['target']})\n";
    $rows = $readSheet($zip, $s['target'], $sharedStrings);
    
    $found = 0;
    foreach ($rows as $rowNum => $row) {
        $rowStr = implode(' | ', $row);
        if (stripos($rowStr, 'United') !== false) {
            echo "  Row $rowNum: $rowStr\n";
            $found++;
        }
    }
    if ($found === 0) {
        echo "  (No matches found)\n";
    }
    echo "\n";
}

$zip->close();
