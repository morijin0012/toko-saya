@extends('layouts.app')

@section('title', 'Penjualan')

@section('content')

<div class="container fade-in">

    <div class="page-header">

        <div class="page-header-main">
            <div class="page-header-icon icon-accent-blue">
                @include('partials.icon', ['name' => 'sale'])
            </div>
            <div>
                <h1>Riwayat Penjualan</h1>
                <p>Pantau seluruh transaksi penjualan produk toko Anda.</p>
            </div>
        </div>

        <div class="page-header-actions">
            <a href="/sales/create" class="btn-primary">
                @include('partials.icon', ['name' => 'sale', 'class' => 'icon-sm'])
                Catat Penjualan
            </a>
        </div>

    </div>

    @if (session('success'))
        <div class="success-box">{{ session('success') }}</div>
    @endif


    {{-- ===== SEARCH & FILTER ===== --}}

    <div class="toolbar">

        <div class="search-box">
            @include('partials.icon', ['name' => 'search', 'class' => 'icon-sm'])
            <input
                type="text"
                id="saleSearch"
                placeholder="Cari nama produk atau tanggal..."
                oninput="filterSales()"
                autocomplete="off"
            >
            <button type="button" id="clearSaleSearch" class="search-clear" onclick="clearSaleSearch()" style="display:none;">
                @include('partials.icon', ['name' => 'close'])
            </button>
        </div>

        <div class="filter-chips">
            <button type="button" class="chip active" data-range="all" onclick="setSaleRange('all', this)">Semua</button>
            <button type="button" class="chip" data-range="today" onclick="setSaleRange('today', this)">Hari ini</button>
            <button type="button" class="chip" data-range="week" onclick="setSaleRange('week', this)">Minggu ini</button>
            <button type="button" class="chip" data-range="month" onclick="setSaleRange('month', this)">Bulan ini</button>
        </div>

    </div>


    <div class="section">

        @if ($sales->count() > 0)

            <div class="restock-table-wrapper">

                <table id="saleTable">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Jumlah</th>
                            <th>Harga Satuan</th>
                            <th>Total</th>
                            <th>Tanggal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sales as $sale)
                            <tr
                                class="sale-row"
                                data-search="{{ strtolower($sale->product->name ?? '') }} {{ \Carbon\Carbon::parse($sale->sold_at)->format('d-m-Y') }}"
                                data-date="{{ \Carbon\Carbon::parse($sale->sold_at)->format('Y-m-d') }}"
                            >
                                <td><strong>{{ $sale->product->name ?? '—' }}</strong></td>
                                <td>{{ $sale->quantity }}</td>
                                <td>Rp {{ number_format($sale->price, 0, ',', '.') }}</td>
                                <td>
                                    <span class="restock-quantity restock-quantity-money">
                                        Rp {{ number_format($sale->total, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="nowrap-date">{{ \Carbon\Carbon::parse($sale->sold_at)->format('d-m-Y') }}</td>
                                <td class="restock-action-cell">
                                    <div class="row-actions">
                                        <button
                                            type="button"
                                            class="restock-edit-btn"
                                            title="Edit penjualan"
                                            onclick="openSaleEditModal(
                                                {{ $sale->id }},
                                                {{ $sale->product_id ?? 'null' }},
                                                {{ $sale->quantity }},
                                                {{ $sale->price }},
                                                @js(\Carbon\Carbon::parse($sale->sold_at)->format('Y-m-d'))
                                            )"
                                        >
                                            @include('partials.icon', ['name' => 'edit'])
                                        </button>
                                        <button
                                            type="button"
                                            class="restock-delete-btn"
                                            title="Hapus penjualan"
                                            onclick="openSaleDeleteModal(
                                                {{ $sale->id }},
                                                @js($sale->product->name ?? '—'),
                                                {{ $sale->quantity }},
                                                @js(\Carbon\Carbon::parse($sale->sold_at)->format('d-m-Y'))
                                            )"
                                        >
                                            @include('partials.icon', ['name' => 'delete'])
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>

            <div class="restock-cards" id="saleCards">
                @foreach ($sales as $sale)
                    <div
                        class="restock-card-with-delete sale-row"
                        data-search="{{ strtolower($sale->product->name ?? '') }} {{ \Carbon\Carbon::parse($sale->sold_at)->format('d-m-Y') }}"
                        data-date="{{ \Carbon\Carbon::parse($sale->sold_at)->format('Y-m-d') }}"
                    >
                        <div class="restock-card">
                            <div class="restock-card-icon restock-card-icon-blue">
                                @include('partials.icon', ['name' => 'sale', 'class' => 'icon-sm'])
                            </div>
                            <div class="restock-card-body">
                                <div>
                                    <p class="restock-card-name">{{ $sale->product->name ?? '—' }}</p>
                                    <p class="restock-card-date">
                                        {{ $sale->quantity }} pcs &middot; Rp {{ number_format($sale->price, 0, ',', '.') }} &middot;
                                        <span class="nowrap-date">{{ \Carbon\Carbon::parse($sale->sold_at)->format('d-m-Y') }}</span>
                                    </p>
                                </div>
                                <span class="restock-quantity restock-quantity-money">
                                    Rp {{ number_format($sale->total, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                        <div class="row-actions">
                            <button
                                type="button"
                                class="restock-edit-btn"
                                title="Edit penjualan"
                                onclick="openSaleEditModal(
                                    {{ $sale->id }},
                                    {{ $sale->product_id ?? 'null' }},
                                    {{ $sale->quantity }},
                                    {{ $sale->price }},
                                    @js(\Carbon\Carbon::parse($sale->sold_at)->format('Y-m-d'))
                                )"
                            >
                                @include('partials.icon', ['name' => 'edit'])
                            </button>
                            <button
                                type="button"
                                class="restock-delete-btn"
                                title="Hapus penjualan"
                                onclick="openSaleDeleteModal(
                                    {{ $sale->id }},
                                    @js($sale->product->name ?? '—'),
                                    {{ $sale->quantity }},
                                    @js(\Carbon\Carbon::parse($sale->sold_at)->format('d-m-Y'))
                                )"
                            >
                                @include('partials.icon', ['name' => 'delete'])
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="empty-state" id="saleEmptySearch" style="display:none;">
                @include('partials.icon', ['name' => 'search', 'class' => 'icon-empty'])
                <h2>Tidak Ditemukan</h2>
                <p>Coba kata kunci atau filter lain.</p>
            </div>

        @else

            <div class="empty-state">
                @include('partials.icon', ['name' => 'sale', 'class' => 'icon-empty'])
                <h2>Belum Ada Penjualan</h2>
                <p>Catat penjualan pertama Anda.</p>
                <a href="/sales/create" class="btn-primary">Catat Penjualan</a>
            </div>

        @endif

    </div>

</div>


{{-- MODAL EDIT PENJUALAN --}}

<div id="saleEditModal" class="edit-modal">
    <div class="edit-modal-content">

        <button type="button" class="modal-close" onclick="closeSaleEditModal()">
            @include('partials.icon', ['name' => 'close'])
        </button>

        <h2>Edit Penjualan</h2>
        <p class="modal-description">Ubah produk, jumlah, harga, atau tanggal penjualan. Stok akan disesuaikan otomatis.</p>

        <form id="saleEditForm" method="POST">

            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="sale_edit_product_id">Produk</label>
                <div class="select-wrapper">
                    @include('partials.icon', ['name' => 'product', 'class' => 'icon-sm select-wrapper-icon'])
                    <select id="sale_edit_product_id" name="product_id" required>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="sale_edit_quantity">Jumlah Terjual</label>
                <input type="number" id="sale_edit_quantity" name="quantity" min="1" required>
            </div>

            <div class="form-group">
                <label for="sale_edit_price">Harga Jual (per satuan)</label>
                <input type="number" id="sale_edit_price" name="price" min="0" required>
            </div>

            <div class="form-group">
                <label for="sale_edit_sold_at">Tanggal Penjualan</label>
                <input type="date" id="sale_edit_sold_at" name="sold_at" required>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeSaleEditModal()">Batal</button>
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
            </div>

        </form>

    </div>
</div>


{{-- MODAL HAPUS PENJUALAN --}}

<div id="saleDeleteModal" class="edit-modal">
    <div class="delete-modal-content">

        <div class="delete-icon">
            @include('partials.icon', ['name' => 'warning'])
        </div>

        <h2>Hapus Transaksi Ini?</h2>

        <p class="modal-description">
            <strong id="saleDeleteName"></strong> &middot; <span id="saleDeleteQty"></span> &middot; <span id="saleDeleteDate"></span>
        </p>

        <p class="delete-warning">
            Stok produk akan dikembalikan sebesar jumlah pada transaksi ini. Tindakan ini tidak dapat dibatalkan.
        </p>

        <form id="saleDeleteForm" method="POST">
            @csrf
            @method('DELETE')

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeSaleDeleteModal()">Batal</button>
                <button type="submit" class="btn-delete-confirm">Hapus</button>
            </div>
        </form>

    </div>
</div>


<script>

    let currentSaleRange = 'all';

    function setSaleRange(range, button)
    {
        currentSaleRange = range;

        document.querySelectorAll('.filter-chips .chip').forEach(function (chip) {
            chip.classList.remove('active');
        });

        button.classList.add('active');

        filterSales();
    }

    function isSaleInRange(dateStr, range)
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

    function filterSales()
    {
        const keyword = document.getElementById('saleSearch').value.trim().toLowerCase();

        document.getElementById('clearSaleSearch').style.display = keyword ? 'flex' : 'none';

        const rows = document.querySelectorAll('.sale-row');

        let visibleCount = 0;

        rows.forEach(function (row) {
            const matchesSearch = row.dataset.search.includes(keyword);
            const matchesRange = isSaleInRange(row.dataset.date, currentSaleRange);
            const visible = matchesSearch && matchesRange;

            row.style.display = visible ? '' : 'none';
            if (visible) visibleCount++;
        });

        const emptyState = document.getElementById('saleEmptySearch');
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    function clearSaleSearch()
    {
        document.getElementById('saleSearch').value = '';
        filterSales();
        document.getElementById('saleSearch').focus();
    }


    // ========== EDIT PENJUALAN ==========

    function openSaleEditModal(id, productId, quantity, price, soldAt)
    {
        document.getElementById('sale_edit_product_id').value = productId;
        document.getElementById('sale_edit_quantity').value = quantity;
        document.getElementById('sale_edit_price').value = price;
        document.getElementById('sale_edit_sold_at').value = soldAt;
        document.getElementById('saleEditForm').action = '/sales/' + id;
        document.getElementById('saleEditModal').classList.add('active');
    }

    function closeSaleEditModal()
    {
        document.getElementById('saleEditModal').classList.remove('active');
    }


    // ========== HAPUS PENJUALAN ==========

    function openSaleDeleteModal(id, productName, quantity, soldAt)
    {
        document.getElementById('saleDeleteName').textContent = productName;
        document.getElementById('saleDeleteQty').textContent = quantity + ' pcs';
        document.getElementById('saleDeleteDate').textContent = soldAt;
        document.getElementById('saleDeleteForm').action = '/sales/' + id;
        document.getElementById('saleDeleteModal').classList.add('active');
    }

    function closeSaleDeleteModal()
    {
        document.getElementById('saleDeleteModal').classList.remove('active');
    }


    // ========== KLIK DI LUAR MODAL ==========

    ['saleEditModal', 'saleDeleteModal'].forEach(function (id) {
        document.getElementById(id).addEventListener('click', function (event) {
            if (event.target === this) {
                this.classList.remove('active');
            }
        });
    });

</script>

@endsection
