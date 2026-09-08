<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Menambahkan kolom `uuid` (identifier stabil) ke sales, restocks, dan
 * expenses.
 *
 * LATAR BELAKANG:
 * Duplicate-protection sebelumnya hanya mencocokkan kombinasi kolom data
 * (product_id, quantity, price, total, sold_at, created_at, dst). Ini
 * bermasalah karena dua transaksi ASLI yang nilainya kebetulan identik bisa
 * salah dianggap duplicate. Kolom `uuid` memberi setiap baris identitas unik
 * yang dibuat SEKALI saat baris dibuat (lihat trait HasStableUuid), lalu
 * ikut disertakan di backup dan dipakai sebagai kunci pencocokan utama saat
 * restore — jadi restore berulang pada file backup yang sama tetap aman
 * tanpa perlu menebak dari kombinasi nilai data.
 *
 * KEAMANAN MIGRATION:
 * - Kolom dibuat NULLABLE (bukan not-null) supaya baris lama pada SQLite
 *   tidak perlu proses ALTER TABLE yang berat (SQLite/Doctrine DBAL tidak
 *   selalu mendukung mengubah kolom existing jadi NOT NULL dengan mulus).
 * - Backfill dilakukan di migration ini juga, jadi setelah migrate, SEMUA
 *   baris (lama maupun baru) sudah punya uuid.
 * - Baris baru akan otomatis mendapat uuid dari Model (lihat
 *   App\Models\Concerns\HasStableUuid), bukan dari default database,
 *   supaya kompatibel dengan SQLite tanpa fungsi UUID bawaan.
 * - `migrate:fresh` tetap aman: tabel dibuat kosong lalu backfill loop di
 *   bawah otomatis tidak memproses apa pun (tidak ada baris lama).
 * - Unique index mengizinkan banyak NULL sekaligus (perilaku standar SQLite
 *   & MySQL), jadi tidak akan gagal walau backfill berjalan bertahap.
 */
return new class extends Migration
{
    private array $tables = ['sales', 'restocks', 'expenses'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('uuid', 36)->nullable()->after('id');
            });
        }

        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->unique('uuid', $table.'_uuid_unique');
            });
        }

        // Backfill baris lama yang belum punya uuid (dibuat sebelum kolom
        // ini ada). Dilakukan per-baris (bukan satu query massal) supaya
        // setiap baris mendapat uuid yang benar-benar unik.
        foreach ($this->tables as $table) {
            DB::table($table)->whereNull('uuid')->orderBy('id')->select('id')
                ->chunkById(500, function ($rows) use ($table) {
                    foreach ($rows as $row) {
                        DB::table($table)->where('id', $row->id)->update([
                            'uuid' => (string) Str::uuid(),
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropUnique($table.'_uuid_unique');
                $blueprint->dropColumn('uuid');
            });
        }
    }
};
