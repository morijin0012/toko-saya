<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::latest('expense_date')
            ->latest()
            ->get();

        return view('expenses.index', [
            'expenses' => $expenses,
        ]);
    }

    public function create()
    {
        return view('expenses.create');
    }

    public function store(Request $request)
    {
        $request->merge([
        'amount' => preg_replace('/\D/', '', (string) $request->amount),
    ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'amount' => 'required|integer|min:0',
            'expense_date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        try {
            Expense::create([
                'name' => $request->name,
                'category' => $request->category,
                'amount' => $request->amount,
                'expense_date' => $request->expense_date,
                'note' => $request->note,
            ]);
        } catch (Throwable $e) {
            Log::error('Simpan pengeluaran gagal: '.$e->getMessage());

            return back()
                ->withErrors(['amount' => 'Pengeluaran gagal disimpan. Silakan coba lagi.'])
                ->withInput();
        }

        return redirect('/expenses')->with('success', 'Pengeluaran berhasil dicatat.');
    }

    /**
     * Perbarui data pengeluaran. Tidak ada stok produk yang terpengaruh
     * sama sekali oleh pengeluaran, jadi tidak ada koreksi stok di sini.
     * UUID transaksi sengaja TIDAK disentuh (tidak ada di array update)
     * supaya nilainya tetap sama seperti sebelum edit.
     */
    public function update(Request $request, Expense $expense)
    {
        $request->merge([
        'amount' => preg_replace('/\D/', '', (string) $request->amount),
    ]);


        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'amount' => 'required|integer|min:0',
            'expense_date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($request, $expense) {
                $expense->update([
                    'name' => $request->name,
                    'category' => $request->category,
                    'amount' => $request->amount,
                    'expense_date' => $request->expense_date,
                    'note' => $request->note,
                ]);
            });
        } catch (Throwable $e) {
            Log::error('Edit pengeluaran gagal: '.$e->getMessage());

            return back()
                ->withErrors(['amount' => 'Perubahan pengeluaran gagal disimpan. Silakan coba lagi.'])
                ->withInput();
        }

        return redirect('/expenses')->with('success', 'Pengeluaran berhasil diperbarui.');
    }

    /**
     * Hapus transaksi pengeluaran. Tidak memengaruhi stok produk sama
     * sekali.
     */
    public function destroy(Expense $expense)
    {
        try {
            DB::transaction(function () use ($expense) {
                $expense->delete();
            });
        } catch (Throwable $e) {
            Log::error('Hapus pengeluaran gagal: '.$e->getMessage());

            return back()->withErrors(['expense' => 'Gagal menghapus pengeluaran. Silakan coba lagi.']);
        }

        return redirect('/expenses')->with('success', 'Pengeluaran berhasil dihapus.');
    }
}
