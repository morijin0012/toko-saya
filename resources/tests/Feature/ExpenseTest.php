<?php

use App\Models\Expense;
use App\Models\Product;
use Carbon\Carbon;

test('edit pengeluaran berhasil mengubah data', function () {
    $this->post('/expenses', [
        'name' => 'Beli plastik kemasan',
        'category' => 'Operasional',
        'amount' => 50000,
        'expense_date' => '2026-02-01',
        'note' => null,
    ]);

    $expense = Expense::first();

    $response = $this->put('/expenses/'.$expense->id, [
        'name' => 'Beli plastik kemasan (tambahan)',
        'category' => 'Pembelian Barang',
        'amount' => 75000,
        'expense_date' => '2026-02-05',
        'note' => 'Stok menipis',
    ]);

    $response->assertRedirect('/expenses');

    $fresh = $expense->fresh();
    expect($fresh->name)->toBe('Beli plastik kemasan (tambahan)');
    expect($fresh->category)->toBe('Pembelian Barang');
});

test('edit amount pengeluaran berhasil', function () {
    $this->post('/expenses', [
        'name' => 'Listrik toko',
        'category' => 'Listrik',
        'amount' => 100000,
        'expense_date' => '2026-02-01',
    ]);

    $expense = Expense::first();

    $this->put('/expenses/'.$expense->id, [
        'name' => $expense->name,
        'category' => $expense->category,
        'amount' => 120000,
        'expense_date' => '2026-02-01',
    ]);

    expect($expense->fresh()->amount)->toBe(120000);
});

test('edit tanggal pengeluaran berhasil', function () {
    $this->post('/expenses', [
        'name' => 'Transport barang',
        'category' => 'Transportasi',
        'amount' => 30000,
        'expense_date' => '2026-02-01',
    ]);

    $expense = Expense::first();

    $this->put('/expenses/'.$expense->id, [
        'name' => $expense->name,
        'category' => $expense->category,
        'amount' => $expense->amount,
        'expense_date' => '2026-03-10',
    ]);

    expect(Carbon::parse($expense->fresh()->expense_date)->format('Y-m-d'))->toBe('2026-03-10');
});

test('edit note pengeluaran berhasil', function () {
    $this->post('/expenses', [
        'name' => 'Beli galon air',
        'category' => 'Operasional',
        'amount' => 20000,
        'expense_date' => '2026-02-01',
    ]);

    $expense = Expense::first();

    $this->put('/expenses/'.$expense->id, [
        'name' => $expense->name,
        'category' => $expense->category,
        'amount' => $expense->amount,
        'expense_date' => '2026-02-01',
        'note' => 'Untuk galon cadangan',
    ]);

    expect($expense->fresh()->note)->toBe('Untuk galon cadangan');
});

test('uuid pengeluaran tetap sama setelah edit', function () {
    $this->post('/expenses', [
        'name' => 'Beli kantong plastik',
        'category' => 'Pembelian Barang',
        'amount' => 15000,
        'expense_date' => '2026-02-01',
    ]);

    $expense = Expense::first();
    $originalUuid = $expense->uuid;

    $this->put('/expenses/'.$expense->id, [
        'name' => 'Beli kantong plastik besar',
        'category' => $expense->category,
        'amount' => 25000,
        'expense_date' => '2026-02-01',
    ]);

    expect($expense->fresh()->uuid)->toBe($originalUuid);
});

test('hapus pengeluaran berhasil dan tidak memengaruhi stok produk', function () {
    $product = Product::create(['name' => 'Produk A', 'price' => 5000, 'stock' => 20]);

    $this->post('/expenses', [
        'name' => 'Beli plastik kemasan',
        'category' => 'Operasional',
        'amount' => 50000,
        'expense_date' => '2026-02-01',
    ]);

    $expense = Expense::first();

    $response = $this->delete('/expenses/'.$expense->id);

    $response->assertRedirect('/expenses');

    $this->assertDatabaseCount('expenses', 0);

    // Stok produk tidak boleh berubah sama sekali karena pengeluaran
    // tidak pernah menyentuh stok.
    expect($product->fresh()->stock)->toBe(20);
});
