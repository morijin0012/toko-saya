<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Menambahkan kolom `uuid` (identifier stabil) ke products.
 *
 * LATAR BELAKANG:
 * sales/restocks/expenses sudah punya uuid stabil (lihat migration
 * 2026_09_03_110000_add_uuid_to_transaction_tables.php) untuk
 * duplicate-protection saat backup/restore. Product sendiri BELUM punya
 * uuid — backup/restore selama ini hanya mengandalkan `id` + `name` untuk
 * mencocokkan Product antar-database, yang berisiko salah tempel jika:
 *   - `id` produk berubah (mis. restore ke database kosong / berbeda), atau
 *   - `id` lama dipakai ulang oleh produk yang sama sekali berbeda, atau
 *   - nama produk diedit setelah backup dibuat.
 * Kolom `uuid` ini memberi setiap Product identitas yang stabil dan tidak
 * pernah berubah/dipakai ulang, dipakai sebagai kunci pencocokan UTAMA saat
 * restore (lihat DataController::resolveProductId()), dengan id+name tetap
 * dipertahankan sebagai fallback untuk file backup lama yang belum
 * menyertakan product uuid sama sekali.
 *
 * KEAMANAN MIGRATION (pola yang sama seperti migration uuid transaksi):
 * - Kolom dibuat NULLABLE, bukan not-null, supaya baris lama pada SQLite
 *   tidak perlu ALTER TABLE yang berat.
 * - Backfill dilakukan di migration ini juga (per-baris, memakai chunkById)
 *   supaya setiap Product yang sudah ada sebelum kolom ini dibuat tetap
 *   mendapat uuid yang benar-benar unik, bukan nilai kosong/duplikat.
 * - Baris baru otomatis mendapat uuid dari Model (lihat
 *   App\Models\Concerns\HasStableUuid, sekarang juga dipakai oleh
 *   App\Models\Product), bukan dari default database, supaya kompatibel
 *   dengan SQLite tanpa fungsi UUID bawaan.
 * - `migrate:fresh` tetap aman: tabel dibuat kosong sehingga backfill loop
 *   di bawah otomatis tidak memproses apa pun.
 * - Unique index mengizinkan banyak NULL sekaligus (perilaku standar
 *   SQLite & MySQL), jadi tidak akan gagal walau backfill berjalan bertahap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('uuid', 36)->nullable()->after('id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unique('uuid', 'products_uuid_unique');
        });

        // Backfill Product lama yang belum punya uuid (dibuat sebelum
        // kolom ini ada).
        DB::table('products')->whereNull('uuid')->orderBy('id')->select('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('products')->where('id', $row->id)->update([
                        'uuid' => (string) Str::uuid(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_uuid_unique');
            $table->dropColumn('uuid');
        });
    }
};
