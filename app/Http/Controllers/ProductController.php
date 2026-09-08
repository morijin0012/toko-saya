<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Restock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::all();

        return view('products.index', [
            'products' => $products,
        ]);
    }

    public function create()
    {
        return view('products.create');
    }

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
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'stock' => 'required|integer|min:0',
        ]);

        Product::create([
            'name' => $request->name,
            'price' => $request->price,
            'stock' => $request->stock,
        ]);

        return redirect('/products');
    }

    public function edit(Product $product)
    {
        return view('products.edit', [
            'product' => $product,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $request->merge([
    'price' => preg_replace(
        '/\D/',
        '',
        (string) $request->price
    ),
]);
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'stock' => 'required|integer|min:0',
        ]);

        $product->update([
            'name' => $request->name,
            'price' => $request->price,
            'stock' => $request->stock,
        ]);

        return redirect('/products');
    }

    /**
     * Hapus produk. Sesuai desain yang sudah ada (dan sudah diberi tahu ke
     * pengguna lewat modal konfirmasi di halaman Produk), riwayat restock
     * milik produk ini ikut terhapus otomatis lewat foreign key cascade di
     * database — perilaku ini TIDAK diubah. Yang ditambahkan di sini hanya
     * penanganan error supaya kegagalan tidak menampilkan halaman error PHP.
     */
    public function destroy(Product $product)
    {
        try {
            $product->delete();
        } catch (Throwable $e) {
            Log::error('Hapus produk gagal: '.$e->getMessage());

            return back()->withErrors([
                'product' => 'Gagal menghapus produk. Silakan coba lagi.',
            ]);
        }

        return redirect('/products');
    }

    public function createRestock()
    {
        $products = Product::all();

        return view('restocks.create', [
            'products' => $products,
        ]);
    }

    /**
     * Simpan restock: tambah stok produk + catat riwayat restock.
     * Dibungkus transaction supaya kedua langkah selalu berhasil bersamaan,
     * atau tidak sama sekali (tidak ada keadaan stok bertambah tanpa riwayat,
     * atau sebaliknya).
     */
    public function storeRestock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $product = Product::findOrFail($request->product_id);

                $product->stock += $request->quantity;
                $product->save();

                Restock::create([
                    'product_id' => $product->id,
                    'quantity' => $request->quantity,
                ]);
            });
        } catch (Throwable $e) {
            Log::error('Restock gagal: '.$e->getMessage());

            return back()
                ->withErrors(['quantity' => 'Restock gagal disimpan. Tidak ada perubahan yang tersimpan.'])
                ->withInput();
        }

        return redirect('/products');
    }

    public function restocks()
    {
        $restocks = Restock::with('product')
            ->latest()
            ->get();

        return view('restocks.index', [
            'restocks' => $restocks,
        ]);
    }

    /**
     * Hapus SATU riwayat restock.
     * Tidak menghapus Product, tidak mengubah stok saat ini.
     * Ini murni membersihkan histori, bukan membatalkan restock.
     */
    public function destroyRestock(Restock $restock)
    {
        try {
            $restock->delete();
        } catch (Throwable $e) {
            Log::error('Hapus riwayat restock gagal: '.$e->getMessage());

            return back()->withErrors(['restock' => 'Gagal menghapus riwayat restock.']);
        }

        return redirect('/restocks')->with('success', 'Riwayat restock berhasil dihapus.');
    }

    /**
     * Hapus riwayat restock berdasarkan bulan.
     * Tidak menghapus Product, tidak mengubah stok saat ini.
     * Data Sale dan Expense TIDAK ikut terhapus (hanya Restock).
     */
    public function bulkDestroyRestocks(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'confirm' => 'required|in:1',
        ]);

        try {
            $date = Carbon::createFromFormat('Y-m', $request->month);
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();

            DB::transaction(function () use ($start, $end) {
                Restock::whereBetween('created_at', [$start, $end])->delete();
            });
        } catch (Throwable $e) {
            Log::error('Hapus riwayat restock bulanan gagal: '.$e->getMessage());

            return back()->withErrors(['month' => 'Gagal menghapus riwayat restock bulan tersebut.']);
        }

        return redirect('/restocks')->with('success', 'Riwayat restock bulan tersebut berhasil dihapus.');
    }
}
