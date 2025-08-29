<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id'];

    /**
     * Relasi ke Financial Entries: Satu aset bisa memiliki banyak catatan keuangan.
     */
    public function financialEntries()
    {
        return $this->hasMany(FinancialEntry::class);
    }
}
