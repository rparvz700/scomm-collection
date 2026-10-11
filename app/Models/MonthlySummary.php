<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\HasClientSnapshot;

class MonthlySummary extends Model
{
    use HasClientSnapshot;

    protected $table = 'monthly_summary';

    protected $primaryKey = 'monthly_summary_id';

    const UPDATED_AT = null;

    protected $guarded = [
        'monthly_summary_id',
    ];

    protected function casts(): array
    {
        return [
            'summary_month' => 'date',
            'client_payment_commitment_date' => 'date',
            'client_btrc_license_discontinuation_date' => 'date',
            'client_service_discontinuation_date' => 'date',
            'client_nttn_billing_commencement_date' => 'date',
            'client_iig_itc_billing_commencement_date' => 'date',
            'client_legal' => 'boolean',
            'client_other_upstream' => 'boolean',
            'client_barred_at' => 'datetime',
            'client_barring_percentage' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            foreach ($model->getDirty() as $key => $newValue) {
                if ($key === 'updated_at' || $key === 'created_at') continue;

                $oldValue = $model->getOriginal($key);

                if (is_bool($oldValue)) {
                    $oldValue = $oldValue ? 'Yes' : 'No';
                }
                if (is_bool($newValue)) {
                    $newValue = $newValue ? 'Yes' : 'No';
                }
                if ($oldValue instanceof \DateTimeInterface) {
                    $oldValue = $oldValue->format('Y-m-d H:i:s');
                }
                if ($newValue instanceof \DateTimeInterface) {
                    $newValue = $newValue->format('Y-m-d H:i:s');
                }

                if ((string)$oldValue === (string)$newValue) continue;

                \App\Models\ClientLog::create([
                    'client_id' => $model->client_id,
                    'field_name' => $model->getTable() . '.' . $key,
                    'old_value' => $oldValue === null ? null : (string) $oldValue,
                    'new_value' => $newValue === null ? null : (string) $newValue,
                    'updated_by' => auth()->user()?->email ?? 'System',
                ]);
            }
        });
    }

    public static function getDeferredMetrics($client, string $summaryMonthDate, float $currentLatestOs, float $currentMrc, float $currentCollection = 0.0): array
    {
        $clientId = is_object($client) ? $client->client_id : $client;
        $deferredDays = (int) (is_object($client) ? ($client->deferred_period ?? 0) : (\App\Models\Client::where('client_id', $clientId)->value('deferred_period') ?? 0));

        if ($deferredDays <= 0) {
            return [
                'deferred_opening_os' => $currentLatestOs,
                'deferred_closing_os' => $currentLatestOs,
                'deferred_os'         => $currentLatestOs,
                'maturity_mrc'        => $currentMrc,
            ];
        }

        $lookbackMonths = (int) round($deferredDays / 30.4167);
        if ($lookbackMonths <= 0) {
            $lookbackMonths = 1;
        }

        $lookbackMonthDate = \Carbon\Carbon::parse($summaryMonthDate)
            ->startOfMonth()
            ->subMonths($lookbackMonths)
            ->endOfMonth()
            ->toDateString();

        $lookbackSummary = self::where('client_id', $clientId)
            ->whereDate('summary_month', $lookbackMonthDate)
            ->first();

        if ($lookbackSummary) {
            $mrc = (float) $lookbackSummary->getRawOriginal('total_mrc');
            $deferredOpeningOs = (float) $lookbackSummary->getRawOriginal('total_latest_os');
            $deferredClosingOs = max(0.00, $deferredOpeningOs - $currentCollection);
            return [
                'deferred_opening_os' => $deferredOpeningOs,
                'deferred_closing_os' => $deferredClosingOs,
                'deferred_os'         => $deferredClosingOs,
                'maturity_mrc'        => $mrc > 0 ? $mrc : $currentMrc,
            ];
        }

        return [
            'deferred_opening_os' => $currentLatestOs,
            'deferred_closing_os' => $currentLatestOs,
            'deferred_os'         => $currentLatestOs,
            'maturity_mrc'        => $currentMrc,
        ];
    }

    public function getLatestCrAttribute($value)
    {
        $client = $this->relationLoaded('client') ? $this->client : \App\Models\Client::find($this->client_id);
        if ($client && (int) ($client->deferred_period ?? 0) > 0) {
            $monthDate = $this->summary_month ? (\Carbon\Carbon::parse($this->summary_month)->toDateString()) : '';
            $collection = (float) ($this->attributes['total_collection'] ?? ((float) ($this->attributes['total_opening_os'] ?? 0) - (float) ($this->attributes['total_latest_os'] ?? 0)));
            if ($collection < 0) {
                $collection = 0.0;
            }
            $metrics = self::getDeferredMetrics($client, $monthDate, (float) ($this->attributes['total_latest_os'] ?? 0), (float) ($this->attributes['total_mrc'] ?? 0), $collection);
            $maturityMrc = $metrics['maturity_mrc'];
            return $maturityMrc > 0 ? round($metrics['deferred_closing_os'] / $maturityMrc, 2) : 0.00;
        }
        return (float) $value;
    }

    public function getLatestRatingCategoryAttribute($value)
    {
        $client = $this->relationLoaded('client') ? $this->client : \App\Models\Client::find($this->client_id);
        if ($client && (int) ($client->deferred_period ?? 0) > 0) {
            return \App\Models\Collection::getRatingCategory($this->latest_cr);
        }
        return $value ?: 'Unknown';
    }

    public function getOpeningCrAttribute($value)
    {
        $client = $this->relationLoaded('client') ? $this->client : \App\Models\Client::find($this->client_id);
        if ($client && (int) ($client->deferred_period ?? 0) > 0) {
            $monthDate = $this->summary_month ? (\Carbon\Carbon::parse($this->summary_month)->toDateString()) : '';
            $metrics = self::getDeferredMetrics($client, $monthDate, (float) ($this->attributes['total_opening_os'] ?? 0), (float) ($this->attributes['total_mrc'] ?? 0), 0.0);
            $maturityMrc = $metrics['maturity_mrc'];
            return $maturityMrc > 0 ? round($metrics['deferred_opening_os'] / $maturityMrc, 2) : 0.00;
        }
        return (float) $value;
    }

    public function getOpeningRatingCategoryAttribute($value)
    {
        $client = $this->relationLoaded('client') ? $this->client : \App\Models\Client::find($this->client_id);
        if ($client && (int) ($client->deferred_period ?? 0) > 0) {
            return \App\Models\Collection::getRatingCategory($this->opening_cr);
        }
        return $value ?: 'Unknown';
    }

    public static function syncDeferredSummaries(): int
    {
        $deferredClients = \App\Models\Client::where('deferred_period', '>', 0)->get();
        $updatedCount = 0;

        foreach ($deferredClients as $client) {
            $summaries = self::where('client_id', $client->client_id)
                ->orderBy('summary_month', 'asc')
                ->get();

            foreach ($summaries as $summary) {
                $monthStr = $summary->summary_month ? (\Carbon\Carbon::parse($summary->summary_month)->toDateString()) : null;
                if (!$monthStr) continue;

                $openingOs = (float) $summary->getRawOriginal('total_opening_os');
                $latestOs = (float) $summary->getRawOriginal('total_latest_os');
                $currentMrc = (float) $summary->getRawOriginal('total_mrc');
                $collection = (float) ($summary->getRawOriginal('total_collection') ?? max(0, $openingOs - $latestOs));

                $deferredMetrics = self::getDeferredMetrics($client, $monthStr, $latestOs, $currentMrc, $collection);
                $defOpeningOs = $deferredMetrics['deferred_opening_os'];
                $defClosingOs = $deferredMetrics['deferred_closing_os'];
                $maturityMrc = $deferredMetrics['maturity_mrc'];

                $openingCr = $maturityMrc > 0 ? round($defOpeningOs / $maturityMrc, 2) : 0.00;
                $openingRating = \App\Models\Collection::getRatingCategory($openingCr);

                $latestCr = $maturityMrc > 0 ? round($defClosingOs / $maturityMrc, 2) : 0.00;
                $latestRating = \App\Models\Collection::getRatingCategory($latestCr);

                $summary->update([
                    'total_maturity' => $maturityMrc,
                    'opening_cr' => $openingCr,
                    'opening_rating_category' => $openingRating,
                    'latest_cr' => $latestCr,
                    'latest_rating_category' => $latestRating,
                ]);
                $updatedCount++;
            }
        }
        return $updatedCount;
    }
}
