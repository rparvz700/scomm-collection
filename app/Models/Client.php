<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Client extends Model
{
    use \App\Traits\HasTeamScope;

    protected $table = 'client';

    protected $primaryKey = 'client_id';

    protected $appends = [
        'formatted_security_coverage',
        'overall_agreement_health',
    ];

    protected $fillable = [
        'opus_id',
        'client_name',
        'client_status',
        'barred_at',
        'barring_percentage',
        'barring_workflow_status',
        'agreement_status',
        'barring_priority',
        'btrc_license_discontinuation_date',
        'legal',
        'service_discontinuation_date',
        'billing_modality_kpi',
        'service_type_billing',
        'license_billing',
        'btrc_letter',
        'security_coverage',
        'payment_plan',
        'deferred_period',
        'other_upstream',
        'sm_kam',
        'sm_kam_id',
        'team_name',
        'sm_supervisor',
        'sm_supervisor_id',
        'collection_kam',
        'collection_kam_id',
        'collection_supervisor',
        'collection_supervisor_id',
        'collection_team_name',
        'nttn_billing_kam',
        'nttn_billing_kam_id',
        'iig_itc_billing_kam',
        'iig_itc_billing_kam_id',
        'nttn_billing_commencement_date',
        'iig_itc_billing_commencement_date',
    ];

    protected function casts(): array
    {
        return [
            'agreement_status' => 'array',
            'other_upstream' => 'boolean',
            'deferred_period' => 'integer',
            'barring_percentage' => 'float',
            'btrc_license_discontinuation_date' => 'date',
            'service_discontinuation_date' => 'date',
            'nttn_billing_commencement_date' => 'date',
            'iig_itc_billing_commencement_date' => 'date',
            'barred_at' => 'datetime',
        ];
    }

    public function getAgreementStatusAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && !empty($value)) {
            $decoded = json_decode($value, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            return is_array($decoded) ? $decoded : $value;
        }
        return $value;
    }

    public function getFormattedSecurityCoverageAttribute(): string
    {
        $val = $this->attributes['security_coverage'] ?? null;
        if ($val === null || $val === '' || $val === '#DIV/0!') {
            return (string) ($val ?? '');
        }
        $clean = trim((string) $val);
        if (str_ends_with($clean, '%')) {
            return $clean;
        }
        if (is_numeric($clean)) {
            $num = (float) $clean;
            if ($num >= 0 && $num <= 1) {
                $pct = round($num * 100, 2);
                return ($pct == (int)$pct ? (int)$pct : $pct) . '%';
            }
            return (round($num, 2) == (int)round($num, 2) ? (int)round($num, 2) : round($num, 2)) . '%';
        }
        return $clean;
    }

    /**
     * Dynamically evaluate status per license entry on-the-fly (zero stale state)
     */
    public function getEvaluatedAgreementsAttribute(): array
    {
        $items = $this->agreement_status;
        if (is_string($items)) {
            $items = json_decode($items, true);
        }
        if (!is_array($items)) {
            $items = [];
        }

        $today = \Carbon\Carbon::today();

        return array_values(array_filter(array_map(function ($item) use ($today) {
            if (!is_array($item) || empty($item['license_name'])) {
                return null;
            }

            $expiry = !empty($item['expiry_date']) ? \Carbon\Carbon::parse($item['expiry_date']) : null;
            
            if (!$expiry) {
                $status = 'unknown';
                $badge = 'gray';
                $daysLeft = null;
                $label = 'No Expiry Set';
            } elseif ($expiry->isPast()) {
                $status = 'expired';
                $badge = 'danger';
                $daysLeft = -$today->diffInDays($expiry);
                $label = 'Expired (' . abs($daysLeft) . 'd ago)';
            } else {
                $daysLeft = $today->diffInDays($expiry);
                if ($daysLeft <= 30) {
                    $status = 'urgent_renewal';
                    $badge = 'danger';
                    $label = 'Renewal Due (' . $daysLeft . 'd left)';
                } elseif ($daysLeft <= 60) {
                    $status = 'upcoming_renewal';
                    $badge = 'warning';
                    $label = 'Renewal Upcoming (' . $daysLeft . 'd left)';
                } else {
                    $status = 'valid';
                    $badge = 'success';
                    $label = 'Valid (' . $daysLeft . 'd left)';
                }
            }

            return [
                'license_name' => $item['license_name'],
                'signing_date' => $item['signing_date'] ?? null,
                'expiry_date' => $item['expiry_date'] ?? null,
                'status' => $status,
                'badge' => $badge,
                'days_remaining' => $daysLeft,
                'label' => $label,
                'ref_status' => $item['status'] ?? $item['ref_no'] ?? null,
            ];
        }, $items)));
    }

    /**
     * Overall calculated agreement status
     */
    public function getOverallAgreementHealthAttribute(): string
    {
        $evaluated = $this->evaluated_agreements;
        if (empty($evaluated)) return 'No Record';

        $statuses = array_column($evaluated, 'status');
        if (in_array('expired', $statuses)) return 'Expired';
        if (in_array('urgent_renewal', $statuses)) return 'Renewal Due (30 Days)';
        if (in_array('upcoming_renewal', $statuses)) return 'Renewal Upcoming (60 Days)';

        return 'Valid';
    }

    public function growthTrend()
    {
        return $this->hasOne(ClientGrowthTrend::class, 'client_id', 'client_id');
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class, 'client_id', 'client_id');
    }
    public function risks(): HasMany
    {
        return $this->hasMany(Risk::class, 'client_id', 'client_id');
    }
    public function logs(): HasMany
    {
        return $this->hasMany(ClientLog::class, 'client_id', 'client_id');
    }

    public function monthlySummaries(): HasMany
    {
        return $this->hasMany(MonthlySummary::class, 'client_id', 'client_id');
    }

    public function latestSummary()
    {
        return $this->hasOne(MonthlySummary::class, 'client_id', 'client_id')->latestOfMany('summary_month');
    }

    public function collectionKamUser()
    {
        return $this->belongsTo(User::class, 'collection_kam_id', 'id');
    }

    public function collectionSupervisorUser()
    {
        return $this->belongsTo(User::class, 'collection_supervisor_id', 'id');
    }

    public function nttnBillingKamUser()
    {
        return $this->belongsTo(User::class, 'nttn_billing_kam_id', 'id');
    }

    public function iigItcBillingKamUser()
    {
        return $this->belongsTo(User::class, 'iig_itc_billing_kam_id', 'id');
    }

    public function smKamUser()
    {
        return $this->belongsTo(User::class, 'sm_kam_id', 'id');
    }

    public static function boot()
    {
        parent::boot();

        static::saving(function ($client) {
            if ($client->isDirty('barring_percentage') || $client->isDirty('client_status')) {
                if ($client->barring_percentage > 0) {
                    $client->barred_at = $client->barred_at ?: now();
                } else {
                    $client->barred_at = null;
                }

                if ($client->barring_percentage >= 100) {
                    $client->client_status = 'Discontinued';
                } elseif ($client->client_status === 'Barred' || strtolower($client->client_status ?? '') === 'inactive') {
                    $client->client_status = 'Active';
                }
            }
        });

        static::updating(function ($client) {
            foreach ($client->getDirty() as $key => $newValue) {
                if ($key === 'updated_at') continue;

                $oldValue = $client->getOriginal($key);

                if (is_array($oldValue)) {
                    $oldValue = json_encode($oldValue);
                }
                if (is_array($newValue)) {
                    $newValue = json_encode($newValue);
                }
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
                    'client_id' => $client->client_id,
                    'field_name' => $key,
                    'old_value' => $oldValue === null ? null : (string) $oldValue,
                    'new_value' => $newValue === null ? null : (string) $newValue,
                    'updated_by' => auth()->user()?->email ?? 'System',
                ]);
            }
        });
    }
}
