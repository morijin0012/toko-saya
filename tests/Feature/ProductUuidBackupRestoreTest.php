<?php

use App\Models\Product;
use App\Models\Restock;
use App\Models\Sale;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Helper: buat file upload "backup_file" palsu berisi konten JSON tertentu.
 * (Duplikat kecil dari helper di BackupRestoreTest.php — Pest tidak berbagi
 * helper antar file test secara otomatis kecuali didaftarkan global.)
 */
function fakeUuidBackupFile(array $payload): UploadedFile
{
    return UploadedFile::fake()->createWithContent('backup.json', json_encode($payload));
}

// -----------------------------------------------------------------------
// 1) Stable UUID Product
// -----------------------------------------------------------------------

test('product baru otomatis mendapat uuid yang stabil', function () {
    $product = Product::create(['name' => 'Kopi Sachet', 'price' => 2000, 'stock' => 50]);

    expect($product->uuid)->not->toBeNull();
    expect(Str::isUuid($product->uuid))->toBeTrue();
});

test('uuid product tidak berubah setelah product diupdate', function () {
    $product = Product::create(['name' => 'Teh Botol', 'price' => 5000, 'stock' => 10]);
    $originalUuid = $product->uuid;

    $product->update(['name' => 'Teh Botol Besar', 'price' => 6000, 'stock' => 15]);

    expect($product->fresh()->uuid)->toBe($originalUuid);
});

test('dua product berbeda tidak pernah mendapat uuid yang sama', function () {
    $a = Product::create(['name' => 'Produk A', 'price' => 1000, 'stock' => 1]);
    $b = Product::create(['name' => 'Produk B', 'price' => 1000, 'stock' => 1]);

    expect($a->uuid)->not->toBe($b->uuid);
});

// -----------------------------------------------------------------------
// 2) Backup/restore berbasis Product UUID
// -----------------------------------------------------------------------

test('backup menyertakan uuid product di snapshot dan product_uuid di tiap baris sales', function () {
    $product = Product::create(['name' => 'Kopi Sachet', 'price' => 2000, 'stock' => 50]);

    $sale = Sale::create([
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 2000,
        'total' => 2000,
        'sold_at' => '2026-01-05',
    ]);
    $sale->timestamps = false;
    $sale->created_at = '2026-01-05 10:00:00';
    $sale->updated_at = '2026-01-05 10:00:00';
    $sale->save();

    $response = $this->post('/data/backup', ['month' => '2026-01']);
    $backup = json_decode($response->getContent(), true);

    expect($backup['backup_version'])->toBe('2.1');
    expect($backup['products'][0]['uuid'])->toBe($product->uuid);
    expect($backup['sales'][0]['product_uuid'])->toBe($product->uuid);
});

test('backup menyertakan product_uuid di tiap baris restocks', function () {
    $product = Product::create(['name' => 'Gula 1kg', 'price' => 15000, 'stock' => 10]);

    $restock = Restock::create([
        'product_id' => $product->id,
        'quantity' => 20,
    ]);
    $restock->timestamps = false;
    $restock->created_at = '2026-01-07 08:00:00';
    $restock->updated_at = '2026-01-07 08:00:00';
    $restock->save();

    $response = $this->post('/data/backup', ['month' => '2026-01']);
    $backup = json_decode($response->getContent(), true);

    expect($backup['restocks'][0]['product_uuid'])->toBe($product->uuid);
    expect($backup['restocks'][0]['product_id'])->toBe($product->id);
});

test('restore tetap tertaut ke product yang benar lewat uuid walau nama product sudah diedit setelah backup dibuat', function () {
    $product = Product::create(['name' => 'Rokok A', 'price' => 25000, 'stock' => 5]);

    $payload = [
        'app' => 'Toko Saya',
        'backup_version' => '2.1',
        'period' => '2026-01',
        'generated_at' => '2026-03-01 08:00:00',
        'products' => [
            ['id' => $product->id, 'uuid' => $product->uuid, 'name' => 'Rokok A', 'price' => 25000, 'stock' => 5],
        ],
        'sales' => [[
            'uuid' => (string) Str::uuid(),
            'product_id' => $product->id,
            'product_uuid' => $product->uuid,
            // Nama di baris backup sengaja masih nama LAMA...
            'product_name' => 'Rokok A',
            'quantity' => 1,
            'price' => 25000,
            'total' => 25000,
            'sold_at' => '2026-01-15',
            'created_at' => '2026-01-15 09:00:00',
            'updated_at' => '2026-01-15 09:00:00',
        ]],
        'restocks' => [],
        'expenses' => [],
    ];

    // ...tapi product-nya sudah diedit namanya SEBELUM restore dijalankan.
    $product->update(['name' => 'Rokok A (Kemasan Baru)']);

    $response = $this->post('/data/restore', ['backup_file' => fakeUuidBackupFile($payload)]);
    $response->assertSessionHasNoErrors();

    // Tidak ada product baru yang dibuat ulang — sale tertaut ke product
    // yang sama persis (lewat uuid), bukan product duplikat.
    $this->assertDatabaseCount('products', 1);

    $sale = Sale::first();
    expect($sale->product_id)->toBe($product->id);
});

// -----------------------------------------------------------------------
// 3) Mencegah salah relasi ketika Product ID berubah/dipakai ulang
// -----------------------------------------------------------------------

test('restore tidak salah tempel ke product lain walau id lama dipakai ulang oleh product yang sama sekali berbeda', function () {
    $original = Product::create(['name' => 'Rokok A', 'price' => 25000, 'stock' => 5]);
    $originalId = $original->id;
    $originalUuid = $original->uuid;

    $payload = [
        'app' => 'Toko Saya',
        'backup_version' => '2.1',
        'period' => '2026-01',
        'generated_at' => '2026-03-01 08:00:00',
        'products' => [
            ['id' => $originalId, 'uuid' => $originalUuid, 'name' => 'Rokok A', 'price' => 25000, 'stock' => 5],
        ],
        'sales' => [[
            'uuid' => (string) Str::uuid(),
            'product_id' => $originalId,
            'product_uuid' => $originalUuid,
            'product_name' => 'Rokok A',
            'quantity' => 1,
            'price' => 25000,
            'total' => 25000,
            'sold_at' => '2026-01-15',
            'created_at' => '2026-01-15 09:00:00',
            'updated_at' => '2026-01-15 09:00:00',
        ]],
        'restocks' => [],
        'expenses' => [],
    ];

    // Hapus product asli, lalu paksa id lamanya dipakai ulang oleh product
    // LAIN yang sama sekali tidak berhubungan (nama beda, uuid baru).
    $original->delete();
    DB::table('products')->insert([
        'id' => $originalId,
        'uuid' => (string) Str::uuid(),
        'name' => 'Produk Lain Yang Tidak Berhubungan',
        'price' => 999,
        'stock' => 999,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $impostor = Product::find($originalId);
    expect($impostor->name)->toBe('Produk Lain Yang Tidak Berhubungan');

    $response = $this->post('/data/restore', ['backup_file' => fakeUuidBackupFile($payload)]);
    $response->assertSessionHasNoErrors();

    // Sale TIDAK boleh tertaut ke product impostor yang kebetulan memakai
    // id lama. uuid tidak ditemukan (product asli sudah dihapus) dan nama
    // di baris backup ("Rokok A") tidak cocok dengan nama product impostor
    // ("Produk Lain Yang Tidak Berhubungan") — jadi product baru dibuat
    // ulang dari snapshot, dengan uuid asli dipertahankan.
    $sale = Sale::first();
    expect($sale)->not->toBeNull();
    expect($sale->product_id)->not->toBe($impostor->id);

    $recreated = Product::find($sale->product_id);
    expect($recreated->uuid)->toBe($originalUuid);
    expect($recreated->name)->toBe('Rokok A');
});

// -----------------------------------------------------------------------
// 4) Timestamp asli tetap dipertahankan (termasuk untuk Product yang
//    dibuat ulang dari snapshot — bukan cuma sales/restocks/expenses)
// -----------------------------------------------------------------------

test('product yang dibuat ulang dari snapshot memakai created_at asli dari backup, bukan waktu restore', function () {
    $product = Product::create(['name' => 'Rokok A', 'price' => 25000, 'stock' => 5]);
    $productUuid = $product->uuid;

    $payload = [
        'app' => 'Toko Saya',
        'backup_version' => '2.1',
        'period' => '2026-01',
        'generated_at' => '2026-03-01 08:00:00',
        'products' => [[
            'id' => $product->id,
            'uuid' => $productUuid,
            'name' => 'Rokok A',
            'price' => 25000,
            'stock' => 5,
            'created_at' => '2025-06-01 08:00:00',
            'updated_at' => '2025-06-01 08:00:00',
        ]],
        'sales' => [[
            'uuid' => (string) Str::uuid(),
            'product_id' => $product->id,
            'product_uuid' => $productUuid,
            'product_name' => 'Rokok A',
            'quantity' => 1,
            'price' => 25000,
            'total' => 25000,
            'sold_at' => '2026-01-15',
            'created_at' => '2026-01-15 09:00:00',
            'updated_at' => '2026-01-15 09:00:00',
        ]],
        'restocks' => [],
        'expenses' => [],
    ];

    $product->delete();

    $response = $this->post('/data/restore', ['backup_file' => fakeUuidBackupFile($payload)]);
    $response->assertSessionHasNoErrors();

    $recreated = Product::where('uuid', $productUuid)->first();
    expect($recreated)->not->toBeNull();
    expect($recreated->created_at->format('Y-m-d H:i:s'))->toBe('2025-06-01 08:00:00');
});

// -----------------------------------------------------------------------
// 5) Duplicate protection (untuk Product yang dibuat ulang dari snapshot)
// -----------------------------------------------------------------------

test('restore backup yang sama dua kali tidak menggandakan product yang dibuat ulang dari snapshot', function () {
    $product = Product::create(['name' => 'Rokok A', 'price' => 25000, 'stock' => 5]);
    $productUuid = $product->uuid;

    $payload = [
        'app' => 'Toko Saya',
        'backup_version' => '2.1',
        'period' => '2026-01',
        'generated_at' => '2026-03-01 08:00:00',
        'products' => [
            ['id' => $product->id, 'uuid' => $productUuid, 'name' => 'Rokok A', 'price' => 25000, 'stock' => 5],
        ],
        'sales' => [[
            'uuid' => (string) Str::uuid(),
            'product_id' => $product->id,
            'product_uuid' => $productUuid,
            'product_name' => 'Rokok A',
            'quantity' => 1,
            'price' => 25000,
            'total' => 25000,
            'sold_at' => '2026-01-15',
            'created_at' => '2026-01-15 09:00:00',
            'updated_at' => '2026-01-15 09:00:00',
        ]],
        'restocks' => [],
        'expenses' => [],
    ];

    $product->delete();

    $this->post('/data/restore', ['backup_file' => fakeUuidBackupFile($payload)]);
    $this->assertDatabaseCount('products', 1);
    $this->assertDatabaseCount('sales', 1);

    // Restore file backup yang SAMA persis untuk kedua kalinya.
    $this->post('/data/restore', ['backup_file' => fakeUuidBackupFile($payload)]);
    $this->assertDatabaseCount('products', 1);
    $this->assertDatabaseCount('sales', 1);
});

// -----------------------------------------------------------------------
// 6) Stok saat ini tidak berubah saat restore riwayat (termasuk saat
//    Product-nya sendiri harus dibuat ulang dari snapshot)
// -----------------------------------------------------------------------

test('product yang dibuat ulang dari snapshot mendapat stock 0, bukan stock dari snapshot', function () {
    $product = Product::create(['name' => 'Rokok A', 'price' => 25000, 'stock' => 999]);
    $productUuid = $product->uuid;

    $payload = [
        'app' => 'Toko Saya',
        'backup_version' => '2.1',
        'period' => '2026-01',
        'generated_at' => '2026-03-01 08:00:00',
        'products' => [
            ['id' => $product->id, 'uuid' => $productUuid, 'name' => 'Rokok A', 'price' => 25000, 'stock' => 999],
        ],
        'sales' => [[
            'uuid' => (string) Str::uuid(),
            'product_id' => $product->id,
            'product_uuid' => $productUuid,
            'product_name' => 'Rokok A',
            'quantity' => 1,
            'price' => 25000,
            'total' => 25000,
            'sold_at' => '2026-01-15',
            'created_at' => '2026-01-15 09:00:00',
            'updated_at' => '2026-01-15 09:00:00',
        ]],
        'restocks' => [],
        'expenses' => [],
    ];

    $product->delete();

    $this->post('/data/restore', ['backup_file' => fakeUuidBackupFile($payload)]);

    $recreated = Product::where('uuid', $productUuid)->first();
    expect($recreated->stock)->toBe(0);
});

// -----------------------------------------------------------------------
// 7) Backward compatibility dengan backup lama (tanpa product uuid sama
//    sekali) — perilaku yang sudah ada sebelumnya harus tetap jalan persis
//    sama setelah patch ini.
// -----------------------------------------------------------------------

test('backup format 2.0 (tanpa product_uuid maupun uuid di snapshot produk) tetap bisa direstore lewat fallback id+name', function () {
    $product = Product::create(['name' => 'Teh Botol', 'price' => 5000, 'stock' => 10]);

    $payload = [
        'app' => 'Toko Saya',
        'backup_version' => '2.0',
        'backup_type' => 'monthly',
        'period' => '2026-01',
        'generated_at' => '2026-03-01 08:00:00',
        // Snapshot produk format 2.0: TIDAK ada field 'uuid' sama sekali.
        'products' => [
            ['id' => $product->id, 'name' => 'Teh Botol', 'price' => 5000, 'stock' => 10],
        ],
        'sales' => [[
            'uuid' => (string) Str::uuid(),
            'product_id' => $product->id,
            // Baris sales format 2.0: TIDAK ada field 'product_uuid'.
            'product_name' => 'Teh Botol',
            'quantity' => 2,
            'price' => 5000,
            'total' => 10000,
            'sold_at' => '2026-01-05',
            'created_at' => '2026-01-05 10:30:00',
            'updated_at' => '2026-01-05 10:30:00',
        ]],
        'restocks' => [],
        'expenses' => [],
    ];

    $response = $this->post('/data/restore', ['backup_file' => fakeUuidBackupFile($payload)]);

    $response->assertRedirect('/data');
    $response->assertSessionHasNoErrors();

    $sale = Sale::first();
    expect($sale)->not->toBeNull();
    expect($sale->product_id)->toBe($product->id);
});

test('backup format 1.x (tanpa uuid transaksi maupun snapshot produk) tetap bisa direstore seperti sebelumnya', function () {
    $product = Product::create(['name' => 'Gula 1kg', 'price' => 15000, 'stock' => 10]);

    $payload = [
        'app' => 'Toko Saya',
        'backup_version' => '1.1',
        'period' => '2026-01',
        'generated_at' => '2026-03-01 08:00:00',
        // Tidak ada key 'products' sama sekali di format 1.x.
        'sales' => [[
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 15000,
            'total' => 15000,
            'sold_at' => '2026-01-10',
            'created_at' => '2026-01-10 12:00:00',
            'updated_at' => '2026-01-10 12:00:00',
        ]],
        'restocks' => [],
        'expenses' => [],
    ];

    $response = $this->post('/data/restore', ['backup_file' => fakeUuidBackupFile($payload)]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseCount('sales', 1);

    $sale = Sale::first();
    expect($sale->product_id)->toBe($product->id);
});

// -----------------------------------------------------------------------
// 8) Migration: kolom uuid pada products
// -----------------------------------------------------------------------

test('kolom uuid pada tabel products ada dan unik', function () {
    expect(Schema::hasColumn('products', 'uuid'))->toBeTrue();

    $a = Product::create(['name' => 'A', 'price' => 1, 'stock' => 1]);

    // Baris kedua dengan uuid yang sama persis harus ditolak database
    // (unique index products_uuid_unique).
    expect(fn () => DB::table('products')->insert([
        'uuid' => $a->uuid,
        'name' => 'B',
        'price' => 1,
        'stock' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('migration membackfill uuid untuk product lama yang dibuat sebelum kolom uuid ada', function () {
    // Simulasikan Product LAMA: rollback migration uuid Product (kolom
    // uuid ikut hilang), lalu insert baris LANGSUNG lewat DB (bukan lewat
    // Model, supaya trait HasStableUuid tidak ikut membuatkan uuid) —
    // ini meniru data yang sudah ada di database SEBELUM kolom uuid
    // ditambahkan. Migrate up lagi lalu pastikan migration MEMBACKFILL
    // uuid-nya secara otomatis untuk baris lama tsb, tanpa menyentuh data
    // lain (name/price/stock tetap sama).
    Artisan::call('migrate:rollback', ['--step' => 2]);

    expect(Schema::hasColumn('products', 'uuid'))->toBeFalse();

    $legacyId = DB::table('products')->insertGetId([
        'name' => 'Produk Lama Sebelum UUID',
        'price' => 12000,
        'stock' => 7,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Artisan::call('migrate');

    expect(Schema::hasColumn('products', 'uuid'))->toBeTrue();

    $legacyRow = DB::table('products')->where('id', $legacyId)->first();
    expect($legacyRow->uuid)->not->toBeNull();
    expect(Str::isUuid($legacyRow->uuid))->toBeTrue();
    // Data lain tidak ikut berubah oleh backfill.
    expect($legacyRow->name)->toBe('Produk Lama Sebelum UUID');
    expect($legacyRow->stock)->toBe(7);
});

// -----------------------------------------------------------------------
// 9) Dua Product dengan nama SAMA tidak boleh menyebabkan transaksi
//    tertaut ke Product yang salah (nama bukan identity utama)
// -----------------------------------------------------------------------

test('dua product dengan nama sama tapi uuid berbeda: restore tetap tertaut ke product yang tepat lewat uuid', function () {
    $productA = Product::create(['name' => 'Rokok A', 'price' => 25000, 'stock' => 5]);
    $productB = Product::create(['name' => 'Rokok A', 'price' => 25000, 'stock' => 9]);

    expect($productA->uuid)->not->toBe($productB->uuid);

    $payload = [
        'app' => 'Toko Saya',
        'backup_version' => '2.1',
        'period' => '2026-01',
        'generated_at' => '2026-03-01 08:00:00',
        'products' => [
            ['id' => $productA->id, 'uuid' => $productA->uuid, 'name' => 'Rokok A', 'price' => 25000, 'stock' => 5],
            ['id' => $productB->id, 'uuid' => $productB->uuid, 'name' => 'Rokok A', 'price' => 25000, 'stock' => 9],
        ],
        // Baris backup SENGAJA menunjuk product B lewat uuid-nya, walau
        // ada dua product bernama sama persis di database.
        'sales' => [[
            'uuid' => (string) Str::uuid(),
            'product_id' => $productB->id,
            'product_uuid' => $productB->uuid,
            'product_name' => 'Rokok A',
            'quantity' => 1,
            'price' => 25000,
            'total' => 25000,
            'sold_at' => '2026-01-15',
            'created_at' => '2026-01-15 09:00:00',
            'updated_at' => '2026-01-15 09:00:00',
        ]],
        'restocks' => [],
        'expenses' => [],
    ];

    $response = $this->post('/data/restore', ['backup_file' => fakeUuidBackupFile($payload)]);
    $response->assertSessionHasNoErrors();

    $sale = Sale::first();
    expect($sale)->not->toBeNull();
    expect($sale->product_id)->toBe($productB->id);
    expect($sale->product_id)->not->toBe($productA->id);

    // Tidak ada product baru yang dibuat ulang — kedua product asli tetap
    // dua baris seperti semula.
    $this->assertDatabaseCount('products', 2);
});

// -----------------------------------------------------------------------
// 12) Restore history tidak mengubah current stock milik product yang
//     MASIH ADA (bukan cuma product yang dibuat ulang dari snapshot)
// -----------------------------------------------------------------------

test('restore sales/restocks untuk product yang masih ada tidak mengubah current stock product tsb', function () {
    $product = Product::create(['name' => 'Beras 5kg', 'price' => 65000, 'stock' => 42]);
    $stockBefore = $product->stock;

    $payload = [
        'app' => 'Toko Saya',
        'backup_version' => '2.1',
        'period' => '2026-01',
        'generated_at' => '2026-03-01 08:00:00',
        'products' => [
            ['id' => $product->id, 'uuid' => $product->uuid, 'name' => 'Beras 5kg', 'price' => 65000, 'stock' => 42],
        ],
        'sales' => [[
            'uuid' => (string) Str::uuid(),
            'product_id' => $product->id,
            'product_uuid' => $product->uuid,
            'product_name' => 'Beras 5kg',
            'quantity' => 3,
            'price' => 65000,
            'total' => 195000,
            'sold_at' => '2026-01-15',
            'created_at' => '2026-01-15 09:00:00',
            'updated_at' => '2026-01-15 09:00:00',
        ]],
        'restocks' => [[
            'uuid' => (string) Str::uuid(),
            'product_id' => $product->id,
            'product_uuid' => $product->uuid,
            'product_name' => 'Beras 5kg',
            'quantity' => 100,
            'created_at' => '2026-01-16 09:00:00',
            'updated_at' => '2026-01-16 09:00:00',
        ]],
        'expenses' => [],
    ];

    $response = $this->post('/data/restore', ['backup_file' => fakeUuidBackupFile($payload)]);
    $response->assertSessionHasNoErrors();

    $this->assertDatabaseCount('sales', 1);
    $this->assertDatabaseCount('restocks', 1);

    // Restock yang dipulihkan mencatat quantity=100 di RIWAYAT, tapi
    // current stock product TIDAK ikut bertambah 100 — restore history
    // bukan full database recovery (lihat docblock restore()).
    expect($product->fresh()->stock)->toBe($stockBefore);
});
