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

test('edit penjualan dengan quantity naik mengurangi stok tambahan', function () {
    $product = Product::create(['name' => 'Produk A', 'price' => 5000, 'stock' => 20]);

    $this->post('/sales', [
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 5000,
        'sold_at' => '2026-02-01',
    ]);

    $sale = Sale::first();
    expect($product->fresh()->stock)->toBe(18);

    $response = $this->put('/sales/'.$sale->id, [
        'product_id' => $product->id,
        'quantity' => 3,
        'price' => 5000,
        'sold_at' => '2026-02-01',
    ]);

    $response->assertRedirect('/sales');

    // Stok berkurang 1 tambahan (dari 18 menjadi 17).
    expect($product->fresh()->stock)->toBe(17);
    expect($sale->fresh()->quantity)->toBe(3);
    expect($sale->fresh()->total)->toBe(15000);
});

test('edit penjualan dengan quantity turun mengembalikan sebagian stok', function () {
    $product = Product::create(['name' => 'Produk A', 'price' => 5000, 'stock' => 20]);

    $this->post('/sales', [
        'product_id' => $product->id,
        'quantity' => 3,
        'price' => 5000,
        'sold_at' => '2026-02-01',
    ]);

    $sale = Sale::first();
    expect($product->fresh()->stock)->toBe(17);

    $this->put('/sales/'.$sale->id, [
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 5000,
        'sold_at' => '2026-02-01',
    ]);

    // Stok bertambah 2 (dari 17 menjadi 19).
    expect($product->fresh()->stock)->toBe(19);
    expect($sale->fresh()->quantity)->toBe(1);
});

test('edit penjualan pindah produk mengoreksi stok kedua produk', function () {
    $productA = Product::create(['name' => 'Produk A', 'price' => 5000, 'stock' => 20]);
    $productB = Product::create(['name' => 'Produk B', 'price' => 7000, 'stock' => 20]);

    $this->post('/sales', [
        'product_id' => $productA->id,
        'quantity' => 2,
        'price' => 5000,
        'sold_at' => '2026-02-01',
    ]);

    $sale = Sale::first();
    expect($productA->fresh()->stock)->toBe(18);

    $response = $this->put('/sales/'.$sale->id, [
        'product_id' => $productB->id,
        'quantity' => 3,
        'price' => 7000,
        'sold_at' => '2026-02-01',
    ]);

    $response->assertRedirect('/sales');

    // Stok Produk A dikembalikan +2 (dari 18 menjadi 20).
    expect($productA->fresh()->stock)->toBe(20);
    // Stok Produk B dikurangi -3 (dari 20 menjadi 17).
    expect($productB->fresh()->stock)->toBe(17);
    expect($sale->fresh()->product_id)->toBe($productB->id);
});

test('edit harga penjualan menghitung ulang total', function () {
    $product = Product::create(['name' => 'Produk A', 'price' => 5000, 'stock' => 20]);

    $this->post('/sales', [
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 5000,
        'sold_at' => '2026-02-01',
    ]);

    $sale = Sale::first();

    $this->put('/sales/'.$sale->id, [
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 9000,
        'sold_at' => '2026-02-01',
    ]);

    expect($sale->fresh()->price)->toBe(9000);
    expect($sale->fresh()->total)->toBe(18000);
});

test('edit tanggal penjualan berhasil berubah', function () {
    $product = Product::create(['name' => 'Produk A', 'price' => 5000, 'stock' => 20]);

    $this->post('/sales', [
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 5000,
        'sold_at' => '2026-02-01',
    ]);

    $sale = Sale::first();

    $this->put('/sales/'.$sale->id, [
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 5000,
        'sold_at' => '2026-03-15',
    ]);

    expect($sale->fresh()->sold_at->format('Y-m-d'))->toBe('2026-03-15');
});

test('uuid penjualan tetap sama setelah edit', function () {
    $product = Product::create(['name' => 'Produk A', 'price' => 5000, 'stock' => 20]);

    $this->post('/sales', [
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 5000,
        'sold_at' => '2026-02-01',
    ]);

    $sale = Sale::first();
    $originalUuid = $sale->uuid;

    $this->put('/sales/'.$sale->id, [
        'product_id' => $product->id,
        'quantity' => 4,
        'price' => 5000,
        'sold_at' => '2026-02-01',
    ]);

    expect($sale->fresh()->uuid)->toBe($originalUuid);
});

test('edit penjualan tidak boleh membuat stok negatif dan tidak mengubah apapun jika gagal', function () {
    $product = Product::create(['name' => 'Produk A', 'price' => 5000, 'stock' => 20]);

    $this->post('/sales', [
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 5000,
        'sold_at' => '2026-02-01',
    ]);

    $sale = Sale::first();
    expect($product->fresh()->stock)->toBe(18);

    // 18 stok saat ini + 2 yang dikembalikan dari transaksi lama = 20
    // stok efektif tersedia. Minta quantity 25 harus ditolak.
    $response = $this->put('/sales/'.$sale->id, [
        'product_id' => $product->id,
        'quantity' => 25,
        'price' => 5000,
        'sold_at' => '2026-02-01',
    ]);

    $response->assertSessionHasErrors('quantity');

    // Tidak ada perubahan sama sekali: stok & data transaksi tetap sama.
    expect($product->fresh()->stock)->toBe(18);
    expect($sale->fresh()->quantity)->toBe(2);
});

test('hapus penjualan mengembalikan stok produk', function () {
    $product = Product::create(['name' => 'Produk A', 'price' => 5000, 'stock' => 20]);

    $this->post('/sales', [
        'product_id' => $product->id,
        'quantity' => 5,
        'price' => 5000,
        'sold_at' => '2026-02-01',
    ]);

    $sale = Sale::first();
    expect($product->fresh()->stock)->toBe(15);

    $response = $this->delete('/sales/'.$sale->id);

    $response->assertRedirect('/sales');

    expect($product->fresh()->stock)->toBe(20);
    $this->assertDatabaseCount('sales', 0);
});
