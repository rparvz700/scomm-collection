<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;

class ClientUserMappingSeeder extends Seeder
{
    public function run(): void
    {
        // 1. collection_kam mapping
        $collectionKamMapping = [
            'Ehsanul' => 16,
            'Khairul' => 13,
            'Anik' => 14,
            'Shehave' => 3,
            'Shareef' => 15,
            'Mithun' => 5,
            'Hasan' => 28,
            'Azim' => 2,
            'Shohag' => 17,
            'Mehedi' => 12,
            'Tarikul' => 4,
            'Sarwar' => 11,
            'Hasnat' => 10,
            'Ferdaus' => 2,
        ];

        foreach ($collectionKamMapping as $name => $userId) {
            Client::where('collection_kam', $name)->update(['collection_kam_id' => $userId]);
        }

        // 2. collection_supervisor mapping
        $collectionSupervisorMapping = [
            'CS Mithun' => 5,
            'CS Tarikul' => 4,
            'CS Shehave' => 3,
            'CS Azim' => 2,
            'CS Ferdaus' => 2,
        ];

        foreach ($collectionSupervisorMapping as $name => $userId) {
            Client::where('collection_supervisor', $name)->update(['collection_supervisor_id' => $userId]);
        }

        // 3. nttn_billing_kam mapping
        $nttnBillingKamMapping = [
            'Waly' => 23,
            'Jubayar' => 18,
            'Nayeemur' => 25,
            'Tanim' => 21,
            'Palash' => 26,
        ];

        foreach ($nttnBillingKamMapping as $name => $userId) {
            Client::where('nttn_billing_kam', $name)->update(['nttn_billing_kam_id' => $userId]);
        }

        // 4. iig_itc_billing_kam mapping
        $iigItcBillingKamMapping = [
            'Monira' => 27,
            'Mahbub' => 19,
            'Aftab' => 20,
            'Jubayar' => 18,
            'Tawhid' => 22,
        ];

        foreach ($iigItcBillingKamMapping as $name => $userId) {
            Client::where('iig_itc_billing_kam', $name)->update(['iig_itc_billing_kam_id' => $userId]);
        }
    }
}
