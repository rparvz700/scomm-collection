<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('summary_audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('summary_type', 50)->comment('active or discontinued');
            $table->unsignedBigInteger('summary_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('field_name', 100);
            $table->string('field_label', 150)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->date('summary_month')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('updated_by', 255)->default('User');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('client_id')->on('client')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('summary_audit_logs');
    }
};
