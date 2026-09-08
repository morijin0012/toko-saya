<?php

namespace App\Services;

use App\Models\BackupArchive;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Restock;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ArchiveManager
{
    public function __construct(
        private readonly MonthlyReportGenerator $reportGenerator,
    ) {}

    public function shouldArchive(string $month): bool
    {
        $archive = BackupArchive::query()
            ->where('month', $month)
            ->first();

        if (! $archive) {
            return true;
        }

        /*
         * Arsip pernah dihapus.
         *
         * Jangan buat ulang hanya karena transaksi lama masih ada.
         * Arsip hanya dibuat ulang jika ada transaksi BARU setelah
         * marker saat arsip dihapus.
         */
        if ($archive->status === 'deleted') {
            $markerJson = $archive->deleted_transaction_marker;

            if (! $markerJson) {
                return false;
            }

            $marker = json_decode($markerJson, true);

            if (! is_array($marker)) {
                return false;
            }

            $start = Carbon::createFromFormat('Y-m', $month)
                ->startOfMonth();

            $end = $start->copy()->endOfMonth();

            $newSale = Sale::query()
                ->where('id', '>', (int) ($marker['sales_id'] ?? 0))
                ->whereBetween('sold_at', [
                    $start->toDateString(),
                    $end->toDateString(),
                ])
                ->exists();

            $newRestock = Restock::query()
                ->where('id', '>', (int) ($marker['restocks_id'] ?? 0))
                ->whereBetween('created_at', [$start, $end])
                ->exists();

            $newExpense = Expense::query()
                ->where('id', '>', (int) ($marker['expenses_id'] ?? 0))
                ->whereBetween('expense_date', [
                    $start->toDateString(),
                    $end->toDateString(),
                ])
                ->exists();

            return $newSale || $newRestock || $newExpense;
        }

        /*
         * Arsip tercatat sebagai archived tetapi salah satu file hilang.
         * Izinkan archive() membuat file yang hilang.
         */
        if ($archive->status === 'archived') {
            return ! $this->filesExist($month);
        }

        return true;
    }

    public function archive(string $month): void
    {
        $directory = "backups/{$month}";

        Storage::disk('local')->makeDirectory($directory);

        $start = Carbon::createFromFormat('Y-m', $month)
            ->startOfMonth();

        $end = $start->copy()->endOfMonth();

        $jsonPath = "{$directory}/toko-saya-backup-{$month}.json";
        $txtPath = "{$directory}/laporan-{$month}.txt";

        if (! Storage::disk('local')->exists($jsonPath)) {
            $payload = $this->buildPayload(
                $month,
                $start,
                $end
            );

            Storage::disk('local')->put(
                $jsonPath,
                json_encode(
                    $payload,
                    JSON_PRETTY_PRINT |
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                )
            );
        }

        if (! Storage::disk('local')->exists($txtPath)) {
            Storage::disk('local')->put(
                $txtPath,
                $this->reportGenerator->generate($month)
            );
        }

        BackupArchive::updateOrCreate(
            ['month' => $month],
            [
                'status' => 'archived',
                'archived_at' => now(),
                'deleted_at' => null,
                'deleted_transaction_marker' => null,
            ]
        );
    }

    public function delete(string $month): void
    {
        $start = Carbon::createFromFormat('Y-m', $month)
            ->startOfMonth();

        $end = $start->copy()->endOfMonth();

        /*
         * Simpan ID transaksi terakhir yang sudah termasuk
         * dalam arsip sebelum arsip tersebut dihapus.
         *
         * Transaksi baru setelah penghapusan akan memiliki ID
         * yang lebih besar dan dapat dideteksi secara deterministik.
         */
        $marker = [
            'sales_id' => (int) Sale::query()
                ->whereBetween('sold_at', [
                    $start->toDateString(),
                    $end->toDateString(),
                ])
                ->max('id'),

            'restocks_id' => (int) Restock::query()
                ->whereBetween('created_at', [$start, $end])
                ->max('id'),

            'expenses_id' => (int) Expense::query()
                ->whereBetween('expense_date', [
                    $start->toDateString(),
                    $end->toDateString(),
                ])
                ->max('id'),
        ];

        Storage::disk('local')->deleteDirectory(
            "backups/{$month}"
        );

        BackupArchive::updateOrCreate(
            ['month' => $month],
            [
                'status' => 'deleted',
                'deleted_at' => now(),
                'archived_at' => null,
                'deleted_transaction_marker' => json_encode(
                    $marker,
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                ),
            ]
        );
    }

    public function filesExist(string $month): bool
    {
        return Storage::disk('local')->exists(
            "backups/{$month}/toko-saya-backup-{$month}.json"
        ) && Storage::disk('local')->exists(
            "backups/{$month}/laporan-{$month}.txt"
        );
    }

    private function buildPayload(
        string $month,
        Carbon $start,
        Carbon $end,
    ): array {
        $products = Product::query()
            ->get()
            ->map(function (Product $product) {
                return [
                    'id' => $product->id,
                    'uuid' => $product->uuid,
                    'name' => $product->name,
                    'price' => $product->price,
                    'stock' => $product->stock,
                    'created_at' => $product->created_at?->toISOString(),
                    'updated_at' => $product->updated_at?->toISOString(),
                ];
            })
            ->values()
            ->all();

        $sales = Sale::with('product')
            ->whereBetween('sold_at', [$start, $end])
            ->get()
            ->map(function (Sale $sale) {
                return [
                    'id' => $sale->id,
                    'uuid' => $sale->uuid,
                    'product_uuid' => $sale->product?->uuid,
                    'quantity' => $sale->quantity,
                    'price' => $sale->price,
                    'total' => $sale->total,
                    'sold_at' => $sale->sold_at,
                    'created_at' => $sale->created_at?->toISOString(),
                    'updated_at' => $sale->updated_at?->toISOString(),
                ];
            })
            ->values()
            ->all();

        $restocks = Restock::with('product')
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->map(function (Restock $restock) {
                return [
                    'id' => $restock->id,
                    'uuid' => $restock->uuid,
                    'product_uuid' => $restock->product?->uuid,
                    'quantity' => $restock->quantity,
                    'created_at' => $restock->created_at?->toISOString(),
                    'updated_at' => $restock->updated_at?->toISOString(),
                ];
            })
            ->values()
            ->all();

        $expenses = Expense::query()
            ->whereBetween('expense_date', [$start, $end])
            ->get()
            ->map(function (Expense $expense) {
                return [
                    'id' => $expense->id,
                    'uuid' => $expense->uuid,
                    'name' => $expense->name,
                    'category' => $expense->category,
                    'amount' => $expense->amount,
                    'expense_date' => $expense->expense_date,
                    'note' => $expense->note,
                    'created_at' => $expense->created_at?->toISOString(),
                    'updated_at' => $expense->updated_at?->toISOString(),
                ];
            })
            ->values()
            ->all();

        return [
            'backup_version' => '2.1',
            'period' => $month,
            'exported_at' => Carbon::now()->toISOString(),
            'products' => $products,
            'sales' => $sales,
            'restocks' => $restocks,
            'expenses' => $expenses,
        ];
    }
}