<?php
// PHP Importer script booted inside Laravel context to batch import monthly Excel files.
ignore_user_abort(true);
set_time_limit(0);
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();


use Illuminate\Support\Facades\DB;

// Folder configuration
$folder = isset($_GET['folder']) ? trim($_GET['folder']) : "D:\\D\\Automation-Innovation\\scomm-collection\\KPI_ENGINE_FILES";
$folder = str_replace('/', '\\', $folder);

if (!is_dir($folder)) {
    header('Content-Type: text/plain; charset=utf-8');
    die("Error: Folder not found at $folder\n");
}

$allFiles = scandir($folder);
$filesToImport = [];

$monthsMap = [
    'jan' => '01', 'feb' => '02', 'mar' => '03', 'apr' => '04',
    'may' => '05', 'jun' => '06', 'jul' => '07', 'aug' => '08',
    'sep' => '09', 'oct' => '10', 'nov' => '11', 'dec' => '12'
];

foreach ($allFiles as $f) {
    if (pathinfo($f, PATHINFO_EXTENSION) !== 'xlsx' || strpos($f, '~$') === 0) {
        continue;
    }
    
    // Extract month name and year (supporting formats like _Jun'25_ or _Jun'25)
    if (preg_match('/_([A-Za-z]{3,4})\'([0-9]{2})/i', $f, $matches)) {
        $mStr = strtolower(substr($matches[1], 0, 3));
        $yStr = $matches[2];
        
        if (isset($monthsMap[$mStr])) {
            $mNum = $monthsMap[$mStr];
            $yNum = "20" . $yStr;
            $dateStr = date('Y-m-t', strtotime("$yNum-$mNum-01"));
            $filesToImport[$dateStr] = $folder . '\\' . $f;
        }
    }
}

// Sort chronologically by target month (ascending)
ksort($filesToImport);

// Support single-month filtering
$monthFilter = isset($_GET['month']) ? trim($_GET['month']) : null;
if ($monthFilter) {
    $filtered = [];
    if (isset($filesToImport[$monthFilter])) {
        $filtered[$monthFilter] = $filesToImport[$monthFilter];
    }
    $filesToImport = $filtered;
}

// Support running from CLI
if (php_sapi_name() === 'cli') {
    $_GET['ajax'] = 1;
    $_GET['file'] = isset($argv[1]) ? $argv[1] : null;
}

// AJAX Endpoint: Process a single file
if (isset($_GET['ajax']) && isset($_GET['file'])) {
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json; charset=utf-8');
    }
    
        // Lock check disabled for programmatic execution
    
    $fileToProcess = trim($_GET['file']);
    $excelFile = $folder . '\\' . $fileToProcess;
    
    $summaryMonth = null;
    foreach ($filesToImport as $date => $path) {
        if (basename($path) === $fileToProcess) {
            $summaryMonth = $date;
            break;
        }
    }
    
    if (!$summaryMonth || !file_exists($excelFile)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file name or file not found.']);
        exit;
    }
    
    $zip = new ZipArchive();
    if ($zip->open($excelFile) !== TRUE) {
        echo json_encode(['success' => false, 'message' => 'Could not open ZIP archive.']);
        exit;
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

    // Helper: Read a sheet into a clean row-by-row array
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

    // Helper: Clean client name to strip leading numeric prefix (with punctuation) and extra spacing
    $cleanClientName = function($name) {
        $name = trim($name);
        $name = preg_replace('/^\d+[\.\)\-_\/]+\s*/', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return trim($name);
    };

    // Helper: Convert Excel date (serial number) to YYYY-MM-DD
    $excelDateToPhp = function($serial) {
        if (empty($serial) || !is_numeric($serial)) {
            return null;
        }
        $serial = (int)$serial;
        if ($serial > 60) {
            $serial--;
        }
        $utcDays = $serial - 25568;
        return date('Y-m-d', $utcDays * 86400);
    };

    // Helper: Determine rating category based on CR ranges
    $getRatingCategory = function($cr) {
        if ($cr === null || !is_numeric($cr)) {
            return null;
        }
        $cr = (float)$cr;
        $ranges = config('risk.ranges') ?: [
            ['label' => '0.00 - 1.50', 'min' => 0, 'max' => 1.50, 'category' => 'Best'],
            ['label' => '1.51 - 2.00', 'min' => 1.51, 'max' => 2.00, 'category' => 'Good'],
            ['label' => '2.01 - 2.50', 'min' => 2.01, 'max' => 2.50, 'category' => 'Moderate'],
            ['label' => '2.51 - 2.99', 'min' => 2.51, 'max' => 2.99, 'category' => 'Risky'],
            ['label' => '3.00 - 3.49', 'min' => 3.00, 'max' => 3.49, 'category' => 'High Risky'],
            ['label' => '>= 3.50', 'min' => 3.50, 'max' => null, 'category' => 'Most Risky'],
        ];

        foreach ($ranges as $range) {
            $min = isset($range['min']) ? (float)$range['min'] : null;
            $max = isset($range['max']) ? (float)$range['max'] : null;
            if ($min === 0.0) {
                if ($cr <= $max) {
                    return $range['category'];
                }
            } else {
                if ($cr >= $min && ($max === null || $cr <= $max)) {
                    return $range['category'];
                }
            }
        }
        return null;
    };
    
    try {
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
        $sheetTargets = [
            'Post-Paid+Pre-Paid' => null,
            'Discontinued (Collection)' => null,
            'LIVE_Post-Paid (Collection)' => null,
            'LIVE_Pre-Paid (Billing)' => null
        ];
        
        foreach ($sheets as $s) {
            $normalized = strtolower(str_replace([' ', '_', '-', '+'], '', $s['name']));
            if (strpos($normalized, 'postpaidprepaid') !== false || strpos($normalized, 'postprepaid') !== false) {
                $sheetTargets['Post-Paid+Pre-Paid'] = $s['target'];
            } elseif (strpos($normalized, 'discontinued') !== false) {
                $sheetTargets['Discontinued (Collection)'] = $s['target'];
            } elseif (strpos($normalized, 'livepostpaid') !== false) {
                $sheetTargets['LIVE_Post-Paid (Collection)'] = $s['target'];
            } elseif (strpos($normalized, 'liveprepaid') !== false) {
                $sheetTargets['LIVE_Pre-Paid (Billing)'] = $s['target'];
            }
        }
        
        // Verify sheets exist
        $missingSheets = [];
        foreach ($sheetTargets as $name => $target) {
            if (!$target) {
                $missingSheets[] = $name;
            }
        }
        if (!empty($missingSheets)) {
            echo json_encode(['success' => false, 'message' => 'Missing sheets: ' . implode(', ', $missingSheets)]);
            $zip->close();
            exit;
        }
        
        // 4. Load Client Cache from database
        $existingClients = DB::table('client')->get();
        
        // Load Residue Mapping Cache
        $mappingsRaw = DB::table('residue_client_mapping')->get();
        $residueMappings = [];
        foreach ($mappingsRaw as $m) {
            $key = strtolower(trim($m->client_name));
            $residueMappings[$key] = trim($m->c_client_name);
        }
        
        $findClient = function($excelName, $excelOpus) use ($existingClients, $cleanClientName, $residueMappings) {
            $cleanedExcelName = $cleanClientName($excelName);
            if (empty($cleanedExcelName)) {
                return null;
            }

            // 1. Exact Match by Name AND Opus ID
            foreach ($existingClients as $c) {
                $cleanedDbName = $cleanClientName($c->client_name);
                if (strtolower($cleanedDbName) === strtolower($cleanedExcelName) && strval($c->opus_id) === strval($excelOpus)) {
                    return $c;
                }
            }

            // 2. Exact Match by Name only (cleaned)
            foreach ($existingClients as $c) {
                $cleanedDbName = $cleanClientName($c->client_name);
                if (strtolower($cleanedDbName) === strtolower($cleanedExcelName)) {
                    return $c;
                }
            }

            // 3. Similarity Match: if name is not found exactly, match by opus_id where name similarity is > 95%
            if (!empty($excelOpus)) {
                foreach ($existingClients as $c) {
                    if (strval($c->opus_id) === strval($excelOpus)) {
                        $cleanedDbName = $cleanClientName($c->client_name);
                        similar_text(strtolower($cleanedExcelName), strtolower($cleanedDbName), $percent);
                        if ($percent > 95.0) {
                            return $c;
                        }
                    }
                }
            }

            // 4. Residue Mapping Translation Match
            $key = strtolower($cleanedExcelName);
            if (isset($residueMappings[$key])) {
                $mappedName = $residueMappings[$key];
                $cleanedMappedName = $cleanClientName($mappedName);
                foreach ($existingClients as $c) {
                    $cleanedDbName = $cleanClientName($c->client_name);
                    if (strtolower($cleanedDbName) === strtolower($cleanedMappedName)) {
                        return $c;
                    }
                }
            }

            return null; // Unmatched
        };

        $ignoredStatusChangeIds = [
            106, 679, 942, 916, 595, 623, 11, 1064, 133, 959, 1009, 1022, 273, 1069, 419, 1080, 1026, 994, 965, 981, 1084, 445, 458, 1050, 1000, 520, 560, 592
        ];
        
        $ignoredStatusChangeNames = [
            'central net broadband network', 'drik ict limited', 'ideal network', 'pioneer services limited',
            'tamim net service', 'united communications & service', 'united communications & service ltd',
            'united communication services', 'united communication services ltd.', 'adel online', 'adel online technology',
            'cyber solutions bd', 'cybernet communications', 'cyber net communication', 'explore online',
            'green net city', 'idea tec ltd.', 'it link', 'jbh net', 'm/s net zone', 'net zone', 'maxtop tech',
            'modhumoti internet service', 'net relation', 'network solution', 'our online', 'paradise technologies ltd.',
            'proton communication', 'rodela online', 'royal green ltd.', 'royal green limited', 'shahrasti broadband service',
            'speed plus', 'talha café', 'talha cafe'
        ];

        $updateClientStatusIfNeeded = function($client, $newStatus) use ($ignoredStatusChangeIds, $ignoredStatusChangeNames) {
            $clientNameLower = strtolower(trim($client->client_name));
            if (in_array($client->client_id, $ignoredStatusChangeIds) || in_array($clientNameLower, $ignoredStatusChangeNames)) {
                return; // Do not update status for these clients
            }
            if ($client->client_status !== $newStatus) {
                // Log status change
                \App\Models\ClientLog::create([
                    'client_id'  => $client->client_id,
                    'field_name' => 'client_status',
                    'old_value'  => $client->client_status,
                    'new_value'  => $newStatus,
                    'updated_by' => 'System',
                ]);
                
                // Update client status ONLY
                DB::table('client')->where('client_id', $client->client_id)->update([
                    'client_status' => $newStatus,
                    'updated_at'    => now()
                ]);
                
                $client->client_status = $newStatus;
            }
        };
        
        // Start Database Transaction for this file
        DB::beginTransaction();

        // Clear existing summary, collection, and residue data for this month to prevent duplicates
        DB::table('monthly_summary')->where('summary_month', $summaryMonth)->delete();
        DB::table('monthly_summary_discontinued')->where('summary_month', $summaryMonth)->delete();
        DB::table('monthly_summary_residue')->where('summary_month', $summaryMonth)->delete();
        DB::table('monthly_summary_discontinued_residue')->where('summary_month', $summaryMonth)->delete();
        DB::table('collection')->where('collection_month', $summaryMonth)->delete();
        
        $matchedActiveClientIds = [];
        
        // PHASE 1: Parse Post-Paid+Pre-Paid Worksheet (Live Client Data)
        $parsedLiveClients = [];
        $liveNameToParsedIndex = [];
        
        $liveRows = $readSheet($zip, $sheetTargets['Post-Paid+Pre-Paid'], $sharedStrings);
        $liveImportCount = 0;
        
        foreach ($liveRows as $rowNum => $row) {
            if ($rowNum < 3) continue; // Headers
            
            $opusId = isset($row[0]) ? trim($row[0]) : '';
            $rawClientName = isset($row[1]) ? trim($row[1]) : '';
            if (empty($opusId) || empty($rawClientName) || !ctype_digit($opusId)) {
                continue; // Skip invalid rows
            }
            
            $clientName = $cleanClientName($rawClientName);
            
            // Match client
            $client = $findClient($clientName, $opusId);
            if (!$client && isset($_GET['create_clients']) && $_GET['create_clients'] == 1) {
                $serviceType = isset($row[3]) ? trim($row[3]) : null;
                $licenseBilling = isset($row[5]) ? trim($row[5]) : null;
                $smKam = isset($row[65]) ? trim($row[65]) : null;
                $teamName = isset($row[66]) ? trim($row[66]) : null;
                $collectionKam = isset($row[67]) ? trim($row[67]) : null;
                $collectionSupervisor = isset($row[68]) ? trim($row[68]) : null;
                $nttnKam = isset($row[69]) ? trim($row[69]) : null;
                $iigKam = isset($row[70]) ? trim($row[70]) : null;

                $opusIdToSave = (empty($opusId) || strtolower($opusId) === 'n/a') ? null : $opusId;
                $newId = DB::table('client')->insertGetId([
                    'opus_id' => $opusIdToSave,
                    'client_name' => $clientName,
                    'client_status' => 'Active',
                    'service_type_billing' => $serviceType,
                    'license_billing' => $licenseBilling,
                    'sm_kam' => $smKam,
                    'team_name' => $teamName,
                    'collection_kam' => $collectionKam,
                    'collection_supervisor' => $collectionSupervisor,
                    'nttn_billing_kam' => $nttnKam,
                    'iig_itc_billing_kam' => $iigKam,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $client = DB::table('client')->where('client_id', $newId)->first();
                $existingClients->push($client);
            }
            if ($client) {
                $updateClientStatusIfNeeded($client, 'Active');
                $matchedActiveClientIds[$client->client_id] = true;
            }
            
            $nttnTds = isset($row[71]) && is_numeric($row[71]) ? (float)$row[71] : 0;
            $balAfterRecovery = isset($row[75]) && is_numeric($row[75]) ? (float)$row[75] : 0;
            
            $openingCr = (isset($row[81]) && $row[81] !== '' && is_numeric($row[81])) ? (float)$row[81] : null;
            $latestCr = (isset($row[82]) && $row[82] !== '' && is_numeric($row[82])) ? (float)$row[82] : null;
            $openingRatingCategory = $getRatingCategory($openingCr);
            $latestRatingCategory = $getRatingCategory($latestCr);
            
            $netBacklogPostpaid = isset($row[21]) && is_numeric($row[21]) ? (float)$row[21] : 0.0;
            $netBacklogPrepaid = isset($row[22]) && is_numeric($row[22]) ? (float)$row[22] : 0.0;
            $netBacklogTotal = isset($row[23]) && is_numeric($row[23]) ? (float)$row[23] : 0.0;
            
            $parsedLiveClients[] = [
                'client' => $client,
                'excel_name' => $clientName,
                'excel_opus' => $opusId,
                'metrics' => [
                    'nttn_tds_amount' => $nttnTds,
                    'balance_after_recovery' => $balAfterRecovery,
                    'opening_cr' => $openingCr,
                    'latest_cr' => $latestCr,
                    'opening_rating_category' => $openingRatingCategory,
                    'latest_rating_category' => $latestRatingCategory,
                    'net_backlog_postpaid' => $netBacklogPostpaid,
                    'net_backlog_prepaid' => $netBacklogPrepaid,
                    'net_backlog_total' => $netBacklogTotal,
                ]
            ];
            
            $idx = count($parsedLiveClients) - 1;
            $liveNameToParsedIndex[strtolower($clientName)] = $idx;
            $liveImportCount++;
        }
        
        // PHASE 2: Parse Discontinued (Collection) Worksheet
        $discRows = $readSheet($zip, $sheetTargets['Discontinued (Collection)'], $sharedStrings);
        $discImportCount = 0;
        $discResidueCount = 0;
        
        if (!empty($discRows)) {
            $row1 = isset($discRows[1]) ? $discRows[1] : [];
            $row2 = isset($discRows[2]) ? $discRows[2] : [];
            
            $currentGroup = '';
            $groupHeaders = [];
            for ($i = 0; $i < 250; $i++) {
                if (isset($row1[$i]) && $row1[$i] !== '') {
                    $currentGroup = strtolower(trim($row1[$i]));
                }
                $groupHeaders[$i] = $currentGroup;
            }
            
            $colMap = [
                'client_id' => 0,
                'client_name' => 1,
                'service_type' => 2,
                'btrc_letter' => 3,
                'license' => 4,
                'other_upstream' => 5,
                'status' => 6,
                'btrc_dis_date' => 7,
                'legal' => 8,
                'remarks' => 9,
                'service_dis_date' => 10,
                'opening_os' => 11,
                'opening_os_nttn' => 12,
                'opening_os_iig' => 13,
                'opening_os_itc' => 14,
                'opening_os_nix' => 15,
                'target' => 16,
                'collection_amount' => 17,
                'shortfall_target' => 18,
                'latest_os' => 19,
                'latest_os_nttn' => 20,
                'latest_os_iig' => 21,
                'latest_os_itc' => 22,
                'latest_os_nix' => 23,
                'payment_plan_description' => 24,
                'pdc' => 25,
                'udc' => 26,
                'total_security' => 27,
                'security_coverage' => 28,
                'pdc_chq' => 29,
                'udc_chq' => 30,
                'expired_chq' => 31,
                'collection_postpaid_nttn' => 163,
                'collection_postpaid_iig' => 164,
                'collection_postpaid_itc' => 165,
                'collection_postpaid_nix' => 166,
                'nttn_discontinuation_date' => 167,
                'iig_itc_discontinuation_date' => 168,
                'unbilled_total' => 169,
                'unbilled_nttn_os' => 170,
                'unbilled_iig_os' => 171,
                'unbilled_itc_os' => 172,
            ];
            
            foreach ($row2 as $colIdx => $val) {
                $valClean = strtolower(trim($val));
                $group = isset($groupHeaders[$colIdx]) ? $groupHeaders[$colIdx] : '';
                
                if ($valClean === 'client id') {
                    $colMap['client_id'] = $colIdx;
                } elseif ($valClean === 'client name') {
                    $colMap['client_name'] = $colIdx;
                } elseif ($valClean === 'service type') {
                    $colMap['service_type'] = $colIdx;
                } elseif ($valClean === 'btrc letter') {
                    $colMap['btrc_letter'] = $colIdx;
                } elseif ($valClean === 'license') {
                    $colMap['license'] = $colIdx;
                } elseif ($valClean === 'other upstream' || $valClean === 'other upsterm') {
                    $colMap['other_upstream'] = $colIdx;
                } elseif ($valClean === 'barred or discontinued' || $valClean === 'status') {
                    $colMap['status'] = $colIdx;
                } elseif ($valClean === 'btrc license dis. date' || $valClean === 'btrc license discontinuation date') {
                    $colMap['btrc_dis_date'] = $colIdx;
                } elseif ($valClean === 'legal') {
                    $colMap['legal'] = $colIdx;
                } elseif ($valClean === 'visit remarks / latest updates' || $valClean === 'remarks') {
                    $colMap['remarks'] = $colIdx;
                } elseif ($valClean === 'service discontinuation date') {
                    $colMap['service_dis_date'] = $colIdx;
                }
                
                // Financials
                elseif (strpos($valClean, 'opening os') !== false) {
                    $colMap['opening_os'] = $colIdx;
                } elseif ($valClean === 'nttn os') {
                    if (strpos($group, 'opening') !== false) {
                        $colMap['opening_os_nttn'] = $colIdx;
                    } elseif (strpos($group, 'latest') !== false) {
                        $colMap['latest_os_nttn'] = $colIdx;
                    }
                } elseif ($valClean === 'iig os') {
                    if (strpos($group, 'opening') !== false) {
                        $colMap['opening_os_iig'] = $colIdx;
                    } elseif (strpos($group, 'latest') !== false) {
                        $colMap['latest_os_iig'] = $colIdx;
                    }
                } elseif ($valClean === 'itc os') {
                    if (strpos($group, 'opening') !== false) {
                        $colMap['opening_os_itc'] = $colIdx;
                    } elseif (strpos($group, 'latest') !== false) {
                        $colMap['latest_os_itc'] = $colIdx;
                    }
                } elseif ($valClean === 'nix os') {
                    if (strpos($group, 'opening') !== false) {
                        $colMap['opening_os_nix'] = $colIdx;
                    } elseif (strpos($group, 'latest') !== false) {
                        $colMap['latest_os_nix'] = $colIdx;
                    }
                }
                
                elseif ($valClean === 'target') {
                    $colMap['target'] = $colIdx;
                }
                
                // collection_amount
                elseif (strpos($valClean, 'actual collection') !== false && strpos($group, 'actual collection') === false) {
                    $colMap['collection_amount'] = $colIdx;
                } elseif ($valClean === 'collection' || $valClean === 'collection amount') {
                    $colMap['collection_amount'] = $colIdx;
                }
                
                elseif (strpos($valClean, 'shortfall from target') !== false) {
                    $colMap['shortfall_target'] = $colIdx;
                }
                
                elseif ($valClean === 'latest os') {
                    $colMap['latest_os'] = $colIdx;
                }
                
                elseif ($valClean === 'payment plan description') {
                    $colMap['payment_plan_description'] = $colIdx;
                } elseif ($valClean === 'pdc amount') {
                    $colMap['pdc'] = $colIdx;
                } elseif ($valClean === 'udc amount') {
                    $colMap['udc'] = $colIdx;
                } elseif ($valClean === 'total security') {
                    $colMap['total_security'] = $colIdx;
                } elseif ($valClean === 'security coverage') {
                    $colMap['security_coverage'] = $colIdx;
                } elseif ($valClean === 'pdc chq') {
                    $colMap['pdc_chq'] = $colIdx;
                } elseif ($valClean === 'udc chq') {
                    $colMap['udc_chq'] = $colIdx;
                } elseif ($valClean === 'expired chq') {
                    $colMap['expired_chq'] = $colIdx;
                }
                
                // Payments
                elseif ($valClean === 'nttn payment' && strpos($group, 'actual collection') !== false) {
                    $colMap['collection_postpaid_nttn'] = $colIdx;
                } elseif ($valClean === 'iig payment' && strpos($group, 'actual collection') !== false) {
                    $colMap['collection_postpaid_iig'] = $colIdx;
                } elseif ($valClean === 'itc payment' && strpos($group, 'actual collection') !== false) {
                    $colMap['collection_postpaid_itc'] = $colIdx;
                } elseif ($valClean === 'nix payment' && strpos($group, 'actual collection') !== false) {
                    $colMap['collection_postpaid_nix'] = $colIdx;
                }
                
                elseif ($valClean === 'nttn discontinuation date') {
                    $colMap['nttn_discontinuation_date'] = $colIdx;
                } elseif ($valClean === 'iig / itc discontinuation date' || $valClean === 'iig/itc discontinuation date') {
                    $colMap['iig_itc_discontinuation_date'] = $colIdx;
                }
                
                // Unbilled
                elseif ($valClean === 'un-billed total' || $valClean === 'unbilled total') {
                    $colMap['unbilled_total'] = $colIdx;
                } elseif ($valClean === 'un-billed nttn os' || $valClean === 'unbilled nttn os') {
                    $colMap['unbilled_nttn_os'] = $colIdx;
                } elseif ($valClean === 'un-billed iig os' || $valClean === 'unbilled iig os') {
                    $colMap['unbilled_iig_os'] = $colIdx;
                } elseif ($valClean === 'un-billed itc os' || $valClean === 'unbilled itc os') {
                    $colMap['unbilled_itc_os'] = $colIdx;
                }
                
                // Managers
                elseif ($valClean === 'sales kam' || $valClean === 'sales manager') {
                    $colMap['sales_kam'] = $colIdx;
                } elseif ($valClean === 'team name') {
                    $colMap['team_name'] = $colIdx;
                } elseif ($valClean === 'collections kam' || $valClean === 'collection kam') {
                    $colMap['collections_kam'] = $colIdx;
                } elseif ($valClean === 'collection supervisor') {
                    $colMap['collection_supervisor'] = $colIdx;
                } elseif ($valClean === 'nttn billing kam' || (strpos($valClean, 'nttn') !== false && strpos($valClean, 'billing kam') !== false)) {
                    $colMap['nttn_billing_kam'] = $colIdx;
                } elseif ($valClean === 'iig / itc billing kam' || $valClean === 'iig/itc billing kam' || (strpos($valClean, 'iig') !== false && strpos($valClean, 'billing kam') !== false)) {
                    $colMap['iig_itc_billing_kam'] = $colIdx;
                }
            }

            $aggregatedDisc = [];
            $aggregatedResidues = [];
            
            foreach ($discRows as $rowNum => $row) {
                if ($rowNum < 3) continue; // Headers
                
                $discId = isset($row[$colMap['client_id']]) ? trim($row[$colMap['client_id']]) : '';
                $rawClientName = isset($row[$colMap['client_name']]) ? trim($row[$colMap['client_name']]) : '';
                
                // Skip if client name is empty or is the duplicate header row
                if (empty($rawClientName) || strtolower($rawClientName) === 'client name') {
                    continue;
                }
                
                // Skip if client_id is empty
                if (empty($discId)) {
                    continue;
                }
                
                $clientName = $cleanClientName($rawClientName);
                $clientStatus = isset($row[$colMap['status']]) ? trim($row[$colMap['status']]) : 'Discontinued';
                if (empty($clientStatus)) {
                    $clientStatus = 'Discontinued';
                }
                
                $opusId = strpos($discId, 'DISC-') === 0 ? $discId : "DISC-" . $discId;
                
                // Match client
                $client = $findClient($clientName, $opusId);
                if (!$client && isset($_GET['create_clients']) && $_GET['create_clients'] == 1) {
                    $serviceType = isset($row[$colMap['service_type']]) ? trim($row[$colMap['service_type']]) : null;
                    $licenseBilling = isset($row[$colMap['license']]) ? trim($row[$colMap['license']]) : null;
                    $smKam = isset($row[32]) ? trim($row[32]) : null;
                    $teamName = isset($row[33]) ? trim($row[33]) : null;
                    $collectionKam = isset($row[34]) ? trim($row[34]) : null;
                    $collectionSupervisor = isset($row[35]) ? trim($row[35]) : null;

                    $opusIdToSave = (empty($discId) || strtolower($discId) === 'n/a' || strtolower($opusId) === 'disc-n/a') ? null : $opusId;
                    $newId = DB::table('client')->insertGetId([
                        'opus_id' => $opusIdToSave,
                        'client_name' => $clientName,
                        'client_status' => $clientStatus,
                        'service_type_billing' => $serviceType,
                        'license_billing' => $licenseBilling,
                        'sm_kam' => $smKam,
                        'team_name' => $teamName,
                        'collection_kam' => $collectionKam,
                        'collection_supervisor' => $collectionSupervisor,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $client = DB::table('client')->where('client_id', $newId)->first();
                    $existingClients->push($client);
                }
                
                if ($client) {
                    $clientNameLower = strtolower(trim($client->client_name));
                    if (in_array($client->client_id, $ignoredStatusChangeIds) || in_array($clientNameLower, $ignoredStatusChangeNames)) {
                        if ($client->client_status === 'Active') {
                            continue;
                        }
                    }
                }
                
                if ($client && isset($matchedActiveClientIds[$client->client_id])) {
                    continue;
                }

                $btrcDisDate = isset($colMap['btrc_dis_date']) && isset($row[$colMap['btrc_dis_date']]) ? $excelDateToPhp($row[$colMap['btrc_dis_date']]) : null;
                $legalStr = isset($colMap['legal']) && isset($row[$colMap['legal']]) ? strtolower(trim($row[$colMap['legal']])) : '';
                $legal = (!empty($legalStr) && $legalStr !== 'no') ? true : false;
                $serviceDisDate = isset($colMap['service_dis_date']) && isset($row[$colMap['service_dis_date']]) ? $excelDateToPhp($row[$colMap['service_dis_date']]) : null;
                
                $discSummaryFields = [
                    'summary_month' => $summaryMonth,
                    
                    'opening_os' => isset($colMap['opening_os']) && isset($row[$colMap['opening_os']]) && is_numeric($row[$colMap['opening_os']]) ? (float)$row[$colMap['opening_os']] : 0.0,
                    'opening_os_nttn' => isset($colMap['opening_os_nttn']) && isset($row[$colMap['opening_os_nttn']]) && is_numeric($row[$colMap['opening_os_nttn']]) ? (float)$row[$colMap['opening_os_nttn']] : 0.0,
                    'opening_os_iig' => isset($colMap['opening_os_iig']) && isset($row[$colMap['opening_os_iig']]) && is_numeric($row[$colMap['opening_os_iig']]) ? (float)$row[$colMap['opening_os_iig']] : 0.0,
                    'opening_os_itc' => isset($colMap['opening_os_itc']) && isset($row[$colMap['opening_os_itc']]) && is_numeric($row[$colMap['opening_os_itc']]) ? (float)$row[$colMap['opening_os_itc']] : 0.0,
                    'opening_os_nix' => isset($colMap['opening_os_nix']) && isset($row[$colMap['opening_os_nix']]) && is_numeric($row[$colMap['opening_os_nix']]) ? (float)$row[$colMap['opening_os_nix']] : 0.0,
                    
                    'target' => isset($colMap['target']) && isset($row[$colMap['target']]) && is_numeric($row[$colMap['target']]) ? (float)$row[$colMap['target']] : 0.0,
                    'collection_amount' => isset($colMap['collection_amount']) && isset($row[$colMap['collection_amount']]) && is_numeric($row[$colMap['collection_amount']]) ? (float)$row[$colMap['collection_amount']] : 0.0,
                    'shortfall_target' => isset($colMap['shortfall_target']) && isset($row[$colMap['shortfall_target']]) && is_numeric($row[$colMap['shortfall_target']]) ? (float)$row[$colMap['shortfall_target']] : 0.0,
                    
                    'latest_os' => isset($colMap['latest_os']) && isset($row[$colMap['latest_os']]) && is_numeric($row[$colMap['latest_os']]) ? (float)$row[$colMap['latest_os']] : 0.0,
                    'latest_os_nttn' => isset($colMap['latest_os_nttn']) && isset($row[$colMap['latest_os_nttn']]) && is_numeric($row[$colMap['latest_os_nttn']]) ? (float)$row[$colMap['latest_os_nttn']] : 0.0,
                    'latest_os_iig' => isset($colMap['latest_os_iig']) && isset($row[$colMap['latest_os_iig']]) && is_numeric($row[$colMap['latest_os_iig']]) ? (float)$row[$colMap['latest_os_iig']] : 0.0,
                    'latest_os_itc' => isset($colMap['latest_os_itc']) && isset($row[$colMap['latest_os_itc']]) && is_numeric($row[$colMap['latest_os_itc']]) ? (float)$row[$colMap['latest_os_itc']] : 0.0,
                    'latest_os_nix' => isset($colMap['latest_os_nix']) && isset($row[$colMap['latest_os_nix']]) && is_numeric($row[$colMap['latest_os_nix']]) ? (float)$row[$colMap['latest_os_nix']] : 0.0,
                    
                    'payment_plan_description' => isset($colMap['payment_plan_description']) && isset($row[$colMap['payment_plan_description']]) ? trim($row[$colMap['payment_plan_description']]) : null,
                    'pdc' => isset($colMap['pdc']) && isset($row[$colMap['pdc']]) && is_numeric($row[$colMap['pdc']]) ? (float)$row[$colMap['pdc']] : 0.0,
                    'udc' => isset($colMap['udc']) && isset($row[$colMap['udc']]) && is_numeric($row[$colMap['udc']]) ? (float)$row[$colMap['udc']] : 0.0,
                    'total_security' => isset($colMap['total_security']) && isset($row[$colMap['total_security']]) && is_numeric($row[$colMap['total_security']]) ? (float)$row[$colMap['total_security']] : 0.0,
                    'security_coverage' => isset($colMap['security_coverage']) && isset($row[$colMap['security_coverage']]) && is_numeric($row[$colMap['security_coverage']]) ? (float)$row[$colMap['security_coverage']] : 0.0,
                    'pdc_chq' => isset($colMap['pdc_chq']) && isset($row[$colMap['pdc_chq']]) ? trim($row[$colMap['pdc_chq']]) : null,
                    'udc_chq' => isset($colMap['udc_chq']) && isset($row[$colMap['udc_chq']]) ? trim($row[$colMap['udc_chq']]) : null,
                    'expired_chq' => isset($colMap['expired_chq']) && isset($row[$colMap['expired_chq']]) && is_numeric($row[$colMap['expired_chq']]) ? (float)$row[$colMap['expired_chq']] : 0.0,
                    
                    'collection_postpaid_nttn' => isset($colMap['collection_postpaid_nttn']) && isset($row[$colMap['collection_postpaid_nttn']]) && is_numeric($row[$colMap['collection_postpaid_nttn']]) ? (float)$row[$colMap['collection_postpaid_nttn']] : 0.0,
                    'collection_postpaid_iig' => isset($colMap['collection_postpaid_iig']) && isset($row[$colMap['collection_postpaid_iig']]) && is_numeric($row[$colMap['collection_postpaid_iig']]) ? (float)$row[$colMap['collection_postpaid_iig']] : 0.0,
                    'collection_postpaid_itc' => isset($colMap['collection_postpaid_itc']) && isset($row[$colMap['collection_postpaid_itc']]) && is_numeric($row[$colMap['collection_postpaid_itc']]) ? (float)$row[$colMap['collection_postpaid_itc']] : 0.0,
                    'collection_postpaid_nix' => isset($colMap['collection_postpaid_nix']) && isset($row[$colMap['collection_postpaid_nix']]) && is_numeric($row[$colMap['collection_postpaid_nix']]) ? (float)$row[$colMap['collection_postpaid_nix']] : 0.0,
                    
                    'nttn_discontinuation_date' => isset($colMap['nttn_discontinuation_date']) && isset($row[$colMap['nttn_discontinuation_date']]) ? $excelDateToPhp($row[$colMap['nttn_discontinuation_date']]) : null,
                    'iig_itc_discontinuation_date' => isset($colMap['iig_itc_discontinuation_date']) && isset($row[$colMap['iig_itc_discontinuation_date']]) ? $excelDateToPhp($row[$colMap['iig_itc_discontinuation_date']]) : null,
                    
                    'unbilled_total' => isset($colMap['unbilled_total']) && isset($row[$colMap['unbilled_total']]) && is_numeric($row[$colMap['unbilled_total']]) ? (float)$row[$colMap['unbilled_total']] : 0.0,
                    'unbilled_nttn_os' => isset($colMap['unbilled_nttn_os']) && isset($row[$colMap['unbilled_nttn_os']]) && is_numeric($row[$colMap['unbilled_nttn_os']]) ? (float)$row[$colMap['unbilled_nttn_os']] : 0.0,
                    'unbilled_iig_os' => isset($colMap['unbilled_iig_os']) && isset($row[$colMap['unbilled_iig_os']]) && is_numeric($row[$colMap['unbilled_iig_os']]) ? (float)$row[$colMap['unbilled_iig_os']] : 0.0,
                    'unbilled_itc_os' => isset($colMap['unbilled_itc_os']) && isset($row[$colMap['unbilled_itc_os']]) && is_numeric($row[$colMap['unbilled_itc_os']]) ? (float)$row[$colMap['unbilled_itc_os']] : 0.0,
                    
                    'visit_remarks' => isset($colMap['remarks']) && isset($row[$colMap['remarks']]) ? trim($row[$colMap['remarks']]) : null,
                    
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                if ($client) {
                    $cid = $client->client_id;
                    if (!isset($aggregatedDisc[$cid])) {
                        $discSummaryFields['client_id'] = $client->client_id;
                        $discSummaryFields['client_opus_id'] = $client->opus_id;
                        $discSummaryFields['client_name'] = $client->client_name;
                        $discSummaryFields['client_status'] = $client->client_status;
                        $discSummaryFields['client_agreement_status'] = $client->agreement_status;
                        $discSummaryFields['client_barring_priority'] = $client->barring_priority;
                        $discSummaryFields['client_btrc_license_discontinuation_date'] = $client->btrc_license_discontinuation_date;
                        $discSummaryFields['client_legal'] = $client->legal;
                        $discSummaryFields['client_service_discontinuation_date'] = $client->service_discontinuation_date;
                        $discSummaryFields['client_billing_modality_kpi'] = $client->billing_modality_kpi;
                        $discSummaryFields['client_service_type_billing'] = $client->service_type_billing;
                        $discSummaryFields['client_license_billing'] = $client->license_billing;
                        $discSummaryFields['client_btrc_letter'] = $client->btrc_letter;
                        $discSummaryFields['client_security_coverage'] = $client->security_coverage;
                        $discSummaryFields['client_payment_plan'] = $client->payment_plan;
                        $discSummaryFields['client_other_upstream'] = $client->other_upstream;
                        $discSummaryFields['client_sm_kam'] = $client->sm_kam;
                        $discSummaryFields['client_team_name'] = $client->team_name;
                        $discSummaryFields['client_collection_kam'] = $client->collection_kam;
                        $discSummaryFields['client_collection_supervisor'] = $client->collection_supervisor;
                        $discSummaryFields['client_nttn_billing_kam'] = $client->nttn_billing_kam;
                        $discSummaryFields['client_iig_itc_billing_kam'] = $client->iig_itc_billing_kam;
                        $discSummaryFields['client_nttn_billing_commencement_date'] = $client->nttn_billing_commencement_date;
                        $discSummaryFields['client_iig_itc_billing_commencement_date'] = $client->iig_itc_billing_commencement_date;

                        $aggregatedDisc[$cid] = $discSummaryFields;
                        
                        $updateClientStatusIfNeeded($client, $clientStatus);
                    } else {
                        $fieldsToSum = [
                            'opening_os', 'opening_os_nttn', 'opening_os_iig', 'opening_os_itc', 'opening_os_nix',
                            'target', 'collection_amount', 'shortfall_target',
                            'latest_os', 'latest_os_nttn', 'latest_os_iig', 'latest_os_itc', 'latest_os_nix',
                            'pdc', 'udc', 'total_security', 'expired_chq',
                            'collection_postpaid_nttn', 'collection_postpaid_iig', 'collection_postpaid_itc', 'collection_postpaid_nix',
                            'unbilled_total', 'unbilled_nttn_os', 'unbilled_iig_os', 'unbilled_itc_os'
                        ];
                        foreach ($fieldsToSum as $f) {
                            $aggregatedDisc[$cid][$f] += $discSummaryFields[$f];
                        }
                        
                        if (empty($aggregatedDisc[$cid]['visit_remarks']) && !empty($discSummaryFields['visit_remarks'])) {
                            $aggregatedDisc[$cid]['visit_remarks'] = $discSummaryFields['visit_remarks'];
                        }
                        if (empty($aggregatedDisc[$cid]['payment_plan_description']) && !empty($discSummaryFields['payment_plan_description'])) {
                            $aggregatedDisc[$cid]['payment_plan_description'] = $discSummaryFields['payment_plan_description'];
                        }
                    }
                } else {
                    $keyName = strtolower($clientName);
                    if (!isset($aggregatedResidues[$keyName])) {
                        $discSummaryFields['client_id'] = null;
                        $discSummaryFields['client_name'] = $clientName;
                        $discSummaryFields['client_opus_id'] = $opusId;

                        $aggregatedResidues[$keyName] = $discSummaryFields;
                    } else {
                        $fieldsToSum = [
                            'opening_os', 'opening_os_nttn', 'opening_os_iig', 'opening_os_itc', 'opening_os_nix',
                            'target', 'collection_amount', 'shortfall_target',
                            'latest_os', 'latest_os_nttn', 'latest_os_iig', 'latest_os_itc', 'latest_os_nix',
                            'pdc', 'udc', 'total_security', 'expired_chq',
                            'collection_postpaid_nttn', 'collection_postpaid_iig', 'collection_postpaid_itc', 'collection_postpaid_nix',
                            'unbilled_total', 'unbilled_nttn_os', 'unbilled_iig_os', 'unbilled_itc_os'
                        ];
                        foreach ($fieldsToSum as $f) {
                            $aggregatedResidues[$keyName][$f] += $discSummaryFields[$f];
                        }
                        
                        if (empty($aggregatedResidues[$keyName]['visit_remarks']) && !empty($discSummaryFields['visit_remarks'])) {
                            $aggregatedResidues[$keyName]['visit_remarks'] = $discSummaryFields['visit_remarks'];
                        }
                    }
                }
            }

            foreach ($aggregatedDisc as $cid => $fields) {
                $fields['total_collection'] = 
                    $fields['collection_postpaid_nttn'] + 
                    $fields['collection_postpaid_iig'] + 
                    $fields['collection_postpaid_itc'] + 
                    $fields['collection_postpaid_nix'];

                $discTotalColl = isset($fields['collection_amount']) ? (float)$fields['collection_amount'] : (float)$fields['total_collection'];
                $discOpeningOs = isset($fields['opening_os']) ? (float)$fields['opening_os'] : 0.0;

                $fields['collection_mrc'] = 0.00;
                $fields['collection_backlog'] = $discTotalColl;
                $fields['mrc_shortfall'] = 0.00;
                $fields['backlog_shortfall'] = max(0.00, $discOpeningOs - $discTotalColl);

                DB::table('monthly_summary_discontinued')->updateOrInsert(
                    [
                        'client_id' => $cid,
                        'summary_month' => $summaryMonth
                    ],
                    $fields
                );

                // Sync collections of discontinued client to collection table
                $collectionTypes = [
                    'postpaid_nttn' => 'collection_postpaid_nttn',
                    'postpaid_iig' => 'collection_postpaid_iig',
                    'postpaid_itc' => 'collection_postpaid_itc',
                    'postpaid_nix' => 'collection_postpaid_nix',
                ];
                
                $segmentedSum = 0.0;
                foreach ($collectionTypes as $enumVal => $field) {
                    $amount = isset($fields[$field]) ? (float)$fields[$field] : 0.0;
                    if ($amount > 0.0) {
                        $segmentedSum += $amount;
                        DB::table('collection')->insert([
                            'client_id' => $cid,
                            'collection_datetime' => $summaryMonth . ' 23:59:59',
                            'collection_month' => $summaryMonth,
                            'collection_type' => $enumVal,
                            'collection_amount' => $amount,
                            'remarks' => 'Imported discontinued collection from Excel ' . $fileToProcess,
                            'created_by' => 'System',
                            'created_at' => now()
                        ]);
                    }
                }

                $totalColl = isset($fields['collection_amount']) ? (float)$fields['collection_amount'] : 0.0;
                if ($totalColl > ($segmentedSum + 0.01)) {
                    $diff = $totalColl - $segmentedSum;
                    DB::table('collection')->insert([
                        'client_id' => $cid,
                        'collection_datetime' => $summaryMonth . ' 23:59:59',
                        'collection_month' => $summaryMonth,
                        'collection_type' => 'postpaid_nttn',
                        'collection_amount' => $diff,
                        'remarks' => 'Imported unsegmented discontinued collection remainder from Excel ' . $fileToProcess,
                        'created_by' => 'System',
                        'created_at' => now()
                    ]);
                }
                $discImportCount++;
            }

            foreach ($aggregatedResidues as $keyName => $fields) {
                $fields['total_collection'] = 
                    $fields['collection_postpaid_nttn'] + 
                    $fields['collection_postpaid_iig'] + 
                    $fields['collection_postpaid_itc'] + 
                    $fields['collection_postpaid_nix'];

                DB::table('monthly_summary_discontinued_residue')->updateOrInsert(
                    [
                        'client_name' => $fields['client_name'],
                        'summary_month' => $summaryMonth
                    ],
                    $fields
                );
                $discResidueCount++;
            }
        }
        
        // PHASE 3: Parse LIVE_Post-Paid (Collection) Worksheet
        $postPaidRows = $readSheet($zip, $sheetTargets['LIVE_Post-Paid (Collection)'], $sharedStrings);
        $postPaidHeaders = isset($postPaidRows[2]) ? $postPaidRows[2] : [];
        $colPostpaidNttn = 187; // default fallback
        $colPostpaidIig  = 188;
        $colPostpaidItc  = 189;
        $colPostpaidNix  = 190;
        foreach ($postPaidHeaders as $colIdx => $headerVal) {
            $headerValClean = strtolower(trim($headerVal));
            if ($headerValClean === 'nttn payment') {
                $colPostpaidNttn = $colIdx;
            } elseif ($headerValClean === 'iig payment') {
                $colPostpaidIig = $colIdx;
            } elseif ($headerValClean === 'itc payment') {
                $colPostpaidItc = $colIdx;
            } elseif ($headerValClean === 'nix payment') {
                $colPostpaidNix = $colIdx;
            }
        }

        foreach ($postPaidRows as $rowNum => $row) {
            if ($rowNum < 3) continue; // Headers
            
            $rawClientName = isset($row[0]) ? trim($row[0]) : '';
            if (empty($rawClientName)) continue;
            
            $clientName = $cleanClientName($rawClientName);
            $idx = $liveNameToParsedIndex[strtolower($clientName)] ?? null;
            
            if ($idx === null) {
                // If not found in Phase 1 master sheet, check if name matches client table
                $client = $findClient($clientName, null);
                if (!$client && isset($_GET['create_clients']) && $_GET['create_clients'] == 1) {
                    $newId = DB::table('client')->insertGetId([
                        'client_name' => $clientName,
                        'client_status' => 'Active',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $client = DB::table('client')->where('client_id', $newId)->first();
                    $existingClients->push($client);
                }
                if ($client) {
                    $updateClientStatusIfNeeded($client, 'Active');
                }
                $parsedLiveClients[] = [
                    'client' => $client,
                    'excel_name' => $clientName,
                    'excel_opus' => $client ? $client->opus_id : null,
                    'metrics' => []
                ];
                $idx = count($parsedLiveClients) - 1;
                $liveNameToParsedIndex[strtolower($clientName)] = $idx;
            }
            
            $m = &$parsedLiveClients[$idx]['metrics'];
            $m['opening_os_postpaid_nttn'] = isset($row[9]) && is_numeric($row[9]) ? (float)$row[9] : 0;
            $m['opening_os_postpaid_iig']  = isset($row[10]) && is_numeric($row[10]) ? (float)$row[10] : 0;
            $m['opening_os_postpaid_itc']  = isset($row[11]) && is_numeric($row[11]) ? (float)$row[11] : 0;
            $m['opening_os_postpaid_nix']  = isset($row[12]) && is_numeric($row[12]) ? (float)$row[12] : 0;
            
            $m['mrc_postpaid_nttn'] = isset($row[14]) && is_numeric($row[14]) ? (float)$row[14] : 0;
            $m['mrc_postpaid_iig']  = isset($row[15]) && is_numeric($row[15]) ? (float)$row[15] : 0;
            $m['mrc_postpaid_itc']  = isset($row[16]) && is_numeric($row[16]) ? (float)$row[16] : 0;
            $m['mrc_postpaid_nix']  = isset($row[17]) && is_numeric($row[17]) ? (float)$row[17] : 0;
            
            $m['maturity_postpaid_nttn'] = isset($row[19]) && is_numeric($row[19]) ? (float)$row[19] : 0;
            $m['maturity_postpaid_iig']  = isset($row[20]) && is_numeric($row[20]) ? (float)$row[20] : 0;
            $m['maturity_postpaid_itc']  = isset($row[21]) && is_numeric($row[21]) ? (float)$row[21] : 0;
            $m['maturity_postpaid_nix']  = isset($row[22]) && is_numeric($row[22]) ? (float)$row[22] : 0;
            
            $m['target_maturity_commitment_postpaid'] = isset($row[27]) && is_numeric($row[27]) ? (float)$row[27] : 0;
            $m['payment_plan_postpaid'] = isset($row[25]) && is_numeric($row[25]) ? (float)$row[25] : 0;
            
            $m['shortfall_target_postpaid']   = isset($row[33]) && is_numeric($row[33]) ? (float)$row[33] : 0;
            $m['shortfall_mrc_postpaid']      = isset($row[34]) && is_numeric($row[34]) ? (float)$row[34] : 0;
            $m['shortfall_maturity_postpaid'] = isset($row[35]) && is_numeric($row[35]) ? (float)$row[35] : 0;
            
            $m['latest_os_balance_postpaid'] = isset($row[36]) && is_numeric($row[36]) ? (float)$row[36] : 0;
            
            $m['pdc']                     = isset($row[43]) && is_numeric($row[43]) ? (float)$row[43] : 0;
            $m['payment_plan_description'] = isset($row[44]) ? trim($row[44]) : null;
            $m['expired_chq']             = isset($row[45]) && is_numeric($row[45]) ? (float)$row[45] : 0;
            $m['current_month_remarks']    = isset($row[28]) ? trim($row[28]) : null;
            
            $m['collection_postpaid_nttn'] = isset($row[$colPostpaidNttn]) && is_numeric($row[$colPostpaidNttn]) ? (float)$row[$colPostpaidNttn] : 0.0;
            $m['collection_postpaid_iig']  = isset($row[$colPostpaidIig])  && is_numeric($row[$colPostpaidIig])  ? (float)$row[$colPostpaidIig]  : 0.0;
            $m['collection_postpaid_itc']  = isset($row[$colPostpaidItc])  && is_numeric($row[$colPostpaidItc])  ? (float)$row[$colPostpaidItc]  : 0.0;
            $m['collection_postpaid_nix']  = isset($row[$colPostpaidNix])  && is_numeric($row[$colPostpaidNix])  ? (float)$row[$colPostpaidNix]  : 0.0;
        }
        
        // PHASE 4: Parse LIVE_Pre-Paid (Billing) Worksheet
        $prePaidRows = $readSheet($zip, $sheetTargets['LIVE_Pre-Paid (Billing)'], $sharedStrings);
        $prePaidHeaders = isset($prePaidRows[2]) ? $prePaidRows[2] : [];
        $colPrepaidNttn = 330; // default fallback
        $colPrepaidIig  = 331;
        $colPrepaidItc  = 332;
        $colPrepaidNix  = 333;
        foreach ($prePaidHeaders as $colIdx => $headerVal) {
            $headerValClean = strtolower(trim($headerVal));
            if ($headerValClean === 'nttn payment') {
                $colPrepaidNttn = $colIdx;
            } elseif ($headerValClean === 'iig payment') {
                $colPrepaidIig = $colIdx;
            } elseif ($headerValClean === 'itc payment') {
                $colPrepaidItc = $colIdx;
            } elseif ($headerValClean === 'nix payment') {
                $colPrepaidNix = $colIdx;
            }
        }

        foreach ($prePaidRows as $rowNum => $row) {
            if ($rowNum < 3) continue; // Headers
            
            $rawClientName = isset($row[0]) ? trim($row[0]) : '';
            if (empty($rawClientName)) continue;
            
            $clientName = $cleanClientName($rawClientName);
            $idx = $liveNameToParsedIndex[strtolower($clientName)] ?? null;
            
            if ($idx === null) {
                $client = $findClient($clientName, null);
                if (!$client && isset($_GET['create_clients']) && $_GET['create_clients'] == 1) {
                    $newId = DB::table('client')->insertGetId([
                        'client_name' => $clientName,
                        'client_status' => 'Active',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $client = DB::table('client')->where('client_id', $newId)->first();
                    $existingClients->push($client);
                }
                if ($client) {
                    $updateClientStatusIfNeeded($client, 'Active');
                }
                $parsedLiveClients[] = [
                    'client' => $client,
                    'excel_name' => $clientName,
                    'excel_opus' => $client ? $client->opus_id : null,
                    'metrics' => []
                ];
                $idx = count($parsedLiveClients) - 1;
                $liveNameToParsedIndex[strtolower($clientName)] = $idx;
            }
            
            $m = &$parsedLiveClients[$idx]['metrics'];
            $m['opening_os_prepaid_nttn'] = isset($row[8]) && is_numeric($row[8]) ? (float)$row[8] : 0;
            $m['opening_os_prepaid_iig']  = isset($row[9]) && is_numeric($row[9]) ? (float)$row[9] : 0;
            $m['opening_os_prepaid_itc']  = isset($row[10]) && is_numeric($row[10]) ? (float)$row[10] : 0;
            $m['opening_os_prepaid_nix']  = isset($row[11]) && is_numeric($row[11]) ? (float)$row[11] : 0;
            
            $m['mrc_prepaid_nttn']     = isset($row[23]) && is_numeric($row[23]) ? (float)$row[23] : 0;
            $m['mrc_prepaid_iig']      = isset($row[24]) && is_numeric($row[24]) ? (float)$row[24] : 0;
            $m['mrc_prepaid_itc']      = isset($row[25]) && is_numeric($row[25]) ? (float)$row[25] : 0;
            $m['mrc_prepaid_nix']      = isset($row[26]) && is_numeric($row[26]) ? (float)$row[26] : 0;
            $m['mrc_prepaid_nttn_iig'] = isset($row[28]) && is_numeric($row[28]) ? (float)$row[28] : 0;
            
            $m['maturity_prepaid_nttn'] = isset($row[18]) && is_numeric($row[18]) ? (float)$row[18] : 0;
            $m['maturity_prepaid_iig']  = isset($row[19]) && is_numeric($row[19]) ? (float)$row[19] : 0;
            $m['maturity_prepaid_itc']  = isset($row[20]) && is_numeric($row[20]) ? (float)$row[20] : 0;
            $m['maturity_prepaid_nix']  = isset($row[21]) && is_numeric($row[21]) ? (float)$row[21] : 0;
            
            $m['target_maturity_commitment_prepaid'] = isset($row[197]) && is_numeric($row[197]) ? (float)$row[197] : 0;
            $m['payment_plan_prepaid'] = isset($row[195]) && is_numeric($row[195]) ? (float)$row[195] : 0;
            
            $m['shortfall_target_prepaid']   = isset($row[203]) && is_numeric($row[203]) ? (float)$row[203] : 0;
            $m['shortfall_mrc_prepaid']      = isset($row[204]) && is_numeric($row[204]) ? (float)$row[204] : 0;
            $m['shortfall_maturity_prepaid'] = isset($row[205]) && is_numeric($row[205]) ? (float)$row[205] : 0;
            
            $m['latest_os_balance_prepaid'] = isset($row[206]) && is_numeric($row[206]) ? (float)$row[206] : 0;
            
            $m['pdc']         = (isset($m['pdc']) ? $m['pdc'] : 0) + (isset($row[213]) && is_numeric($row[213]) ? (float)$row[213] : 0);
            $m['udc']         = isset($row[214]) && is_numeric($row[214]) ? (float)$row[214] : 0;
            $m['expired_chq'] = (isset($m['expired_chq']) ? $m['expired_chq'] : 0) + (isset($row[215]) && is_numeric($row[215]) ? (float)$row[215] : 0);
            
            if (empty($m['payment_plan_description']) && !empty($row[214])) {
                $m['payment_plan_description'] = trim($row[214]);
            }
            if (empty($m['current_month_remarks']) && !empty($row[198])) {
                $m['current_month_remarks'] = trim($row[198]);
            }
            
            $m['collection_prepaid_nttn'] = isset($row[$colPrepaidNttn]) && is_numeric($row[$colPrepaidNttn]) ? (float)$row[$colPrepaidNttn] : 0.0;
            $m['collection_prepaid_iig']  = isset($row[$colPrepaidIig])  && is_numeric($row[$colPrepaidIig])  ? (float)$row[$colPrepaidIig]  : 0.0;
            $m['collection_prepaid_itc']  = isset($row[$colPrepaidItc])  && is_numeric($row[$colPrepaidItc])  ? (float)$row[$colPrepaidItc]  : 0.0;
            $m['collection_prepaid_nix']  = isset($row[$colPrepaidNix])  && is_numeric($row[$colPrepaidNix])  ? (float)$row[$colPrepaidNix]  : 0.0;
        }

        // PHASE 5: Consolidate and Insert/Update monthly_summary / monthly_summary_residue
        $summaryInsertCount = 0;
        $residueInsertCount = 0;
        
        $decimalFields = [
            'opening_os_postpaid_nttn', 'opening_os_postpaid_iig_nttn', 'opening_os_postpaid_iig', 'opening_os_postpaid_itc', 'opening_os_postpaid_nix',
            'opening_os_prepaid_nttn', 'opening_os_prepaid_iig_nttn', 'opening_os_prepaid_iig', 'opening_os_prepaid_itc', 'opening_os_prepaid_nix',
            'total_opening_os',
            'mrc_postpaid_nttn', 'mrc_postpaid_nttn_iig', 'mrc_postpaid_iig', 'mrc_postpaid_itc', 'mrc_postpaid_nix',
            'mrc_prepaid_nttn', 'mrc_prepaid_nttn_iig', 'mrc_prepaid_iig', 'mrc_prepaid_itc', 'mrc_prepaid_nix',
            'total_mrc',
            'maturity_postpaid_nttn', 'maturity_postpaid_nttn_iig', 'maturity_postpaid_iig', 'maturity_postpaid_itc', 'maturity_postpaid_nix',
            'maturity_prepaid_nttn', 'maturity_prepaid_nttn_iig', 'maturity_prepaid_iig', 'maturity_prepaid_itc', 'maturity_prepaid_nix',
            'total_maturity',
            'net_backlog_postpaid', 'net_backlog_prepaid', 'net_backlog_total',
            'target_maturity_commitment_postpaid', 'target_maturity_commitment_prepaid', 'total_target_maturity_commitment',
            'target_additional_shortfall_from_maturity', 'maturity_commitment_total',
            'payment_plan_postpaid', 'payment_plan_prepaid', 'total_payment_plan',
            'shortfall_target_postpaid', 'shortfall_target_prepaid', 'total_shortfall_target',
            'shortfall_mrc_postpaid', 'shortfall_mrc_prepaid', 'total_shortfall_mrc',
            'shortfall_maturity_postpaid', 'shortfall_maturity_prepaid', 'total_shortfall_maturity',
            'shortfall_payment_plan_postpaid', 'shortfall_payment_plan_prepaid', 'total_shortfall_payment_plan',
            'latest_os_balance_postpaid', 'latest_os_balance_prepaid', 'total_latest_os',
            'nttn_tds_amount', 'balance_after_recovery',
            'collection_postpaid_nttn', 'collection_postpaid_nttn_iig', 'collection_postpaid_iig', 'collection_postpaid_itc', 'collection_postpaid_nix',
            'collection_prepaid_nttn', 'collection_prepaid_nttn_iig', 'collection_prepaid_iig', 'collection_prepaid_itc', 'collection_prepaid_nix',
            'pdc', 'udc', 'expired_chq', 'total_collection'
        ];
        
        foreach ($parsedLiveClients as $pc) {
            $data = $pc['metrics'];
            $insertData = [
                'summary_month' => $summaryMonth
            ];
            
            foreach ($decimalFields as $field) {
                $insertData[$field] = isset($data[$field]) ? (float)$data[$field] : 0.0;
            }
            
            $insertData['total_opening_os'] = 
                $insertData['opening_os_postpaid_nttn'] + $insertData['opening_os_postpaid_iig'] + $insertData['opening_os_postpaid_itc'] + $insertData['opening_os_postpaid_nix'] +
                $insertData['opening_os_prepaid_nttn'] + $insertData['opening_os_prepaid_iig'] + $insertData['opening_os_prepaid_itc'] + $insertData['opening_os_prepaid_nix'];
                
            $insertData['total_mrc'] = 
                $insertData['mrc_postpaid_nttn'] + $insertData['mrc_postpaid_iig'] + $insertData['mrc_postpaid_itc'] + $insertData['mrc_postpaid_nix'] +
                $insertData['mrc_prepaid_nttn'] + $insertData['mrc_prepaid_iig'] + $insertData['mrc_prepaid_itc'] + $insertData['mrc_prepaid_nix'] + $insertData['mrc_prepaid_nttn_iig'];
                
            $insertData['total_maturity'] = 
                $insertData['maturity_postpaid_nttn'] + $insertData['maturity_postpaid_iig'] + $insertData['maturity_postpaid_itc'] + $insertData['maturity_postpaid_nix'] +
                $insertData['maturity_prepaid_nttn'] + $insertData['maturity_prepaid_iig'] + $insertData['maturity_prepaid_itc'] + $insertData['maturity_prepaid_nix'];
                
            $insertData['total_target_maturity_commitment'] = $insertData['target_maturity_commitment_postpaid'] + $insertData['target_maturity_commitment_prepaid'];
            $insertData['total_payment_plan'] = $insertData['payment_plan_postpaid'] + $insertData['payment_plan_prepaid'];
            
            $insertData['total_shortfall_target'] = $insertData['shortfall_target_postpaid'] + $insertData['shortfall_target_prepaid'];
            $insertData['total_shortfall_mrc'] = $insertData['shortfall_mrc_postpaid'] + $insertData['shortfall_mrc_prepaid'];
            $insertData['total_shortfall_maturity'] = $insertData['shortfall_maturity_postpaid'] + $insertData['shortfall_maturity_prepaid'];
            $insertData['total_shortfall_payment_plan'] = $insertData['shortfall_payment_plan_postpaid'] + $insertData['shortfall_payment_plan_prepaid'];
            
            $insertData['total_latest_os'] = $insertData['latest_os_balance_postpaid'] + $insertData['latest_os_balance_prepaid'];
            
            $insertData['total_collection'] = 
                $insertData['collection_postpaid_nttn'] + $insertData['collection_postpaid_iig'] + $insertData['collection_postpaid_itc'] + $insertData['collection_postpaid_nix'] +
                $insertData['collection_prepaid_nttn'] + $insertData['collection_prepaid_iig'] + $insertData['collection_prepaid_itc'] + $insertData['collection_prepaid_nix'];
                
            $totColl = (float)$insertData['total_collection'];
            $totMrc = (float)$insertData['total_mrc'];
            $netBacklogTot = (float)$insertData['net_backlog_total'];

            $collMrc = min($totColl, $totMrc);
            $collBacklog = max(0.00, $totColl - $totMrc);
            $mrcShortfall = max(0.00, $totMrc - $collMrc);
            $backlogShortfall = max(0.00, $netBacklogTot - $collBacklog);

            $insertData['collection_mrc'] = $collMrc;
            $insertData['collection_backlog'] = $collBacklog;
            $insertData['mrc_shortfall'] = $mrcShortfall;
            $insertData['backlog_shortfall'] = $backlogShortfall;
                
            $insertData['current_month_remarks'] = isset($data['current_month_remarks']) ? $data['current_month_remarks'] : null;
            $insertData['payment_plan_description'] = isset($data['payment_plan_description']) ? $data['payment_plan_description'] : null;
            
            $insertData['opening_cr'] = isset($data['opening_cr']) ? (float)$data['opening_cr'] : 0.0;
            $insertData['latest_cr'] = isset($data['latest_cr']) ? (float)$data['latest_cr'] : 0.0;
            $insertData['opening_rating_category'] = isset($data['opening_rating_category']) ? $data['opening_rating_category'] : null;
            $insertData['latest_rating_category'] = isset($data['latest_rating_category']) ? $data['latest_rating_category'] : null;

            if ($pc['client']) {
                $client = $pc['client'];
                $updateClientStatusIfNeeded($client, 'Active');
                
                $insertData['client_id'] = $client->client_id;
                $insertData['client_opus_id'] = $client->opus_id;
                $insertData['client_name'] = $client->client_name;
                $insertData['client_status'] = $client->client_status;
                $insertData['client_agreement_status'] = $client->agreement_status;
                $insertData['client_barring_priority'] = $client->barring_priority;
                $insertData['client_btrc_license_discontinuation_date'] = $client->btrc_license_discontinuation_date;
                $insertData['client_legal'] = $client->legal;
                $insertData['client_service_discontinuation_date'] = $client->service_discontinuation_date;
                $insertData['client_billing_modality_kpi'] = $client->billing_modality_kpi;
                $insertData['client_service_type_billing'] = $client->service_type_billing;
                $insertData['client_license_billing'] = $client->license_billing;
                $insertData['client_btrc_letter'] = $client->btrc_letter;
                $insertData['client_security_coverage'] = $client->security_coverage;
                $insertData['client_payment_plan'] = $client->payment_plan;
                $insertData['client_other_upstream'] = $client->other_upstream;
                $insertData['client_sm_kam'] = $client->sm_kam;
                $insertData['client_team_name'] = $client->team_name;
                $insertData['client_collection_kam'] = $client->collection_kam;
                $insertData['client_collection_supervisor'] = $client->collection_supervisor;
                $insertData['client_nttn_billing_kam'] = $client->nttn_billing_kam;
                $insertData['client_iig_itc_billing_kam'] = $client->iig_itc_billing_kam;
                $insertData['client_nttn_billing_commencement_date'] = $client->nttn_billing_commencement_date;
                $insertData['client_iig_itc_billing_commencement_date'] = $client->iig_itc_billing_commencement_date;

                DB::table('monthly_summary')->updateOrInsert(
                    [
                        'client_id' => $client->client_id,
                        'summary_month' => $summaryMonth
                    ],
                    $insertData
                );

                // Sync with collection table: delete existing collections for this client and month
                DB::table('collection')
                    ->where('client_id', $client->client_id)
                    ->where('collection_month', $summaryMonth)
                    ->delete();

                $collectionTypes = [
                    'postpaid_nttn' => 'collection_postpaid_nttn',
                    'postpaid_nttn_iig' => 'collection_postpaid_nttn_iig',
                    'postpaid_iig' => 'collection_postpaid_iig',
                    'postpaid_itc' => 'collection_postpaid_itc',
                    'postpaid_nix' => 'collection_postpaid_nix',
                    'prepaid_nttn' => 'collection_prepaid_nttn',
                    'prepaid_nttn_iig' => 'collection_prepaid_nttn_iig',
                    'prepaid_iig' => 'collection_prepaid_iig',
                    'prepaid_itc' => 'collection_prepaid_itc',
                    'prepaid_nix' => 'collection_prepaid_nix',
                ];

                foreach ($collectionTypes as $enumVal => $field) {
                    $amount = isset($data[$field]) ? (float)$data[$field] : 0.0;
                    if ($amount > 0.0) {
                        DB::table('collection')->insert([
                            'client_id' => $client->client_id,
                            'collection_datetime' => $summaryMonth . ' 23:59:59',
                            'collection_month' => $summaryMonth,
                            'collection_type' => $enumVal,
                            'collection_amount' => $amount,
                            'remarks' => 'Imported from Excel ' . $fileToProcess,
                            'created_by' => 'System',
                            'created_at' => now()
                        ]);
                    }
                }

                $summaryInsertCount++;
            } else {
                // Route unmatched live client to monthly_summary_residue
                $insertData['client_id'] = null;
                $insertData['client_name'] = $pc['excel_name'];
                $insertData['client_opus_id'] = $pc['excel_opus'];
                unset($insertData['collection_mrc'], $insertData['collection_backlog'], $insertData['mrc_shortfall'], $insertData['backlog_shortfall']);

                DB::table('monthly_summary_residue')->updateOrInsert(
                    [
                        'client_name' => $pc['excel_name'],
                        'summary_month' => $summaryMonth
                    ],
                    $insertData
                );
                $residueInsertCount++;
            }
        }
        
        DB::commit();
        $zip->close();
        
        echo json_encode([
            'success' => true,
            'live_clients' => $liveImportCount,
            'discontinued_clients' => $discImportCount,
            'summaries' => $summaryInsertCount,
            'live_residue' => $residueInsertCount,
            'disc_residue' => $discResidueCount
        ]);
        exit;
        
    } catch (\Throwable $e) {
        DB::rollBack();
        $zip->close();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chronological KPI Importer</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --primary: #0ea5e9;
            --primary-hover: #0284c7;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: #334155;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            line-height: 1.6;
            padding: 40px 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            margin-bottom: 40px;
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #38bdf8, #0ea5e9, #6366f1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        p.subtitle {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
            margin-bottom: 30px;
        }

        .control-panel {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 15px;
        }

        .folder-info {
            font-size: 0.95rem;
            color: var(--text-muted);
            word-break: break-all;
        }

        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 28px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px 0 rgba(14, 165, 233, 0.4);
        }

        .btn:hover:not(:disabled) {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn:disabled {
            background-color: var(--border);
            cursor: not-allowed;
            box-shadow: none;
        }

        .progress-container {
            margin-bottom: 30px;
        }

        .progress-bar-wrapper {
            background-color: #0f172a;
            border-radius: 9999px;
            height: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
            margin-bottom: 10px;
        }

        .progress-bar {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #0ea5e9, #10b981);
            border-radius: 9999px;
            transition: width 0.3s ease;
        }

        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .file-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .file-item {
            background-color: #0f172a;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
        }

        .file-item.active {
            border-color: var(--primary);
            box-shadow: 0 0 12px rgba(14, 165, 233, 0.15);
        }

        .file-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .file-name {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .file-date {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .badge {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 9999px;
            text-transform: uppercase;
        }

        .badge-pending {
            background-color: #334155;
            color: #cbd5e1;
        }

        .badge-processing {
            background-color: rgba(14, 165, 233, 0.15);
            color: var(--primary);
            animation: pulse 1.5s infinite;
        }

        .badge-success {
            background-color: rgba(16, 185, 129, 0.15);
            color: var(--success);
        }

        .badge-error {
            background-color: rgba(239, 68, 68, 0.15);
            color: var(--error);
        }

        .stats {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .console-log {
            background-color: #020617;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            font-family: monospace;
            font-size: 0.85rem;
            color: #38bdf8;
            max-height: 250px;
            overflow-y: auto;
            white-space: pre-wrap;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>KPI Data Batch Importer</h1>
            <p class="subtitle">Imports monthly snapshot files in chronological order</p>
        </header>

        <div class="card">
            <div class="control-panel">
                <div class="folder-info">
                    <strong>Source Folder:</strong><br>
                    <span><?= htmlspecialchars($folder) ?></span>
                </div>
                <button id="start-btn" class="btn">Start Import</button>
            </div>

            <div class="progress-container">
                <div class="progress-bar-wrapper">
                    <div id="progress" class="progress-bar"></div>
                </div>
                <div class="progress-text">
                    <span id="progress-percent">0% Completed</span>
                    <span id="progress-fraction">0 / <?= count($filesToImport) ?> Files</span>
                </div>
            </div>

            <div class="file-list">
                <?php $idx = 0; foreach ($filesToImport as $date => $path): $basename = basename($path); ?>
                    <div class="file-item" id="file-row-<?= $idx ?>" data-filename="<?= htmlspecialchars($basename) ?>">
                        <div class="file-details">
                            <div class="file-name"><?= htmlspecialchars($basename) ?></div>
                            <div class="file-date">Target Month: <strong><?= $date ?></strong></div>
                            <div class="stats" id="file-stats-<?= $idx ?>"></div>
                        </div>
                        <div>
                            <span class="badge badge-pending" id="file-badge-<?= $idx ?>">Pending</span>
                        </div>
                    </div>
                <?php $idx++; endforeach; ?>
            </div>
        </div>

        <div class="console-log" id="console">Ready. Press "Start Import" to begin batch processing...</div>
    </div>

    <script>
        const files = <?= json_encode(array_map('basename', array_values($filesToImport))) ?>;
        const startBtn = document.getElementById('start-btn');
        const progressBar = document.getElementById('progress');
        const progressPercent = document.getElementById('progress-percent');
        const progressFraction = document.getElementById('progress-fraction');
        const consoleLog = document.getElementById('console');
        
        let activeIndex = 0;
        
        function log(message) {
            consoleLog.textContent += "\n" + message;
            consoleLog.scrollTop = consoleLog.scrollHeight;
        }

        async function processFile(index) {
            if (index >= files.length) {
                log("\n=========================================");
                log("Batch processing completed successfully!");
                log("=========================================");
                startBtn.disabled = false;
                startBtn.textContent = "Restart Batch";
                return;
            }
            
            const filename = files[index];
            const row = document.getElementById(`file-row-${index}`);
            const badge = document.getElementById(`file-badge-${index}`);
            const stats = document.getElementById(`file-stats-${index}`);
            
            row.classList.add('active');
            badge.className = 'badge badge-processing';
            badge.textContent = 'Processing';
            log(`[${index + 1}/${files.length}] Starting import of: ${filename}...`);
            
            try {
                // Fetch dynamic cache-busting AJAX URL
                const response = await fetch(`?ajax=1&file=${encodeURIComponent(filename)}&t=${Date.now()}`);
                const data = await response.json();
                
                if (data.success) {
                    badge.className = 'badge badge-success';
                    badge.textContent = 'Success';
                    stats.innerHTML = `✓ Live: <strong>${data.live_clients}</strong> (${data.live_residue || 0} residue) | Disc: <strong>${data.discontinued_clients}</strong> (${data.disc_residue || 0} residue) | Summaries: <strong>${data.summaries}</strong>`;
                    log(`  ↳ SUCCESS: Imported ${data.live_clients} live (Residue: ${data.live_residue || 0}), ${data.discontinued_clients} discontinued (Residue: ${data.disc_residue || 0}), and ${data.summaries} summaries.`);
                } else {
                    badge.className = 'badge badge-error';
                    badge.textContent = 'Failed';
                    stats.innerHTML = `✗ Error: <span style="color:var(--error);">${data.message}</span>`;
                    log(`  ↳ FAILED: ${data.message}`);
                }
            } catch (err) {
                badge.className = 'badge badge-error';
                badge.textContent = 'Failed';
                stats.innerHTML = `✗ Network Error`;
                log(`  ↳ FAILED: Network error or server timeout.`);
            }
            
            row.classList.remove('active');
            
            // Update overall progress bar
            const completed = index + 1;
            const pct = Math.round((completed / files.length) * 100);
            progressBar.style.width = `${pct}%`;
            progressPercent.textContent = `${pct}% Completed`;
            progressFraction.textContent = `${completed} / ${files.length} Files`;
            
            // Recurse to next file
            processFile(index + 1);
        }

        startBtn.addEventListener('click', () => {
            startBtn.disabled = true;
            startBtn.textContent = "Processing...";
            consoleLog.textContent = "Initializing batch run...";
            
            // Reset UI states
            progressBar.style.width = '0%';
            progressPercent.textContent = '0% Completed';
            progressFraction.textContent = `0 / ${files.length} Files`;
            
            for (let i = 0; i < files.length; i++) {
                document.getElementById(`file-badge-${i}`).className = 'badge badge-pending';
                document.getElementById(`file-badge-${i}`).textContent = 'Pending';
                document.getElementById(`file-stats-${i}`).innerHTML = '';
            }
            
            processFile(0);
        });
    </script>
</body>
</html>
