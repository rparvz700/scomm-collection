<?php
header('Content-Type: text/plain; charset=utf-8');

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$excelFile = "D:\\D\\Automation-Innovation\\scomm-collection\\KPI_ENGINE_FILES\\KPI ENGINE_LIVE & Discontinued ISPs_Aug'26 27th Aug'26.xlsx";

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
        if ((string)$sheet['name'] === 'Post-Paid+Pre-Paid') {
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

    $sheetPath = 'xl/' . $targetPath;
    $sheetContent = $zip->getFromName($sheetPath);
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

    // Print headers in row 2
    $headers = [];
    foreach ($wsXml->sheetData->row as $row) {
        $rowNum = (int)$row['r'];
        if ($rowNum === 2) {
            foreach ($row->c as $c) {
                $ref = (string)$c['r'];
                preg_match('/^([A-Z]+)/', $ref, $m);
                $col = $m[1];
                $val = '';
                if (isset($c->v)) {
                    $val = (string)$c->v;
                    if (isset($c['t']) && (string)$c['t'] === 's') {
                        $idx = (int)$val;
                        $val = isset($sharedStrings[$idx]) ? $sharedStrings[$idx] : '';
                    }
                }
                $headers[$colLetterToIdx($col)] = $val;
            }
            break;
        }
    }

    // Print details of row 8
    foreach ($wsXml->sheetData->row as $row) {
        $rowNum = (int)$row['r'];
        if ($rowNum === 8) {
            echo "Row 8 Cells:\n";
            foreach ($row->c as $c) {
                $ref = (string)$c['r'];
                preg_match('/^([A-Z]+)/', $ref, $m);
                $col = $m[1];
                $colIdx = $colLetterToIdx($col);
                $headerName = isset($headers[$colIdx]) ? $headers[$colIdx] : 'Unknown';
                
                $val = '';
                if (isset($c->v)) {
                    $val = (string)$c->v;
                    if (isset($c['t']) && (string)$c['t'] === 's') {
                        $idx = (int)$val;
                        $val = isset($sharedStrings[$idx]) ? $sharedStrings[$idx] : '';
                    }
                }
                echo "  $col ($headerName): '$val'\n";
            }
            break;
        }
    }

    $zip->close();
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
