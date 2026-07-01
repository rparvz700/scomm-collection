<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client', function (Blueprint $table) {
            $table->bigIncrements('client_id');

            $table->string('opus_id', 50)->unique()
                ->comment('Unique customer identifier from OPUS/billing system');

            $table->string('client_name', 255)
                ->comment('Official customer/client name');

            $table->string('client_status', 50)->nullable()
                ->comment('Operational customer status');

            $table->string('agreement_status', 100)->nullable()
                ->comment('Agreement status of the client');

            $table->enum('barring_priority', ['P1', 'P2'])->nullable()
                ->comment('Default operational barring priority');

            $table->date('btrc_license_discontinuation_date')->nullable()
                ->comment('BTRC license discontinuation date if applicable');

            $table->boolean('legal')->default(false)
                ->comment('Legal escalation flag');

            $table->date('service_discontinuation_date')->nullable()
                ->comment('Service discontinuation date');

            $table->string('billing_modality_kpi', 100)->nullable()
                ->comment('Billing modality used for KPI calculations');

            $table->string('service_type_billing', 100)->nullable()
                ->comment('Billing service type classification');

            $table->string('license_billing', 100)->nullable()
                ->comment('Billing license category');

            $table->string('btrc_letter', 255)->nullable()
                ->comment('Reference of BTRC letter or notice');

            $table->string('security_coverage', 100)->nullable()
                ->comment('Security coverage category');

            $table->string('payment_plan', 255)->nullable()
                ->comment('Customer payment plan summary');

            $table->boolean('other_upstream')->default(false)
                ->comment('TRUE if customer has upstream other than SComm');

            $table->string('sm_kam', 255)->nullable()
                ->comment('Sales and Marketing KAM');

            $table->string('team_name', 255)->nullable()
                ->comment('Assigned operational team');

            $table->string('collection_kam', 255)->nullable()
                ->comment('Assigned collection KAM');

            $table->string('collection_supervisor', 255)->nullable()
                ->comment('Assigned collection supervisor');

            $table->string('nttn_billing_kam', 255)->nullable()
                ->comment('NTTN billing KAM');

            $table->string('iig_itc_billing_kam', 255)->nullable()
                ->comment('IIG/ITC billing KAM');

            $table->date('nttn_billing_commencement_date')->nullable()
                ->comment('NTTN billing commencement date');

            $table->date('iig_itc_billing_commencement_date')->nullable()
                ->comment('IIG/ITC billing commencement date');

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::table('client', function (Blueprint $table) {
            $table->comment('Stores stable customer master data');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client');
    }
};
