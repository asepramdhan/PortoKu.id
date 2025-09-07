<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebApp extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'tags' => 'array',
        'is_published' => 'boolean',
    ];
}
