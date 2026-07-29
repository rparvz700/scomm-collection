<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client', function (Blueprint $table) {
            if (!Schema::hasColumn('client', 'barred_at')) {
                $table->timestamp('barred_at')->nullable()->after('client_status');
            }
            if (!Schema::hasColumn('client', 'barring_percentage')) {
                $table->decimal('barring_percentage', 5, 2)->default(0.00)->after('barred_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('client', function (Blueprint $table) {
            $table->dropColumn(['barred_at', 'barring_percentage']);
        });
    }
};
