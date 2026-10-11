<?php
header('Content-Type: text/plain; charset=utf-8');

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$excelFile = "D:\\D\\Automation-Innovation\\scomm-collection\\Information for OS Automation.xlsx";

try {
    $zip = new ZipArchive();
    $zip->open($excelFile);

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

    $dbClients = DB::table('client')->get();

    // Check non-empty Legal rows in Excel
    echo "Inspecting non-empty Legal rows from Excel:\n";
    $count = 0;
    foreach ($rows as $rowNum => $row) {
        if ($rowNum <= 3) continue;
        $clientName = isset($row[0]) ? trim($row[0]) : '';
        $legalVal = isset($row[5]) ? trim($row[5]) : '';
        if (!empty($legalVal)) {
            $count++;
            $match = DB::table('client')->where('client_name', $clientName)->first();
            if (!$match) {
                // Try clean match
                $cleanName = preg_replace('/^\d+[\.\)\-_\/]+\s*/', '', $clientName);
                $cleanName = preg_replace('/\s+/', ' ', $cleanName);
                $match = DB::table('client')->where('client_name', 'like', "%$cleanName%")->first();
            }
            $dbLegal = $match ? $match->legal : 'NOT MATCHED';
            $dbName = $match ? $match->client_name : 'NOT MATCHED';
            echo "Row $rowNum | Excel Name: '$clientName' | DB Name: '$dbName' | Excel Legal: '$legalVal' | Current DB Legal: '$dbLegal'\n";
        }
    }
    echo "\nTotal Excel rows with non-empty Legal: $count\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
