<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCookie extends Model
{
    protected $guarded = ['id'];

    // relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // relasi ke message
    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
