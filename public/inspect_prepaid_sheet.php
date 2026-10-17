<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

// 1. Check monthly_summary columns
$cols = DB::getSchemaBuilder()->getColumnListing('monthly_summary');
$osCols = array_filter($cols, function($c) {
    return str_contains($c, 'os') || str_contains($c, 'latest');
});
echo "monthly_summary OS/Latest Columns:\n";
print_r($osCols);

// 2. Check LIVE_Pre-Paid (Billing) headers in Sep'26 file
$folder = "D:\\D\\Automation-Innovation\\scomm-collection\\KPI_ENGINE_FILES";
$filename = "18KPI_ENGINE_LIVE_&_Discontinued_ISPs_Sep'26_30th_Sep'26_Final.xlsx";
$filepath = $folder . '\\' . $filename;

$zip = new ZipArchive();
if ($zip->open($filepath) !== true) die("Cannot open zip\n");

$sharedStrings = [];
$ssContent = $zip->getFromName('xl/sharedStrings.xml');
if ($ssContent !== false) {
    $xml = simplexml_load_string($ssContent);
    foreach ($xml->si as $si) {
        $text = '';
        if (isset($si->t)) $text = (string)$si->t;
        else foreach ($si->r as $r) if (isset($r->t)) $text .= (string)$r->t;
        $sharedStrings[] = trim($text);
    }
}

$wbXml = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
$relsXml = simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'));
$sheetMap = [];
foreach ($relsXml->Relationship as $rel) $sheetMap[(string)$rel['Id']] = (string)$rel['Target'];
$sheets = [];
foreach ($wbXml->sheets->sheet as $sheet) {
    $rId = (string)$sheet->attributes('r', true)['id'];
    $sheets[(string)$sheet['name']] = $sheetMap[$rId] ?? null;
}

$colLetterToIdx = function($col) {
    $len = strlen($col);
    $idx = 0;
    for ($i = 0; $i < $len; $i++) $idx = $idx * 26 + (ord($col[$i]) - ord('A') + 1);
    return $idx - 1;
};

$readRow = function($zip, $target, $sharedStrings, $targetRowNum) use ($colLetterToIdx) {
    $content = $zip->getFromName('xl/' . $target);
    if (!$content) return [];
    $xml = simplexml_load_string($content);
    $rowData = [];
    foreach ($xml->sheetData->row as $r) {
        if ((int)$r['r'] === $targetRowNum) {
            foreach ($r->c as $c) {
                $ref = (string)$c['r'];
                preg_match('/^([A-Z]+)/', $ref, $matches);
                $colLetter = $matches[1] ?? 'A';
                $colIdx = $colLetterToIdx($colLetter);
                $val = '';
                if (isset($c->v)) {
                    $val = (string)$c->v;
                    if ((string)$c['t'] === 's') $val = $sharedStrings[(int)$val] ?? '';
                }
                $rowData[$colIdx] = trim($val);
            }
            break;
        }
    }
    return $rowData;
};

$preTarget = $sheets['LIVE_Pre-Paid (Billing)'] ?? null;
echo "\nTarget sheet for LIVE_Pre-Paid (Billing): $preTarget\n";
if ($preTarget) {
    $row1 = $readRow($zip, $preTarget, $sharedStrings, 1);
    $row2 = $readRow($zip, $preTarget, $sharedStrings, 2);
    $row3 = $readRow($zip, $preTarget, $sharedStrings, 3);
    
    echo "\nSearching for 'Latest' or 'OS' or 'Outstanding' in LIVE_Pre-Paid (Billing) headers:\n";
    for ($i = 0; $i < 350; $i++) {
        $v1 = $row1[$i] ?? '';
        $v2 = $row2[$i] ?? '';
        if (stripos($v1, 'latest') !== false || stripos($v2, 'latest') !== false || stripos($v2, 'os') !== false || stripos($v2, 'outstanding') !== false) {
            echo "Col [$i]: Row1='$v1' | Row2='$v2'\n";
        }
    }
    
    echo "\nAlso let's check what is at Col 200 to 215 in Row 1 & 2:\n";
    for ($i = 195; $i <= 215; $i++) {
        echo "Col [$i]: Row1='" . ($row1[$i] ?? '') . "' | Row2='" . ($row2[$i] ?? '') . "' | Row3 Sample='" . ($row3[$i] ?? '') . "'\n";
    }
}
$zip->close();
