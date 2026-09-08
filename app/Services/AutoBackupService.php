<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Restock;
use App\Models\Sale;
use Carbon\Carbon;

class AutoBackupService
{
    public function __construct(
        private readonly ArchiveManager $archiveManager,
    ) {}

    /**
     * Mengarsipkan semua bulan transaksi yang sudah selesai
     * dan belum ditandai sebagai dihapus oleh pemilik.
     */
    public function run(?string $currentMonth = null): array
    {
        $currentMonth ??= Carbon::now()->format('Y-m');

        $lastCompletedMonth = Carbon::createFromFormat('Y-m', $currentMonth)
            ->subMonth()
            ->format('Y-m');

        $months = $this->transactionMonthsBefore($lastCompletedMonth);

        $processed = [];

        foreach ($months as $month) {
            if (! $this->archiveManager->shouldArchive($month)) {
                continue;
            }

            $this->archiveManager->archive($month);

            $processed[] = $month;
        }

        return $processed;
    }

    /**
     * Mencari semua bulan yang memiliki transaksi dan sudah selesai.
     *
     * Tidak dibatasi tahun tertentu sehingga bisa dipakai customer
     * selama bertahun-tahun.
     */
    private function transactionMonthsBefore(string $lastCompletedMonth): array
    {
        $months = collect();

        Sale::query()
            ->whereNotNull('sold_at')
            ->pluck('sold_at')
            ->each(function ($date) use ($months, $lastCompletedMonth) {
                $month = Carbon::parse($date)->format('Y-m');

                if ($month <= $lastCompletedMonth) {
                    $months->push($month);
                }
            });

        Restock::query()
            ->whereNotNull('created_at')
            ->pluck('created_at')
            ->each(function ($date) use ($months, $lastCompletedMonth) {
                $month = Carbon::parse($date)->format('Y-m');

                if ($month <= $lastCompletedMonth) {
                    $months->push($month);
                }
            });

        Expense::query()
            ->whereNotNull('expense_date')
            ->pluck('expense_date')
            ->each(function ($date) use ($months, $lastCompletedMonth) {
                $month = Carbon::parse($date)->format('Y-m');

                if ($month <= $lastCompletedMonth) {
                    $months->push($month);
                }
            });

        return $months
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
