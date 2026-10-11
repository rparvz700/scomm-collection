<?php
header('Content-Type: text/plain; charset=utf-8');

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$excelFile = "D:\\D\\Automation-Innovation\\scomm-collection\\KPI_ENGINE_FILES\\17KPI ENGINE_LIVE  Discontinued ISPs_Sep'26 30th Sep'26.xlsx";

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

    $sheetTargets = [
        'Post-Paid+Pre-Paid' => null,
        'Discontinued (Collection)' => null,
    ];
    foreach ($wbXml->sheets->sheet as $sheet) {
        $name = (string)$sheet['name'];
        if (array_key_exists($name, $sheetTargets)) {
            $rId = (string)$sheet->attributes('r', true)['id'];
            $sheetTargets[$name] = isset($sheetMap[$rId]) ? $sheetMap[$rId] : null;
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

    $readSheet = function($zip, $targetPath, $sharedStrings) use ($colLetterToIdx, $parseCellRef) {
        if (!$targetPath) return [];
        $sheetPath = 'xl/' . $targetPath;
        $sheetContent = $zip->getFromName($sheetPath);
        if ($sheetContent === false) return [];
        
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

    $cleanClientName = function($name) {
        $name = trim($name);
        $name = preg_replace('/^\d+[\.\)\-_\/]+\s*/', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return trim($name);
    };

    $liveRows = $readSheet($zip, $sheetTargets['Post-Paid+Pre-Paid'], $sharedStrings);
    $discRows = $readSheet($zip, $sheetTargets['Discontinued (Collection)'], $sharedStrings);
    $zip->close();

    $liveHeaderMap = [];
    if (isset($liveRows[2])) {
        foreach ($liveRows[2] as $colIdx => $val) {
            $v = strtolower(trim(preg_replace('/\s+/', ' ', $val)));
            if ($v === 'opus id' || $v === 'client id') $liveHeaderMap['opus_id'] = $colIdx;
            elseif ($v === 'client name') $liveHeaderMap['client_name'] = $colIdx;
            elseif ($v === 's&m kam' || $v === 'sales kam' || $v === 'sales manager') $liveHeaderMap['sm_kam'] = $colIdx;
            elseif ($v === 'team name') $liveHeaderMap['team_name'] = $colIdx;
            elseif ($v === 'collection kam' || $v === 'collections kam') $liveHeaderMap['collection_kam'] = $colIdx;
            elseif ($v === 'collection supervisor') $liveHeaderMap['collection_supervisor'] = $colIdx;
            elseif (strpos($v, 'nttn billing') !== false && strpos($v, 'kam') !== false) $liveHeaderMap['nttn_billing_kam'] = $colIdx;
            elseif (strpos($v, 'iig') !== false && strpos($v, 'kam') !== false) $liveHeaderMap['iig_itc_billing_kam'] = $colIdx;
        }
    }

    $discHeaderMap = [];
    if (isset($discRows[2])) {
        foreach ($discRows[2] as $colIdx => $val) {
            $v = strtolower(trim(preg_replace('/\s+/', ' ', $val)));
            if ($v === 'client id' || $v === 'opus id') $discHeaderMap['opus_id'] = $colIdx;
            elseif ($v === 'client name') $discHeaderMap['client_name'] = $colIdx;
            elseif ($v === 'sales kam' || $v === 's&m kam' || $v === 'sales manager') $discHeaderMap['sm_kam'] = $colIdx;
            elseif ($v === 'team name') $discHeaderMap['team_name'] = $colIdx;
            elseif ($v === 'collections kam' || $v === 'collection kam') $discHeaderMap['collection_kam'] = $colIdx;
            elseif ($v === 'collection supervisor') $discHeaderMap['collection_supervisor'] = $colIdx;
            elseif (strpos($v, 'nttn billing') !== false && strpos($v, 'kam') !== false) $discHeaderMap['nttn_billing_kam'] = $colIdx;
            elseif (strpos($v, 'iig') !== false && strpos($v, 'kam') !== false) $discHeaderMap['iig_itc_billing_kam'] = $colIdx;
        }
    }

    $dbClients = DB::table('client')->get();
    
    $residueMappings = [];
    if (Schema::hasTable('residue_client_mapping')) {
        $mappings = DB::table('residue_client_mapping')->get();
        foreach ($mappings as $m) {
            $residueMappings[strtolower(trim($m->client_name))] = trim($m->c_client_name);
        }
    }

    $findClientMatch = function($excelName, $excelOpus, $dbClients, $cleanClientName, $residueMappings) {
        $cleanedExcelName = $cleanClientName($excelName);
        if (empty($cleanedExcelName)) return null;

        $opusToCheck = [];
        if (!empty($excelOpus)) {
            $opusToCheck[] = strval($excelOpus);
            if (strpos($excelOpus, 'DISC-') === 0) {
                $opusToCheck[] = substr($excelOpus, 5);
            } else {
                $opusToCheck[] = 'DISC-' . $excelOpus;
            }
        }

        foreach ($dbClients as $c) {
            $cleanedDbName = $cleanClientName($c->client_name);
            if (strtolower($cleanedDbName) === strtolower($cleanedExcelName)) {
                if (in_array(strval($c->opus_id), $opusToCheck)) return $c;
            }
        }

        foreach ($dbClients as $c) {
            $cleanedDbName = $cleanClientName($c->client_name);
            if (strtolower($cleanedDbName) === strtolower($cleanedExcelName)) return $c;
        }

        if (!empty($excelOpus)) {
            foreach ($dbClients as $c) {
                if (in_array(strval($c->opus_id), $opusToCheck)) {
                    $cleanedDbName = $cleanClientName($c->client_name);
                    similar_text(strtolower($cleanedExcelName), strtolower($cleanedDbName), $percent);
                    if ($percent > 95.0) return $c;
                }
            }
        }

        $key = strtolower($cleanedExcelName);
        if (isset($residueMappings[$key])) {
            $mappedName = $residueMappings[$key];
            $cleanedMappedName = $cleanClientName($mappedName);
            foreach ($dbClients as $c) {
                $cleanedDbName = $cleanClientName($c->client_name);
                if (strtolower($cleanedDbName) === strtolower($cleanedMappedName)) return $c;
            }
        }

        return null;
    };

    $supervisorAliases = [
        'cs tarikul' => 'md. tarikul islam',
        'cs mithun'  => 'mithun kumar ghosh',
        'cs azim'    => 'md. ferdous azim',
        'cs shehave' => 'md. shehave hossen',
    ];

    $normalizeVal = function($val, $fieldKey, $supervisorAliases) {
        $v = strtolower(trim(preg_replace('/\s+/', ' ', $val)));
        if ($v === 'n/a' || $v === 'none' || $v === '-' || $v === '0') {
            return '';
        }
        if ($fieldKey === 'collection_supervisor') {
            if (isset($supervisorAliases[$v])) {
                return $supervisorAliases[$v];
            }
        }
        return $v;
    };

    $structuralDifferences = [];

    $extractAndCompare = function($rows, $headerMap, $sheetName) use (&$structuralDifferences, $dbClients, $cleanClientName, $residueMappings, $findClientMatch, $normalizeVal, $supervisorAliases) {
        foreach ($rows as $rowNum => $row) {
            if ($rowNum < 3) continue;
            
            $opusId = isset($headerMap['opus_id']) && isset($row[$headerMap['opus_id']]) ? trim($row[$headerMap['opus_id']]) : '';
            $rawName = isset($headerMap['client_name']) && isset($row[$headerMap['client_name']]) ? trim($row[$headerMap['client_name']]) : '';

            if (empty($rawName) || strtolower($rawName) === 'client name') continue;

            $match = $findClientMatch($rawName, $opusId, $dbClients, $cleanClientName, $residueMappings);
            if (!$match) continue;

            $fieldsToCompare = [
                'sm_kam' => 'S&M / Sales KAM',
                'team_name' => 'Team Name',
                'collection_kam' => 'Collection KAM',
                'collection_supervisor' => 'Collection Supervisor',
                'nttn_billing_kam' => 'NTTN Billing KAM',
                'iig_itc_billing_kam' => 'IIG/ITC Billing KAM'
            ];

            $rowStructDiffs = [];

            foreach ($fieldsToCompare as $fieldKey => $fieldLabel) {
                $excelVal = isset($headerMap[$fieldKey]) && isset($row[$headerMap[$fieldKey]]) ? trim($row[$headerMap[$fieldKey]]) : '';
                $dbVal = isset($match->$fieldKey) ? trim($match->$fieldKey) : '';

                $normExcelNorm = $normalizeVal($excelVal, $fieldKey, $supervisorAliases);
                $normDbNorm = $normalizeVal($dbVal, $fieldKey, $supervisorAliases);

                if ($normExcelNorm !== $normDbNorm) {
                    $rowStructDiffs[] = [
                        'field' => $fieldLabel,
                        'excel' => $excelVal !== '' ? $excelVal : '(empty)',
                        'db' => $dbVal !== '' ? $dbVal : '(empty)'
                    ];
                }
            }

            if (!empty($rowStructDiffs)) {
                $structuralDifferences[] = [
                    'client_id' => $match->client_id,
                    'db_opus' => $match->opus_id,
                    'client_name' => $match->client_name,
                    'sheet' => $sheetName,
                    'row' => $rowNum,
                    'diffs' => $rowStructDiffs
                ];
            }
        }
    };

    $extractAndCompare($liveRows, $liveHeaderMap, 'Post-Paid+Pre-Paid');
    $extractAndCompare($discRows, $discHeaderMap, 'Discontinued (Collection)');

    // Write Markdown Artifact
    $md = "# KAM, Team, and Supervisor Comparison Report (September 2026 KPI File 17 vs Database)\n\n";
    $md .= "This report lists all structural differences found in **Sales KAM (`sm_kam`)**, **Team Name (`team_name`)**, **Collection KAM (`collection_kam`)**, **Collection Supervisor (`collection_supervisor`)**, **NTTN Billing KAM (`nttn_billing_kam`)**, and **IIG/ITC Billing KAM (`iig_itc_billing_kam`)** between the current `client` table and the latest September 2026 KPI Excel file (`17KPI ENGINE_LIVE  Discontinued ISPs_Sep'26 30th Sep'26.xlsx`).\n\n";
    $md .= "> [!NOTE]\n";
    $md .= "> Formatting differences in supervisor names (e.g. `CS Tarikul` vs `Md. Tarikul Islam`) have been normalized so that only **genuine assignment changes** are reported below.\n\n";
    $md .= "### Summary\n";
    $md .= "- **Total Clients with Structural Changes:** `" . count($structuralDifferences) . "`\n\n";
    $md .= "---\n\n";

    $md .= "## List of Clients with Structural Differences\n\n";
    $md .= "| DB ID | Opus ID | Client Name | Excel Sheet | Changed Fields & Details |\n";
    $md .= "| :--- | :--- | :--- | :--- | :--- |\n";

    foreach ($structuralDifferences as $d) {
        $details = [];
        foreach ($d['diffs'] as $diff) {
            $details[] = "**{$diff['field']}**: DB = `{$diff['db']}` ➔ Excel = `{$diff['excel']}`";
        }
        $detailStr = implode("<br>", $details);
        $md .= "| `{$d['client_id']}` | `{$d['db_opus']}` | {$d['client_name']} | {$d['sheet']} (Row {$d['row']}) | {$detailStr} |\n";
    }

    $artifactPath = "C:\\Users\\raihan.parvez.SUMMIT\\.gemini\\antigravity\\brain\\f74fc0b4-3eab-4c16-abe5-c14d331b1a18\\september_kpi_team_diff_report.md";
    file_put_contents($artifactPath, $md);

    echo "Artifact september_kpi_team_diff_report.md written successfully. Total structural differences: " . count($structuralDifferences) . "\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
