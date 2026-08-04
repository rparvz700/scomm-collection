<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Collection;
use App\Models\MonthlySummary;
use App\Models\MonthlySummaryDiscontinued;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonthRolloverController extends Controller
{
    public function rollover(Request $request): JsonResponse
    {
        $targetParam = $request->query('target_month');
        $force = $request->boolean('force');

        if ($targetParam) {
            $targetCarbon = Carbon::parse($targetParam)->endOfMonth();
        } else {
            $latestActiveMonth = MonthlySummary::max('summary_month');
            $latestDiscMonth = MonthlySummaryDiscontinued::max('summary_month');
            $maxLatest = max($latestActiveMonth, $latestDiscMonth);

            if ($maxLatest) {
                $targetCarbon = Carbon::parse($maxLatest)->startOfMonth()->addMonth()->endOfMonth();
            } else {
                $targetCarbon = Carbon::now()->endOfMonth();
            }
        }

        $targetMonthDate = $targetCarbon->toDateString();
        $sourceMonthDate = $targetCarbon->copy()->startOfMonth()->subDays(1)->endOfMonth()->toDateString();

        // Check existing target records for idempotency
        $existingActiveClientIds = MonthlySummary::whereDate('summary_month', $targetMonthDate)
            ->pluck('client_id')
            ->flip();

        $existingDiscClientIds = MonthlySummaryDiscontinued::whereDate('summary_month', $targetMonthDate)
            ->pluck('client_id')
            ->flip();

        // Pre-fetch source summaries indexed by client_id
        $sourceActiveSummaries = MonthlySummary::whereDate('summary_month', $sourceMonthDate)
            ->get()
            ->keyBy('client_id');

        $sourceDiscSummaries = MonthlySummaryDiscontinued::whereDate('summary_month', $sourceMonthDate)
            ->get()
            ->keyBy('client_id');

        $activeCreated = 0;
        $activeSkipped = 0;
        $discontinuedCreated = 0;
        $discontinuedSkipped = 0;

        $activeRowsToInsert = [];
        $discRowsToInsert = [];
        $now = Carbon::now()->toDateTimeString();

        // 1. Process Active Clients
        $activeClients = Client::where('client_status', 'Active')->get();

        foreach ($activeClients as $client) {
            if (isset($existingActiveClientIds[$client->client_id]) && !$force) {
                $activeSkipped++;
                continue;
            }

            $sourceSummary = $sourceActiveSummaries->get($client->client_id);

            // Roll over MRC components
            $mrcComponents = [
                'mrc_postpaid_nttn', 'mrc_postpaid_nttn_iig', 'mrc_postpaid_iig', 'mrc_postpaid_itc', 'mrc_postpaid_nix',
                'mrc_prepaid_nttn', 'mrc_prepaid_nttn_iig', 'mrc_prepaid_iig', 'mrc_prepaid_itc', 'mrc_prepaid_nix'
            ];
            $mrcData = [];
            foreach ($mrcComponents as $col) {
                $mrcData[$col] = ($sourceSummary && isset($sourceSummary->$col)) ? (float) $sourceSummary->$col : 0.00;
            }

            // Roll over Backlog components from prev month's Latest OS components
            $backlogData = [];
            $components = ['postpaid_nttn', 'postpaid_iig_nttn', 'postpaid_iig', 'postpaid_itc', 'postpaid_nix', 'prepaid_nttn', 'prepaid_iig_nttn', 'prepaid_iig', 'prepaid_itc', 'prepaid_nix'];
            foreach ($components as $comp) {
                $latestOsCol = 'latest_os_' . $comp;
                $backlogCol = 'backlog_' . $comp;
                if ($sourceSummary && isset($sourceSummary->$latestOsCol)) {
                    $backlogData[$backlogCol] = (float) $sourceSummary->$latestOsCol;
                } else {
                    // fallback
                    $openingCol = 'opening_os_' . $comp;
                    $mrcCol = 'mrc_' . $comp;
                    $prevOpening = ($sourceSummary && isset($sourceSummary->$openingCol)) ? (float) $sourceSummary->$openingCol : 0.00;
                    $prevMrc = ($sourceSummary && isset($sourceSummary->$mrcCol)) ? (float) $sourceSummary->$mrcCol : 0.00;
                    $backlogData[$backlogCol] = max(0.00, $prevOpening - $prevMrc);
                }
            }

            // Calculate Opening OS components = mrc + backlog
            $openingOsData = [];
            foreach ($components as $comp) {
                $openingCol = 'opening_os_' . $comp;
                $mrcCol = 'mrc_' . $comp;
                $backlogCol = 'backlog_' . $comp;
                $openingOsData[$openingCol] = $mrcData[$mrcCol] + $backlogData[$backlogCol];
            }

            // Sum totals
            $totalMrcPostpaid = $mrcData['mrc_postpaid_nttn'] + $mrcData['mrc_postpaid_nttn_iig'] + $mrcData['mrc_postpaid_iig'] + $mrcData['mrc_postpaid_itc'] + $mrcData['mrc_postpaid_nix'];
            $totalMrcPrepaid = $mrcData['mrc_prepaid_nttn'] + $mrcData['mrc_prepaid_nttn_iig'] + $mrcData['mrc_prepaid_iig'] + $mrcData['mrc_prepaid_itc'] + $mrcData['mrc_prepaid_nix'];
            $totalMrc = $totalMrcPostpaid + $totalMrcPrepaid;

            $totalOpeningOsPostpaid = $openingOsData['opening_os_postpaid_nttn'] + $openingOsData['opening_os_postpaid_iig_nttn'] + $openingOsData['opening_os_postpaid_iig'] + $openingOsData['opening_os_postpaid_itc'] + $openingOsData['opening_os_postpaid_nix'];
            $totalOpeningOsPrepaid = $openingOsData['opening_os_prepaid_nttn'] + $openingOsData['opening_os_prepaid_iig_nttn'] + $openingOsData['opening_os_prepaid_iig'] + $openingOsData['opening_os_prepaid_itc'] + $openingOsData['opening_os_prepaid_nix'];
            $totalOpeningOs = $totalOpeningOsPostpaid + $totalOpeningOsPrepaid;

            $netBacklogPostpaid = $backlogData['backlog_postpaid_nttn'] + $backlogData['backlog_postpaid_iig_nttn'] + $backlogData['backlog_postpaid_iig'] + $backlogData['backlog_postpaid_itc'] + $backlogData['backlog_postpaid_nix'];
            $netBacklogPrepaid = $backlogData['backlog_prepaid_nttn'] + $backlogData['backlog_prepaid_iig_nttn'] + $backlogData['backlog_prepaid_iig'] + $backlogData['backlog_prepaid_itc'] + $backlogData['backlog_prepaid_nix'];
            $netBacklogTotal = $netBacklogPostpaid + $netBacklogPrepaid;

            // Latest OS components (initial = opening os, collection = 0)
            $latestOsData = [];
            foreach ($components as $comp) {
                $latestCol = 'latest_os_' . $comp;
                $openingCol = 'opening_os_' . $comp;
                $latestOsData[$latestCol] = $openingOsData[$openingCol];
            }
            $latestOsBalancePostpaid = $totalOpeningOsPostpaid;
            $latestOsBalancePrepaid = $totalOpeningOsPrepaid;
            $totalLatestOs = $totalOpeningOs;

            $openingCr = $totalMrc > 0 ? round($totalOpeningOs / $totalMrc, 2) : 0.00;
            $openingRating = Collection::getRatingCategory($openingCr);
            $latestCr = $openingCr;
            $latestRating = $openingRating;

            $data = array_merge([
                'client_id' => $client->client_id,
                'summary_month' => $targetMonthDate,
                'total_opening_os' => $totalOpeningOs,
                'total_opening_os_postpaid' => $totalOpeningOsPostpaid,
                'total_opening_os_prepaid' => $totalOpeningOsPrepaid,
                'total_mrc' => $totalMrc,
                'total_mrc_postpaid' => $totalMrcPostpaid,
                'total_mrc_prepaid' => $totalMrcPrepaid,
                'total_maturity' => $totalMrc,
                'net_backlog_postpaid' => $netBacklogPostpaid,
                'net_backlog_prepaid' => $netBacklogPrepaid,
                'net_backlog_total' => $netBacklogTotal,
                'opening_cr' => $openingCr,
                'opening_rating_category' => $openingRating,
                'total_collection' => 0.00,
                'collection_mrc' => 0.00,
                'collection_backlog' => 0.00,
                'mrc_shortfall' => $totalMrc,
                'backlog_shortfall' => $netBacklogTotal,
                'total_shortfall_maturity' => $totalMrc,
                'latest_os_balance_postpaid' => $latestOsBalancePostpaid,
                'latest_os_balance_prepaid' => $latestOsBalancePrepaid,
                'total_latest_os' => $totalLatestOs,
                'latest_cr' => $latestCr,
                'latest_rating_category' => $latestRating,
                'barring_percentage' => (float) ($client->barring_percentage ?? 0.00),
                'created_at' => $now,
            ], $mrcData, $backlogData, $openingOsData, $latestOsData);

            $activeRowsToInsert[] = $data;
            $activeCreated++;
        }

        // 2. Process Discontinued & Barred Clients
        $nonActiveClients = Client::where('client_status', '!=', 'Active')->get();

        foreach ($nonActiveClients as $client) {
            if (isset($existingDiscClientIds[$client->client_id]) && !$force) {
                $discontinuedSkipped++;
                continue;
            }

            $sourceDisc = $sourceDiscSummaries->get($client->client_id);
            $sourceActive = $sourceActiveSummaries->get($client->client_id);

            $prevClosingOs = $sourceDisc ? (float) $sourceDisc->latest_os : ($sourceActive ? (float) $sourceActive->total_latest_os : 0.00);
            $openingOs = $prevClosingOs;
            $latestOs = $openingOs;

            $discRowsToInsert[] = [
                'client_id' => $client->client_id,
                'client_name' => $client->client_name,
                'client_status' => $client->client_status,
                'summary_month' => $targetMonthDate,
                'opening_os' => $openingOs,
                'collection_amount' => 0.00,
                'latest_os' => $latestOs,
                'mrc_shortfall' => 0.00,
                'backlog_shortfall' => $openingOs,
                'barring_percentage' => (float) ($client->barring_percentage ?? 0.00),
                'client_legal' => $client->legal ? 1 : 0,
                'visit_remarks' => $sourceDisc?->visit_remarks,
                'payment_plan_description' => $sourceDisc?->payment_plan_description,
                'created_at' => $now,
            ];

            $discontinuedCreated++;
        }

        DB::transaction(function () use ($activeRowsToInsert, $discRowsToInsert, $force, $targetMonthDate) {
            if (!empty($activeRowsToInsert)) {
                if ($force) {
                    MonthlySummary::whereDate('summary_month', $targetMonthDate)
                        ->whereIn('client_id', array_column($activeRowsToInsert, 'client_id'))
                        ->delete();
                }
                foreach (array_chunk($activeRowsToInsert, 200) as $chunk) {
                    MonthlySummary::insert($chunk);
                }
            }

            if (!empty($discRowsToInsert)) {
                if ($force) {
                    MonthlySummaryDiscontinued::whereDate('summary_month', $targetMonthDate)
                        ->whereIn('client_id', array_column($discRowsToInsert, 'client_id'))
                        ->delete();
                }
                foreach (array_chunk($discRowsToInsert, 200) as $chunk) {
                    MonthlySummaryDiscontinued::insert($chunk);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Month-opening data rollover completed for {$targetMonthDate}",
            'target_month' => $targetMonthDate,
            'source_month' => $sourceMonthDate,
            'active_created' => $activeCreated,
            'active_skipped' => $activeSkipped,
            'discontinued_created' => $discontinuedCreated,
            'discontinued_skipped' => $discontinuedSkipped,
        ]);
    }
}
