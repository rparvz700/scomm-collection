<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Client extends Model
{
    protected $table = 'client';

    protected $primaryKey = 'client_id';

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
        'other_upstream',
        'sm_kam',
        'sm_kam_id',
        'team_name',
        'collection_kam',
        'collection_kam_id',
        'collection_supervisor',
        'collection_supervisor_id',
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
            'legal' => 'boolean',
            'other_upstream' => 'boolean',
            'btrc_license_discontinuation_date' => 'date',
            'service_discontinuation_date' => 'date',
            'nttn_billing_commencement_date' => 'date',
            'iig_itc_billing_commencement_date' => 'date',
            'barred_at' => 'datetime',
        ];
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

        static::addGlobalScope('role_based_clients', function (\Illuminate\Database\Eloquent\Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();
                
                // Admin, Supervisors, and Dashboard routes bypass scoping
                if ($user->hasRole('admin') || $user->hasRole('collection_supervisor') || $user->hasRole('collection_hod') || request()->routeIs('dashboard*') || request()->is('dashboard*')) {
                    return;
                }

                $builder->where(function ($query) use ($user) {
                    // Condition 1: Completely untagged (visible to everyone)
                    $query->whereNull('collection_kam_id')
                          ->whereNull('nttn_billing_kam_id')
                          ->whereNull('iig_itc_billing_kam_id')
                          ->whereNull('sm_kam_id');

                    // Condition 2: Assigned specifically to the user's role
                    if ($user->hasRole('collection_kam')) {
                        $query->orWhere('collection_kam_id', $user->id);
                    }
                    if ($user->hasRole('nttn_billing_kam')) {
                        $query->orWhere('nttn_billing_kam_id', $user->id);
                    }
                    if ($user->hasRole('iig_itc_billing_kam')) {
                        $query->orWhere('iig_itc_billing_kam_id', $user->id);
                    }
                    if ($user->hasRole('sm_kam')) {
                        $query->orWhere('sm_kam_id', $user->id);
                    }
                });
            }
        });

        static::saving(function ($client) {
            if ($client->isDirty('client_status')) {
                if ($client->client_status === 'Barred') {
                    $client->barred_at = $client->barred_at ?: now();
                } else {
                    $client->barred_at = null;
                }
            }
        });

        static::updating(function ($client) {
            foreach ($client->getDirty() as $key => $newValue) {
                if ($key === 'updated_at') continue;

                $oldValue = $client->getOriginal($key);

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
