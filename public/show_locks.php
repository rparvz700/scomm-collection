<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
header('Content-Type: text/plain; charset=utf-8');

echo "=== Active Processlist ===\n";
$processes = DB::select("SHOW FULL PROCESSLIST");
foreach ($processes as $p) {
    echo "ID: {$p->Id} | User: {$p->User} | Host: {$p->Host} | db: {$p->db} | Command: {$p->Command} | Time: {$p->Time} | State: {$p->State} | Info: {$p->Info}\n";
}

echo "\n=== InnoDB Locks / Transactions ===\n";
$status = DB::select("SHOW ENGINE INNODB STATUS");
if (!empty($status)) {
    echo $status[0]->Status;
}
