<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Waiver extends Model
{
    protected $table = 'waivers';

    protected $fillable = [
        'client_id',
        'applicable_from_date',
        'waiver_amount',
        'subtract_from_next_month_os',
        'breakdown_config',
        'description',
        'attachment_path',
        'attachment_name',
        'created_by',
    ];

    protected $casts = [
        'applicable_from_date' => 'date',
        'waiver_amount' => 'decimal:2',
        'subtract_from_next_month_os' => 'boolean',
        'breakdown_config' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }
}
