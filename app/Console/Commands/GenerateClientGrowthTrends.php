<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\ClientGrowthTrend;
use App\Models\MonthlySummary;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateClientGrowthTrends extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clients:generate-growth-trends';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pre-compute 12-month customer health trajectories (improving, declining, stable) and save to client_growth_trends table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting 12-month client growth trend calculation...');

        $maxMonthInDb = MonthlySummary::max('summary_month');
        if (!$maxMonthInDb) {
            $this->warn('No monthly summaries found in the database.');
            return Command::SUCCESS;
        }

        // 1. Get previous month before max month as latest date
        $latestMonthDate = Carbon::parse($maxMonthInDb)->subMonth()->startOfMonth();

        // 2. Subtract 12 months from latest date to get ideal baseline date
        $idealBaselineDate = $latestMonthDate->copy()->subMonths(12)->startOfMonth();

        // 3. Fallback: Oldest month in active_summary table
        $oldestMonthInDb = MonthlySummary::min('summary_month');
        $oldestMonthDate = $oldestMonthInDb ? Carbon::parse($oldestMonthInDb)->startOfMonth() : $idealBaselineDate;

        // Fetch historical summaries within window (oldest up to latest month)
        $allHistoricalSummaries = MonthlySummary::whereDate('summary_month', '>=', $oldestMonthDate->format('Y-m-d'))
            ->whereDate('summary_month', '<=', $latestMonthDate->endOfMonth()->format('Y-m-d'))
            ->orderBy('summary_month', 'asc')
            ->get()
            ->groupBy('client_id');

        $clients = Client::all();
        $now = now();
        $upsertData = [];

        foreach ($clients as $client) {
            $clientId = $client->client_id;
            $clientSummaries = $allHistoricalSummaries->get($clientId) ?? collect();

            if ($clientSummaries->isEmpty()) {
                $upsertData[] = [
                    'client_id' => $clientId,
                    'trend_status' => 'stable',
                    'mrc_latest' => 0.00,
                    'mrc_baseline_12m' => 0.00,
                    'mrc_change_pct' => 0.00,
                    'cr_latest' => 0.00,
                    'cr_baseline_12m' => 0.00,
                    'cr_change_val' => 0.00,
                    'calculated_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                continue;
            }

            // Latest summary (previous month before max month in DB)
            $latestSummary = $clientSummaries->where('summary_month', '<=', $latestMonthDate->endOfMonth()->format('Y-m-d'))->last() ?? $clientSummaries->last();

            // Baseline summary (-12 months from latest date)
            $baselineSummary = $clientSummaries->where('summary_month', '<=', $idealBaselineDate->endOfMonth()->format('Y-m-d'))->last();

            // If baseline date comes empty, take oldest month available for this client in the table
            if (!$baselineSummary) {
                $baselineSummary = $clientSummaries->first();
            }

            $mrcBaseline = (float) ($baselineSummary->total_mrc ?? 0.0);
            $mrcLatest = (float) ($latestSummary->total_mrc ?? 0.0);

            $crBaseline = (float) ($baselineSummary->latest_cr ?? 0.0);
            $crLatest = (float) ($latestSummary->latest_cr ?? 0.0);

            // Calculate percentage change in MRC
            if ($mrcBaseline > 0) {
                $mrcChangePct = round((($mrcLatest - $mrcBaseline) / $mrcBaseline) * 100, 2);
            } else {
                $mrcChangePct = $mrcLatest > 0 ? 100.0 : 0.0;
            }

            // Calculate absolute change in CR
            $crChangeVal = round($crLatest - $crBaseline, 2);

            // Thresholds: MRC 10%, CR 0.50
            // Declining: MRC <= -10% OR CR >= +0.50 OR CR crossed into Risky >= 2.51
            // Improving: MRC >= +10% OR CR <= -0.50 OR CR dropped below 2.50
            $isDeclining = ($mrcChangePct <= -10.0) || ($crChangeVal >= 0.50) || ($crLatest >= 2.51 && $crBaseline < 2.51);
            $isImproving = ($mrcChangePct >= 10.0) || ($crChangeVal <= -0.50) || ($crLatest < 2.51 && $crBaseline >= 2.51);

            if ($isDeclining && !$isImproving) {
                $trendStatus = 'declining';
            } elseif ($isImproving && !$isDeclining) {
                $trendStatus = 'improving';
            } elseif ($isDeclining && $isImproving) {
                // If both criteria met, priority goes to credit risk deterioration
                $trendStatus = $crChangeVal > 0 ? 'declining' : 'improving';
            } else {
                $trendStatus = 'stable';
            }

            $upsertData[] = [
                'client_id' => $clientId,
                'trend_status' => $trendStatus,
                'mrc_latest' => $mrcLatest,
                'mrc_baseline_12m' => $mrcBaseline,
                'mrc_change_pct' => $mrcChangePct,
                'cr_latest' => $crLatest,
                'cr_baseline_12m' => $crBaseline,
                'cr_change_val' => $crChangeVal,
                'calculated_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Perform bulk upsert in chunks of 100
        foreach (array_chunk($upsertData, 100) as $chunk) {
            DB::table('client_growth_trends')->upsert(
                $chunk,
                ['client_id'],
                [
                    'trend_status',
                    'mrc_latest',
                    'mrc_baseline_12m',
                    'mrc_change_pct',
                    'cr_latest',
                    'cr_baseline_12m',
                    'cr_change_val',
                    'calculated_at',
                    'updated_at',
                ]
            );
        }

        $this->info('Client growth trends generated successfully for ' . count($upsertData) . ' clients.');
        return Command::SUCCESS;
    }
}
