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
        Schema::table('client', function (Blueprint $table) {
            if (!Schema::hasColumn('client', 'collection_team_name')) {
                $table->string('collection_team_name')->nullable()->after('collection_supervisor_id');
            }
            if (!Schema::hasColumn('client', 'sm_supervisor')) {
                $table->string('sm_supervisor')->nullable()->after('team_name');
            }
            if (!Schema::hasColumn('client', 'sm_supervisor_id')) {
                $table->unsignedBigInteger('sm_supervisor_id')->nullable()->after('sm_supervisor');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client', function (Blueprint $table) {
            if (Schema::hasColumn('client', 'collection_team_name')) {
                $table->dropColumn('collection_team_name');
            }
            if (Schema::hasColumn('client', 'sm_supervisor')) {
                $table->dropColumn('sm_supervisor');
            }
            if (Schema::hasColumn('client', 'sm_supervisor_id')) {
                $table->dropColumn('sm_supervisor_id');
            }
        });
    }
};
