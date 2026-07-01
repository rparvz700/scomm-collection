<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
header('Content-Type: text/plain; charset=utf-8');

$month = '2025-11-30';
echo "=== Verified Summary for November 2025 ===\n\n";

// 1. monthly_summary
$msCount = DB::table('monthly_summary')->where('summary_month', $month)->count();
$msMrc = DB::table('monthly_summary')->where('summary_month', $month)->sum('total_mrc');
$msCol = DB::table('monthly_summary')->where('summary_month', $month)->sum('total_collection');
$msOs = DB::table('monthly_summary')->where('summary_month', $month)->sum('total_latest_os');

echo "monthly_summary (Active Matched):\n";
echo "  Rows: $msCount\n";
echo "  Total MRC: " . number_format($msMrc, 2) . "\n";
echo "  Total Collection: " . number_format($msCol, 2) . "\n";
echo "  Total Latest OS: " . number_format($msOs, 2) . "\n\n";

// 2. monthly_summary_discontinued
$msdCount = DB::table('monthly_summary_discontinued')->where('summary_month', $month)->count();
$msdOpeningOs = DB::table('monthly_summary_discontinued')->where('summary_month', $month)->sum('opening_os');
$msdCol = DB::table('monthly_summary_discontinued')->where('summary_month', $month)->sum('collection_amount');
$msdOs = DB::table('monthly_summary_discontinued')->where('summary_month', $month)->sum('latest_os');

echo "monthly_summary_discontinued (Discontinued Matched):\n";
echo "  Rows: $msdCount\n";
echo "  Total Opening OS: " . number_format($msdOpeningOs, 2) . "\n";
echo "  Total Collection: " . number_format($msdCol, 2) . "\n";
echo "  Total Latest OS: " . number_format($msdOs, 2) . "\n\n";

// 3. monthly_summary_residue
$msrCount = DB::table('monthly_summary_residue')->where('summary_month', $month)->count();
$msrCol = DB::table('monthly_summary_residue')->where('summary_month', $month)->sum('total_collection');
echo "monthly_summary_residue (Unmatched Active):\n";
echo "  Rows: $msrCount\n";
echo "  Total Collection: " . number_format($msrCol, 2) . "\n\n";

// 4. monthly_summary_discontinued_residue
$msdrCount = DB::table('monthly_summary_discontinued_residue')->where('summary_month', $month)->count();
$msdrCol = DB::table('monthly_summary_discontinued_residue')->where('summary_month', $month)->sum('collection_amount');
echo "monthly_summary_discontinued_residue (Unmatched Discontinued):\n";
echo "  Rows: $msdrCount\n";
echo "  Total Collection: " . number_format($msdrCol, 2) . "\n\n";

// 5. collection table
$colTotal = DB::table('collection')->where('collection_month', $month)->sum('collection_amount');
echo "collection table:\n";
echo "  Total Collection: " . number_format($colTotal, 2) . "\n";
