<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientLogSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $fields = ['client_status', 'agreement_status', 'barring_priority', 'service_discontinuation_date'];
        $users = ['admin@scomm.com', 'manager@scomm.com', 'kam@scomm.com'];
        
        $rows = [];
        for ($i = 1; $i <= 50; $i++) {
            $field = $fields[$i % count($fields)];
            $rows[] = [
                'client_id' => $i,
                'field_name' => $field,
                'old_value' => $field === 'client_status' ? 'Active' : ($field === 'barring_priority' ? 'P2' : null),
                'new_value' => $field === 'client_status' ? 'Watchlist' : ($field === 'barring_priority' ? 'P1' : '2026-04-30'),
                'updated_by' => $users[$i % count($users)],
                'created_at' => $now->copy()->subDays($i),
            ];
        }

        DB::table('client_log')->insert($rows);
    }
}
