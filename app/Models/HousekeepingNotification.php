<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HousekeepingNotification extends Model
{
    protected $table = 'housekeeping_notifications';

    protected $fillable = [
        'report_month',
        'total_issues_count',
        'expired_agreements_count',
        'urgent_renewals_count',
        'btrc_license_issues_count',
        'data_discrepancies_count',
        'document_content',
        'recipient_emails',
    ];

    protected function casts(): array
    {
        return [
            'recipient_emails' => 'array',
            'total_issues_count' => 'integer',
            'expired_agreements_count' => 'integer',
            'urgent_renewals_count' => 'integer',
            'btrc_license_issues_count' => 'integer',
            'data_discrepancies_count' => 'integer',
        ];
    }

    public static function ensureTableExists(): void
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('housekeeping_notifications')) {
                \Illuminate\Support\Facades\Schema::create('housekeeping_notifications', function ($table) {
                    $table->id();
                    $table->string('report_month', 7);
                    $table->integer('total_issues_count')->default(0);
                    $table->integer('expired_agreements_count')->default(0);
                    $table->integer('urgent_renewals_count')->default(0);
                    $table->integer('btrc_license_issues_count')->default(0);
                    $table->integer('data_discrepancies_count')->default(0);
                    $table->mediumText('document_content');
                    $table->json('recipient_emails')->nullable();
                    $table->timestamps();
                });
            }
        } catch (\Throwable $e) {
            // Ignore schema errors
        }
    }

    public static function latestDocument(): ?self
    {
        self::ensureTableExists();
        try {
            $latest = self::query()->latest('id')->first();
            if (!$latest) {
                $service = app(\App\Services\HousekeepingAuditService::class);
                $latest = $service->generateAndStoreReport();
            }
            return $latest;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getFormattedHtmlContentAttribute(): string
    {
        $content = e($this->document_content);

        // Render bold tags **text**
        $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);

        $lines = explode("\n", $content);
        $inTable = false;
        $htmlOutput = [];
        $tableRows = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (str_starts_with($trimmed, '|')) {
                // Table header separator line: | --- | --- |
                if (preg_match('/^\|(?:\s*---+\s*\|)+$/', $trimmed)) {
                    continue;
                }

                $cells = array_map('trim', explode('|', trim($trimmed, '|')));
                
                if (!$inTable) {
                    $inTable = true;
                    $tableRows = [];
                    $headerCols = [];
                    foreach ($cells as $cell) {
                        $headerCols[] = '<th style="padding:8px 10px; text-align:left; font-weight:800; color:var(--muted); font-size:11.5px; text-transform:uppercase; background:#f8fafc; border-bottom:2px solid var(--line);">' . $cell . '</th>';
                    }
                    $tableRows[] = '<div style="overflow-x:auto; margin:10px 0 16px;"><table style="width:100%; border-collapse:collapse; font-size:12.5px; border:1px solid var(--line); background:#ffffff;"><thead><tr>' . implode('', $headerCols) . '</tr></thead><tbody>';
                } else {
                    $rowCols = [];
                    foreach ($cells as $cell) {
                        $rowCols[] = '<td style="padding:7px 10px; color:var(--ink); border-bottom:1px solid var(--line);">' . $cell . '</td>';
                    }
                    $tableRows[] = '<tr>' . implode('', $rowCols) . '</tr>';
                }
            } else {
                if ($inTable) {
                    $tableRows[] = '</tbody></table></div>';
                    $htmlOutput[] = implode('', $tableRows);
                    $tableRows = [];
                    $inTable = false;
                }

                if ($trimmed === '') {
                    continue;
                }

                if (str_starts_with($trimmed, '### ')) {
                    $title = mb_substr($trimmed, 4);
                    $htmlOutput[] = '<h4 style="margin:14px 0 6px; color:var(--primary-dark); font-size:14px; font-weight:800;">' . $title . '</h4>';
                } elseif (str_starts_with($trimmed, '## ')) {
                    $title = mb_substr($trimmed, 3);
                    $isFirst = empty($htmlOutput);
                    $marginTop = $isFirst ? '0' : '18px';
                    $htmlOutput[] = '<h3 style="margin:' . $marginTop . ' 0 8px; color:var(--ink); font-size:15px; font-weight:800; border-bottom:1px solid var(--line); padding-bottom:4px;">' . $title . '</h3>';
                } elseif (str_starts_with($trimmed, '* ')) {
                    $item = mb_substr($trimmed, 2);
                    $htmlOutput[] = '<ul style="margin:4px 0 10px; padding-left:20px;"><li style="margin-bottom:4px;">' . $item . '</li></ul>';
                } else {
                    $htmlOutput[] = '<p style="margin:0 0 10px; line-height:1.6;">' . $line . '</p>';
                }
            }
        }

        if ($inTable) {
            $tableRows[] = '</tbody></table></div>';
            $htmlOutput[] = implode('', $tableRows);
        }

        return implode("\n", $htmlOutput);
    }

    public function getReportMonthLabelAttribute(): string
    {
        try {
            return \Carbon\Carbon::parse($this->report_month . '-01')->format('F Y');
        } catch (\Throwable $e) {
            return $this->report_month;
        }
    }
}
