<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientDeferredPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $days60Clients = [
            'ADN International Gateway Limited',
            'ADN Telecom Limited',
            'Amber IT Limited',
            'Digi Jadoo Broadband Ltd.',
            'Level3 Carrier Limited',
            'Rego Communications Ltd.',
            'Starlink Services Bangladesh Ltd',
            'Velocity Networks Limited',
        ];

        $days90Clients = [
            'Earth Telecommunication (Pvt) Ltd.',
            'Race Online Limited',
        ];

        // Set 60 Days
        foreach ($days60Clients as $name) {
            DB::table('client')
                ->where('client_name', 'like', '%' . trim($name) . '%')
                ->update(['deferred_period' => 60]);
        }

        // Set 90 Days
        foreach ($days90Clients as $name) {
            DB::table('client')
                ->where('client_name', 'like', '%' . trim($name) . '%')
                ->update(['deferred_period' => 90]);
        }
    }
}
