<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_summary', function (Blueprint $table) {
            $table->bigIncrements('monthly_summary_id');
            $table->unsignedBigInteger('client_id');
            $table->date('summary_month')->comment('Month-end snapshot period');

            foreach ([
                'opening_os_postpaid_nttn', 'opening_os_postpaid_iig_nttn', 'opening_os_postpaid_iig', 'opening_os_postpaid_itc', 'opening_os_postpaid_nix',
                'opening_os_prepaid_nttn', 'opening_os_prepaid_iig_nttn', 'opening_os_prepaid_iig', 'opening_os_prepaid_itc', 'opening_os_prepaid_nix',
                'total_opening_os',
                'mrc_postpaid_nttn', 'mrc_postpaid_nttn_iig', 'mrc_postpaid_iig', 'mrc_postpaid_itc', 'mrc_postpaid_nix',
                'mrc_prepaid_nttn', 'mrc_prepaid_nttn_iig', 'mrc_prepaid_iig', 'mrc_prepaid_itc', 'mrc_prepaid_nix',
                'total_mrc',
                'maturity_postpaid_nttn', 'maturity_postpaid_nttn_iig', 'maturity_postpaid_iig', 'maturity_postpaid_itc', 'maturity_postpaid_nix',
                'maturity_prepaid_nttn', 'maturity_prepaid_nttn_iig', 'maturity_prepaid_iig', 'maturity_prepaid_itc', 'maturity_prepaid_nix',
                'total_maturity',
                'net_backlog_postpaid', 'net_backlog_prepaid', 'net_backlog_total',
                'target_maturity_commitment_postpaid', 'target_maturity_commitment_prepaid', 'total_target_maturity_commitment',
                'target_additional_shortfall_from_maturity', 'maturity_commitment_total',
                'payment_plan_postpaid', 'payment_plan_prepaid', 'total_payment_plan',
                'shortfall_target_postpaid', 'shortfall_target_prepaid', 'total_shortfall_target',
                'shortfall_mrc_postpaid', 'shortfall_mrc_prepaid', 'total_shortfall_mrc',
                'shortfall_maturity_postpaid', 'shortfall_maturity_prepaid', 'total_shortfall_maturity',
                'shortfall_payment_plan_postpaid', 'shortfall_payment_plan_prepaid', 'total_shortfall_payment_plan',
                'latest_os_balance_postpaid', 'latest_os_balance_prepaid', 'total_latest_os',
                'nttn_tds_amount', 'balance_after_recovery',
                'collection_postpaid_nttn', 'collection_postpaid_nttn_iig', 'collection_postpaid_iig', 'collection_postpaid_itc', 'collection_postpaid_nix',
                'collection_prepaid_nttn', 'collection_prepaid_nttn_iig', 'collection_prepaid_iig', 'collection_prepaid_itc', 'collection_prepaid_nix',
            ] as $column) {
                $table->decimal($column, 18, 2)->default(0);
            }

            $table->text('current_month_remarks')->nullable();

            $table->decimal('pdc', 18, 2)->default(0)->comment('Post-dated cheque amount');
            $table->decimal('udc', 18, 2)->default(0)->comment('Undated cheque amount');
            $table->decimal('expired_chq', 18, 2)->default(0)->comment('Expired cheque amount');

            $table->text('payment_plan_description')->nullable();
            $table->date('client_payment_commitment_date')->nullable()
                ->comment('Committed customer payment date for MRC clearance');

            $table->decimal('total_collection', 18, 2)->default(0)
                ->comment('System generated monthly collection total from collection transaction table');

            $table->decimal('opening_cr', 10, 2)->default(0)
                ->comment('Opening month credit rating snapshot');
            $table->string('opening_rating_category', 100)->nullable()
                ->comment('Opening month rating category snapshot');
            $table->decimal('latest_cr', 10, 2)->default(0)
                ->comment('Latest month-end credit rating snapshot');
            $table->string('latest_rating_category', 100)->nullable()
                ->comment('Latest month-end rating category snapshot');

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('client_id', 'fk_monthly_summary_client')
                ->references('client_id')
                ->on('client');

            $table->unique(['client_id', 'summary_month'], 'uq_monthly_summary');
        });

        Schema::table('monthly_summary', function (Blueprint $table) {
            $table->comment('Stores frozen monthly financial and operational snapshots');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_summary');
    }
};
