<?php

use App\Models\Product;
use App\Models\Restock;

test('restock menambah stok produk dan mencatat riwayat', function () {
    $product = Product::create(['name' => 'Gula 1kg', 'price' => 15000, 'stock' => 10]);

    $response = $this->post('/restocks', [
        'product_id' => $product->id,
        'quantity' => 20,
    ]);

    $response->assertRedirect('/products');

    expect($product->fresh()->stock)->toBe(30);

    $this->assertDatabaseHas('restocks', [
        'product_id' => $product->id,
        'quantity' => 20,
    ]);
});

test('hapus satu riwayat restock tidak mengubah stok saat ini', function () {
    $product = Product::create(['name' => 'Minyak Goreng', 'price' => 20000, 'stock' => 40]);

    $restock = Restock::create(['product_id' => $product->id, 'quantity' => 10]);

    $stockBefore = $product->fresh()->stock;

    $response = $this->delete("/restocks/{$restock->id}");

    $response->assertRedirect('/restocks');

    $this->assertDatabaseMissing('restocks', ['id' => $restock->id]);
    expect($product->fresh()->stock)->toBe($stockBefore);
});

test('hapus riwayat restock bulanan tidak mengubah stok dan tidak menyentuh bulan lain', function () {
    $product = Product::create(['name' => 'Beras 5kg', 'price' => 65000, 'stock' => 100]);

    $inMonth = Restock::create(['product_id' => $product->id, 'quantity' => 5]);
    $inMonth->timestamps = false;
    $inMonth->created_at = '2026-02-10 09:00:00';
    $inMonth->updated_at = '2026-02-10 09:00:00';
    $inMonth->save();

    $otherMonth = Restock::create(['product_id' => $product->id, 'quantity' => 7]);
    $otherMonth->timestamps = false;
    $otherMonth->created_at = '2026-03-10 09:00:00';
    $otherMonth->updated_at = '2026-03-10 09:00:00';
    $otherMonth->save();

    $stockBefore = $product->fresh()->stock;

    $response = $this->delete('/restocks', [
        'month' => '2026-02',
        'confirm' => '1',
    ]);

    $response->assertRedirect('/restocks');

    $this->assertDatabaseMissing('restocks', ['id' => $inMonth->id]);
    $this->assertDatabaseHas('restocks', ['id' => $otherMonth->id]);
    expect($product->fresh()->stock)->toBe($stockBefore);
});
