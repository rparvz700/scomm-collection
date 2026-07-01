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
        // 1. Drop foreign key constraints
        Schema::table('monthly_summary', function (Blueprint $table) {
            $table->dropForeign('fk_monthly_summary_client');
        });

        Schema::table('monthly_summary_discontinued', function (Blueprint $table) {
            $table->dropForeign('monthly_summary_discontinued_client_id_foreign');
        });

        // 2. Add client snapshot columns to monthly_summary
        Schema::table('monthly_summary', function (Blueprint $table) {
            $this->addClientSnapshotColumns($table);
        });

        // 3. Add client snapshot columns to monthly_summary_discontinued
        Schema::table('monthly_summary_discontinued', function (Blueprint $table) {
            $this->addClientSnapshotColumns($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Drop the added columns from monthly_summary
        Schema::table('monthly_summary', function (Blueprint $table) {
            $this->dropClientSnapshotColumns($table);
        });

        // 2. Drop the added columns from monthly_summary_discontinued
        Schema::table('monthly_summary_discontinued', function (Blueprint $table) {
            $this->dropClientSnapshotColumns($table);
        });

        // 3. Re-add foreign key constraints
        Schema::table('monthly_summary', function (Blueprint $table) {
            $table->foreign('client_id', 'fk_monthly_summary_client')
                ->references('client_id')
                ->on('client');
        });

        Schema::table('monthly_summary_discontinued', function (Blueprint $table) {
            $table->foreign('client_id', 'monthly_summary_discontinued_client_id_foreign')
                ->references('client_id')
                ->on('client')
                ->onDelete('cascade');
        });
    }

    /**
     * Helper to define all client snapshot columns.
     */
    private function addClientSnapshotColumns(Blueprint $table): void
    {
        $table->string('client_opus_id', 50)->nullable()->after('client_id')
            ->comment('Snapshot of customer billing identifier');

        $table->string('client_name', 255)->nullable()->after('client_opus_id')
            ->comment('Snapshot of official customer name');

        $table->string('client_status', 50)->nullable()->after('client_name')
            ->comment('Snapshot of operational customer status');

        $table->string('client_agreement_status', 100)->nullable()->after('client_status')
            ->comment('Snapshot of agreement status');

        $table->string('client_barring_priority', 50)->nullable()->after('client_agreement_status')
            ->comment('Snapshot of barring priority');

        $table->date('client_btrc_license_discontinuation_date')->nullable()->after('client_barring_priority')
            ->comment('Snapshot of BTRC license discontinuation date');

        $table->boolean('client_legal')->default(false)->after('client_btrc_license_discontinuation_date')
            ->comment('Snapshot of legal escalation flag');

        $table->date('client_service_discontinuation_date')->nullable()->after('client_legal')
            ->comment('Snapshot of service discontinuation date');

        $table->string('client_billing_modality_kpi', 100)->nullable()->after('client_service_discontinuation_date')
            ->comment('Snapshot of billing modality used for KPI');

        $table->string('client_service_type_billing', 100)->nullable()->after('client_billing_modality_kpi')
            ->comment('Snapshot of billing service type');

        $table->string('client_license_billing', 100)->nullable()->after('client_service_type_billing')
            ->comment('Snapshot of billing license category');

        $table->string('client_btrc_letter', 255)->nullable()->after('client_license_billing')
            ->comment('Snapshot of reference BTRC letter or notice');

        $table->string('client_security_coverage', 100)->nullable()->after('client_btrc_letter')
            ->comment('Snapshot of security coverage category');

        $table->string('client_payment_plan', 255)->nullable()->after('client_security_coverage')
            ->comment('Snapshot of customer payment plan');

        $table->boolean('client_other_upstream')->default(false)->after('client_payment_plan')
            ->comment('Snapshot of other upstream status');

        $table->string('client_sm_kam', 255)->nullable()->after('client_other_upstream')
            ->comment('Snapshot of Sales and Marketing KAM');

        $table->string('client_team_name', 255)->nullable()->after('client_sm_kam')
            ->comment('Snapshot of assigned operational team');

        $table->string('client_collection_kam', 255)->nullable()->after('client_team_name')
            ->comment('Snapshot of assigned collection KAM');

        $table->string('client_collection_supervisor', 255)->nullable()->after('client_collection_kam')
            ->comment('Snapshot of assigned collection supervisor');

        $table->string('client_nttn_billing_kam', 255)->nullable()->after('client_collection_supervisor')
            ->comment('Snapshot of NTTN billing KAM');

        $table->string('client_iig_itc_billing_kam', 255)->nullable()->after('client_nttn_billing_kam')
            ->comment('Snapshot of IIG/ITC billing KAM');

        $table->date('client_nttn_billing_commencement_date')->nullable()->after('client_iig_itc_billing_kam')
            ->comment('Snapshot of NTTN billing commencement date');

        $table->date('client_iig_itc_billing_commencement_date')->nullable()->after('client_nttn_billing_commencement_date')
            ->comment('Snapshot of IIG/ITC billing commencement date');
    }

    /**
     * Helper to drop all client snapshot columns.
     */
    private function dropClientSnapshotColumns(Blueprint $table): void
    {
        $table->dropColumn([
            'client_opus_id',
            'client_name',
            'client_status',
            'client_agreement_status',
            'client_barring_priority',
            'client_btrc_license_discontinuation_date',
            'client_legal',
            'client_service_discontinuation_date',
            'client_billing_modality_kpi',
            'client_service_type_billing',
            'client_license_billing',
            'client_btrc_letter',
            'client_security_coverage',
            'client_payment_plan',
            'client_other_upstream',
            'client_sm_kam',
            'client_team_name',
            'client_collection_kam',
            'client_collection_supervisor',
            'client_nttn_billing_kam',
            'client_iig_itc_billing_kam',
            'client_nttn_billing_commencement_date',
            'client_iig_itc_billing_commencement_date',
        ]);
    }
};
