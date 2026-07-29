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
            'client_barred_at' => 'datetime',
            'client_barring_percentage' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            foreach ($model->getDirty() as $key => $newValue) {
                if ($key === 'updated_at' || $key === 'created_at') continue;

                $oldValue = $model->getOriginal($key);

                if (is_bool($oldValue)) {
                    $oldValue = $oldValue ? 'Yes' : 'No';
                }
                if (is_bool($newValue)) {
                    $newValue = $newValue ? 'Yes' : 'No';
                }
                if ($oldValue instanceof \DateTimeInterface) {
                    $oldValue = $oldValue->format('Y-m-d H:i:s');
                }
                if ($newValue instanceof \DateTimeInterface) {
                    $newValue = $newValue->format('Y-m-d H:i:s');
                }

                if ((string)$oldValue === (string)$newValue) continue;

                \App\Models\ClientLog::create([
                    'client_id' => $model->client_id,
                    'field_name' => $model->getTable() . '.' . $key,
                    'old_value' => $oldValue === null ? null : (string) $oldValue,
                    'new_value' => $newValue === null ? null : (string) $newValue,
                    'updated_by' => auth()->user()?->email ?? 'System',
                ]);
            }
        });
    }
}
