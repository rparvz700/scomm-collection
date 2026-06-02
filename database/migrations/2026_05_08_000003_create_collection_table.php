<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection', function (Blueprint $table) {
            $table->bigIncrements('collection_id');

            $table->unsignedBigInteger('client_id');

            $table->dateTime('collection_datetime')
                ->comment('Actual collection entry timestamp');

            $table->date('collection_month')
                ->comment('Reporting month derived from collection date');

            $table->enum('collection_type', [
                'postpaid_nttn',
                'postpaid_nttn_iig',
                'postpaid_iig',
                'postpaid_itc',
                'postpaid_nix',
                'prepaid_nttn',
                'prepaid_nttn_iig',
                'prepaid_iig',
                'prepaid_itc',
                'prepaid_nix',
            ])->comment('Collection service and billing category');

            $table->decimal('collection_amount', 18, 2)->default(0)
                ->comment('Actual collected amount');

            $table->text('remarks')->nullable()
                ->comment('Collection entry remarks');

            $table->string('created_by', 255)->nullable()
                ->comment('User who entered collection information');

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('client_id', 'fk_collection_client')
                ->references('client_id')
                ->on('client');
        });

        Schema::table('collection', function (Blueprint $table) {
            $table->comment('Stores live collection transactions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection');
    }
};
