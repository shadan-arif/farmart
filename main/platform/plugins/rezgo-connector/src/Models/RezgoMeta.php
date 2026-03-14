<?php

namespace Botble\RezgoConnector\Models;

use Illuminate\Database\Eloquent\Model;

class RezgoMeta extends Model
{
    protected $table = 'rezgo_meta';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'meta_key',
        'meta_value',
    ];

    protected $casts = [
        'entity_id' => 'integer',
    ];
}
