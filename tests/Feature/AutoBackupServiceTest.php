<?php

use App\Models\BackupArchive;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Services\AutoBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

test('auto backup menemukan semua bulan transaksi yang sudah selesai tanpa batas tahun', function () {
    $product = Product::create([
        'name' => 'Produk Arsip',
        'price' => 10000,
        'stock' => 100,
    ]);

    Sale::create([
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 10000,
        'total' => 10000,
        'sold_at' => '2026-09-15',
    ]);

    Sale::create([
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 10000,
        'total' => 20000,
        'sold_at' => '2027-01-10',
    ]);

    Expense::create([
        'name' => 'Pengeluaran 2028',
        'category' => 'Operasional',
        'amount' => 5000,
        'expense_date' => '2028-06-20',
    ]);

    $processed = app(AutoBackupService::class)->run('2028-07');

    expect($processed)->toBe([
        '2026-09',
        '2027-01',
        '2028-06',
    ]);

    Storage::disk('local')->assertExists(
        'backups/2026-09/toko-saya-backup-2026-09.json'
    );

    Storage::disk('local')->assertExists(
        'backups/2026-09/laporan-2026-09.txt'
    );

    Storage::disk('local')->assertExists(
        'backups/2027-01/toko-saya-backup-2027-01.json'
    );

    Storage::disk('local')->assertExists(
        'backups/2027-01/laporan-2027-01.txt'
    );

    Storage::disk('local')->assertExists(
        'backups/2028-06/toko-saya-backup-2028-06.json'
    );

    Storage::disk('local')->assertExists(
        'backups/2028-06/laporan-2028-06.txt'
    );
});

test('auto backup tidak mengarsipkan bulan yang sedang berjalan', function () {
    $product = Product::create([
        'name' => 'Produk Bulan Berjalan',
        'price' => 10000,
        'stock' => 20,
    ]);

    Sale::create([
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 10000,
        'total' => 10000,
        'sold_at' => '2028-07-10',
    ]);

    $processed = app(AutoBackupService::class)->run('2028-07');

    expect($processed)->toBe([]);

    Storage::disk('local')->assertMissing(
        'backups/2028-07/toko-saya-backup-2028-07.json'
    );
});

test('auto backup tidak memproses bulan yang sudah diarsipkan', function () {
    $product = Product::create([
        'name' => 'Produk Sudah Diarsipkan',
        'price' => 10000,
        'stock' => 20,
    ]);

    Sale::create([
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 10000,
        'total' => 10000,
        'sold_at' => '2027-01-10',
    ]);

    Storage::disk('local')->put(
        'backups/2027-01/toko-saya-backup-2027-01.json',
        '{"backup_version":"2.1"}'
    );

    Storage::disk('local')->put(
        'backups/2027-01/laporan-2027-01.txt',
        'LAPORAN January 2027'
    );

    BackupArchive::create([
        'month' => '2027-01',
        'status' => 'archived',
        'archived_at' => now(),
    ]);

    $processed = app(AutoBackupService::class)->run('2028-07');

    expect($processed)->not->toContain('2027-01');
});

test('auto backup tetap bisa mengejar bulan yang terlewat setelah aplikasi lama tidak dibuka', function () {
    $product = Product::create([
        'name' => 'Produk Bulan Terlewat',
        'price' => 10000,
        'stock' => 50,
    ]);

    Sale::create([
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 10000,
        'total' => 10000,
        'sold_at' => '2029-03-05',
    ]);

    Sale::create([
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 10000,
        'total' => 10000,
        'sold_at' => '2029-04-08',
    ]);

    Sale::create([
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 10000,
        'total' => 10000,
        'sold_at' => '2029-05-12',
    ]);

    $processed = app(AutoBackupService::class)->run('2029-06');

    expect($processed)->toBe([
        '2029-03',
        '2029-04',
        '2029-05',
    ]);

    Storage::disk('local')->assertExists(
        'backups/2029-03/toko-saya-backup-2029-03.json'
    );

    Storage::disk('local')->assertExists(
        'backups/2029-04/toko-saya-backup-2029-04.json'
    );

    Storage::disk('local')->assertExists(
        'backups/2029-05/toko-saya-backup-2029-05.json'
    );
});
