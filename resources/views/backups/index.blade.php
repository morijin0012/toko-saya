
@extends('layouts.app')

@section('content')
<div class="page-shell">


    <div class="page-header">
<div class="page-shell">
    <div class="page-header">   
        <div>
            <h1>Arsip Backup</h1>
            <p>Backup dan laporan bulanan yang tersimpan di perangkat.</p>
        </div>
    </div>

    @if ($archives->isEmpty())
        <div class="card">
            <div class="empty-state">
                <div class="empty-state-icon">📦</div>
                <strong>Belum ada arsip</strong>
                <span>
                    Arsip akan dibuat otomatis setelah sebuah bulan selesai.
                </span>
            </div>
        </div>
    @else
        <div class="backup-archive-list">
            @foreach ($archives as $archive)
                @php
                    $monthDate = \Carbon\Carbon::createFromFormat(
                        'Y-m',
                        $archive['month']
                    );
                @endphp

                <div class="card backup-archive-card">
                    <div class="backup-archive-header">
                        <div>
                            <span class="backup-archive-label">
                                PERIODE
                            </span>

                            <h2>
                                {{ $monthDate->translatedFormat('F Y') }}
                            </h2>

                            <small>
                                {{ $archive['month'] }}
                            </small>
                        </div>

                        <div class="backup-archive-status">
                            <span class="backup-status-dot"></span>
                            Tersimpan
                        </div>
                    </div>

                    <div class="backup-archive-files">
                        @if ($archive['txt'])
                            <a
                                href="{{ route('backups.share', [
                                    'month' => $archive['month'],
                                    'type' => 'txt',
                                ]) }}"
                                class="backup-file-button"
                            >
                                <span class="backup-file-icon">📄</span>

                                <span class="backup-file-content">
                                    <strong>Laporan TXT</strong>
                                    <small>
                                        Laporan transaksi bulanan
                                    </small>
                                </span>

                                <span class="backup-file-arrow">›</span>
                            </a>
                        @endif
                        <form
                        method="POST"
                        action="{{ route('backups.delete', $archive['month']) }}"
                        onsubmit="return confirm('Hapus arsip {{ $monthDate->translatedFormat('F Y') }}? File TXT dan JSON akan dihapus dari perangkat. Data transaksi tetap aman.');"
                    >
                        @csrf
                        @method('DELETE')

                        <button type="submit" class="backup-delete-button">
                            <span aria-hidden="true">🗑</span>
                            <span>Hapus Arsip</span>
                        </button>
                    </form>
                        </div>
                        @if ($archive['json'])
                            <a
                                href="{{ route('backups.share', [
                                    'month' => $archive['month'],
                                    'type' => 'json',
                                ]) }}"
                                class="backup-file-button"
                            >
                                <span class="backup-file-icon">💾</span>

                                <span class="backup-file-content">
                                    <strong>Backup JSON</strong>
                                    <small>
                                        Cadangan data untuk pemulihan
                                    </small>
                                </span>

                                <span class="backup-file-arrow">›</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection