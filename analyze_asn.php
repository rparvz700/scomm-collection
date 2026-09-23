<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Client;
use App\Models\MonthlySummary;
use App\Models\MonthlySummaryDiscontinued;
use App\Models\ClientGrowthTrend;

$client = Client::where('client_name', 'LIKE', '%A.S.N%')->first();

if (!$client) {
    echo "ERROR: Client 'A.S.N Internet Service Provider' not found in database.\n";
    exit;
}

$growthTrend = ClientGrowthTrend::where('client_id', $client->client_id)->first();
$activeSummaries = MonthlySummary::where('client_id', $client->client_id)->orderBy('summary_month', 'asc')->get();
$discSummaries = MonthlySummaryDiscontinued::where('client_id', $client->client_id)->orderBy('summary_month', 'asc')->get();

$report = [];
$report[] = "================================================================================";
$report[] = "AD-HOC TEMPORARY ANALYSIS REPORT: A.S.N Internet Service Provider";
$report[] = "================================================================================";
$report[] = "Generated at: " . date('Y-m-d H:i:s');
$report[] = "";
$report[] = "--- 1. MASTER PROFILE & OPERATIONAL STATUS ---";
$report[] = "Client Name      : " . $client->client_name;
$report[] = "OPUS ID          : " . $client->opus_id;
$report[] = "Current Status   : " . ($client->client_status ?? 'N/A');
$report[] = "License Billing  : " . ($client->license_billing ?? 'N/A');
$report[] = "Team Name        : " . ($client->team_name ?? 'N/A');
$report[] = "Collection KAM   : " . ($client->collection_kam ?? 'N/A');
$report[] = "SM KAM           : " . ($client->sm_kam ?? 'N/A');
$report[] = "Discontinued Date: " . ($client->service_discontinuation_date ?? 'N/A');
$report[] = "Legal Action     : " . ($client->legal ? 'Yes' : 'No');
$report[] = "";

$report[] = "--- 2. PRE-COMPUTED 12-MONTH GROWTH TREND ---";
if ($growthTrend) {
    $report[] = "Trend Status     : " . strtoupper($growthTrend->trend_status);
    $report[] = "Latest MRC       : ৳" . number_format($growthTrend->mrc_latest, 2);
    $report[] = "12M Baseline MRC : ৳" . number_format($growthTrend->mrc_baseline_12m, 2);
    $report[] = "MRC Change %     : " . ($growthTrend->mrc_change_pct >= 0 ? '+' : '') . number_format($growthTrend->mrc_change_pct, 2) . "%";
    $report[] = "Latest CR        : " . number_format($growthTrend->cr_latest, 2);
    $report[] = "12M Baseline CR  : " . number_format($growthTrend->cr_baseline_12m, 2);
    $report[] = "CR Change Val    : " . ($growthTrend->cr_change_val >= 0 ? '+' : '') . number_format($growthTrend->cr_change_val, 2);
    $report[] = "Calculated At    : " . $growthTrend->calculated_at;
} else {
    $report[] = "Trend Status     : No trend record generated yet in client_growth_trends table.";
}
$report[] = "";

$report[] = "--- 3. ACTIVE MONTHLY SUMMARIES (" . $activeSummaries->count() . " months) ---";
if ($activeSummaries->isNotEmpty()) {
    foreach ($activeSummaries as $s) {
        $month = \Carbon\Carbon::parse($s->summary_month)->format('Y-m');
        $mrc = number_format((float)$s->total_mrc, 2);
        $coll = number_format((float)$s->collection_mrc, 2);
        $cr = number_format((float)$s->latest_cr, 2);
        $os = number_format((float)$s->total_latest_os, 2);
        $report[] = " Month: $month | MRC: ৳$mrc | Coll: ৳$coll | CR: $cr | Total OS: ৳$os";
    }
} else {
    $report[] = " No active summary records found.";
}
$report[] = "";

$report[] = "--- 4. DISCONTINUED MONTHLY SUMMARIES (" . $discSummaries->count() . " months) ---";
if ($discSummaries->isNotEmpty()) {
    foreach ($discSummaries as $s) {
        $month = \Carbon\Carbon::parse($s->summary_month)->format('Y-m');
        $coll = number_format((float)$s->collection_amount, 2);
        $opOs = number_format((float)$s->opening_os, 2);
        $clOs = number_format((float)$s->closing_os, 2);
        $cr = number_format((float)$s->latest_cr, 2);
        $report[] = " Month: $month | Coll: ৳$coll | Opening OS: ৳$opOs | Closing OS: ৳$clOs | CR: $cr";
    }
} else {
    $report[] = " No discontinued summary records found.";
}
$report[] = "================================================================================";

$reportText = implode("\n", $report);
echo $reportText . "\n";

file_put_contents(__DIR__ . '/asn_analysis_output.json', json_encode([
    'client' => $client,
    'growth_trend' => $growthTrend,
    'active_summaries' => $activeSummaries,
    'discontinued_summaries' => $discSummaries,
], JSON_PRETTY_PRINT));
