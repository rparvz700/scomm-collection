<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientGrowthTrend extends Model
{
    protected $table = 'client_growth_trends';

    protected $fillable = [
        'client_id',
        'trend_status',
        'mrc_latest',
        'mrc_baseline_12m',
        'mrc_change_pct',
        'cr_latest',
        'cr_baseline_12m',
        'cr_change_val',
        'calculated_at',
    ];

    protected $casts = [
        'mrc_latest' => 'float',
        'mrc_baseline_12m' => 'float',
        'mrc_change_pct' => 'float',
        'cr_latest' => 'float',
        'cr_baseline_12m' => 'float',
        'cr_change_val' => 'float',
        'calculated_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }
}
