<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_log', function (Blueprint $table) {
            $table->bigIncrements('client_log_id');
            $table->unsignedBigInteger('client_id');
            $table->string('field_name', 100);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('updated_by', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('client_id', 'fk_client_log_client')
                ->references('client_id')
                ->on('client')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_log');
    }
};
