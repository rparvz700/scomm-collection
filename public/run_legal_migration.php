<?php
header('Content-Type: text/plain; charset=utf-8');

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$excelFile = "D:\\D\\Automation-Innovation\\scomm-collection\\Information for OS Automation.xlsx";

if (!file_exists($excelFile)) {
    echo "ERROR: File not found: $excelFile\n";
    exit;
}

try {
    // 1. Alter column in MySQL
    echo "Altering client.legal column to VARCHAR(500)...\n";
    DB::statement("ALTER TABLE `client` MODIFY COLUMN `legal` VARCHAR(500) NULL COMMENT 'Legal case details or notice text'");
    echo "Column client.legal successfully altered to VARCHAR(500).\n\n";

    // 2. Read sheet 'Discontinued and Legal'
    $zip = new ZipArchive();
    if ($zip->open($excelFile) !== TRUE) {
        echo "Could not open Excel zip archive.\n";
        exit;
    }

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

    $cleanClientName = function($name) {
        $name = trim($name);
        $name = preg_replace('/^\d+[\.\)\-_\/]+\s*/', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return trim($name);
    };

    // Find Header Row
    $headerRow = null;
    $clientNameCol = null;
    $legalCol = null;
    foreach ($rows as $rowNum => $row) {
        foreach ($row as $colIdx => $val) {
            $valClean = strtolower(trim($val));
            if ($valClean === 'client name') {
                $clientNameCol = $colIdx;
                $headerRow = $rowNum;
            } elseif ($valClean === 'legal') {
                $legalCol = $colIdx;
            }
        }
        if ($clientNameCol !== null && $legalCol !== null) break;
    }

    if (!$headerRow || $clientNameCol === null || $legalCol === null) {
        echo "ERROR: Could not locate Client Name and Legal column headers.\n";
        exit;
    }

    // Load Database Clients & Residue Mappings
    $dbClients = DB::table('client')->get();
    
    $residueMappings = [];
    if (Schema::hasTable('residue_client_mapping')) {
        $mappings = DB::table('residue_client_mapping')->get();
        foreach ($mappings as $m) {
            $residueMappings[strtolower(trim($m->client_name))] = trim($m->c_client_name);
        }
    }

    $findClientMatch = function($excelName, $dbClients, $cleanClientName, $residueMappings) {
        $cleanedExcelName = $cleanClientName($excelName);
        if (empty($cleanedExcelName)) return null;

        foreach ($dbClients as $c) {
            $cleanedDbName = $cleanClientName($c->client_name);
            if (strtolower($cleanedDbName) === strtolower($cleanedExcelName)) return $c;
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

        // Fuzzy match (>90%)
        foreach ($dbClients as $c) {
            $cleanedDbName = $cleanClientName($c->client_name);
            similar_text(strtolower($cleanedExcelName), strtolower($cleanedDbName), $percent);
            if ($percent > 90.0) return $c;
        }

        return null;
    };

    DB::beginTransaction();

    $updatedCount = 0;
    $matchedCount = 0;

    foreach ($rows as $rowNum => $row) {
        if ($rowNum <= $headerRow) continue;

        $rawClientName = isset($row[$clientNameCol]) ? trim($row[$clientNameCol]) : '';
        $legalVal = isset($row[$legalCol]) ? trim($row[$legalCol]) : null;

        if (empty($rawClientName)) continue;

        $match = $findClientMatch($rawClientName, $dbClients, $cleanClientName, $residueMappings);
        if ($match) {
            $matchedCount++;
            $oldLegal = $match->legal;

            // Normalize empty string to null
            $newLegal = ($legalVal !== '' && $legalVal !== null) ? $legalVal : null;

            if (strval($oldLegal) !== strval($newLegal)) {
                // Log change
                \App\Models\ClientLog::create([
                    'client_id'  => $match->client_id,
                    'field_name' => 'legal',
                    'old_value'  => $oldLegal,
                    'new_value'  => $newLegal,
                    'updated_by' => 'System',
                ]);

                DB::table('client')->where('client_id', $match->client_id)->update([
                    'legal' => $newLegal,
                    'updated_at' => now()
                ]);

                $updatedCount++;
                echo "Updated client ID {$match->client_id} ('{$match->client_name}'): Legal = " . ($newLegal ? "'$newLegal'" : "NULL") . "\n";
            }
        } else {
            echo "Warning: No DB client matched Excel name '$rawClientName' (Row $rowNum)\n";
        }
    }

    DB::commit();

    echo "\nSummary:\n";
    echo " - Total Excel Rows Processed: " . (count($rows) - $headerRow) . "\n";
    echo " - Total DB Clients Matched: $matchedCount\n";
    echo " - Total DB Clients Updated: $updatedCount\n";

} catch (Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
