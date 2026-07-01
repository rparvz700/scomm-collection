<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
header('Content-Type: text/plain; charset=utf-8');

$tables = ['monthly_summary', 'monthly_summary_discontinued'];
foreach ($tables as $t) {
    echo "=== Columns for $t ===\n";
    $cols = DB::select("SHOW COLUMNS FROM $t");
    foreach ($cols as $c) {
        echo "{$c->Field} ({$c->Type})\n";
    }
}
