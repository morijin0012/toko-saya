<?php

namespace App\Http\Controllers;

use App\Models\BackupArchive;
use App\Services\ArchiveManager;
use Illuminate\Support\Facades\Storage;
use Native\Mobile\Facades\Share;

class BackupArchiveController extends Controller
{
    public function index()
    {
        $archives = BackupArchive::query()
            ->where('status', 'archived')
            ->orderByDesc('month')
            ->get()
            ->map(function (BackupArchive $archive) {
                $month = $archive->month;

                return [
                    'month' => $month,
                    'json' => Storage::disk('local')->exists(
                        "backups/{$month}/toko-saya-backup-{$month}.json"
                    ),
                    'txt' => Storage::disk('local')->exists(
                        "backups/{$month}/laporan-{$month}.txt"
                    ),
                ];
            })
            ->filter(
                fn (array $archive) =>
                    $archive['json'] || $archive['txt']
            )
            ->values();

        return view('backups.index', compact('archives'));
    }

    public function share(string $month, string $type)
    {
        abort_unless(
            preg_match('/^\d{4}-\d{2}$/', $month) === 1,
            404
        );

        abort_unless(
            in_array($type, ['json', 'txt'], true),
            404
        );

        $filename = $type === 'json'
            ? "toko-saya-backup-{$month}.json"
            : "laporan-{$month}.txt";

        $relativePath = "backups/{$month}/{$filename}";

        abort_unless(
            Storage::disk('local')->exists($relativePath),
            404
        );

        $title = $type === 'json'
            ? "Backup Toko Saya {$month}"
            : "Laporan Toko Saya {$month}";

        $text = $type === 'json'
            ? "Backup data Toko Saya periode {$month}"
            : "Laporan transaksi Toko Saya periode {$month}";

        Share::file(
            $title,
            $text,
            Storage::disk('local')->path($relativePath)
        );

        return back();
    }

    public function delete(
        string $month,
        ArchiveManager $archiveManager
    ) {
        abort_unless(
            preg_match('/^\d{4}-\d{2}$/', $month) === 1,
            404
        );

        $archiveManager->delete($month);

        return back()->with(
            'success',
            'Arsip berhasil dihapus.'
        );
    }
}