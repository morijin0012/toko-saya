@extends('layouts.app')

@section('title', 'Restock Produk')

@section('content')

<div class="container fade-in">

    <div class="page-header">
        <div class="page-header-main">
            <div class="page-header-icon icon-accent-green">
                @include('partials.icon', ['name' => 'restock'])
            </div>

            <div>
                <h1>Restock Produk</h1>
                <p>Tambah stok untuk produk yang sudah tersedia.</p>
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

        <div class="form-card-icon icon-accent-green">
            @include('partials.icon', ['name' => 'restock'])
        </div>

        <h2 class="form-card-title">Tambah Stok</h2>

        <p class="form-card-subtitle">
            Pilih produk dan masukkan jumlah stok yang masuk.
        </p>

        <form
            action="/restocks"
            method="POST"
            id="restockCreateForm"
        >

            @csrf

            {{-- PRODUK --}}
            <div class="form-group">
                <label for="productPickerButton">Produk</label>

                <input
                    type="hidden"
                    id="product_id"
                    name="product_id"
                    value="{{ old('product_id') }}"
                >

                <button
                    type="button"
                    id="productPickerButton"
                    class="product-picker-button"
                    onclick="openRestockProductPicker()"
                >
                    <span class="product-picker-icon">
                        @include('partials.icon', [
                            'name' => 'product',
                            'class' => 'icon-sm'
                        ])
                    </span>

                    <span
                        class="product-picker-text"
                        id="selectedProductName"
                    >
                        -- Pilih Produk --
                    </span>

                    <span class="product-picker-arrow">⌄</span>
                </button>

                <p
                    class="product-picker-stock"
                    id="selectedProductStock"
                    style="display:none;"
                ></p>
            </div>

            {{-- JUMLAH RESTOCK --}}
            <div class="form-group">
                <label for="quantity">Jumlah Restock</label>

                <input
                    type="number"
                    id="quantity"
                    name="quantity"
                    min="1"
                    value="{{ old('quantity') }}"
                    placeholder="Contoh: 10"
                    oninput="updateRestockCreatePreview()"
                    required
                >
            </div>

            {{-- PREVIEW --}}
            <div
                class="preview-box preview-box-featured"
                id="restockCreatePreview"
                style="display:none;"
            >
                <div class="preview-row">
                    <span>Stok saat ini</span>
                    <strong id="createPreviewBefore">-</strong>
                </div>

                <div class="preview-row">
                    <span>Restock</span>
                    <strong
                        class="preview-plus"
                        id="createPreviewAdd"
                    >-</strong>
                </div>

                <div class="preview-row preview-row-highlight">
                    <span>Stok setelah restock</span>
                    <strong id="createPreviewAfter">-</strong>
                </div>
            </div>

            <div class="form-actions">
                <a href="/products" class="btn-secondary">
                    Kembali
                </a>

                <button
                    type="submit"
                    class="btn-primary"
                >
                    @include('partials.icon', [
                        'name' => 'restock',
                        'class' => 'icon-sm'
                    ])

                    Simpan Restock
                </button>
            </div>

        </form>

    </div>

</div>


{{-- =========================================================
     PRODUCT PICKER
========================================================= --}}

<div
    id="restockProductPicker"
    class="product-picker-modal"
    aria-hidden="true"
>
    <div class="product-picker-sheet">

        <div class="product-picker-header">

            <div>
                <h2>Pilih Produk</h2>

                <p>
                    Cari produk yang ingin direstock.
                </p>
            </div>

            <button
                type="button"
                class="modal-close"
                onclick="closeRestockProductPicker()"
                aria-label="Tutup"
            >
                @include('partials.icon', ['name' => 'close'])
            </button>

        </div>

        <div class="product-picker-search">

            @include('partials.icon', [
                'name' => 'search',
                'class' => 'icon-sm'
            ])

            <input
                type="text"
                id="restockProductSearch"
                placeholder="Cari nama produk..."
                autocomplete="off"
                oninput="filterRestockProducts()"
            >

            <button
                type="button"
                id="clearRestockProductSearch"
                class="search-clear"
                onclick="clearRestockProductSearch()"
                style="display:none;"
                aria-label="Bersihkan pencarian"
            >
                @include('partials.icon', ['name' => 'close'])
            </button>

        </div>

        <div
            id="restockProductList"
            class="product-picker-list"
        >

            @foreach ($products as $product)

                <button
                    type="button"
                    class="product-picker-item"
                    data-id="{{ $product->id }}"
                    data-name="{{ strtolower($product->name) }}"
                    data-label="{{ $product->name }}"
                    data-stock="{{ $product->stock }}"
                    onclick="selectRestockProduct(this)"
                >

                    <span class="product-picker-item-icon">
                        @include('partials.icon', [
                            'name' => 'product',
                            'class' => 'icon-sm'
                        ])
                    </span>

                    <span class="product-picker-item-content">

                        <strong>
                            {{ $product->name }}
                        </strong>

                        <small>
                            Stok saat ini: {{ $product->stock }}
                        </small>

                    </span>

                    <span class="product-picker-item-arrow">
                        ›
                    </span>

                </button>

            @endforeach

        </div>

        <div
            id="restockProductEmpty"
            class="product-picker-empty"
            style="display:none;"
        >
            @include('partials.icon', [
                'name' => 'search',
                'class' => 'icon-empty'
            ])

            <strong>Produk tidak ditemukan</strong>

            <span>
                Coba gunakan kata kunci lain.
            </span>
        </div>

    </div>
</div>


<style>
    .product-picker-button {
        width: 100%;
        min-height: 56px;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        border: 1px solid var(--border-color, #dfe3e8);
        border-radius: 14px;
        background: var(--surface, #fff);
        color: var(--text-primary, #1f2937);
        font: inherit;
        text-align: left;
        cursor: pointer;
        transition:
            border-color .18s ease,
            box-shadow .18s ease,
            background-color .18s ease;
    }

    .product-picker-button:hover {
        border-color: rgba(5, 150, 105, .45);
    }

    .product-picker-button:focus-visible {
        outline: 3px solid rgba(5, 150, 105, .14);
        outline-offset: 2px;
    }

    .product-picker-icon {
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: rgba(5, 150, 105, .10);
        color: #059669;
    }

    .product-picker-text {
        flex: 1;
        min-width: 0;
        font-weight: 600;
    }

    .product-picker-arrow {
        font-size: 22px;
        line-height: 1;
        color: #9ca3af;
    }

    .product-picker-stock {
        margin: 8px 2px 0;
        font-size: 13px;
        color: #6b7280;
    }

    .product-picker-modal {
        position: fixed;
        inset: 0;
        z-index: 1000;
        display: none;
        align-items: flex-end;
        justify-content: center;
        background: rgba(15, 23, 42, .48);
        backdrop-filter: blur(3px);
    }

    .product-picker-modal.active {
        display: flex;
    }

    .product-picker-sheet {
        width: 100%;
        max-width: 640px;
        max-height: 88vh;
        padding: 20px;
        overflow: hidden;
        border-radius: 22px 22px 0 0;
        background: #fff;
        box-shadow: 0 -10px 40px rgba(0, 0, 0, .18);
    }

    .product-picker-header {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 16px;
    }

    .product-picker-header > div {
        flex: 1;
    }

    .product-picker-header h2 {
        margin: 0 0 4px;
        font-size: 20px;
    }

    .product-picker-header p {
        margin: 0;
        color: #6b7280;
        font-size: 13px;
    }

    .product-picker-search {
        position: relative;
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 48px;
        padding: 0 12px;
        margin-bottom: 12px;
        border: 1px solid #dfe3e8;
        border-radius: 14px;
        background: #f8fafc;
    }

    .product-picker-search svg {
        color: #94a3b8;
        flex: 0 0 auto;
    }

    .product-picker-search input {
        flex: 1;
        min-width: 0;
        border: 0;
        outline: 0;
        background: transparent;
        font: inherit;
    }

    .product-picker-list {
        max-height: calc(88vh - 170px);
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding-bottom: 8px;
    }

    .product-picker-item {
        width: 100%;
        min-height: 68px;
        padding: 10px 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #fff;
        text-align: left;
        font: inherit;
        cursor: pointer;
        transition:
            background-color .16s ease,
            border-color .16s ease,
            transform .12s ease;
    }

    .product-picker-item:hover {
        background: #f8fafc;
        border-color: rgba(5, 150, 105, .35);
    }

    .product-picker-item:active {
        transform: scale(.99);
    }

    .product-picker-item-icon {
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 11px;
        background: rgba(5, 150, 105, .10);
        color: #059669;
    }

    .product-picker-item-content {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .product-picker-item-content strong {
        color: #111827;
        font-size: 15px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .product-picker-item-content small {
        color: #6b7280;
        font-size: 12px;
    }

    .product-picker-item-arrow {
        color: #9ca3af;
        font-size: 22px;
    }

    .product-picker-empty {
        min-height: 180px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-align: center;
        color: #6b7280;
    }

    .product-picker-empty strong {
        color: #374151;
    }

    .product-picker-empty span {
        font-size: 13px;
    }

    @media (min-width: 700px) {
        .product-picker-modal {
            align-items: center;
            padding: 20px;
        }

        .product-picker-sheet {
            border-radius: 22px;
        }
    }
</style>


<script>

    function openRestockProductPicker()
    {
        const modal =
            document.getElementById('restockProductPicker');

        const search =
            document.getElementById('restockProductSearch');

        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');

        search.value = '';

        filterRestockProducts();

        setTimeout(function () {
            search.focus();
        }, 100);
    }


    function closeRestockProductPicker()
    {
        const modal =
            document.getElementById('restockProductPicker');

        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
    }


    function filterRestockProducts()
    {
        const keyword =
            document
                .getElementById('restockProductSearch')
                .value
                .trim()
                .toLowerCase();

        const items =
            document.querySelectorAll(
                '.product-picker-item'
            );

        let visibleCount = 0;

        items.forEach(function (item) {

            const name =
                item.dataset.name || '';

            const visible =
                name.includes(keyword);

            item.style.display =
                visible ? 'flex' : 'none';

            if (visible) {
                visibleCount++;
            }

        });

        document
            .getElementById('clearRestockProductSearch')
            .style.display =
            keyword ? 'flex' : 'none';

        document
            .getElementById('restockProductEmpty')
            .style.display =
            visibleCount === 0 ? 'flex' : 'none';
    }


    function clearRestockProductSearch()
    {
        const input =
            document.getElementById('restockProductSearch');

        input.value = '';

        filterRestockProducts();

        input.focus();
    }


    function selectRestockProduct(button)
    {
        const id =
            button.dataset.id;

        const name =
            button.dataset.label;

        const stock =
            parseInt(
                button.dataset.stock || 0,
                10
            );

        document.getElementById('product_id').value =
            id;

        document.getElementById('selectedProductName').textContent =
            name;

        const stockEl =
            document.getElementById('selectedProductStock');

        stockEl.textContent =
            'Stok saat ini: ' + stock;

        stockEl.style.display =
            'block';

        const pickerButton =
            document.getElementById('productPickerButton');

        pickerButton.classList.add('has-selection');

        closeRestockProductPicker();

        updateRestockCreatePreview();
    }


    function updateRestockCreatePreview()
    {
        const productId =
            document.getElementById('product_id').value;

        const quantityInput =
            document.getElementById('quantity');

        const preview =
            document.getElementById('restockCreatePreview');

        if (!productId) {
            preview.style.display = 'none';
            return;
        }

        const selected =
            document.querySelector(
                '.product-picker-item[data-id="' +
                productId +
                '"]'
            );

        if (!selected) {
            preview.style.display = 'none';
            return;
        }

        const stock =
            parseInt(
                selected.dataset.stock || 0,
                10
            );

        const qty =
            parseInt(
                quantityInput.value || 0,
                10
            );

        if (!qty) {
            preview.style.display = 'none';
            return;
        }

        preview.style.display = 'block';

        document.getElementById(
            'createPreviewBefore'
        ).textContent = stock;

        document.getElementById(
            'createPreviewAdd'
        ).textContent = '+' + qty;

        document.getElementById(
            'createPreviewAfter'
        ).textContent = stock + qty;
    }


    document.addEventListener(
        'DOMContentLoaded',
        function () {

            const oldProductId =
                document.getElementById('product_id').value;

            if (!oldProductId) {
                return;
            }

            const oldProduct =
                document.querySelector(
                    '.product-picker-item[data-id="' +
                    oldProductId +
                    '"]'
                );

            if (oldProduct) {
                selectRestockProduct(oldProduct);
            }

        }
    );


    document
        .getElementById('restockProductPicker')
        .addEventListener('click', function (event) {

            if (event.target === this) {
                closeRestockProductPicker();
            }

        });

</script>

@endsection