<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionLog extends Model
{
    protected $table = 'collection_logs';

    const UPDATED_AT = null;

    protected $fillable = [
        'collection_id',
        'client_id',
        'action',
        'field_name',
        'old_value',
        'new_value',
        'performed_by',
        'created_at',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id', 'collection_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }
}
