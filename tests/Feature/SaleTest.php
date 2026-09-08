<?php

use App\Models\Product;
use App\Models\Sale;

test('penjualan mengurangi stok produk', function () {
    $product = Product::create(['name' => 'Air Mineral', 'price' => 3000, 'stock' => 20]);

    $response = $this->post('/sales', [
        'product_id' => $product->id,
        'quantity' => 5,
        'price' => 3000,
        'sold_at' => '2026-02-01',
    ]);

    $response->assertRedirect('/sales');

    expect($product->fresh()->stock)->toBe(15);

    $this->assertDatabaseHas('sales', [
        'product_id' => $product->id,
        'quantity' => 5,
        'total' => 15000,
    ]);
});

test('penjualan tidak boleh melebihi stok yang tersedia', function () {
    $product = Product::create(['name' => 'Susu Kotak', 'price' => 8000, 'stock' => 3]);

    $response = $this->post('/sales', [
        'product_id' => $product->id,
        'quantity' => 10,
        'price' => 8000,
        'sold_at' => '2026-02-01',
    ]);

    $response->assertSessionHasErrors('quantity');

    // Stok tidak berubah dan tidak ada penjualan yang tersimpan.
    expect($product->fresh()->stock)->toBe(3);
    $this->assertDatabaseCount('sales', 0);
});

test('dua transaksi penjualan yang identik tetap tersimpan sebagai dua transaksi terpisah', function () {
    $product = Product::create(['name' => 'Rokok B', 'price' => 22000, 'stock' => 100]);

    $this->post('/sales', [
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 22000,
        'sold_at' => '2026-02-05',
    ]);

    $this->post('/sales', [
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 22000,
        'sold_at' => '2026-02-05',
    ]);

    // Dua-duanya transaksi ASLI (dibuat lewat form, bukan restore), jadi
    // keduanya harus tersimpan meski nilainya identik.
    $this->assertDatabaseCount('sales', 2);
    expect(Sale::pluck('uuid')->unique())->toHaveCount(2);
});
