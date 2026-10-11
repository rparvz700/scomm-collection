<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('waivers')) {
            Schema::create('waivers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('client_id')->comment('Associated client ID');
                $table->date('applicable_from_date')->comment('Applicable from date');
                $table->decimal('waiver_amount', 15, 2)->default(0.00)->comment('Waiver amount');
                $table->text('description')->nullable()->comment('Description / remarks');
                $table->string('attachment_path', 500)->nullable()->comment('Storage path for attachment file');
                $table->string('attachment_name', 255)->nullable()->comment('Original file name');
                $table->string('created_by', 255)->nullable()->comment('User email who created');
                $table->timestamps();

                $table->index('client_id', 'idx_waiver_client_id');
                $table->index('applicable_from_date', 'idx_waiver_date');
                $table->index('created_at', 'idx_waiver_created_at');

                $table->foreign('client_id', 'fk_waiver_client_id')
                    ->references('client_id')
                    ->on('client')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('waivers');
    }
};
