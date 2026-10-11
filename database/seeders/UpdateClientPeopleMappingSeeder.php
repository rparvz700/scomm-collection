<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class UpdateClientPeopleMappingSeeder extends Seeder
{
    public function run(): void
    {
        $nicknames = config('peoplemapping.nicknames') ?? [];
        $usersMap = User::all()->keyBy(function ($u) {
            return strtolower(trim($u->email));
        });

        // Helper to find user ID by name/nickname
        $findUserId = function (?string $rawName) use ($nicknames, $usersMap): ?int {
            if (!$rawName) return null;
            $cleanName = strtolower(trim($rawName));
            if (empty($cleanName)) return null;

            // 1. Direct email match
            if (filter_var($cleanName, FILTER_VALIDATE_EMAIL)) {
                $u = $usersMap->get($cleanName);
                if ($u) return $u->id;
            }

            // 2. Check nickname mapping
            if (isset($nicknames[$cleanName])) {
                $email = strtolower(trim($nicknames[$cleanName]));
                $u = $usersMap->get($email);
                if ($u) return $u->id;
            }

            // 3. Name match in users table
            foreach ($usersMap as $email => $user) {
                if (strtolower(trim($user->name)) === $cleanName) {
                    return $user->id;
                }
            }

            return null;
        };

        $clients = Client::all();
        $updatedCount = 0;

        foreach ($clients as $client) {
            $updates = [];

            // Collection KAM ID
            if ($client->collection_kam && !$client->collection_kam_id) {
                $id = $findUserId($client->collection_kam);
                if ($id) $updates['collection_kam_id'] = $id;
            }

            // Collection Supervisor ID
            if ($client->collection_supervisor && !$client->collection_supervisor_id) {
                $id = $findUserId($client->collection_supervisor);
                if ($id) $updates['collection_supervisor_id'] = $id;
            }

            // SM KAM ID
            if ($client->sm_kam && !$client->sm_kam_id) {
                $id = $findUserId($client->sm_kam);
                if ($id) $updates['sm_kam_id'] = $id;
            }

            // SM Supervisor ID
            if ($client->sm_supervisor && !$client->sm_supervisor_id) {
                $id = $findUserId($client->sm_supervisor);
                if ($id) $updates['sm_supervisor_id'] = $id;
            }

            // Populate collection_team_name if missing
            if (empty($client->collection_team_name) && !empty($client->collection_supervisor)) {
                $updates['collection_team_name'] = 'Collection Team - ' . trim($client->collection_supervisor);
            }

            if (!empty($updates)) {
                $client->update($updates);
                $updatedCount++;
            }
        }
    }
}
