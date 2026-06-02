<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlySummary extends Model
{
    protected $table = 'monthly_summary';

    protected $primaryKey = 'monthly_summary_id';

    const UPDATED_AT = null;

    protected $guarded = [
        'monthly_summary_id',
    ];

    protected function casts(): array
    {
        return [
            'summary_month' => 'date',
            'client_payment_commitment_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }
}
