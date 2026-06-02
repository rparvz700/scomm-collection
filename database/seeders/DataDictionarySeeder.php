<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DataDictionarySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['table_name' => 'client', 'column_name' => 'client_id', 'business_name' => 'Client ID', 'business_definition' => 'Internal system generated unique client identifier', 'data_type' => 'BIGINT', 'source_type' => 'system_generated', 'module_name' => 'Master Data'],
            ['table_name' => 'client', 'column_name' => 'opus_id', 'business_name' => 'Opus ID', 'business_definition' => 'Unique customer identifier from OPUS or billing system', 'data_type' => 'VARCHAR(50)', 'source_type' => 'manual', 'module_name' => 'Master Data'],
            ['table_name' => 'client', 'column_name' => 'client_name', 'business_name' => 'Client Name', 'business_definition' => 'Official customer or company name', 'data_type' => 'VARCHAR(255)', 'source_type' => 'manual', 'module_name' => 'Master Data'],
            ['table_name' => 'client', 'column_name' => 'client_status', 'business_name' => 'Client Status', 'business_definition' => 'Operational customer lifecycle status', 'data_type' => 'VARCHAR(50)', 'source_type' => 'manual', 'module_name' => 'Master Data'],
            ['table_name' => 'client', 'column_name' => 'barring_priority', 'business_name' => 'Barring Priority', 'business_definition' => 'Operational barring priority assigned to customer', 'data_type' => 'ENUM', 'source_type' => 'manual', 'module_name' => 'Risk Management'],
            ['table_name' => 'client', 'column_name' => 'legal', 'business_name' => 'Legal Flag', 'business_definition' => 'Indicates whether customer is under legal escalation', 'data_type' => 'BOOLEAN', 'source_type' => 'manual', 'module_name' => 'Risk Management'],
            ['table_name' => 'client', 'column_name' => 'service_discontinuation_date', 'business_name' => 'Service Discontinuation Date', 'business_definition' => 'Date when customer service was discontinued', 'data_type' => 'DATE', 'source_type' => 'manual', 'module_name' => 'Operations'],
            ['table_name' => 'client', 'column_name' => 'billing_modality_kpi', 'business_name' => 'Billing Modality for KPI', 'business_definition' => 'Billing modality used for KPI calculations', 'data_type' => 'VARCHAR(100)', 'source_type' => 'manual', 'module_name' => 'Billing'],
            ['table_name' => 'client', 'column_name' => 'service_type_billing', 'business_name' => 'Service Type Billing', 'business_definition' => 'Billing service type classification', 'data_type' => 'VARCHAR(100)', 'source_type' => 'manual', 'module_name' => 'Billing'],
            ['table_name' => 'client', 'column_name' => 'license_billing', 'business_name' => 'License Billing', 'business_definition' => 'License category under billing model', 'data_type' => 'VARCHAR(100)', 'source_type' => 'manual', 'module_name' => 'Billing'],
            ['table_name' => 'client', 'column_name' => 'security_coverage', 'business_name' => 'Security Coverage', 'business_definition' => 'Security coverage classification for customer', 'data_type' => 'VARCHAR(100)', 'source_type' => 'manual', 'module_name' => 'Finance'],
            ['table_name' => 'client', 'column_name' => 'payment_plan', 'business_name' => 'Payment Plan', 'business_definition' => 'Customer payment arrangement summary', 'data_type' => 'VARCHAR(255)', 'source_type' => 'manual', 'module_name' => 'Collection'],
            ['table_name' => 'client', 'column_name' => 'other_upstream', 'business_name' => 'Other Upstream', 'business_definition' => 'Indicates whether customer uses providers other than SComm', 'data_type' => 'BOOLEAN', 'source_type' => 'manual', 'module_name' => 'Business'],
            ['table_name' => 'client', 'column_name' => 'sm_kam', 'business_name' => 'S&M KAM', 'business_definition' => 'Assigned sales and marketing account manager', 'data_type' => 'VARCHAR(255)', 'source_type' => 'manual', 'module_name' => 'Operations'],
            ['table_name' => 'client', 'column_name' => 'collection_kam', 'business_name' => 'Collection KAM', 'business_definition' => 'Assigned collection account manager', 'data_type' => 'VARCHAR(255)', 'source_type' => 'manual', 'module_name' => 'Collection'],
            ['table_name' => 'client', 'column_name' => 'collection_supervisor', 'business_name' => 'Collection Supervisor', 'business_definition' => 'Assigned collection supervisor', 'data_type' => 'VARCHAR(255)', 'source_type' => 'manual', 'module_name' => 'Collection'],

            ['table_name' => 'monthly_summary', 'column_name' => 'summary_month', 'business_name' => 'Summary Month', 'business_definition' => 'Month-end reporting snapshot period', 'data_type' => 'DATE', 'source_type' => 'snapshot', 'module_name' => 'Reporting'],
            ['table_name' => 'monthly_summary', 'column_name' => 'total_opening_os', 'business_name' => 'Total Opening OS', 'business_definition' => 'Total opening outstanding balance for reporting month', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'snapshot', 'module_name' => 'Finance'],
            ['table_name' => 'monthly_summary', 'column_name' => 'total_mrc', 'business_name' => 'Total MRC', 'business_definition' => 'Total monthly recurring charge for reporting month', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'snapshot', 'module_name' => 'Billing'],
            ['table_name' => 'monthly_summary', 'column_name' => 'total_maturity', 'business_name' => 'Total Maturity', 'business_definition' => 'Total maturity amount for reporting period', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'snapshot', 'module_name' => 'Collection'],
            ['table_name' => 'monthly_summary', 'column_name' => 'net_backlog_total', 'business_name' => 'Net Backlog Total', 'business_definition' => 'Total outstanding backlog amount', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'snapshot', 'module_name' => 'Collection'],
            ['table_name' => 'monthly_summary', 'column_name' => 'total_target_maturity_commitment', 'business_name' => 'Total Target Maturity Commitment', 'business_definition' => 'Combined operational collection target based on maturity and commitment', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'snapshot', 'module_name' => 'Collection'],
            ['table_name' => 'monthly_summary', 'column_name' => 'total_payment_plan', 'business_name' => 'Total Payment Plan', 'business_definition' => 'Total committed payment plan amount', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'snapshot', 'module_name' => 'Collection'],
            ['table_name' => 'monthly_summary', 'column_name' => 'total_shortfall_target', 'business_name' => 'Total Shortfall Target', 'business_definition' => 'Shortfall against operational target', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'calculated', 'module_name' => 'Collection'],
            ['table_name' => 'monthly_summary', 'column_name' => 'total_shortfall_mrc', 'business_name' => 'Total Shortfall MRC', 'business_definition' => 'Shortfall against total MRC', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'calculated', 'module_name' => 'Collection'],
            ['table_name' => 'monthly_summary', 'column_name' => 'total_shortfall_maturity', 'business_name' => 'Total Shortfall Maturity', 'business_definition' => 'Shortfall against maturity amount', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'calculated', 'module_name' => 'Collection'],
            ['table_name' => 'monthly_summary', 'column_name' => 'total_latest_os', 'business_name' => 'Total Latest OS', 'business_definition' => 'Latest outstanding balance at reporting snapshot', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'snapshot', 'module_name' => 'Finance'],
            ['table_name' => 'monthly_summary', 'column_name' => 'total_collection', 'business_name' => 'Total Collection', 'business_definition' => 'System generated monthly collection total aggregated from collection transactions', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'system_generated', 'module_name' => 'Collection'],
            ['table_name' => 'monthly_summary', 'column_name' => 'opening_cr', 'business_name' => 'Opening CR', 'business_definition' => 'Opening month credit rating snapshot', 'data_type' => 'DECIMAL(10,2)', 'source_type' => 'snapshot', 'module_name' => 'Risk'],
            ['table_name' => 'monthly_summary', 'column_name' => 'latest_cr', 'business_name' => 'Latest CR', 'business_definition' => 'Latest month-end credit rating snapshot', 'data_type' => 'DECIMAL(10,2)', 'source_type' => 'snapshot', 'module_name' => 'Risk'],
            ['table_name' => 'monthly_summary', 'column_name' => 'opening_rating_category', 'business_name' => 'Opening Rating Category', 'business_definition' => 'Opening month risk category snapshot', 'data_type' => 'VARCHAR(100)', 'source_type' => 'snapshot', 'module_name' => 'Risk'],
            ['table_name' => 'monthly_summary', 'column_name' => 'latest_rating_category', 'business_name' => 'Latest Rating Category', 'business_definition' => 'Latest month-end risk category snapshot', 'data_type' => 'VARCHAR(100)', 'source_type' => 'snapshot', 'module_name' => 'Risk'],
            ['table_name' => 'monthly_summary', 'column_name' => 'pdc', 'business_name' => 'PDC', 'business_definition' => 'Post-dated cheque amount', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'manual', 'module_name' => 'Collection'],
            ['table_name' => 'monthly_summary', 'column_name' => 'udc', 'business_name' => 'UDC', 'business_definition' => 'Undated cheque amount', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'manual', 'module_name' => 'Collection'],
            ['table_name' => 'monthly_summary', 'column_name' => 'expired_chq', 'business_name' => 'Expired Cheque', 'business_definition' => 'Expired cheque amount', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'manual', 'module_name' => 'Collection'],
            ['table_name' => 'monthly_summary', 'column_name' => 'balance_after_recovery', 'business_name' => 'Balance After Recovery', 'business_definition' => 'Remaining balance after recovery activities', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'calculated', 'module_name' => 'Finance'],

            ['table_name' => 'collection', 'column_name' => 'collection_datetime', 'business_name' => 'Collection Datetime', 'business_definition' => 'Actual collection transaction timestamp', 'data_type' => 'DATETIME', 'source_type' => 'event_based', 'module_name' => 'Collection'],
            ['table_name' => 'collection', 'column_name' => 'collection_month', 'business_name' => 'Collection Month', 'business_definition' => 'Reporting month derived from collection date', 'data_type' => 'DATE', 'source_type' => 'system_generated', 'module_name' => 'Collection'],
            ['table_name' => 'collection', 'column_name' => 'collection_type', 'business_name' => 'Collection Type', 'business_definition' => 'Billing/service category against which collection was received', 'data_type' => 'ENUM', 'source_type' => 'manual', 'module_name' => 'Collection'],
            ['table_name' => 'collection', 'column_name' => 'collection_amount', 'business_name' => 'Collection Amount', 'business_definition' => 'Actual collected amount from customer', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'event_based', 'module_name' => 'Collection'],
            ['table_name' => 'collection', 'column_name' => 'remarks', 'business_name' => 'Collection Remarks', 'business_definition' => 'Operational remarks against collection entry', 'data_type' => 'TEXT', 'source_type' => 'manual', 'module_name' => 'Collection'],
            ['table_name' => 'collection', 'column_name' => 'created_by', 'business_name' => 'Created By', 'business_definition' => 'User entering collection information', 'data_type' => 'VARCHAR(255)', 'source_type' => 'manual', 'module_name' => 'System'],

            ['table_name' => 'risk', 'column_name' => 'risk_event_datetime', 'business_name' => 'Risk Event Datetime', 'business_definition' => 'Timestamp when risk recalculation event occurred', 'data_type' => 'DATETIME', 'source_type' => 'event_based', 'module_name' => 'Risk'],
            ['table_name' => 'risk', 'column_name' => 'event_type', 'business_name' => 'Risk Event Type', 'business_definition' => 'Trigger source causing CR recalculation', 'data_type' => 'ENUM', 'source_type' => 'system_generated', 'module_name' => 'Risk'],
            ['table_name' => 'risk', 'column_name' => 'outstanding_balance', 'business_name' => 'Outstanding Balance', 'business_definition' => 'Outstanding balance used during CR calculation', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'event_based', 'module_name' => 'Risk'],
            ['table_name' => 'risk', 'column_name' => 'mrc_amount', 'business_name' => 'MRC Amount', 'business_definition' => 'MRC value used during CR calculation', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'event_based', 'module_name' => 'Risk'],
            ['table_name' => 'risk', 'column_name' => 'cr_value', 'business_name' => 'CR Value', 'business_definition' => 'Calculated credit rating. Formula: Outstanding Balance / MRC', 'data_type' => 'DECIMAL(10,2)', 'source_type' => 'calculated', 'module_name' => 'Risk'],
            ['table_name' => 'risk', 'column_name' => 'rating_category', 'business_name' => 'Rating Category', 'business_definition' => 'Calculated customer risk category based on CR', 'data_type' => 'VARCHAR(100)', 'source_type' => 'calculated', 'module_name' => 'Risk'],
            ['table_name' => 'risk', 'column_name' => 'legal', 'business_name' => 'Legal Status', 'business_definition' => 'Legal escalation status during risk event', 'data_type' => 'BOOLEAN', 'source_type' => 'manual', 'module_name' => 'Risk'],
            ['table_name' => 'risk', 'column_name' => 'barring_priority', 'business_name' => 'Risk Barring Priority', 'business_definition' => 'Operational barring category during risk event', 'data_type' => 'ENUM', 'source_type' => 'manual', 'module_name' => 'Risk'],
            ['table_name' => 'risk', 'column_name' => 'created_by', 'business_name' => 'Created By', 'business_definition' => 'User or system process generating risk event', 'data_type' => 'VARCHAR(255)', 'source_type' => 'system_generated', 'module_name' => 'System'],
        ];

        $rows = array_merge($rows, $this->monthlySummaryDictionaryRows());

        DB::table('data_dictionary')->upsert(
            $rows,
            ['table_name', 'column_name'],
            ['business_name', 'business_definition', 'data_type', 'source_type', 'module_name']
        );
    }

    private function monthlySummaryDictionaryRows(): array
    {
        $columns = [
            'monthly_summary_id' => ['Monthly Summary ID', 'Internal system generated monthly summary identifier', 'BIGINT', 'system_generated', 'Reporting'],
            'client_id' => ['Client ID', 'Client linked to this monthly summary snapshot', 'BIGINT', 'snapshot', 'Reporting'],
            'opening_os_postpaid_nttn' => ['Opening OS Postpaid NTTN', 'Opening postpaid NTTN outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance'],
            'opening_os_postpaid_iig_nttn' => ['Opening OS Postpaid IIG NTTN', 'Opening postpaid IIG NTTN outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance'],
            'opening_os_postpaid_iig' => ['Opening OS Postpaid IIG', 'Opening postpaid IIG outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance'],
            'opening_os_postpaid_itc' => ['Opening OS Postpaid ITC', 'Opening postpaid ITC outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance'],
            'opening_os_postpaid_nix' => ['Opening OS Postpaid NIX', 'Opening postpaid NIX outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance'],
            'opening_os_prepaid_nttn' => ['Opening OS Prepaid NTTN', 'Opening prepaid NTTN outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance'],
            'opening_os_prepaid_iig_nttn' => ['Opening OS Prepaid IIG NTTN', 'Opening prepaid IIG NTTN outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance'],
            'opening_os_prepaid_iig' => ['Opening OS Prepaid IIG', 'Opening prepaid IIG outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance'],
            'opening_os_prepaid_itc' => ['Opening OS Prepaid ITC', 'Opening prepaid ITC outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance'],
            'opening_os_prepaid_nix' => ['Opening OS Prepaid NIX', 'Opening prepaid NIX outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance'],
            'mrc_postpaid_nttn' => ['MRC Postpaid NTTN', 'Postpaid NTTN monthly recurring charge', 'DECIMAL(18,2)', 'snapshot', 'Billing'],
            'mrc_postpaid_nttn_iig' => ['MRC Postpaid NTTN IIG', 'Postpaid NTTN IIG monthly recurring charge', 'DECIMAL(18,2)', 'snapshot', 'Billing'],
            'mrc_postpaid_iig' => ['MRC Postpaid IIG', 'Postpaid IIG monthly recurring charge', 'DECIMAL(18,2)', 'snapshot', 'Billing'],
            'mrc_postpaid_itc' => ['MRC Postpaid ITC', 'Postpaid ITC monthly recurring charge', 'DECIMAL(18,2)', 'snapshot', 'Billing'],
            'mrc_postpaid_nix' => ['MRC Postpaid NIX', 'Postpaid NIX monthly recurring charge', 'DECIMAL(18,2)', 'snapshot', 'Billing'],
            'mrc_prepaid_nttn' => ['MRC Prepaid NTTN', 'Prepaid NTTN monthly recurring charge', 'DECIMAL(18,2)', 'snapshot', 'Billing'],
            'mrc_prepaid_nttn_iig' => ['MRC Prepaid NTTN IIG', 'Prepaid NTTN IIG monthly recurring charge', 'DECIMAL(18,2)', 'snapshot', 'Billing'],
            'mrc_prepaid_iig' => ['MRC Prepaid IIG', 'Prepaid IIG monthly recurring charge', 'DECIMAL(18,2)', 'snapshot', 'Billing'],
            'mrc_prepaid_itc' => ['MRC Prepaid ITC', 'Prepaid ITC monthly recurring charge', 'DECIMAL(18,2)', 'snapshot', 'Billing'],
            'mrc_prepaid_nix' => ['MRC Prepaid NIX', 'Prepaid NIX monthly recurring charge', 'DECIMAL(18,2)', 'snapshot', 'Billing'],
            'maturity_postpaid_nttn' => ['Maturity Postpaid NTTN', 'Postpaid NTTN maturity amount', 'DECIMAL(18,2)', 'snapshot', 'Collection'],
            'maturity_postpaid_nttn_iig' => ['Maturity Postpaid NTTN IIG', 'Postpaid NTTN IIG maturity amount', 'DECIMAL(18,2)', 'snapshot', 'Collection'],
            'maturity_postpaid_iig' => ['Maturity Postpaid IIG', 'Postpaid IIG maturity amount', 'DECIMAL(18,2)', 'snapshot', 'Collection'],
            'maturity_postpaid_itc' => ['Maturity Postpaid ITC', 'Postpaid ITC maturity amount', 'DECIMAL(18,2)', 'snapshot', 'Collection'],
            'maturity_postpaid_nix' => ['Maturity Postpaid NIX', 'Postpaid NIX maturity amount', 'DECIMAL(18,2)', 'snapshot', 'Collection'],
            'maturity_prepaid_nttn' => ['Maturity Prepaid NTTN', 'Prepaid NTTN maturity amount', 'DECIMAL(18,2)', 'snapshot', 'Collection'],
            'maturity_prepaid_nttn_iig' => ['Maturity Prepaid NTTN IIG', 'Prepaid NTTN IIG maturity amount', 'DECIMAL(18,2)', 'snapshot', 'Collection'],
            'maturity_prepaid_iig' => ['Maturity Prepaid IIG', 'Prepaid IIG maturity amount', 'DECIMAL(18,2)', 'snapshot', 'Collection'],
            'maturity_prepaid_itc' => ['Maturity Prepaid ITC', 'Prepaid ITC maturity amount', 'DECIMAL(18,2)', 'snapshot', 'Collection'],
            'maturity_prepaid_nix' => ['Maturity Prepaid NIX', 'Prepaid NIX maturity amount', 'DECIMAL(18,2)', 'snapshot', 'Collection'],
            'net_backlog_postpaid' => ['Net Backlog Postpaid', 'Net postpaid backlog amount', 'DECIMAL(18,2)', 'snapshot', 'Collection'],
            'net_backlog_prepaid' => ['Net Backlog Prepaid', 'Net prepaid backlog amount', 'DECIMAL(18,2)', 'snapshot', 'Collection'],
            'target_maturity_commitment_postpaid' => ['Target Maturity Commitment Postpaid', 'Postpaid target maturity commitment amount', 'DECIMAL(18,2)', 'snapshot', 'Collection'],
            'target_maturity_commitment_prepaid' => ['Target Maturity Commitment Prepaid', 'Prepaid target maturity commitment amount', 'DECIMAL(18,2)', 'snapshot', 'Collection'],
            'target_additional_shortfall_from_maturity' => ['Additional Shortfall From Maturity', 'Additional shortfall calculated from maturity target', 'DECIMAL(18,2)', 'calculated', 'Collection'],
            'maturity_commitment_total' => ['Maturity Commitment Total', 'Total maturity commitment amount', 'DECIMAL(18,2)', 'snapshot', 'Collection'],
            'payment_plan_postpaid' => ['Payment Plan Postpaid', 'Postpaid payment plan amount', 'DECIMAL(18,2)', 'manual', 'Collection'],
            'payment_plan_prepaid' => ['Payment Plan Prepaid', 'Prepaid payment plan amount', 'DECIMAL(18,2)', 'manual', 'Collection'],
            'current_month_remarks' => ['Current Month Remarks', 'Remarks for the current monthly summary period', 'TEXT', 'manual', 'Collection'],
            'shortfall_target_postpaid' => ['Shortfall Target Postpaid', 'Postpaid shortfall against target', 'DECIMAL(18,2)', 'calculated', 'Collection'],
            'shortfall_target_prepaid' => ['Shortfall Target Prepaid', 'Prepaid shortfall against target', 'DECIMAL(18,2)', 'calculated', 'Collection'],
            'shortfall_mrc_postpaid' => ['Shortfall MRC Postpaid', 'Postpaid shortfall against MRC', 'DECIMAL(18,2)', 'calculated', 'Collection'],
            'shortfall_mrc_prepaid' => ['Shortfall MRC Prepaid', 'Prepaid shortfall against MRC', 'DECIMAL(18,2)', 'calculated', 'Collection'],
            'shortfall_maturity_postpaid' => ['Shortfall Maturity Postpaid', 'Postpaid shortfall against maturity', 'DECIMAL(18,2)', 'calculated', 'Collection'],
            'shortfall_maturity_prepaid' => ['Shortfall Maturity Prepaid', 'Prepaid shortfall against maturity', 'DECIMAL(18,2)', 'calculated', 'Collection'],
            'shortfall_payment_plan_postpaid' => ['Shortfall Payment Plan Postpaid', 'Postpaid shortfall against payment plan', 'DECIMAL(18,2)', 'calculated', 'Collection'],
            'shortfall_payment_plan_prepaid' => ['Shortfall Payment Plan Prepaid', 'Prepaid shortfall against payment plan', 'DECIMAL(18,2)', 'calculated', 'Collection'],
            'latest_os_balance_postpaid' => ['Latest OS Balance Postpaid', 'Latest postpaid outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance'],
            'latest_os_balance_prepaid' => ['Latest OS Balance Prepaid', 'Latest prepaid outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance'],
            'payment_plan_description' => ['Payment Plan Description', 'Detailed description of customer payment plan', 'TEXT', 'manual', 'Collection'],
            'client_payment_commitment_date' => ['Client Payment Commitment Date', 'Committed customer payment date for MRC clearance', 'DATE', 'manual', 'Collection'],
            'nttn_tds_amount' => ['NTTN TDS Amount', 'Tax deducted at source amount for NTTN billing', 'DECIMAL(18,2)', 'manual', 'Finance'],
            'collection_postpaid_nttn' => ['Collection Postpaid NTTN', 'Postpaid NTTN collection amount', 'DECIMAL(18,2)', 'system_generated', 'Collection'],
            'collection_postpaid_nttn_iig' => ['Collection Postpaid NTTN IIG', 'Postpaid NTTN IIG collection amount', 'DECIMAL(18,2)', 'system_generated', 'Collection'],
            'collection_postpaid_iig' => ['Collection Postpaid IIG', 'Postpaid IIG collection amount', 'DECIMAL(18,2)', 'system_generated', 'Collection'],
            'collection_postpaid_itc' => ['Collection Postpaid ITC', 'Postpaid ITC collection amount', 'DECIMAL(18,2)', 'system_generated', 'Collection'],
            'collection_postpaid_nix' => ['Collection Postpaid NIX', 'Postpaid NIX collection amount', 'DECIMAL(18,2)', 'system_generated', 'Collection'],
            'collection_prepaid_nttn' => ['Collection Prepaid NTTN', 'Prepaid NTTN collection amount', 'DECIMAL(18,2)', 'system_generated', 'Collection'],
            'collection_prepaid_nttn_iig' => ['Collection Prepaid NTTN IIG', 'Prepaid NTTN IIG collection amount', 'DECIMAL(18,2)', 'system_generated', 'Collection'],
            'collection_prepaid_iig' => ['Collection Prepaid IIG', 'Prepaid IIG collection amount', 'DECIMAL(18,2)', 'system_generated', 'Collection'],
            'collection_prepaid_itc' => ['Collection Prepaid ITC', 'Prepaid ITC collection amount', 'DECIMAL(18,2)', 'system_generated', 'Collection'],
            'collection_prepaid_nix' => ['Collection Prepaid NIX', 'Prepaid NIX collection amount', 'DECIMAL(18,2)', 'system_generated', 'Collection'],
        ];

        return collect($columns)
            ->map(fn (array $field, string $column) => [
                'table_name' => 'monthly_summary',
                'column_name' => $column,
                'business_name' => $field[0],
                'business_definition' => $field[1],
                'data_type' => $field[2],
                'source_type' => $field[3],
                'module_name' => $field[4],
            ])
            ->values()
            ->all();
    }
}
