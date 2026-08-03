<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client', function (Blueprint $table) {
            $table->unsignedBigInteger('collection_kam_id')->nullable()->after('collection_kam');
            $table->unsignedBigInteger('collection_supervisor_id')->nullable()->after('collection_supervisor');
            $table->unsignedBigInteger('nttn_billing_kam_id')->nullable()->after('nttn_billing_kam');
            $table->unsignedBigInteger('iig_itc_billing_kam_id')->nullable()->after('iig_itc_billing_kam');
            $table->unsignedBigInteger('sm_kam_id')->nullable()->after('sm_kam');

            // Set foreign key relationships
            $table->foreign('collection_kam_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('collection_supervisor_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('nttn_billing_kam_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('iig_itc_billing_kam_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('sm_kam_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('client', function (Blueprint $table) {
            $table->dropForeign(['collection_kam_id']);
            $table->dropForeign(['collection_supervisor_id']);
            $table->dropForeign(['nttn_billing_kam_id']);
            $table->dropForeign(['iig_itc_billing_kam_id']);
            $table->dropForeign(['sm_kam_id']);

            $table->dropColumn([
                'collection_kam_id',
                'collection_supervisor_id',
                'nttn_billing_kam_id',
                'iig_itc_billing_kam_id',
                'sm_kam_id'
            ]);
        });
    }
};
