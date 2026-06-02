<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CollectionSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = [];
        $id = 1;
        $types = [
            'postpaid_nttn',
            'postpaid_nttn_iig',
            'postpaid_iig',
            'postpaid_itc',
            'postpaid_nix',
            'prepaid_nttn',
            'prepaid_nttn_iig',
            'prepaid_iig',
            'prepaid_itc',
            'prepaid_nix',
        ];

        foreach ($this->months() as $monthIndex => $month) {
            $monthDate = Carbon::parse($month);

            for ($clientId = 1; $clientId <= 500; $clientId++) {
                $entryCount = 1 + (($clientId + $monthIndex) % 3);

                for ($entry = 0; $entry < $entryCount; $entry++) {
                    $amount = 6000 + (($clientId * 71 + $monthIndex * 43 + $entry * 19) % 95000);

                    $rows[] = [
                        'collection_id' => $id++,
                        'client_id' => $clientId,
                        'collection_datetime' => $monthDate->copy()
                            ->startOfMonth()
                            ->addDays(($clientId + $entry * 7) % 26)
                            ->setTime(9 + (($clientId + $entry) % 8), ($clientId * 3 + $entry * 11) % 60)
                            ->toDateTimeString(),
                        'collection_month' => $month,
                        'collection_type' => $types[($clientId + $monthIndex + $entry) % count($types)],
                        'collection_amount' => round($amount, 2),
                        'remarks' => $entry === 0 ? 'Regular monthly collection' : 'Additional partial collection',
                        'created_by' => 'system.seed',
                        'created_at' => $now,
                    ];

                    if (count($rows) >= 500) {
                        $this->upsert($rows);
                        $rows = [];
                    }
                }
            }
        }

        if ($rows) {
            $this->upsert($rows);
        }
    }

    private function upsert(array $rows): void
    {
        DB::table('collection')->upsert($rows, ['collection_id'], [
            'client_id',
            'collection_datetime',
            'collection_month',
            'collection_type',
            'collection_amount',
            'remarks',
            'created_by',
        ]);
    }

    private function months(): array
    {
        $months = [];

        for ($i = 0; $i < 12; $i++) {
            $months[] = Carbon::create(2025, 5, 1)->addMonths($i)->endOfMonth()->toDateString();
        }

        return $months;
    }
}
