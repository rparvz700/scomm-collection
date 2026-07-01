<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MonthlySummarySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = [];
        $id = 1;

        foreach ($this->months() as $monthIndex => $month) {
            for ($clientId = 1; $clientId <= 500; $clientId++) {
                $mrc = $this->amount(18000, 145000, $clientId, $monthIndex, 7);
                $riskFactor = 0.75 + (($clientId * 13 + $monthIndex * 5) % 36) / 10;
                $openingOs = round($mrc * $riskFactor, 2);
                $collectionRatio = 0.35 + (($clientId * 17 + $monthIndex * 11) % 75) / 100;
                $collection = round($mrc * $collectionRatio, 2);
                $latestOs = max(round($openingOs + $mrc - $collection, 2), 0);
                $latestCr = $mrc > 0 ? round($latestOs / $mrc, 2) : 0;
                $openingCr = $mrc > 0 ? round($openingOs / $mrc, 2) : 0;
                $postpaidShare = (($clientId + $monthIndex) % 60 + 25) / 100;
                $prepaidShare = 1 - $postpaidShare;

                $row = [
                    'monthly_summary_id' => $id++,
                    'client_id' => $clientId,
                    'summary_month' => $month,
                    'total_opening_os' => $openingOs,
                    'total_mrc' => $mrc,
                    'total_maturity' => round($mrc * (0.85 + (($clientId + $monthIndex) % 25) / 100), 2),
                    'net_backlog_total' => max(round($latestOs - $mrc, 2), 0),
                    'target_maturity_commitment_postpaid' => round($mrc * $postpaidShare * 0.72, 2),
                    'target_maturity_commitment_prepaid' => round($mrc * $prepaidShare * 0.72, 2),
                    'target_additional_shortfall_from_maturity' => max(round(($mrc * 0.9) - $collection, 2), 0),
                    'maturity_commitment_total' => round($mrc * 0.72, 2),
                    'payment_plan_postpaid' => round($mrc * $postpaidShare * 0.35, 2),
                    'payment_plan_prepaid' => round($mrc * $prepaidShare * 0.35, 2),
                    'current_month_remarks' => $this->remark($latestCr),
                    'latest_os_balance_postpaid' => round($latestOs * $postpaidShare, 2),
                    'latest_os_balance_prepaid' => round($latestOs * $prepaidShare, 2),
                    'total_latest_os' => $latestOs,
                    'pdc' => $clientId % 9 === 0 ? round($mrc * 0.25, 2) : 0,
                    'udc' => $clientId % 13 === 0 ? round($mrc * 0.15, 2) : 0,
                    'expired_chq' => $clientId % 31 === 0 ? round($mrc * 0.08, 2) : 0,
                    'payment_plan_description' => $clientId % 4 === 0 ? 'Installment commitment under monitoring' : 'Regular monthly follow-up',
                    'client_payment_commitment_date' => Carbon::parse($month)->addDays(15)->toDateString(),
                    'nttn_tds_amount' => round($mrc * 0.02, 2),
                    'balance_after_recovery' => max(round($latestOs - $collection, 2), 0),
                    'total_collection' => $collection,
                    'opening_cr' => $openingCr,
                    'opening_rating_category' => $this->rating($openingCr),
                    'latest_cr' => $latestCr,
                    'latest_rating_category' => $this->rating($latestCr),
                    'created_at' => $now,
                ];

                $row = array_merge($row, $this->splitAmounts('opening_os', $openingOs, $postpaidShare));
                $row = array_merge($row, $this->splitAmounts('mrc', $mrc, $postpaidShare));
                $row = array_merge($row, $this->splitAmounts('maturity', $row['total_maturity'], $postpaidShare));
                $row = array_merge($row, $this->splitShortfalls($mrc, $collection, $postpaidShare));
                $row = array_merge($row, $this->splitAmounts('collection', $collection, $postpaidShare));

                $row['total_target_maturity_commitment'] = $row['target_maturity_commitment_postpaid'] + $row['target_maturity_commitment_prepaid'];
                $row['total_payment_plan'] = $row['payment_plan_postpaid'] + $row['payment_plan_prepaid'];
                $row['net_backlog_postpaid'] = round($row['net_backlog_total'] * $postpaidShare, 2);
                $row['net_backlog_prepaid'] = round($row['net_backlog_total'] * $prepaidShare, 2);

                $rows[] = $row;

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
        DB::table('monthly_summary')->upsert($rows, ['client_id', 'summary_month'], array_keys($rows[0]));
    }

    private function months(): array
    {
        $months = [];

        for ($i = 0; $i < 12; $i++) {
            $months[] = Carbon::create(2025, 5, 1)->addMonths($i)->endOfMonth()->toDateString();
        }

        return $months;
    }

    private function amount(int $min, int $max, int $clientId, int $monthIndex, int $salt): float
    {
        $range = $max - $min;
        $wave = (($clientId * 97 + $monthIndex * 53 + $salt * 29) % $range);

        return round($min + $wave + ($monthIndex * 750), 2);
    }

    private function splitAmounts(string $prefix, float $total, float $postpaidShare): array
    {
        $postpaid = round($total * $postpaidShare, 2);
        $prepaid = round($total - $postpaid, 2);
        $comboColumn = $prefix === 'opening_os' ? 'iig_nttn' : 'nttn_iig';

        return [
            "{$prefix}_postpaid_nttn" => round($postpaid * 0.28, 2),
            "{$prefix}_postpaid_{$comboColumn}" => round($postpaid * 0.18, 2),
            "{$prefix}_postpaid_iig" => round($postpaid * 0.22, 2),
            "{$prefix}_postpaid_itc" => round($postpaid * 0.17, 2),
            "{$prefix}_postpaid_nix" => round($postpaid * 0.15, 2),
            "{$prefix}_prepaid_nttn" => round($prepaid * 0.28, 2),
            "{$prefix}_prepaid_{$comboColumn}" => round($prepaid * 0.18, 2),
            "{$prefix}_prepaid_iig" => round($prepaid * 0.22, 2),
            "{$prefix}_prepaid_itc" => round($prepaid * 0.17, 2),
            "{$prefix}_prepaid_nix" => round($prepaid * 0.15, 2),
        ];
    }

    private function splitShortfalls(float $mrc, float $collection, float $postpaidShare): array
    {
        $targetShortfall = max(round(($mrc * 0.9) - $collection, 2), 0);
        $mrcShortfall = max(round($mrc - $collection, 2), 0);
        $maturityShortfall = max(round(($mrc * 0.85) - $collection, 2), 0);
        $paymentShortfall = max(round(($mrc * 0.35) - $collection, 2), 0);
        $prepaidShare = 1 - $postpaidShare;

        return [
            'shortfall_target_postpaid' => round($targetShortfall * $postpaidShare, 2),
            'shortfall_target_prepaid' => round($targetShortfall * $prepaidShare, 2),
            'total_shortfall_target' => $targetShortfall,
            'shortfall_mrc_postpaid' => round($mrcShortfall * $postpaidShare, 2),
            'shortfall_mrc_prepaid' => round($mrcShortfall * $prepaidShare, 2),
            'total_shortfall_mrc' => $mrcShortfall,
            'shortfall_maturity_postpaid' => round($maturityShortfall * $postpaidShare, 2),
            'shortfall_maturity_prepaid' => round($maturityShortfall * $prepaidShare, 2),
            'total_shortfall_maturity' => $maturityShortfall,
            'shortfall_payment_plan_postpaid' => round($paymentShortfall * $postpaidShare, 2),
            'shortfall_payment_plan_prepaid' => round($paymentShortfall * $prepaidShare, 2),
            'total_shortfall_payment_plan' => $paymentShortfall,
        ];
    }

    private function rating(float $cr): string
    {
        return match (true) {
            $cr <= 1.50 => 'Best',
            $cr <= 2.00 => 'Good',
            $cr <= 2.50 => 'Moderate',
            $cr <= 2.99 => 'Risky',
            $cr <= 3.49 => 'High Risky',
            default => 'Most Risky',
        };
    }

    private function remark(float $cr): string
    {
        return $cr >= 2.51 ? 'Recovery pressure required for high CR client' : 'Normal collection monitoring';
    }
}
