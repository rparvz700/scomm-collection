<?php

namespace App\Services;

use App\Models\Client;
use App\Models\HousekeepingNotification;
use App\Mail\HousekeepingReportMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class HousekeepingAuditService
{
    public function runFullAudit(): array
    {
        $today = Carbon::today();
        $thresholdDays = config('housekeeping.expiry_threshold_days', 30);

        $expiredAgreements = [];
        $urgentAgreements = [];
        $btrcExpiries = [];
        $unassignedKams = [];
        $missingOpusIds = [];
        $barringDiscrepancies = [];
        $missingBillingDates = [];

        $clients = Client::query()->get();

        foreach ($clients as $client) {
            $smKam = !empty(trim($client->sm_kam ?? '')) ? trim($client->sm_kam) : 'Unassigned';
            $collKam = !empty(trim($client->collection_kam ?? '')) ? trim($client->collection_kam) : 'Unassigned';

            // 1. Audit Agreements JSON (30 Days Threshold)
            $evaluated = $client->evaluated_agreements;
            foreach ($evaluated as $ag) {
                $licName = $ag['license_name'] ?? 'Unknown License';
                $expiryStr = !empty($ag['expiry_date']) ? Carbon::parse($ag['expiry_date'])->format('d M Y') : 'N/A';
                
                if ($ag['status'] === 'expired') {
                    $expiredAgreements[] = [
                        'client_name' => $client->client_name,
                        'license' => $licName,
                        'detail' => "Expired " . abs($ag['days_remaining'] ?? 0) . " days ago ({$expiryStr})",
                        'sm_kam' => $smKam,
                        'collection_kam' => $collKam,
                    ];
                } elseif (!is_null($ag['days_remaining']) && $ag['days_remaining'] >= 0 && $ag['days_remaining'] <= $thresholdDays) {
                    $urgentAgreements[] = [
                        'client_name' => $client->client_name,
                        'license' => $licName,
                        'detail' => "Expires in " . ($ag['days_remaining']) . " days ({$expiryStr})",
                        'sm_kam' => $smKam,
                        'collection_kam' => $collKam,
                    ];
                }
            }

            // 2. Audit BTRC License Expiry (30 Days Threshold)
            if ($client->btrc_license_discontinuation_date) {
                $btrcDate = Carbon::parse($client->btrc_license_discontinuation_date);
                if ($btrcDate->isPast()) {
                    $btrcExpiries[] = [
                        'client_name' => $client->client_name,
                        'detail' => "Expired on " . $btrcDate->format('d M Y') . " (" . abs($today->diffInDays($btrcDate)) . "d ago)",
                        'sm_kam' => $smKam,
                        'collection_kam' => $collKam,
                    ];
                } elseif ($today->diffInDays($btrcDate) <= $thresholdDays) {
                    $btrcExpiries[] = [
                        'client_name' => $client->client_name,
                        'detail' => "Expiring in " . $today->diffInDays($btrcDate) . " days (" . $btrcDate->format('d M Y') . ")",
                        'sm_kam' => $smKam,
                        'collection_kam' => $collKam,
                    ];
                }
            }

            // 3. Audit Data Discrepancies for Active Clients
            if ($client->client_status === 'Active' || empty($client->client_status)) {
                // Missing KAMs
                $missingRoles = [];
                if (!$client->collection_kam_id && empty(trim($client->collection_kam ?? ''))) $missingRoles[] = 'Collection KAM';
                if (!$client->sm_kam_id && empty(trim($client->sm_kam ?? ''))) $missingRoles[] = 'Sales KAM';
                if (!$client->nttn_billing_kam_id && empty(trim($client->nttn_billing_kam ?? ''))) $missingRoles[] = 'NTTN Billing KAM';
                if (!$client->iig_itc_billing_kam_id && empty(trim($client->iig_itc_billing_kam ?? ''))) $missingRoles[] = 'IIG/ITC Billing KAM';

                if (count($missingRoles) >= 2) {
                    $unassignedKams[] = [
                        'client_name' => $client->client_name,
                        'category' => 'Unassigned KAM',
                        'detail' => "Missing " . implode(', ', $missingRoles),
                        'sm_kam' => $smKam,
                        'collection_kam' => $collKam,
                    ];
                }

                // Missing OPUS Code
                if (empty(trim($client->opus_id ?? ''))) {
                    $missingOpusIds[] = [
                        'client_name' => $client->client_name,
                        'category' => 'Missing OPUS ID',
                        'detail' => "OPUS ID is empty or missing",
                        'sm_kam' => $smKam,
                        'collection_kam' => $collKam,
                    ];
                }

                // Barring status mismatch
                if ($client->barring_percentage > 0 && !$client->barred_at) {
                    $barringDiscrepancies[] = [
                        'client_name' => $client->client_name,
                        'category' => 'Barring State Mismatch',
                        'detail' => "Barred {$client->barring_percentage}% but barred_at is null",
                        'sm_kam' => $smKam,
                        'collection_kam' => $collKam,
                    ];
                }

                // Missing commencement dates
                if (empty($client->nttn_billing_commencement_date) && empty($client->iig_itc_billing_commencement_date)) {
                    $missingBillingDates[] = [
                        'client_name' => $client->client_name,
                        'category' => 'Missing Commencement Date',
                        'detail' => "Missing NTTN/IIG billing commencement dates",
                        'sm_kam' => $smKam,
                        'collection_kam' => $collKam,
                    ];
                }
            }
        }

        return [
            'expired_agreements' => $expiredAgreements,
            'urgent_agreements' => $urgentAgreements,
            'btrc_expiries' => $btrcExpiries,
            'unassigned_kams' => $unassignedKams,
            'missing_opus_ids' => $missingOpusIds,
            'barring_discrepancies' => $barringDiscrepancies,
            'missing_billing_dates' => $missingBillingDates,
        ];
    }

    public function compileTextDocument(array $audit): string
    {
        $monthLabel = Carbon::now()->format('F Y');

        $doc = "## 🧹 Housekeeping & Data Integrity Report — {$monthLabel}\n";
        $doc .= "System-wide audit summary for service agreements, BTRC compliance (<= 30 days expiry), and portfolio data integrity.\n\n";

        // Section 1: Agreement Expiries & Renewals (Expired or <= 30 Days)
        $doc .= "## 📜 AGREEMENT RENEWAL WATCHLIST (Expired & <= 30 Days)\n";
        $agreementsList = array_merge($audit['expired_agreements'], $audit['urgent_agreements']);

        if (!empty($agreementsList)) {
            $doc .= "| Client Name | License | Expiry Status | S&M KAM | Collection KAM |\n";
            $doc .= "|---|---|---|---|---|\n";
            foreach ($agreementsList as $item) {
                $doc .= "| **{$item['client_name']}** | {$item['license']} | {$item['detail']} | {$item['sm_kam']} | {$item['collection_kam']} |\n";
            }
        } else {
            $doc .= "* All service agreements are compliant. No expiries detected within 30 days.\n";
        }
        $doc .= "\n";

        // Section 2: BTRC License Watchlist (Expired & <= 30 Days)
        $doc .= "## 🏛️ BTRC LICENSE EXPIRY WATCHLIST (Expired & <= 30 Days)\n";
        if (!empty($audit['btrc_expiries'])) {
            $doc .= "| Client Name | BTRC Expiry Detail | S&M KAM | Collection KAM |\n";
            $doc .= "|---|---|---|---|\n";
            foreach ($audit['btrc_expiries'] as $item) {
                $doc .= "| **{$item['client_name']}** | {$item['detail']} | {$item['sm_kam']} | {$item['collection_kam']} |\n";
            }
        } else {
            $doc .= "* All BTRC licenses are compliant. No discontinuation dates detected within 30 days.\n";
        }
        $doc .= "\n";

        // Section 3: Data Discrepancy Audits
        $doc .= "## ⚠️ CLIENT DATA DISCREPANCY AUDITS\n";
        $discrepancyList = array_merge(
            $audit['unassigned_kams'],
            $audit['missing_opus_ids'],
            $audit['barring_discrepancies'],
            $audit['missing_billing_dates']
        );

        if (!empty($discrepancyList)) {
            $doc .= "| Client Name | Issue Category | Discrepancy Detail | S&M KAM | Collection KAM |\n";
            $doc .= "|---|---|---|---|---|\n";
            foreach ($discrepancyList as $item) {
                $doc .= "| **{$item['client_name']}** | {$item['category']} | {$item['detail']} | {$item['sm_kam']} | {$item['collection_kam']} |\n";
            }
        } else {
            $doc .= "* Clean audit. No critical portfolio data discrepancies found.\n";
        }

        return $doc;
    }

    public function generateAndStoreReport(): HousekeepingNotification
    {
        HousekeepingNotification::ensureTableExists();

        $audit = $this->runFullAudit();
        $docContent = $this->compileTextDocument($audit);

        $expiredCount = count($audit['expired_agreements']);
        $urgentCount = count($audit['urgent_agreements']);
        $btrcCount = count($audit['btrc_expiries']);
        $discrepancyCount = count($audit['unassigned_kams']) + 
                            count($audit['missing_opus_ids']) + 
                            count($audit['barring_discrepancies']) + 
                            count($audit['missing_billing_dates']);

        $totalCount = $expiredCount + $urgentCount + $btrcCount + $discrepancyCount;
        $recipients = config('housekeeping.recipients', []);

        return HousekeepingNotification::create([
            'report_month' => Carbon::now()->format('Y-m'),
            'total_issues_count' => $totalCount,
            'expired_agreements_count' => $expiredCount,
            'urgent_renewals_count' => $urgentCount,
            'btrc_license_issues_count' => $btrcCount,
            'data_discrepancies_count' => $discrepancyCount,
            'document_content' => $docContent,
            'recipient_emails' => $recipients,
        ]);
    }

    public function emailReportToGroup(HousekeepingNotification $notification): void
    {
        $recipients = config('housekeeping.recipients', []);
        if (empty($recipients)) return;

        try {
            Mail::to($recipients)->send(new HousekeepingReportMail($notification));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Housekeeping email dispatch failed: ' . $e->getMessage());
        }
    }
}
