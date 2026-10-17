<?php
if (function_exists('opcache_reset')) { opcache_reset(); }
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;

set_time_limit(600);
ini_set('memory_limit', '1024M');
header('Content-Type: text/plain; charset=utf-8');

$dir = 'D:\D\Automation-Innovation\scomm-collection\KPI_ENGINE_FILES';

$monthFiles = [
    '2025-06-30' => '1KPI ENGINE_LIVE & Discontinued ISPs_Jun\'25.xlsx',
    '2025-07-31' => '2KPI ENGINE_LIVE & Discontinued ISPs_Jul\'25_31 Jul-25 Final.xlsx',
    '2025-08-31' => '3KPI ENGINE_LIVE & Discontinued ISPs_Aug\'25_31 Aug-25 Final.xlsx',
    '2025-09-30' => '5KPI ENGINE_LIVE & Discontinued ISPs_Sep\'25_30 Sep-25 Final.xlsx',
    '2025-10-31' => '4KPI ENGINE_LIVE & Discontinued ISPs_Oct\'25_31 Oct-25 Updated V1.xlsx',
    '2025-11-30' => '6KPI ENGINE_LIVE & Discontinued ISPs_Nov\'25_30 Nov-25 Updated.xlsx',
    '2025-12-31' => '7KPI ENGINE_LIVE & Discontinued ISPs_Dec\'25_31st Dec-25 Updated.xlsx',
    '2026-01-31' => '8KPI ENGINE_LIVE & Discontinued ISPs_Jan\'26_31st Jan\'26 Updated.xlsx',
    '2026-02-28' => '9KPI ENGINE_LIVE & Discontinued ISPs_Feb\'26.xlsx',
    '2026-03-31' => '10KPI ENGINE_LIVE & Discontinued ISPs_Mar\'26_31st Mar\'26.xlsx',
    '2026-04-30' => '11KPI ENGINE_LIVE & Discontinued ISPs_Apr\'26_30th Apr\'26.xlsx',
    '2026-05-31' => '12KPI ENGINE_LIVE & Discontinued ISPs_May\'26_31th.xlsx',
    '2026-06-30' => '13KPI_ENGINE_LIVE_&_Discontinued_ISPs_June\'26_30th_June\'26_Updated.xlsx',
    '2026-07-31' => '14KPI_ENGINE_LIVE_&_Discontinued_ISPs_July\'26_31st_July\'26_Final.xlsx',
    '2026-08-31' => '17KPI_ENGINE_LIVE_&_Discontinued_ISPs_Aug\'26_31st_Aug\'26_updated_KAM.xlsx',
    '2026-09-30' => '18KPI_ENGINE_LIVE_&_Discontinued_ISPs_Sep\'26_30th_Sep\'26_Final.xlsx',
];

// Optional filter: ?month=2026-09-30
$targetMonth = $_GET['month'] ?? null;
if ($targetMonth) {
    if (!isset($monthFiles[$targetMonth])) {
        echo "Error: Unknown month $targetMonth\n";
        exit;
    }
    $monthFiles = [$targetMonth => $monthFiles[$targetMonth]];
}

// Helpers
$cleanClientName = function($name) {
    $name = trim($name);
    $name = preg_replace('/^\d+[\.\)\-_\/]+\s*/', '', $name);
    $name = preg_replace('/\s+/', ' ', $name);
    return trim($name);
};

// Build Client Lookup Map
$clients = DB::table('client')->select('client_id', 'client_name', 'opus_id')->get();
$clientMap = [];
foreach ($clients as $c) {
    $clientMap[strtolower(trim($c->client_name))] = $c->client_id;
    if ($c->opus_id) {
        $clientMap[strtolower(trim($c->opus_id))] = $c->client_id;
    }
}
$residueMappings = DB::table('residue_client_mapping')->get();
foreach ($residueMappings as $rm) {
    if (isset($clientMap[strtolower(trim($rm->c_client_name))])) {
        $clientMap[strtolower(trim($rm->client_name))] = $clientMap[strtolower(trim($rm->c_client_name))];
    }
}

function parseSheetXmlRows($zip, $target, $sharedStrings) {
    $sheetXml = $zip->getFromName($target);
    if (!$sheetXml) return [];
    $reader = new XMLReader();
    $reader->xml($sheetXml);
    $rows = [];
    $rIdx = 0;
    while ($reader->read()) {
        if ($reader->nodeType == XMLReader::ELEMENT && $reader->name === 'row') {
            $rNode = new SimpleXMLElement($reader->readOuterXML());
            $rowArr = [];
            foreach ($rNode->c as $c) {
                $cellRef = (string)$c['r'];
                preg_match('/([A-Z]+)(\d+)/', $cellRef, $matches);
                if (!isset($matches[1])) continue;
                $colLetters = $matches[1];
                $colIdx = 0;
                for ($i = 0; $i < strlen($colLetters); $i++) {
                    $colIdx = $colIdx * 26 + (ord($colLetters[$i]) - 64);
                }
                $colIdx--;

                $val = '';
                $type = (string)$c['t'];
                if ($type === 's') $val = $sharedStrings[(int)$c->v] ?? '';
                elseif ($type === 'inlineStr') $val = (string)$c->is->t;
                else $val = (string)$c->v;
                $rowArr[$colIdx] = trim($val);
            }
            $rows[$rIdx] = $rowArr;
            $rIdx++;
        }
    }
    $reader->close();
    return $rows;
}

echo "====================================================================\n";
echo "HISTORICAL LATEST OS BATCH UPDATER\n";
echo "Total months to process: " . count($monthFiles) . "\n";
echo "====================================================================\n\n";

$overallStart = microtime(true);

foreach ($monthFiles as $summaryMonth => $filename) {
    $filePath = $dir . DIRECTORY_SEPARATOR . $filename;
    echo ">>> Processing Month: $summaryMonth ($filename)\n";

    if (!file_exists($filePath)) {
        echo "    ERROR: File not found: $filePath\n\n";
        continue;
    }

    $monthStart = microtime(true);
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        echo "    ERROR: Cannot open zip archive\n\n";
        continue;
    }

    $wbXmlStr = $zip->getFromName('xl/workbook.xml');
    $relsXmlStr = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if (!$wbXmlStr || !$relsXmlStr) {
        $zip->close();
        echo "    ERROR: Invalid workbook format\n\n";
        continue;
    }

    $wbDoc = simplexml_load_string($wbXmlStr);
    $relsDoc = simplexml_load_string($relsXmlStr);
    $relMap = [];
    foreach ($relsDoc->Relationship as $rel) {
        $relMap[(string)$rel['Id']] = (string)$rel['Target'];
    }

    $prepaidTarget = null;
    $postpaidTarget = null;
    foreach ($wbDoc->sheets->sheet as $sheet) {
        $name = (string)$sheet['name'];
        $rId = (string)$sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
        $target = $relMap[$rId] ?? '';
        if (strpos($target, '/') !== 0 && strpos($target, 'xl/') !== 0) $target = 'xl/' . $target;

        if (stripos($name, 'Pre-Paid') !== false && stripos($name, 'Billing') !== false) {
            $prepaidTarget = $target;
        }
        if (stripos($name, 'Post-Paid') !== false && stripos($name, 'Collection') !== false) {
            $postpaidTarget = $target;
        }
    }

    if (!$prepaidTarget) {
        $zip->close();
        echo "    ERROR: LIVE_Pre-Paid (Billing) sheet not found in workbook\n\n";
        continue;
    }

    // Shared Strings
    $sharedStrings = [];
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml) {
        $reader = new XMLReader();
        $reader->xml($ssXml);
        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT && $reader->name === 'si') {
                $node = new SimpleXMLElement($reader->readOuterXML());
                $text = '';
                if (isset($node->t)) $text = (string)$node->t;
                elseif (isset($node->r)) {
                    foreach ($node->r as $r) $text .= (string)$r->t;
                }
                $sharedStrings[] = $text;
            }
        }
        $reader->close();
    }

    // 1. Parse Prepaid sheet
    $preRows = parseSheetXmlRows($zip, $prepaidTarget, $sharedStrings);
    $colPrepaidTotal = 206; $colPrepaidNttn = 207; $colPrepaidIig = 208; $colPrepaidItc = 209; $colPrepaidNix = 210;
    for ($rIdx = 0; $rIdx < min(3, count($preRows)); $rIdx++) {
        foreach ($preRows[$rIdx] as $cIdx => $val) {
            $vClean = strtolower(trim($val));
            if (strpos($vClean, 'latest os balance') !== false) {
                $colPrepaidTotal = $cIdx;
            } elseif ($vClean === 'nttn os' && $cIdx > 150) {
                $colPrepaidNttn = $cIdx;
            } elseif ($vClean === 'iig os' && $cIdx > 150) {
                $colPrepaidIig = $cIdx;
            } elseif ($vClean === 'itc os' && $cIdx > 150) {
                $colPrepaidItc = $cIdx;
            } elseif ($vClean === 'nix os' && $cIdx > 150) {
                $colPrepaidNix = $cIdx;
            }
        }
    }

    // 2. Parse Postpaid sheet
    $postRows = $postpaidTarget ? parseSheetXmlRows($zip, $postpaidTarget, $sharedStrings) : [];
    $colPostpaidTotal = 36; $colPostpaidNttn = 37; $colPostpaidIig = 38; $colPostpaidItc = 39; $colPostpaidNix = 40;
    for ($rIdx = 0; $rIdx < min(3, count($postRows)); $rIdx++) {
        foreach ($postRows[$rIdx] as $cIdx => $val) {
            $vClean = strtolower(trim($val));
            if (strpos($vClean, 'latest os balance') !== false) {
                $colPostpaidTotal = $cIdx;
            } elseif ($vClean === 'nttn os' && $cIdx >= 30 && $cIdx <= 60) {
                $colPostpaidNttn = $cIdx;
            } elseif ($vClean === 'iig os' && $cIdx >= 30 && $cIdx <= 60) {
                $colPostpaidIig = $cIdx;
            } elseif ($vClean === 'itc os' && $cIdx >= 30 && $cIdx <= 60) {
                $colPostpaidItc = $cIdx;
            } elseif ($vClean === 'nix os' && $cIdx >= 30 && $cIdx <= 60) {
                $colPostpaidNix = $cIdx;
            }
        }
    }

    $zip->close();

    $isNonClientRow = function($rawName) {
        $t = trim($rawName);
        if (empty($t) || is_numeric($t)) return true;
        $tl = strtolower($t);
        if (strpos($tl, 'total') !== false) return true;
        if (strpos($tl, 'btrc letter') !== false) return true;
        if (strpos($tl, 'common in') !== false) return true;
        if (in_array($tl, ['y-yes', 'n-no', 'p-potential', 'remarks', 'status', 'sl', 'client name'])) return true;
        return false;
    };

    // Aggregate by Client
    $clientOS = []; // [cid => [...]]
    $residueOS = []; // [clientNameClean => [...]]

    // Collect Prepaid
    foreach ($preRows as $rn => $r) {
        if ($rn < 2 || empty($r[0])) continue;
        $rawName = $r[0];
        if ($isNonClientRow($rawName)) continue;
        
        $nameClean = $cleanClientName($rawName);
        $cid = $clientMap[strtolower($nameClean)] ?? null;

        $bal  = isset($r[$colPrepaidTotal]) && is_numeric($r[$colPrepaidTotal]) ? (float)$r[$colPrepaidTotal] : 0.0;
        $nttn = isset($r[$colPrepaidNttn]) && is_numeric($r[$colPrepaidNttn]) ? (float)$r[$colPrepaidNttn] : 0.0;
        $iig  = isset($r[$colPrepaidIig]) && is_numeric($r[$colPrepaidIig]) ? (float)$r[$colPrepaidIig] : 0.0;
        $itc  = isset($r[$colPrepaidItc]) && is_numeric($r[$colPrepaidItc]) ? (float)$r[$colPrepaidItc] : 0.0;
        $nix  = isset($r[$colPrepaidNix]) && is_numeric($r[$colPrepaidNix]) ? (float)$r[$colPrepaidNix] : 0.0;

        if ($cid) {
            if (!isset($clientOS[$cid])) {
                $clientOS[$cid] = [
                    'pre_total' => 0.0, 'pre_nttn' => 0.0, 'pre_iig' => 0.0, 'pre_itc' => 0.0, 'pre_nix' => 0.0,
                    'post_total' => 0.0, 'post_nttn' => 0.0, 'post_iig' => 0.0, 'post_itc' => 0.0, 'post_nix' => 0.0,
                ];
            }
            $clientOS[$cid]['pre_total'] += $bal;
            $clientOS[$cid]['pre_nttn']  += $nttn;
            $clientOS[$cid]['pre_iig']   += $iig;
            $clientOS[$cid]['pre_itc']   += $itc;
            $clientOS[$cid]['pre_nix']   += $nix;
        } else {
            if (!isset($residueOS[$nameClean])) {
                $residueOS[$nameClean] = [
                    'pre_total' => 0.0, 'pre_nttn' => 0.0, 'pre_iig' => 0.0, 'pre_itc' => 0.0, 'pre_nix' => 0.0,
                    'post_total' => 0.0, 'post_nttn' => 0.0, 'post_iig' => 0.0, 'post_itc' => 0.0, 'post_nix' => 0.0,
                ];
            }
            $residueOS[$nameClean]['pre_total'] += $bal;
            $residueOS[$nameClean]['pre_nttn']  += $nttn;
            $residueOS[$nameClean]['pre_iig']   += $iig;
            $residueOS[$nameClean]['pre_itc']   += $itc;
            $residueOS[$nameClean]['pre_nix']   += $nix;
        }
    }

    // Collect Postpaid
    foreach ($postRows as $rn => $r) {
        if ($rn < 2 || empty($r[0])) continue;
        $rawName = $r[0];
        if ($isNonClientRow($rawName)) continue;

        $nameClean = $cleanClientName($rawName);
        $cid = $clientMap[strtolower($nameClean)] ?? null;

        $bal  = isset($r[$colPostpaidTotal]) && is_numeric($r[$colPostpaidTotal]) ? (float)$r[$colPostpaidTotal] : 0.0;
        $nttn = isset($r[$colPostpaidNttn]) && is_numeric($r[$colPostpaidNttn]) ? (float)$r[$colPostpaidNttn] : 0.0;
        $iig  = isset($r[$colPostpaidIig]) && is_numeric($r[$colPostpaidIig]) ? (float)$r[$colPostpaidIig] : 0.0;
        $itc  = isset($r[$colPostpaidItc]) && is_numeric($r[$colPostpaidItc]) ? (float)$r[$colPostpaidItc] : 0.0;
        $nix  = isset($r[$colPostpaidNix]) && is_numeric($r[$colPostpaidNix]) ? (float)$r[$colPostpaidNix] : 0.0;

        if ($cid) {
            if (!isset($clientOS[$cid])) {
                $clientOS[$cid] = [
                    'pre_total' => 0.0, 'pre_nttn' => 0.0, 'pre_iig' => 0.0, 'pre_itc' => 0.0, 'pre_nix' => 0.0,
                    'post_total' => 0.0, 'post_nttn' => 0.0, 'post_iig' => 0.0, 'post_itc' => 0.0, 'post_nix' => 0.0,
                ];
            }
            $clientOS[$cid]['post_total'] += $bal;
            $clientOS[$cid]['post_nttn']  += $nttn;
            $clientOS[$cid]['post_iig']   += $iig;
            $clientOS[$cid]['post_itc']   += $itc;
            $clientOS[$cid]['post_nix']   += $nix;
        } else {
            if (!isset($residueOS[$nameClean])) {
                $residueOS[$nameClean] = [
                    'pre_total' => 0.0, 'pre_nttn' => 0.0, 'pre_iig' => 0.0, 'pre_itc' => 0.0, 'pre_nix' => 0.0,
                    'post_total' => 0.0, 'post_nttn' => 0.0, 'post_iig' => 0.0, 'post_itc' => 0.0, 'post_nix' => 0.0,
                ];
            }
            $residueOS[$nameClean]['post_total'] += $bal;
            $residueOS[$nameClean]['post_nttn']  += $nttn;
            $residueOS[$nameClean]['post_iig']   += $iig;
            $residueOS[$nameClean]['post_itc']   += $itc;
            $residueOS[$nameClean]['post_nix']   += $nix;
        }
    }

    echo "DEBUG: preRows=" . count($preRows) . ", postRows=" . count($postRows) . ", clientOS=" . count($clientOS) . ", residueOS=" . count($residueOS) . "\n";
    // Perform DB updates in a transaction
    DB::beginTransaction();
    $updatedCount = 0;
    $sumPrepaidTotal = 0; $sumPrepaidNttn = 0; $sumPrepaidIig = 0; $sumPrepaidItc = 0; $sumPrepaidNix = 0;
    $sumPostpaidTotal = 0; $sumPostpaidNttn = 0; $sumPostpaidIig = 0; $sumPostpaidItc = 0; $sumPostpaidNix = 0;

    foreach ($clientOS as $cid => $os) {
        $totLatestOs = $os['post_total'] + $os['pre_total'];
        $affected = DB::table('monthly_summary')
            ->where('summary_month', $summaryMonth)
            ->where('client_id', $cid)
            ->update([
                'latest_os_balance_prepaid'  => $os['pre_total'],
                'latest_os_prepaid_nttn'     => $os['pre_nttn'],
                'latest_os_prepaid_iig'      => $os['pre_iig'],
                'latest_os_prepaid_itc'      => $os['pre_itc'],
                'latest_os_prepaid_nix'      => $os['pre_nix'],
                'latest_os_balance_postpaid' => $os['post_total'],
                'latest_os_postpaid_nttn'    => $os['post_nttn'],
                'latest_os_postpaid_iig'     => $os['post_iig'],
                'latest_os_postpaid_itc'     => $os['post_itc'],
                'latest_os_postpaid_nix'     => $os['post_nix'],
                'total_latest_os'            => $totLatestOs,
            ]);

        $updatedCount++;
        $sumPrepaidTotal  += $os['pre_total'];
        $sumPrepaidNttn   += $os['pre_nttn'];
        $sumPrepaidIig    += $os['pre_iig'];
        $sumPrepaidItc    += $os['pre_itc'];
        $sumPrepaidNix    += $os['pre_nix'];
        $sumPostpaidTotal += $os['post_total'];
        $sumPostpaidNttn  += $os['post_nttn'];
        $sumPostpaidIig   += $os['post_iig'];
        $sumPostpaidItc   += $os['post_itc'];
        $sumPostpaidNix   += $os['post_nix'];
    }

    $residueUpdated = 0;
    $existingResidues = DB::table('monthly_summary_residue')
        ->where('summary_month', $summaryMonth)
        ->pluck('client_name')
        ->toArray();
    $existingResidueMap = [];
    foreach ($existingResidues as $erName) {
        $existingResidueMap[strtolower(trim($erName))] = $erName;
    }

    foreach ($residueOS as $rName => $os) {
        $cleanR = strtolower(trim($rName));
        if (!isset($existingResidueMap[$cleanR])) continue;
        $dbClientName = $existingResidueMap[$cleanR];

        $totLatestOs = $os['post_total'] + $os['pre_total'];
        $affected = DB::table('monthly_summary_residue')
            ->where('summary_month', $summaryMonth)
            ->where('client_name', '=', (string)$dbClientName)
            ->update([
                'latest_os_balance_prepaid'  => $os['pre_total'],
                'latest_os_prepaid_nttn'     => $os['pre_nttn'],
                'latest_os_prepaid_iig'      => $os['pre_iig'],
                'latest_os_prepaid_itc'      => $os['pre_itc'],
                'latest_os_prepaid_nix'      => $os['pre_nix'],
                'latest_os_balance_postpaid' => $os['post_total'],
                'latest_os_postpaid_nttn'    => $os['post_nttn'],
                'latest_os_postpaid_iig'     => $os['post_iig'],
                'latest_os_postpaid_itc'     => $os['post_itc'],
                'latest_os_postpaid_nix'     => $os['post_nix'],
                'total_latest_os'            => $totLatestOs,
            ]);
        if ($affected > 0) $residueUpdated++;
    }

    DB::commit();

    $elapsed = round(microtime(true) - $monthStart, 2);
    echo "    Updated $updatedCount live client summaries (Residues updated: $residueUpdated) in {$elapsed}s\n";
    echo "    Prepaid OS:  Total=" . number_format($sumPrepaidTotal, 2) . " [NTTN=" . number_format($sumPrepaidNttn, 2) . ", IIG=" . number_format($sumPrepaidIig, 2) . ", ITC=" . number_format($sumPrepaidItc, 2) . ", NIX=" . number_format($sumPrepaidNix, 2) . "]\n";
    echo "    Postpaid OS: Total=" . number_format($sumPostpaidTotal, 2) . " [NTTN=" . number_format($sumPostpaidNttn, 2) . ", IIG=" . number_format($sumPostpaidIig, 2) . ", ITC=" . number_format($sumPostpaidItc, 2) . ", NIX=" . number_format($sumPostpaidNix, 2) . "]\n";
    echo "    Total OS:    " . number_format($sumPrepaidTotal + $sumPostpaidTotal, 2) . "\n\n";
}

// Recalculate any deferred metrics if needed
echo ">>> Recalculating deferred summaries across all months...\n";
\App\Models\MonthlySummary::syncDeferredSummaries();
echo "    Done!\n\n";

$totalElapsed = round(microtime(true) - $overallStart, 2);
echo "====================================================================\n";
echo "ALL HISTORICAL UPDATES COMPLETED SUCCESSFULLY IN {$totalElapsed}s\n";
echo "====================================================================\n";
