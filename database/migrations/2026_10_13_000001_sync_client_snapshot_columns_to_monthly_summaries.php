<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update columns in monthly_summary
        DB::statement("ALTER TABLE `monthly_summary` MODIFY COLUMN `client_legal` VARCHAR(500) NULL COMMENT 'Snapshot of legal case details or notice text'");
        DB::statement("ALTER TABLE `monthly_summary` MODIFY COLUMN `client_agreement_status` TEXT NULL COMMENT 'Snapshot of customer agreement status JSON'");

        Schema::table('monthly_summary', function (Blueprint $table) {
            $this->addNewSnapshotColumns($table);
        });

        // 2. Update columns in monthly_summary_discontinued
        DB::statement("ALTER TABLE `monthly_summary_discontinued` MODIFY COLUMN `client_legal` VARCHAR(500) NULL COMMENT 'Snapshot of legal case details or notice text'");
        DB::statement("ALTER TABLE `monthly_summary_discontinued` MODIFY COLUMN `client_agreement_status` TEXT NULL COMMENT 'Snapshot of customer agreement status JSON'");

        Schema::table('monthly_summary_discontinued', function (Blueprint $table) {
            $this->addNewSnapshotColumns($table);
        });

        // 3. Backfill data from client table into both summary tables
        $this->backfillClientSnapshots();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_summary', function (Blueprint $table) {
            $this->dropNewSnapshotColumns($table);
        });

        Schema::table('monthly_summary_discontinued', function (Blueprint $table) {
            $this->dropNewSnapshotColumns($table);
        });

        DB::statement("ALTER TABLE `monthly_summary` MODIFY COLUMN `client_legal` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Snapshot of legal escalation flag'");
        DB::statement("ALTER TABLE `monthly_summary` MODIFY COLUMN `client_agreement_status` VARCHAR(100) NULL COMMENT 'Snapshot of agreement status'");

        DB::statement("ALTER TABLE `monthly_summary_discontinued` MODIFY COLUMN `client_legal` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Snapshot of legal escalation flag'");
        DB::statement("ALTER TABLE `monthly_summary_discontinued` MODIFY COLUMN `client_agreement_status` VARCHAR(100) NULL COMMENT 'Snapshot of agreement status'");
    }

    private function addNewSnapshotColumns(Blueprint $table): void
    {
        $tableName = $table->getTable();

        if (!Schema::hasColumn($tableName, 'client_deferred_period')) {
            $table->unsignedInteger('client_deferred_period')->default(0)->after('client_payment_plan')
                ->comment('Snapshot of deferred period in days');
        }
        if (!Schema::hasColumn($tableName, 'client_sm_kam_id')) {
            $table->unsignedBigInteger('client_sm_kam_id')->nullable()->after('client_sm_kam')
                ->comment('Snapshot of Sales and Marketing KAM User ID');
        }
        if (!Schema::hasColumn($tableName, 'client_sm_supervisor')) {
            $table->string('client_sm_supervisor', 255)->nullable()->after('client_team_name')
                ->comment('Snapshot of Sales and Marketing Supervisor');
        }
        if (!Schema::hasColumn($tableName, 'client_sm_supervisor_id')) {
            $table->unsignedBigInteger('client_sm_supervisor_id')->nullable()->after('client_sm_supervisor')
                ->comment('Snapshot of Sales and Marketing Supervisor User ID');
        }
        if (!Schema::hasColumn($tableName, 'client_collection_kam_id')) {
            $table->unsignedBigInteger('client_collection_kam_id')->nullable()->after('client_collection_kam')
                ->comment('Snapshot of Collection KAM User ID');
        }
        if (!Schema::hasColumn($tableName, 'client_collection_supervisor_id')) {
            $table->unsignedBigInteger('client_collection_supervisor_id')->nullable()->after('client_collection_supervisor')
                ->comment('Snapshot of Collection Supervisor User ID');
        }
        if (!Schema::hasColumn($tableName, 'client_collection_team_name')) {
            $table->string('client_collection_team_name', 255)->nullable()->after('client_collection_supervisor_id')
                ->comment('Snapshot of Collection Team Name');
        }
        if (!Schema::hasColumn($tableName, 'client_nttn_billing_kam_id')) {
            $table->unsignedBigInteger('client_nttn_billing_kam_id')->nullable()->after('client_nttn_billing_kam')
                ->comment('Snapshot of NTTN Billing KAM User ID');
        }
        if (!Schema::hasColumn($tableName, 'client_iig_itc_billing_kam_id')) {
            $table->unsignedBigInteger('client_iig_itc_billing_kam_id')->nullable()->after('client_iig_itc_billing_kam')
                ->comment('Snapshot of IIG/ITC Billing KAM User ID');
        }
    }

    private function dropNewSnapshotColumns(Blueprint $table): void
    {
        $tableName = $table->getTable();
        $cols = [
            'client_deferred_period',
            'client_sm_kam_id',
            'client_sm_supervisor',
            'client_sm_supervisor_id',
            'client_collection_kam_id',
            'client_collection_supervisor_id',
            'client_collection_team_name',
            'client_nttn_billing_kam_id',
            'client_iig_itc_billing_kam_id',
        ];
        foreach ($cols as $col) {
            if (Schema::hasColumn($tableName, $col)) {
                $table->dropColumn($col);
            }
        }
    }

    private function backfillClientSnapshots(): void
    {
        DB::statement("
            UPDATE monthly_summary ms
            JOIN client c ON ms.client_id = c.client_id
            SET
                ms.client_deferred_period = COALESCE(c.deferred_period, 0),
                ms.client_sm_kam_id = c.sm_kam_id,
                ms.client_sm_supervisor = c.sm_supervisor,
                ms.client_sm_supervisor_id = c.sm_supervisor_id,
                ms.client_collection_kam_id = c.collection_kam_id,
                ms.client_collection_supervisor_id = c.collection_supervisor_id,
                ms.client_collection_team_name = c.collection_team_name,
                ms.client_nttn_billing_kam_id = c.nttn_billing_kam_id,
                ms.client_iig_itc_billing_kam_id = c.iig_itc_billing_kam_id,
                ms.client_legal = c.legal,
                ms.client_agreement_status = c.agreement_status
        ");

        DB::statement("
            UPDATE monthly_summary_discontinued msd
            JOIN client c ON msd.client_id = c.client_id
            SET
                msd.client_deferred_period = COALESCE(c.deferred_period, 0),
                msd.client_sm_kam_id = c.sm_kam_id,
                msd.client_sm_supervisor = c.sm_supervisor,
                msd.client_sm_supervisor_id = c.sm_supervisor_id,
                msd.client_collection_kam_id = c.collection_kam_id,
                msd.client_collection_supervisor_id = c.collection_supervisor_id,
                msd.client_collection_team_name = c.collection_team_name,
                msd.client_nttn_billing_kam_id = c.nttn_billing_kam_id,
                msd.client_iig_itc_billing_kam_id = c.iig_itc_billing_kam_id,
                msd.client_legal = c.legal,
                msd.client_agreement_status = c.agreement_status
        ");
    }
};
