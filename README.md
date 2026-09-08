# Toko Saya

> Offline POS & Inventory Management Android App built with Laravel and NativePHP.

Toko Saya adalah aplikasi kasir dan manajemen stok yang dirancang untuk membantu usaha kecil mengelola produk, penjualan, pengeluaran, restock, laporan, dan arsip backup dalam satu aplikasi Android yang dapat digunakan secara offline.

Project ini dibangun sebagai aplikasi nyata sekaligus sebagai project portfolio untuk menunjukkan kemampuan dalam pengembangan Laravel, database, frontend, testing, dan integrasi Android.

---

## Features

### Dashboard

- Ringkasan kondisi toko
- Informasi penjualan
- Pengeluaran
- Stok dan aktivitas utama

### Product Management

- Tambah produk
- Edit produk
- Hapus produk
- Informasi harga dan stok
- Pencarian produk

### Sales

- Pencatatan transaksi penjualan
- Pemilihan produk dengan product picker
- Pencarian produk
- Perhitungan total transaksi
- Validasi stok
- Format harga Rupiah

### Restock

- Menambah stok produk
- Pemilihan produk dengan product picker
- Pencarian produk
- Riwayat restock

### Expense Management

- Pencatatan pengeluaran
- Kategori pengeluaran
- Catatan pengeluaran
- Format nominal Rupiah
- Edit dan hapus pengeluaran

### Monthly Reports

- Laporan penjualan
- Laporan pengeluaran
- Laporan restock
- Ringkasan hasil bersih
- Status untung/rugi

### Backup & Data Management

- Arsip backup berdasarkan bulan
- Backup JSON
- Laporan TXT
- Perlindungan terhadap duplikasi data
- Stable UUID untuk data penting
- Pengelolaan data lokal

### User Interface

- Responsive mobile-first interface
- Mobile bottom navigation
- Custom product picker
- Product search
- Light mode
- Dark mode
- Android system bar theme synchronization

### Offline

- SQLite local database
- Penggunaan utama tidak membutuhkan koneksi internet
- Data tersimpan secara lokal pada perangkat

---

## Technology Stack

| Technology | Usage |
|---|---|
| PHP 8.4 | Backend |
| Laravel 13 | Application framework |
| SQLite | Local database |
| Blade | Server-rendered UI |
| JavaScript | Interactive UI |
| CSS | Responsive interface |
| NativePHP Mobile | Android integration |
| Kotlin | Native Android integration |
| Vite | Frontend asset build |
| Pest / PHPUnit | Automated testing |

---

## Architecture

Aplikasi menggunakan pendekatan offline-first.

```text
Android App
|
+-- NativePHP
|   |
|   +-- Native Android Layer
|
+-- Laravel
    |
    +-- Controllers
    +-- Services
    +-- Models
    +-- Blade Views
    +-- SQLite
```

Data transaksi disimpan secara lokal sehingga fitur utama aplikasi tetap dapat digunakan tanpa koneksi internet.

---

## Screenshots

<p align="center">
  <img src="screenshots/dashboard.jpeg" width="30%">
  <img src="screenshots/products.jpeg" width="30%">
  <img src="screenshots/sales.jpeg" width="30%">
</p>

<p align="center">
  <img src="screenshots/restock.jpeg" width="30%">
  <img src="screenshots/expenses.jpeg" width="30%">
  <img src="screenshots/dark-mode.jpeg" width="30%">
</p>  +-- SQLite