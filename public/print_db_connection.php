<?php
require __DIR__.'/../vendor/autoload.php';

// Set DB explicitly to mysql
putenv('DB_CONNECTION=mysql');

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
header('Content-Type: text/plain; charset=utf-8');

echo "Driver: " . DB::connection()->getDriverName() . "\n";
echo "Database: " . DB::connection()->getDatabaseName() . "\n";
try {
    $cnt = DB::table('residue_client_mapping')->count();
    echo "residue_client_mapping count: $cnt\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
