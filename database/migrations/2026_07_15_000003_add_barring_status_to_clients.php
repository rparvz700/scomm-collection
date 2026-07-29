<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client', function (Blueprint $table) {
            if (!Schema::hasColumn('client', 'barring_workflow_status')) {
                $table->string('barring_workflow_status', 50)->default('none')->after('barring_percentage');
            }
        });
    }

    public function down(): void
    {
        Schema::table('client', function (Blueprint $table) {
            $table->dropColumn('barring_workflow_status');
        });
    }
};
