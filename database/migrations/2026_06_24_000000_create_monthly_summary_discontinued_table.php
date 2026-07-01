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
        Schema::create('monthly_summary_discontinued', function (Blueprint $table) {
            $table->id('monthly_summary_discontinued_id');
            $table->foreignId('client_id')->constrained('client', 'client_id')->onDelete('cascade');
            $table->date('summary_month');
            
            // Financial Snapshot Fields
            $table->decimal('opening_os', 15, 2)->default(0.00);
            $table->decimal('opening_os_nttn', 15, 2)->default(0.00);
            $table->decimal('opening_os_iig', 15, 2)->default(0.00);
            $table->decimal('opening_os_itc', 15, 2)->default(0.00);
            $table->decimal('opening_os_nix', 15, 2)->default(0.00);
            
            $table->decimal('target', 15, 2)->default(0.00);
            $table->decimal('collection_amount', 15, 2)->default(0.00);
            $table->decimal('shortfall_target', 15, 2)->default(0.00);
            
            $table->decimal('latest_os', 15, 2)->default(0.00);
            $table->decimal('latest_os_nttn', 15, 2)->default(0.00);
            $table->decimal('latest_os_iig', 15, 2)->default(0.00);
            $table->decimal('latest_os_itc', 15, 2)->default(0.00);
            $table->decimal('latest_os_nix', 15, 2)->default(0.00);
            
            $table->text('payment_plan_description')->nullable();
            $table->decimal('pdc', 15, 2)->default(0.00);
            $table->decimal('udc', 15, 2)->default(0.00);
            $table->decimal('total_security', 15, 2)->default(0.00);
            $table->decimal('security_coverage', 15, 2)->default(0.00);
            $table->string('pdc_chq')->nullable();
            $table->string('udc_chq')->nullable();
            $table->decimal('expired_chq', 15, 2)->default(0.00);
            
            // Collection breakdown
            $table->decimal('collection_postpaid_nttn', 15, 2)->default(0.00);
            $table->decimal('collection_postpaid_iig', 15, 2)->default(0.00);
            $table->decimal('collection_postpaid_itc', 15, 2)->default(0.00);
            $table->decimal('collection_postpaid_nix', 15, 2)->default(0.00);
            $table->decimal('total_collection', 15, 2)->default(0.00);
            
            // Discontinuation Dates
            $table->date('nttn_discontinuation_date')->nullable();
            $table->date('iig_itc_discontinuation_date')->nullable();
            
            // Un-billed OS breakdown
            $table->decimal('unbilled_total', 15, 2)->default(0.00);
            $table->decimal('unbilled_nttn_os', 15, 2)->default(0.00);
            $table->decimal('unbilled_iig_os', 15, 2)->default(0.00);
            $table->decimal('unbilled_itc_os', 15, 2)->default(0.00);
            
            $table->timestamps();
            
            // Unique constraint per client per month
            $table->unique(['client_id', 'summary_month'], 'uq_monthly_summary_discontinued');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_summary_discontinued');
    }
};
