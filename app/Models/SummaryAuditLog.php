<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SummaryAuditLog extends Model
{
    protected $table = 'summary_audit_logs';

    protected $fillable = [
        'summary_type',
        'summary_id',
        'client_id',
        'field_name',
        'field_label',
        'old_value',
        'new_value',
        'summary_month',
        'user_id',
        'updated_by',
        'ip_address',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
