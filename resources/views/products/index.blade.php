@extends('layouts.app')

@section('title', 'Produk')

@section('content')

<div class="container fade-in">

    <div class="page-header">

        <div class="page-header-main">
            <div class="page-header-icon icon-accent-teal">
                @include('partials.icon', ['name' => 'product'])
            </div>
            <div>
                <h1>Produk</h1>
                <p>Kelola daftar produk dan stok toko Anda.</p>
            </div>
        </div>

        <div class="page-header-actions">
            <button type="button" class="btn-primary" onclick="openAddModal()">
                + Tambah Produk
            </button>
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


    {{-- ===== SEARCH & SORT ===== --}}

    <div class="toolbar">

        <div class="search-box">
            @include('partials.icon', ['name' => 'search', 'class' => 'icon-sm'])
            <input
                type="text"
                id="productSearch"
                placeholder="Cari nama produk..."
                oninput="filterProducts()"
                autocomplete="off"
            >
            <button type="button" id="clearSearch" class="search-clear" onclick="clearProductSearch()" style="display:none;">
                @include('partials.icon', ['name' => 'close'])
            </button>
        </div>

        <select id="productSort" onchange="filterProducts()">
            <option value="default">Urutkan</option>
            <option value="name-asc">Nama A-Z</option>
            <option value="name-desc">Nama Z-A</option>
            <option value="stock-desc">Stok Tertinggi</option>
            <option value="stock-asc">Stok Terendah</option>
            <option value="price-desc">Harga Tertinggi</option>
            <option value="price-asc">Harga Terendah</option>
        </select>

    </div>


    {{-- DAFTAR PRODUK --}}

    @if ($products->count() > 0)

        <div class="product-list" id="productList">

            @foreach ($products as $product)

                @php
                    if ($product->stock <= 0) {
                        $stockStatus = ['label' => 'Stok habis', 'class' => 'stock-habis'];
                    } elseif ($product->stock <= 10) {
                        $stockStatus = ['label' => 'Stok menipis', 'class' => 'stock-menipis'];
                    } else {
                        $stockStatus = ['label' => 'Stok aman', 'class' => 'stock-aman'];
                    }
                @endphp

                <div
                    class="product-card"
                    data-name="{{ strtolower($product->name) }}"
                    data-stock="{{ $product->stock }}"
                    data-price="{{ $product->price }}"
                >

                    <button
                        type="button"
                        class="btn-delete"
                        onclick="openDeleteModal({{ $product->id }}, @js($product->name))"
                        title="Hapus Produk"
                    >
                        @include('partials.icon', ['name' => 'delete', 'class' => 'icon-sm'])
                    </button>

                    <div class="product-info">

                        <h2>{{ $product->name }}</h2>

                        <p class="product-price">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </p>

                        <p class="product-stock">
                            Stok: <strong>{{ $product->stock }}</strong>
                        </p>

                        <span class="stock-badge {{ $stockStatus['class'] }}">
                            {{ $stockStatus['label'] }}
                        </span>

                    </div>

                    <div class="product-actions">

                        <button
                            type="button"
                            class="btn-restock"
                            onclick="openRestockModal({{ $product->id }}, @js($product->name), {{ $product->stock }})"
                        >
                            @include('partials.icon', ['name' => 'restock', 'class' => 'icon-sm'])
                            Restock
                        </button>

                        <button
                            type="button"
                            class="btn-edit"
                            onclick="openEditModal({{ $product->id }}, @js($product->name), {{ $product->price }}, {{ $product->stock }})"
                        >
                            @include('partials.icon', ['name' => 'edit', 'class' => 'icon-sm'])
                            Edit
                        </button>

                    </div>

                </div>

            @endforeach

        </div>

        <div class="empty-state" id="productEmptySearch" style="display:none;">
            @include('partials.icon', ['name' => 'search', 'class' => 'icon-empty'])
            <h2>Produk Tidak Ditemukan</h2>
            <p>Coba kata kunci pencarian lain.</p>
        </div>

    @else

        <div class="empty-state">
            @include('partials.icon', ['name' => 'box', 'class' => 'icon-empty'])
            <h2>Belum Ada Produk</h2>
            <p>Tambahkan produk pertama Anda untuk mulai mengelola stok.</p>
            <button type="button" class="btn-primary" onclick="openAddModal()">+ Tambah Produk</button>
        </div>

    @endif

</div>


{{-- MODAL TAMBAH PRODUK --}}

<div id="addModal" class="edit-modal">
    <div class="edit-modal-content">

        <button type="button" class="modal-close" onclick="closeAddModal()">
            @include('partials.icon', ['name' => 'close'])
        </button>

        <h2>Tambah Produk</h2>
        <p class="modal-description">Tambahkan produk baru ke dalam stok.</p>

        <form id="addForm" action="/products" method="POST">

            @csrf

            <div class="form-group">
                <label for="add_name">Nama Produk</label>
                <input type="text" id="add_name" name="name" placeholder="Contoh: Kopi Bubuk 200gr" required>
            </div>

            <div class="form-group">
                <label for="add_price">Harga</label>
                <input
                    type="text"
                    id="add_price"
                    name="price"
                    inputmode="numeric"
                    autocomplete="off"
                    placeholder="Contoh: 30.000"
                    required
                >
            </div>

            <div class="form-group">
                <label for="add_stock">Stok Awal</label>
                <input type="number" id="add_stock" name="stock" placeholder="Contoh: 20" min="0" required>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeAddModal()">Batal</button>
                <button type="submit" class="btn-primary">Simpan Produk</button>
            </div>

        </form>

    </div>
</div>


{{-- MODAL EDIT PRODUK --}}

<div id="editModal" class="edit-modal">
    <div class="edit-modal-content">

        <button type="button" class="modal-close" onclick="closeEditModal()">
            @include('partials.icon', ['name' => 'close'])
        </button>

        <h2>Edit Produk</h2>
        <p class="modal-description">Ubah informasi produk.</p>

        <form id="editForm" method="POST">

            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="edit_name">Nama Produk</label>
                <input type="text" id="edit_name" name="name" required>
            </div>

            <div class="form-group">
                <label for="edit_price">Harga</label>
                <input
                type="text"
                id="edit_price"
                name="price"
                inputmode="numeric"
                autocomplete="off"
                required
            >
        </div>
            <div class="form-group">
                <label for="edit_stock">Stok</label>
                <input type="number" id="edit_stock" name="stock" min="0" required>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
            </div>

        </form>

    </div>
</div>


{{-- MODAL RESTOCK --}}

<div id="restockModal" class="edit-modal">
    <div class="edit-modal-content">

        <button type="button" class="modal-close" onclick="closeRestockModal()">
            @include('partials.icon', ['name' => 'close'])
        </button>

        <div class="modal-icon-badge">
            @include('partials.icon', ['name' => 'restock'])
        </div>

        <h2>Restock Produk</h2>
        <p class="modal-description">Tambahkan stok untuk produk yang dipilih.</p>

        <form id="restockForm" action="/restocks" method="POST">

            @csrf

            <input type="hidden" id="restock_product_id" name="product_id">

            <div class="form-group">
                <label>Nama Produk</label>
                <input type="text" id="restock_product_name" readonly>
            </div>

            <div class="form-group">
                <label>Stok Saat Ini</label>
                <input type="number" id="restock_current_stock" readonly>
            </div>

            <div class="form-group">
                <label for="restock_quantity">Jumlah Restock</label>
                <input type="number" id="restock_quantity" name="quantity" min="1" placeholder="Contoh: 10" oninput="updateRestockPreview()" required>
            </div>

            <div class="preview-box" id="restockPreview" style="display:none;">
                <div class="preview-row">
                    <span>Stok saat ini</span>
                    <strong id="restockPreviewBefore">-</strong>
                </div>
                <div class="preview-row">
                    <span>Restock</span>
                    <strong id="restockPreviewAdd">-</strong>
                </div>
                <div class="preview-row preview-row-highlight">
                    <span>Stok setelah restock</span>
                    <strong id="restockPreviewAfter">-</strong>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeRestockModal()">Batal</button>
                <button type="submit" class="btn-primary">Simpan Restock</button>
            </div>

        </form>

    </div>
</div>


{{-- MODAL HAPUS PRODUK --}}

<div id="deleteModal" class="edit-modal">
    <div class="delete-modal-content">

        <div class="delete-icon">
            @include('partials.icon', ['name' => 'warning'])
        </div>

        <h2>Hapus Produk?</h2>

        <p class="modal-description">
            Apakah kamu yakin ingin menghapus <strong id="delete_product_name"></strong>?
        </p>

        <p class="delete-warning">
            Data produk beserta seluruh riwayat restock dan penjualan yang terkait akan ikut terhapus. Tindakan ini tidak dapat dibatalkan.
        </p>

        <form id="deleteForm" method="POST">

            @csrf
            @method('DELETE')

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Batal</button>
                <button type="submit" class="btn-delete-confirm">Ya, Hapus</button>
            </div>

        </form>

    </div>
</div>


<script>

    // ========== SEARCH & SORT ==========

    function filterProducts()
    {
        const keyword = document.getElementById('productSearch').value.trim().toLowerCase();
        const sortBy = document.getElementById('productSort').value;

        document.getElementById('clearSearch').style.display = keyword ? 'flex' : 'none';

        const list = document.getElementById('productList');
        if (!list) return;

        const cards = Array.from(list.querySelectorAll('.product-card'));

        let visibleCount = 0;

        cards.forEach(function (card) {
            const matches = card.dataset.name.includes(keyword);
            card.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });

        document.getElementById('productEmptySearch').style.display =
            (visibleCount === 0 && keyword) ? 'block' : 'none';

        const sorters = {
            'name-asc': (a, b) => a.dataset.name.localeCompare(b.dataset.name),
            'name-desc': (a, b) => b.dataset.name.localeCompare(a.dataset.name),
            'stock-desc': (a, b) => b.dataset.stock - a.dataset.stock,
            'stock-asc': (a, b) => a.dataset.stock - b.dataset.stock,
            'price-desc': (a, b) => b.dataset.price - a.dataset.price,
            'price-asc': (a, b) => a.dataset.price - b.dataset.price,
        };

        if (sorters[sortBy]) {
            cards.sort(sorters[sortBy]).forEach(function (card) {
                list.appendChild(card);
            });
        }
    }

    function clearProductSearch()
    {
        document.getElementById('productSearch').value = '';
        filterProducts();
        document.getElementById('productSearch').focus();
    }
    function formatPriceInput(value)
{
    const numeric = String(value).replace(/\D/g, '');

    return numeric
        ? Number(numeric).toLocaleString('id-ID')
        : '';
}

function getNumericPrice(value)
{
    return parseInt(
        String(value).replace(/\D/g, ''),
        10
    ) || 0;
}

const addPriceInput = document.getElementById('add_price');
const editPriceInput = document.getElementById('edit_price');

if (addPriceInput) {
    addPriceInput.addEventListener('input', function () {
        this.value = formatPriceInput(this.value);
    });
}

if (editPriceInput) {
    editPriceInput.addEventListener('input', function () {
        this.value = formatPriceInput(this.value);
    });
}

    // ========== TAMBAH PRODUK ==========

    function openAddModal()
    {
        document.getElementById('addForm').reset();
        document.getElementById('addModal').classList.add('active');
    }

    function closeAddModal()
    {
        document.getElementById('addModal').classList.remove('active');
    }


    // ========== EDIT PRODUK ==========

    function openEditModal(id, name, price, stock)
{
    document.getElementById('edit_name').value = name;

    document.getElementById('edit_price').value =
        formatPriceInput(price);

    document.getElementById('edit_stock').value = stock;

    document.getElementById('editForm').action =
        '/products/' + id;

    document.getElementById('editModal').classList.add('active');
}

    function closeEditModal()
    {
        document.getElementById('editModal').classList.remove('active');
    }


    // ========== RESTOCK ==========

    function openRestockModal(id, name, stock)
    {
        document.getElementById('restock_product_id').value = id;
        document.getElementById('restock_product_name').value = name;
        document.getElementById('restock_current_stock').value = stock;
        document.getElementById('restock_quantity').value = '';
        document.getElementById('restockPreview').style.display = 'none';
        document.getElementById('restockModal').classList.add('active');
    }

    function closeRestockModal()
    {
        document.getElementById('restockModal').classList.remove('active');
    }

    function updateRestockPreview()
    {
        const current = parseInt(document.getElementById('restock_current_stock').value || 0);
        const qty = parseInt(document.getElementById('restock_quantity').value || 0);

        const preview = document.getElementById('restockPreview');

        if (!qty) {
            preview.style.display = 'none';
            return;
        }

        preview.style.display = 'block';
        document.getElementById('restockPreviewBefore').textContent = current;
        document.getElementById('restockPreviewAdd').textContent = '+' + qty;
        document.getElementById('restockPreviewAfter').textContent = current + qty;
    }


    // ========== HAPUS PRODUK ==========

    function openDeleteModal(id, name)
    {
        document.getElementById('delete_product_name').textContent = name;
        document.getElementById('deleteForm').action = '/products/' + id;
        document.getElementById('deleteModal').classList.add('active');
    }

    function closeDeleteModal()
    {
        document.getElementById('deleteModal').classList.remove('active');
    }


    // ========== KLIK DI LUAR MODAL ==========

    ['addModal', 'editModal', 'restockModal', 'deleteModal'].forEach(function (id) {
        document.getElementById(id).addEventListener('click', function (event) {
            if (event.target === this) {
                this.classList.remove('active');
            }
        });
    });

</script>

@endsection
