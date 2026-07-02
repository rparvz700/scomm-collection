<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DataDictionarySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // Client Table Columns
            ['table_name' => 'client', 'column_name' => 'client_id', 'business_name' => 'Client ID', 'business_definition' => 'Internal system generated unique client identifier', 'data_type' => 'BIGINT', 'source_type' => 'system_generated', 'module_name' => 'Master Data', 'remarks' => null],
            ['table_name' => 'client', 'column_name' => 'opus_id', 'business_name' => 'Opus ID', 'business_definition' => 'Unique customer identifier from OPUS or billing system', 'data_type' => 'VARCHAR(50)', 'source_type' => 'manual', 'module_name' => 'Master Data', 'remarks' => 'Post-Paid+Pre-Paid.Opus ID'],
            ['table_name' => 'client', 'column_name' => 'client_name', 'business_name' => 'Client Name', 'business_definition' => 'Official customer or company name', 'data_type' => 'VARCHAR(255)', 'source_type' => 'manual', 'module_name' => 'Master Data', 'remarks' => 'Post-Paid+Pre-Paid.Client Name'],
            ['table_name' => 'client', 'column_name' => 'client_status', 'business_name' => 'Client Status', 'business_definition' => 'Operational customer lifecycle status', 'data_type' => 'VARCHAR(50)', 'source_type' => 'manual', 'module_name' => 'Master Data', 'remarks' => 'Discontinued (Collection).Barred or Discontinued'],
            ['table_name' => 'client', 'column_name' => 'agreement_status', 'business_name' => 'Agreement Status', 'business_definition' => 'Current status of customer agreements/contracts', 'data_type' => 'VARCHAR(100)', 'source_type' => 'manual', 'module_name' => 'Legal', 'remarks' => 'Post-Paid+Pre-Paid.Agreement Status'],
            ['table_name' => 'client', 'column_name' => 'barring_priority', 'business_name' => 'Barring Priority', 'business_definition' => 'Operational barring priority assigned to customer', 'data_type' => 'ENUM', 'source_type' => 'manual', 'module_name' => 'Risk Management', 'remarks' => null],
            ['table_name' => 'client', 'column_name' => 'btrc_license_discontinuation_date', 'business_name' => 'BTRC License Discontinuation Date', 'business_definition' => 'BTRC license discontinuation date if applicable', 'data_type' => 'DATE', 'source_type' => 'manual', 'module_name' => 'Legal', 'remarks' => 'Discontinued (Collection).BTRC License Dis. Date'],
            ['table_name' => 'client', 'column_name' => 'legal', 'business_name' => 'Legal Flag', 'business_definition' => 'Indicates whether customer is under legal escalation', 'data_type' => 'BOOLEAN', 'source_type' => 'manual', 'module_name' => 'Risk Management', 'remarks' => 'Discontinued (Collection).Legal'],
            ['table_name' => 'client', 'column_name' => 'service_discontinuation_date', 'business_name' => 'Service Discontinuation Date', 'business_definition' => 'Date when customer service was discontinued', 'data_type' => 'DATE', 'source_type' => 'manual', 'module_name' => 'Operations', 'remarks' => 'Discontinued (Collection).Service Discontinuation Date'],
            ['table_name' => 'client', 'column_name' => 'billing_modality_kpi', 'business_name' => 'Billing Modality for KPI', 'business_definition' => 'Billing modality used for KPI calculations', 'data_type' => 'VARCHAR(100)', 'source_type' => 'manual', 'module_name' => 'Billing', 'remarks' => 'Post-Paid+Pre-Paid.Billing Modality for KPI'],
            ['table_name' => 'client', 'column_name' => 'service_type_billing', 'business_name' => 'Service Type Billing', 'business_definition' => 'Billing service type classification', 'data_type' => 'VARCHAR(100)', 'source_type' => 'manual', 'module_name' => 'Billing', 'remarks' => 'Post-Paid+Pre-Paid.Service Type (Billing)'],
            ['table_name' => 'client', 'column_name' => 'license_billing', 'business_name' => 'License Billing', 'business_definition' => 'License category under billing model', 'data_type' => 'VARCHAR(100)', 'source_type' => 'manual', 'module_name' => 'Billing', 'remarks' => 'Post-Paid+Pre-Paid.License (Billing)'],
            ['table_name' => 'client', 'column_name' => 'btrc_letter', 'business_name' => 'BTRC Letter', 'business_definition' => 'Reference of BTRC letter or notice', 'data_type' => 'VARCHAR(255)', 'source_type' => 'manual', 'module_name' => 'Legal', 'remarks' => 'Post-Paid+Pre-Paid.BTRC Letter'],
            ['table_name' => 'client', 'column_name' => 'security_coverage', 'business_name' => 'Security Coverage', 'business_definition' => 'Security coverage classification for customer', 'data_type' => 'VARCHAR(100)', 'source_type' => 'manual', 'module_name' => 'Finance', 'remarks' => 'Post-Paid+Pre-Paid.Security Coverage'],
            ['table_name' => 'client', 'column_name' => 'payment_plan', 'business_name' => 'Payment Plan', 'business_definition' => 'Customer payment arrangement summary', 'data_type' => 'VARCHAR(255)', 'source_type' => 'manual', 'module_name' => 'Collection', 'remarks' => 'Post-Paid+Pre-Paid.Payment Plan'],
            ['table_name' => 'client', 'column_name' => 'other_upstream', 'business_name' => 'Other Upstream', 'business_definition' => 'Indicates whether customer uses providers other than SComm', 'data_type' => 'BOOLEAN', 'source_type' => 'manual', 'module_name' => 'Business', 'remarks' => 'Post-Paid+Pre-Paid.Other Upstream (Yes) / Only Scomm'],
            ['table_name' => 'client', 'column_name' => 'sm_kam', 'business_name' => 'S&M KAM', 'business_definition' => 'Assigned sales and marketing account manager', 'data_type' => 'VARCHAR(255)', 'source_type' => 'manual', 'module_name' => 'Operations', 'remarks' => 'Post-Paid+Pre-Paid.S&M KAM'],
            ['table_name' => 'client', 'column_name' => 'team_name', 'business_name' => 'Team Name', 'business_definition' => 'Assigned operational team', 'data_type' => 'VARCHAR(255)', 'source_type' => 'manual', 'module_name' => 'Master Data', 'remarks' => 'Post-Paid+Pre-Paid.Team Name'],
            ['table_name' => 'client', 'column_name' => 'collection_kam', 'business_name' => 'Collection KAM', 'business_definition' => 'Assigned collection account manager', 'data_type' => 'VARCHAR(255)', 'source_type' => 'manual', 'module_name' => 'Collection', 'remarks' => 'Post-Paid+Pre-Paid.Collection KAM'],
            ['table_name' => 'client', 'column_name' => 'collection_supervisor', 'business_name' => 'Collection Supervisor', 'business_definition' => 'Assigned collection supervisor', 'data_type' => 'VARCHAR(255)', 'source_type' => 'manual', 'module_name' => 'Collection', 'remarks' => 'Post-Paid+Pre-Paid.Collection Supervisor'],
            ['table_name' => 'client', 'column_name' => 'nttn_billing_kam', 'business_name' => 'NTTN Billing KAM', 'business_definition' => 'Assigned billing key account manager for NTTN services', 'data_type' => 'VARCHAR(255)', 'source_type' => 'manual', 'module_name' => 'Billing', 'remarks' => 'Post-Paid+Pre-Paid.NTTN Billing KAM'],
            ['table_name' => 'client', 'column_name' => 'iig_itc_billing_kam', 'business_name' => 'IIG/ITC Billing KAM', 'business_definition' => 'Assigned billing key account manager for IIG/ITC services', 'data_type' => 'VARCHAR(255)', 'source_type' => 'manual', 'module_name' => 'Billing', 'remarks' => 'Post-Paid+Pre-Paid.IIG / ITC Billing KAM'],
            ['table_name' => 'client', 'column_name' => 'nttn_billing_commencement_date', 'business_name' => 'NTTN Billing Commencement Date', 'business_definition' => 'Date when billing commenced for NTTN services', 'data_type' => 'DATE', 'source_type' => 'manual', 'module_name' => 'Billing', 'remarks' => 'Post-Paid+Pre-Paid.NTTN Billing Commencement Date (Billing)'],
            ['table_name' => 'client', 'column_name' => 'iig_itc_billing_commencement_date', 'business_name' => 'IIG/ITC Billing Commencement Date', 'business_definition' => 'Date when billing commenced for IIG/ITC services', 'data_type' => 'DATE', 'source_type' => 'manual', 'module_name' => 'Billing', 'remarks' => 'Post-Paid+Pre-Paid.IIG/ITC Billing Commencement Date (Billing)'],

            // Monthly Summary Table Columns
            ['table_name' => 'monthly_summary', 'column_name' => 'summary_month', 'business_name' => 'Summary Month', 'business_definition' => 'Month-end reporting snapshot period', 'data_type' => 'DATE', 'source_type' => 'snapshot', 'module_name' => 'Reporting', 'remarks' => null],
            ['table_name' => 'monthly_summary', 'column_name' => 'total_opening_os', 'business_name' => 'Total Opening OS', 'business_definition' => 'Total opening outstanding balance for reporting month', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'snapshot', 'module_name' => 'Finance', 'remarks' => 'Post-Paid+Pre-Paid.Total Opening OS (Pre+Post)'],
            ['table_name' => 'monthly_summary', 'column_name' => 'total_mrc', 'business_name' => 'Total MRC', 'business_definition' => 'Total monthly recurring charge for reporting month', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'snapshot', 'module_name' => 'Billing', 'remarks' => 'Post-Paid+Pre-Paid.Total MRC (Pre+Post)'],
            ['table_name' => 'monthly_summary', 'column_name' => 'total_maturity', 'business_name' => 'Total Maturity', 'business_definition' => 'Total maturity amount for reporting period', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'snapshot', 'module_name' => 'Collection', 'remarks' => 'Post-Paid+Pre-Paid.Total Maturity (Pre+Post)'],
            ['table_name' => 'monthly_summary', 'column_name' => 'net_backlog_total', 'business_name' => 'Net Backlog Total', 'business_definition' => 'Total outstanding backlog amount', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'snapshot', 'module_name' => 'Collection', 'remarks' => 'Post-Paid+Pre-Paid.Net Backlog / OS (Pre+Post)'],
            ['table_name' => 'monthly_summary', 'column_name' => 'total_target_maturity_commitment', 'business_name' => 'Total Target Maturity Commitment', 'business_definition' => 'Combined operational collection target based on maturity and commitment', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'snapshot', 'module_name' => 'Collection', 'remarks' => 'Post-Paid+Pre-Paid.Total Target / (Maturity + Commitment) (Post+Pre)'],
            ['table_name' => 'monthly_summary', 'column_name' => 'total_payment_plan', 'business_name' => 'Total Payment Plan', 'business_definition' => 'Total committed payment plan amount', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'snapshot', 'module_name' => 'Collection', 'remarks' => 'Post-Paid+Pre-Paid.Total Payment Plan (Post + Pre)'],
            ['table_name' => 'monthly_summary', 'column_name' => 'total_shortfall_target', 'business_name' => 'Total Shortfall Target', 'business_definition' => 'Shortfall against operational target', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'calculated', 'module_name' => 'Collection', 'remarks' => 'Post-Paid+Pre-Paid.Total Shortfall from Target / (Maturity + Commitment) (Post+Pre)'],
            ['table_name' => 'monthly_summary', 'column_name' => 'total_shortfall_mrc', 'business_name' => 'Total Shortfall MRC', 'business_definition' => 'Shortfall against total MRC', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'calculated', 'module_name' => 'Collection', 'remarks' => 'Post-Paid+Pre-Paid.Total Shortfall from MRC (Post+Pre)'],
            ['table_name' => 'monthly_summary', 'column_name' => 'total_shortfall_maturity', 'business_name' => 'Total Shortfall Maturity', 'business_definition' => 'Shortfall against maturity amount', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'calculated', 'module_name' => 'Collection', 'remarks' => 'Post-Paid+Pre-Paid.Total Shortfall from Maturity (Post+Pre)'],
            ['table_name' => 'monthly_summary', 'column_name' => 'total_latest_os', 'business_name' => 'Total Latest OS', 'business_definition' => 'Latest outstanding balance at reporting snapshot', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'snapshot', 'module_name' => 'Finance', 'remarks' => 'Post-Paid+Pre-Paid.Total Latest OS (Post+Pre)'],
            ['table_name' => 'monthly_summary', 'column_name' => 'total_collection', 'business_name' => 'Total Collection', 'business_definition' => 'System generated monthly collection total aggregated from collection transactions', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'system_generated', 'module_name' => 'Collection', 'remarks' => 'Post-Paid+Pre-Paid.Total Collection (Post+Pre)'],
            ['table_name' => 'monthly_summary', 'column_name' => 'opening_cr', 'business_name' => 'Opening CR', 'business_definition' => 'Opening month credit rating snapshot', 'data_type' => 'DECIMAL(10,2)', 'source_type' => 'snapshot', 'module_name' => 'Risk', 'remarks' => 'Post-Paid+Pre-Paid.MRC Count.Opening MRC Count (Post & Pre-paid)'],
            ['table_name' => 'monthly_summary', 'column_name' => 'latest_cr', 'business_name' => 'Latest CR', 'business_definition' => 'Latest month-end credit rating snapshot', 'data_type' => 'DECIMAL(10,2)', 'source_type' => 'snapshot', 'module_name' => 'Risk', 'remarks' => 'Post-Paid+Pre-Paid.MRC Count.Latest MRC Count (Post & Pre-paid)'],
            ['table_name' => 'monthly_summary', 'column_name' => 'opening_rating_category', 'business_name' => 'Opening Rating Category', 'business_definition' => 'Opening month risk category snapshot', 'data_type' => 'VARCHAR(100)', 'source_type' => 'snapshot', 'module_name' => 'Risk', 'remarks' => 'Post-Paid+Pre-Paid.Opening Rating Category (Post & Pre-paid)'],
            ['table_name' => 'monthly_summary', 'column_name' => 'latest_rating_category', 'business_name' => 'Latest Rating Category', 'business_definition' => 'Latest month-end risk category snapshot', 'data_type' => 'VARCHAR(100)', 'source_type' => 'snapshot', 'module_name' => 'Risk', 'remarks' => 'Post-Paid+Pre-Paid.Latest Rating Category (Post & Pre-paid)'],
            ['table_name' => 'monthly_summary', 'column_name' => 'pdc', 'business_name' => 'PDC', 'business_definition' => 'Post-dated cheque amount', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'manual', 'module_name' => 'Collection', 'remarks' => 'Post-Paid+Pre-Paid.PDC'],
            ['table_name' => 'monthly_summary', 'column_name' => 'udc', 'business_name' => 'UDC', 'business_definition' => 'Undated cheque amount', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'manual', 'module_name' => 'Collection', 'remarks' => 'Post-Paid+Pre-Paid.UDC'],
            ['table_name' => 'monthly_summary', 'column_name' => 'expired_chq', 'business_name' => 'Expired Cheque', 'business_definition' => 'Expired cheque amount', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'manual', 'module_name' => 'Collection', 'remarks' => 'Post-Paid+Pre-Paid.Expired CHQ'],
            ['table_name' => 'monthly_summary', 'column_name' => 'balance_after_recovery', 'business_name' => 'Balance After Recovery', 'business_definition' => 'Remaining balance after recovery activities', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'calculated', 'module_name' => 'Finance', 'remarks' => 'Post-Paid+Pre-Paid.Balance after Recover During this month'],

            // Collection Table Columns
            ['table_name' => 'collection', 'column_name' => 'collection_id', 'business_name' => 'Collection ID', 'business_definition' => 'Internal system generated unique collection transaction identifier', 'data_type' => 'BIGINT', 'source_type' => 'system_generated', 'module_name' => 'Collection', 'remarks' => null],
            ['table_name' => 'collection', 'column_name' => 'client_id', 'business_name' => 'Client ID', 'business_definition' => 'Client linked to this collection transaction', 'data_type' => 'BIGINT', 'source_type' => 'snapshot', 'module_name' => 'Collection', 'remarks' => null],
            ['table_name' => 'collection', 'column_name' => 'collection_datetime', 'business_name' => 'Collection Datetime', 'business_definition' => 'Actual collection transaction timestamp', 'data_type' => 'DATETIME', 'source_type' => 'event_based', 'module_name' => 'Collection', 'remarks' => null],
            ['table_name' => 'collection', 'column_name' => 'collection_month', 'business_name' => 'Collection Month', 'business_definition' => 'Reporting month derived from collection date', 'data_type' => 'DATE', 'source_type' => 'system_generated', 'module_name' => 'Collection', 'remarks' => null],
            ['table_name' => 'collection', 'column_name' => 'collection_type', 'business_name' => 'Collection Type', 'business_definition' => 'Billing/service category against which collection was received', 'data_type' => 'ENUM', 'source_type' => 'manual', 'module_name' => 'Collection', 'remarks' => null],
            ['table_name' => 'collection', 'column_name' => 'collection_amount', 'business_name' => 'Collection Amount', 'business_definition' => 'Actual collected amount from customer', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'event_based', 'module_name' => 'Collection', 'remarks' => null],
            ['table_name' => 'collection', 'column_name' => 'remarks', 'business_name' => 'Collection Remarks', 'business_definition' => 'Operational remarks against collection entry', 'data_type' => 'TEXT', 'source_type' => 'manual', 'module_name' => 'Collection', 'remarks' => null],
            ['table_name' => 'collection', 'column_name' => 'created_by', 'business_name' => 'Created By', 'business_definition' => 'User entering collection information', 'data_type' => 'VARCHAR(255)', 'source_type' => 'manual', 'module_name' => 'System', 'remarks' => null],

            // Risk Table Columns
            ['table_name' => 'risk', 'column_name' => 'risk_id', 'business_name' => 'Risk ID', 'business_definition' => 'Internal system generated unique risk event identifier', 'data_type' => 'BIGINT', 'source_type' => 'system_generated', 'module_name' => 'Risk', 'remarks' => null],
            ['table_name' => 'risk', 'column_name' => 'client_id', 'business_name' => 'Client ID', 'business_definition' => 'Client linked to this risk event', 'data_type' => 'BIGINT', 'source_type' => 'snapshot', 'module_name' => 'Risk', 'remarks' => null],
            ['table_name' => 'risk', 'column_name' => 'risk_event_datetime', 'business_name' => 'Risk Event Datetime', 'business_definition' => 'Timestamp when risk recalculation event occurred', 'data_type' => 'DATETIME', 'source_type' => 'event_based', 'module_name' => 'Risk', 'remarks' => null],
            ['table_name' => 'risk', 'column_name' => 'event_type', 'business_name' => 'Risk Event Type', 'business_definition' => 'Trigger source causing CR recalculation', 'data_type' => 'ENUM', 'source_type' => 'system_generated', 'module_name' => 'Risk', 'remarks' => null],
            ['table_name' => 'risk', 'column_name' => 'outstanding_balance', 'business_name' => 'Outstanding Balance', 'business_definition' => 'Outstanding balance used during CR calculation', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'event_based', 'module_name' => 'Risk', 'remarks' => null],
            ['table_name' => 'risk', 'column_name' => 'mrc_amount', 'business_name' => 'MRC Amount', 'business_definition' => 'MRC value used during CR calculation', 'data_type' => 'DECIMAL(18,2)', 'source_type' => 'event_based', 'module_name' => 'Risk', 'remarks' => null],
            ['table_name' => 'risk', 'column_name' => 'cr_value', 'business_name' => 'CR Value', 'business_definition' => 'Calculated credit rating. Formula: Outstanding Balance / MRC', 'data_type' => 'DECIMAL(10,2)', 'source_type' => 'calculated', 'module_name' => 'Risk', 'remarks' => null],
            ['table_name' => 'risk', 'column_name' => 'rating_category', 'business_name' => 'Rating Category', 'business_definition' => 'Calculated customer risk category based on CR', 'data_type' => 'VARCHAR(100)', 'source_type' => 'calculated', 'module_name' => 'Risk', 'remarks' => null],
            ['table_name' => 'risk', 'column_name' => 'legal', 'business_name' => 'Legal Status', 'business_definition' => 'Legal escalation status during risk event', 'data_type' => 'BOOLEAN', 'source_type' => 'manual', 'module_name' => 'Risk', 'remarks' => null],
            ['table_name' => 'risk', 'column_name' => 'barring_priority', 'business_name' => 'Risk Barring Priority', 'business_definition' => 'Operational barring category during risk event', 'data_type' => 'ENUM', 'source_type' => 'manual', 'module_name' => 'Risk', 'remarks' => null],
            ['table_name' => 'risk', 'column_name' => 'remarks', 'business_name' => 'Remarks', 'business_definition' => 'System or manual remarks for the risk event', 'data_type' => 'TEXT', 'source_type' => 'manual', 'module_name' => 'Risk', 'remarks' => null],
            ['table_name' => 'risk', 'column_name' => 'created_by', 'business_name' => 'Created By', 'business_definition' => 'User or system process generating risk event', 'data_type' => 'VARCHAR(255)', 'source_type' => 'system_generated', 'module_name' => 'System', 'remarks' => null],
        ];

        $rows = array_merge(
            $rows,
            $this->monthlySummaryDictionaryRows(),
            $this->monthlySummaryDiscontinuedDictionaryRows(),
            $this->clientSnapshotDictionaryRows('monthly_summary'),
            $this->clientSnapshotDictionaryRows('monthly_summary_discontinued'),
            $this->clientLogsDictionaryRows()
        );

        DB::table('data_dictionary')->upsert(
            $rows,
            ['table_name', 'column_name'],
            ['business_name', 'business_definition', 'data_type', 'source_type', 'module_name', 'remarks']
        );
    }

    private function monthlySummaryDictionaryRows(): array
    {
        $columns = [
            'monthly_summary_id' => ['Monthly Summary ID', 'Internal system generated monthly summary identifier', 'BIGINT', 'system_generated', 'Reporting', null],
            'client_id' => ['Client ID', 'Client linked to this monthly summary snapshot', 'BIGINT', 'snapshot', 'Reporting', null],
            'opening_os_postpaid_nttn' => ['Opening OS Postpaid NTTN', 'Opening postpaid NTTN outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance', 'LIVE_Post-Paid (Collection).NTTN OS'],
            'opening_os_postpaid_iig_nttn' => ['Opening OS Postpaid IIG NTTN', 'Opening postpaid IIG NTTN outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance', null],
            'opening_os_postpaid_iig' => ['Opening OS Postpaid IIG', 'Opening postpaid IIG outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance', 'LIVE_Post-Paid (Collection).IIG OS'],
            'opening_os_postpaid_itc' => ['Opening OS Postpaid ITC', 'Opening postpaid ITC outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance', 'LIVE_Post-Paid (Collection).ITC OS'],
            'opening_os_postpaid_nix' => ['Opening OS Postpaid NIX', 'Opening postpaid NIX outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance', 'LIVE_Post-Paid (Collection).NIX OS'],
            'opening_os_prepaid_nttn' => ['Opening OS Prepaid NTTN', 'Opening prepaid NTTN outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance', 'LIVE_Pre-Paid (Billing).NTTN OS'],
            'opening_os_prepaid_iig_nttn' => ['Opening OS Prepaid IIG NTTN', 'Opening prepaid IIG NTTN outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance', null],
            'opening_os_prepaid_iig' => ['Opening OS Prepaid IIG', 'Opening prepaid IIG outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance', 'LIVE_Pre-Paid (Billing).IIG OS'],
            'opening_os_prepaid_itc' => ['Opening OS Prepaid ITC', 'Opening prepaid ITC outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance', 'LIVE_Pre-Paid (Billing).ITC OS'],
            'opening_os_prepaid_nix' => ['Opening OS Prepaid NIX', 'Opening prepaid NIX outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance', 'LIVE_Pre-Paid (Billing).NIX OS'],
            'mrc_postpaid_nttn' => ['MRC Postpaid NTTN', 'Postpaid NTTN monthly recurring charge', 'DECIMAL(18,2)', 'snapshot', 'Billing', 'LIVE_Post-Paid (Collection).NTTN MRC'],
            'mrc_postpaid_nttn_iig' => ['MRC Postpaid NTTN IIG', 'Postpaid NTTN IIG monthly recurring charge', 'DECIMAL(18,2)', 'snapshot', 'Billing', null],
            'mrc_postpaid_iig' => ['MRC Postpaid IIG', 'Postpaid IIG monthly recurring charge', 'DECIMAL(18,2)', 'snapshot', 'Billing', 'LIVE_Post-Paid (Collection).IIG MRC'],
            'mrc_postpaid_itc' => ['MRC Postpaid ITC', 'Postpaid ITC monthly recurring charge', 'DECIMAL(18,2)', 'snapshot', 'Billing', 'LIVE_Post-Paid (Collection).ITC MRC'],
            'mrc_postpaid_nix' => ['MRC Postpaid NIX', 'Postpaid NIX monthly recurring charge', 'DECIMAL(18,2)', 'snapshot', 'Billing', 'LIVE_Post-Paid (Collection).NIX MRC'],
            'mrc_prepaid_nttn' => ['MRC Prepaid NTTN', 'Prepaid NTTN monthly recurring charge', 'DECIMAL(18,2)', 'snapshot', 'Billing', 'LIVE_Pre-Paid (Billing).NTTN MRC'],
            'mrc_prepaid_nttn_iig' => ['MRC Prepaid NTTN IIG', 'Prepaid NTTN IIG monthly recurring charge', 'DECIMAL(18,2)', 'snapshot', 'Billing', 'LIVE_Pre-Paid (Billing).IIG-NTTN MRC (Split)'],
            'mrc_prepaid_iig' => ['MRC Prepaid IIG', 'Prepaid IIG monthly recurring charge', 'DECIMAL(18,2)', 'snapshot', 'Billing', 'LIVE_Pre-Paid (Billing).IIG MRC'],
            'mrc_prepaid_itc' => ['MRC Prepaid ITC', 'Prepaid ITC monthly recurring charge', 'DECIMAL(18,2)', 'snapshot', 'Billing', 'LIVE_Pre-Paid (Billing).ITC MRC'],
            'mrc_prepaid_nix' => ['MRC Prepaid NIX', 'Prepaid NIX monthly recurring charge', 'DECIMAL(18,2)', 'snapshot', 'Billing', 'LIVE_Pre-Paid (Billing).NIX MRC'],
            'maturity_postpaid_nttn' => ['Maturity Postpaid NTTN', 'Postpaid NTTN maturity amount', 'DECIMAL(18,2)', 'snapshot', 'Collection', 'LIVE_Post-Paid (Collection).NTTN Maturity'],
            'maturity_postpaid_nttn_iig' => ['Maturity Postpaid NTTN IIG', 'Postpaid NTTN IIG maturity amount', 'DECIMAL(18,2)', 'snapshot', 'Collection', null],
            'maturity_postpaid_iig' => ['Maturity Postpaid IIG', 'Postpaid IIG maturity amount', 'DECIMAL(18,2)', 'snapshot', 'Collection', 'LIVE_Post-Paid (Collection).IIG Maturity'],
            'maturity_postpaid_itc' => ['Maturity Postpaid ITC', 'Postpaid ITC maturity amount', 'DECIMAL(18,2)', 'snapshot', 'Collection', 'LIVE_Post-Paid (Collection).ITC Maturity'],
            'maturity_postpaid_nix' => ['Maturity Postpaid NIX', 'Postpaid NIX maturity amount', 'DECIMAL(18,2)', 'snapshot', 'Collection', 'LIVE_Post-Paid (Collection).NIX Maturity'],
            'maturity_prepaid_nttn' => ['Maturity Prepaid NTTN', 'Prepaid NTTN maturity amount', 'DECIMAL(18,2)', 'snapshot', 'Collection', 'LIVE_Pre-Paid (Billing).NTTN Maturity'],
            'maturity_prepaid_nttn_iig' => ['Maturity Prepaid NTTN IIG', 'Prepaid NTTN IIG maturity amount', 'DECIMAL(18,2)', 'snapshot', 'Collection', null],
            'maturity_prepaid_iig' => ['Maturity Prepaid IIG', 'Prepaid IIG maturity amount', 'DECIMAL(18,2)', 'snapshot', 'Collection', 'LIVE_Pre-Paid (Billing).IIG Maturity'],
            'maturity_prepaid_itc' => ['Maturity Prepaid ITC', 'Prepaid ITC maturity amount', 'DECIMAL(18,2)', 'snapshot', 'Collection', 'LIVE_Pre-Paid (Billing).ITC Maturity'],
            'maturity_prepaid_nix' => ['Maturity Prepaid NIX', 'Prepaid NIX maturity amount', 'DECIMAL(18,2)', 'snapshot', 'Collection', 'LIVE_Pre-Paid (Billing).NIX Maturity'],
            'net_backlog_postpaid' => ['Net Backlog Postpaid', 'Net postpaid backlog amount', 'DECIMAL(18,2)', 'snapshot', 'Collection', 'Post-Paid+Pre-Paid.Post-paid (Net Backlog)'],
            'net_backlog_prepaid' => ['Net Backlog Prepaid', 'Net prepaid backlog amount', 'DECIMAL(18,2)', 'snapshot', 'Collection', 'Post-Paid+Pre-Paid.Pre-paid (Net Backlog)'],
            'target_maturity_commitment_postpaid' => ['Target Maturity Commitment Postpaid', 'Postpaid target maturity commitment amount', 'DECIMAL(18,2)', 'snapshot', 'Collection', 'LIVE_Post-Paid (Collection).Target / (Maturity + Commitment)'],
            'target_maturity_commitment_prepaid' => ['Target Maturity Commitment Prepaid', 'Prepaid target maturity commitment amount', 'DECIMAL(18,2)', 'snapshot', 'Collection', 'LIVE_Pre-Paid (Billing).Target / (Maturity + Commitment)'],
            'target_additional_shortfall_from_maturity' => ['Additional Shortfall From Maturity', 'Additional shortfall calculated from maturity target', 'DECIMAL(18,2)', 'calculated', 'Collection', 'Post-Paid+Pre-Paid.Target Additional / (Shortfall) from Maturity'],
            'maturity_commitment_total' => ['Maturity Commitment Total', 'Total maturity commitment amount', 'DECIMAL(18,2)', 'snapshot', 'Collection', 'Post-Paid+Pre-Paid.Maturity + Commitment'],
            'payment_plan_postpaid' => ['Payment Plan Postpaid', 'Postpaid payment plan amount', 'DECIMAL(18,2)', 'manual', 'Collection', 'LIVE_Post-Paid (Collection).Payment Plan Amount'],
            'payment_plan_prepaid' => ['Payment Plan Prepaid', 'Prepaid payment plan amount', 'DECIMAL(18,2)', 'manual', 'Collection', 'LIVE_Pre-Paid (Billing).Payment Plan Amount'],
            'current_month_remarks' => ['Current Month Remarks', 'Remarks for the current monthly summary period', 'TEXT', 'manual', 'Collection', 'LIVE_Post-Paid (Collection).Last Month Remarks'],
            'shortfall_target_postpaid' => ['Shortfall Target Postpaid', 'Postpaid shortfall against target', 'DECIMAL(18,2)', 'calculated', 'Collection', 'LIVE_Post-Paid (Collection).Shortfall from Target / (Maturity + Commitment) (W-AB)'],
            'shortfall_target_prepaid' => ['Shortfall Target Prepaid', 'Prepaid shortfall against target', 'DECIMAL(18,2)', 'calculated', 'Collection', 'LIVE_Pre-Paid (Billing).Shortfall from Target / (Maturity + Commitment) (GF-GK)'],
            'shortfall_mrc_postpaid' => ['Shortfall MRC Postpaid', 'Postpaid shortfall against MRC', 'DECIMAL(18,2)', 'calculated', 'Collection', 'LIVE_Post-Paid (Collection).Shortfall from MRC (N-AB)'],
            'shortfall_mrc_prepaid' => ['Shortfall MRC Prepaid', 'Prepaid shortfall against MRC', 'DECIMAL(18,2)', 'calculated', 'Collection', 'LIVE_Pre-Paid (Billing).Shortfall from MRC (S-GK)'],
            'shortfall_maturity_postpaid' => ['Shortfall Maturity Postpaid', 'Postpaid shortfall against maturity', 'DECIMAL(18,2)', 'calculated', 'Collection', 'LIVE_Post-Paid (Collection).Shortfall from Maturity (N-AB)'],
            'shortfall_maturity_prepaid' => ['Shortfall Maturity Prepaid', 'Prepaid shortfall against maturity', 'DECIMAL(18,2)', 'calculated', 'Collection', 'LIVE_Pre-Paid (Billing).Shortfall from Maturity'],
            'shortfall_payment_plan_postpaid' => ['Shortfall Payment Plan Postpaid', 'Postpaid shortfall against payment plan', 'DECIMAL(18,2)', 'calculated', 'Collection', 'Post-Paid+Pre-Paid.Payment Plan (Post-paid)'],
            'shortfall_payment_plan_prepaid' => ['Shortfall Payment Plan Prepaid', 'Prepaid shortfall against payment plan', 'DECIMAL(18,2)', 'calculated', 'Collection', 'Post-Paid+Pre-Paid.Payment Plan (Pre-paid)'],
            'total_shortfall_payment_plan' => ['Total Shortfall Payment Plan', 'Total shortfall against payment plan', 'DECIMAL(18,2)', 'calculated', 'Collection', 'Post-Paid+Pre-Paid.Total Payment Plan (Post + Pre)'],
            'latest_os_balance_postpaid' => ['Latest OS Balance Postpaid', 'Latest postpaid outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance', 'LIVE_Post-Paid (Collection).Latest OS Balance (H-AB)'],
            'latest_os_balance_prepaid' => ['Latest OS Balance Prepaid', 'Latest prepaid outstanding balance', 'DECIMAL(18,2)', 'snapshot', 'Finance', 'LIVE_Pre-Paid (Billing).Latest OS Balance (H-GK)'],
            'payment_plan_description' => ['Payment Plan Description', 'Detailed description of customer payment plan', 'TEXT', 'manual', 'Collection', 'LIVE_Pre-Paid (Billing).Security CHQ'],
            'client_payment_commitment_date' => ['Client Payment Commitment Date', 'Committed customer payment date for MRC clearance', 'DATE', 'manual', 'Collection', 'LIVE_Post-Paid (Collection).Possible date for payment'],
            'nttn_tds_amount' => ['NTTN TDS Amount', 'Tax deducted at source amount for NTTN billing', 'DECIMAL(18,2)', 'manual', 'Finance', 'Post-Paid+Pre-Paid.NTTN TDS Amount'],
            'collection_postpaid_nttn' => ['Collection Postpaid NTTN', 'Postpaid NTTN collection amount', 'DECIMAL(18,2)', 'system_generated', 'Collection', 'LIVE_Post-Paid (Collection).NTTN Payment'],
            'collection_postpaid_nttn_iig' => ['Collection Postpaid NTTN IIG', 'Postpaid NTTN IIG collection amount', 'DECIMAL(18,2)', 'system_generated', 'Collection', null],
            'collection_postpaid_iig' => ['Collection Postpaid IIG', 'Postpaid IIG collection amount', 'DECIMAL(18,2)', 'system_generated', 'Collection', 'LIVE_Post-Paid (Collection).IIG Payment'],
            'collection_postpaid_itc' => ['Collection Postpaid ITC', 'Postpaid ITC collection amount', 'DECIMAL(18,2)', 'system_generated', 'Collection', 'LIVE_Post-Paid (Collection).ITC Payment'],
            'collection_postpaid_nix' => ['Collection Postpaid NIX', 'Postpaid NIX collection amount', 'DECIMAL(18,2)', 'system_generated', 'Collection', 'LIVE_Post-Paid (Collection).NIX Payment'],
            'collection_prepaid_nttn' => ['Collection Prepaid NTTN', 'Prepaid NTTN collection amount', 'DECIMAL(18,2)', 'system_generated', 'Collection', 'LIVE_Pre-Paid (Billing).NTTN Payment'],
            'collection_prepaid_nttn_iig' => ['Collection Prepaid NTTN IIG', 'Prepaid NTTN IIG collection amount', 'DECIMAL(18,2)', 'system_generated', 'Collection', null],
            'collection_prepaid_iig' => ['Collection Prepaid IIG', 'Prepaid IIG collection amount', 'DECIMAL(18,2)', 'system_generated', 'Collection', 'LIVE_Pre-Paid (Billing).IIG Payment'],
            'collection_prepaid_itc' => ['Collection Prepaid ITC', 'Prepaid ITC collection amount', 'DECIMAL(18,2)', 'system_generated', 'Collection', 'LIVE_Pre-Paid (Billing).ITC Payment'],
            'collection_prepaid_nix' => ['Collection Prepaid NIX', 'Prepaid NIX collection amount', 'DECIMAL(18,2)', 'system_generated', 'Collection', 'LIVE_Pre-Paid (Billing).NIX Payment'],
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
                'remarks' => isset($field[5]) ? $field[5] : null,
            ])
            ->values()
            ->all();
    }

    private function clientLogsDictionaryRows(): array
    {
        return [
            ['table_name' => 'client_log', 'column_name' => 'client_log_id', 'business_name' => 'Log ID', 'business_definition' => 'System-generated unique identifier for client modification log', 'data_type' => 'BIGINT', 'source_type' => 'system_generated', 'module_name' => 'Master Data', 'remarks' => null],
            ['table_name' => 'client_log', 'column_name' => 'client_id', 'business_name' => 'Client ID', 'business_definition' => 'Associated client identifier', 'data_type' => 'BIGINT', 'source_type' => 'snapshot', 'module_name' => 'Master Data', 'remarks' => null],
            ['table_name' => 'client_log', 'column_name' => 'field_name', 'business_name' => 'Field Name', 'business_definition' => 'Name of the database field that was changed', 'data_type' => 'VARCHAR(100)', 'source_type' => 'system_generated', 'module_name' => 'Master Data', 'remarks' => null],
            ['table_name' => 'client_log', 'column_name' => 'old_value', 'business_name' => 'Old Value', 'business_definition' => 'Value of the field before modification', 'data_type' => 'TEXT', 'source_type' => 'system_generated', 'module_name' => 'Master Data', 'remarks' => null],
            ['table_name' => 'client_log', 'column_name' => 'new_value', 'business_name' => 'New Value', 'business_definition' => 'Value of the field after modification', 'data_type' => 'TEXT', 'source_type' => 'system_generated', 'module_name' => 'Master Data', 'remarks' => null],
            ['table_name' => 'client_log', 'column_name' => 'updated_by', 'business_name' => 'Updated By User', 'business_definition' => 'User ID who modified client profile data', 'data_type' => 'VARCHAR(255)', 'source_type' => 'manual', 'module_name' => 'Master Data', 'remarks' => null],
            ['table_name' => 'client_log', 'column_name' => 'created_at', 'business_name' => 'Log Timestamp', 'business_definition' => 'Timestamp when client profile change was recorded', 'data_type' => 'TIMESTAMP', 'source_type' => 'system_generated', 'module_name' => 'Master Data', 'remarks' => null],
        ];
    }

    private function monthlySummaryDiscontinuedDictionaryRows(): array
    {
        $columns = [
            'monthly_summary_discontinued_id' => ['Discontinued Summary ID', 'System-generated unique identifier for discontinued monthly snapshot record', 'BIGINT', 'system_generated', 'Reporting', null],
            'client_id' => ['Client ID', 'Associated client identifier', 'BIGINT', 'snapshot', 'Reporting', null],
            'summary_month' => ['Summary Month', 'Month-end reporting snapshot period', 'DATE', 'snapshot', 'Reporting', null],
            'opening_os' => ['Opening OS', 'Total opening outstanding balance for discontinued month', 'DECIMAL(15,2)', 'snapshot', 'Finance', null],
            'opening_os_nttn' => ['Opening OS NTTN', 'Opening NTTN outstanding balance', 'DECIMAL(15,2)', 'snapshot', 'Finance', null],
            'opening_os_iig' => ['Opening OS IIG', 'Opening IIG outstanding balance', 'DECIMAL(15,2)', 'snapshot', 'Finance', null],
            'opening_os_itc' => ['Opening OS ITC', 'Opening ITC outstanding balance', 'DECIMAL(15,2)', 'snapshot', 'Finance', null],
            'opening_os_nix' => ['Opening OS NIX', 'Opening NIX outstanding balance', 'DECIMAL(15,2)', 'snapshot', 'Finance', null],
            'target' => ['Target', 'Collection target amount set for discontinued client', 'DECIMAL(15,2)', 'snapshot', 'Collection', null],
            'collection_amount' => ['Collection Amount', 'Actual collection amount received from discontinued client', 'DECIMAL(15,2)', 'system_generated', 'Collection', null],
            'shortfall_target' => ['Shortfall Target', 'Collection shortfall against operational target amount', 'DECIMAL(15,2)', 'calculated', 'Collection', null],
            'latest_os' => ['Latest OS', 'Latest month-end outstanding balance for discontinued client', 'DECIMAL(15,2)', 'snapshot', 'Finance', null],
            'latest_os_nttn' => ['Latest OS NTTN', 'Latest month-end outstanding balance for NTTN service', 'DECIMAL(15,2)', 'snapshot', 'Finance', null],
            'latest_os_iig' => ['Latest OS IIG', 'Latest month-end outstanding balance for IIG service', 'DECIMAL(15,2)', 'snapshot', 'Finance', null],
            'latest_os_itc' => ['Latest OS ITC', 'Latest month-end outstanding balance for ITC service', 'DECIMAL(15,2)', 'snapshot', 'Finance', null],
            'latest_os_nix' => ['Latest OS NIX', 'Latest month-end outstanding balance for NIX service', 'DECIMAL(15,2)', 'snapshot', 'Finance', null],
            'payment_plan_description' => ['Payment Plan Description', 'Detailed payment agreement or recovery plan description', 'TEXT', 'manual', 'Collection', null],
            'pdc' => ['PDC Amount', 'Post-dated cheque amount registered', 'DECIMAL(15,2)', 'manual', 'Collection', null],
            'udc' => ['UDC Amount', 'Undated cheque amount registered', 'DECIMAL(15,2)', 'manual', 'Collection', null],
            'total_security' => ['Total Security', 'Total security deposit registered', 'DECIMAL(15,2)', 'manual', 'Finance', null],
            'security_coverage' => ['Security Coverage Value', 'Security coverage value assessed', 'DECIMAL(15,2)', 'manual', 'Finance', null],
            'pdc_chq' => ['PDC Cheque Details', 'Post-dated cheque reference numbers/details', 'VARCHAR(255)', 'manual', 'Collection', null],
            'udc_chq' => ['UDC Cheque Details', 'Undated cheque reference numbers/details', 'VARCHAR(255)', 'manual', 'Collection', null],
            'expired_chq' => ['Expired Cheque Amount', 'Expired post-dated cheque amount in possession', 'DECIMAL(15,2)', 'manual', 'Collection', null],
            'collection_postpaid_nttn' => ['Collection Postpaid NTTN', 'Collections received against postpaid NTTN service', 'DECIMAL(15,2)', 'system_generated', 'Collection', null],
            'collection_postpaid_iig' => ['Collection Postpaid IIG', 'Collections received against postpaid IIG service', 'DECIMAL(15,2)', 'system_generated', 'Collection', null],
            'collection_postpaid_itc' => ['Collection Postpaid ITC', 'Collections received against postpaid ITC service', 'DECIMAL(15,2)', 'system_generated', 'Collection', null],
            'collection_postpaid_nix' => ['Collection Postpaid NIX', 'Collections received against postpaid NIX service', 'DECIMAL(15,2)', 'system_generated', 'Collection', null],
            'total_collection' => ['Total Collection', 'Combined collection amount from all services', 'DECIMAL(15,2)', 'system_generated', 'Collection', null],
            'nttn_discontinuation_date' => ['NTTN Discontinuation Date', 'Date when NTTN service was officially discontinued', 'DATE', 'manual', 'Operations', null],
            'iig_itc_discontinuation_date' => ['IIG/ITC Discontinuation Date', 'Date when IIG/ITC services were officially discontinued', 'DATE', 'manual', 'Operations', null],
            'unbilled_total' => ['Unbilled Total OS', 'Total unbilled outstanding amount', 'DECIMAL(15,2)', 'snapshot', 'Finance', null],
            'unbilled_nttn_os' => ['Unbilled NTTN OS', 'Unbilled outstanding amount for NTTN service', 'DECIMAL(15,2)', 'snapshot', 'Finance', null],
            'unbilled_iig_os' => ['Unbilled IIG OS', 'Unbilled outstanding amount for IIG service', 'DECIMAL(15,2)', 'snapshot', 'Finance', null],
            'unbilled_itc_os' => ['Unbilled ITC OS', 'Unbilled outstanding amount for ITC service', 'DECIMAL(15,2)', 'snapshot', 'Finance', null],
        ];

        return collect($columns)
            ->map(fn (array $field, string $column) => [
                'table_name' => 'monthly_summary_discontinued',
                'column_name' => $column,
                'business_name' => $field[0],
                'business_definition' => $field[1],
                'data_type' => $field[2],
                'source_type' => $field[3],
                'module_name' => $field[4],
                'remarks' => $field[5],
            ])
            ->values()
            ->all();
    }

    private function clientSnapshotDictionaryRows(string $tableName): array
    {
        $columns = [
            'client_opus_id' => ['Snapshot Opus ID', 'Snapshot of client unique OPUS billing identifier', 'VARCHAR(50)', 'snapshot', 'Master Data', null],
            'client_name' => ['Snapshot Client Name', 'Snapshot of official customer company name', 'VARCHAR(255)', 'snapshot', 'Master Data', null],
            'client_status' => ['Snapshot Client Status', 'Snapshot of customer lifecycle operational status', 'VARCHAR(50)', 'snapshot', 'Master Data', null],
            'client_agreement_status' => ['Snapshot Agreement Status', 'Snapshot of contract agreement status', 'VARCHAR(100)', 'snapshot', 'Legal', null],
            'client_barring_priority' => ['Snapshot Barring Priority', 'Snapshot of assigned barring priority', 'VARCHAR(50)', 'snapshot', 'Risk Management', null],
            'client_btrc_license_discontinuation_date' => ['Snapshot BTRC License Discontinuation Date', 'Snapshot of BTRC license discontinuation date', 'DATE', 'snapshot', 'Legal', null],
            'client_legal' => ['Snapshot Legal Flag', 'Snapshot of legal dispute status indicator', 'BOOLEAN', 'snapshot', 'Risk Management', null],
            'client_service_discontinuation_date' => ['Snapshot Service Discontinuation Date', 'Snapshot of date service was discontinued', 'DATE', 'snapshot', 'Operations', null],
            'client_billing_modality_kpi' => ['Snapshot Billing Modality KPI', 'Snapshot of billing modality used for KPI', 'VARCHAR(100)', 'snapshot', 'Billing', null],
            'client_service_type_billing' => ['Snapshot Service Type Billing', 'Snapshot of billing service type', 'VARCHAR(100)', 'snapshot', 'Billing', null],
            'client_license_billing' => ['Snapshot License Billing', 'Snapshot of billing license category', 'VARCHAR(100)', 'snapshot', 'Billing', null],
            'client_btrc_letter' => ['Snapshot BTRC Letter', 'Snapshot of BTRC letter or notice reference', 'VARCHAR(255)', 'snapshot', 'Legal', null],
            'client_security_coverage' => ['Snapshot Security Coverage', 'Snapshot of security coverage classification', 'VARCHAR(100)', 'snapshot', 'Finance', null],
            'client_payment_plan' => ['Snapshot Payment Plan', 'Snapshot of customer payment plan arrangement', 'VARCHAR(255)', 'snapshot', 'Collection', null],
            'client_other_upstream' => ['Snapshot Other Upstream', 'Snapshot of upstream provider status', 'BOOLEAN', 'snapshot', 'Business', null],
            'client_sm_kam' => ['Snapshot S&M KAM', 'Snapshot of Sales & Marketing Account Manager name', 'VARCHAR(255)', 'snapshot', 'Operations', null],
            'client_team_name' => ['Snapshot Team Name', 'Snapshot of assigned operational team', 'VARCHAR(255)', 'snapshot', 'Master Data', null],
            'client_collection_kam' => ['Snapshot Collection KAM', 'Snapshot of Collection Account Manager name', 'VARCHAR(255)', 'snapshot', 'Collection', null],
            'client_collection_supervisor' => ['Snapshot Collection Supervisor', 'Snapshot of Collection Supervisor name', 'VARCHAR(255)', 'snapshot', 'Collection', null],
            'client_nttn_billing_kam' => ['Snapshot NTTN Billing KAM', 'Snapshot of NTTN Billing KAM name', 'VARCHAR(255)', 'snapshot', 'Billing', null],
            'client_iig_itc_billing_kam' => ['Snapshot IIG/ITC Billing KAM', 'Snapshot of IIG/ITC Billing KAM name', 'VARCHAR(255)', 'snapshot', 'Billing', null],
            'client_nttn_billing_commencement_date' => ['Snapshot NTTN Billing Commencement Date', 'Snapshot of NTTN billing commencement date', 'DATE', 'snapshot', 'Billing', null],
            'client_iig_itc_billing_commencement_date' => ['Snapshot IIG/ITC Billing Commencement Date', 'Snapshot of IIG/ITC billing commencement date', 'DATE', 'snapshot', 'Billing', null],
        ];

        return collect($columns)
            ->map(fn (array $field, string $column) => [
                'table_name' => $tableName,
                'column_name' => $column,
                'business_name' => $field[0],
                'business_definition' => $field[1],
                'data_type' => $field[2],
                'source_type' => $field[3],
                'module_name' => $field[4],
                'remarks' => isset($field[5]) ? $field[5] : null,
            ])
            ->values()
            ->all();
    }
}