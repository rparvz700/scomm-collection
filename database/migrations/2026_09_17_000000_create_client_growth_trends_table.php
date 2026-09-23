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
        Schema::create('client_growth_trends', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->unique();
            $table->string('trend_status', 20)->default('stable')->index();
            $table->decimal('mrc_latest', 15, 2)->default(0.00);
            $table->decimal('mrc_baseline_12m', 15, 2)->default(0.00);
            $table->decimal('mrc_change_pct', 8, 2)->default(0.00);
            $table->decimal('cr_latest', 8, 2)->default(0.00);
            $table->decimal('cr_baseline_12m', 8, 2)->default(0.00);
            $table->decimal('cr_change_val', 8, 2)->default(0.00);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->foreign('client_id')
                ->references('client_id')
                ->on('client')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_growth_trends');
    }
};
