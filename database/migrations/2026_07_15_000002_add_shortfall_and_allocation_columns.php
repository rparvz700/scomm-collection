<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_summary', function (Blueprint $table) {
            if (!Schema::hasColumn('monthly_summary', 'collection_mrc')) {
                $table->decimal('collection_mrc', 18, 2)->default(0.00)->after('barring_percentage');
            }
            if (!Schema::hasColumn('monthly_summary', 'collection_backlog')) {
                $table->decimal('collection_backlog', 18, 2)->default(0.00)->after('collection_mrc');
            }
            if (!Schema::hasColumn('monthly_summary', 'mrc_shortfall')) {
                $table->decimal('mrc_shortfall', 18, 2)->default(0.00)->after('collection_backlog');
            }
            if (!Schema::hasColumn('monthly_summary', 'backlog_shortfall')) {
                $table->decimal('backlog_shortfall', 18, 2)->default(0.00)->after('mrc_shortfall');
            }
        });

        Schema::table('monthly_summary_discontinued', function (Blueprint $table) {
            if (!Schema::hasColumn('monthly_summary_discontinued', 'collection_mrc')) {
                $table->decimal('collection_mrc', 18, 2)->default(0.00)->after('barring_percentage');
            }
            if (!Schema::hasColumn('monthly_summary_discontinued', 'collection_backlog')) {
                $table->decimal('collection_backlog', 18, 2)->default(0.00)->after('collection_mrc');
            }
            if (!Schema::hasColumn('monthly_summary_discontinued', 'mrc_shortfall')) {
                $table->decimal('mrc_shortfall', 18, 2)->default(0.00)->after('collection_backlog');
            }
            if (!Schema::hasColumn('monthly_summary_discontinued', 'backlog_shortfall')) {
                $table->decimal('backlog_shortfall', 18, 2)->default(0.00)->after('mrc_shortfall');
            }
        });
    }

    public function down(): void
    {
        Schema::table('monthly_summary', function (Blueprint $table) {
            $table->dropColumn(['collection_mrc', 'collection_backlog', 'mrc_shortfall', 'backlog_shortfall']);
        });

        Schema::table('monthly_summary_discontinued', function (Blueprint $table) {
            $table->dropColumn(['collection_mrc', 'collection_backlog', 'mrc_shortfall', 'backlog_shortfall']);
        });
    }
};
