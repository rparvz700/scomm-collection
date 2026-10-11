<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HousekeepingAuditService;

class GenerateHousekeepingReport extends Command
{
    protected $signature = 'housekeeping:generate-report';
    protected $description = 'Generate housekeeping document, store in database, and dispatch email report to recipient group';

    public function handle(HousekeepingAuditService $auditService): int
    {
        $this->info('Generating Housekeeping & Data Integrity Document...');
        
        $notification = $auditService->generateAndStoreReport();
        $this->info("Report generated successfully (ID: {$notification->id}, Issues: {$notification->total_issues_count}).");
        
        $auditService->emailReportToGroup($notification);
        $this->info('Email dispatched to recipient group.');

        return Command::SUCCESS;
    }
}
