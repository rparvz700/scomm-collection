<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
header('Content-Type: text/plain; charset=utf-8');

$excelFile = "D:\\D\\Automation-Innovation\\scomm-collection\\KPI_ENGINE_FILES\\15KPI_ENGINE_LIVE_&_Discontinued_ISPs_Aug'26_16th_Aug'26.xlsx";
$zip = new ZipArchive();
if ($zip->open($excelFile) !== TRUE) {
    die("Could not open ZIP archive.\n");
}

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

$findClient = function($excelName, $excelOpus) use ($existingClients, $cleanClientName) {
    $cleanedExcelName = $cleanClientName($excelName);
    if (empty($cleanedExcelName)) return null;

    foreach ($existingClients as $c) {
        $cleanedDbName = $cleanClientName($c->client_name);
        if (stripos($cleanedDbName, 'Pioneer Services') !== false && stripos($cleanedExcelName, 'Pioneer Services') !== false) {
            echo "  Comparing in Step 1:\n";
            echo "    DB Cleaned: '$cleanedDbName' (length " . strlen($cleanedDbName) . ")\n";
            echo "    Excel Cleaned: '$cleanedExcelName' (length " . strlen($cleanedExcelName) . ")\n";
            echo "    DB Opus: '{$c->opus_id}' (length " . strlen($c->opus_id) . ")\n";
            echo "    Excel Opus: '$excelOpus' (length " . strlen($excelOpus) . ")\n";
            echo "    Name Match: " . (strtolower($cleanedDbName) === strtolower($cleanedExcelName) ? "YES" : "NO") . "\n";
            echo "    Opus Match: " . (strval($c->opus_id) === strval($excelOpus) ? "YES" : "NO") . "\n";
        }
        if (strtolower($cleanedDbName) === strtolower($cleanedExcelName) && strval($c->opus_id) === strval($excelOpus)) {
            return $c;
        }
    }

    foreach ($existingClients as $c) {
        $cleanedDbName = $cleanClientName($c->client_name);
        if (stripos($cleanedDbName, 'Pioneer Services') !== false && stripos($cleanedExcelName, 'Pioneer Services') !== false) {
            echo "  Comparing in Step 2:\n";
            echo "    Name Match: " . (strtolower($cleanedDbName) === strtolower($cleanedExcelName) ? "YES" : "NO") . "\n";
        }
        if (strtolower($cleanedDbName) === strtolower($cleanedExcelName)) {
            return $c;
        }
    }

    return null;
};

$sheetTargets = [];
foreach ($wbXml->sheets->sheet as $sheet) {
    $normalized = strtolower(str_replace([' ', '_', '-', '+'], '', (string)$sheet['name']));
    $target = $sheetMap[(string)$sheet->attributes('r', true)['id']];
    if (strpos($normalized, 'postpaidprepaid') !== false || strpos($normalized, 'postprepaid') !== false) {
        $sheetTargets['Post-Paid+Pre-Paid'] = $target;
    }
}

$liveRows = $readSheet($zip, $sheetTargets['Post-Paid+Pre-Paid'], $sharedStrings);
$row = $liveRows[521];
$opusId = isset($row[0]) ? trim($row[0]) : '';
$rawClientName = isset($row[1]) ? trim($row[1]) : '';
$clientName = $cleanClientName($rawClientName);

$findClient($clientName, $opusId);
$zip->close();
