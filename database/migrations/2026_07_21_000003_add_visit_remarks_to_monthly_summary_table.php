<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_summary', function (Blueprint $table) {
            if (!Schema::hasColumn('monthly_summary', 'visit_remarks')) {
                $table->text('visit_remarks')->nullable()->after('payment_plan_description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('monthly_summary', function (Blueprint $table) {
            if (Schema::hasColumn('monthly_summary', 'visit_remarks')) {
                $table->dropColumn('visit_remarks');
            }
        });
    }
};
