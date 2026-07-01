<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
header('Content-Type: text/plain; charset=utf-8');

// 1. Find all duplicate client names
$dupes = DB::select("
    SELECT a.client_id, a.client_name, a.opus_id, a.client_status, a.created_at,
           b.client_id as b_id, b.opus_id as b_opus, b.client_status as b_status, b.created_at as b_created
    FROM client a
    INNER JOIN client b 
        ON a.client_name = b.client_name 
       AND a.opus_id <> b.opus_id 
       AND a.client_id <> b.client_id
    ORDER BY a.client_name, a.client_id
");

// Group by client_name
$grouped = [];
foreach ($dupes as $d) {
    $grouped[$d->client_name][$d->client_id] = [
        'client_id' => $d->client_id,
        'opus_id'   => $d->opus_id,
        'status'    => $d->client_status,
        'created_at' => $d->created_at,
    ];
    $grouped[$d->client_name][$d->b_id] = [
        'client_id' => $d->b_id,
        'opus_id'   => $d->b_opus,
        'status'    => $d->b_status,
        'created_at' => $d->b_created,
    ];
}

echo "Total duplicate client names: " . count($grouped) . "\n\n";

// Categorize opus_id patterns
$standardOpus = 0; // 9-digit numeric like 012203081
$discOpus = 0;     // DISC-xxx
$otherOpus = 0;    // other
$nullOpus = 0;

foreach ($grouped as $name => $entries) {
    foreach ($entries as $e) {
        $opus = $e['opus_id'];
        if (preg_match('/^\d{9}$/', $opus)) $standardOpus++;
        elseif (strpos($opus, 'DISC-') === 0) $discOpus++;
        elseif (empty($opus) || $opus === null) $nullOpus++;
        else $otherOpus++;
    }
}
echo "Opus ID patterns across duplicates:\n";
echo "  Standard 9-digit (e.g. 012203081): $standardOpus\n";
echo "  DISC-xxx prefix:                   $discOpus\n";
echo "  Other (short, alphanumeric, etc.): $otherOpus\n";
echo "  Null/empty:                        $nullOpus\n\n";

// Show each group
echo "=== DUPLICATE GROUPS ===\n\n";
foreach ($grouped as $name => $entries) {
    echo "Client: \"$name\"\n";
    ksort($entries);
    foreach ($entries as $e) {
        // How many MS records, collection records?
        $msCount = DB::table('monthly_summary')->where('client_id', $e['client_id'])->count();
        $msdCount = DB::table('monthly_summary_discontinued')->where('client_id', $e['client_id'])->count();
        $colCount = DB::table('collection')->where('client_id', $e['client_id'])->count();
        $colSum = DB::table('collection')->where('client_id', $e['client_id'])->sum('collection_amount');
        echo "  ID={$e['client_id']} opus={$e['opus_id']} status={$e['status']} created={$e['created_at']}\n";
        echo "    ms_rows=$msCount  msd_rows=$msdCount  col_rows=$colCount  col_sum=" . number_format($colSum, 0) . "\n";
    }
    echo "\n";
}
