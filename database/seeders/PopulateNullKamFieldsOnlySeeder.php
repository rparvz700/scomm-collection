<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class PopulateNullKamFieldsOnlySeeder extends Seeder
{
    public function run(): void
    {
        $smTeamToLeadMap = [
            'Team Abid' => 'Md. Abid Hasan',
            'Team Fahad' => 'Abul Khayer Md Fahad',
            'Team Jahirul' => 'Jahirul Islam',
            'Team Mitul' => 'Md. Abu Sayeed',
            'Team Mosabbir' => 'Md. Mosabbir Hussain',
            'Team Noor' => 'Md. Noor Hossain',
            'Team Ryhan' => 'Ryhanul Islam Khan',
            'Team S&M' => 'Md. Abdul Kadir Shoel',
            'Team Sakib' => 'Sakib Aziz',
            'Team Talha' => 'Abu Talha Shawon',
            'Team Ridwan' => 'Ridwan',
            'Team Sujon' => 'Sujon',
            'Team Rajib' => 'Rajib',
            'Team Israfil' => 'Md. Israfil Khan',
        ];

        $colTeamToLeadMap = [
            'Team Azim' => 'Md. Ferdous Azim',
            'Team Mithun' => 'Mithun Kumar Ghosh',
            'Team Tarikul' => 'Md. Tarikul Islam',
            'Team Shehave' => 'Md. Shehave Hossen',
        ];

        $clients = Client::where(function ($q) {
            $q->whereNull('sm_kam')
              ->orWhere('sm_kam', '')
              ->orWhereNull('collection_kam')
              ->orWhere('collection_kam', '');
        })->get();

        $updatedCount = 0;

        foreach ($clients as $client) {
            $updates = [];

            // 1. Populate sm_kam ONLY if NULL or empty
            if (empty($client->sm_kam)) {
                if (!empty($client->sm_supervisor)) {
                    $updates['sm_kam'] = $client->sm_supervisor;
                } elseif (!empty($client->team_name) && isset($smTeamToLeadMap[trim($client->team_name)])) {
                    $updates['sm_kam'] = $smTeamToLeadMap[trim($client->team_name)];
                    $updates['sm_supervisor'] = $smTeamToLeadMap[trim($client->team_name)];
                } else {
                    $updates['sm_kam'] = 'Md. Abid Hasan';
                    $updates['team_name'] = $client->team_name ?: 'Team Abid';
                    $updates['sm_supervisor'] = 'Md. Abid Hasan';
                }
            }

            // 2. Populate collection_kam ONLY if NULL or empty
            if (empty($client->collection_kam)) {
                if (!empty($client->collection_supervisor)) {
                    $updates['collection_kam'] = $client->collection_supervisor;
                } elseif (!empty($client->collection_team_name) && isset($colTeamToLeadMap[trim($client->collection_team_name)])) {
                    $updates['collection_kam'] = $colTeamToLeadMap[trim($client->collection_team_name)];
                    $updates['collection_supervisor'] = $colTeamToLeadMap[trim($client->collection_team_name)];
                } else {
                    $updates['collection_kam'] = 'Md. Ferdous Azim';
                    $updates['collection_team_name'] = $client->collection_team_name ?: 'Team Azim';
                    $updates['collection_supervisor'] = 'Md. Ferdous Azim';
                }
            }

            if (!empty($updates)) {
                $client->update($updates);
                $updatedCount++;
            }
        }

        // Re-run team matrix seeder to synchronize user IDs for all newly populated fields
        (new UpdateClientTeamMatrixSeeder())->run();
    }
}
