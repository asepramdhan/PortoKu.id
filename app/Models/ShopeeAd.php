<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopeeAd extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_published' => 'boolean',
    ];
}
