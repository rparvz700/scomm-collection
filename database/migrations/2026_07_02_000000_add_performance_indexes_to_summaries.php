<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('monthly_summary', function (Blueprint $table) {
            $table->index('summary_month', 'idx_monthly_summary_month');
        });

        Schema::table('monthly_summary_discontinued', function (Blueprint $table) {
            $table->index('summary_month', 'idx_monthly_summary_disc_month');
        });

        Schema::table('collection', function (Blueprint $table) {
            $table->index('collection_month', 'idx_collection_month');
            $table->index('collection_datetime', 'idx_collection_datetime');
        });

        Schema::table('risk', function (Blueprint $table) {
            $table->index('risk_event_datetime', 'idx_risk_event_datetime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_summary', function (Blueprint $table) {
            $table->dropIndex('idx_monthly_summary_month');
        });

        Schema::table('monthly_summary_discontinued', function (Blueprint $table) {
            $table->dropIndex('idx_monthly_summary_disc_month');
        });

        Schema::table('collection', function (Blueprint $table) {
            $table->dropIndex('idx_collection_month');
            $table->dropIndex('idx_collection_datetime');
        });

        Schema::table('risk', function (Blueprint $table) {
            $table->dropIndex('idx_risk_event_datetime');
        });
    }
};
