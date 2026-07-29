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
            $table->timestamp('client_barred_at')->nullable()->after('client_iig_itc_billing_commencement_date')
                ->comment('Snapshot of customer barred timestamp');
            $table->decimal('client_barring_percentage', 5, 2)->default(0.00)->after('client_barred_at')
                ->comment('Snapshot of customer barring percentage');
            $table->string('client_barring_workflow_status', 50)->default('none')->after('client_barring_percentage')
                ->comment('Snapshot of customer barring workflow status');
        });

        Schema::table('monthly_summary_discontinued', function (Blueprint $table) {
            $table->timestamp('client_barred_at')->nullable()->after('client_iig_itc_billing_commencement_date')
                ->comment('Snapshot of customer barred timestamp');
            $table->decimal('client_barring_percentage', 5, 2)->default(0.00)->after('client_barred_at')
                ->comment('Snapshot of customer barring percentage');
            $table->string('client_barring_workflow_status', 50)->default('none')->after('client_barring_percentage')
                ->comment('Snapshot of customer barring workflow status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_summary', function (Blueprint $table) {
            $table->dropColumn(['client_barred_at', 'client_barring_percentage', 'client_barring_workflow_status']);
        });

        Schema::table('monthly_summary_discontinued', function (Blueprint $table) {
            $table->dropColumn(['client_barred_at', 'client_barring_percentage', 'client_barring_workflow_status']);
        });
    }
};
