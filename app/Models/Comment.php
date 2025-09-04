<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    protected $guarded = ['id'];

    // Definisikan relasi: Komentar ini milik siapa
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Definisikan relasi: Komentar ini untuk postingan mana
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
