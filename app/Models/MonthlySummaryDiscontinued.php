<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\HasClientSnapshot;

class MonthlySummaryDiscontinued extends Model
{
    use HasClientSnapshot;

    protected $table = 'monthly_summary_discontinued';

    protected $primaryKey = 'monthly_summary_discontinued_id';

    protected $guarded = [
        'monthly_summary_discontinued_id',
    ];

    protected function casts(): array
    {
        return [
            'summary_month' => 'date',
            'nttn_discontinuation_date' => 'date',
            'iig_itc_discontinuation_date' => 'date',
            'client_btrc_license_discontinuation_date' => 'date',
            'client_service_discontinuation_date' => 'date',
            'client_nttn_billing_commencement_date' => 'date',
            'client_iig_itc_billing_commencement_date' => 'date',
            'client_legal' => 'boolean',
            'client_other_upstream' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }
}
