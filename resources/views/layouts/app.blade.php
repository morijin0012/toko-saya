<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">

    <title>@yield('title', 'Dashboard') - Toko Saya</title>

    <script>
        (function () {
            const theme = localStorage.getItem('toko-saya-theme') || 'light';
            document.documentElement.dataset.theme = theme;
        })();
    </script>

    @vite('resources/css/app.css')
</head>

<body>

    <header class="navbar">

        <div class="navbar-content">

            <a href="/" class="brand">
                Toko Saya
            </a>

            <nav class="desktop-menu">

                <a href="/" class="{{ request()->is('/') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'dashboard', 'class' => 'icon-sm'])
                    Dashboard
                </a>

                <a href="/products" class="{{ request()->is('products*') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'product', 'class' => 'icon-sm'])
                    Produk
                </a>

                <a href="/restocks/create" class="{{ request()->is('restocks/create') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'restock', 'class' => 'icon-sm'])
                    Restock
                </a>

                <a href="/restocks" class="{{ request()->is('restocks') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'history', 'class' => 'icon-sm'])
                    Riwayat
                </a>

                <a href="/sales" class="{{ request()->is('sales*') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'sale', 'class' => 'icon-sm'])
                    Penjualan
                </a>

                <a href="/expenses" class="{{ request()->is('expenses*') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'expense', 'class' => 'icon-sm'])
                    Pengeluaran
                </a>

                <a href="/data" class="{{ request()->is('data*') ? 'active' : '' }}">
    		@include('partials.icon', ['name' => 'history', 'class' => 'icon-sm'])
   		 Data
		</a>

		<a href="{{ route('backups.index') }}" class="{{ request()->is('backups*') ? 'active' : '' }}">
    			@include('partials.icon', ['name' => 'database', 'class' => 'icon-sm'])
   		 Arsip Backup
		</a>

            </nav>

        </div>

    </header>


    <main class="main-content">

        @yield('content')

    </main>


    <nav class="mobile-menu">

    <a
        href="/"
        class="{{ request()->is('/') ? 'active' : '' }}"
    >
        @include('partials.icon', ['name' => 'dashboard'])
        <small>Dashboard</small>
    </a>

    <a
        href="/products"
        class="{{ request()->is('products*') ? 'active' : '' }}"
    >
        @include('partials.icon', ['name' => 'product'])
        <small>Produk</small>
    </a>

    <button
        type="button"
        class="mobile-more-button {{
            request()->is('restocks*')
            || request()->is('data*')
            || request()->is('backups*')
            ? 'active'
            : ''
        }}"
        onclick="openMoreMenu()"
        aria-label="Buka menu lainnya"
    >
        <span class="mobile-more-button-icon">
            @include('partials.icon', ['name' => 'more'])
        </span>

        <small>Lainnya</small>
    </button>

    <a
        href="/sales"
        class="{{ request()->is('sales*') ? 'active' : '' }}"
    >
        @include('partials.icon', ['name' => 'sale'])
        <small>Penjualan</small>
    </a>

    <a
        href="/expenses"
        class="{{ request()->is('expenses*') ? 'active' : '' }}"
    >
        @include('partials.icon', ['name' => 'expense'])
        <small>Pengeluaran</small>
    </a>

</nav>


 {{-- Sheet menu "Lainnya" untuk mobile --}}
<div id="moreMenu" class="edit-modal">

    <div class="edit-modal-content more-menu-content">

        <button
            type="button"
            class="modal-close"
            onclick="closeMoreMenu()"
        >
            @include('partials.icon', ['name' => 'close'])
        </button>

        <h2>Menu Lainnya</h2>

        <p class="modal-description">
            Akses cepat ke fitur lainnya.
        </p>

        <a href="/restocks/create" class="more-menu-item">
            @include('partials.icon', ['name' => 'restock'])

            <div>
                <strong>Restock</strong>
                <span>Tambah stok produk</span>
            </div>
        </a>

        <a href="/restocks" class="more-menu-item">
            @include('partials.icon', ['name' => 'history'])

            <div>
                <strong>Riwayat</strong>
                <span>Lihat riwayat restock</span>
            </div>
        </a>

        <a href="/data" class="more-menu-item">
            @include('partials.icon', ['name' => 'history'])

            <div>
                <strong>Kelola Data</strong>
                <span>Backup dan hapus data transaksi bulanan</span>
            </div>
        </a>

        <a
            href="{{ route('backups.index') }}"
            class="more-menu-item"
        >
            @include('partials.icon', ['name' => 'database'])

            <div>
                <strong>Arsip Backup</strong>
                <span>Lihat dan bagikan arsip bulanan</span>
            </div>
                </a>
                <button
            type="button"
            class="more-menu-item more-menu-theme"
            onclick="toggleTheme()"
        >
            <span class="theme-menu-icon" id="themeMenuIcon">
                @include('partials.icon', ['name' => 'database'])
            </span>

            <div>
                <strong id="themeMenuTitle">Mode Gelap</strong>
                <span id="themeMenuDescription">
                    Gunakan tampilan gelap
                </span>
            </div>
        </button>

    </div>

</div>

    <script>
    function openMoreMenu()
    {
        document.getElementById('moreMenu').classList.add('active');
        updateThemeMenu();
    }

    function closeMoreMenu()
    {
        document.getElementById('moreMenu').classList.remove('active');
    }

    document.getElementById('moreMenu').addEventListener('click', function (event) {
        if (event.target === this) {
            closeMoreMenu();
        }
    });


    function getCurrentTheme()
    {
        return document.documentElement.dataset.theme || 'light';
    }


    function applyTheme(theme)
    {
        document.documentElement.dataset.theme = theme;
        localStorage.setItem('toko-saya-theme', theme);

        updateThemeMenu();
    }


    function toggleTheme()
    {
        const currentTheme = getCurrentTheme();
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        applyTheme(newTheme);
    }


    function updateThemeMenu()
    {
        const theme = getCurrentTheme();

        const title = document.getElementById('themeMenuTitle');
        const description = document.getElementById('themeMenuDescription');

        if (!title || !description) {
            return;
        }

        if (theme === 'dark') {
            title.textContent = 'Mode Terang';
            description.textContent = 'Kembali ke tampilan terang';
        } else {
            title.textContent = 'Mode Gelap';
            description.textContent = 'Gunakan tampilan gelap';
        }
    }


    document.addEventListener('DOMContentLoaded', function () {
        updateThemeMenu();
    });
</script>

</body>

</html>
