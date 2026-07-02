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
        Schema::create('report_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255)->comment('Name of the saved template');
            $table->text('description')->nullable()->comment('User-defined description of the report');
            $table->json('query_config')->comment('JSON abstract syntax tree of selected columns, joins, and filters');
            $table->unsignedBigInteger('created_by')->comment('Reference to the user who created it');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_templates');
    }
};
