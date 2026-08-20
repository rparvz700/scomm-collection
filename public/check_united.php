<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
header('Content-Type: text/plain; charset=utf-8');

$c = DB::table('client')->where('client_name', 'like', '%United%')->get();
foreach ($c as $row) {
    echo "ID: {$row->client_id} | Opus: {$row->opus_id} | Name: {$row->client_name} | Status: {$row->client_status}\n";
}
echo "Also checking residue mapping for United:\n";
$m = DB::table('residue_client_mapping')->where('client_name', 'like', '%United%')->orWhere('c_client_name', 'like', '%United%')->get();
foreach ($m as $row) {
    echo "  Map: '{$row->client_name}' => '{$row->c_client_name}'\n";
}
