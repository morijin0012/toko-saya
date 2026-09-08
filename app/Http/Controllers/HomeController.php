<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Product;
use App\Models\Restock;
use App\Models\Sale;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        // ==== Data lama (jangan diubah) ====

        $totalProducts = Product::count();

        $totalStock = Product::sum('stock');

        $latestRestocks = Restock::with('product')
            ->latest()
            ->take(5)
            ->get();

        // ==== Statistik keuangan bulan ini ====

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $monthRevenue = Sale::whereBetween('sold_at', [$startOfMonth, $endOfMonth])->sum('total');

        $monthExpenses = Expense::whereBetween('expense_date', [$startOfMonth, $endOfMonth])->sum('amount');

        $monthProfit = $monthRevenue - $monthExpenses;

        $monthSalesCount = Sale::whereBetween('sold_at', [$startOfMonth, $endOfMonth])->count();

        $todayRevenue = Sale::whereDate('sold_at', Carbon::today())->sum('total');

        // ==== Ringkasan kondisi toko ====

        $lowStockCount = Product::where('stock', '>', 0)->where('stock', '<=', 10)->count();

        $outOfStockCount = Product::where('stock', '<=', 0)->count();

        $topProduct = Product::orderByDesc('stock')->first();

        $recentlyRestockedProduct = $latestRestocks->first()?->product;

        // ==== Grafik penjualan 7 hari terakhir (tanpa library berat) ====

        $salesChart = collect(range(6, 0))->map(function ($daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);

            return [
                'label' => $date->translatedFormat('D'),
                'total' => (int) Sale::whereDate('sold_at', $date)->sum('total'),
            ];
        });

        $maxChartValue = max($salesChart->max('total'), 1);

        return view('home', [
            'totalProducts' => $totalProducts,
            'totalStock' => $totalStock,
            'latestRestocks' => $latestRestocks,

            'monthRevenue' => $monthRevenue,
            'monthExpenses' => $monthExpenses,
            'monthProfit' => $monthProfit,
            'monthSalesCount' => $monthSalesCount,
            'todayRevenue' => $todayRevenue,

            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
            'topProduct' => $topProduct,
            'recentlyRestockedProduct' => $recentlyRestockedProduct,

            'salesChart' => $salesChart,
            'maxChartValue' => $maxChartValue,
        ]);
    }
}
