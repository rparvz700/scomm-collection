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
        'team_name',
        'collection_kam',
        'collection_supervisor',
        'nttn_billing_kam',
        'iig_itc_billing_kam',
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

}
