<?php

use App\Models\Product;

test('produk baru dapat dibuat', function () {
    $response = $this->post('/products', [
        'name' => 'Kopi Sachet',
        'price' => 2000,
        'stock' => 50,
    ]);

    $response->assertRedirect('/products');

    $this->assertDatabaseHas('products', [
        'name' => 'Kopi Sachet',
        'price' => 2000,
        'stock' => 50,
    ]);
});

test('produk dapat diupdate', function () {
    $product = Product::create(['name' => 'Teh Botol', 'price' => 5000, 'stock' => 10]);

    $response = $this->put("/products/{$product->id}", [
        'name' => 'Teh Botol Besar',
        'price' => 6000,
        'stock' => 15,
    ]);

    $response->assertRedirect('/products');

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Teh Botol Besar',
        'price' => 6000,
        'stock' => 15,
    ]);
});

test('produk dapat dihapus', function () {
    $product = Product::create(['name' => 'Rokok A', 'price' => 25000, 'stock' => 5]);

    $response = $this->delete("/products/{$product->id}");

    $response->assertRedirect('/products');

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});
