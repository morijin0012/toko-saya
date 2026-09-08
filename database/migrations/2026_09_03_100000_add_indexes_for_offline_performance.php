<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan index yang belum ada pada kolom yang paling sering dipakai
 * untuk filter/where (product_id, tanggal transaksi, created_at) di
 * restocks/sales/expenses.
 *
 * Ini tidak mengubah struktur data (tidak ada kolom baru, tidak ada data
 * yang berubah) — murni index untuk menjaga performa query tetap ringan
 * seiring data bertambah, khususnya penting untuk mode offline di
 * perangkat mobile yang sumber dayanya terbatas.
 *
 * Catatan: foreignId()->constrained() di SQLite TIDAK otomatis membuat
 * index seperti di MySQL, jadi index product_id di sini perlu ditambahkan
 * secara eksplisit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restocks', function (Blueprint $table) {
            $table->index('product_id', 'restocks_product_id_index_v2');
            $table->index('created_at', 'restocks_created_at_index');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index('product_id', 'sales_product_id_index_v2');
            $table->index('sold_at', 'sales_sold_at_index');
            $table->index('created_at', 'sales_created_at_index');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index('expense_date', 'expenses_expense_date_index');
            $table->index('created_at', 'expenses_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('restocks', function (Blueprint $table) {
            $table->dropIndex('restocks_product_id_index_v2');
            $table->dropIndex('restocks_created_at_index');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_product_id_index_v2');
            $table->dropIndex('sales_sold_at_index');
            $table->dropIndex('sales_created_at_index');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('expenses_expense_date_index');
            $table->dropIndex('expenses_created_at_index');
        });
    }
};
