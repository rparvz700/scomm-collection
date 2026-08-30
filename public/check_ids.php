<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
header('Content-Type: text/plain; charset=utf-8');

$c1 = DB::table('client')->where('opus_id', '012418198')->first();
$c2 = DB::table('client')->where('opus_id', '012308261')->first();
$c3 = DB::table('client')->where('opus_id', '012206885')->first();

echo "Bismillah Telecom Service (012418198) ID: " . ($c1 ? $c1->client_id : 'NOT FOUND') . "\n";
echo "Bogra Broadband Communication System (012308261) ID: " . ($c2 ? $c2->client_id : 'NOT FOUND') . "\n";
echo "KT Computer & Network (012206885) ID: " . ($c3 ? $c3->client_id : 'NOT FOUND') . "\n";
