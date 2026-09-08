@extends('layouts.app')

@section('title', 'Edit Produk')

@section('content')

<div class="container">

    <div class="page-header">
        <div class="page-header-main">
            <div class="page-header-icon icon-accent-teal">
                @include('partials.icon', ['name' => 'edit'])
            </div>
            <div>
                <h1>Edit Produk</h1>
                <p>Ubah informasi produk yang tersedia.</p>
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

        <form action="/products/{{ $product->id }}" method="POST">

            @csrf
            @method('PUT')


            <div class="form-group">

                <label for="name">
                    Nama Produk
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $product->name) }}"
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
                    value="{{ old('price', $product->price) }}"
                    placeholder="Contoh: 30000"
                >

            </div>


            <div class="form-group">

                <label for="stock">
                    Stok
                </label>

                <input
                    type="number"
                    id="stock"
                    name="stock"
                    value="{{ old('stock', $product->stock) }}"
                    placeholder="Contoh: 20"
                >

            </div>


            <div class="form-actions">

                <a href="/products" class="btn-secondary">
                    Kembali
                </a>

                <button type="submit" class="btn-primary">
                    Update Produk
                </button>

            </div>

        </form>

    </div>

</div>

@endsection
