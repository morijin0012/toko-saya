<?php

use App\Models\Expense;
use App\Models\Product;
use App\Models\Restock;
use App\Models\Sale;
use App\Services\MonthlyReportGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('laporan bulanan hanya mengambil transaksi pada bulan yang dipilih dan menghitung hasil bersih dengan benar', function () {
    $product = Product::create([
        'name' => 'Produk Test',
        'price' => 10000,
        'stock' => 100,
    ]);

    Sale::create([
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 10000,
        'total' => 20000,
        'sold_at' => '2026-09-10',
        'created_at' => '2026-09-10 10:15:00',
        'updated_at' => '2026-09-10 10:15:00',
    ]);

    Sale::create([
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 10000,
        'total' => 10000,
        'sold_at' => '2026-10-05',
        'created_at' => '2026-10-05 09:00:00',
        'updated_at' => '2026-10-05 09:00:00',
    ]);

    Expense::create([
        'name' => 'Beli plastik',
        'category' => 'Operasional',
        'amount' => 5000,
        'expense_date' => '2026-09-10',
        'note' => 'Test September',
        'created_at' => '2026-09-10 11:00:00',
        'updated_at' => '2026-09-10 11:00:00',
    ]);

    Expense::create([
        'name' => 'Biaya Oktober',
        'category' => 'Operasional',
        'amount' => 99999,
        'expense_date' => '2026-10-05',
        'note' => 'Jangan masuk laporan September',
        'created_at' => '2026-10-05 10:00:00',
        'updated_at' => '2026-10-05 10:00:00',
    ]);

    Restock::create([
        'product_id' => $product->id,
        'quantity' => 20,
        'created_at' => '2026-09-10 08:00:00',
        'updated_at' => '2026-09-10 08:00:00',
    ]);

    $report = app(MonthlyReportGenerator::class)->generate('2026-09');

    expect($report)->toContain('LAPORAN September 2026');
    expect($report)->toContain('Produk Test');
    expect($report)->toContain('Beli plastik');
    expect($report)->toContain('TOTAL PENJUALAN');
    expect($report)->toContain('Rp 20.000');
    expect($report)->toContain('TOTAL PENGELUARAN');
    expect($report)->toContain('Rp 5.000');
    expect($report)->toContain('HASIL BERSIH');
    expect($report)->toContain('Rp 15.000');
    expect($report)->toContain('STATUS: UNTUNG');

    expect($report)->not->toContain('Biaya Oktober');
    expect($report)->not->toContain('99999');
    expect($report)->not->toContain('05-10-2026');
});

test('laporan bulanan mengurutkan transaksi berdasarkan tanggal dan waktu', function () {
    $product = Product::create([
        'name' => 'Produk Urutan',
        'price' => 10000,
        'stock' => 100,
    ]);

    $sale = Sale::create([
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 10000,
        'total' => 10000,
        'sold_at' => '2026-09-20',
    ]);

    $expense = Expense::create([
        'name' => 'Pengeluaran Pagi',
        'category' => 'Operasional',
        'amount' => 1000,
        'expense_date' => '2026-09-20',
        'note' => null,
    ]);

    $sale->forceFill([
        'created_at' => '2026-09-20 15:00:00',
    ])->saveQuietly();

    $expense->forceFill([
        'created_at' => '2026-09-20 08:00:00',
    ])->saveQuietly();

    $report = app(MonthlyReportGenerator::class)->generate('2026-09');

    $pagi = strpos($report, '08:00');
    $siang = strpos($report, '15:00');

    expect($pagi)->not->toBeFalse();
    expect($siang)->not->toBeFalse();
    expect($pagi)->toBeLessThan($siang);
});
