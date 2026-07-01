<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $serviceTypes = ['IIG', 'ISP', 'NTTN', 'ITC', 'NIX', 'Cable Operator', 'Broadband Operator', 'Corporate Operator'];
        $statuses = ['Active', 'Active', 'Active', 'Watchlist', 'Barring Proposed', 'Already Barred', 'Suspended'];
        $agreementStatuses = ['Active', 'Expired', 'Pending Renewal', 'Under Negotiation'];
        $teams = ['Enterprise North', 'Enterprise South', 'Enterprise East', 'Strategic Accounts', 'Recovery Desk'];
        $collectionKams = ['Nusrat Jahan', 'Farhana Rahman', 'Sabbir Khan', 'Mou Akter', 'Tanvir Hasan'];
        $supervisors = ['Tariq Hasan', 'Maliha Akter', 'Samia Chowdhury'];
        $smKams = ['Rahim Ahmed', 'Mehedi Hasan', 'Arif Chowdhury', 'Tasnim Karim', 'Sadia Islam'];

        $rows = [];

        for ($i = 1; $i <= 500; $i++) {
            $serviceType = $serviceTypes[$i % count($serviceTypes)];

            $rows[] = [
                'client_id' => $i,
                'opus_id' => sprintf('OPUS-%05d', $i),
                'client_name' => sprintf('%s %03d', $this->companyPrefix($i), $i),
                'client_status' => $statuses[$i % count($statuses)],
                'agreement_status' => $agreementStatuses[$i % count($agreementStatuses)],
                'barring_priority' => $i % 3 === 0 ? 'P1' : 'P2',
                'btrc_license_discontinuation_date' => $i % 37 === 0 ? '2026-04-30' : null,
                'legal' => $i % 11 === 0,
                'service_discontinuation_date' => $i % 41 === 0 ? '2026-04-30' : null,
                'billing_modality_kpi' => $i % 4 === 0 ? 'Hybrid' : ($i % 2 === 0 ? 'Prepaid' : 'Postpaid'),
                'service_type_billing' => $serviceType,
                'license_billing' => $serviceType,
                'btrc_letter' => $i % 29 === 0 ? sprintf('BTRC/NOTICE/%04d', $i) : null,
                'security_coverage' => ['Covered', 'Partial', 'Uncovered'][$i % 3],
                'payment_plan' => ['Monthly settlement', 'Advance payment', 'Installment plan', 'Recovery commitment'][$i % 4],
                'other_upstream' => $i % 5 === 0,
                'sm_kam' => $smKams[$i % count($smKams)],
                'team_name' => $teams[$i % count($teams)],
                'collection_kam' => $collectionKams[$i % count($collectionKams)],
                'collection_supervisor' => $supervisors[$i % count($supervisors)],
                'nttn_billing_kam' => 'NTTN Billing KAM ' . (($i % 5) + 1),
                'iig_itc_billing_kam' => 'IIG ITC Billing KAM ' . (($i % 5) + 1),
                'nttn_billing_commencement_date' => '2024-01-01',
                'iig_itc_billing_commencement_date' => '2024-01-15',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('client')->upsert($chunk, ['opus_id'], [
                'client_name',
                'client_status',
                'agreement_status',
                'barring_priority',
                'btrc_license_discontinuation_date',
                'legal',
                'service_discontinuation_date',
                'billing_modality_kpi',
                'service_type_billing',
                'license_billing',
                'btrc_letter',
                'security_coverage',
                'payment_plan',
                'other_upstream',
                'sm_kam',
                'team_name',
                'collection_kam',
                'collection_supervisor',
                'nttn_billing_kam',
                'iig_itc_billing_kam',
                'nttn_billing_commencement_date',
                'iig_itc_billing_commencement_date',
                'updated_at',
            ]);
        }
    }

    private function companyPrefix(int $index): string
    {
        return [
            'Alpha Net',
            'Beacon Broadband',
            'City Link',
            'Delta Online',
            'Eastern Fiber',
            'Frontier Connect',
            'Global Transit',
            'Horizon Data',
            'Metro Wave',
            'Orbit Telecom',
        ][$index % 10];
    }
}
