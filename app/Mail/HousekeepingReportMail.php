<?php

namespace App\Mail;

use App\Models\HousekeepingNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class HousekeepingReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public HousekeepingNotification $notification;

    public function __construct(HousekeepingNotification $notification)
    {
        $this->notification = $notification;
    }

    public function build()
    {
        return $this->subject('🧹 SCOMM Collection Housekeeping & Data Integrity Report - ' . $this->notification->report_month_label)
                    ->view('emails.housekeeping_report');
    }
}
