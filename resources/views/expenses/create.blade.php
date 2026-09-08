@extends('layouts.app')

@section('title', 'Catat Pengeluaran')

@section('content')

<div class="container fade-in">

    <div class="page-header">
        <div class="page-header-main">
            <div class="page-header-icon icon-accent-orange">
                @include('partials.icon', ['name' => 'expense'])
            </div>
            <div>
                <h1>Catat Pengeluaran</h1>
                <p>Simpan catatan biaya operasional toko.</p>
            </div>
        </div>
    </div>

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

    <div class="form-card form-card-featured">

        <div class="form-card-icon icon-accent-orange">
            @include('partials.icon', ['name' => 'expense'])
        </div>

        <h2 class="form-card-title">Pengeluaran Baru</h2>
        <p class="form-card-subtitle">Catat biaya operasional toko Anda.</p>

        <form action="/expenses" method="POST">

            @csrf

            <div class="form-group">
                <label for="name">Nama / Keterangan</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Contoh: Beli plastik kemasan" required>
            </div>

            <div class="form-group">
                <label for="category">Kategori</label>
                <div class="select-wrapper">
                    @include('partials.icon', ['name' => 'expense', 'class' => 'icon-sm select-wrapper-icon'])
                    <select id="category" name="category" required>
                        <option value="">-- Pilih Kategori --</option>
                        @foreach (['Pembelian Barang', 'Listrik', 'Transportasi', 'Operasional', 'Lainnya'] as $category)
                            <option value="{{ $category }}" {{ old('category') == $category ? 'selected' : '' }}>
                                {{ $category }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="amount">Jumlah Biaya</label>
                <input
                type="text"
                id="amount"
                name="amount"
                inputmode="numeric"
                autocomplete="off"
                value="{{ old('amount') }}"
                placeholder="Contoh: 50.000"
                required
            >
            </div>

            <div class="form-group">
                <label for="expense_date">Tanggal</label>
                <input type="date" id="expense_date" name="expense_date" value="{{ old('expense_date', date('Y-m-d')) }}" required>
            </div>

            <div class="form-group">
                <label for="note">Catatan (opsional)</label>
                <input type="text" id="note" name="note" value="{{ old('note') }}" placeholder="Catatan tambahan">
            </div>

            <div class="form-actions">
                <a href="/expenses" class="btn-secondary">Kembali</a>
                <button type="submit" class="btn-primary">
                    @include('partials.icon', ['name' => 'expense', 'class' => 'icon-sm'])
                    Simpan Pengeluaran
                </button>
            </div>

        </form>

    </div>

</div>

<script>
    const amountInput = document.getElementById('amount');

    if (amountInput) {
        amountInput.addEventListener('input', function () {
            let value = this.value.replace(/\D/g, '');

            if (value) {
                this.value = Number(value).toLocaleString('id-ID');
            } else {
                this.value = '';
            }
        });
    }
</script>

@endsection
