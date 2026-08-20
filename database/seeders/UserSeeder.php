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
            // Admin and Management who are not in the new department list, or have specific mappings:
            [
                'name' => 'Tamjidul Haque Chowdhury',
                'email' => 'tamjidul.haque@summitcommunications.net',
                'role' => 'mgt',
            ],

            // Sales & Marketing SComm -> sm_kam (plus retaining other roles like mgt)
            [
                'name' => 'Abu Talha Shawon',
                'email' => 'abu.talha@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Abul Khayer Md. Fahad',
                'email' => 'fahad.khayer@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Ashak-A-Mahbub Khan',
                'email' => 'mahbub.khan@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Azmatul Aman',
                'email' => 'azmatul.aman@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Ishtiaq Uddin Ahmmed',
                'email' => 'ishtiaq.uddin@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Jahirul Islam',
                'email' => 'jahirul.islam@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Jannatul Nayem',
                'email' => 'jannatul.nayem@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Jibeshwar Purakayastha',
                'email' => 'j.purakayastha@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'K.M. Mazharul Islam',
                'email' => 'mazharul.islam@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Kawsar Ahammed Siddiki',
                'email' => 'kawsar.ahammed@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md Mohsin Sujon',
                'email' => 'mohsin.sujon@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md. Abdul Kadir Shoel',
                'email' => 'abdul.kadir@summitcommunications.net',
                'role' => ['mgt', 'sm_kam'],
            ],
            [
                'name' => 'Md. Abu Sayeed',
                'email' => 'abu.sayeed@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md. Ashik Uz Zoha',
                'email' => 'ashik.zoha@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md. Fahim Ahmed',
                'email' => 'fahim.ahmed@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md. Fazle Rabbi',
                'email' => 'fazle.rabbi@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md. Mahin Alam',
                'email' => 'mahin.alam@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md. Maksudul Karim',
                'email' => 'maksudul.karim@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md. Mezbah Uddin Chy',
                'email' => 'm.uddin@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md. Mominul Islam',
                'email' => 'mominul.islam@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md. Mosabbir Hussain',
                'email' => 'mosabbir.hussain@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md. Mostafizur Rahman',
                'email' => 'mostafizur.rahman@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md. Mushfek Arefin',
                'email' => 'mushfek.arefin@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md. Mustafijur Rahman Shishir',
                'email' => 'mustafijur.rahman@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md. Neamul Islam',
                'email' => 'neamul.Islam@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md. Sakib Aziz',
                'email' => 'sakib.aziz@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md. Shahinur Islam Saju',
                'email' => 'shahinur.islam@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md. Shamrat Shahriar',
                'email' => 'md.shamrat@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md. Sohel Rana',
                'email' => 'm.rana@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md. Taher Bin Hossain',
                'email' => 'taher.bin@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md. Wahiduzzaman Shanto',
                'email' => 'md.shanto@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md.Abid Hasan',
                'email' => 'md.abid@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md.Israfil Khan',
                'email' => 'israfil.khan@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Md.Noor Hossain',
                'email' => 'hossain.noor@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Mollick Tanzim Akhtar',
                'email' => 'tanzim.akhtar@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Nubuiya Mahfuz Naimee',
                'email' => 'nubuiya.mahfuz@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Partha Pratim Sarkar',
                'email' => 'partha.sarkar@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Roachi Shome',
                'email' => 'roachi.shome@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Rubaet Bin Hasan',
                'email' => 'rubaet.hasan@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Rubaiyat Rahman',
                'email' => 'rubaiyat.rahman@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Ryhanul Islam Khan',
                'email' => 'ryhanul.islam@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'S.M. Tanvir Rahman',
                'email' => 'tanvir.rahman@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Sadia Nowshin Farin',
                'email' => 'sadia.nowshin@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Sharmin Zaman',
                'email' => 'sharmin.zaman@summitcommunications.net',
                'role' => ['mgt', 'sm_kam'],
            ],
            [
                'name' => 'SK Sanaullah',
                'email' => 'sk.sanaullah@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Syed Shihab Rahman',
                'email' => 'shihab.rahman@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Tabassom Tamanna Haque',
                'email' => 'tabassom.tamanna@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Tasnim Rahman Nafis',
                'email' => 'tasnim.rahman@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Toukir Azam Chowdhury',
                'email' => 'toukir.azam@summitcommunications.net',
                'role' => 'sm_kam',
            ],
            [
                'name' => 'Umaya Afrin Priya',
                'email' => 'umaya.afrin@summitcommunications.net',
                'role' => 'sm_kam',
            ],

            // Billing & Revenue Collection SComm -> billing (along with any previously designated roles like collection_kam or collection_hod)
            [
                'name' => 'Bocktiar Asif',
                'email' => 'bocktiar.asif@summitcommunications.net',
                'role' => 'billing',
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
                'name' => 'Md Ferdous Azim',
                'email' => 'ferdous.azim@summitcommunications.net',
                'role' => ['collection_kam', 'billing'],
            ],
            [
                'name' => 'Md Khairul',
                'email' => 'md.khairul@summitcommunications.net',
                'role' => ['collection_kam', 'billing'],
            ],
            [
                'name' => 'Md. Aftab Hossain',
                'email' => 'aftab.hossain@summitcommunications.net',
                'role' => 'billing',
            ],
            [
                'name' => 'Md. Anik',
                'email' => 'md.anik@summitcommunications.net',
                'role' => ['collection_kam', 'billing'],
            ],
            [
                'name' => 'Md. Hasanul Banna',
                'email' => 'hasanul.banna@summitcommunications.net',
                'role' => 'billing',
            ],
            [
                'name' => 'Md. Mehedi Hassan',
                'email' => 'md.mehedi@summitcommunications.net',
                'role' => 'billing',
            ],
            [
                'name' => 'Md. Mozammel Hossain',
                'email' => 'md.mozammel@summitcommunications.net',
                'role' => 'billing',
            ],
            [
                'name' => 'Md. Nur Hossain Tanim',
                'email' => 'nur.tanim@summitcommunications.net',
                'role' => 'billing',
            ],
            [
                'name' => 'Md. Shamsudhoha Ferdaus',
                'email' => 'md.shamsudhoha@summitcommunications.net',
                'role' => 'billing',
            ],
            [
                'name' => 'Md. Shareef Al Hoque',
                'email' => 'shareef.hoque@summitcommunications.net',
                'role' => ['collection_kam', 'billing'],
            ],
            [
                'name' => 'Md. Shehave Hossen',
                'email' => 'shehave.hossen@summitcommunications.net',
                'role' => ['collection_kam', 'billing'],
            ],
            [
                'name' => 'Md. Tarikul Islam',
                'email' => 'md.tarikul@summitcommunications.net',
                'role' => ['collection_kam', 'billing'],
            ],
            [
                'name' => 'Md. Taslimur Rahman',
                'email' => 'taslimur.rahman@summitcommunications.net',
                'role' => ['collection_hod', 'billing'],
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
                'name' => 'Md.Abul Hasnat',
                'email' => 'abul.hasnat@summitcommunications.net',
                'role' => ['collection_kam', 'billing'],
            ],
            [
                'name' => 'Mehedi Hasan',
                'email' => 'hasan.mehedi@summitcommunications.net',
                'role' => ['collection_kam', 'billing'],
            ],
            [
                'name' => 'Mithun Kumar Ghosh',
                'email' => 'mithun.kumar@summitcommunications.net',
                'role' => ['collection_kam', 'billing'],
            ],
            [
                'name' => 'Mohammad Ehsanul Haque',
                'email' => 'ehsanul.haque@summitcommunications.net',
                'role' => ['collection_kam', 'billing'],
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
                'name' => 'Sajeda Naznin',
                'email' => 'sajeda.naznin@summitcommunications.net',
                'role' => 'billing',
            ],
            [
                'name' => 'Sarwar Sayeed',
                'email' => 'sarwar.sayeed@summitcommunications.net',
                'role' => ['collection_kam', 'billing'],
            ],
            [
                'name' => 'Serajum Monira',
                'email' => 'serajum.monira@summitcommunications.net',
                'role' => 'billing',
            ],
            [
                'name' => 'Shohag Setu',
                'email' => 'shohag.setu@summitcommunications.net',
                'role' => ['collection_kam', 'billing'],
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
