<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Md Ferdous Azim',
                'email' => 'ferdous.azim@summitcommunications.net',
                'role' => 'collection_kam',
            ],
            [
                'name' => 'Md. Shehave Hossen',
                'email' => 'shehave.hossen@summitcommunications.net',
                'role' => 'collection_kam',
            ],
            [
                'name' => 'Md. Tarikul Islam',
                'email' => 'md.tarikul@summitcommunications.net',
                'role' => 'collection_kam',
            ],
            [
                'name' => 'Mithun Kumar Ghosh',
                'email' => 'mithun.kumar@summitcommunications.net',
                'role' => 'collection_kam',
            ],
            [
                'name' => 'Md. Taslimur Rahman',
                'email' => 'taslimur.rahman@summitcommunications.net',
                'role' => 'collection_hod',
            ],
            [
                'name' => 'Sharmin Zaman',
                'email' => 'sharmin.zaman@summitcommunications.net',
                'role' => 'mgt',
            ],
            [
                'name' => 'Md. Abdul Kadir Shoel',
                'email' => 'abdul.kadir@summitcommunications.net',
                'role' => 'mgt',
            ],
            [
                'name' => 'Tamjidul Haque Chowdhury',
                'email' => 'tamjidul.haque@summitcommunications.net',
                'role' => 'mgt',
            ],
            [
                'name' => 'Md.Abul Hasnat',
                'email' => 'abul.hasnat@summitcommunications.net',
                'role' => 'collection_kam',
            ],
            [
                'name' => 'Sarwar Sayeed',
                'email' => 'sarwar.sayeed@summitcommunications.net',
                'role' => 'collection_kam',
            ],
            [
                'name' => 'Mehedi Hasan',
                'email' => 'hasan.mehedi@summitcommunications.net',
                'role' => ['collection_kam', 'billing'],
            ],
            [
                'name' => 'Md Khairul',
                'email' => 'md.khairul@summitcommunications.net',
                'role' => 'collection_kam',
            ],
            [
                'name' => 'Md. Anik',
                'email' => 'md.anik@summitcommunications.net',
                'role' => 'collection_kam',
            ],
            [
                'name' => 'Md. Shareef Al Hoque',
                'email' => 'shareef.hoque@summitcommunications.net',
                'role' => 'collection_kam',
            ],
            [
                'name' => 'Mohammad Ehsanul Haque',
                'email' => 'ehsanul.haque@summitcommunications.net',
                'role' => 'collection_kam',
            ],
            [
                'name' => 'Shohag Setu',
                'email' => 'shohag.setu@summitcommunications.net',
                'role' => 'collection_kam',
            ],
            [
                'name' => 'Jubayar Anwar',
                'email' => 'jubayar.anwar@summitcommunications.net',
                'role' => 'billing',
            ],
            [
                'name' => 'Mahbubul Hasan Mahbub',
                'email' => 'mahbubul.hasan@summitcommunications.net',
                'role' => 'billing',
            ],
            [
                'name' => 'Md. Aftab Hossain',
                'email' => 'aftab.hossain@summitcommunications.net',
                'role' => 'billing',
            ],
            [
                'name' => 'Md. Nur Hossain Tanim',
                'email' => 'nur.tanim@summitcommunications.net',
                'role' => 'billing',
            ],
            [
                'name' => 'Md. Tawhidul Islam',
                'email' => 'tawhidul.islam@summitcommunications.net',
                'role' => 'billing',
            ],
            [
                'name' => 'Md. Walyul Islam Oly',
                'email' => 'walyul.islam@summitcommunications.net',
                'role' => 'billing',
            ],
            [
                'name' => 'Md. Wasif Hossain',
                'email' => 'wasif.hossain@summitcommunications.net',
                'role' => 'billing',
            ],
            [
                'name' => 'Nayeemur Rahman',
                'email' => 'nayeemur.rahman@summitcommunications.net',
                'role' => 'billing',
            ],
            [
                'name' => 'Palash Kumar Sarkar',
                'email' => 'palash.sarkar@summitcommunications.net',
                'role' => 'billing',
            ],
            [
                'name' => 'Serajum Monira',
                'email' => 'serajum.monira@summitcommunications.net',
                'role' => 'billing',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                ]
            );

            $roles = (array) $userData['role'];
            $user->syncRoles($roles);
        }
    }
}
