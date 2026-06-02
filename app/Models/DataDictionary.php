<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataDictionary extends Model
{
    protected $table = 'data_dictionary';

    protected $primaryKey = 'data_dictionary_id';

    const UPDATED_AT = null;

    protected $fillable = [
        'table_name',
        'column_name',
        'business_name',
        'business_definition',
        'data_type',
        'source_type',
        'module_name',
        'remarks',
    ];
}
