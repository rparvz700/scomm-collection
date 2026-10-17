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
        Schema::table('waivers', function (Blueprint $table) {
            if (!Schema::hasColumn('waivers', 'subtract_from_next_month_os')) {
                $table->boolean('subtract_from_next_month_os')->default(false)->after('waiver_amount');
            }
            if (!Schema::hasColumn('waivers', 'breakdown_config')) {
                $table->json('breakdown_config')->nullable()->after('subtract_from_next_month_os');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waivers', function (Blueprint $table) {
            if (Schema::hasColumn('waivers', 'breakdown_config')) {
                $table->dropColumn('breakdown_config');
            }
            if (Schema::hasColumn('waivers', 'subtract_from_next_month_os')) {
                $table->dropColumn('subtract_from_next_month_os');
            }
        });
    }
};
