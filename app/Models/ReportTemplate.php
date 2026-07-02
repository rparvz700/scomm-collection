<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportTemplate extends Model
{
    protected $table = 'report_templates';

    protected $fillable = [
        'name',
        'description',
        'query_config',
        'created_by',
    ];

    protected $casts = [
        'query_config' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
