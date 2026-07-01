<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
}
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

$mgtFolder = "D:\\D\\Automation-Innovation\\scomm-collection\\MGT_REPORT_FILES";

if (!is_dir($mgtFolder)) {
    die("Error: Management Reports directory not found at $mgtFolder\n");
}

$monthsMap = [
    'jan' => '01', 'feb' => '02', 'mar' => '03', 'apr' => '04',
    'may' => '05', 'jun' => '06', 'jul' => '07', 'aug' => '08',
    'sep' => '09', 'oct' => '10', 'nov' => '11', 'dec' => '12'
];

$xlsxFiles = scandir($mgtFolder);
$mgtReports = [];

foreach ($xlsxFiles as $f) {
    if (pathinfo($f, PATHINFO_EXTENSION) !== 'xlsx' || strpos($f, '~$') === 0) {
        continue;
    }
    
    // Extract month and year, e.g. July'25 or May'26
    if (preg_match('/_([A-Za-z]{3,4})\'([0-9]{2})/i', $f, $matches)) {
        $mStr = substr(strtolower($matches[1]), 0, 3);
        $yStr = $matches[2];
        if (isset($monthsMap[$mStr])) {
            $mNum = $monthsMap[$mStr];
            $yNum = "20" . $yStr;
            $dateStr = date('Y-m-t', strtotime("$yNum-$mNum-01"));
            if ($dateStr === '2026-05-31') {
                continue;
            }
            $mgtReports[$dateStr] = [
                'file' => $f,
                'path' => $mgtFolder . '\\' . $f
            ];
        }
    }
}

ksort($mgtReports);

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

$cleanName = function($name) {
    $name = trim($name);
    $name = preg_replace('/^\d+[\.\)\-_\/]+\s*/', '', $name);
    $name = preg_replace('/\s+/', ' ', $name);
    return trim($name);
};

$detailMonth = isset($_GET['month']) ? trim($_GET['month']) : null; // e.g. 2025-07-31

echo "=== DATABASE vs MANAGEMENT REPORT COMPARISON ===\n\n";

foreach ($mgtReports as $month => $reportInfo) {
    if ($detailMonth && $month !== $detailMonth) {
        continue;
    }
    
    $file = $reportInfo['file'];
    $path = $reportInfo['path'];
    
    $zip = new ZipArchive();
    if ($zip->open($path) !== TRUE) {
        echo "Error: Could not open file $file\n\n";
        continue;
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
    
    // Load Workbook Sheets
    $wbContent = $zip->getFromName('xl/workbook.xml');
    $wbXml = simplexml_load_string($wbContent);
    $relsContent = $zip->getFromName('xl/_rels/workbook.xml.rels');
    $relsXml = simplexml_load_string($relsContent);
    $sheetMap = [];
    foreach ($relsXml->Relationship as $rel) {
        $sheetMap[(string)$rel['Id']] = (string)$rel['Target'];
    }
    
    $targetPathActive = null;
    $targetPathDisc = null;
    foreach ($wbXml->sheets->sheet as $sheet) {
        $sheetNameClean = strtolower(trim((string)$sheet['name']));
        if ($sheetNameClean === 'raw data') {
            $rId = '';
            $attrs = $sheet->attributes('r', true);
            if (isset($attrs['id'])) {
                $rId = (string)$attrs['id'];
            }
            $targetPathActive = isset($sheetMap[$rId]) ? $sheetMap[$rId] : null;
        } elseif ($sheetNameClean === 'discontinued') {
            $rId = '';
            $attrs = $sheet->attributes('r', true);
            if (isset($attrs['id'])) {
                $rId = (string)$attrs['id'];
            }
            $targetPathDisc = isset($sheetMap[$rId]) ? $sheetMap[$rId] : null;
        }
    }
    
    // Parse Active Clients
    $mgtClientsActive = [];
    $mgtSumMRCActive = 0.0;
    $mgtSumOpeningOSActive = 0.0;
    $mgtSumCollectionActive = 0.0;
    $mgtSumLatestOSActive = 0.0;
    
    if ($targetPathActive) {
        $sheetContentActive = $zip->getFromName('xl/' . $targetPathActive);
        $wsXmlActive = simplexml_load_string($sheetContentActive);
        
        $headerRow = null;
        foreach ($wsXmlActive->sheetData->row as $row) {
            $rowNum = (int)$row['r'];
            if ($rowNum > 10) break;
            
            $hasClientName = false;
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
                if (strpos(strtolower(trim($val)), 'client name') !== false) {
                    $hasClientName = true;
                    break;
                }
            }
            
            if ($hasClientName) {
                $headerRow = $rowNum;
                break;
            }
        }
        
        if ($headerRow) {
            if ($headerRow === 2) {
                $colMap = [
                    'name' => 1,       // B
                    'mrc' => 9,        // J
                    'opening_os' => 10, // K
                    'collection' => 13, // N
                    'latest_os' => 14,  // O
                ];
            } else {
                $colMap = [
                    'name' => 1,       // B
                    'mrc' => 13,       // N
                    'opening_os' => 14, // O
                    'collection' => 17, // R
                    'latest_os' => 23,  // X
                ];
            }
            
            foreach ($wsXmlActive->sheetData->row as $row) {
                $rowNum = (int)$row['r'];
                if ($rowNum <= $headerRow) continue;
                
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
                
                $cName = isset($rowData[$colMap['name']]) ? trim($rowData[$colMap['name']]) : '';
                if (empty($cName) || strpos(strtolower($cName), 'total') !== false || strpos(strtolower($cName), 'grand') !== false) {
                    continue;
                }
                
                $cleaned = $cleanName($cName);
                $mrc = isset($rowData[$colMap['mrc']]) && is_numeric($rowData[$colMap['mrc']]) ? (float)$rowData[$colMap['mrc']] : 0.0;
                $openingOS = isset($rowData[$colMap['opening_os']]) && is_numeric($rowData[$colMap['opening_os']]) ? (float)$rowData[$colMap['opening_os']] : 0.0;
                $collection = isset($rowData[$colMap['collection']]) && is_numeric($rowData[$colMap['collection']]) ? (float)$rowData[$colMap['collection']] : 0.0;
                $latestOS = isset($rowData[$colMap['latest_os']]) && is_numeric($rowData[$colMap['latest_os']]) ? (float)$rowData[$colMap['latest_os']] : 0.0;
                
                $mgtClientsActive[$cleaned] = [
                    'name' => $cleaned,
                    'original_name' => $cName,
                    'mrc' => $mrc,
                    'opening_os' => $openingOS,
                    'collection' => $collection,
                    'latest_os' => $latestOS
                ];
                
                $mgtSumMRCActive += $mrc;
                $mgtSumOpeningOSActive += $openingOS;
                $mgtSumCollectionActive += $collection;
                $mgtSumLatestOSActive += $latestOS;
            }
        }
    }
    
    // Parse Discontinued Clients
    $mgtClientsDisc = [];
    $mgtSumOpeningOSDisc = 0.0;
    $mgtSumCollectionDisc = 0.0;
    $mgtSumLatestOSDisc = 0.0;
    
    if ($targetPathDisc) {
        $sheetContentDisc = $zip->getFromName('xl/' . $targetPathDisc);
        $wsXmlDisc = simplexml_load_string($sheetContentDisc);
        
        foreach ($wsXmlDisc->sheetData->row as $row) {
            $rowNum = (int)$row['r'];
            if ($rowNum <= 2) continue; // Skip header rows (1 & 2)
            
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
            
            $cName = isset($rowData[1]) ? trim($rowData[1]) : ''; // Col B
            if (empty($cName) || strpos(strtolower($cName), 'total') !== false || strpos(strtolower($cName), 'grand') !== false) {
                continue;
            }
            
            $cleaned = $cleanName($cName);
            $openingOS = isset($rowData[11]) && is_numeric($rowData[11]) ? (float)$rowData[11] : 0.0; // Col L
            $collection = isset($rowData[17]) && is_numeric($rowData[17]) ? (float)$rowData[17] : 0.0; // Col R
            $latestOS = isset($rowData[19]) && is_numeric($rowData[19]) ? (float)$rowData[19] : 0.0; // Col T
            
            $mgtClientsDisc[$cleaned] = [
                'name' => $cleaned,
                'original_name' => $cName,
                'opening_os' => $openingOS,
                'collection' => $collection,
                'latest_os' => $latestOS
            ];
            
            $mgtSumOpeningOSDisc += $openingOS;
            $mgtSumCollectionDisc += $collection;
            $mgtSumLatestOSDisc += $latestOS;
        }
    }
    
    $zip->close();
    
    // Get DB Client-level Data for active clients
    $dbDataActive = DB::table('monthly_summary')
        ->join('client', 'monthly_summary.client_id', '=', 'client.client_id')
        ->where('monthly_summary.summary_month', $month)
        ->select(
            'client.client_name',
            'monthly_summary.total_opening_os',
            'monthly_summary.total_mrc',
            'monthly_summary.total_collection',
            'monthly_summary.total_latest_os'
        )
        ->get();
        
    $dbClientsActive = [];
    $dbSumMRCActive = 0.0;
    $dbSumOpeningOSActive = 0.0;
    $dbSumCollectionActive = 0.0;
    $dbSumLatestOSActive = 0.0;
    
    foreach ($dbDataActive as $row) {
        $cleaned = $cleanName($row->client_name);
        $dbClientsActive[$cleaned] = [
            'name' => $cleaned,
            'original_name' => $row->client_name,
            'mrc' => (float)$row->total_mrc,
            'opening_os' => (float)$row->total_opening_os,
            'collection' => (float)$row->total_collection,
            'latest_os' => (float)$row->total_latest_os
        ];
        
        $dbSumMRCActive += (float)$row->total_mrc;
        $dbSumOpeningOSActive += (float)$row->total_opening_os;
        $dbSumCollectionActive += (float)$row->total_collection;
        $dbSumLatestOSActive += (float)$row->total_latest_os;
    }
    
    // Get DB Client-level Data for discontinued clients
    $dbDataDisc = DB::table('monthly_summary_discontinued')
        ->join('client', 'monthly_summary_discontinued.client_id', '=', 'client.client_id')
        ->where('monthly_summary_discontinued.summary_month', $month)
        ->select(
            'client.client_name',
            'monthly_summary_discontinued.opening_os',
            'monthly_summary_discontinued.collection_amount',
            'monthly_summary_discontinued.latest_os'
        )
        ->get();
        
    $dbClientsDisc = [];
    $dbSumOpeningOSDisc = 0.0;
    $dbSumCollectionDisc = 0.0;
    $dbSumLatestOSDisc = 0.0;
    
    foreach ($dbDataDisc as $row) {
        $cleaned = $cleanName($row->client_name);
        $dbClientsDisc[$cleaned] = [
            'name' => $cleaned,
            'original_name' => $row->client_name,
            'opening_os' => (float)$row->opening_os,
            'collection' => (float)$row->collection_amount,
            'latest_os' => (float)$row->latest_os
        ];
        
        $dbSumOpeningOSDisc += (float)$row->opening_os;
        $dbSumCollectionDisc += (float)$row->collection_amount;
        $dbSumLatestOSDisc += (float)$row->latest_os;
    }
    
    // Display Monthly Aggregates
    echo "Month: $month ($file)\n";
    echo "======================================================================\n";
    echo "ACTIVE CLIENTS\n";
    echo "----------------------------------------------------------------------\n";
    echo "Metric            | Management Report | Database           | Difference\n";
    echo "----------------------------------------------------------------------\n";
    
    $diffClientsActive = count($mgtClientsActive) - count($dbClientsActive);
    $diffOpeningOSActive = $mgtSumOpeningOSActive - $dbSumOpeningOSActive;
    $diffMRCActive = $mgtSumMRCActive - $dbSumMRCActive;
    $diffCollectionActive = $mgtSumCollectionActive - $dbSumCollectionActive;
    $diffLatestOSActive = $mgtSumLatestOSActive - $dbSumLatestOSActive;
    
    printf("Clients Count     | %17d | %18d | %10d\n", count($mgtClientsActive), count($dbClientsActive), $diffClientsActive);
    printf("Sum Opening OS    | %17.2f | %18.2f | %10.2f\n", $mgtSumOpeningOSActive, $dbSumOpeningOSActive, $diffOpeningOSActive);
    printf("Sum Latest MRC    | %17.2f | %18.2f | %10.2f\n", $mgtSumMRCActive, $dbSumMRCActive, $diffMRCActive);
    printf("Sum Collections   | %17.2f | %18.2f | %10.2f\n", $mgtSumCollectionActive, $dbSumCollectionActive, $diffCollectionActive);
    printf("Sum Latest OS     | %17.2f | %18.2f | %10.2f\n", $mgtSumLatestOSActive, $dbSumLatestOSActive, $diffLatestOSActive);
    echo "----------------------------------------------------------------------\n\n";

    echo "DISCONTINUED CLIENTS\n";
    echo "----------------------------------------------------------------------\n";
    echo "Metric            | Management Report | Database           | Difference\n";
    echo "----------------------------------------------------------------------\n";
    
    $diffClientsDisc = count($mgtClientsDisc) - count($dbClientsDisc);
    $diffOpeningOSDisc = $mgtSumOpeningOSDisc - $dbSumOpeningOSDisc;
    $diffCollectionDisc = $mgtSumCollectionDisc - $dbSumCollectionDisc;
    $diffLatestOSDisc = $mgtSumLatestOSDisc - $dbSumLatestOSDisc;
    
    printf("Clients Count     | %17d | %18d | %10d\n", count($mgtClientsDisc), count($dbClientsDisc), $diffClientsDisc);
    printf("Sum Opening OS    | %17.2f | %18.2f | %10.2f\n", $mgtSumOpeningOSDisc, $dbSumOpeningOSDisc, $diffOpeningOSDisc);
    printf("Sum Collections   | %17.2f | %18.2f | %10.2f\n", $mgtSumCollectionDisc, $dbSumCollectionDisc, $diffCollectionDisc);
    printf("Sum Latest OS     | %17.2f | %18.2f | %10.2f\n", $mgtSumLatestOSDisc, $dbSumLatestOSDisc, $diffLatestOSDisc);
    echo "----------------------------------------------------------------------\n\n";
    
    // Detailed client discrepancies
    if ($detailMonth) {
        echo "=== CLIENT-LEVEL DISCREPANCIES (ACTIVE) FOR $month ===\n";
        
        // 1. Missing in DB but present in Mgt Report
        $missingInDb = [];
        foreach ($mgtClientsActive as $name => $mClient) {
            if (!isset($dbClientsActive[$name])) {
                $missingInDb[] = $mClient;
            }
        }
        
        if (!empty($missingInDb)) {
            echo "\nMissing in Database (Present in Management Report):\n";
            echo "  Client Name                 | Opening OS | Latest MRC | Collection | Latest OS\n";
            foreach ($missingInDb as $c) {
                printf("  %-27s | %10.2f | %10.2f | %10.2f | %10.2f\n", substr($c['name'], 0, 27), $c['opening_os'], $c['mrc'], $c['collection'], $c['latest_os']);
            }
        }
        
        // 2. Missing in Mgt Report but present in DB
        $missingInMgt = [];
        foreach ($dbClientsActive as $name => $dbClient) {
            if (!isset($mgtClientsActive[$name])) {
                $missingInMgt[] = $dbClient;
            }
        }
        
        if (!empty($missingInMgt)) {
            echo "\nMissing in Management Report (Present in Database):\n";
            echo "  Client Name                 | Opening OS | Latest MRC | Collection | Latest OS\n";
            foreach ($missingInMgt as $c) {
                printf("  %-27s | %10.2f | %10.2f | %10.2f | %10.2f\n", substr($c['name'], 0, 27), $c['opening_os'], $c['mrc'], $c['collection'], $c['latest_os']);
            }
        }
        
        // 3. Numeric differences for matching clients
        $numericDiffs = [];
        foreach ($mgtClientsActive as $name => $mClient) {
            if (isset($dbClientsActive[$name])) {
                $dbClient = $dbClientsActive[$name];
                
                $diffOp = abs($mClient['opening_os'] - $dbClient['opening_os']);
                $diffMrc = abs($mClient['mrc'] - $dbClient['mrc']);
                $diffColl = abs($mClient['collection'] - $dbClient['collection']);
                $diffLat = abs($mClient['latest_os'] - $dbClient['latest_os']);
                
                if ($diffOp > 0.05 || $diffMrc > 0.05 || $diffColl > 0.05 || $diffLat > 0.05) {
                    $numericDiffs[] = [
                        'name' => $name,
                        'mgt' => $mClient,
                        'db' => $dbClient
                    ];
                }
            }
        }
        
        if (!empty($numericDiffs)) {
            echo "\nNumeric Discrepancies (Mgt Report vs Database):\n";
            foreach ($numericDiffs as $d) {
                echo "  Client: '{$d['name']}'\n";
                echo "    Opening OS:  Mgt: " . number_format($d['mgt']['opening_os'], 2) . " | DB: " . number_format($d['db']['opening_os'], 2) . " | Diff: " . number_format($d['mgt']['opening_os'] - $d['db']['opening_os'], 2) . "\n";
                echo "    Latest MRC:  Mgt: " . number_format($d['mgt']['mrc'], 2) . " | DB: " . number_format($d['db']['mrc'], 2) . " | Diff: " . number_format($d['mgt']['mrc'] - $d['db']['mrc'], 2) . "\n";
                echo "    Collection:  Mgt: " . number_format($d['mgt']['collection'], 2) . " | DB: " . number_format($d['db']['collection'], 2) . " | Diff: " . number_format($d['mgt']['collection'] - $d['db']['collection'], 2) . "\n";
                echo "    Latest OS:   Mgt: " . number_format($d['mgt']['latest_os'], 2) . " | DB: " . number_format($d['db']['latest_os'], 2) . " | Diff: " . number_format($d['mgt']['latest_os'] - $d['db']['latest_os'], 2) . "\n";
                echo "\n";
            }
        } else {
            echo "\nNo numeric discrepancies found for matching active clients!\n";
        }

        echo "\n=== CLIENT-LEVEL DISCREPANCIES (DISCONTINUED) FOR $month ===\n";
        
        // 1. Missing in DB but present in Mgt Report
        $missingInDbDisc = [];
        foreach ($mgtClientsDisc as $name => $mClient) {
            if (!isset($dbClientsDisc[$name])) {
                $missingInDbDisc[] = $mClient;
            }
        }
        
        if (!empty($missingInDbDisc)) {
            echo "\nMissing in Database (Present in Management Report):\n";
            echo "  Client Name                 | Opening OS | Collection | Latest OS\n";
            foreach ($missingInDbDisc as $c) {
                printf("  %-27s | %10.2f | %10.2f | %10.2f\n", substr($c['name'], 0, 27), $c['opening_os'], $c['collection'], $c['latest_os']);
            }
        }
        
        // 2. Missing in Mgt Report but present in DB
        $missingInMgtDisc = [];
        foreach ($dbClientsDisc as $name => $dbClient) {
            if (!isset($mgtClientsDisc[$name])) {
                $missingInMgtDisc[] = $dbClient;
            }
        }
        
        if (!empty($missingInMgtDisc)) {
            echo "\nMissing in Management Report (Present in Database):\n";
            echo "  Client Name                 | Opening OS | Collection | Latest OS\n";
            foreach ($missingInMgtDisc as $c) {
                printf("  %-27s | %10.2f | %10.2f | %10.2f\n", substr($c['name'], 0, 27), $c['opening_os'], $c['collection'], $c['latest_os']);
            }
        }
        
        // 3. Numeric differences for matching clients
        $numericDiffsDisc = [];
        foreach ($mgtClientsDisc as $name => $mClient) {
            if (isset($dbClientsDisc[$name])) {
                $dbClient = $dbClientsDisc[$name];
                
                $diffOp = abs($mClient['opening_os'] - $dbClient['opening_os']);
                $diffColl = abs($mClient['collection'] - $dbClient['collection']);
                $diffLat = abs($mClient['latest_os'] - $dbClient['latest_os']);
                
                if ($diffOp > 0.05 || $diffColl > 0.05 || $diffLat > 0.05) {
                    $numericDiffsDisc[] = [
                        'name' => $name,
                        'mgt' => $mClient,
                        'db' => $dbClient
                    ];
                }
            }
        }
        
        if (!empty($numericDiffsDisc)) {
            echo "\nNumeric Discrepancies (Mgt Report vs Database):\n";
            foreach ($numericDiffsDisc as $d) {
                echo "  Client: '{$d['name']}'\n";
                echo "    Opening OS:  Mgt: " . number_format($d['mgt']['opening_os'], 2) . " | DB: " . number_format($d['db']['opening_os'], 2) . " | Diff: " . number_format($d['mgt']['opening_os'] - $d['db']['opening_os'], 2) . "\n";
                echo "    Collection:  Mgt: " . number_format($d['mgt']['collection'], 2) . " | DB: " . number_format($d['db']['collection'], 2) . " | Diff: " . number_format($d['mgt']['collection'] - $d['db']['collection'], 2) . "\n";
                echo "    Latest OS:   Mgt: " . number_format($d['mgt']['latest_os'], 2) . " | DB: " . number_format($d['db']['latest_os'], 2) . " | Diff: " . number_format($d['mgt']['latest_os'] - $d['db']['latest_os'], 2) . "\n";
                echo "\n";
            }
        } else {
            echo "\nNo numeric discrepancies found for matching discontinued clients!\n";
        }
    }
}
