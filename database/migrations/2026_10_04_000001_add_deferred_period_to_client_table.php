<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client', function (Blueprint $table) {
            if (!Schema::hasColumn('client', 'deferred_period')) {
                $table->unsignedInteger('deferred_period')->default(0)->after('payment_plan')
                    ->comment('Deferred period in Days before billed MRC matures');
            }
        });
    }

    public function down(): void
    {
        Schema::table('client', function (Blueprint $table) {
            if (Schema::hasColumn('client', 'deferred_period')) {
                $table->dropColumn('deferred_period');
            }
        });
    }
};
