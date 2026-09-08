# Toko Saya

> Offline POS & Inventory Management Android App built with Laravel and NativePHP.

Toko Saya adalah aplikasi kasir dan manajemen stok yang saya buat untuk membantu pencatatan operasional toko sehari-hari.

Aplikasi ini mencakup pengelolaan produk, penjualan, restock, pengeluaran, laporan bulanan, serta backup data. Fokus utama project ini adalah membuat aplikasi yang tetap dapat digunakan secara offline di Android.

Saya membangun project ini sambil mempelajari dan menerapkan Laravel, SQLite, Blade, JavaScript, testing, serta integrasi NativePHP dengan Android.

## Why I Built It

Saya ingin membuat aplikasi kasir sederhana yang tidak bergantung pada koneksi internet untuk penggunaan sehari-hari.

Selama mengembangkan Toko Saya, saya fokus pada penyimpanan data lokal, pencatatan transaksi, pengelolaan stok, backup dan restore, serta pengalaman penggunaan di perangkat mobile.

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
</p>

---

## Testing

Automated tests digunakan untuk memvalidasi fitur utama aplikasi.

Current test status:

```text
48 tests passed
```

Area yang diuji mencakup:

- Product management
- Sales
- Restock
- Expenses
- Monthly reports
- Backup
- Restore
- UUID data integrity
- Automatic backup
- Backup archive management

---

## Android

Aplikasi dikemas sebagai Android APK menggunakan NativePHP Mobile.

```text
Android API 36
```

Package:

```text
com.rizky.cloudsummitquasar
```

---

## What I Focused On

### Offline-first

Saya merancang aplikasi agar fitur utama tetap dapat digunakan tanpa koneksi internet dan data utama tersimpan secara lokal.

### Backup & Restore

Saya membuat sistem backup bulanan dengan arsip JSON dan laporan TXT agar data dapat disimpan dan dipulihkan kembali.

### Stable UUID

UUID digunakan untuk menjaga identitas data tetap konsisten ketika proses backup dan restore dilakukan.

### Mobile Product Picker

Saya membuat product picker dengan fitur pencarian agar pemilihan produk tetap nyaman ketika jumlah produk bertambah.

### Responsive Mobile Navigation

Saya membuat navigasi khusus mobile untuk memudahkan perpindahan antar fitur utama aplikasi.

### Light & Dark Mode

Saya menambahkan mode terang dan gelap serta menyesuaikan tampilan system bar Android agar tema aplikasi tetap konsisten.

---

## Installation

Clone repository:

```bash
git clone https://github.com/morijin0012/toko-saya.git
cd toko-saya
```

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies:

```bash
npm install
```

Copy environment file:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Run database migrations:

```bash
php artisan migrate
```

Build frontend assets:

```bash
npm run build
```

---

## Run Development Server

```bash
php artisan serve
```

For frontend development:

```bash
npm run dev
```

---

## Build Android APK

The Android application is packaged using NativePHP Mobile.

```bash
php artisan native:package android --build-type=release
```

---

## Project Structure

```text
app/
├── Http/
├── Models/
├── Services/
└── Providers/

database/
├── migrations/
└── seeders/

resources/
├── css/
├── js/
└── views/

routes/
└── web.php

tests/
├── Feature/
└── Unit/

nativephp/
└── android/
```

---

## Next Improvements

Beberapa hal yang ingin saya kembangkan berikutnya:

- Product categories
- More advanced reporting
- Additional data export options
- Improved backup management
- Additional Android native integrations

---

## Author

**Rizky**

Personal project built to learn, experiment, and improve my skills in Laravel, mobile application development, and software engineering.

---

## License

This project is intended primarily as a portfolio and learning project.