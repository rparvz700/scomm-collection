<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $ranges = config('risk.ranges', []);

        foreach ($ranges as $range) {
            $min = $range['min'];
            $max = $range['max'];
            $category = $range['category'];

            // 1. Update opening_rating_category where NULL
            $queryOpening = DB::table('monthly_summary')
                ->whereNull('opening_rating_category');

            if ($min !== null) {
                $queryOpening->where('opening_cr', '>=', $min);
            }
            if ($max !== null) {
                $queryOpening->where('opening_cr', '<=', $max);
            }
            $queryOpening->update(['opening_rating_category' => $category]);

            // 2. Update latest_rating_category where NULL
            $queryLatest = DB::table('monthly_summary')
                ->whereNull('latest_rating_category');

            if ($min !== null) {
                $queryLatest->where('latest_cr', '>=', $min);
            }
            if ($max !== null) {
                $queryLatest->where('latest_cr', '<=', $max);
            }
            $queryLatest->update(['latest_rating_category' => $category]);
        }
    }

    public function down(): void
    {
        // Reverting this is not strictly required.
    }
};
