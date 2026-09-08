<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::with('product')
            ->latest('sold_at')
            ->latest()
            ->get();

        $products = Product::orderBy('name')->get();

        return view('sales.index', [
            'sales' => $sales,
            'products' => $products,
        ]);
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();

        return view('sales.create', [
            'products' => $products,
        ]);
    }

    /**
     * Simpan penjualan: validasi stok, kurangi stok produk, dan catat
     * transaksi. Dibungkus transaction supaya stok dan riwayat penjualan
     * selalu konsisten (tidak ada keadaan stok berkurang tanpa tercatat,
     * atau tercatat tanpa stok berkurang).
     */
    public function store(Request $request)
    {
        $request->merge([
        'price' => preg_replace(
            '/\D/',
            '',
            (string) $request->price
        ),
    ]);
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|integer|min:0',
            'sold_at' => 'required|date',
        ]);

        try {
            $result = DB::transaction(function () use ($request) {
                // Kunci baris produk supaya aman dari kondisi balapan
                // (mis. dua penjualan disimpan hampir bersamaan).
                $product = Product::whereKey($request->product_id)->lockForUpdate()->firstOrFail();

                if ($request->quantity > $product->stock) {
                    return [
                        'error' => 'Jumlah terjual melebihi stok yang tersedia (stok saat ini: '.$product->stock.').',
                    ];
                }

                $total = $request->quantity * $request->price;

                Sale::create([
                    'product_id' => $product->id,
                    'quantity' => $request->quantity,
                    'price' => $request->price,
                    'total' => $total,
                    'sold_at' => $request->sold_at,
                ]);

                // Stok berkurang otomatis setelah penjualan.
                $product->stock -= $request->quantity;
                $product->save();

                return ['error' => null];
            });
        } catch (Throwable $e) {
            Log::error('Penjualan gagal: '.$e->getMessage());

            return back()
                ->withErrors(['quantity' => 'Penjualan gagal disimpan. Tidak ada perubahan yang tersimpan.'])
                ->withInput();
        }

        if ($result['error']) {
            return back()
                ->withErrors(['quantity' => $result['error']])
                ->withInput();
        }

        return redirect('/sales')->with('success', 'Penjualan berhasil dicatat.');
    }

    /**
     * Perbarui transaksi penjualan sekaligus mengoreksi stok produk lama
     * dan/atau produk baru. UUID transaksi sengaja TIDAK disentuh sama
     * sekali di sini (tidak ada di $sale->update([...])) supaya nilainya
     * tetap sama seperti sebelum edit.
     *
     * Koreksi stok:
     * 1. Stok produk LAMA dikembalikan dulu sebesar quantity LAMA (seolah
     *    transaksi lama dibatalkan).
     * 2. Baru kemudian stok produk BARU (bisa produk yang sama atau
     *    berbeda) dikurangi sebesar quantity BARU.
     * Jika produk lama & baru sama, langkah di atas otomatis menghasilkan
     * penyesuaian selisih (naik/turun) pada produk yang sama.
     */
    public function update(Request $request, Sale $sale)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|integer|min:0',
            'sold_at' => 'required|date',
        ]);

        try {
            $result = DB::transaction(function () use ($request, $sale) {
                $oldProductId = $sale->product_id;
                $newProductId = (int) $request->product_id;

                // Kunci baris produk yang terlibat (urutan id menaik supaya
                // konsisten dan menghindari deadlock jika ada dua edit
                // berbarengan yang menyentuh dua produk yang sama).
                $products = Product::whereIn('id', [$oldProductId, $newProductId])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $oldProduct = $products[$oldProductId];
                $newProduct = $products[$newProductId];

                // Langkah 1: kembalikan stok produk lama sebesar quantity lama.
                // Jika produk lama & baru sama, $oldProduct dan $newProduct
                // adalah objek yang sama (referensi sama dari keyBy), jadi
                // perubahan ini langsung "terlihat" oleh $newProduct juga.
                $oldProduct->stock += $sale->quantity;

                // Langkah 2: validasi & kurangi stok produk baru.
                if ($request->quantity > $newProduct->stock) {
                    return [
                        'error' => 'Jumlah terjual melebihi stok yang tersedia (stok saat ini: '.$newProduct->stock.').',
                    ];
                }

                $newProduct->stock -= $request->quantity;

                $oldProduct->save();

                if ($newProduct->id !== $oldProduct->id) {
                    $newProduct->save();
                }

                $sale->update([
                    'product_id' => $newProduct->id,
                    'quantity' => $request->quantity,
                    'price' => $request->price,
                    'total' => $request->quantity * $request->price,
                    'sold_at' => $request->sold_at,
                ]);

                return ['error' => null];
            });
        } catch (Throwable $e) {
            Log::error('Edit penjualan gagal: '.$e->getMessage());

            return back()
                ->withErrors(['quantity' => 'Perubahan penjualan gagal disimpan. Tidak ada perubahan yang tersimpan.'])
                ->withInput();
        }

        if ($result['error']) {
            return back()
                ->withErrors(['quantity' => $result['error']])
                ->withInput();
        }

        return redirect('/sales')->with('success', 'Penjualan berhasil diperbarui.');
    }

    /**
     * Hapus transaksi penjualan dan kembalikan stok produk sebesar
     * quantity transaksi tersebut.
     */
    public function destroy(Sale $sale)
    {
        try {
            DB::transaction(function () use ($sale) {
                $product = Product::whereKey($sale->product_id)->lockForUpdate()->first();

                if ($product) {
                    $product->stock += $sale->quantity;
                    $product->save();
                }

                $sale->delete();
            });
        } catch (Throwable $e) {
            Log::error('Hapus penjualan gagal: '.$e->getMessage());

            return back()->withErrors(['sale' => 'Gagal menghapus penjualan. Silakan coba lagi.']);
        }

        return redirect('/sales')->with('success', 'Penjualan berhasil dihapus.');
    }
}
