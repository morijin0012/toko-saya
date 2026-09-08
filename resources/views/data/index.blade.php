@extends('layouts.app')

@section('title', 'Kelola Data')

@section('content')

<div class="container fade-in">

    <div class="page-header">
        <div class="page-header-main">
            <div class="page-header-icon icon-accent-purple">
                @include('partials.icon', ['name' => 'database'])
            </div>
            <div>
                <h1>Kelola Data</h1>
                <p>Backup atau hapus data transaksi per bulan.</p>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="success-box">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="error-box">
            <strong>Terjadi kesalahan:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    {{-- ===== RINGKASAN BULAN ===== --}}

    <div class="section">

        <div class="section-header">
            <h2>Ringkasan Bulan</h2>
        </div>

        <form method="GET" action="/data" class="month-picker-form">

            <div class="form-group month-picker-group">
                <label for="month">Pilih Bulan</label>
                <select id="month" name="month" onchange="this.form.submit()">
                    @foreach ($monthOptions as $option)
                        <option value="{{ $option['value'] }}" {{ $option['value'] === $selectedMonth ? 'selected' : '' }}>
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

        </form>

        <div class="data-summary">

            <div class="data-summary-item">
                @include('partials.icon', ['name' => 'sale', 'class' => 'icon-sm'])
                <div>
                    <strong>{{ $salesCount }}</strong>
                    <span>Penjualan</span>
                </div>
            </div>

            <div class="data-summary-item">
                @include('partials.icon', ['name' => 'restock', 'class' => 'icon-sm'])
                <div>
                    <strong>{{ $restocksCount }}</strong>
                    <span>Restock</span>
                </div>
            </div>

            <div class="data-summary-item">
                @include('partials.icon', ['name' => 'expense', 'class' => 'icon-sm'])
                <div>
                    <strong>{{ $expensesCount }}</strong>
                    <span>Pengeluaran</span>
                </div>
            </div>

        </div>

        <p class="data-summary-caption">
            Ringkasan data transaksi untuk <strong>{{ $selectedMonthLabel }}</strong>. Data produk (nama, harga, stok saat ini) tidak termasuk dan tidak akan pernah dihapus dari sini.
        </p>

    </div>


    {{-- ===== BACKUP ===== --}}

    <div class="section">

        <div class="section-header">
            <h2>Backup</h2>
        </div>

        <div class="info-row">
            <div class="info-row-icon">
                @include('partials.icon', ['name' => 'shield-check'])
            </div>
            <div class="info-row-text">
                <p class="info-row-label">Unduh salinan data</p>
                <p class="info-row-value" style="font-size: 14px; font-weight: 500;">
                    Simpan data {{ $selectedMonthLabel }} sebagai file JSON sebelum menghapus atau berpindah perangkat.
                </p>
            </div>
        </div>

        <form action="/data/backup" method="POST" style="margin-top: 14px;">
            @csrf
            <input type="hidden" name="month" value="{{ $selectedMonth }}">
            <button type="submit" class="btn-secondary data-action-btn">
                @include('partials.icon', ['name' => 'database', 'class' => 'icon-sm'])
                Backup {{ $selectedMonthLabel }}
            </button>
        </form>

    </div>




    {{-- ===== HAPUS DATA (DANGER ZONE) ===== --}}

    <div class="section">

        <div class="section-header">
            <h2>Hapus Data</h2>
        </div>

        <div class="status-banner status-banner-danger">
            @include('partials.icon', ['name' => 'warning', 'class' => 'icon-sm'])
            Tindakan ini tidak dapat dibatalkan — sebaiknya backup dahulu.
        </div>

        <p class="muted-text" style="margin: 12px 0;">
            Menghapus data {{ $selectedMonthLabel }}: {{ $salesCount }} penjualan, {{ $restocksCount }} restock, dan {{ $expensesCount }} pengeluaran. Data produk tidak terhapus.
        </p>

        <button
            type="button"
            class="btn-delete-confirm data-action-btn"
            onclick="openDeleteDataModal()"
            {{ ($salesCount + $restocksCount + $expensesCount) === 0 ? 'disabled' : '' }}
        >
            @include('partials.icon', ['name' => 'delete', 'class' => 'icon-sm'])
            Hapus Data {{ $selectedMonthLabel }}
        </button>

    </div>

</div>


{{-- MODAL KONFIRMASI HAPUS DATA BULANAN --}}

<div id="deleteDataModal" class="edit-modal">
    <div class="delete-modal-content">

        <div class="delete-icon">
            @include('partials.icon', ['name' => 'warning'])
        </div>

        <h2>Hapus Data {{ $selectedMonthLabel }}?</h2>

        <p class="modal-description">
            Anda akan menghapus:
        </p>

        <div class="data-summary data-summary-modal">
            <div class="data-summary-item">
                <div><strong>{{ $salesCount }}</strong><span>Penjualan</span></div>
            </div>
            <div class="data-summary-item">
                <div><strong>{{ $restocksCount }}</strong><span>Restock</span></div>
            </div>
            <div class="data-summary-item">
                <div><strong>{{ $expensesCount }}</strong><span>Pengeluaran</span></div>
            </div>
        </div>

        <p class="delete-warning">
            Data produk (nama, harga, stok saat ini) TIDAK akan terhapus. Tindakan ini tidak dapat dibatalkan — sebaiknya backup terlebih dahulu.
        </p>

        <form action="/data" method="POST">

            @csrf
            @method('DELETE')

            <input type="hidden" name="month" value="{{ $selectedMonth }}">
            <input type="hidden" name="confirm" value="1">

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeDeleteDataModal()">Batal</button>
                <button type="submit" class="btn-delete-confirm">Ya, Hapus Data</button>
            </div>

        </form>

    </div>
</div>

<script>

    function openDeleteDataModal()
    {
        document.getElementById('deleteDataModal').classList.add('active');
    }

    function closeDeleteDataModal()
    {
        document.getElementById('deleteDataModal').classList.remove('active');
    }

    document.getElementById('deleteDataModal').addEventListener('click', function (event) {
        if (event.target === this) {
            closeDeleteDataModal();
        }
    });

</script>

@endsection
