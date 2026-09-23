<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('collection', function (Blueprint $table) {
                $table->unsignedBigInteger('client_id')->nullable()->change();
            });
        } catch (\Throwable $e) {
            DB::statement('ALTER TABLE collection MODIFY client_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        try {
            Schema::table('collection', function (Blueprint $table) {
                $table->unsignedBigInteger('client_id')->nullable(false)->change();
            });
        } catch (\Throwable $e) {
            DB::statement('ALTER TABLE collection MODIFY client_id BIGINT UNSIGNED NOT NULL');
        }
    }
};
