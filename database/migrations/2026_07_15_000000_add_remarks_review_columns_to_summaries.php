<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_summary', function (Blueprint $table) {
            if (!Schema::hasColumn('monthly_summary', 'sales_review_status')) {
                $table->string('sales_review_status', 50)->default('Pending')->after('current_month_remarks');
            }
            if (!Schema::hasColumn('monthly_summary', 'sales_review_remarks')) {
                $table->text('sales_review_remarks')->nullable()->after('sales_review_status');
            }
            if (!Schema::hasColumn('monthly_summary', 'barring_percentage')) {
                $table->decimal('barring_percentage', 5, 2)->default(0.00)->after('sales_review_remarks');
            }
        });

        Schema::table('monthly_summary_discontinued', function (Blueprint $table) {
            if (!Schema::hasColumn('monthly_summary_discontinued', 'visit_remarks')) {
                $table->text('visit_remarks')->nullable()->after('payment_plan_description');
            }
            if (!Schema::hasColumn('monthly_summary_discontinued', 'sales_review_status')) {
                $table->string('sales_review_status', 50)->default('Pending')->after('visit_remarks');
            }
            if (!Schema::hasColumn('monthly_summary_discontinued', 'sales_review_remarks')) {
                $table->text('sales_review_remarks')->nullable()->after('sales_review_status');
            }
            if (!Schema::hasColumn('monthly_summary_discontinued', 'barring_percentage')) {
                $table->decimal('barring_percentage', 5, 2)->default(0.00)->after('sales_review_remarks');
            }
        });
    }

    public function down(): void
    {
        Schema::table('monthly_summary', function (Blueprint $table) {
            $table->dropColumn(['sales_review_status', 'sales_review_remarks', 'barring_percentage']);
        });

        Schema::table('monthly_summary_discontinued', function (Blueprint $table) {
            $table->dropColumn(['visit_remarks', 'sales_review_status', 'sales_review_remarks', 'barring_percentage']);
        });
    }
};
