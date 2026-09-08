@extends('layouts.app')

@section('title', 'Riwayat Restock')

@section('content')

<div class="container fade-in">

    <div class="page-header">

        <div class="page-header-main">
            <div class="page-header-icon icon-accent-green">
                @include('partials.icon', ['name' => 'history'])
            </div>
            <div>
                <h1>Riwayat Restock</h1>
                <p>Kelola dan pantau seluruh aktivitas restock toko.</p>
            </div>
        </div>

        <div class="page-header-actions">
            <a href="/restocks/create" class="btn-primary">
                + Restock Baru
            </a>
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


    {{-- ===== SEARCH & FILTER ===== --}}

    <div class="toolbar">

        <div class="search-box">
            @include('partials.icon', ['name' => 'search', 'class' => 'icon-sm'])
            <input
                type="text"
                id="restockSearch"
                placeholder="Cari nama produk, jumlah, atau tanggal..."
                oninput="filterRestocks()"
                autocomplete="off"
            >
            <button type="button" id="clearRestockSearch" class="search-clear" onclick="clearRestockSearch()" style="display:none;">
                @include('partials.icon', ['name' => 'close'])
            </button>
        </div>

        <div class="filter-chips">
            <button type="button" class="chip active" data-range="all" onclick="setRestockRange('all', this)">Semua</button>
            <button type="button" class="chip" data-range="today" onclick="setRestockRange('today', this)">Hari ini</button>
            <button type="button" class="chip" data-range="week" onclick="setRestockRange('week', this)">Minggu ini</button>
            <button type="button" class="chip" data-range="month" onclick="setRestockRange('month', this)">Bulan ini</button>
        </div>

    </div>


    {{-- ===== HAPUS BERDASARKAN BULAN ===== --}}

    <div class="bulk-delete-bar">
        <span class="bulk-delete-bar-text">
            @include('partials.icon', ['name' => 'delete', 'class' => 'icon-sm'])
            Ingin membersihkan riwayat lama supaya database tidak terus membesar?
        </span>
        <button type="button" class="btn-secondary" onclick="openBulkDeleteModal()">
            Hapus Berdasarkan Bulan
        </button>
    </div>


    <div class="section">

        @if ($restocks->count() > 0)

            <div class="restock-table-wrapper">

                <table id="restockTable">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Jumlah</th>
                            <th>Tanggal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($restocks as $restock)
                            <tr
                                class="restock-row"
                                data-search="{{ strtolower($restock->product->name) }} {{ $restock->quantity }} {{ $restock->created_at->format('d-m-Y') }}"
                                data-date="{{ $restock->created_at->format('Y-m-d') }}"
                            >
                                <td><strong>{{ $restock->product->name }}</strong></td>
                                <td><span class="restock-quantity">+{{ $restock->quantity }}</span></td>
                                <td>{{ $restock->created_at->format('d-m-Y H:i') }}</td>
                                <td class="restock-action-cell">
                                    <button
                                        type="button"
                                        class="restock-delete-btn"
                                        title="Hapus riwayat ini"
                                        onclick="openSingleDeleteModal(
                                            {{ $restock->id }},
                                            @js($restock->product->name),
                                            {{ $restock->quantity }},
                                            @js($restock->created_at->format('d-m-Y H:i'))
                                        )"
                                    >
                                        @include('partials.icon', ['name' => 'delete'])
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>

            <div class="restock-cards" id="restockCards">
                @foreach ($restocks as $restock)
                    <div
                        class="restock-card-with-delete restock-row"
                        data-search="{{ strtolower($restock->product->name) }} {{ $restock->quantity }} {{ $restock->created_at->format('d-m-Y') }}"
                        data-date="{{ $restock->created_at->format('Y-m-d') }}"
                    >
                        <div class="restock-card">
                            <div class="restock-card-icon">
                                @include('partials.icon', ['name' => 'restock', 'class' => 'icon-sm'])
                            </div>
                            <div class="restock-card-body">
                                <div>
                                    <p class="restock-card-name">{{ $restock->product->name }}</p>
                                    <p class="restock-card-date">{{ $restock->created_at->format('d-m-Y H:i') }}</p>
                                </div>
                                <span class="restock-quantity">+{{ $restock->quantity }}</span>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="restock-delete-btn"
                            title="Hapus riwayat ini"
                            onclick="openSingleDeleteModal(
                                {{ $restock->id }},
                                @js($restock->product->name),
                                {{ $restock->quantity }},
                                @js($restock->created_at->format('d-m-Y H:i'))
                            )"
                        >
                            @include('partials.icon', ['name' => 'delete'])
                        </button>
                    </div>
                @endforeach
            </div>

            <div class="empty-state" id="restockEmptySearch" style="display:none;">
                @include('partials.icon', ['name' => 'search', 'class' => 'icon-empty'])
                <h2>Tidak Ditemukan</h2>
                <p>Coba kata kunci atau filter lain.</p>
            </div>

        @else

            <div class="empty-state">
                @include('partials.icon', ['name' => 'history', 'class' => 'icon-empty'])
                <h2>Belum Ada Riwayat</h2>
                <p>Belum ada aktivitas restock produk.</p>
                <a href="/restocks/create" class="btn-primary">+ Restock Produk</a>
            </div>

        @endif

    </div>

</div>


{{-- MODAL HAPUS SATU RIWAYAT --}}

<div id="singleDeleteModal" class="edit-modal">
    <div class="delete-modal-content">

        <div class="delete-icon">
            @include('partials.icon', ['name' => 'warning'])
        </div>

        <h2>Hapus Riwayat Restock Ini?</h2>

        <p class="modal-description">
            <strong id="singleDeleteName"></strong> &middot; <span id="singleDeleteQty"></span> &middot; <span id="singleDeleteDate"></span>
        </p>

        <p class="delete-warning">
            Ini hanya menghapus catatan riwayat. Produk dan stok saat ini tidak akan berubah.
        </p>

        <form id="singleDeleteForm" method="POST">
            @csrf
            @method('DELETE')

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeSingleDeleteModal()">Batal</button>
                <button type="submit" class="btn-delete-confirm">Hapus</button>
            </div>
        </form>

    </div>
</div>


{{-- MODAL HAPUS BERDASARKAN BULAN --}}

<div id="bulkDeleteModal" class="edit-modal">
    <div class="delete-modal-content">

        <div class="delete-icon">
            @include('partials.icon', ['name' => 'warning'])
        </div>

        <h2>Hapus Riwayat Berdasarkan Bulan</h2>

        <p class="modal-description">
            Pilih bulan yang riwayatnya ingin dihapus. Hanya riwayat restock pada bulan tersebut yang akan dihapus — produk dan stok saat ini tidak terpengaruh.
        </p>

        <form id="bulkDeleteForm" action="/restocks" method="POST">

            @csrf
            @method('DELETE')

            <div class="form-group">
                <label for="bulk_delete_month">Bulan</label>
                <input type="month" id="bulk_delete_month" name="month" value="{{ date('Y-m') }}" required>
            </div>

            <div class="confirm-checkbox-row">
                <input type="checkbox" id="bulk_delete_confirm_checkbox" required>
                <label for="bulk_delete_confirm_checkbox">
                    Saya sudah backup data (via halaman <a href="/data" target="_blank">Kelola Data</a>) atau memang tidak memerlukannya, dan ingin melanjutkan hapus.
                </label>
            </div>

            <input type="hidden" name="confirm" value="1">

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeBulkDeleteModal()">Batal</button>
                <button type="submit" class="btn-delete-confirm">Hapus Riwayat</button>
            </div>

        </form>

    </div>
</div>


<script>

    let currentRestockRange = 'all';

    function setRestockRange(range, button)
    {
        currentRestockRange = range;

        document.querySelectorAll('.filter-chips .chip').forEach(function (chip) {
            chip.classList.remove('active');
        });

        button.classList.add('active');

        filterRestocks();
    }

    function isInRange(dateStr, range)
    {
        if (range === 'all') return true;

        const date = new Date(dateStr);
        const now = new Date();

        if (range === 'today') {
            return date.toDateString() === now.toDateString();
        }

        if (range === 'week') {
            const startOfWeek = new Date(now);
            startOfWeek.setDate(now.getDate() - now.getDay());
            startOfWeek.setHours(0, 0, 0, 0);
            return date >= startOfWeek;
        }

        if (range === 'month') {
            return date.getMonth() === now.getMonth() && date.getFullYear() === now.getFullYear();
        }

        return true;
    }

    function filterRestocks()
    {
        const keyword = document.getElementById('restockSearch').value.trim().toLowerCase();

        document.getElementById('clearRestockSearch').style.display = keyword ? 'flex' : 'none';

        const rows = document.querySelectorAll('.restock-row');

        let visibleCount = 0;

        rows.forEach(function (row) {
            const matchesSearch = row.dataset.search.includes(keyword);
            const matchesRange = isInRange(row.dataset.date, currentRestockRange);
            const visible = matchesSearch && matchesRange;

            row.style.display = visible ? '' : 'none';
            if (visible) visibleCount++;
        });

        const emptyState = document.getElementById('restockEmptySearch');
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    function clearRestockSearch()
    {
        document.getElementById('restockSearch').value = '';
        filterRestocks();
        document.getElementById('restockSearch').focus();
    }


    // ========== HAPUS SATU RIWAYAT ==========

    function openSingleDeleteModal(id, name, qty, date)
    {
        document.getElementById('singleDeleteName').textContent = name;
        document.getElementById('singleDeleteQty').textContent = '+' + qty + ' pcs';
        document.getElementById('singleDeleteDate').textContent = date;
        document.getElementById('singleDeleteForm').action = '/restocks/' + id;
        document.getElementById('singleDeleteModal').classList.add('active');
    }

    function closeSingleDeleteModal()
    {
        document.getElementById('singleDeleteModal').classList.remove('active');
    }


    // ========== HAPUS BERDASARKAN BULAN ==========

    function openBulkDeleteModal()
    {
        document.getElementById('bulkDeleteModal').classList.add('active');
    }

    function closeBulkDeleteModal()
    {
        document.getElementById('bulkDeleteModal').classList.remove('active');
    }


    // ========== KLIK DI LUAR MODAL ==========

    ['singleDeleteModal', 'bulkDeleteModal'].forEach(function (id) {
        document.getElementById(id).addEventListener('click', function (event) {
            if (event.target === this) {
                this.classList.remove('active');
            }
        });
    });

</script>

@endsection
