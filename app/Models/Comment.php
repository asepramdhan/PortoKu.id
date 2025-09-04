<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    // Relasi ke komentar induknya (sebuah balasan hanya punya satu induk)
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    // Relasi ke balasannya (sebuah komentar bisa punya banyak balasan)
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->latest();
    }
}
