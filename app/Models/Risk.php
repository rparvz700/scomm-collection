<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Risk extends Model
{
    protected $table = 'risk';

    protected $primaryKey = 'risk_id';

    const UPDATED_AT = null;

    protected $fillable = [
        'client_id',
        'risk_event_datetime',
        'event_type',
        'outstanding_balance',
        'mrc_amount',
        'cr_value',
        'rating_category',
        'legal',
        'barring_priority',
        'remarks',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'risk_event_datetime' => 'datetime',
            'outstanding_balance' => 'decimal:2',
            'mrc_amount' => 'decimal:2',
            'cr_value' => 'decimal:2',
            'legal' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }
}
