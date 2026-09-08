@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<div class="container fade-in">

    {{-- ===== APP TOPBAR (brand card, khusus Dashboard) ===== --}}

    <div class="app-topbar">
        <div class="app-topbar-logo">TS</div>
        <div class="app-topbar-text">
            <h1>Toko Saya</h1>
            <p>Kelola produk &amp; stok toko dengan mudah.</p>
        </div>
    </div>

    {{-- ===== STATISTIK UTAMA ===== --}}

    <div class="stats-grid-v2">

        <div class="stat-card-v2">
            <div class="stat-card-v2-icon stat-card-v2-icon-teal">@include('partials.icon', ['name' => 'box'])</div>
            <div class="stat-card-v2-body">
                <p class="stat-card-v2-label">Total Produk</p>
                <p class="stat-card-v2-value">{{ $totalProducts }}</p>
            </div>
        </div>

        <div class="stat-card-v2">
            <div class="stat-card-v2-icon stat-card-v2-icon-blue">@include('partials.icon', ['name' => 'stats'])</div>
            <div class="stat-card-v2-body">
                <p class="stat-card-v2-label">Total Stok</p>
                <p class="stat-card-v2-value">{{ $totalStock }}</p>
            </div>
        </div>

        <div class="stat-card-v2">
            <div class="stat-card-v2-icon stat-card-v2-icon-green">@include('partials.icon', ['name' => 'sale'])</div>
            <div class="stat-card-v2-body">
                <p class="stat-card-v2-label">Penjualan Bulan Ini</p>
                <p class="stat-card-v2-value">{{ $monthSalesCount }}</p>
                <p class="stat-card-v2-caption">transaksi</p>
            </div>
        </div>

        <div class="stat-card-v2">
            <div class="stat-card-v2-icon stat-card-v2-icon-orange">@include('partials.icon', ['name' => 'money'])</div>
            <div class="stat-card-v2-body">
                <p class="stat-card-v2-label">Pendapatan Bulan Ini</p>
                <p class="stat-card-v2-value">Rp {{ number_format($monthRevenue, 0, ',', '.') }}</p>
                <p class="stat-card-v2-caption">hari ini: Rp {{ number_format($todayRevenue, 0, ',', '.') }}</p>
            </div>
        </div>

    </div>

    {{-- Pengeluaran ditampilkan full-width (bukan ikut grid 2 kolom) supaya
         tidak menyisakan 1 card sendirian setengah lebar, dan supaya angka
         besar tetap nyaman dibaca. --}}
    <div class="info-row">
        <div class="info-row-icon info-row-icon-danger">@include('partials.icon', ['name' => 'expense'])</div>
        <div class="info-row-text">
            <p class="info-row-label">Pengeluaran Bulan Ini</p>
            <p class="info-row-value">Rp {{ number_format($monthExpenses, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="stats-grid-v2">

        <div class="stat-card-v2 stat-card-v2-highlight">
            <div class="stat-card-v2-icon">@include('partials.icon', ['name' => 'trend-up'])</div>
            <div class="stat-card-v2-body">
                <p class="stat-card-v2-label">Keuntungan Bulan Ini</p>
                <p class="stat-card-v2-value {{ $monthProfit < 0 ? 'stat-negative' : '' }}">
                    Rp {{ number_format($monthProfit, 0, ',', '.') }}
                </p>
            </div>
            <div class="stat-card-v2-highlight-trend">
                @include('partials.icon', ['name' => 'trend-up'])
            </div>
        </div>

    </div>


    {{-- ===== RINGKASAN KONDISI TOKO ===== --}}

    @if ($outOfStockCount > 0)
        <div class="status-banner status-banner-danger">
            @include('partials.icon', ['name' => 'warning', 'class' => 'icon-sm'])
            {{ $outOfStockCount }} produk stok habis
        </div>
    @endif

    @if ($lowStockCount > 0)
        <div class="status-banner status-banner-warning">
            @include('partials.icon', ['name' => 'warning', 'class' => 'icon-sm'])
            {{ $lowStockCount }} produk stok menipis
        </div>
    @endif

    @if ($outOfStockCount === 0 && $lowStockCount === 0 && $totalProducts > 0)
        <div class="status-banner status-banner-success">
            @include('partials.icon', ['name' => 'shield-check', 'class' => 'icon-sm'])
            Semua stok dalam kondisi aman
        </div>
    @endif

    @if ($topProduct)
        <div class="info-row">
            <div class="info-row-icon">@include('partials.icon', ['name' => 'trophy'])</div>
            <div class="info-row-text">
                <p class="info-row-label">Stok terbanyak</p>
                <p class="info-row-value">{{ $topProduct->name }} ({{ $topProduct->stock }})</p>
            </div>
        </div>
    @endif

    @if ($recentlyRestockedProduct)
        <div class="summary-badges">
            <div class="summary-badge">
                @include('partials.icon', ['name' => 'restock', 'class' => 'icon-sm'])
                Baru direstock: {{ $recentlyRestockedProduct->name }}
            </div>
        </div>
    @endif

    {{-- ===== AKSES CEPAT ===== --}}

    <p class="subsection-title">Akses Cepat</p>

    <div class="quick-actions-grid">

        <a href="/sales" class="quick-action-card quick-action-card-blue">
            @include('partials.icon', ['name' => 'sale'])
            <span>Penjualan</span>
        </a>

        <a href="/sales/create" class="quick-action-card quick-action-card-green">
            @include('partials.icon', ['name' => 'cart-plus'])
            <span>Catat Penjualan</span>
        </a>

        <a href="/expenses" class="quick-action-card quick-action-card-orange">
            @include('partials.icon', ['name' => 'wallet'])
            <span>Pengeluaran</span>
        </a>

        <a href="/data" class="quick-action-card quick-action-card-purple">
            @include('partials.icon', ['name' => 'database'])
            <span>Kelola Data</span>
        </a>

    </div>

    <div class="dashboard-grid">

        {{-- ===== AKTIVITAS RESTOCK TERBARU (TIMELINE) ===== --}}

        <div class="section">

            <div class="section-header">
                <h2>Aktivitas Restock Terbaru</h2>
                <a href="/restocks" class="link-more">Lihat Semua</a>
            </div>

            @if ($latestRestocks->count() > 0)

                <div class="timeline">

                    @foreach ($latestRestocks as $restock)

                        <div class="timeline-item">

                            <div class="timeline-icon">
                                @include('partials.icon', ['name' => 'restock', 'class' => 'icon-sm'])
                            </div>

                            <div class="timeline-content">
                                <p class="timeline-title">
                                    {{ $restock->product->name }}
                                    <span class="restock-quantity">+{{ $restock->quantity }}</span>
                                </p>
                                <p class="timeline-time">{{ $restock->created_at->format('d M Y, H:i') }}</p>
                            </div>

                        </div>

                    @endforeach

                </div>

            @else

                <p class="muted-text">Belum ada aktivitas restock.</p>

            @endif

        </div>


        {{-- ===== PIE CHART PENJUALAN 7 HARI ===== --}}

        <div class="section">

            <div class="section-header">
                <h2>Penjualan 7 Hari Terakhir</h2>
            </div>

            @php
                $chartTotal = $salesChart->sum('total');

                // Warna hidup & mudah dibedakan, khusus untuk pie chart.
                $pieColors = ['#2563eb', '#16a34a', '#f59e0b', '#dc2626', '#7c3aed', '#0891b2', '#db2777'];

                $cx = 100;
                $cy = 100;
                $r = 92;

                $cumulativeAngle = -90; // mulai dari jam 12
                $slices = [];
                $legend = [];

                foreach ($salesChart as $index => $point) {
                    $percent = $chartTotal > 0 ? ($point['total'] / $chartTotal) * 100 : 0;
                    $color = $pieColors[$index % count($pieColors)];

                    if ($percent > 0) {
                        $startAngle = $cumulativeAngle;
                        $sweep = ($percent / 100) * 360;
                        $endAngle = $startAngle + $sweep;

                        $startRad = deg2rad($startAngle);
                        $endRad = deg2rad($endAngle);

                        $x1 = $cx + $r * cos($startRad);
                        $y1 = $cy + $r * sin($startRad);
                        $x2 = $cx + $r * cos($endRad);
                        $y2 = $cy + $r * sin($endRad);

                        $largeArc = $sweep > 180 ? 1 : 0;

                        // Kalau cuma 1 hari yang ada datanya (100%), gambar lingkaran penuh.
                        if ($percent >= 99.99) {
                            $path = "M {$cx},{$cy} m -{$r},0 a {$r},{$r} 0 1,0 " . ($r * 2) . ",0 a {$r},{$r} 0 1,0 -" . ($r * 2) . ",0 Z";
                        } else {
                            $path = "M {$cx},{$cy} L {$x1},{$y1} A {$r},{$r} 0 {$largeArc},1 {$x2},{$y2} Z";
                        }

                        $slices[] = [
                            'path' => $path,
                            'color' => $color,
                            'label' => $point['label'],
                            'total' => $point['total'],
                            'percent' => round($percent),
                        ];

                        $cumulativeAngle = $endAngle;
                    }

                    $legend[] = [
                        'label' => $point['label'],
                        'total' => $point['total'],
                        'percent' => round($percent),
                        'color' => $color,
                    ];
                }
            @endphp

            @if ($chartTotal > 0)

                <div class="pie-chart-wrap">

                    <div class="pie-chart-svg-holder">

                        <svg viewBox="0 0 200 200" class="pie-chart-svg" id="salesPieChart">

                            @foreach ($slices as $i => $slice)
                                <path
                                    d="{{ $slice['path'] }}"
                                    fill="{{ $slice['color'] }}"
                                    class="pie-slice"
                                    data-label="{{ $slice['label'] }}"
                                    data-value="Rp {{ number_format($slice['total'], 0, ',', '.') }}"
                                    data-percent="{{ $slice['percent'] }}"
                                    onclick="selectPieSlice(this)"
                                    style="animation-delay: {{ $i * 0.06 }}s;"
                                >
                                    <title>{{ $slice['label'] }}: Rp {{ number_format($slice['total'], 0, ',', '.') }} ({{ $slice['percent'] }}%)</title>
                                </path>
                            @endforeach

                        </svg>

                        <div class="pie-chart-center pie-chart-center-svg" id="pieCenterInfo">
                            <span>Total 7 Hari</span>
                            <strong>Rp {{ number_format($chartTotal, 0, ',', '.') }}</strong>
                        </div>

                    </div>

                    <div class="pie-legend">
                        @foreach ($legend as $item)
                            @if ($item['total'] > 0)
                                <button
                                    type="button"
                                    class="pie-legend-item"
                                    onclick="selectPieSlice(document.querySelector('.pie-slice[data-label=\'{{ $item['label'] }}\']'))"
                                >
                                    <span class="pie-legend-dot" style="background: {{ $item['color'] }};"></span>
                                    <span class="pie-legend-label">{{ $item['label'] }}</span>
                                    <span class="pie-legend-value">{{ $item['percent'] }}%</span>
                                </button>
                            @endif
                        @endforeach
                    </div>

                </div>

                <script>

                    function selectPieSlice(el)
                    {
                        if (!el) return;

                        document.querySelectorAll('.pie-slice').forEach(function (s) {
                            s.classList.remove('pie-slice-active');
                        });

                        el.classList.add('pie-slice-active');

                        const info = document.getElementById('pieCenterInfo');

                        info.innerHTML =
                            '<span>' + el.dataset.label + '</span><strong>' + el.dataset.value + '</strong>';
                    }

                </script>

            @else

                <p class="muted-text">Belum ada data penjualan dalam 7 hari terakhir.</p>

            @endif

        </div>

    </div>

</div>

@endsection
