<?php

namespace App\Models;

use App\Models\Concerns\HasStableUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * `uuid`: identifier stabil, dibuat sekali saat Product pertama kali
 * disimpan (lihat App\Models\Concerns\HasStableUuid) dan tidak pernah
 * berubah setelahnya — sama seperti pada Sale/Restock/Expense. Dipakai
 * sebagai kunci pencocokan utama saat backup/restore (lihat
 * DataController::resolveProductId()) supaya restore tetap tertaut ke
 * Product yang benar walau `id`-nya berubah/dipakai ulang atau namanya
 * diedit setelah backup dibuat. BUKAN primary key, BUKAN pengganti `id` —
 * semua relasi & route model binding tetap memakai `id` seperti biasa.
 */
class Product extends Model
{
    use HasStableUuid;

    protected $fillable = [
        'name',
        'price',
        'stock',
    ];

    public function restocks(): HasMany
    {
        return $this->hasMany(Restock::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
