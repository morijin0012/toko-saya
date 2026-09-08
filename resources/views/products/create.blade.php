@extends('layouts.app')

@section('title', 'Tambah Produk')

@section('content')

<div class="container">

    <div class="page-header">
        <div class="page-header-main">
            <div class="page-header-icon icon-accent-teal">
                @include('partials.icon', ['name' => 'product'])
            </div>
            <div>
                <h1>Tambah Produk</h1>
                <p>Tambahkan produk baru ke dalam stok.</p>
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


    <div class="form-card">

        <form action="/products" method="POST">

            @csrf

            <div class="form-group">

                <label for="name">
                    Nama Produk
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="Contoh: Kopi Bubuk 200gr"
                >

            </div>


            <div class="form-group">

                <label for="price">
                    Harga
                </label>

                <input
                    type="number"
                    id="price"
                    name="price"
                    value="{{ old('price') }}"
                    placeholder="Contoh: 30000"
                >

            </div>


            <div class="form-group">

                <label for="stock">
                    Stok Awal
                </label>

                <input
                    type="number"
                    id="stock"
                    name="stock"
                    value="{{ old('stock') }}"
                    placeholder="Contoh: 20"
                >

            </div>


            <div class="form-actions">

                <a href="/products" class="btn-secondary">
                    Kembali
                </a>

                <button type="submit" class="btn-primary">
                    Simpan Produk
                </button>

            </div>

        </form>

    </div>

</div>

@endsection
