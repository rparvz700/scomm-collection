<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_summary', function (Blueprint $table) {
            // 1. Opening OS Totals
            $table->decimal('total_opening_os_postpaid', 18, 2)->default(0.00)->after('total_opening_os');
            $table->decimal('total_opening_os_prepaid', 18, 2)->default(0.00)->after('total_opening_os_postpaid');

            // 2. Backlog Sub-components
            $table->decimal('backlog_postpaid_nttn', 18, 2)->default(0.00)->after('net_backlog_total');
            $table->decimal('backlog_postpaid_iig_nttn', 18, 2)->default(0.00)->after('backlog_postpaid_nttn');
            $table->decimal('backlog_postpaid_iig', 18, 2)->default(0.00)->after('backlog_postpaid_iig_nttn');
            $table->decimal('backlog_postpaid_itc', 18, 2)->default(0.00)->after('backlog_postpaid_iig');
            $table->decimal('backlog_postpaid_nix', 18, 2)->default(0.00)->after('backlog_postpaid_itc');
            $table->decimal('backlog_prepaid_nttn', 18, 2)->default(0.00)->after('backlog_postpaid_nix');
            $table->decimal('backlog_prepaid_iig_nttn', 18, 2)->default(0.00)->after('backlog_prepaid_nttn');
            $table->decimal('backlog_prepaid_iig', 18, 2)->default(0.00)->after('backlog_prepaid_iig_nttn');
            $table->decimal('backlog_prepaid_itc', 18, 2)->default(0.00)->after('backlog_prepaid_iig');
            $table->decimal('backlog_prepaid_nix', 18, 2)->default(0.00)->after('backlog_prepaid_itc');

            // 3. MRC Totals
            $table->decimal('total_mrc_postpaid', 18, 2)->default(0.00)->after('total_mrc');
            $table->decimal('total_mrc_prepaid', 18, 2)->default(0.00)->after('total_mrc_postpaid');

            // 4. Latest OS Sub-components
            $table->decimal('latest_os_postpaid_nttn', 18, 2)->default(0.00)->after('total_latest_os');
            $table->decimal('latest_os_postpaid_iig_nttn', 18, 2)->default(0.00)->after('latest_os_postpaid_nttn');
            $table->decimal('latest_os_postpaid_iig', 18, 2)->default(0.00)->after('latest_os_postpaid_iig_nttn');
            $table->decimal('latest_os_postpaid_itc', 18, 2)->default(0.00)->after('latest_os_postpaid_iig');
            $table->decimal('latest_os_postpaid_nix', 18, 2)->default(0.00)->after('latest_os_postpaid_itc');
            $table->decimal('latest_os_prepaid_nttn', 18, 2)->default(0.00)->after('latest_os_postpaid_nix');
            $table->decimal('latest_os_prepaid_iig_nttn', 18, 2)->default(0.00)->after('latest_os_prepaid_nttn');
            $table->decimal('latest_os_prepaid_iig', 18, 2)->default(0.00)->after('latest_os_prepaid_iig_nttn');
            $table->decimal('latest_os_prepaid_itc', 18, 2)->default(0.00)->after('latest_os_prepaid_iig');
            $table->decimal('latest_os_prepaid_nix', 18, 2)->default(0.00)->after('latest_os_prepaid_itc');
        });

        // Run data backfill updates
        DB::statement("UPDATE monthly_summary SET
            total_opening_os_postpaid = (opening_os_postpaid_nttn + opening_os_postpaid_iig_nttn + opening_os_postpaid_iig + opening_os_postpaid_itc + opening_os_postpaid_nix),
            total_opening_os_prepaid = (opening_os_prepaid_nttn + opening_os_prepaid_iig_nttn + opening_os_prepaid_iig + opening_os_prepaid_itc + opening_os_prepaid_nix),
            total_mrc_postpaid = (mrc_postpaid_nttn + mrc_postpaid_nttn_iig + mrc_postpaid_iig + mrc_postpaid_itc + mrc_postpaid_nix),
            total_mrc_prepaid = (mrc_prepaid_nttn + mrc_prepaid_nttn_iig + mrc_prepaid_iig + mrc_prepaid_itc + mrc_prepaid_nix),

            backlog_postpaid_nttn = (opening_os_postpaid_nttn - mrc_postpaid_nttn),
            backlog_postpaid_iig_nttn = (opening_os_postpaid_iig_nttn - mrc_postpaid_nttn_iig),
            backlog_postpaid_iig = (opening_os_postpaid_iig - mrc_postpaid_iig),
            backlog_postpaid_itc = (opening_os_postpaid_itc - mrc_postpaid_itc),
            backlog_postpaid_nix = (opening_os_postpaid_nix - mrc_postpaid_nix),
            backlog_prepaid_nttn = (opening_os_prepaid_nttn - mrc_prepaid_nttn),
            backlog_prepaid_iig_nttn = (opening_os_prepaid_iig_nttn - mrc_prepaid_nttn_iig),
            backlog_prepaid_iig = (opening_os_prepaid_iig - mrc_prepaid_iig),
            backlog_prepaid_itc = (opening_os_prepaid_itc - mrc_prepaid_itc),
            backlog_prepaid_nix = (opening_os_prepaid_nix - mrc_prepaid_nix),

            latest_os_postpaid_nttn = (opening_os_postpaid_nttn - collection_postpaid_nttn),
            latest_os_postpaid_iig_nttn = (opening_os_postpaid_iig_nttn - collection_postpaid_nttn_iig),
            latest_os_postpaid_iig = (opening_os_postpaid_iig - collection_postpaid_iig),
            latest_os_postpaid_itc = (opening_os_postpaid_itc - collection_postpaid_itc),
            latest_os_postpaid_nix = (opening_os_postpaid_nix - collection_postpaid_nix),
            latest_os_prepaid_nttn = (opening_os_prepaid_nttn - collection_prepaid_nttn),
            latest_os_prepaid_iig_nttn = (opening_os_prepaid_iig_nttn - collection_prepaid_nttn_iig),
            latest_os_prepaid_iig = (opening_os_prepaid_iig - collection_prepaid_iig),
            latest_os_prepaid_itc = (opening_os_prepaid_itc - collection_prepaid_itc),
            latest_os_prepaid_nix = (opening_os_prepaid_nix - collection_prepaid_nix)
        ");
    }

    public function down(): void
    {
        Schema::table('monthly_summary', function (Blueprint $table) {
            $table->dropColumn([
                'total_opening_os_postpaid', 'total_opening_os_prepaid',
                'backlog_postpaid_nttn', 'backlog_postpaid_iig_nttn', 'backlog_postpaid_iig', 'backlog_postpaid_itc', 'backlog_postpaid_nix',
                'backlog_prepaid_nttn', 'backlog_prepaid_iig_nttn', 'backlog_prepaid_iig', 'backlog_prepaid_itc', 'backlog_prepaid_nix',
                'total_mrc_postpaid', 'total_mrc_prepaid',
                'latest_os_postpaid_nttn', 'latest_os_postpaid_iig_nttn', 'latest_os_postpaid_iig', 'latest_os_postpaid_itc', 'latest_os_postpaid_nix',
                'latest_os_prepaid_nttn', 'latest_os_prepaid_iig_nttn', 'latest_os_prepaid_iig', 'latest_os_prepaid_itc', 'latest_os_prepaid_nix'
            ]);
        });
    }
};
