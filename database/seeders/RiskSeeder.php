<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RiskSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = [];
        $id = 1;
        $events = [
            'mrc_generated',
            'collection_received',
            'manual_adjustment',
            'monthly_reassessment',
            'system_recalculation',
        ];

        foreach ($this->months() as $monthIndex => $month) {
            for ($clientId = 1; $clientId <= 500; $clientId++) {
                $mrc = 18000 + (($clientId * 97 + $monthIndex * 53 + 203) % 127000) + ($monthIndex * 750);
                $riskFactor = 0.75 + (($clientId * 13 + $monthIndex * 5) % 36) / 10;
                $outstanding = round($mrc * $riskFactor, 2);
                $cr = $mrc > 0 ? round($outstanding / $mrc, 2) : 0;

                $rows[] = [
                    'risk_id' => $id++,
                    'client_id' => $clientId,
                    'risk_event_datetime' => Carbon::parse($month)->setTime(18, ($clientId + $monthIndex) % 60)->toDateTimeString(),
                    'event_type' => $events[($clientId + $monthIndex) % count($events)],
                    'outstanding_balance' => $outstanding,
                    'mrc_amount' => round($mrc, 2),
                    'cr_value' => $cr,
                    'rating_category' => $this->rating($cr),
                    'legal' => $clientId % 11 === 0,
                    'barring_priority' => $cr >= 2.51 || $clientId % 3 === 0 ? 'P1' : 'P2',
                    'remarks' => $cr >= 2.51 ? 'High CR event flagged for recovery action' : 'Routine monthly risk event',
                    'created_by' => 'system.seed',
                    'created_at' => $now,
                ];

                if (count($rows) >= 500) {
                    $this->upsert($rows);
                    $rows = [];
                }
            }
        }

        if ($rows) {
            $this->upsert($rows);
        }
    }

    private function upsert(array $rows): void
    {
        DB::table('risk')->upsert($rows, ['risk_id'], [
            'client_id',
            'risk_event_datetime',
            'event_type',
            'outstanding_balance',
            'mrc_amount',
            'cr_value',
            'rating_category',
            'legal',
            'barring_priority',
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

    private function rating(float $cr): string
    {
        return match (true) {
            $cr <= 1.50 => 'Low',
            $cr <= 2.00 => 'Watch',
            $cr <= 2.50 => 'Medium',
            $cr <= 2.99 => 'High',
            $cr <= 3.49 => 'Critical',
            default => 'Severe',
        };
    }
}
