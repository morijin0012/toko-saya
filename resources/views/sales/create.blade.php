@extends('layouts.app')

@section('title', 'Catat Penjualan')

@section('content')

<div class="container fade-in">

    <div class="page-header">
        <div class="page-header-main">
            <div class="page-header-icon icon-accent-blue">
                @include('partials.icon', ['name' => 'sale'])
            </div>

            <div>
                <h1>Catat Penjualan</h1>
                <p>Pilih produk dan masukkan jumlah terjual.</p>
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

        <div class="form-card-icon icon-accent-blue">
            @include('partials.icon', ['name' => 'sale'])
        </div>

        <h2 class="form-card-title">Transaksi Baru</h2>

        <p class="form-card-subtitle">
            Pilih produk, jumlah, dan harga jual.
        </p>

        <form
            action="/sales"
            method="POST"
            id="saleForm"
        >

            @csrf

            {{-- =========================
                 PILIH PRODUK
            ========================== --}}

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
                    class="product-picker-button sale-product-picker-button"
                    onclick="openSaleProductPicker()"
                >
                    <span class="product-picker-icon sale-product-picker-icon">
                        @include('partials.icon', [
                            'name' => 'product',
                            'class' => 'icon-sm'
                        ])
                    </span>

                    <span
                        class="product-picker-text"
                        id="selectedSaleProductName"
                    >
                        -- Pilih Produk --
                    </span>

                    <span class="product-picker-arrow">⌄</span>
                </button>

                <p
                    class="product-picker-stock"
                    id="selectedSaleProductStock"
                    style="display:none;"
                ></p>
            </div>


            {{-- =========================
                 JUMLAH TERJUAL
            ========================== --}}

            <div class="form-group">
                <label for="quantity">Jumlah Terjual</label>

                <input
                    type="number"
                    id="quantity"
                    name="quantity"
                    min="1"
                    value="{{ old('quantity', 1) }}"
                    oninput="updateSalePreview()"
                    required
                >
            </div>


            {{-- =========================
                 HARGA JUAL
            ========================== --}}

            <div class="form-group">
                <label for="price">Harga Jual (per satuan)</label>

                <input
                    type="text"
                    id="price"
                    name="price"
                    inputmode="numeric"
                    autocomplete="off"
                    value="{{ old('price') }}"
                    placeholder="Contoh: 30.000"
                    required
                >
            </div>


            {{-- =========================
                 TANGGAL
            ========================== --}}

            <div class="form-group">
                <label for="sold_at">Tanggal Penjualan</label>

                <input
                    type="date"
                    id="sold_at"
                    name="sold_at"
                    value="{{ old('sold_at', date('Y-m-d')) }}"
                    required
                >
            </div>


            {{-- =========================
                 PREVIEW
            ========================== --}}

            <div
                class="preview-box preview-box-featured"
                id="salePreview"
                style="display:none;"
            >

                <div class="preview-row">
                    <span>Stok saat ini</span>
                    <strong id="previewStockBefore">-</strong>
                </div>

                <div class="preview-row">
                    <span>Terjual</span>
                    <strong id="previewQty">-</strong>
                </div>

                <div class="preview-row preview-row-highlight">
                    <span>Stok setelah penjualan</span>
                    <strong id="previewStockAfter">-</strong>
                </div>

                <div class="preview-row">
                    <span>Total penjualan</span>
                    <strong id="previewTotal">Rp 0</strong>
                </div>

            </div>


            {{-- =========================
                 ACTION
            ========================== --}}

            <div class="form-actions">

                <a
                    href="/sales"
                    class="btn-secondary"
                >
                    Kembali
                </a>

                <button
                    type="submit"
                    class="btn-primary"
                >
                    @include('partials.icon', [
                        'name' => 'sale',
                        'class' => 'icon-sm'
                    ])

                    Simpan Penjualan
                </button>

            </div>

        </form>

    </div>

</div>


{{-- =========================================================
     PRODUCT PICKER
========================================================= --}}

<div
    id="saleProductPicker"
    class="product-picker-modal"
    aria-hidden="true"
>
    <div class="product-picker-sheet">

        <div class="product-picker-header">

            <div>
                <h2>Pilih Produk</h2>

                <p>
                    Cari produk yang ingin dijual.
                </p>
            </div>

            <button
                type="button"
                class="modal-close"
                onclick="closeSaleProductPicker()"
                aria-label="Tutup"
            >
                @include('partials.icon', [
                    'name' => 'close'
                ])
            </button>

        </div>


        {{-- SEARCH --}}

        <div class="product-picker-search">

            @include('partials.icon', [
                'name' => 'search',
                'class' => 'icon-sm'
            ])

            <input
                type="text"
                id="saleProductSearch"
                placeholder="Cari nama produk..."
                autocomplete="off"
                oninput="filterSaleProducts()"
            >

            <button
                type="button"
                id="clearSaleProductSearch"
                class="search-clear"
                onclick="clearSaleProductSearch()"
                style="display:none;"
                aria-label="Bersihkan pencarian"
            >
                @include('partials.icon', [
                    'name' => 'close'
                ])
            </button>

        </div>


        {{-- PRODUCT LIST --}}

        <div
            id="saleProductList"
            class="product-picker-list"
        >

            @foreach ($products as $product)

                <button
                    type="button"
                    class="product-picker-item sale-product-picker-item"
                    data-id="{{ $product->id }}"
                    data-name="{{ strtolower($product->name) }}"
                    data-label="{{ $product->name }}"
                    data-stock="{{ $product->stock }}"
                    data-price="{{ $product->price }}"
                    onclick="selectSaleProduct(this)"
                >

                    <span class="product-picker-item-icon sale-product-item-icon">
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
                            Stok: {{ $product->stock }}
                            &nbsp;·&nbsp;
                            Harga: Rp {{ number_format($product->price, 0, ',', '.') }}
                        </small>

                    </span>

                    <span class="product-picker-item-arrow">
                        ›
                    </span>

                </button>

            @endforeach

        </div>


        {{-- EMPTY SEARCH --}}

        <div
            id="saleProductEmpty"
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
        border-color: rgba(37, 99, 235, .45);
    }

    .product-picker-button:focus-visible {
        outline: 3px solid rgba(37, 99, 235, .14);
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

    .sale-product-picker-icon {
        background: rgba(37, 99, 235, .10);
        color: #2563eb;
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
        border-color: rgba(37, 99, 235, .35);
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

    .sale-product-item-icon {
        background: rgba(37, 99, 235, .10);
        color: #2563eb;
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

    // =========================================================
    // FORMAT HARGA
    // =========================================================

    function formatRupiahInput(value)
    {
        const numeric = String(value).replace(/\D/g, '');

        return numeric
            ? Number(numeric).toLocaleString('id-ID')
            : '';
    }


    function getNumericValue(value)
    {
        return parseInt(
            String(value).replace(/\D/g, ''),
            10
        ) || 0;
    }


    // =========================================================
    // PRODUCT PICKER
    // =========================================================

    function openSaleProductPicker()
    {
        const modal =
            document.getElementById('saleProductPicker');

        const search =
            document.getElementById('saleProductSearch');

        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');

        search.value = '';

        filterSaleProducts();

        setTimeout(function () {
            search.focus();
        }, 100);
    }


    function closeSaleProductPicker()
    {
        const modal =
            document.getElementById('saleProductPicker');

        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
    }


    function filterSaleProducts()
    {
        const keyword =
            document
                .getElementById('saleProductSearch')
                .value
                .trim()
                .toLowerCase();

        const items =
            document.querySelectorAll(
                '.sale-product-picker-item'
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
            .getElementById('clearSaleProductSearch')
            .style.display =
            keyword ? 'flex' : 'none';

        document
            .getElementById('saleProductEmpty')
            .style.display =
            visibleCount === 0 ? 'flex' : 'none';
    }


    function clearSaleProductSearch()
    {
        const input =
            document.getElementById('saleProductSearch');

        input.value = '';

        filterSaleProducts();

        input.focus();
    }


    function selectSaleProduct(button)
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

        const price =
            parseInt(
                button.dataset.price || 0,
                10
            );

        document.getElementById('product_id').value =
            id;

        document.getElementById(
            'selectedSaleProductName'
        ).textContent = name;

        const stockEl =
            document.getElementById(
                'selectedSaleProductStock'
            );

        stockEl.textContent =
            'Stok tersedia: ' + stock;

        stockEl.style.display = 'block';

        document
            .getElementById('productPickerButton')
            .classList.add('has-selection');

        /*
         * Harga produk otomatis digunakan sebagai
         * harga jual awal.
         */
        document.getElementById('price').value =
            formatRupiahInput(price);

        closeSaleProductPicker();

        updateSalePreview();
    }


    // =========================================================
    // SALE PREVIEW
    // =========================================================

    function updateSalePreview()
    {
        const productId =
            document.getElementById('product_id').value;

        const quantityInput =
            document.getElementById('quantity');

        const priceInput =
            document.getElementById('price');

        const preview =
            document.getElementById('salePreview');

        if (!productId) {
            preview.style.display = 'none';
            return;
        }

        const selected =
            document.querySelector(
                '.sale-product-picker-item[data-id="' +
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

        /*
         * Jika harga kosong, isi dengan harga produk.
         */
        if (!priceInput.value) {
            priceInput.value =
                formatRupiahInput(
                    selected.dataset.price || 0
                );
        }

        const price =
            getNumericValue(
                priceInput.value
            );

        preview.style.display = 'block';

        document.getElementById(
            'previewStockBefore'
        ).textContent = stock;

        document.getElementById(
            'previewQty'
        ).textContent = '-' + qty;

        const after =
            stock - qty;

        const afterEl =
            document.getElementById(
                'previewStockAfter'
            );

        afterEl.textContent =
            after;

        afterEl.style.color =
            after < 0
                ? 'var(--color-danger)'
                : 'var(--color-success-text)';

        const total =
            qty * price;

        document.getElementById(
            'previewTotal'
        ).textContent =
            'Rp ' +
            total.toLocaleString('id-ID');
    }


    // =========================================================
    // FORMAT HARGA SAAT DIKETIK
    // =========================================================

    const salePriceInput =
        document.getElementById('price');

    if (salePriceInput) {

        salePriceInput.addEventListener(
            'input',
            function () {

                this.value =
                    formatRupiahInput(this.value);

                updateSalePreview();

            }
        );

    }


    // =========================================================
    // INITIAL STATE
    // =========================================================

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            const productId =
                document.getElementById('product_id').value;

            /*
             * Kalau validasi sebelumnya gagal dan product_id
             * masih ada, tampilkan kembali produk tersebut.
             */
            if (productId) {

                const selected =
                    document.querySelector(
                        '.sale-product-picker-item[data-id="' +
                        productId +
                        '"]'
                    );

                if (selected) {

                    document.getElementById(
                        'selectedSaleProductName'
                    ).textContent =
                        selected.dataset.label;

                    const stock =
                        parseInt(
                            selected.dataset.stock || 0,
                            10
                        );

                    const stockEl =
                        document.getElementById(
                            'selectedSaleProductStock'
                        );

                    stockEl.textContent =
                        'Stok tersedia: ' + stock;

                    stockEl.style.display =
                        'block';

                    document
                        .getElementById(
                            'productPickerButton'
                        )
                        .classList.add(
                            'has-selection'
                        );
                }
            }

            const priceInput =
                document.getElementById('price');

            if (priceInput.value) {
                priceInput.value =
                    formatRupiahInput(
                        priceInput.value
                    );
            }

            updateSalePreview();
        }
    );


    // Tutup picker jika klik area gelap di luar sheet.
    document
        .getElementById('saleProductPicker')
        .addEventListener(
            'click',
            function (event) {

                if (event.target === this) {
                    closeSaleProductPicker();
                }

            }
        );

</script>

@endsection