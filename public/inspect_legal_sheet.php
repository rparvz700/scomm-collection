<?php
header('Content-Type: text/plain; charset=utf-8');

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$excelFile = "D:\\D\\Automation-Innovation\\scomm-collection\\Information for OS Automation.xlsx";

try {
    $zip = new ZipArchive();
    $zip->open($excelFile);

    // Get workbook XML to find sheet target
    $wbContent = $zip->getFromName('xl/workbook.xml');
    $wbXml = simplexml_load_string($wbContent);
    $relsContent = $zip->getFromName('xl/_rels/workbook.xml.rels');
    $relsXml = simplexml_load_string($relsContent);
    $sheetMap = [];
    foreach ($relsXml->Relationship as $rel) {
        $sheetMap[(string)$rel['Id']] = (string)$rel['Target'];
    }

    $targetPath = null;
    foreach ($wbXml->sheets->sheet as $sheet) {
        if ((string)$sheet['name'] === 'Discontinued and Legal') {
            $rId = (string)$sheet->attributes('r', true)['id'];
            $targetPath = $sheetMap[$rId];
        }
    }

    // Load shared strings
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

    $sheetPath = 'xl/' . $targetPath;
    $sheetContent = $zip->getFromName($sheetPath);
    $wsXml = simplexml_load_string($sheetContent);

    // Print headers in row 2 or 1
    $rows = [];
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
                if (isset($c['t']) && (string)$c['t'] === 's') {
                    $idx = (int)$val;
                    $val = isset($sharedStrings[$idx]) ? $sharedStrings[$idx] : '';
                }
            }
            $rowData[$colIdx] = trim($val);
        }
        $rows[$rowNum] = $rowData;
    }

    $zip->close();

    echo "First 5 rows of sheet 'Discontinued and Legal':\n";
    for ($i = 1; $i <= 5; $i++) {
        if (isset($rows[$i])) {
            echo "Row $i:\n";
            print_r($rows[$i]);
        }
    }

    // Find header for Client Name and Legal
    $headerRow = null;
    $clientNameCol = null;
    $legalCol = null;
    foreach ($rows as $rowNum => $row) {
        foreach ($row as $colIdx => $val) {
            $valClean = strtolower(trim($val));
            if ($valClean === 'client name') {
                $clientNameCol = $colIdx;
                $headerRow = $rowNum;
            } elseif ($valClean === 'legal' || $valClean === 'legal status' || $valClean === 'legal notice') {
                $legalCol = $colIdx;
            }
        }
        if ($clientNameCol !== null) break;
    }

    echo "\nHeader Row: $headerRow | Client Name Col: $clientNameCol | Legal Col: $legalCol\n\n";

    if ($headerRow && $clientNameCol !== null && $legalCol !== null) {
        echo "Sample Legal values:\n";
        $maxLength = 0;
        $count = 0;
        foreach ($rows as $rowNum => $row) {
            if ($rowNum <= $headerRow) continue;
            $clientName = isset($row[$clientNameCol]) ? trim($row[$clientNameCol]) : '';
            $legalVal = isset($row[$legalCol]) ? trim($row[$legalCol]) : '';
            if (!empty($clientName)) {
                $len = strlen($legalVal);
                if ($len > $maxLength) $maxLength = $len;
                if (!empty($legalVal)) {
                    $count++;
                    if ($count <= 20) {
                        echo " - Client: '$clientName' | Legal: '$legalVal' (length: $len)\n";
                    }
                }
            }
        }
        echo "\nTotal rows with Legal values: $count | Max text length: $maxLength\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
