@extends('layouts.app')

@section('title', 'Pengeluaran')

@section('content')

<div class="container fade-in">

    <div class="page-header">

        <div class="page-header-main">
            <div class="page-header-icon icon-accent-orange">
                @include('partials.icon', ['name' => 'expense'])
            </div>
            <div>
                <h1>Riwayat Pengeluaran</h1>
                <p>Pantau biaya operasional toko Anda.</p>
            </div>
        </div>

        <div class="page-header-actions">
            <a href="/expenses/create" class="btn-primary">
                @include('partials.icon', ['name' => 'expense', 'class' => 'icon-sm'])
                Catat Pengeluaran
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
                id="expenseSearch"
                placeholder="Cari keterangan, kategori, atau tanggal..."
                oninput="filterExpenses()"
                autocomplete="off"
            >
            <button type="button" id="clearExpenseSearch" class="search-clear" onclick="clearExpenseSearch()" style="display:none;">
                @include('partials.icon', ['name' => 'close'])
            </button>
        </div>

        <div class="filter-chips">
            <button type="button" class="chip active" data-range="all" onclick="setExpenseRange('all', this)">Semua</button>
            <button type="button" class="chip" data-range="today" onclick="setExpenseRange('today', this)">Hari ini</button>
            <button type="button" class="chip" data-range="week" onclick="setExpenseRange('week', this)">Minggu ini</button>
            <button type="button" class="chip" data-range="month" onclick="setExpenseRange('month', this)">Bulan ini</button>
        </div>

    </div>


    <div class="section">

        @if ($expenses->count() > 0)

            <div class="restock-table-wrapper">
                <table id="expenseTable">
                    <thead>
                        <tr>
                            <th>Keterangan</th>
                            <th>Kategori</th>
                            <th>Jumlah</th>
                            <th>Tanggal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($expenses as $expense)
                            @php $expenseDate = \Carbon\Carbon::parse($expense->expense_date)->format('Y-m-d'); @endphp
                            <tr
                                class="expense-row"
                                data-search="{{ strtolower($expense->name) }} {{ strtolower($expense->category) }} {{ \Carbon\Carbon::parse($expense->expense_date)->format('d-m-Y') }}"
                                data-date="{{ \Carbon\Carbon::parse($expense->expense_date)->format('Y-m-d') }}"
                            >
                                <td>
                                    <strong>{{ $expense->name }}</strong>
                                    @if ($expense->note)
                                        <br><span class="muted-text">{{ $expense->note }}</span>
                                    @endif
                                </td>
                                <td><span class="category-chip">{{ $expense->category }}</span></td>
                                <td>
                                    <span class="restock-quantity restock-quantity-expense">
                                        Rp {{ number_format($expense->amount, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="nowrap-date">{{ \Carbon\Carbon::parse($expense->expense_date)->format('d-m-Y') }}</td>
                                <td class="restock-action-cell">
                                    <div class="row-actions">
                                        <button
                                            type="button"
                                            class="restock-edit-btn"
                                            title="Edit pengeluaran"
                                            onclick="openExpenseEditModal(
                                                {{ $expense->id }},
                                                @js($expense->name),
                                                @js($expense->category),
                                                {{ $expense->amount }},
                                                @js($expenseDate),
                                                @js($expense->note ?? '')
                                            )"
                                        >
                                            @include('partials.icon', ['name' => 'edit'])
                                        </button>
                                        <button
                                            type="button"
                                            class="restock-delete-btn"
                                            title="Hapus pengeluaran"
                                            onclick="openExpenseDeleteModal(
                                                {{ $expense->id }},
                                                @js($expense->name),
                                                @js($expense->category),
                                                @js(\Carbon\Carbon::parse($expense->expense_date)->format('d-m-Y'))
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

            <div class="restock-cards" id="expenseCards">
                @foreach ($expenses as $expense)
                    @php $expenseDate = \Carbon\Carbon::parse($expense->expense_date)->format('Y-m-d'); @endphp
                    <div
                        class="restock-card-with-delete expense-row"
                        data-search="{{ strtolower($expense->name) }} {{ strtolower($expense->category) }} {{ \Carbon\Carbon::parse($expense->expense_date)->format('d-m-Y') }}"
                        data-date="{{ \Carbon\Carbon::parse($expense->expense_date)->format('Y-m-d') }}"
                    >
                        <div class="restock-card">
                            <div class="restock-card-icon restock-card-icon-orange">
                                @include('partials.icon', ['name' => 'expense', 'class' => 'icon-sm'])
                            </div>
                            <div class="restock-card-body">
                                <div>
                                    <p class="restock-card-name">{{ $expense->name }}</p>
                                    <p class="restock-card-date">
                                        <span class="category-chip">{{ $expense->category }}</span>
                                        &middot; <span class="nowrap-date">{{ \Carbon\Carbon::parse($expense->expense_date)->format('d-m-Y') }}</span>
                                    </p>
                                    @if ($expense->note)
                                        <p class="restock-card-date">{{ $expense->note }}</p>
                                    @endif
                                </div>
                                <span class="restock-quantity restock-quantity-expense">
                                    Rp {{ number_format($expense->amount, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                        <div class="row-actions">
                            <button
                                type="button"
                                class="restock-edit-btn"
                                title="Edit pengeluaran"
                                onclick="openExpenseEditModal(
                                    {{ $expense->id }},
                                    @js($expense->name),
                                    @js($expense->category),
                                    {{ $expense->amount }},
                                    @js($expenseDate),
                                    @js($expense->note ?? '')
                                )"
                            >
                                @include('partials.icon', ['name' => 'edit'])
                            </button>
                            <button
                                type="button"
                                class="restock-delete-btn"
                                title="Hapus pengeluaran"
                                onclick="openExpenseDeleteModal(
                                    {{ $expense->id }},
                                    @js($expense->name),
                                    @js($expense->category),
                                    @js(\Carbon\Carbon::parse($expense->expense_date)->format('d-m-Y'))
                                )"
                            >
                                @include('partials.icon', ['name' => 'delete'])
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="empty-state" id="expenseEmptySearch" style="display:none;">
                @include('partials.icon', ['name' => 'search', 'class' => 'icon-empty'])
                <h2>Tidak Ditemukan</h2>
                <p>Coba kata kunci atau filter lain.</p>
            </div>

        @else

            <div class="empty-state">
                @include('partials.icon', ['name' => 'expense', 'class' => 'icon-empty'])
                <h2>Belum Ada Pengeluaran</h2>
                <p>Catat pengeluaran pertama toko Anda.</p>
                <a href="/expenses/create" class="btn-primary">Catat Pengeluaran</a>
            </div>

        @endif

    </div>

</div>


{{-- MODAL EDIT PENGELUARAN --}}

<div id="expenseEditModal" class="edit-modal">
    <div class="edit-modal-content">

        <button type="button" class="modal-close" onclick="closeExpenseEditModal()">
            @include('partials.icon', ['name' => 'close'])
        </button>

        <h2>Edit Pengeluaran</h2>
        <p class="modal-description">Ubah keterangan, kategori, jumlah, tanggal, atau catatan.</p>

        <form id="expenseEditForm" method="POST">

            @csrf
            

            <div class="form-group">
                <label for="expense_edit_name">Nama / Keterangan</label>
                <input type="text" id="expense_edit_name" name="name" required>
            </div>

            <div class="form-group">
                <label for="expense_edit_category">Kategori</label>
                <div class="select-wrapper">
                    @include('partials.icon', ['name' => 'expense', 'class' => 'icon-sm select-wrapper-icon'])
                    <select id="expense_edit_category" name="category" required>
                        @foreach (['Pembelian Barang', 'Listrik', 'Transportasi', 'Operasional', 'Lainnya'] as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="expense_edit_amount">Jumlah Biaya</label>
                <input
                type="text"
                id="expense_edit_amount"
                name="amount"
                inputmode="numeric"
                autocomplete="off"
                required
            >
            </div>

            <div class="form-group">
                <label for="expense_edit_date">Tanggal</label>
                <input type="date" id="expense_edit_date" name="expense_date" required>
            </div>

            <div class="form-group">
                <label for="expense_edit_note">Catatan (opsional)</label>
                <input type="text" id="expense_edit_note" name="note">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeExpenseEditModal()">Batal</button>
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
            </div>

        </form>

    </div>
</div>


{{-- MODAL HAPUS PENGELUARAN --}}

<div id="expenseDeleteModal" class="edit-modal">
    <div class="delete-modal-content">

        <div class="delete-icon">
            @include('partials.icon', ['name' => 'warning'])
        </div>

        <h2>Hapus Pengeluaran Ini?</h2>

        <p class="modal-description">
            <strong id="expenseDeleteName"></strong> &middot; <span id="expenseDeleteCategory"></span> &middot; <span id="expenseDeleteDate"></span>
        </p>

        <p class="delete-warning">
            Tindakan ini tidak dapat dibatalkan. Stok produk tidak terpengaruh oleh penghapusan ini.
        </p>

        <form id="expenseDeleteForm" method="POST">
            @csrf
            @method('DELETE')

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeExpenseDeleteModal()">Batal</button>
                <button type="submit" class="btn-delete-confirm">Hapus</button>
            </div>
        </form>

    </div>
</div>


<script>

    let currentExpenseRange = 'all';

    function setExpenseRange(range, button)
    {
        currentExpenseRange = range;

        document.querySelectorAll('.filter-chips .chip').forEach(function (chip) {
            chip.classList.remove('active');
        });

        button.classList.add('active');

        filterExpenses();
    }


    function isExpenseInRange(dateStr, range)
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
            return (
                date.getMonth() === now.getMonth() &&
                date.getFullYear() === now.getFullYear()
            );
        }

        return true;
    }


    function filterExpenses()
    {
        const keyword = document
            .getElementById('expenseSearch')
            .value
            .trim()
            .toLowerCase();

        document.getElementById('clearExpenseSearch').style.display =
            keyword ? 'flex' : 'none';

        const rows = document.querySelectorAll('.expense-row');

        let visibleCount = 0;

        rows.forEach(function (row) {
            const matchesSearch =
                row.dataset.search.includes(keyword);

            const matchesRange =
                isExpenseInRange(
                    row.dataset.date,
                    currentExpenseRange
                );

            const visible =
                matchesSearch && matchesRange;

            row.style.display = visible ? '' : 'none';

            if (visible) {
                visibleCount++;
            }
        });

        const emptyState =
            document.getElementById('expenseEmptySearch');

        if (emptyState) {
            emptyState.style.display =
                visibleCount === 0 ? 'block' : 'none';
        }
    }


    function clearExpenseSearch()
    {
        document.getElementById('expenseSearch').value = '';

        filterExpenses();

        document.getElementById('expenseSearch').focus();
    }


    // ========== EDIT PENGELUARAN ==========

    function openExpenseEditModal(
        id,
        name,
        category,
        amount,
        expenseDate,
        note
    )
    {
        document.getElementById('expense_edit_name').value = name;

        document.getElementById('expense_edit_category').value =
            category;

        document.getElementById('expense_edit_amount').value =
            Number(amount).toLocaleString('id-ID');

        document.getElementById('expense_edit_date').value =
            expenseDate;

        document.getElementById('expense_edit_note').value =
            note;

        document.getElementById('expenseEditForm').action =
            '/expenses/' + id + '/update';

        document
            .getElementById('expenseEditModal')
            .classList.add('active');
    }


    function closeExpenseEditModal()
    {
        document
            .getElementById('expenseEditModal')
            .classList.remove('active');
    }


    // Format jumlah biaya saat mengetik
    const expenseEditAmount =
        document.getElementById('expense_edit_amount');

    if (expenseEditAmount) {
        expenseEditAmount.addEventListener('input', function () {
            let value = this.value.replace(/\D/g, '');

            this.value = value
                ? Number(value).toLocaleString('id-ID')
                : '';
        });
    }


    // ========== HAPUS PENGELUARAN ==========

    function openExpenseDeleteModal(
        id,
        name,
        category,
        expenseDate
    )
    {
        document.getElementById('expenseDeleteName').textContent =
            name;

        document.getElementById('expenseDeleteCategory').textContent =
            category;

        document.getElementById('expenseDeleteDate').textContent =
            expenseDate;

        document.getElementById('expenseDeleteForm').action =
            '/expenses/' + id;

        document
            .getElementById('expenseDeleteModal')
            .classList.add('active');
    }


    function closeExpenseDeleteModal()
    {
        document
            .getElementById('expenseDeleteModal')
            .classList.remove('active');
    }


    // ========== KLIK DI LUAR MODAL ==========

    ['expenseEditModal', 'expenseDeleteModal'].forEach(function (id) {

        const modal = document.getElementById(id);

        if (modal) {
            modal.addEventListener('click', function (event) {

                if (event.target === this) {
                    this.classList.remove('active');
                }

            });
        }

    });

</script>

@endsection
