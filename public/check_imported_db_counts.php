<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
header('Content-Type: text/plain; charset=utf-8');

$julyFile = "D:\\D\\Automation-Innovation\\scomm-collection\\KPI_ENGINE_FILES\\14KPI_ENGINE_LIVE_&_Discontinued_ISPs_July'26_31st_July'26_Final.xlsx";
$augFile = "D:\\D\\Automation-Innovation\\scomm-collection\\KPI_ENGINE_FILES\\15KPI_ENGINE_LIVE_&_Discontinued_ISPs_Aug'26_16th_Aug'26.xlsx";

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
                if ((string)$c['t'] === 's') {
                    $idx = (int)$val;
                    if (isset($sharedStrings[$idx])) $val = $sharedStrings[$idx];
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

$existingClients = DB::table('client')->get();
$mappingsRaw = DB::table('residue_client_mapping')->get();
$residueMappings = [];
foreach ($mappingsRaw as $m) {
    $key = strtolower(trim($m->client_name));
    $residueMappings[$key] = trim($m->c_client_name);
}

$findClient = function($excelName, $excelOpus) use ($existingClients, $cleanClientName, $residueMappings) {
    $cleanedExcelName = $cleanClientName($excelName);
    if (empty($cleanedExcelName)) return null;

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

    // 3. Similarity Match
    if (!empty($excelOpus)) {
        foreach ($existingClients as $c) {
            if (strval($c->opus_id) === strval($excelOpus)) {
                $cleanedDbName = $cleanClientName($c->client_name);
                similar_text(strtolower($cleanedExcelName), strtolower($cleanedDbName), $percent);
                if ($percent > 95.0) return $c;
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

    return null;
};

function analyzeDiscontinuedSheet($excelFile, $findClient, $readSheet, $cleanClientName, $ignoredStatusChangeIds, $ignoredStatusChangeNames) {
    $zip = new ZipArchive();
    if ($zip->open($excelFile) !== TRUE) return;
    
    $sharedStrings = [];
    $ssContent = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssContent !== false) {
        $xml = simplexml_load_string($ssContent);
        foreach ($xml->si as $si) {
            $text = '';
            if (isset($si->t)) $text = (string)$si->t;
            else {
                foreach ($si->r as $r) {
                    if (isset($r->t)) $text .= (string)$r->t;
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
    
    $liveTarget = null;
    $discTarget = null;
    foreach ($wbXml->sheets->sheet as $sheet) {
        $normalized = strtolower(str_replace([' ', '_', '-', '+'], '', (string)$sheet['name']));
        $target = $sheetMap[(string)$sheet->attributes('r', true)['id']];
        if (strpos($normalized, 'postpaidprepaid') !== false || strpos($normalized, 'postprepaid') !== false) {
            $liveTarget = $target;
        } elseif (strpos($normalized, 'discontinued') !== false) {
            $discTarget = $target;
        }
    }
    
    $liveRows = $readSheet($zip, $liveTarget, $sharedStrings);
    $matchedActiveClientIds = [];
    foreach ($liveRows as $rowNum => $row) {
        if ($rowNum < 3) continue;
        $opusId = isset($row[0]) ? trim($row[0]) : '';
        $rawClientName = isset($row[1]) ? trim($row[1]) : '';
        if (empty($opusId) || empty($rawClientName) || !ctype_digit($opusId)) continue;
        $client = $findClient($cleanClientName($rawClientName), $opusId);
        if ($client) {
            $matchedActiveClientIds[$client->client_id] = true;
        }
    }
    
    $discRows = $readSheet($zip, $discTarget, $sharedStrings);
    
    $parsed = 0;
    $ignoredActiveList = [];
    $ignoredExclusionsList = [];
    
    foreach ($discRows as $rowNum => $row) {
        if ($rowNum < 3) continue;
        $discId = isset($row[0]) ? trim($row[0]) : '';
        $rawClientName = isset($row[1]) ? trim($row[1]) : '';
        if (empty($rawClientName) || strtolower($rawClientName) === 'client name') continue;
        if (empty($discId)) continue;

        $parsed++;
        $clientName = $cleanClientName($rawClientName);
        $opusId = strpos($discId, 'DISC-') === 0 ? $discId : "DISC-" . $discId;
        
        $client = $findClient($clientName, $opusId);
        
        // Skip if in exclusions check (meaning they belong to the 27 clients list, and DB status is Active)
        if ($client) {
            $clientNameLower = strtolower(trim($client->client_name));
            if (in_array($client->client_id, $ignoredStatusChangeIds) || in_array($clientNameLower, $ignoredStatusChangeNames)) {
                if ($client->client_status === 'Active') {
                    $ignoredExclusionsList[] = "Row $rowNum | Name: '$rawClientName' | ID: '$discId' | Client: {$client->client_name}";
                    continue;
                }
            }
        }
        
        // Skip if matched active live
        if ($client && isset($matchedActiveClientIds[$client->client_id])) {
            $ignoredActiveList[] = "Row $rowNum | Name: '$rawClientName' | ID: '$discId' | Client: {$client->client_name}";
            continue;
        }
    }
    
    $zip->close();
    
    return [
        'parsed' => $parsed,
        'exclusions' => $ignoredExclusionsList,
        'active_live' => $ignoredActiveList
    ];
}

echo "=== July Discontinued Detail ===\n";
$julyRes = analyzeDiscontinuedSheet($julyFile, $findClient, $readSheet, $cleanClientName, $ignoredStatusChangeIds, $ignoredStatusChangeNames);
echo "Parsed: {$julyRes['parsed']}\n";
echo "Excluded due to DB Active status (of the 27 designated clients): " . count($julyRes['exclusions']) . "\n";
foreach ($julyRes['exclusions'] as $e) {
    echo "  - $e\n";
}
echo "Excluded because matched in Live sheet: " . count($julyRes['active_live']) . "\n";
foreach ($julyRes['active_live'] as $e) {
    echo "  - $e\n";
}

echo "\n=== August Discontinued Detail ===\n";
$augRes = analyzeDiscontinuedSheet($augFile, $findClient, $readSheet, $cleanClientName, $ignoredStatusChangeIds, $ignoredStatusChangeNames);
echo "Parsed: {$augRes['parsed']}\n";
echo "Excluded due to DB Active status (of the 27 designated clients): " . count($augRes['exclusions']) . "\n";
foreach ($augRes['exclusions'] as $e) {
    echo "  - $e\n";
}
echo "Excluded because matched in Live sheet: " . count($augRes['active_live']) . "\n";
foreach ($augRes['active_live'] as $e) {
    echo "  - $e\n";
}
