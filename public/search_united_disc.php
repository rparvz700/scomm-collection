<?php
$excelFile = "D:\\D\\Automation-Innovation\\scomm-collection\\KPI_ENGINE_FILES\\15KPI_ENGINE_LIVE_&_Discontinued_ISPs_Aug'26_16th_Aug'26.xlsx";
header('Content-Type: text/plain; charset=utf-8');

$zip = new ZipArchive();
if ($zip->open($excelFile) !== TRUE) {
    die("Could not open ZIP archive.\n");
}

$sharedStrings = [];
$ssContent = $zip->getFromName('xl/sharedStrings.xml');
if ($ssContent !== false) {
    $xml = simplexml_load_string($ssContent);
    foreach ($xml->si as $si) {
        $text = '';
        if (isset($si->t)) $text = (string)$si->t;
        else {
            foreach ($si->r as $r) {
                if (isset($r->t)) $text .= (string)$r->t;
            }
        }
        $sharedStrings[] = trim($text);
    }
}

$wbContent = $zip->getFromName('xl/workbook.xml');
$wbXml = simplexml_load_string($wbContent);
$relsContent = $zip->getFromName('xl/_rels/workbook.xml.rels');
$relsXml = simplexml_load_string($relsContent);
$sheetMap = [];
foreach ($relsXml->Relationship as $rel) {
    $sheetMap[(string)$rel['Id']] = (string)$rel['Target'];
}

$discTarget = null;
foreach ($wbXml->sheets->sheet as $sheet) {
    $sheetName = (string)$sheet['name'];
    $normalized = strtolower(str_replace([' ', '_', '-', '+', '(', ')'], '', $sheetName));
    $target = $sheetMap[(string)$sheet->attributes('r', true)['id']];
    if (strpos($normalized, 'discontinued') !== false && strpos($normalized, 'collection') !== false) {
        $discTarget = $target;
        echo "Found disc sheet: '$sheetName' => $target\n";
    }
}

if (!$discTarget) {
    die("Could not find Discontinued sheet.\n");
}

$sheetContent = $zip->getFromName('xl/' . $discTarget);
$wsXml = simplexml_load_string($sheetContent);

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
        return ['col' => $matches[1], 'row' => (int)$matches[2]];
    }
    return null;
};

foreach ($wsXml->sheetData->row as $row) {
    $rowNum = (int)$row['r'];
    $rowData = [];
    foreach ($row->c as $c) {
        $ref = (string)$c['r'];
        if (preg_match('/^([A-Z]+)([0-9]+)$/', $ref, $m)) {
            $col = $colLetterToIdx($m[1]);
            $val = '';
            if (isset($c->v)) {
                $val = (string)$c->v;
                if ((string)$c['t'] === 's') {
                    $idx = (int)$val;
                    $val = $sharedStrings[$idx] ?? '';
                }
            }
            $rowData[$col] = trim($val);
        }
    }
    $col0 = $rowData[0] ?? '';
    $col1 = $rowData[1] ?? '';
    if (stripos($col1, 'United') !== false || stripos($col1, 'United') !== false) {
        echo "Row $rowNum: Col0(discId)='$col0' | Col1(name)='$col1'\n";
    }
}

$zip->close();
