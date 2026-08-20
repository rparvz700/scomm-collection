<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
header('Content-Type: text/plain; charset=utf-8');

$newClients = [
    [
        'opus_id'             => '012209552',
        'client_name'         => 'Amader Net Ad Communication',
        'client_status'       => 'Active',
        'service_type_billing'=> 'NTTN, IIG',
        'sm_kam'              => 'Md. Wahiduzzaman Shanto',
        'team_name'           => 'Team Ryhan',
        'collection_kam'      => 'Sarwar',
        'collection_supervisor'=> 'CS Azim',
    ],
    [
        'opus_id'             => '012312958',
        'client_name'         => 'Datalink 1',
        'client_status'       => 'Active',
        'service_type_billing'=> 'NTTN, IIG',
        'sm_kam'              => 'Md. Taher Bin Hossain',
        'team_name'           => 'Team Israfil',
        'collection_kam'      => 'Mehedi',
        'collection_supervisor'=> 'CS Azim',
    ],
    [
        'opus_id'             => '012618396',
        'client_name'         => 'EasyNet Wifi',
        'client_status'       => 'Active',
        'service_type_billing'=> 'NTTN, IIG',
        'sm_kam'              => 'Md. Sohel Rana',
        'team_name'           => 'Team Jahirul',
        'collection_kam'      => 'Naznin',
        'collection_supervisor'=> 'CS Shehave',
    ],
    [
        'opus_id'             => '012618394',
        'client_name'         => 'Freedom Online',
        'client_status'       => 'Active',
        'service_type_billing'=> 'IIG',
        'sm_kam'              => 'Tabassom Tamanna Haque',
        'team_name'           => 'Team Abid',
        'collection_kam'      => 'Hasnat',
        'collection_supervisor'=> 'CS Azim',
    ],
    [
        'opus_id'             => '012618389',
        'client_name'         => 'Siddique Online',
        'client_status'       => 'Active',
        'service_type_billing'=> 'NTTN, IIG',
        'sm_kam'              => 'Md. Sohel Rana',
        'team_name'           => 'Team Jahirul',
        'collection_kam'      => 'Sarwar',
        'collection_supervisor'=> 'CS Azim',
    ],
];

$inserted = 0;
$skipped  = 0;

foreach ($newClients as $data) {
    $exists = DB::table('client')
        ->where('opus_id', $data['opus_id'])
        ->orWhere('client_name', $data['client_name'])
        ->first();

    if ($exists) {
        echo "SKIPPED (already exists): {$data['client_name']} (Opus: {$data['opus_id']}) — DB ID: {$exists->client_id}\n";
        $skipped++;
        continue;
    }

    $data['created_at'] = now();
    $data['updated_at'] = now();
    $id = DB::table('client')->insertGetId($data);
    echo "INSERTED: {$data['client_name']} (Opus: {$data['opus_id']}) => client_id: $id\n";
    $inserted++;
}

echo "\nDone. Inserted: $inserted | Skipped: $skipped\n";
