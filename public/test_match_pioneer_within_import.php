<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
header('Content-Type: text/plain; charset=utf-8');

$existingClients = DB::table('client')->get();

$cleanClientName = function($name) {
    $name = trim($name);
    $name = preg_replace('/^\d+[\.\)\-_\/]+\s*/', '', $name);
    $name = preg_replace('/\s+/', ' ', $name);
    return trim($name);
};

$excelName = "Pioneer Services Ltd.";
$excelOpus = "012203376";

$cleanedExcelName = $cleanClientName($excelName);

$found1 = false;
foreach ($existingClients as $c) {
    $cleanedDbName = $cleanClientName($c->client_name);
    if (strtolower($cleanedDbName) === strtolower($cleanedExcelName) && strval($c->opus_id) === strval($excelOpus)) {
        echo "Step 1 matched: ID {$c->client_id} | Name: {$c->client_name}\n";
        $found1 = true;
    }
}

if (!$found1) {
    echo "Step 1 did NOT match. Checking Step 2...\n";
    foreach ($existingClients as $c) {
        $cleanedDbName = $cleanClientName($c->client_name);
        if (strtolower($cleanedDbName) === strtolower($cleanedExcelName)) {
            echo "Step 2 matched: ID {$c->client_id} | Name: {$c->client_name}\n";
        }
    }
}
