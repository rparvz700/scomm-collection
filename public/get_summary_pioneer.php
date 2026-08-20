<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
header('Content-Type: text/plain; charset=utf-8');

echo "=== monthly_summary queries ===\n";
$res1 = DB::table('monthly_summary')
    ->where('client_id', 916)
    ->get();
foreach ($res1 as $row) {
    echo "  Month: {$row->summary_month} | Name: {$row->client_name} | Client ID: {$row->client_id}\n";
}

echo "\n=== monthly_summary_residue queries ===\n";
$res2 = DB::table('monthly_summary_residue')
    ->where('client_name', 'like', '%Pioneer%')
    ->get();
foreach ($res2 as $row) {
    echo "  Month: {$row->summary_month} | Name: {$row->client_name} | Client ID: {$row->client_id}\n";
}
