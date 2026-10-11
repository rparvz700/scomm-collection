<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('housekeeping_notifications')) {
            Schema::create('housekeeping_notifications', function (Blueprint $table) {
                $table->id();
                $table->string('report_month', 7); // e.g. 2026-10
                $table->integer('total_issues_count')->default(0);
                $table->integer('expired_agreements_count')->default(0);
                $table->integer('urgent_renewals_count')->default(0);
                $table->integer('btrc_license_issues_count')->default(0);
                $table->integer('data_discrepancies_count')->default(0);
                $table->mediumText('document_content'); // Formatted document content (< 5000 chars)
                $table->json('recipient_emails')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('housekeeping_notifications');
    }
};
