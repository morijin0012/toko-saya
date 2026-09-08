<?php

use App\Models\BackupArchive;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Restock;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

test('request aplikasi menjalankan auto backup dan membuat arsip bulanan lengkap', function () {
    $product = Product::create([
        'name' => 'Produk Integrasi',
        'price' => 25000,
        'stock' => 50,
    ]);

    Sale::create([
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 25000,
        'total' => 50000,
        'sold_at' => now()
            ->subMonth()
            ->startOfMonth()
            ->addDays(5)
            ->format('Y-m-d'),
    ]);

    Expense::create([
        'name' => 'Biaya Integrasi',
        'category' => 'Operasional',
        'amount' => 10000,
        'expense_date' => now()
            ->subMonth()
            ->startOfMonth()
            ->addDays(5)
            ->format('Y-m-d'),
        'note' => 'Test otomatis',
    ]);

    Restock::create([
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    $previousMonth = now()
        ->subMonth()
        ->format('Y-m');

    $response = $this->get('/');

    $response->assertSuccessful();

    Storage::disk('local')->assertExists(
        "backups/{$previousMonth}/toko-saya-backup-{$previousMonth}.json"
    );

    Storage::disk('local')->assertExists(
        "backups/{$previousMonth}/laporan-{$previousMonth}.txt"
    );

    $json = Storage::disk('local')->get(
        "backups/{$previousMonth}/toko-saya-backup-{$previousMonth}.json"
    );

    $report = Storage::disk('local')->get(
        "backups/{$previousMonth}/laporan-{$previousMonth}.txt"
    );

    expect($json)
        ->toContain('Produk Integrasi')
        ->toContain('50000')
        ->toContain('Biaya Integrasi');

    expect($report)
        ->toContain('Produk Integrasi')
        ->toContain('TOTAL PENJUALAN')
        ->toContain('Rp 50.000')
        ->toContain('TOTAL PENGELUARAN')
        ->toContain('Rp 10.000')
        ->toContain('HASIL BERSIH')
        ->toContain('Rp 40.000');

    expect(
        BackupArchive::where('month', $previousMonth)
            ->where('status', 'archived')
            ->exists()
    )->toBeTrue();
});

test('transaksi bulan lama yang baru dimasukkan tetap dapat diarsipkan', function () {
    Product::create([
        'name' => 'Produk Backdated',
        'price' => 15000,
        'stock' => 30,
    ]);

    // Request pertama dilakukan sebelum transaksi Agustus dimasukkan.
    $this->get('/')->assertSuccessful();

    // Customer kemudian memasukkan transaksi dengan tanggal Agustus.
    Expense::create([
        'name' => 'Pengeluaran Agustus',
        'category' => 'Operasional',
        'amount' => 7000,
        'expense_date' => '2026-08-31',
        'note' => 'Dimasukkan setelah bulan berjalan dimulai',
    ]);

    // Request berikutnya harus menemukan Agustus.
    $this->get('/')->assertSuccessful();

    Storage::disk('local')->assertExists(
        'backups/2026-08/laporan-2026-08.txt'
    );

    Storage::disk('local')->assertExists(
        'backups/2026-08/toko-saya-backup-2026-08.json'
    );

    expect(
        BackupArchive::where('month', '2026-08')
            ->where('status', 'archived')
            ->exists()
    )->toBeTrue();
});
