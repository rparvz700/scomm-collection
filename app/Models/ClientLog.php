<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientLog extends Model
{
    protected $table = 'client_log';
    protected $primaryKey = 'client_log_id';

    const UPDATED_AT = null;

    protected $fillable = [
        'client_id',
        'field_name',
        'old_value',
        'new_value',
        'updated_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }
}
