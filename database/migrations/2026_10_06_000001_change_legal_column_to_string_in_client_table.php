<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `client` MODIFY COLUMN `legal` VARCHAR(500) NULL COMMENT 'Legal case details or notice text'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `client` MODIFY COLUMN `legal` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Legal escalation flag'");
    }
};
