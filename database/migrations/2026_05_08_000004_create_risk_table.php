<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk', function (Blueprint $table) {
            $table->bigIncrements('risk_id');

            $table->unsignedBigInteger('client_id');

            $table->dateTime('risk_event_datetime')
                ->comment('Timestamp when risk event was generated');

            $table->enum('event_type', [
                'mrc_generated',
                'collection_received',
                'manual_adjustment',
                'monthly_reassessment',
                'system_recalculation',
            ])->comment('Trigger source of risk recalculation');

            $table->decimal('outstanding_balance', 18, 2)->default(0)
                ->comment('Outstanding balance at time of assessment');

            $table->decimal('mrc_amount', 18, 2)->default(0)
                ->comment('MRC value used during CR calculation');

            $table->decimal('cr_value', 10, 2)->default(0)
                ->comment('Calculated credit rating at event time. Formula: outstanding_balance / mrc_amount');

            $table->string('rating_category', 100)->nullable()
                ->comment('Calculated rating category at event time');

            $table->boolean('legal')->default(false)
                ->comment('Legal escalation status during risk event');

            $table->enum('barring_priority', ['P1', 'P2'])->nullable()
                ->comment('Operational barring priority during risk event');

            $table->text('remarks')->nullable();

            $table->string('created_by', 255)->nullable()
                ->comment('User or system process generating risk event');

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('client_id', 'fk_risk_client')
                ->references('client_id')
                ->on('client');
        });

        Schema::table('risk', function (Blueprint $table) {
            $table->comment('Stores event-based customer risk and credit history');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk');
    }
};
