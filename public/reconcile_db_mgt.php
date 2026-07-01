<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
}
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');
set_time_limit(900); // 15 minutes max

echo "=== DATABASE vs MANAGEMENT REPORT RECONCILIATION ===\n\n";

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
    
    // Extract month and year
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

// Helper: Allocate total to splits based on modality and service types
$allocateSplits = function($total, $modality, $serviceTypeStr, $metric) {
    $isPrepaid = (strpos(strtolower($modality), 'pre') !== false);
    $prefix = $isPrepaid ? 'prepaid' : 'postpaid';
    
    $services = [];
    $sLower = strtolower($serviceTypeStr);
    if (strpos($sLower, 'nttn') !== false) $services[] = 'nttn';
    if (strpos($sLower, 'iig') !== false) $services[] = 'iig';
    if (strpos($sLower, 'itc') !== false) $services[] = 'itc';
    if (strpos($sLower, 'nix') !== false) $services[] = 'nix';
    
    if (empty($services)) {
        $services[] = 'nttn';
    }
    
    $splits = [];
    $splitVal = round($total / count($services), 2);
    
    foreach ($services as $srv) {
        $splits["{$metric}_{$prefix}_{$srv}"] = $splitVal;
    }
    
    return $splits;
};

// Helper: Scale splits proportionally
$scaleSplits = function($fields, $currentSplitsKeys, $oldTotal, $newTotal) {
    $scaled = [];
    if ($oldTotal == 0.0) {
        foreach ($currentSplitsKeys as $key) {
            $scaled[$key] = 0.0;
        }
    } else {
        $ratio = $newTotal / $oldTotal;
        foreach ($currentSplitsKeys as $key) {
            $val = isset($fields[$key]) ? (float)$fields[$key] : 0.0;
            $scaled[$key] = round($val * $ratio, 2);
        }
    }
    return $scaled;
};

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

$detailMonth = isset($_GET['month']) ? trim($_GET['month']) : null; // e.g. 2025-08-31

foreach ($mgtReports as $month => $reportInfo) {
    if ($detailMonth && $month !== $detailMonth) {
        continue;
    }
    $file = $reportInfo['file'];
    $path = $reportInfo['path'];
    
    echo "Reconciling Month: $month ($file)...\n";
    
    $zip = new ZipArchive();
    if ($zip->open($path) !== TRUE) {
        echo "  ↳ ERROR: Could not open file $file\n\n";
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
    
    // 1. Read Active clients from Raw Data sheet
    $mgtClientsActive = [];
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
                    'modality' => 4,   // E
                    'service' => 5,    // F
                    'mrc' => 9,        // J
                    'opening_os' => 10, // K
                    'opening_cr' => 11, // L
                    'collection' => 13, // N
                    'latest_os' => 14,  // O
                    'latest_cr' => 15,  // P
                ];
            } else {
                $colMap = [
                    'name' => 1,       // B
                    'modality' => 8,   // I
                    'service' => 9,    // J
                    'mrc' => 13,       // N
                    'opening_os' => 14, // O
                    'opening_cr' => 15, // P
                    'collection' => 17, // R
                    'latest_os' => 23,  // X
                    'latest_cr' => 24,  // Y
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
                $openingCR = isset($rowData[$colMap['opening_cr']]) && is_numeric($rowData[$colMap['opening_cr']]) ? (float)$rowData[$colMap['opening_cr']] : 0.0;
                $collection = isset($rowData[$colMap['collection']]) && is_numeric($rowData[$colMap['collection']]) ? (float)$rowData[$colMap['collection']] : 0.0;
                $latestOS = isset($rowData[$colMap['latest_os']]) && is_numeric($rowData[$colMap['latest_os']]) ? (float)$rowData[$colMap['latest_os']] : 0.0;
                $latestCR = isset($rowData[$colMap['latest_cr']]) && is_numeric($rowData[$colMap['latest_cr']]) ? (float)$rowData[$colMap['latest_cr']] : 0.0;
                
                $mgtClientsActive[$cleaned] = [
                    'name' => $cleaned,
                    'original_name' => $cName,
                    'modality' => isset($rowData[$colMap['modality']]) ? trim($rowData[$colMap['modality']]) : '',
                    'service' => isset($rowData[$colMap['service']]) ? trim($rowData[$colMap['service']]) : '',
                    'mrc' => $mrc,
                    'opening_os' => $openingOS,
                    'opening_cr' => $openingCR,
                    'collection' => $collection,
                    'latest_os' => $latestOS,
                    'latest_cr' => $latestCR
                ];
            }
        }
    }
    
    // 2. Read Discontinued clients from Discontinued sheet
    $mgtClientsDisc = [];
    if ($targetPathDisc) {
        $sheetContentDisc = $zip->getFromName('xl/' . $targetPathDisc);
        $wsXmlDisc = simplexml_load_string($sheetContentDisc);
        
        // Find Row 1 and Row 2 to map headers dynamically
        $row1 = [];
        $row2 = [];
        foreach ($wsXmlDisc->sheetData->row as $row) {
            $rowNum = (int)$row['r'];
            if ($rowNum === 1) {
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
                    $row1[$colIdx] = trim($val);
                }
            } elseif ($rowNum === 2) {
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
                    $row2[$colIdx] = trim($val);
                }
                break;
            }
        }
        
        $currentGroup = '';
        $groupHeaders = [];
        for ($i = 0; $i < 250; $i++) {
            if (isset($row1[$i]) && $row1[$i] !== '') {
                $currentGroup = strtolower(trim($row1[$i]));
            }
            $groupHeaders[$i] = $currentGroup;
        }
        
        $colMapDisc = [
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
            'collection_postpaid_nttn' => 163, // Fallbacks
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
                $colMapDisc['client_id'] = $colIdx;
            } elseif ($valClean === 'client name') {
                $colMapDisc['client_name'] = $colIdx;
            } elseif ($valClean === 'service type') {
                $colMapDisc['service_type'] = $colIdx;
            } elseif ($valClean === 'btrc letter') {
                $colMapDisc['btrc_letter'] = $colIdx;
            } elseif ($valClean === 'license') {
                $colMapDisc['license'] = $colIdx;
            } elseif ($valClean === 'other upstream' || $valClean === 'other upsterm') {
                $colMapDisc['other_upstream'] = $colIdx;
            } elseif ($valClean === 'barred or discontinued' || $valClean === 'status') {
                $colMapDisc['status'] = $colIdx;
            } elseif ($valClean === 'btrc license dis. date' || $valClean === 'btrc license discontinuation date') {
                $colMapDisc['btrc_dis_date'] = $colIdx;
            } elseif ($valClean === 'legal') {
                $colMapDisc['legal'] = $colIdx;
            } elseif ($valClean === 'visit remarks / latest updates' || $valClean === 'remarks') {
                $colMapDisc['remarks'] = $colIdx;
            } elseif ($valClean === 'service discontinuation date') {
                $colMapDisc['service_dis_date'] = $colIdx;
            } elseif (strpos($valClean, 'opening os') !== false) {
                $colMapDisc['opening_os'] = $colIdx;
            } elseif ($valClean === 'nttn os') {
                if (strpos($group, 'opening') !== false) {
                    $colMapDisc['opening_os_nttn'] = $colIdx;
                } elseif (strpos($group, 'latest') !== false) {
                    $colMapDisc['latest_os_nttn'] = $colIdx;
                }
            } elseif ($valClean === 'iig os') {
                if (strpos($group, 'opening') !== false) {
                    $colMapDisc['opening_os_iig'] = $colIdx;
                } elseif (strpos($group, 'latest') !== false) {
                    $colMapDisc['latest_os_iig'] = $colIdx;
                }
            } elseif ($valClean === 'itc os') {
                if (strpos($group, 'opening') !== false) {
                    $colMapDisc['opening_os_itc'] = $colIdx;
                } elseif (strpos($group, 'latest') !== false) {
                    $colMapDisc['latest_os_itc'] = $colIdx;
                }
            } elseif ($valClean === 'nix os') {
                if (strpos($group, 'opening') !== false) {
                    $colMapDisc['opening_os_nix'] = $colIdx;
                } elseif (strpos($group, 'latest') !== false) {
                    $colMapDisc['latest_os_nix'] = $colIdx;
                }
            } elseif ($valClean === 'target') {
                $colMapDisc['target'] = $colIdx;
            } elseif (strpos($valClean, 'actual collection') !== false && strpos($group, 'actual collection') === false) {
                $colMapDisc['collection_amount'] = $colIdx;
            } elseif ($valClean === 'collection' || $valClean === 'collection amount') {
                $colMapDisc['collection_amount'] = $colIdx;
            } elseif (strpos($valClean, 'shortfall from target') !== false) {
                $colMapDisc['shortfall_target'] = $colIdx;
            } elseif ($valClean === 'latest os') {
                $colMapDisc['latest_os'] = $colIdx;
            } elseif ($valClean === 'payment plan description') {
                $colMapDisc['payment_plan_description'] = $colIdx;
            } elseif ($valClean === 'pdc amount') {
                $colMapDisc['pdc'] = $colIdx;
            } elseif ($valClean === 'udc amount') {
                $colMapDisc['udc'] = $colIdx;
            } elseif ($valClean === 'total security') {
                $colMapDisc['total_security'] = $colIdx;
            } elseif ($valClean === 'security coverage') {
                $colMapDisc['security_coverage'] = $colIdx;
            } elseif ($valClean === 'pdc chq') {
                $colMapDisc['pdc_chq'] = $colIdx;
            } elseif ($valClean === 'udc chq') {
                $colMapDisc['udc_chq'] = $colIdx;
            } elseif ($valClean === 'expired chq') {
                $colMapDisc['expired_chq'] = $colIdx;
            } elseif ($valClean === 'nttn payment' && strpos($group, 'actual collection') !== false) {
                $colMapDisc['collection_postpaid_nttn'] = $colIdx;
            } elseif ($valClean === 'iig payment' && strpos($group, 'actual collection') !== false) {
                $colMapDisc['collection_postpaid_iig'] = $colIdx;
            } elseif ($valClean === 'itc payment' && strpos($group, 'actual collection') !== false) {
                $colMapDisc['collection_postpaid_itc'] = $colIdx;
            } elseif ($valClean === 'nix payment' && strpos($group, 'actual collection') !== false) {
                $colMapDisc['collection_postpaid_nix'] = $colIdx;
            } elseif ($valClean === 'nttn discontinuation date') {
                $colMapDisc['nttn_discontinuation_date'] = $colIdx;
            } elseif ($valClean === 'iig / itc discontinuation date' || $valClean === 'iig/itc discontinuation date') {
                $colMapDisc['iig_itc_discontinuation_date'] = $colIdx;
            } elseif ($valClean === 'un-billed total' || $valClean === 'unbilled total') {
                $colMapDisc['unbilled_total'] = $colIdx;
            } elseif ($valClean === 'un-billed nttn os' || $valClean === 'unbilled nttn os') {
                $colMapDisc['unbilled_nttn_os'] = $colIdx;
            } elseif ($valClean === 'un-billed iig os' || $valClean === 'unbilled iig os') {
                $colMapDisc['unbilled_iig_os'] = $colIdx;
            } elseif ($valClean === 'un-billed itc os' || $valClean === 'unbilled itc os') {
                $colMapDisc['unbilled_itc_os'] = $colIdx;
            } elseif ($valClean === 'sales kam' || $valClean === 'sales manager') {
                $colMapDisc['sales_kam'] = $colIdx;
            } elseif ($valClean === 'team name') {
                $colMapDisc['team_name'] = $colIdx;
            } elseif ($valClean === 'collections kam' || $valClean === 'collection kam') {
                $colMapDisc['collections_kam'] = $colIdx;
            } elseif ($valClean === 'collection supervisor') {
                $colMapDisc['collection_supervisor'] = $colIdx;
            } elseif ($valClean === 'nttn billing kam' || (strpos($valClean, 'nttn') !== false && strpos($valClean, 'billing kam') !== false)) {
                $colMapDisc['nttn_billing_kam'] = $colIdx;
            } elseif ($valClean === 'iig / itc billing kam' || $valClean === 'iig/itc billing kam' || (strpos($valClean, 'iig') !== false && strpos($valClean, 'billing kam') !== false)) {
                $colMapDisc['iig_itc_billing_kam'] = $colIdx;
            }
        }
        
        foreach ($wsXmlDisc->sheetData->row as $row) {
            $rowNum = (int)$row['r'];
            if ($rowNum <= 2) continue;
            
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
            
            $cName = isset($rowData[$colMapDisc['client_name']]) ? trim($rowData[$colMapDisc['client_name']]) : '';
            if (empty($cName) || strpos(strtolower($cName), 'total') !== false || strpos(strtolower($cName), 'grand') !== false) {
                continue;
            }
            
            $cleaned = $cleanName($cName);
            
            $mgtClientsDisc[$cleaned] = [
                'name' => $cleaned,
                'original_name' => $cName,
                
                'service_type' => isset($rowData[$colMapDisc['service_type']]) ? trim($rowData[$colMapDisc['service_type']]) : '',
                'btrc_letter' => isset($rowData[$colMapDisc['btrc_letter']]) ? trim($rowData[$colMapDisc['btrc_letter']]) : '',
                'license' => isset($rowData[$colMapDisc['license']]) ? trim($rowData[$colMapDisc['license']]) : '',
                'other_upstream' => isset($rowData[$colMapDisc['other_upstream']]) ? trim($rowData[$colMapDisc['other_upstream']]) : '',
                'status' => isset($rowData[$colMapDisc['status']]) ? trim($rowData[$colMapDisc['status']]) : '',
                'btrc_dis_date' => isset($rowData[$colMapDisc['btrc_dis_date']]) ? $excelDateToPhp($rowData[$colMapDisc['btrc_dis_date']]) : null,
                'legal' => isset($rowData[$colMapDisc['legal']]) ? trim($rowData[$colMapDisc['legal']]) : '',
                'remarks' => isset($rowData[$colMapDisc['remarks']]) ? trim($rowData[$colMapDisc['remarks']]) : '',
                'service_dis_date' => isset($rowData[$colMapDisc['service_dis_date']]) ? $excelDateToPhp($rowData[$colMapDisc['service_dis_date']]) : null,
                
                'opening_os' => isset($rowData[$colMapDisc['opening_os']]) && is_numeric($rowData[$colMapDisc['opening_os']]) ? (float)$rowData[$colMapDisc['opening_os']] : 0.0,
                'opening_os_nttn' => isset($rowData[$colMapDisc['opening_os_nttn']]) && is_numeric($rowData[$colMapDisc['opening_os_nttn']]) ? (float)$rowData[$colMapDisc['opening_os_nttn']] : 0.0,
                'opening_os_iig' => isset($rowData[$colMapDisc['opening_os_iig']]) && is_numeric($rowData[$colMapDisc['opening_os_iig']]) ? (float)$rowData[$colMapDisc['opening_os_iig']] : 0.0,
                'opening_os_itc' => isset($rowData[$colMapDisc['opening_os_itc']]) && is_numeric($rowData[$colMapDisc['opening_os_itc']]) ? (float)$rowData[$colMapDisc['opening_os_itc']] : 0.0,
                'opening_os_nix' => isset($rowData[$colMapDisc['opening_os_nix']]) && is_numeric($rowData[$colMapDisc['opening_os_nix']]) ? (float)$rowData[$colMapDisc['opening_os_nix']] : 0.0,
                
                'target' => isset($rowData[$colMapDisc['target']]) && is_numeric($rowData[$colMapDisc['target']]) ? (float)$rowData[$colMapDisc['target']] : 0.0,
                'collection_amount' => isset($rowData[$colMapDisc['collection_amount']]) && is_numeric($rowData[$colMapDisc['collection_amount']]) ? (float)$rowData[$colMapDisc['collection_amount']] : 0.0,
                'shortfall_target' => isset($rowData[$colMapDisc['shortfall_target']]) && is_numeric($rowData[$colMapDisc['shortfall_target']]) ? (float)$rowData[$colMapDisc['shortfall_target']] : 0.0,
                
                'latest_os' => isset($rowData[$colMapDisc['latest_os']]) && is_numeric($rowData[$colMapDisc['latest_os']]) ? (float)$rowData[$colMapDisc['latest_os']] : 0.0,
                'latest_os_nttn' => isset($rowData[$colMapDisc['latest_os_nttn']]) && is_numeric($rowData[$colMapDisc['latest_os_nttn']]) ? (float)$rowData[$colMapDisc['latest_os_nttn']] : 0.0,
                'latest_os_iig' => isset($rowData[$colMapDisc['latest_os_iig']]) && is_numeric($rowData[$colMapDisc['latest_os_iig']]) ? (float)$rowData[$colMapDisc['latest_os_iig']] : 0.0,
                'latest_os_itc' => isset($rowData[$colMapDisc['latest_os_itc']]) && is_numeric($rowData[$colMapDisc['latest_os_itc']]) ? (float)$rowData[$colMapDisc['latest_os_itc']] : 0.0,
                'latest_os_nix' => isset($rowData[$colMapDisc['latest_os_nix']]) && is_numeric($rowData[$colMapDisc['latest_os_nix']]) ? (float)$rowData[$colMapDisc['latest_os_nix']] : 0.0,
                
                'payment_plan_description' => isset($rowData[$colMapDisc['payment_plan_description']]) ? trim($rowData[$colMapDisc['payment_plan_description']]) : '',
                'pdc' => isset($rowData[$colMapDisc['pdc']]) && is_numeric($rowData[$colMapDisc['pdc']]) ? (float)$rowData[$colMapDisc['pdc']] : 0.0,
                'udc' => isset($rowData[$colMapDisc['udc']]) && is_numeric($rowData[$colMapDisc['udc']]) ? (float)$rowData[$colMapDisc['udc']] : 0.0,
                'total_security' => isset($rowData[$colMapDisc['total_security']]) && is_numeric($rowData[$colMapDisc['total_security']]) ? (float)$rowData[$colMapDisc['total_security']] : 0.0,
                'security_coverage' => isset($rowData[$colMapDisc['security_coverage']]) && is_numeric($rowData[$colMapDisc['security_coverage']]) ? (float)$rowData[$colMapDisc['security_coverage']] : 0.0,
                'pdc_chq' => isset($rowData[$colMapDisc['pdc_chq']]) ? trim($rowData[$colMapDisc['pdc_chq']]) : '',
                'udc_chq' => isset($rowData[$colMapDisc['udc_chq']]) ? trim($rowData[$colMapDisc['udc_chq']]) : '',
                'expired_chq' => isset($rowData[$colMapDisc['expired_chq']]) && is_numeric($rowData[$colMapDisc['expired_chq']]) ? (float)$rowData[$colMapDisc['expired_chq']] : 0.0,
                
                'collection_postpaid_nttn' => isset($rowData[$colMapDisc['collection_postpaid_nttn']]) && is_numeric($rowData[$colMapDisc['collection_postpaid_nttn']]) ? (float)$rowData[$colMapDisc['collection_postpaid_nttn']] : 0.0,
                'collection_postpaid_iig' => isset($rowData[$colMapDisc['collection_postpaid_iig']]) && is_numeric($rowData[$colMapDisc['collection_postpaid_iig']]) ? (float)$rowData[$colMapDisc['collection_postpaid_iig']] : 0.0,
                'collection_postpaid_itc' => isset($rowData[$colMapDisc['collection_postpaid_itc']]) && is_numeric($rowData[$colMapDisc['collection_postpaid_itc']]) ? (float)$rowData[$colMapDisc['collection_postpaid_itc']] : 0.0,
                'collection_postpaid_nix' => isset($rowData[$colMapDisc['collection_postpaid_nix']]) && is_numeric($rowData[$colMapDisc['collection_postpaid_nix']]) ? (float)$rowData[$colMapDisc['collection_postpaid_nix']] : 0.0,
                
                'nttn_discontinuation_date' => isset($rowData[$colMapDisc['nttn_discontinuation_date']]) ? $excelDateToPhp($rowData[$colMapDisc['nttn_discontinuation_date']]) : null,
                'iig_itc_discontinuation_date' => isset($rowData[$colMapDisc['iig_itc_discontinuation_date']]) ? $excelDateToPhp($rowData[$colMapDisc['iig_itc_discontinuation_date']]) : null,
                
                'unbilled_total' => isset($rowData[$colMapDisc['unbilled_total']]) && is_numeric($rowData[$colMapDisc['unbilled_total']]) ? (float)$rowData[$colMapDisc['unbilled_total']] : 0.0,
                'unbilled_nttn_os' => isset($rowData[$colMapDisc['unbilled_nttn_os']]) && is_numeric($rowData[$colMapDisc['unbilled_nttn_os']]) ? (float)$rowData[$colMapDisc['unbilled_nttn_os']] : 0.0,
                'unbilled_iig_os' => isset($rowData[$colMapDisc['unbilled_iig_os']]) && is_numeric($rowData[$colMapDisc['unbilled_iig_os']]) ? (float)$rowData[$colMapDisc['unbilled_iig_os']] : 0.0,
                'unbilled_itc_os' => isset($rowData[$colMapDisc['unbilled_itc_os']]) && is_numeric($rowData[$colMapDisc['unbilled_itc_os']]) ? (float)$rowData[$colMapDisc['unbilled_itc_os']] : 0.0,
                
                'sales_kam' => isset($rowData[$colMapDisc['sales_kam']]) ? trim($rowData[$colMapDisc['sales_kam']]) : null,
                'team_name' => isset($rowData[$colMapDisc['team_name']]) ? trim($rowData[$colMapDisc['team_name']]) : null,
                'collections_kam' => isset($rowData[$colMapDisc['collections_kam']]) ? trim($rowData[$colMapDisc['collections_kam']]) : null,
                'collection_supervisor' => isset($rowData[$colMapDisc['collection_supervisor']]) ? trim($rowData[$colMapDisc['collection_supervisor']]) : null,
                'nttn_billing_kam' => isset($rowData[$colMapDisc['nttn_billing_kam']]) ? trim($rowData[$colMapDisc['nttn_billing_kam']]) : null,
                'iig_itc_billing_kam' => isset($rowData[$colMapDisc['iig_itc_billing_kam']]) ? trim($rowData[$colMapDisc['iig_itc_billing_kam']]) : null,
            ];
        }
    }
    
    $zip->close();
    
    // Start Database Transaction for this month's reconciliation
    DB::beginTransaction();
    
    try {
        $clearedCollectionsClientIds = [];
        // Fetch DB data for this month
        $dbDataActive = DB::table('monthly_summary')
            ->join('client', 'monthly_summary.client_id', '=', 'client.client_id')
            ->where('monthly_summary.summary_month', $month)
            ->select('client.client_name', 'monthly_summary.client_id', 'monthly_summary.monthly_summary_id')
            ->get();
            
        $dbDataDisc = DB::table('monthly_summary_discontinued')
            ->join('client', 'monthly_summary_discontinued.client_id', '=', 'client.client_id')
            ->where('monthly_summary_discontinued.summary_month', $month)
            ->select('client.client_name', 'monthly_summary_discontinued.client_id', 'monthly_summary_discontinued.monthly_summary_discontinued_id')
            ->get();
            
        $dbClientsActive = [];
        foreach ($dbDataActive as $row) {
            $cleaned = $cleanName($row->client_name);
            $dbClientsActive[$cleaned][] = [
                'client_id' => $row->client_id,
                'monthly_summary_id' => $row->monthly_summary_id
            ];
        }
        
        $dbClientsDisc = [];
        foreach ($dbDataDisc as $row) {
            $cleaned = $cleanName($row->client_name);
            $dbClientsDisc[$cleaned][] = [
                'client_id' => $row->client_id,
                'monthly_summary_discontinued_id' => $row->monthly_summary_discontinued_id
            ];
        }
        
        // A. Process Deletions: Remove records in DB that are not in the Management Report
        $deletedCount = 0;
        
        // Active deletions
        foreach ($dbClientsActive as $name => $infos) {
            if (!isset($mgtClientsActive[$name])) {
                foreach ($infos as $info) {
                    DB::table('collection')
                        ->where('client_id', $info['client_id'])
                        ->where('collection_month', $month)
                        ->delete();
                        
                    DB::table('monthly_summary')
                        ->where('monthly_summary_id', $info['monthly_summary_id'])
                        ->delete();
                        
                    $deletedCount++;
                }
            }
        }
        
        // Discontinued deletions
        foreach ($dbClientsDisc as $name => $infos) {
            if (!isset($mgtClientsDisc[$name])) {
                foreach ($infos as $info) {
                    DB::table('collection')
                        ->where('client_id', $info['client_id'])
                        ->where('collection_month', $month)
                        ->delete();
                        
                    DB::table('monthly_summary_discontinued')
                        ->where('monthly_summary_discontinued_id', $info['monthly_summary_discontinued_id'])
                        ->delete();
                        
                    $deletedCount++;
                }
            }
        }
        
        if ($deletedCount > 0) {
            echo "  Removed $deletedCount records not in Management Report sheets.\n";
        }
        
        // B. Reconcile Active Clients
        $reconciledActive = 0;
        $insertedActive = 0;
        
        foreach ($mgtClientsActive as $name => $m) {
            $clientRows = DB::table('client')->where('client_name', $m['original_name'])->get();
            if ($clientRows->isEmpty()) {
                $clientRows = DB::table('client')->where('client_name', $name)->get();
            }
            
            if ($clientRows->isEmpty()) {
                $opusId = "MGT-ACT-" . rand(100000, 999999);
                $clientId = DB::table('client')->insertGetId([
                    'opus_id' => $opusId,
                    'client_name' => $m['original_name'],
                    'client_status' => 'Active',
                    'agreement_status' => 'Pending',
                    'billing_modality_kpi' => $m['modality'],
                    'service_type_billing' => $m['service'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $clientIds = [$clientId];
            } else {
                $clientIds = $clientRows->pluck('client_id')->toArray();
                // Ensure client status is Active
                DB::table('client')->where('client_id', $clientIds[0])->update([
                    'client_status' => 'Active',
                    'billing_modality_kpi' => $m['modality'],
                    'service_type_billing' => $m['service'],
                    'updated_at' => now()
                ]);
            }
            
            $clientId = $clientIds[0];
            
            // Delete discontinued summary if any, only if not also present in discontinued sheet
            if (!isset($mgtClientsDisc[$name])) {
                DB::table('monthly_summary_discontinued')
                    ->where('client_id', $clientId)
                    ->where('summary_month', $month)
                    ->delete();
            }
                
            // Deduplicate other active summary records if any
            for ($i = 1; $i < count($clientIds); $i++) {
                $dupId = $clientIds[$i];
                DB::table('collection')->where('client_id', $dupId)->where('collection_month', $month)->delete();
                DB::table('monthly_summary')->where('client_id', $dupId)->where('summary_month', $month)->delete();
            }
            
            $summaryRow = DB::table('monthly_summary')
                ->where('client_id', $clientId)
                ->where('summary_month', $month)
                ->first();
                
            $summaryFields = [];
            $openingOSKeys = [
                'opening_os_postpaid_nttn', 'opening_os_postpaid_iig_nttn', 'opening_os_postpaid_iig', 'opening_os_postpaid_itc', 'opening_os_postpaid_nix',
                'opening_os_prepaid_nttn', 'opening_os_prepaid_iig_nttn', 'opening_os_prepaid_iig', 'opening_os_prepaid_itc', 'opening_os_prepaid_nix'
            ];
            $mrcKeys = [
                'mrc_postpaid_nttn', 'mrc_postpaid_nttn_iig', 'mrc_postpaid_iig', 'mrc_postpaid_itc', 'mrc_postpaid_nix',
                'mrc_prepaid_nttn', 'mrc_prepaid_nttn_iig', 'mrc_prepaid_iig', 'mrc_prepaid_itc', 'mrc_prepaid_nix'
            ];
            $latestOSKeys = [
                'latest_os_balance_postpaid', 'latest_os_balance_prepaid'
            ];
            $collectionKeys = [
                'collection_postpaid_nttn', 'collection_postpaid_nttn_iig', 'collection_postpaid_iig', 'collection_postpaid_itc', 'collection_postpaid_nix',
                'collection_prepaid_nttn', 'collection_prepaid_nttn_iig', 'collection_prepaid_iig', 'collection_prepaid_itc', 'collection_prepaid_nix'
            ];
            
            $isPrepaid = (strpos(strtolower($m['modality']), 'pre') !== false);
            $prefix = $isPrepaid ? 'prepaid' : 'postpaid';
            $otherPrefix = $isPrepaid ? 'postpaid' : 'prepaid';
            
            $filterKeys = function($keys, $pref) {
                return array_filter($keys, function($k) use ($pref) {
                    return strpos($k, $pref) !== false;
                });
            };
            
            $activeOpeningOSKeys = $filterKeys($openingOSKeys, $prefix);
            $inactiveOpeningOSKeys = $filterKeys($openingOSKeys, $otherPrefix);
            $activeMrcKeys = $filterKeys($mrcKeys, $prefix);
            $inactiveMrcKeys = $filterKeys($mrcKeys, $otherPrefix);
            $activeLatestOSKeys = $filterKeys($latestOSKeys, $prefix);
            $inactiveLatestOSKeys = $filterKeys($latestOSKeys, $otherPrefix);
            $activeCollectionKeys = $filterKeys($collectionKeys, $prefix);
            $inactiveCollectionKeys = $filterKeys($collectionKeys, $otherPrefix);
            
            $inactiveFields = [];
            foreach (array_merge($inactiveOpeningOSKeys, $inactiveMrcKeys, $inactiveLatestOSKeys, $inactiveCollectionKeys) as $k) {
                $inactiveFields[$k] = 0.0;
            }
            
            if ($summaryRow) {
                $fields = (array)$summaryRow;
                
                $activeFieldsInit = [];
                foreach (array_merge($activeOpeningOSKeys, $activeMrcKeys, $activeLatestOSKeys, $activeCollectionKeys) as $k) {
                    $activeFieldsInit[$k] = 0.0;
                }
                
                // Opening OS
                $oldOpeningOS = 0.0;
                foreach ($activeOpeningOSKeys as $k) {
                    $oldOpeningOS += isset($fields[$k]) ? (float)$fields[$k] : 0.0;
                }
                if ($oldOpeningOS == 0.0 && $m['opening_os'] > 0.0) {
                    $scaledOpening = $allocateSplits($m['opening_os'], $m['modality'], $m['service'], 'opening_os');
                } else {
                    $scaledOpening = $scaleSplits($fields, $activeOpeningOSKeys, $oldOpeningOS, $m['opening_os']);
                }
                
                // MRC
                $oldMRC = 0.0;
                foreach ($activeMrcKeys as $k) {
                    $oldMRC += isset($fields[$k]) ? (float)$fields[$k] : 0.0;
                }
                if ($oldMRC == 0.0 && $m['mrc'] > 0.0) {
                    $scaledMRC = $allocateSplits($m['mrc'], $m['modality'], $m['service'], 'mrc');
                } else {
                    $scaledMRC = $scaleSplits($fields, $activeMrcKeys, $oldMRC, $m['mrc']);
                }
                
                // Latest OS
                $oldLatestOS = 0.0;
                foreach ($activeLatestOSKeys as $k) {
                    $oldLatestOS += isset($fields[$k]) ? (float)$fields[$k] : 0.0;
                }
                if ($oldLatestOS == 0.0 && $m['latest_os'] > 0.0) {
                    $scaledLatest = [];
                    if ($isPrepaid) {
                        $scaledLatest['latest_os_balance_prepaid'] = $m['latest_os'];
                    } else {
                        $scaledLatest['latest_os_balance_postpaid'] = $m['latest_os'];
                    }
                } else {
                    $scaledLatest = $scaleSplits($fields, $activeLatestOSKeys, $oldLatestOS, $m['latest_os']);
                }
                
                // Collection
                $oldCollection = 0.0;
                foreach ($activeCollectionKeys as $k) {
                    $oldCollection += isset($fields[$k]) ? (float)$fields[$k] : 0.0;
                }
                if ($oldCollection == 0.0 && $m['collection'] > 0.0) {
                    $scaledCollection = $allocateSplits($m['collection'], $m['modality'], $m['service'], 'collection');
                } else {
                    $scaledCollection = $scaleSplits($fields, $activeCollectionKeys, $oldCollection, $m['collection']);
                }
                
                $summaryFields = array_merge($activeFieldsInit, $scaledOpening, $scaledMRC, $scaledLatest, $scaledCollection, $inactiveFields);
                
                $summaryFields['total_opening_os'] = $m['opening_os'];
                $summaryFields['total_mrc'] = $m['mrc'];
                $summaryFields['total_latest_os'] = $m['latest_os'];
                $summaryFields['total_collection'] = $m['collection'];
                $summaryFields['opening_cr'] = $m['opening_cr'];
                $summaryFields['latest_cr'] = $m['latest_cr'];
                $summaryFields['opening_rating_category'] = $getRatingCategory($m['opening_cr']);
                $summaryFields['latest_rating_category'] = $getRatingCategory($m['latest_cr']);
                $summaryFields['net_backlog_total'] = $m['opening_os'] - $m['mrc'];
                
                DB::table('monthly_summary')
                    ->where('monthly_summary_id', $summaryRow->monthly_summary_id)
                    ->update($summaryFields);
                
                $reconciledActive++;
            } else {
                foreach (array_merge($openingOSKeys, $mrcKeys, $latestOSKeys, $collectionKeys) as $key) {
                    $summaryFields[$key] = 0.0;
                }
                
                $allocatedOpening = $allocateSplits($m['opening_os'], $m['modality'], $m['service'], 'opening_os');
                $allocatedMRC = $allocateSplits($m['mrc'], $m['modality'], $m['service'], 'mrc');
                $allocatedCollection = $allocateSplits($m['collection'], $m['modality'], $m['service'], 'collection');
                
                if ($isPrepaid) {
                    $summaryFields['latest_os_balance_prepaid'] = $m['latest_os'];
                } else {
                    $summaryFields['latest_os_balance_postpaid'] = $m['latest_os'];
                }
                
                $summaryFields = array_merge($summaryFields, $allocatedOpening, $allocatedMRC, $allocatedCollection);
                
                $summaryFields['client_id'] = $clientId;
                $summaryFields['summary_month'] = $month;
                $summaryFields['total_opening_os'] = $m['opening_os'];
                $summaryFields['total_mrc'] = $m['mrc'];
                $summaryFields['total_latest_os'] = $m['latest_os'];
                $summaryFields['total_collection'] = $m['collection'];
                $summaryFields['opening_cr'] = $m['opening_cr'];
                $summaryFields['latest_cr'] = $m['latest_cr'];
                $summaryFields['opening_rating_category'] = $getRatingCategory($m['opening_cr']);
                $summaryFields['latest_rating_category'] = $getRatingCategory($m['latest_cr']);
                $summaryFields['net_backlog_total'] = $m['opening_os'] - $m['mrc'];
                
                DB::table('monthly_summary')->insert($summaryFields);
                $insertedActive++;
            }
            
            // Sync collection table for Active client (clear only once per month)
            if (!in_array($clientId, $clearedCollectionsClientIds)) {
                DB::table('collection')
                    ->where('client_id', $clientId)
                    ->where('collection_month', $month)
                    ->delete();
                $clearedCollectionsClientIds[] = $clientId;
            }
                
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
                $amount = isset($summaryFields[$field]) ? (float)$summaryFields[$field] : 0.0;
                if ($amount > 0.0) {
                    DB::table('collection')->insert([
                        'client_id' => $clientId,
                        'collection_datetime' => $month . ' 23:59:59',
                        'collection_month' => $month,
                        'collection_type' => $enumVal,
                        'collection_amount' => $amount,
                        'remarks' => 'Reconciled from Management Report ' . $file,
                        'created_by' => 'System',
                        'created_at' => now()
                    ]);
                }
            }
        }
        
        // C. Reconcile Discontinued Clients
        $reconciledDisc = 0;
        $insertedDisc = 0;
        
        foreach ($mgtClientsDisc as $name => $m) {
            $clientRows = DB::table('client')->where('client_name', $m['original_name'])->get();
            if ($clientRows->isEmpty()) {
                $clientRows = DB::table('client')->where('client_name', $name)->get();
            }
            
            $status = empty($m['status']) ? 'Discontinued' : $m['status'];
            
            if ($clientRows->isEmpty()) {
                $opusId = "MGT-DSC-" . rand(100000, 999999);
                $clientId = DB::table('client')->insertGetId([
                    'opus_id' => $opusId,
                    'client_name' => $m['original_name'],
                    'client_status' => $status,
                    'agreement_status' => 'Discontinued',
                    'btrc_license_discontinuation_date' => $m['btrc_dis_date'],
                    'legal' => ($m['legal'] && strtolower($m['legal']) !== 'no') ? true : false,
                    'service_discontinuation_date' => $m['service_dis_date'],
                    'billing_modality_kpi' => $m['service_type'],
                    'service_type_billing' => $m['service_type'],
                    'license_billing' => $m['license'],
                    'btrc_letter' => $m['btrc_letter'],
                    'security_coverage' => $m['security_coverage'],
                    'payment_plan' => $m['payment_plan_description'],
                    'other_upstream' => ($m['other_upstream'] && strpos(strtolower($m['other_upstream']), 'only') === false) ? true : false,
                    'sm_kam' => $m['sales_kam'],
                    'team_name' => $m['team_name'],
                    'collection_kam' => $m['collections_kam'],
                    'collection_supervisor' => $m['collection_supervisor'],
                    'nttn_billing_kam' => $m['nttn_billing_kam'],
                    'iig_itc_billing_kam' => $m['iig_itc_billing_kam'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $clientIds = [$clientId];
            } else {
                $clientIds = $clientRows->pluck('client_id')->toArray();
                DB::table('client')->where('client_id', $clientIds[0])->update([
                    'client_status' => $status,
                    'btrc_license_discontinuation_date' => $m['btrc_dis_date'],
                    'legal' => ($m['legal'] && strtolower($m['legal']) !== 'no') ? true : false,
                    'service_discontinuation_date' => $m['service_dis_date'],
                    'billing_modality_kpi' => $m['service_type'],
                    'service_type_billing' => $m['service_type'],
                    'license_billing' => $m['license'],
                    'btrc_letter' => $m['btrc_letter'],
                    'security_coverage' => $m['security_coverage'],
                    'payment_plan' => $m['payment_plan_description'],
                    'other_upstream' => ($m['other_upstream'] && strpos(strtolower($m['other_upstream']), 'only') === false) ? true : false,
                    'sm_kam' => $m['sales_kam'],
                    'team_name' => $m['team_name'],
                    'collection_kam' => $m['collections_kam'],
                    'collection_supervisor' => $m['collection_supervisor'],
                    'nttn_billing_kam' => $m['nttn_billing_kam'],
                    'iig_itc_billing_kam' => $m['iig_itc_billing_kam'],
                    'updated_at' => now()
                ]);
            }
            
            $clientId = $clientIds[0];
            
            // Delete active summary if any, only if not also present in active sheet
            if (!isset($mgtClientsActive[$name])) {
                DB::table('monthly_summary')
                    ->where('client_id', $clientId)
                    ->where('summary_month', $month)
                    ->delete();
            }
                
            // Deduplicate other discontinued summary records if any
            for ($i = 1; $i < count($clientIds); $i++) {
                $dupId = $clientIds[$i];
                DB::table('collection')->where('client_id', $dupId)->where('collection_month', $month)->delete();
                DB::table('monthly_summary_discontinued')->where('client_id', $dupId)->where('summary_month', $month)->delete();
            }
            
            $summaryFieldsDisc = [
                'opening_os' => $m['opening_os'],
                'opening_os_nttn' => $m['opening_os_nttn'],
                'opening_os_iig' => $m['opening_os_iig'],
                'opening_os_itc' => $m['opening_os_itc'],
                'opening_os_nix' => $m['opening_os_nix'],
                
                'target' => $m['target'],
                'collection_amount' => $m['collection_amount'],
                'shortfall_target' => $m['shortfall_target'],
                
                'latest_os' => $m['latest_os'],
                'latest_os_nttn' => $m['latest_os_nttn'],
                'latest_os_iig' => $m['latest_os_iig'],
                'latest_os_itc' => $m['latest_os_itc'],
                'latest_os_nix' => $m['latest_os_nix'],
                
                'payment_plan_description' => $m['payment_plan_description'],
                'pdc' => $m['pdc'],
                'udc' => $m['udc'],
                'total_security' => $m['total_security'],
                'security_coverage' => $m['security_coverage'],
                'pdc_chq' => $m['pdc_chq'],
                'udc_chq' => $m['udc_chq'],
                'expired_chq' => $m['expired_chq'],
                
                'collection_postpaid_nttn' => $m['collection_postpaid_nttn'],
                'collection_postpaid_iig' => $m['collection_postpaid_iig'],
                'collection_postpaid_itc' => $m['collection_postpaid_itc'],
                'collection_postpaid_nix' => $m['collection_postpaid_nix'],
                'total_collection' => $m['collection_amount'],
                
                'nttn_discontinuation_date' => $m['nttn_discontinuation_date'],
                'iig_itc_discontinuation_date' => $m['iig_itc_discontinuation_date'],
                
                'unbilled_total' => $m['unbilled_total'],
                'unbilled_nttn_os' => $m['unbilled_nttn_os'],
                'unbilled_iig_os' => $m['unbilled_iig_os'],
                'unbilled_itc_os' => $m['unbilled_itc_os'],
                
                'updated_at' => now()
            ];
            
            $summaryRowDisc = DB::table('monthly_summary_discontinued')
                ->where('client_id', $clientId)
                ->where('summary_month', $month)
                ->first();
                
            if ($summaryRowDisc) {
                DB::table('monthly_summary_discontinued')
                    ->where('monthly_summary_discontinued_id', $summaryRowDisc->monthly_summary_discontinued_id)
                    ->update($summaryFieldsDisc);
                $reconciledDisc++;
            } else {
                $summaryFieldsDisc['client_id'] = $clientId;
                $summaryFieldsDisc['summary_month'] = $month;
                $summaryFieldsDisc['created_at'] = now();
                DB::table('monthly_summary_discontinued')->insert($summaryFieldsDisc);
                $insertedDisc++;
            }
            
            // Sync collection table for discontinued client (clear only once per month)
            if (!in_array($clientId, $clearedCollectionsClientIds)) {
                DB::table('collection')
                    ->where('client_id', $clientId)
                    ->where('collection_month', $month)
                    ->delete();
                $clearedCollectionsClientIds[] = $clientId;
            }
                
            $collectionTypesDisc = [
                'postpaid_nttn' => 'collection_postpaid_nttn',
                'postpaid_iig' => 'collection_postpaid_iig',
                'postpaid_itc' => 'collection_postpaid_itc',
                'postpaid_nix' => 'collection_postpaid_nix',
            ];
            
            foreach ($collectionTypesDisc as $enumVal => $field) {
                $amount = isset($summaryFieldsDisc[$field]) ? (float)$summaryFieldsDisc[$field] : 0.0;
                if ($amount > 0.0) {
                    DB::table('collection')->insert([
                        'client_id' => $clientId,
                        'collection_datetime' => $month . ' 23:59:59',
                        'collection_month' => $month,
                        'collection_type' => $enumVal,
                        'collection_amount' => $amount,
                        'remarks' => 'Reconciled discontinued collection from Management Report ' . $file,
                        'created_by' => 'System',
                        'created_at' => now()
                    ]);
                }
            }
        }
        
        echo "  ↳ Reconciled Active: $reconciledActive updated, $insertedActive new active clients inserted.\n";
        echo "  ↳ Reconciled Discontinued: $reconciledDisc updated, $insertedDisc new discontinued clients inserted.\n\n";
        DB::commit();
        
    } catch (\Throwable $e) {
        DB::rollBack();
        echo "  ↳ FAILED: " . $e->getMessage() . "\n\n";
    }
}

echo "Reconciliation completed!\n";
