<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UpdateClientTeamMatrixSeeder extends Seeder
{
    public function run(): void
    {
        $nicknames = config('peoplemapping.nicknames') ?? [];

        // SM Matrix: email => ['team' => ..., 'supervisor' => ..., 'supervisor_email' => ...]
        $smMatrix = [
            'md.abid@summitcommunications.net' => ['team' => 'Team Abid', 'supervisor' => 'Md. Abid Hasan', 'supervisor_email' => 'md.abid@summitcommunications.net'],
            'shihab.rahman@summitcommunications.net' => ['team' => 'Team Abid', 'supervisor' => 'Md. Abid Hasan', 'supervisor_email' => 'md.abid@summitcommunications.net'],
            'mahbub.khan@summitcommunications.net' => ['team' => 'Team Abid', 'supervisor' => 'Md. Abid Hasan', 'supervisor_email' => 'md.abid@summitcommunications.net'],
            'mahin.alam@summitcommunications.net' => ['team' => 'Team Abid', 'supervisor' => 'Md. Abid Hasan', 'supervisor_email' => 'md.abid@summitcommunications.net'],
            'tabassom.tamanna@summitcommunications.net' => ['team' => 'Team Abid', 'supervisor' => 'Md. Abid Hasan', 'supervisor_email' => 'md.abid@summitcommunications.net'],
            'neamul.islam@summitcommunications.net' => ['team' => 'Team Abid', 'supervisor' => 'Md. Abid Hasan', 'supervisor_email' => 'md.abid@summitcommunications.net'],
            'fahad.khayer@summitcommunications.net' => ['team' => 'Team Fahad', 'supervisor' => 'Abul Khayer Md Fahad', 'supervisor_email' => 'fahad.khayer@summitcommunications.net'],
            'nubuiya.mahfuz@summitcommunications.net' => ['team' => 'Team Fahad', 'supervisor' => 'Abul Khayer Md Fahad', 'supervisor_email' => 'fahad.khayer@summitcommunications.net'],
            'mostafizur.rahman@summitcommunications.net' => ['team' => 'Team Fahad', 'supervisor' => 'Abul Khayer Md Fahad', 'supervisor_email' => 'fahad.khayer@summitcommunications.net'],
            'mominul.islam@summitcommunications.net' => ['team' => 'Team Fahad', 'supervisor' => 'Abul Khayer Md Fahad', 'supervisor_email' => 'fahad.khayer@summitcommunications.net'],
            'jahirul.islam@summitcommunications.net' => ['team' => 'Team Jahirul', 'supervisor' => 'Jahirul Islam', 'supervisor_email' => 'jahirul.islam@summitcommunications.net'],
            'partha.sarkar@summitcommunications.net' => ['team' => 'Team Jahirul', 'supervisor' => 'Jahirul Islam', 'supervisor_email' => 'jahirul.islam@summitcommunications.net'],
            'tanvir.rahman@summitcommunications.net' => ['team' => 'Team Jahirul', 'supervisor' => 'Jahirul Islam', 'supervisor_email' => 'jahirul.islam@summitcommunications.net'],
            'm.rana@summitcommunications.net' => ['team' => 'Team Jahirul', 'supervisor' => 'Jahirul Islam', 'supervisor_email' => 'jahirul.islam@summitcommunications.net'],
            'rubaiyat.rahman@summitcommunications.net' => ['team' => 'Team Jahirul', 'supervisor' => 'Jahirul Islam', 'supervisor_email' => 'jahirul.islam@summitcommunications.net'],
            'rubaet.hasan@summitcommunications.net' => ['team' => 'Team Jahirul', 'supervisor' => 'Jahirul Islam', 'supervisor_email' => 'jahirul.islam@summitcommunications.net'],
            'abu.sayeed@summitcommunications.net' => ['team' => 'Team Mitul', 'supervisor' => 'Md. Abu Sayeed', 'supervisor_email' => 'abu.sayeed@summitcommunications.net'],
            'jannatul.nayem@summitcommunications.net' => ['team' => 'Team Mitul', 'supervisor' => 'Md. Abu Sayeed', 'supervisor_email' => 'abu.sayeed@summitcommunications.net'],
            'mustafijur.rahman@summitcommunications.net' => ['team' => 'Team Mitul', 'supervisor' => 'Md. Abu Sayeed', 'supervisor_email' => 'abu.sayeed@summitcommunications.net'],
            'toukir.azam@summitcommunications.net' => ['team' => 'Team Mitul', 'supervisor' => 'Md. Abu Sayeed', 'supervisor_email' => 'abu.sayeed@summitcommunications.net'],
            'umaya.afrin@summitcommunications.net' => ['team' => 'Team Mitul', 'supervisor' => 'Md. Abu Sayeed', 'supervisor_email' => 'abu.sayeed@summitcommunications.net'],
            'mosabbir.hussain@summitcommunications.net' => ['team' => 'Team Mosabbir', 'supervisor' => 'Md. Mosabbir Hussain', 'supervisor_email' => 'mosabbir.hussain@summitcommunications.net'],
            'mazharul.islam@summitcommunications.net' => ['team' => 'Team Mosabbir', 'supervisor' => 'Md. Mosabbir Hussain', 'supervisor_email' => 'mosabbir.hussain@summitcommunications.net'],
            'mushfek.arefin@summitcommunications.net' => ['team' => 'Team Mosabbir', 'supervisor' => 'Md. Mosabbir Hussain', 'supervisor_email' => 'mosabbir.hussain@summitcommunications.net'],
            'fazle.rabbi@summitcommunications.net' => ['team' => 'Team Mosabbir', 'supervisor' => 'Md. Mosabbir Hussain', 'supervisor_email' => 'mosabbir.hussain@summitcommunications.net'],
            'shahinur.islam@summitcommunications.net' => ['team' => 'Team Mosabbir', 'supervisor' => 'Md. Mosabbir Hussain', 'supervisor_email' => 'mosabbir.hussain@summitcommunications.net'],
            'hossain.noor@summitcommunications.net' => ['team' => 'Team Noor', 'supervisor' => 'Md. Noor Hossain', 'supervisor_email' => 'hossain.noor@summitcommunications.net'],
            'ishtiaq.uddin@summitcommunications.net' => ['team' => 'Team Noor', 'supervisor' => 'Md. Noor Hossain', 'supervisor_email' => 'hossain.noor@summitcommunications.net'],
            'taher.bin@summitcommunications.net' => ['team' => 'Team Noor', 'supervisor' => 'Md. Noor Hossain', 'supervisor_email' => 'hossain.noor@summitcommunications.net'],
            'ryhanul.islam@summitcommunications.net' => ['team' => 'Team Ryhan', 'supervisor' => 'Ryhanul Islam Khan', 'supervisor_email' => 'ryhanul.islam@summitcommunications.net'],
            'md.shanto@summitcommunications.net' => ['team' => 'Team Ryhan', 'supervisor' => 'Ryhanul Islam Khan', 'supervisor_email' => 'ryhanul.islam@summitcommunications.net'],
            'sk.sanaullah@summitcommunications.net' => ['team' => 'Team Ryhan', 'supervisor' => 'Ryhanul Islam Khan', 'supervisor_email' => 'ryhanul.islam@summitcommunications.net'],
            'md.shamrat@summitcommunications.net' => ['team' => 'Team Ryhan', 'supervisor' => 'Ryhanul Islam Khan', 'supervisor_email' => 'ryhanul.islam@summitcommunications.net'],
            'asif.dipu@summitcommunications.net' => ['team' => 'Team Ryhan', 'supervisor' => 'Ryhanul Islam Khan', 'supervisor_email' => 'ryhanul.islam@summitcommunications.net'],
            'fahim.ahmed@summitcommunications.net' => ['team' => 'Team Ryhan', 'supervisor' => 'Ryhanul Islam Khan', 'supervisor_email' => 'ryhanul.islam@summitcommunications.net'],
            'abdul.kadir@summitcommunications.net' => ['team' => 'Team S&M', 'supervisor' => 'Md. Abdul Kadir Shoel', 'supervisor_email' => 'abdul.kadir@summitcommunications.net'],
            'azmatul.aman@summitcommunications.net' => ['team' => 'Team S&M', 'supervisor' => 'Md. Abdul Kadir Shoel', 'supervisor_email' => 'abdul.kadir@summitcommunications.net'],
            'sakib.aziz@summitcommunications.net' => ['team' => 'Team Sakib', 'supervisor' => 'Sakib Aziz', 'supervisor_email' => 'sakib.aziz@summitcommunications.net'],
            'tasnim.rahman@summitcommunications.net' => ['team' => 'Team Sakib', 'supervisor' => 'Sakib Aziz', 'supervisor_email' => 'sakib.aziz@summitcommunications.net'],
            'm.uddin@summitcommunications.net' => ['team' => 'Team Sakib', 'supervisor' => 'Sakib Aziz', 'supervisor_email' => 'sakib.aziz@summitcommunications.net'],
            'abu.talha@summitcommunications.net' => ['team' => 'Team Talha', 'supervisor' => 'Abu Talha Shawon', 'supervisor_email' => 'abu.talha@summitcommunications.net'],
            'j.purakayastha@summitcommunications.net' => ['team' => 'Team Talha', 'supervisor' => 'Abu Talha Shawon', 'supervisor_email' => 'abu.talha@summitcommunications.net'],
            'roachi.shome@summitcommunications.net' => ['team' => 'Team Talha', 'supervisor' => 'Abu Talha Shawon', 'supervisor_email' => 'abu.talha@summitcommunications.net'],
            'sadia.nowshin@summitcommunications.net' => ['team' => 'Team Talha', 'supervisor' => 'Abu Talha Shawon', 'supervisor_email' => 'abu.talha@summitcommunications.net'],
        ];

        // Direct Team Name to Supervisor Map
        $teamToSupervisorMap = [
            'Team Abid' => ['supervisor' => 'Md. Abid Hasan', 'email' => 'md.abid@summitcommunications.net'],
            'Team Fahad' => ['supervisor' => 'Abul Khayer Md Fahad', 'email' => 'fahad.khayer@summitcommunications.net'],
            'Team Jahirul' => ['supervisor' => 'Jahirul Islam', 'email' => 'jahirul.islam@summitcommunications.net'],
            'Team Mitul' => ['supervisor' => 'Md. Abu Sayeed', 'email' => 'abu.sayeed@summitcommunications.net'],
            'Team Mosabbir' => ['supervisor' => 'Md. Mosabbir Hussain', 'email' => 'mosabbir.hussain@summitcommunications.net'],
            'Team Noor' => ['supervisor' => 'Md. Noor Hossain', 'email' => 'hossain.noor@summitcommunications.net'],
            'Team Ryhan' => ['supervisor' => 'Ryhanul Islam Khan', 'email' => 'ryhanul.islam@summitcommunications.net'],
            'Team S&M' => ['supervisor' => 'Md. Abdul Kadir Shoel', 'email' => 'abdul.kadir@summitcommunications.net'],
            'Team Sakib' => ['supervisor' => 'Sakib Aziz', 'email' => 'sakib.aziz@summitcommunications.net'],
            'Team Talha' => ['supervisor' => 'Abu Talha Shawon', 'email' => 'abu.talha@summitcommunications.net'],
            'Team Ridwan' => ['supervisor' => 'Ridwan', 'email' => 'ridwan@summitcommunications.net'],
            'Team Sujon' => ['supervisor' => 'Sujon', 'email' => 'sujon@summitcommunications.net'],
            'Team Rajib' => ['supervisor' => 'Rajib', 'email' => 'rajib@summitcommunications.net'],
            'Team Israfil' => ['supervisor' => 'Md. Israfil Khan', 'email' => 'israfil.khan@summitcommunications.net'],
        ];

        // Collection Matrix: email => ['team' => ..., 'supervisor' => ..., 'supervisor_email' => ...]
        $collectionMatrix = [
            'ferdous.azim@summitcommunications.net' => ['team' => 'Team Azim', 'supervisor' => 'Md. Ferdous Azim', 'supervisor_email' => 'ferdous.azim@summitcommunications.net'],
            'abul.hasnat@summitcommunications.net' => ['team' => 'Team Azim', 'supervisor' => 'Md. Ferdous Azim', 'supervisor_email' => 'ferdous.azim@summitcommunications.net'],
            'md.mehedi@summitcommunications.net' => ['team' => 'Team Azim', 'supervisor' => 'Md. Ferdous Azim', 'supervisor_email' => 'ferdous.azim@summitcommunications.net'],
            'sarwar.sayeed@summitcommunications.net' => ['team' => 'Team Azim', 'supervisor' => 'Md. Ferdous Azim', 'supervisor_email' => 'ferdous.azim@summitcommunications.net'],
            'mithun.kumar@summitcommunications.net' => ['team' => 'Team Mithun', 'supervisor' => 'Mithun Kumar Ghosh', 'supervisor_email' => 'mithun.kumar@summitcommunications.net'],
            'ehsanul.haque@summitcommunications.net' => ['team' => 'Team Mithun', 'supervisor' => 'Mithun Kumar Ghosh', 'supervisor_email' => 'mithun.kumar@summitcommunications.net'],
            'shohag.setu@summitcommunications.net' => ['team' => 'Team Mithun', 'supervisor' => 'Mithun Kumar Ghosh', 'supervisor_email' => 'mithun.kumar@summitcommunications.net'],
            'md.tarikul@summitcommunications.net' => ['team' => 'Team Tarikul', 'supervisor' => 'Md. Tarikul Islam', 'supervisor_email' => 'md.tarikul@summitcommunications.net'],
            'md.anik@summitcommunications.net' => ['team' => 'Team Tarikul', 'supervisor' => 'Md. Tarikul Islam', 'supervisor_email' => 'md.tarikul@summitcommunications.net'],
            'md.khairul@summitcommunications.net' => ['team' => 'Team Tarikul', 'supervisor' => 'Md. Tarikul Islam', 'supervisor_email' => 'md.tarikul@summitcommunications.net'],
            'shareef.hoque@summitcommunications.net' => ['team' => 'Team Tarikul', 'supervisor' => 'Md. Tarikul Islam', 'supervisor_email' => 'md.tarikul@summitcommunications.net'],
            'shehave.hossen@summitcommunications.net' => ['team' => 'Team Shehave', 'supervisor' => 'Md. Shehave Hossen', 'supervisor_email' => 'shehave.hossen@summitcommunications.net'],
            'bocktiar.asif@summitcommunications.net' => ['team' => 'Team Shehave', 'supervisor' => 'Md. Shehave Hossen', 'supervisor_email' => 'shehave.hossen@summitcommunications.net'],
            'hasanul.banna@summitcommunications.net' => ['team' => 'Team Shehave', 'supervisor' => 'Md. Shehave Hossen', 'supervisor_email' => 'shehave.hossen@summitcommunications.net'],
            'sajeda.naznin@summitcommunications.net' => ['team' => 'Team Shehave', 'supervisor' => 'Md. Shehave Hossen', 'supervisor_email' => 'shehave.hossen@summitcommunications.net'],
        ];

        // Ensure user exists helper
        $getOrCreateUserId = function (string $email, string $defaultName): int {
            $cleanEmail = strtolower(trim($email));
            $user = User::whereRaw('LOWER(email) = ?', [$cleanEmail])->first();
            if ($user) {
                return $user->id;
            }

            $newUser = User::create([
                'name' => $defaultName,
                'email' => $cleanEmail,
                'password' => Hash::make('password'),
                'is_active' => true,
            ]);

            return $newUser->id;
        };

        // Helper: Resolve User ID from raw text name or email using config/peoplemapping.php
        $resolveUserIdByName = function (?string $rawName) use ($nicknames, $getOrCreateUserId): ?int {
            if (!$rawName) return null;
            $clean = strtolower(trim($rawName));
            if (empty($clean)) return null;

            if (filter_var($clean, FILTER_VALIDATE_EMAIL)) {
                return $getOrCreateUserId($clean, ucwords(str_replace(['.', '_'], ' ', explode('@', $clean)[0])));
            }

            if (isset($nicknames[$clean])) {
                $email = strtolower(trim($nicknames[$clean]));
                return $getOrCreateUserId($email, ucwords($rawName));
            }

            $variant1 = preg_replace('/\.(\s+)/', '.', $clean);
            $variant2 = preg_replace('/\.([^\s])/', '. $1', $clean);
            if (isset($nicknames[$variant1])) {
                return $getOrCreateUserId(strtolower(trim($nicknames[$variant1])), ucwords($rawName));
            }
            if (isset($nicknames[$variant2])) {
                return $getOrCreateUserId(strtolower(trim($nicknames[$variant2])), ucwords($rawName));
            }

            $user = User::whereRaw('LOWER(name) = ?', [$clean])->first();
            if ($user) {
                return $user->id;
            }

            // Fallback: auto-provision user so KAM ID is never broken
            $fallbackEmail = Str::slug($clean, '.') . '@summitcommunications.net';
            return $getOrCreateUserId($fallbackEmail, ucwords($rawName));
        };

        $clients = Client::all();
        $updatedCount = 0;

        foreach ($clients as $client) {
            $updates = [];

            // -------------------------------------------------------------
            // 1. Process Sales & Marketing (SM) Matrix
            // -------------------------------------------------------------
            $smEmail = null;
            if (!empty($client->sm_kam)) {
                $clean = strtolower(trim($client->sm_kam));
                $smEmail = $nicknames[$clean] ?? (filter_var($clean, FILTER_VALIDATE_EMAIL) ? $clean : null);
            }

            if ($smEmail && isset($smMatrix[$smEmail])) {
                $smData = $smMatrix[$smEmail];
                $updates['team_name'] = $smData['team'];
                $updates['sm_supervisor'] = $smData['supervisor'];
                $updates['sm_supervisor_id'] = $getOrCreateUserId($smData['supervisor_email'], $smData['supervisor']);
                $updates['sm_kam_id'] = $getOrCreateUserId($smEmail, $client->sm_kam ?: 'SM KAM');
            } else {
                if (!empty($client->sm_kam)) {
                    $id = $resolveUserIdByName($client->sm_kam);
                    if ($id) $updates['sm_kam_id'] = $id;
                }
                if (!empty($client->sm_supervisor)) {
                    $id = $resolveUserIdByName($client->sm_supervisor);
                    if ($id) $updates['sm_supervisor_id'] = $id;
                }
            }

            // Fallback: If sm_supervisor is null/empty but team_name is populated
            $effectiveTeam = $updates['team_name'] ?? $client->team_name;
            $effectiveSupervisor = $updates['sm_supervisor'] ?? $client->sm_supervisor;

            if (empty($effectiveSupervisor) && !empty($effectiveTeam)) {
                $tName = trim($effectiveTeam);
                if (isset($teamToSupervisorMap[$tName])) {
                    $supData = $teamToSupervisorMap[$tName];
                    $updates['sm_supervisor'] = $supData['supervisor'];
                    $updates['sm_supervisor_id'] = $getOrCreateUserId($supData['email'], $supData['supervisor']);
                } else {
                    $leadName = Str::startsWith($tName, 'Team ') ? trim(substr($tName, 5)) : $tName;
                    if (!empty($leadName)) {
                        $supId = $resolveUserIdByName($leadName);
                        $updates['sm_supervisor'] = $leadName;
                        if ($supId) {
                            $updates['sm_supervisor_id'] = $supId;
                        }
                    }
                }
            }

            // -------------------------------------------------------------
            // 2. Process Collection Matrix
            // -------------------------------------------------------------
            $colEmail = null;
            if (!empty($client->collection_kam)) {
                $clean = strtolower(trim($client->collection_kam));
                $colEmail = $nicknames[$clean] ?? (filter_var($clean, FILTER_VALIDATE_EMAIL) ? $clean : null);
            }

            if ($colEmail && isset($collectionMatrix[$colEmail])) {
                $colData = $collectionMatrix[$colEmail];
                $updates['collection_team_name'] = $colData['team'];
                $updates['collection_supervisor'] = $colData['supervisor'];
                $updates['collection_supervisor_id'] = $getOrCreateUserId($colData['supervisor_email'], $colData['supervisor']);
                $updates['collection_kam_id'] = $getOrCreateUserId($colEmail, $client->collection_kam ?: 'Collection KAM');
            } else {
                if (!empty($client->collection_kam)) {
                    $id = $resolveUserIdByName($client->collection_kam);
                    if ($id) $updates['collection_kam_id'] = $id;
                }
                if (!empty($client->collection_supervisor)) {
                    $id = $resolveUserIdByName($client->collection_supervisor);
                    if ($id) $updates['collection_supervisor_id'] = $id;
                }
            }

            if (!empty($updates)) {
                $client->update($updates);
                $updatedCount++;
            }
        }
    }
}
