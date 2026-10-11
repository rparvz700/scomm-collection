<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class UpdateSpecificClientDifferencesSeeder extends Seeder
{
    public function run(): void
    {
        $changes = [
            13   => ['sm_kam' => 'Md.Noor Hossain', 'team_name' => 'Team Noor'],
            14   => ['sm_kam' => 'Md.Noor Hossain', 'team_name' => 'Team Noor'],
            16   => ['sm_kam' => 'Md.Noor Hossain', 'team_name' => 'Team Noor'],
            36   => ['sm_kam' => 'Md. Mostafizur Rahman'],
            807  => ['sm_kam' => 'Roachi Shome', 'team_name' => 'Team Talha', 'collection_kam' => 'Hasan', 'collection_supervisor' => 'CS Shehave', 'nttn_billing_kam' => 'Waly'],
            1047 => ['sm_kam' => 'Md.Noor Hossain', 'team_name' => 'Team Noor'],
            106  => ['iig_itc_billing_kam' => 'N/A'],
            141  => ['iig_itc_billing_kam' => 'Mahbub'],
            150  => ['sm_kam' => 'Md.Noor Hossain', 'team_name' => 'Team Noor'],
            156  => ['sm_kam' => 'Ishtiaq Uddin Ahmmed', 'team_name' => 'Team Noor'],
            158  => ['sm_kam' => 'Ishtiaq Uddin Ahmmed', 'team_name' => 'Team Noor'],
            163  => ['nttn_billing_kam' => 'Nayeemur'],
            1205 => ['nttn_billing_kam' => 'Nayeemur'],
            1253 => ['iig_itc_billing_kam' => 'Mahbub'],
            179  => ['team_name' => 'Team Abid'],
            183  => ['sm_kam' => 'Md.Noor Hossain', 'team_name' => 'Team Noor'],
            198  => ['sm_kam' => 'Ishtiaq Uddin Ahmmed', 'team_name' => 'Team Noor'],
            199  => ['sm_kam' => 'Ishtiaq Uddin Ahmmed'],
            222  => ['sm_kam' => 'Ishtiaq Uddin Ahmmed'],
            231  => ['sm_kam' => 'Md. Taher Bin Hossain', 'team_name' => 'Team Noor'],
            247  => ['nttn_billing_kam' => 'Waly'],
            252  => ['sm_kam' => 'Md. Taher Bin Hossain', 'team_name' => 'Team Noor'],
            992  => ['sm_kam' => 'Md. Mezbah Uddin Chy'],
            838  => ['sm_kam' => 'Md. Mustafijur Rahman Shishir', 'team_name' => 'Team Mitul', 'nttn_billing_kam' => 'Nayeemur', 'iig_itc_billing_kam' => 'Monira'],
            961  => ['sm_kam' => 'Md.Noor Hossain', 'team_name' => 'Team Noor'],
            301  => ['sm_kam' => 'Md. Taher Bin Hossain', 'team_name' => 'Team Noor'],
            306  => ['sm_kam' => 'Ishtiaq Uddin Ahmmed', 'team_name' => 'Team Noor'],
            312  => ['sm_kam' => 'Md. Fazle Rabbi', 'team_name' => 'Team Mosabbir'],
            975  => ['nttn_billing_kam' => 'Nayeemur'],
            327  => ['sm_kam' => 'Toukir Azam Chowdhury', 'team_name' => 'Team Mitul'],
            317  => ['iig_itc_billing_kam' => 'Monira'],
            334  => ['sm_kam' => 'Ishtiaq Uddin Ahmmed', 'team_name' => 'Team Noor'],
            335  => ['sm_kam' => 'Md.Noor Hossain', 'team_name' => 'Team Noor'],
            336  => ['sm_kam' => 'Md.Noor Hossain', 'team_name' => 'Team Noor'],
            338  => ['sm_kam' => 'Partha Pratim Sarkar'],
            341  => ['sm_kam' => 'Ishtiaq Uddin Ahmmed', 'team_name' => 'Team Noor'],
            1210 => ['nttn_billing_kam' => 'Nayeemur'],
            976  => ['nttn_billing_kam' => 'Nayeemur'],
            377  => ['sm_kam' => 'Md.Noor Hossain', 'team_name' => 'Team Noor'],
            378  => ['sm_kam' => 'Md.Noor Hossain', 'team_name' => 'Team Noor'],
            385  => ['sm_kam' => 'Ishtiaq Uddin Ahmmed', 'team_name' => 'Team Noor'],
            397  => ['sm_kam' => 'Md.Noor Hossain', 'team_name' => 'Team Noor'],
            995  => ['sm_kam' => 'Ishtiaq Uddin Ahmmed', 'team_name' => 'Team Noor'],
            443  => ['iig_itc_billing_kam' => 'Aftab'],
            479  => ['sm_kam' => 'Ishtiaq Uddin Ahmmed', 'team_name' => 'Team Noor'],
            999  => ['sm_kam' => 'Ishtiaq Uddin Ahmmed', 'team_name' => 'Team Noor'],
            871  => ['sm_kam' => 'Md. Neamul Islam', 'nttn_billing_kam' => 'Nayeemur', 'iig_itc_billing_kam' => 'N/A'],
            548  => ['sm_kam' => 'Ishtiaq Uddin Ahmmed', 'team_name' => 'Team Noor'],
            553  => ['sm_kam' => 'Md.Noor Hossain', 'team_name' => 'Team Noor'],
            554  => ['nttn_billing_kam' => 'Nayeemur'],
            556  => ['nttn_billing_kam' => 'Ejaz'],
            584  => ['sm_kam' => 'Md.Noor Hossain', 'team_name' => 'Team Noor'],
            896  => ['sm_kam' => 'Roachi Shome', 'collection_kam' => 'Ehsanul', 'collection_supervisor' => 'CS Mithun', 'iig_itc_billing_kam' => 'Mahbub'],
            597  => ['sm_kam' => 'Ishtiaq Uddin Ahmmed', 'team_name' => 'Team Noor'],
            606  => ['iig_itc_billing_kam' => 'Mahbub'],
            615  => ['sm_kam' => 'Md.Noor Hossain', 'team_name' => 'Team Noor'],
            771  => ['collection_kam' => 'Mehedi', 'collection_supervisor' => 'CS Azim', 'nttn_billing_kam' => 'Nayeemur', 'iig_itc_billing_kam' => 'Monira'],
            637  => ['sm_kam' => 'Md. Taher Bin Hossain', 'team_name' => 'Team Noor'],
            643  => ['nttn_billing_kam' => 'Nayeemur'],
            648  => ['sm_kam' => 'Md.Noor Hossain', 'team_name' => 'Team Noor'],
            688  => ['nttn_billing_kam' => 'Nayeemur', 'iig_itc_billing_kam' => 'N/A'],
            672  => ['team_name' => 'Team Ridwan'],
            116  => ['team_name' => 'Team Israfil'],
            731  => ['team_name' => 'Team Rajib'],
            1173 => ['team_name' => 'Team Ridwan'],
            756  => ['team_name' => 'Team Sujon'],
            1187 => ['team_name' => 'Team Rajib'],
            669  => ['team_name' => 'Team Ridwan'],
            775  => ['team_name' => 'Team Ridwan'],
            783  => ['team_name' => 'Team Sujon'],
            1198 => ['team_name' => 'Team Rajib'],
            703  => ['team_name' => 'Team Ridwan'],
            657  => ['team_name' => 'Team Sujon'],
            780  => ['team_name' => 'Team Israfil'],
            1186 => ['team_name' => 'Team Ridwan'],
            781  => ['team_name' => 'Team Israfil'],
            713  => ['team_name' => 'Team Ridwan'],
            982  => ['team_name' => 'Team Sujon'],
            692  => ['team_name' => 'Team Rajib'],
            1199 => ['team_name' => 'Team Ridwan'],
            768  => ['team_name' => 'Team Rajib'],
            493  => ['team_name' => 'Team Sujon'],
            1193 => ['team_name' => 'Team Sujon'],
            1194 => ['team_name' => 'Team Rajib'],
            770  => ['team_name' => 'Team Sujon'],
            723  => ['team_name' => 'Team Ridwan'],
            664  => ['team_name' => 'Team Sujon'],
            805  => ['team_name' => 'Team Ridwan'],
            1021 => ['sm_kam' => 'Md. Shamrat Shahriar', 'nttn_billing_kam' => 'Nayeemur', 'iig_itc_billing_kam' => 'N/A'],
            830  => ['team_name' => 'Team Sujon'],
            835  => ['team_name' => 'Team Ridwan'],
            841  => ['team_name' => 'Team Ridwan'],
            324  => ['collection_kam' => 'Anik', 'collection_supervisor' => 'CS Tarikul'],
            328  => ['team_name' => 'Team Sujon'],
            849  => ['team_name' => 'Team Israfil'],
            436  => ['team_name' => 'Team Sujon'],
            440  => ['team_name' => 'Team Sujon'],
            921  => ['team_name' => 'Team Rajib'],
            496  => ['team_name' => 'Team Sujon'],
            944  => ['team_name' => 'Team Ridwan'],
            886  => ['team_name' => 'Team Ridwan'],
        ];

        $updatedCount = 0;
        foreach ($changes as $clientId => $data) {
            $client = Client::find($clientId);
            if ($client) {
                $client->update($data);
                $updatedCount++;
            }
        }

        // Re-run team matrix seeder to update all IDs and supervisors
        (new UpdateClientTeamMatrixSeeder())->run();
    }
}
