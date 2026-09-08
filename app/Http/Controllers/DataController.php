<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Product;
use App\Models\Restock;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Native\Mobile\Facades\Share;
use Throwable;

class DataController extends Controller
{
    /**
     * Nama aplikasi yang dipakai untuk menandai & memvalidasi file backup.
     */
    private const APP_NAME = 'Toko Saya';

    /**
     * Versi format backup saat ini.
     *
     * Riwayat format:
     * - 1.0/1.1: hanya sales/restocks/expenses, tanpa uuid, tanpa snapshot
     *   produk. Duplicate-protection memakai kombinasi kolom data.
     * - 2.0: menambahkan (a) `uuid` stabil per baris transaksi untuk
     *   duplicate-protection yang tidak mungkin salah anggap dua transaksi
     *   asli yang identik sebagai duplicate, (b) snapshot seluruh Produk
     *   supaya restore tetap bisa memulihkan transaksi walau Product aslinya
     *   sudah dihapus / direstore ke database yang berbeda / database kosong,
     *   dan (c) `product_name` di tiap baris sales/restocks sebagai penanda
     *   tambahan (bukan pengganti id) untuk mendeteksi jika sebuah product_id
     *   sudah dipakai ulang oleh produk yang berbeda di database tujuan.
     * - 2.1: Product sekarang juga punya `uuid` stabil sendiri (lihat
     *   App\Models\Product). Snapshot produk di backup menyertakan `uuid`
     *   tsb, dan tiap baris sales/restocks menyertakan `product_uuid` (uuid
     *   dari Product yang ditunjuk saat baris itu dibuat). Ini jadi kunci
     *   pencocokan Product yang PALING diutamakan saat restore — lebih
     *   aman daripada id+name karena tidak mungkin salah tempel walau `id`
     *   Product berubah/dipakai ulang ATAU namanya diedit setelah backup
     *   dibuat (lihat DataController::resolveProductId()).
     *
     * Restore mendukung SEMUA format sekaligus lewat feature-detection
     * (mengecek keberadaan field, bukan mencocokkan angka versi secara
     * ketat), supaya file backup lama tetap bisa dipulihkan dengan aman.
     */
    private const BACKUP_VERSION = '2.1';

    /**
     * Ambil rentang tanggal (awal & akhir bulan) dari string "Y-m".
     */
    private function monthRange(string $month): array
    {
        $date = Carbon::createFromFormat('Y-m', $month);

        return [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()];
    }

    /**
     * Halaman Kelola Data: pilih bulan, lihat ringkasan, backup/hapus.
     */
    public function index(Request $request)
    {
        $selectedMonth = $request->input('month', now()->format('Y-m'));

        // Jika parameter month dari URL tidak valid (mis. diutak-atik manual),
        // jangan sampai halaman error — kembali ke bulan berjalan.
        if (! preg_match('/^\d{4}-\d{2}$/', $selectedMonth) || ! $this->isValidMonth($selectedMonth)) {
            $selectedMonth = now()->format('Y-m');
        }

        [$start, $end] = $this->monthRange($selectedMonth);

        $salesCount = Sale::whereBetween('sold_at', [$start, $end])->count();

        $restocksCount = Restock::whereBetween('created_at', [$start, $end])->count();

        $expensesCount = Expense::whereBetween('expense_date', [$start, $end])->count();

        // Opsi bulan: 12 bulan ke belakang dari sekarang.
        $monthOptions = collect(range(0, 11))->map(function ($i) {
            $date = now()->subMonths($i);

            return [
                'value' => $date->format('Y-m'),
                'label' => $date->translatedFormat('F Y'),
            ];
        });

        return view('data.index', [
            'selectedMonth' => $selectedMonth,
            'selectedMonthLabel' => Carbon::createFromFormat('Y-m', $selectedMonth)->translatedFormat('F Y'),
            'salesCount' => $salesCount,
            'restocksCount' => $restocksCount,
            'expensesCount' => $expensesCount,
            'monthOptions' => $monthOptions,
        ]);
    }

    private function isValidMonth(string $month): bool
    {
        try {
            Carbon::createFromFormat('Y-m', $month);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Parse sebuah nilai tanggal/waktu dari file backup (format apa pun yang
     * bisa dibaca Carbon, mis. "Y-m-d H:i:s" atau ISO8601) menjadi string
     * "Y-m-d H:i:s" yang konsisten dengan format yang dipakai kolom
     * created_at/updated_at di database. Mengembalikan null jika tidak valid,
     * supaya baris tersebut bisa dilewati dengan aman alih-alih error.
     */
    private function normalizeDateTime(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Backup data transaksi (Penjualan, Restock, Pengeluaran) pada bulan
     * tertentu menjadi file JSON yang bisa diunduh.
     *
     * Sejak format 2.0, backup JUGA menyertakan snapshot SELURUH data Produk
     * saat ini (bukan cuma produk yang muncul di transaksi bulan ini). Ini
     * adalah desain paling aman untuk "Product Master Strategy": karena
     * Product bisa dihapus kapan saja (lihat ProductController::destroy),
     * satu-satunya cara menjamin restore SELALU bisa memulihkan transaksi
     * (baik hari ini, maupun bertahun-tahun kemudian ke database yang sudah
     * jauh berbeda) adalah dengan menyimpan datanya di dalam file backup itu
     * sendiri, bukan mengandalkan Product yang mungkin sudah tidak ada.
     * Biaya penyimpanan ini sangat kecil (daftar produk biasanya hanya
     * puluhan/ratusan baris) dibandingkan risiko kehilangan riwayat
     * transaksi secara permanen.
     *
     * created_at/updated_at masing-masing baris ikut disertakan apa adanya
     * agar saat restore, tanggal transaksi asli (termasuk tanggal restock,
     * yang memakai created_at sebagai tanggal transaksi) tidak berubah.
     */
    public function backup(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        try {
            [$start, $end] = $this->monthRange($request->month);

            $sales = Sale::with('product')
                ->whereBetween('sold_at', [$start, $end])
                ->orderBy('id')
                ->get()
                ->map(fn (Sale $sale) => $this->exportSaleRow($sale));

            $restocks = Restock::with('product')
                ->whereBetween('created_at', [$start, $end])
                ->orderBy('id')
                ->get()
                ->map(fn (Restock $restock) => $this->exportRestockRow($restock));

            $expenses = Expense::whereBetween('expense_date', [$start, $end])
                ->orderBy('id')
                ->get()
                ->map(fn (Expense $expense) => $this->exportExpenseRow($expense));

            $backup = [
                'app' => self::APP_NAME,
                'backup_version' => self::BACKUP_VERSION,
                'backup_type' => 'monthly',
                'period' => $request->month,
                'generated_at' => now()->toDateTimeString(),
                // Snapshot seluruh Produk saat ini (lihat penjelasan di
                // docblock method ini). Field ini HANYA dipakai saat restore
                // untuk memulihkan Product yang hilang — TIDAK PERNAH dipakai
                // untuk menimpa data Product yang masih ada.
                'products' => Product::orderBy('id')->get(['id', 'uuid', 'name', 'price', 'stock', 'created_at', 'updated_at'])->toArray(),
                'sales' => $sales->values()->all(),
                'restocks' => $restocks->values()->all(),
                'expenses' => $expenses->values()->all(),
            ];

            $filename = 'toko-saya-backup-'.$request->month.'.json';

            $json = json_encode(
                $backup,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );

            if (function_exists('nativephp_call') && ! app()->environment('testing')) {
                $path = storage_path('app/'.$filename);

                if (file_put_contents($path, $json) === false) {
                    throw new \RuntimeException('Gagal menulis file backup ke storage aplikasi.');
                }

                Share::file(
                    'Backup '.self::APP_NAME,
                    'Backup data transaksi bulan '.$request->month.'. Simpan file ini di penyimpanan perangkat atau bagikan ke aplikasi lain.',
                    $path
                );

                return redirect('/data?month='.$request->month);
            }

            return response(
                $json,
                200,
                [
                    'Content-Type' => 'application/json',
                    'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                ]
            );
        } catch (Throwable $e) {
            return back()->withErrors([
                'month' => 'BACKUP ERROR: '.$e::class.' — '.$e->getMessage(),
            ]);
        }
    }

    private function exportSaleRow(Sale $sale): array
    {
        return [
            'uuid' => $sale->uuid,
            'product_id' => $sale->product_id,
            'product_uuid' => $sale->product?->uuid,
            'product_name' => $sale->product?->name,
            'quantity' => $sale->quantity,
            'price' => $sale->price,
            'total' => $sale->total,
            // sold_at sekarang di-cast ke Carbon (lihat App\Models\Sale).
            // Format eksplisit ke 'Y-m-d' supaya isi file backup TIDAK
            // berubah dari sebelumnya (dulu string mentah "Y-m-d" langsung
            // dari database) — format backup 2.1 tetap sama persis.
            'sold_at' => $sale->sold_at?->format('Y-m-d'),
            'created_at' => $sale->created_at,
            'updated_at' => $sale->updated_at,
        ];
    }

    private function exportRestockRow(Restock $restock): array
    {
        return [
            'uuid' => $restock->uuid,
            'product_id' => $restock->product_id,
            'product_uuid' => $restock->product?->uuid,
            'product_name' => $restock->product?->name,
            'quantity' => $restock->quantity,
            'created_at' => $restock->created_at,
            'updated_at' => $restock->updated_at,
        ];
    }

    private function exportExpenseRow(Expense $expense): array
    {
        return [
            'uuid' => $expense->uuid,
            'name' => $expense->name,
            'category' => $expense->category,
            'amount' => $expense->amount,
            'expense_date' => $expense->expense_date,
            'note' => $expense->note,
            'created_at' => $expense->created_at,
            'updated_at' => $expense->updated_at,
        ];
    }

    /**
     * Hapus data transaksi (Penjualan, Restock, Pengeluaran) pada bulan
     * tertentu. Data Produk (nama, harga, stok saat ini) TIDAK ikut dihapus,
     * dan stok produk saat ini TIDAK diubah oleh penghapusan riwayat ini.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'confirm' => 'required|in:1',
        ]);

        try {
            [$start, $end] = $this->monthRange($request->month);

            DB::transaction(function () use ($start, $end) {
                Sale::whereBetween('sold_at', [$start, $end])->delete();
                Restock::whereBetween('created_at', [$start, $end])->delete();
                Expense::whereBetween('expense_date', [$start, $end])->delete();
            });

            return redirect('/data?month='.$request->month)
                ->with('success', 'Data transaksi bulan tersebut berhasil dihapus. Data produk tidak terpengaruh.');
        } catch (Throwable $e) {
            Log::error('Hapus data bulanan gagal: '.$e->getMessage());

            return back()->withErrors([
                'month' => 'Gagal menghapus data bulan tersebut. Tidak ada data yang terhapus.',
            ]);
        }
    }

    /**
     * Form upload file backup untuk restore.
     */
    public function restoreForm()
    {
        return view('data.restore');
    }

    /**
     * Proses restore dari file backup JSON.
     *
     * PRODUCT MASTER STRATEGY (lihat resolveProductId()):
     * Setiap baris sales/restocks mencantumkan product_id ASLI, product_uuid
     * ASLI (format 2.1+), dan product_name ASLI. Saat restore, product_id
     * tersebut TIDAK langsung dipakai mentah-mentah (itu bisa salah kalau id
     * sudah dipakai ulang oleh produk lain di database tujuan). Urutan
     * pencarian:
     *   0. Baris punya product_uuid (format 2.1+) -> ini kunci PALING
     *      diutamakan karena uuid Product tidak pernah berubah dan tidak
     *      pernah dipakai ulang (lihat App\Models\Concerns\HasStableUuid).
     *      - Ada Product dengan uuid tsb di database tujuan -> pakai id-nya,
     *        TIDAK PEDULI apakah id atau namanya sudah berbeda dari saat
     *        backup dibuat (mis. produk sudah diedit namanya).
     *      - Tidak ada, tapi backup punya snapshot produk dengan uuid tsb
     *        -> buat ulang Product dari snapshot itu (lihat langkah 3).
     *      - Tidak ditemukan lewat uuid sama sekali (mis. snapshot produk
     *        tsb entah kenapa tidak ikut tersimpan) -> lanjut ke langkah 1
     *        sebagai fallback, bukan langsung gagal.
     *      Baris backup format lama (2.0 ke bawah) tidak punya product_uuid
     *      sama sekali, sehingga langkah ini otomatis dilewati dan restore
     *      langsung memakai strategi id+name di bawah seperti sebelumnya.
     *   1. Product dengan id tsb masih ada DAN namanya cocok  -> pakai id itu.
     *   2. Ada Product lain dengan nama yang sama persis (tanpa memandang
     *      besar/kecil huruf) -> pakai id produk tsb (menangani kasus id
     *      berubah/direstore ke database baru).
     *   3. Tidak ditemukan sama sekali, tapi backup punya snapshot produk
     *      tsb -> buat ulang Product dari snapshot (stock diset 0, BUKAN
     *      dari snapshot, supaya tidak mengubah stok gudang yang sedang
     *      berjalan saat ini — lihat aturan "delete history != reverse
     *      transaction" yang berlaku sama untuk restore transaksi lama).
     *      Product yang dibuat ulang memakai uuid ASLI dari snapshot (jika
     *      ada) dan created_at/updated_at ASLI dari snapshot (bukan waktu
     *      restore), supaya restore ulang pada file yang sama mengenalinya
     *      lewat uuid (bukan membuat duplikat) dan riwayat tanggalnya tetap
     *      akurat. Produk yang baru dibuat ulang ini dicatat supaya baris
     *      berikutnya yang merujuk produk yang sama tidak membuat duplikat.
     *   4. Jika tidak ada informasi produk sama sekali -> baris dilewati
     *      (skipped), transaksi tidak bisa dipulihkan dengan aman.
     *
     * DUPLICATE PROTECTION:
     * Baris backup format 2.0 punya `uuid` stabil yang dibuat sekali saat
     * baris tsb pertama kali disimpan (lihat App\Models\Concerns\HasStableUuid).
     * Restore mencocokkan berdasarkan uuid ini — jauh lebih aman daripada
     * mencocokkan kombinasi nilai data, karena dua transaksi ASLI yang
     * nilainya kebetulan sama (produk sama, jumlah sama, tanggal sama) tetap
     * punya uuid berbeda sehingga tetap tersimpan sebagai dua transaksi.
     * Untuk file backup format lama (1.0/1.1) yang belum punya uuid, restore
     * tetap memakai pencocokan kombinasi kolom seperti sebelumnya (fallback),
     * supaya backup lama tidak jadi tidak bisa dipulihkan.
     *
     * Tanggal transaksi asli (sold_at/expense_date) dan created_at/updated_at
     * asli dipertahankan apa adanya — TIDAK diganti dengan waktu restore.
     *
     * Seluruh proses dibungkus DB::transaction: jika terjadi error di
     * tengah jalan, SEMUA perubahan (termasuk Product yang sempat dibuat
     * ulang) dibatalkan otomatis — database tidak pernah tertinggal dalam
     * kondisi setengah restore.
     */
    public function restore(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:json,txt|max:10240',
        ]);

        try {
            $content = file_get_contents($request->file('backup_file')->getRealPath());
        } catch (Throwable $e) {
            return back()->withErrors([
                'backup_file' => 'File backup tidak dapat dibaca.',
            ]);
        }

        $data = json_decode((string) $content, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            return back()->withErrors([
                'backup_file' => 'File backup bukan JSON yang valid.',
            ]);
        }

        if (! isset($data['app']) || $data['app'] !== self::APP_NAME) {
            return back()->withErrors([
                'backup_file' => 'File backup tidak valid atau bukan berasal dari aplikasi '.self::APP_NAME.'.',
            ]);
        }

        if (! isset($data['backup_version']) || ! is_string($data['backup_version'])) {
            return back()->withErrors([
                'backup_file' => 'File backup tidak mencantumkan versi format yang dikenali.',
            ]);
        }

        $restored = [
            'sales' => 0,
            'restocks' => 0,
            'expenses' => 0,
            'products_recreated' => 0,
            'duplicate' => 0,
            'skipped' => 0,
        ];

        try {
            DB::transaction(function () use ($data, &$restored) {

                // Index snapshot produk dari backup (format 2.0+). Backup
                // format lama tidak punya field ini — tetap aman, resolver
                // di bawah hanya akan kehilangan opsi "buat ulang produk".
                $productSnapshotById = [];
                $productSnapshotByName = [];
                $productSnapshotByUuid = [];
                foreach (($data['products'] ?? []) as $snapshot) {
                    if (! is_array($snapshot) || ! isset($snapshot['name'])) {
                        continue;
                    }
                    if (isset($snapshot['id'])) {
                        $productSnapshotById[$snapshot['id']] = $snapshot;
                    }
                    $productSnapshotByName[mb_strtolower(trim((string) $snapshot['name']))] = $snapshot;

                    // uuid produk baru ada sejak format 2.1 — snapshot dari
                    // backup 2.0 ke bawah tidak punya field ini.
                    if (is_string($snapshot['uuid'] ?? null) && trim($snapshot['uuid']) !== '') {
                        $productSnapshotByUuid[trim($snapshot['uuid'])] = $snapshot;
                    }
                }

                // Peta produk yang sudah dibuat ulang selama proses restore
                // ini, supaya tidak dibuat dua kali untuk baris yang berbeda
                // tapi merujuk produk yang sama.
                $recreatedProducts = [];

                foreach (($data['sales'] ?? []) as $row) {
                    if (! $this->rowHasRequiredFields($row, ['product_id', 'quantity', 'price', 'total', 'sold_at'])) {
                        $restored['skipped']++;

                        continue;
                    }

                    $productId = $this->resolveProductId(
                        $row['product_id'],
                        $row['product_name'] ?? null,
                        $row['product_uuid'] ?? null,
                        $productSnapshotById,
                        $productSnapshotByName,
                        $productSnapshotByUuid,
                        $recreatedProducts,
                        $restored
                    );

                    if ($productId === null) {
                        $restored['skipped']++;

                        continue;
                    }

                    $createdAt = $this->normalizeDateTime($row['created_at'] ?? $row['sold_at']);
                    $updatedAt = $this->normalizeDateTime($row['updated_at'] ?? null) ?? $createdAt;

                    if (! $createdAt) {
                        $restored['skipped']++;

                        continue;
                    }

                    if ($this->saleAlreadyRestored($row, $productId, $createdAt)) {
                        $restored['duplicate']++;

                        continue;
                    }

                    $sale = new Sale([
                        'product_id' => $productId,
                        'quantity' => $row['quantity'],
                        'price' => $row['price'],
                        'total' => $row['total'],
                        'sold_at' => $row['sold_at'],
                    ]);
                    $sale->uuid = is_string($row['uuid'] ?? null) ? $row['uuid'] : null;
                    $sale->timestamps = false;
                    $sale->created_at = $createdAt;
                    $sale->updated_at = $updatedAt;
                    $sale->save();

                    $restored['sales']++;
                }

                foreach (($data['restocks'] ?? []) as $row) {
                    if (! $this->rowHasRequiredFields($row, ['product_id', 'quantity'])) {
                        $restored['skipped']++;

                        continue;
                    }

                    $productId = $this->resolveProductId(
                        $row['product_id'],
                        $row['product_name'] ?? null,
                        $row['product_uuid'] ?? null,
                        $productSnapshotById,
                        $productSnapshotByName,
                        $productSnapshotByUuid,
                        $recreatedProducts,
                        $restored
                    );

                    if ($productId === null) {
                        $restored['skipped']++;

                        continue;
                    }

                    // Restock tidak punya kolom tanggal transaksi sendiri —
                    // created_at ADALAH tanggal transaksinya. Wajib dipertahankan.
                    $createdAt = $this->normalizeDateTime($row['created_at'] ?? null);

                    if (! $createdAt) {
                        $restored['skipped']++;

                        continue;
                    }

                    $updatedAt = $this->normalizeDateTime($row['updated_at'] ?? null) ?? $createdAt;

                    if ($this->restockAlreadyRestored($row, $productId, $createdAt)) {
                        $restored['duplicate']++;

                        continue;
                    }

                    $restock = new Restock([
                        'product_id' => $productId,
                        'quantity' => $row['quantity'],
                    ]);
                    $restock->uuid = is_string($row['uuid'] ?? null) ? $row['uuid'] : null;
                    $restock->timestamps = false;
                    $restock->created_at = $createdAt;
                    $restock->updated_at = $updatedAt;
                    $restock->save();

                    // Catatan desain: restore riwayat TIDAK mengubah stok produk
                    // saat ini, konsisten dengan hapus riwayat yang juga tidak
                    // mengubah stok (lihat DataController::destroy &
                    // ProductController::bulkDestroyRestocks).

                    $restored['restocks']++;
                }

                foreach (($data['expenses'] ?? []) as $row) {
                    if (! $this->rowHasRequiredFields($row, ['name', 'category', 'amount', 'expense_date'])) {
                        $restored['skipped']++;

                        continue;
                    }

                    $note = $row['note'] ?? null;
                    $createdAt = $this->normalizeDateTime($row['created_at'] ?? $row['expense_date']);
                    $updatedAt = $this->normalizeDateTime($row['updated_at'] ?? null) ?? $createdAt;

                    if (! $createdAt) {
                        $restored['skipped']++;

                        continue;
                    }

                    if ($this->expenseAlreadyRestored($row, $note, $createdAt)) {
                        $restored['duplicate']++;

                        continue;
                    }

                    $expense = new Expense([
                        'name' => $row['name'],
                        'category' => $row['category'],
                        'amount' => $row['amount'],
                        'expense_date' => $row['expense_date'],
                        'note' => $note,
                    ]);
                    $expense->uuid = is_string($row['uuid'] ?? null) ? $row['uuid'] : null;
                    $expense->timestamps = false;
                    $expense->created_at = $createdAt;
                    $expense->updated_at = $updatedAt;
                    $expense->save();

                    $restored['expenses']++;
                }
            });
        } catch (Throwable $e) {
            Log::error('Restore data gagal: '.$e->getMessage());

            return back()->withErrors([
                'backup_file' => 'Restore gagal diproses. Tidak ada data yang diubah (transaksi dibatalkan otomatis).',
            ]);
        }

        return redirect('/data')->with(
            'success',
            "Restore selesai. Penjualan: {$restored['sales']}, Restock: {$restored['restocks']}, "
            ."Pengeluaran: {$restored['expenses']}, produk dibuat ulang dari backup: {$restored['products_recreated']}, "
            ."sudah ada sebelumnya (dilewati): {$restored['duplicate']}, "
            ."tidak valid (dilewati): {$restored['skipped']}."
        );
    }

    /**
     * Tentukan product_id yang aman dipakai di database TUJUAN untuk sebuah
     * baris backup, dengan strategi bertingkat. Lihat docblock restore().
     *
     * @param  mixed  $originalProductUuid  uuid Product ASLI dari baris backup (format 2.1+), atau null untuk backup lama
     * @param  array<int|string, array>  $productSnapshotById
     * @param  array<string, array>  $productSnapshotByName  kunci: nama lowercase+trim
     * @param  array<string, array>  $productSnapshotByUuid  kunci: uuid produk
     * @param  array<string, int>  $recreatedProducts  kunci: "uuid:<uuid>", "id:<id>", atau "name:<namalowercase>", diisi/dibaca oleh method ini
     */
    private function resolveProductId(
        mixed $originalProductId,
        mixed $originalProductName,
        mixed $originalProductUuid,
        array $productSnapshotById,
        array $productSnapshotByName,
        array $productSnapshotByUuid,
        array &$recreatedProducts,
        array &$restored
    ): ?int {
        $normalizedName = is_string($originalProductName) && trim($originalProductName) !== ''
            ? mb_strtolower(trim($originalProductName))
            : null;

        $normalizedUuid = is_string($originalProductUuid) && trim($originalProductUuid) !== ''
            ? trim($originalProductUuid)
            : null;

        // 0) STRATEGI UTAMA (format 2.1+): cocokkan lewat Product uuid.
        //    Uuid Product tidak pernah berubah/dipakai ulang, jadi ini aman
        //    dipakai walau id sudah berubah/dipakai ulang ATAU nama produk
        //    sudah diedit sejak backup dibuat. Baris backup format lama
        //    tidak punya product_uuid ($normalizedUuid akan null), sehingga
        //    langkah ini otomatis dilewati dan lanjut ke strategi id+name
        //    di bawah seperti sebelumnya (backward compatible).
        if ($normalizedUuid !== null) {
            $recreatedKey = 'uuid:'.$normalizedUuid;
            if (isset($recreatedProducts[$recreatedKey])) {
                return $recreatedProducts[$recreatedKey];
            }

            $existingByUuid = Product::where('uuid', $normalizedUuid)->first();
            if ($existingByUuid) {
                return $existingByUuid->id;
            }

            $uuidSnapshot = $productSnapshotByUuid[$normalizedUuid] ?? null;
            if ($uuidSnapshot && ! empty($uuidSnapshot['name'])) {
                $newProduct = $this->recreateProductFromSnapshot($uuidSnapshot, $normalizedUuid);
                $recreatedProducts[$recreatedKey] = $newProduct->id;
                $restored['products_recreated']++;

                return $newProduct->id;
            }

            // Tidak ditemukan lewat uuid sama sekali (mis. snapshot produk
            // tsb entah kenapa tidak ikut tersimpan) -> lanjut ke fallback
            // di bawah, bukan langsung gagal.
        }

        // 1) Product dengan id yang sama masih ada. Hanya dipakai jika nama
        //    tidak diketahui (backup lama) ATAU namanya memang masih cocok —
        //    supaya tidak salah menempel ke produk lain yang kebetulan
        //    memakai id lama yang sama.
        $existingById = Product::find($originalProductId);
        if ($existingById) {
            if ($normalizedName === null || mb_strtolower(trim($existingById->name)) === $normalizedName) {
                return $existingById->id;
            }
        }

        // 2) Cari produk lain dengan nama yang sama persis.
        if ($normalizedName !== null) {
            $recreatedKey = 'name:'.$normalizedName;
            if (isset($recreatedProducts[$recreatedKey])) {
                return $recreatedProducts[$recreatedKey];
            }

            $existingByName = Product::whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])->first();
            if ($existingByName) {
                return $existingByName->id;
            }
        }

        // 3) Buat ulang Product dari snapshot backup jika tersedia.
        $snapshot = $productSnapshotById[$originalProductId] ?? null;
        if (! $snapshot && $normalizedName !== null) {
            $snapshot = $productSnapshotByName[$normalizedName] ?? null;
        }

        if ($snapshot && ! empty($snapshot['name'])) {
            $snapshotUuid = is_string($snapshot['uuid'] ?? null) && trim($snapshot['uuid']) !== ''
                ? trim($snapshot['uuid'])
                : null;
            $snapshotNameKey = mb_strtolower(trim((string) $snapshot['name']));
            $recreatedKey = $snapshotUuid !== null ? ('uuid:'.$snapshotUuid) : ('name:'.$snapshotNameKey);

            if (isset($recreatedProducts[$recreatedKey])) {
                return $recreatedProducts[$recreatedKey];
            }

            $newProduct = $this->recreateProductFromSnapshot($snapshot, $snapshotUuid);
            $recreatedProducts[$recreatedKey] = $newProduct->id;
            $restored['products_recreated']++;

            return $newProduct->id;
        }

        // 4) Tidak ada cara aman untuk menentukan produknya.
        return null;
    }

    /**
     * Buat ulang Product dari snapshot backup.
     *
     * - Stock SENGAJA diset 0, bukan dari snapshot: memulihkan transaksi
     *   lama tidak boleh mengubah stok gudang yang sedang berjalan saat ini
     *   (aturan yang sama seperti hapus riwayat tidak mengubah stok).
     * - uuid diisi dari uuid ASLI snapshot (jika ada) supaya Product yang
     *   dibuat ulang ini tetap punya identitas yang sama persis dengan
     *   Product aslinya — restore ulang pada file backup yang sama nantinya
     *   akan mengenalinya lewat uuid ini (bukan membuat produk duplikat
     *   kedua kalinya). Jika snapshot tidak punya uuid (backup format lama),
     *   trait HasStableUuid akan membuatkan uuid baru seperti biasa.
     * - created_at/updated_at diisi dari snapshot ASLI (bukan waktu
     *   restore) supaya tanggal Product dibuat tetap akurat, konsisten
     *   dengan cara sales/restocks/expenses mempertahankan timestamp asli.
     */
    private function recreateProductFromSnapshot(array $snapshot, ?string $forceUuid): Product
    {
        $createdAt = $this->normalizeDateTime($snapshot['created_at'] ?? null) ?? now()->format('Y-m-d H:i:s');
        $updatedAt = $this->normalizeDateTime($snapshot['updated_at'] ?? null) ?? $createdAt;

        $newProduct = new Product([
            'name' => $snapshot['name'],
            'price' => $snapshot['price'] ?? 0,
            'stock' => 0,
        ]);
        $newProduct->uuid = $forceUuid;
        $newProduct->timestamps = false;
        $newProduct->created_at = $createdAt;
        $newProduct->updated_at = $updatedAt;
        $newProduct->save();

        return $newProduct;
    }

    private function saleAlreadyRestored(array $row, int $productId, string $createdAt): bool
    {
        if (is_string($row['uuid'] ?? null) && $row['uuid'] !== '') {
            return Sale::where('uuid', $row['uuid'])->exists();
        }

        // Fallback untuk file backup lama (format 1.0/1.1) tanpa uuid.
        return Sale::where('product_id', $productId)
            ->where('quantity', $row['quantity'])
            ->where('price', $row['price'])
            ->where('total', $row['total'])
            ->where('sold_at', $row['sold_at'])
            ->where('created_at', $createdAt)
            ->exists();
    }

    private function restockAlreadyRestored(array $row, int $productId, string $createdAt): bool
    {
        if (is_string($row['uuid'] ?? null) && $row['uuid'] !== '') {
            return Restock::where('uuid', $row['uuid'])->exists();
        }

        return Restock::where('product_id', $productId)
            ->where('quantity', $row['quantity'])
            ->where('created_at', $createdAt)
            ->exists();
    }

    private function expenseAlreadyRestored(array $row, mixed $note, string $createdAt): bool
    {
        if (is_string($row['uuid'] ?? null) && $row['uuid'] !== '') {
            return Expense::where('uuid', $row['uuid'])->exists();
        }

        return Expense::where('name', $row['name'])
            ->where('category', $row['category'])
            ->where('amount', $row['amount'])
            ->where('expense_date', $row['expense_date'])
            ->where('note', $note)
            ->where('created_at', $createdAt)
            ->exists();
    }

    /**
     * Pastikan sebuah baris backup punya field wajib sebelum dipakai,
     * supaya file backup yang rusak/tidak lengkap tidak menyebabkan error
     * dan hanya baris tersebut yang dilewati (bukan seluruh proses restore).
     */
    private function rowHasRequiredFields(mixed $row, array $fields): bool
    {
        if (! is_array($row)) {
            return false;
        }

        foreach ($fields as $field) {
            if (! array_key_exists($field, $row) || $row[$field] === null || $row[$field] === '') {
                return false;
            }
        }

        return true;
    }
}
