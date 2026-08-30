<?php
$excelFile = "D:\\D\\Automation-Innovation\\scomm-collection\\KPI_ENGINE_FILES\\KPI ENGINE_LIVE & Discontinued ISPs_Aug'26 27th Aug'26.xlsx";
header('Content-Type: text/plain; charset=utf-8');

$zip = new ZipArchive();
if ($zip->open($excelFile) !== TRUE) die("Could not open ZIP archive.\n");

$sharedStrings = [];
$ssContent = $zip->getFromName('xl/sharedStrings.xml');
if ($ssContent !== false) {
    $xml = simplexml_load_string($ssContent);
    foreach ($xml->si as $si) {
        $text = '';
        if (isset($si->t)) $text = (string)$si->t;
        else { foreach ($si->r as $r) { if (isset($r->t)) $text .= (string)$r->t; } }
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

$liveTarget = null;
foreach ($wbXml->sheets->sheet as $sheet) {
    $normalized = strtolower(str_replace([' ', '_', '-', '+'], '', (string)$sheet['name']));
    $target = $sheetMap[(string)$sheet->attributes('r', true)['id']];
    if (strpos($normalized, 'postpaidprepaid') !== false || strpos($normalized, 'postprepaid') !== false) {
        $liveTarget = $target;
    }
}

$sheetPath = 'xl/' . $liveTarget;
$sheetContent = $zip->getFromName($sheetPath);
$wsXml = simplexml_load_string($sheetContent);

$colLetterToIdx = function($col) {
    $len = strlen($col); $idx = 0;
    for ($i = 0; $i < $len; $i++) $idx = ($idx * 26) + (ord($col[$i]) - ord('A') + 1);
    return $idx - 1;
};

foreach ($wsXml->sheetData->row as $row) {
    $rowNum = (int)$row['r'];
    if ($rowNum === 8) {
        echo "Row 8 cells:\n";
        foreach ($row->c as $c) {
            $ref = (string)$c['r'];
            $val = isset($c->v) ? (string)$c->v : '';
            $t = isset($c['t']) ? (string)$c['t'] : '';
            if ($t === 's') $val = $sharedStrings[(int)$val] ?? '';
            $colLetter = preg_replace('/[0-9]/', '', $ref);
            $colIdx = $colLetterToIdx($colLetter);
            echo "  Col $colLetter ($colIdx) | Val: '$val'\n";
        }
    }
}

$zip->close();
