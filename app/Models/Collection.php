<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Collection extends Model
{
    protected $table = 'collection';

    protected $primaryKey = 'collection_id';

    const UPDATED_AT = null;

    protected $fillable = [
        'client_id',
        'collection_datetime',
        'collection_month',
        'collection_type',
        'collection_amount',
        'remarks',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'collection_datetime' => 'datetime',
            'collection_month' => 'date',
            'collection_amount' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }
}
