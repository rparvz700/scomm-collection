<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $newColumns = [
            'visit_remarks' => ['Visit Remarks', 'Operational visit notes and customer interaction feedback', 'TEXT', 'manual', 'Collection'],
            'sales_review_status' => ['Sales Review Status', 'Sales review decision status (Pending, Approved, Rejected)', 'VARCHAR(50)', 'manual', 'Sales'],
            'sales_review_remarks' => ['Sales Review Remarks', 'Remarks and comments provided by Sales & Marketing team', 'TEXT', 'manual', 'Sales'],
            'barring_percentage' => ['Barring Percentage', 'Operational line barring percentage applied', 'DECIMAL(5,2)', 'manual', 'Collection'],
            'collection_mrc' => ['Collection MRC (LIFO)', 'Calculated LIFO collection allocated towards current month MRC', 'DECIMAL(18,2)', 'system_generated', 'Collection'],
            'collection_backlog' => ['Collection Backlog (LIFO)', 'Calculated LIFO collection allocated towards outstanding backlog', 'DECIMAL(18,2)', 'system_generated', 'Collection'],
            'mrc_shortfall' => ['MRC Shortfall (LIFO)', 'Remaining shortfall on current month MRC after LIFO collection', 'DECIMAL(18,2)', 'calculated', 'Collection'],
            'backlog_shortfall' => ['Backlog Shortfall (LIFO)', 'Remaining shortfall on outstanding backlog after LIFO collection', 'DECIMAL(18,2)', 'calculated', 'Collection'],
        ];

        $rows = [];
        foreach (['monthly_summary', 'monthly_summary_discontinued'] as $table) {
            foreach ($newColumns as $col => $meta) {
                $rows[] = [
                    'table_name' => $table,
                    'column_name' => $col,
                    'business_name' => $meta[0],
                    'business_definition' => $meta[1],
                    'data_type' => $meta[2],
                    'source_type' => $meta[3],
                    'module_name' => $meta[4],
                    'remarks' => null,
                ];
            }
        }

        DB::table('data_dictionary')->upsert(
            $rows,
            ['table_name', 'column_name'],
            ['business_name', 'business_definition', 'data_type', 'source_type', 'module_name', 'remarks']
        );
    }

    public function down(): void
    {
        DB::table('data_dictionary')
            ->whereIn('table_name', ['monthly_summary', 'monthly_summary_discontinued'])
            ->whereIn('column_name', [
                'visit_remarks', 'sales_review_status', 'sales_review_remarks', 
                'barring_percentage', 'collection_mrc', 'collection_backlog', 
                'mrc_shortfall', 'backlog_shortfall'
            ])
            ->delete();
    }
};
