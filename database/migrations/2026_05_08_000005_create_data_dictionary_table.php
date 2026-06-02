<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_dictionary', function (Blueprint $table) {
            $table->bigIncrements('data_dictionary_id');

            $table->string('table_name', 100)
                ->comment('Database table name');

            $table->string('column_name', 100)
                ->comment('Physical database column name');

            $table->string('business_name', 255)
                ->comment('Business-friendly field name');

            $table->text('business_definition')->nullable()
                ->comment('Business meaning and usage of the field');

            $table->string('data_type', 100)->nullable()
                ->comment('Physical database data type');

            $table->enum('source_type', [
                'manual',
                'system_generated',
                'calculated',
                'snapshot',
                'event_based',
            ])->default('manual')
                ->comment('How the field value is generated');

            $table->string('module_name', 100)->nullable()
                ->comment('Business module/domain ownership');

            $table->text('remarks')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->unique(['table_name', 'column_name'], 'uq_data_dictionary');
        });

        Schema::table('data_dictionary', function (Blueprint $table) {
            $table->comment('Central business metadata repository for schema governance');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_dictionary');
    }
};
