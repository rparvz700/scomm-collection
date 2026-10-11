<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('collection_logs')) {
            Schema::create('collection_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('collection_id')->nullable()->comment('Associated collection ID');
                $table->unsignedBigInteger('client_id')->nullable()->comment('Associated client ID (null for untraced)');
                $table->string('action', 50)->comment('Action type: CREATED, UPDATED, DELETED');
                $table->string('field_name', 100)->nullable()->comment('Changed field name');
                $table->text('old_value')->nullable()->comment('Old field value');
                $table->text('new_value')->nullable()->comment('New field value');
                $table->string('performed_by', 255)->nullable()->comment('User who performed action');
                $table->timestamp('created_at')->useCurrent();

                $table->index('collection_id', 'idx_cl_collection_id');
                $table->index('client_id', 'idx_cl_client_id');
                $table->index('action', 'idx_cl_action');
                $table->index('created_at', 'idx_cl_created_at');
            });

            Schema::table('collection_logs', function (Blueprint $table) {
                $table->comment('Audit change history for collection entries');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_logs');
    }
};
