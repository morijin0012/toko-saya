<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Restock;
use App\Models\Sale;
use Carbon\Carbon;

class MonthlyReportGenerator
{
    public function generate(string $month): string
    {
        $date = Carbon::createFromFormat('Y-m', $month);

        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        $sales = Sale::with('product')
            ->whereBetween('sold_at', [$start, $end])
            ->get()
            ->map(function (Sale $sale) {
                $transactionDate = Carbon::parse(
                    $sale->sold_at ?? $sale->created_at
                );

                $createdAt = Carbon::parse($sale->created_at);

                $transactionDate->setTime(
                    $createdAt->hour,
                    $createdAt->minute,
                    $createdAt->second
                );

                return [
                    'type' => 'sale',
                    'date' => $transactionDate,
                    'product' => $sale->product?->name ?? 'Produk tidak ditemukan',
                    'quantity' => (int) $sale->quantity,
                    'price' => (float) $sale->price,
                    'total' => (float) $sale->total,
                ];
            });

        $restocks = Restock::with('product')
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->map(function (Restock $restock) {
                return [
                    'type' => 'restock',
                    'date' => Carbon::parse($restock->created_at),
                    'product' => $restock->product?->name ?? 'Produk tidak ditemukan',
                    'quantity' => (int) $restock->quantity,
                ];
            });

        $expenses = Expense::whereBetween('expense_date', [$start, $end])
            ->get()
            ->map(function (Expense $expense) {
                $transactionDate = Carbon::parse($expense->expense_date);
                $createdAt = Carbon::parse($expense->created_at);

                $transactionDate->setTime(
                    $createdAt->hour,
                    $createdAt->minute,
                    $createdAt->second
                );

                return [
                    'type' => 'expense',
                    'date' => $transactionDate,
                    'name' => $expense->name,
                    'category' => $expense->category,
                    'amount' => (float) $expense->amount,
                    'note' => $expense->note,
                ];
            });

        $transactions = $sales
            ->concat($restocks)
            ->concat($expenses)
            ->sortBy(function (array $item) {
                return $item['date']->timestamp;
            })
            ->values();

        $totalSales = $sales->sum('total');
        $totalExpenses = $expenses->sum('amount');
        $netResult = $totalSales - $totalExpenses;

        $monthLabel = $date->translatedFormat('F Y');

        $lines = [];

        $lines[] = '========================================';
        $lines[] = '              TOKO SAYA';
        $lines[] = '        LAPORAN '.$monthLabel;
        $lines[] = '========================================';
        $lines[] = '';

        $currentDate = null;

        foreach ($transactions as $transaction) {
            $transactionDate = $transaction['date'];
            $dateLabel = $transactionDate->format('d-m-Y');

            if ($currentDate !== $dateLabel) {
                if ($currentDate !== null) {
                    $lines[] = '';
                    $lines[] = '----------------------------------------';
                    $lines[] = '';
                }

                $currentDate = $dateLabel;

                $lines[] = $dateLabel;
                $lines[] = '';
            }

            $lines[] = $transactionDate->format('H:i');

            if ($transaction['type'] === 'sale') {
                $lines[] = 'PENJUALAN';
                $lines[] = 'Produk : '.$transaction['product'];
                $lines[] = 'Jumlah : '.$transaction['quantity'];
                $lines[] = 'Harga  : '.$this->rupiah($transaction['price']);
                $lines[] = 'Total  : '.$this->rupiah($transaction['total']);
            } elseif ($transaction['type'] === 'restock') {
                $lines[] = 'RESTOCK';
                $lines[] = 'Produk : '.$transaction['product'];
                $lines[] = 'Jumlah : '.$transaction['quantity'];
            } else {
                $lines[] = 'PENGELUARAN';
                $lines[] = 'Keterangan : '.$transaction['name'];
                $lines[] = 'Kategori   : '.$transaction['category'];
                $lines[] = 'Jumlah     : '.$this->rupiah($transaction['amount']);

                if (! empty($transaction['note'])) {
                    $lines[] = 'Catatan    : '.$transaction['note'];
                }
            }

            $lines[] = '';
        }

        if ($transactions->isEmpty()) {
            $lines[] = 'Tidak ada transaksi pada bulan ini.';
            $lines[] = '';
        }

        $lines[] = '========================================';
        $lines[] = '      RINGKASAN '.$monthLabel;
        $lines[] = '========================================';
        $lines[] = '';
        $lines[] = 'TOTAL PENJUALAN';
        $lines[] = $this->rupiah($totalSales);
        $lines[] = '';
        $lines[] = 'TOTAL PENGELUARAN';
        $lines[] = $this->rupiah($totalExpenses);
        $lines[] = '';
        $lines[] = 'HASIL BERSIH';
        $lines[] = $this->rupiah($netResult);
        $lines[] = '';

        if ($netResult >= 0) {
            $lines[] = 'STATUS: UNTUNG';
            $lines[] = $this->rupiah($netResult);
        } else {
            $lines[] = 'STATUS: RUGI';
            $lines[] = $this->rupiah(abs($netResult));
        }

        $lines[] = '';
        $lines[] = '========================================';

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    private function rupiah(float|int $amount): string
    {
        $sign = $amount < 0 ? '-' : '';

        return $sign.'Rp '.number_format(
            abs($amount),
            0,
            ',',
            '.'
        );
    }
}
