<?php

namespace Database\Seeders;

use App\Models\MonthlySummary;
use Illuminate\Database\Seeder;

class RecalculateDeferredCrSeeder extends Seeder
{
    public function run(): void
    {
        MonthlySummary::syncDeferredSummaries();
    }
}
