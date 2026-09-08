<?php

use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Helper: buat file upload "backup_file" palsu berisi konten JSON tertentu,
 * dengan ekstensi .json (lolos validasi mimes:json,txt seperti file backup
 * asli yang diunduh dari fitur backup).
 */
function fakeBackupFile(array $payload): UploadedFile
{
    return UploadedFile::fake()->createWithContent('backup.json', json_encode($payload));
}

test('backup menghasilkan struktur JSON yang valid dan mempertahankan tanggal asli', function () {
    $product = Product::create(['name' => 'Kopi Sachet', 'price' => 2000, 'stock' => 50]);

    $sale = Sale::create([
        'product_id' => $product->id,
        'quantity' => 3,
        'price' => 2000,
        'total' => 6000,
        'sold_at' => '2026-01-05',
    ]);
    $sale->timestamps = false;
    $sale->created_at = '2026-01-05 10:30:00';
    $sale->updated_at = '2026-01-05 10:30:00';
    $sale->save();

    $response = $this->post('/data/backup', ['month' => '2026-01']);

    $response->assertOk();

    $backup = json_decode($response->getContent(), true);

    expect($backup['app'])->toBe('Toko Saya');
    // Format backup saat ini adalah 2.1 (Product juga punya uuid stabil
    // sendiri — lihat DataController::BACKUP_VERSION). Restore tetap
    // mendukung file backup versi lama lewat feature-detection, jadi test
    // ini hanya perlu disesuaikan dengan versi TERBARU yang dihasilkan.
    expect($backup['backup_version'])->toBe('2.1');
    expect($backup)->toHaveKeys(['products', 'sales', 'restocks', 'expenses']);
    expect($backup['products'])->toHaveCount(1);
    expect($backup['sales'])->toHaveCount(1);

    $exportedSale = $backup['sales'][0];
    expect($exportedSale['uuid'])->toBe($sale->uuid);
    expect($exportedSale['product_name'])->toBe('Kopi Sachet');
    expect($exportedSale['created_at'])->toContain('2026-01-05');
});

test('restore mempertahankan tanggal transaksi asli, bukan tanggal saat restore', function () {
    $product = Product::create(['name' => 'Teh Botol', 'price' => 5000, 'stock' => 10]);

    $backupFile = fakeBackupFile([
        'app' => 'Toko Saya',
        'backup_version' => '2.0',
        'backup_type' => 'monthly',
        'period' => '2026-01',
        'generated_at' => '2026-03-01 08:00:00',
        'products' => [
            ['id' => $product->id, 'name' => 'Teh Botol', 'price' => 5000, 'stock' => 10],
        ],
        'sales' => [[
            'uuid' => (string) Str::uuid(),
            'product_id' => $product->id,
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
    ]);

    $response = $this->post('/data/restore', ['backup_file' => $backupFile]);

    $response->assertRedirect('/data');
    $response->assertSessionHasNoErrors();

    $sale = Sale::first();

    expect($sale)->not->toBeNull();
    expect($sale->created_at->format('Y-m-d H:i:s'))->toBe('2026-01-05 10:30:00');
    expect($sale->sold_at->format('Y-m-d'))->toBe('2026-01-05');
});

test('restore backup yang sama dua kali tidak menggandakan transaksi', function () {
    $product = Product::create(['name' => 'Gula 1kg', 'price' => 15000, 'stock' => 10]);

    $payload = [
        'app' => 'Toko Saya',
        'backup_version' => '2.0',
        'period' => '2026-01',
        'generated_at' => '2026-03-01 08:00:00',
        'products' => [
            ['id' => $product->id, 'name' => 'Gula 1kg', 'price' => 15000, 'stock' => 10],
        ],
        'sales' => [[
            'uuid' => (string) Str::uuid(),
            'product_id' => $product->id,
            'product_name' => 'Gula 1kg',
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

    $this->post('/data/restore', ['backup_file' => fakeBackupFile($payload)]);
    $this->assertDatabaseCount('sales', 1);

    // Restore file backup yang SAMA persis untuk kedua kalinya.
    $this->post('/data/restore', ['backup_file' => fakeBackupFile($payload)]);
    $this->assertDatabaseCount('sales', 1);
});

test('restore memulihkan transaksi walau produk aslinya sudah terhapus, dengan membuat ulang produk dari snapshot backup', function () {
    $product = Product::create(['name' => 'Rokok A', 'price' => 25000, 'stock' => 5]);
    $originalProductId = $product->id;

    $payload = [
        'app' => 'Toko Saya',
        'backup_version' => '2.0',
        'period' => '2026-01',
        'generated_at' => '2026-03-01 08:00:00',
        'products' => [
            ['id' => $originalProductId, 'name' => 'Rokok A', 'price' => 25000, 'stock' => 5],
        ],
        'sales' => [[
            'uuid' => (string) Str::uuid(),
            'product_id' => $originalProductId,
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

    // Hapus produk aslinya SEBELUM restore (mensimulasikan Product ID 5
    // yang sudah tidak ada lagi di database tujuan, seperti pada contoh
    // kasus di spesifikasi).
    $product->delete();
    $this->assertDatabaseMissing('products', ['id' => $originalProductId]);

    $response = $this->post('/data/restore', ['backup_file' => fakeBackupFile($payload)]);
    $response->assertSessionHasNoErrors();

    // Product dibuat ulang dari snapshot backup dengan nama yang sama...
    $recreated = Product::where('name', 'Rokok A')->first();
    expect($recreated)->not->toBeNull();
    // ...tapi stock TIDAK diambil dari snapshot (stock gudang saat ini
    // tidak boleh berubah akibat restore riwayat transaksi lama).
    expect($recreated->stock)->toBe(0);

    // Sale berhasil dipulihkan dan tertaut ke Product yang baru dibuat ulang.
    $sale = Sale::first();
    expect($sale)->not->toBeNull();
    expect($sale->product_id)->toBe($recreated->id);
});

test('restore ditolak dengan aman ketika file backup rusak, tanpa mengubah data apa pun', function () {
    $badFile = UploadedFile::fake()->createWithContent('backup.json', '{ ini bukan json valid ');

    $response = $this->post('/data/restore', ['backup_file' => $badFile]);

    $response->assertSessionHasErrors('backup_file');
    $this->assertDatabaseCount('sales', 0);
    $this->assertDatabaseCount('restocks', 0);
    $this->assertDatabaseCount('expenses', 0);
});

test('baris backup yang tidak lengkap dilewati sendiri, tidak menggagalkan seluruh restore', function () {
    $product = Product::create(['name' => 'Kopi Sachet', 'price' => 2000, 'stock' => 50]);

    // Baris expense kedua sengaja tidak lengkap (amount kosong) supaya
    // tervalidasi sebagai baris tidak valid dan DILEWATI (bukan membatalkan
    // transaksi) — ini menegaskan bahwa hanya BARIS tsb yang dilewati,
    // sementara baris valid lain tetap tersimpan dalam SATU transaction
    // yang sama.
    $payload = [
        'app' => 'Toko Saya',
        'backup_version' => '2.0',
        'period' => '2026-01',
        'generated_at' => '2026-03-01 08:00:00',
        'products' => [
            ['id' => $product->id, 'name' => 'Kopi Sachet', 'price' => 2000, 'stock' => 50],
        ],
        'sales' => [[
            'uuid' => (string) Str::uuid(),
            'product_id' => $product->id,
            'product_name' => 'Kopi Sachet',
            'quantity' => 1,
            'price' => 2000,
            'total' => 2000,
            'sold_at' => '2026-01-05',
            'created_at' => '2026-01-05 10:00:00',
            'updated_at' => '2026-01-05 10:00:00',
        ]],
        'restocks' => [],
        'expenses' => [[
            'name' => 'Beli galon',
            'category' => 'Operasional',
            'amount' => null,
            'expense_date' => '2026-01-06',
            'created_at' => '2026-01-06 08:00:00',
        ]],
    ];

    $response = $this->post('/data/restore', ['backup_file' => fakeBackupFile($payload)]);
    $response->assertSessionHasNoErrors();

    $this->assertDatabaseCount('sales', 1);
    $this->assertDatabaseCount('expenses', 0);
});

test('restore memakai database transaction: error di tengah proses membatalkan seluruh restore, bukan hanya baris yang bermasalah', function () {
    $product = Product::create(['name' => 'Kopi Sachet', 'price' => 2000, 'stock' => 50]);

    // Catatan desain (PENTING, jangan diubah jadi skenario uuid sama):
    // Uuid yang sama pada dua baris BUKAN cara yang valid untuk memicu
    // error di sini, karena itu justru akan ditangani dengan benar oleh
    // duplicate-protection (baris kedua dianggap "sudah direstore" dan
    // dilewati secara aman, bukan error) — lihat
    // DataController::saleAlreadyRestored(). Test ini butuh error yang
    // BENAR-BENAR tidak terduga/tidak tertangani oleh validasi aplikasi
    // (mis. data korup dari file backup yang rusak), supaya benar-benar
    // menguji bahwa DB::transaction() membatalkan SEMUA perubahan
    // (termasuk baris pertama yang sudah "berhasil") saat exception
    // terjadi di tengah proses — bukan menguji ulang duplicate-protection
    // yang sudah dicakup test lain.
    $payload = [
        'app' => 'Toko Saya',
        'backup_version' => '2.0',
        'period' => '2026-01',
        'generated_at' => '2026-03-01 08:00:00',
        'products' => [
            ['id' => $product->id, 'name' => 'Kopi Sachet', 'price' => 2000, 'stock' => 50],
        ],
        'sales' => [
            [
                'uuid' => (string) Str::uuid(),
                'product_id' => $product->id,
                'product_name' => 'Kopi Sachet',
                'quantity' => 1,
                'price' => 2000,
                'total' => 2000,
                'sold_at' => '2026-01-05',
                'created_at' => '2026-01-05 10:00:00',
                'updated_at' => '2026-01-05 10:00:00',
            ],
            [
                'uuid' => (string) Str::uuid(),
                'product_id' => $product->id,
                'product_name' => 'Kopi Sachet',
                'quantity' => 2,
                'price' => 2000,
                // 'total' korup: berupa array, bukan angka. Ini melewati
                // validasi rowHasRequiredFields() (fieldnya ADA dan tidak
                // kosong) sehingga baris ini benar-benar diproses sampai
                // ke database, lalu gagal saat disimpan (query exception
                // dari database driver) — error tak terduga yang genuin,
                // bukan hasil rekayasa terhadap duplicate-protection.
                'total' => ['corrupt' => 'data'],
                'sold_at' => '2026-01-06',
                'created_at' => '2026-01-06 10:00:00',
                'updated_at' => '2026-01-06 10:00:00',
            ],
        ],
        'restocks' => [],
        'expenses' => [],
    ];

    $response = $this->post('/data/restore', ['backup_file' => fakeBackupFile($payload)]);

    $response->assertSessionHasErrors('backup_file');

    // Baris pertama TIDAK tertinggal di database walau sempat "berhasil"
    // sebelum baris kedua gagal — database tidak boleh setengah restore.
    $this->assertDatabaseCount('sales', 0);
});
