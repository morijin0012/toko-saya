<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Memberi setiap baris identifier stabil (`uuid`) yang dibuat SEKALI saat
 * baris pertama kali disimpan, dan tidak pernah berubah setelahnya.
 *
 * Tujuannya murni untuk duplicate-protection saat backup/restore (lihat
 * migration 2026_09_03_110000_add_uuid_to_transaction_tables.php) — BUKAN
 * primary key, BUKAN pengganti `id`. Semua relasi & query lain tetap
 * memakai `id` seperti biasa; `uuid` hanya dibaca/ditulis oleh proses
 * backup dan restore.
 *
 * Saat restore dari file backup, uuid ASLI dari baris backup dipakai
 * kembali (di-set manual sebelum save()), sehingga trait ini sengaja tidak
 * menimpa uuid yang sudah diisi.
 */
trait HasStableUuid
{
    public static function bootHasStableUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
