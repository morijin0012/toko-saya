<?php

use App\Models\BackupArchive;
use App\Models\Product;
use App\Models\Sale;
use App\Services\ArchiveManager;
use App\Services\AutoBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

test('halaman arsip menampilkan backup bulanan yang tersedia', function () {
    Storage::disk('local')->put(
        'backups/2026-09/toko-saya-backup-2026-09.json',
        '{"backup_version":"2.1"}'
    );

    Storage::disk('local')->put(
        'backups/2026-09/laporan-2026-09.txt',
        'LAPORAN September 2026'
    );

    Storage::disk('local')->put(
        'backups/2027-01/laporan-2027-01.txt',
        'LAPORAN January 2027'
    );

    BackupArchive::create([
        'month' => '2026-09',
        'status' => 'archived',
        'archived_at' => now(),
    ]);

    BackupArchive::create([
        'month' => '2027-01',
        'status' => 'archived',
        'archived_at' => now(),
    ]);

    $response = $this->get('/backups');

    $response
        ->assertSuccessful()
        ->assertSee('2026-09')
        ->assertSee('2027-01')
        ->assertSee('Laporan TXT')
        ->assertSee('Backup JSON');
});

test('halaman arsip tidak menampilkan folder yang tidak memiliki file backup', function () {
    Storage::disk('local')->put(
        'backups/2026-09/temp.txt',
        'bukan arsip backup'
    );

    $response = $this->get('/backups');

    $response
        ->assertSuccessful()
        ->assertDontSee('2026-09');
});
test('menghapus arsip tidak menghapus data transaksi dan arsip tidak dibuat ulang otomatis', function () {
    $product = Product::create([
        'name' => 'Produk Arsip Hapus',
        'price' => 10000,
        'stock' => 20,
    ]);

    Sale::create([
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 10000,
        'total' => 10000,
        'sold_at' => now()->subMonth()->format('Y-m-d'),
    ]);

    $month = now()->subMonth()->format('Y-m');

    app(ArchiveManager::class)->archive($month);

    Storage::disk('local')->assertExists(
        "backups/{$month}/laporan-{$month}.txt"
    );

    Storage::disk('local')->assertExists(
        "backups/{$month}/toko-saya-backup-{$month}.json"
    );

    app(ArchiveManager::class)->delete($month);

    Storage::disk('local')->assertMissing(
        "backups/{$month}/laporan-{$month}.txt"
    );

    Storage::disk('local')->assertMissing(
        "backups/{$month}/toko-saya-backup-{$month}.json"
    );

    expect(
        Sale::where('product_id', $product->id)->count()
    )->toBe(1);

    expect(
        BackupArchive::where('month', $month)
            ->where('status', 'deleted')
            ->exists()
    )->toBeTrue();

    app(AutoBackupService::class)->run(
        now()->format('Y-m')
    );

    Storage::disk('local')->assertMissing(
        "backups/{$month}/laporan-{$month}.txt"
    );

    Storage::disk('local')->assertMissing(
        "backups/{$month}/toko-saya-backup-{$month}.json"
    );
});
test('arsip yang dihapus dibuat kembali ketika ada transaksi baru pada bulan tersebut', function () {
    $product = Product::create([
        'name' => 'Produk Rearchive',
        'price' => 10000,
        'stock' => 20,
    ]);

    $month = now()->subMonth()->format('Y-m');
    $transactionDate = "{$month}-15";

    Sale::create([
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 10000,
        'total' => 10000,
        'sold_at' => $transactionDate,
    ]);

    app(ArchiveManager::class)->archive($month);
    app(ArchiveManager::class)->delete($month);

    Storage::disk('local')->assertMissing(
        "backups/{$month}/laporan-{$month}.txt"
    );

    Storage::disk('local')->assertMissing(
        "backups/{$month}/toko-saya-backup-{$month}.json"
    );

    expect(
        BackupArchive::where('month', $month)
            ->where('status', 'deleted')
            ->exists()
    )->toBeTrue();

    /*
     * Buat transaksi baru SETELAH arsip dihapus.
     */
    Sale::create([
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 10000,
        'total' => 10000,
        'sold_at' => $transactionDate,
    ]);

    app(AutoBackupService::class)->run(
        now()->format('Y-m')
    );

    Storage::disk('local')->assertExists(
        "backups/{$month}/laporan-{$month}.txt"
    );

    Storage::disk('local')->assertExists(
        "backups/{$month}/toko-saya-backup-{$month}.json"
    );

    expect(
        BackupArchive::where('month', $month)
            ->where('status', 'archived')
            ->exists()
    )->toBeTrue();
});