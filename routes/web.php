<?php

use App\Http\Controllers\BackupArchiveController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', [HomeController::class, 'index']);

Route::get('/products', [ProductController::class, 'index']);

Route::get('/products/create', [ProductController::class, 'create']);

Route::post('/products', [ProductController::class, 'store']);

Route::get('/products/{product}/edit', [ProductController::class, 'edit']);

Route::put('/products/{product}', [ProductController::class, 'update']);

Route::delete('/products/{product}', [ProductController::class, 'destroy']);

Route::get('/restocks/create', [ProductController::class, 'createRestock']);

Route::post('/restocks', [ProductController::class, 'storeRestock']);

Route::get('/restocks', [ProductController::class, 'restocks']);

Route::delete('/restocks/{restock}', [ProductController::class, 'destroyRestock']);

Route::delete('/restocks', [ProductController::class, 'bulkDestroyRestocks']);

Route::get('/sales', [SaleController::class, 'index']);

Route::get('/sales/create', [SaleController::class, 'create']);

Route::post('/sales', [SaleController::class, 'store']);

Route::put('/sales/{sale}', [SaleController::class, 'update']);

Route::delete('/sales/{sale}', [SaleController::class, 'destroy']);

Route::get('/expenses', [ExpenseController::class, 'index']);

Route::get('/expenses/create', [ExpenseController::class, 'create']);

Route::post('/expenses', [ExpenseController::class, 'store']);

Route::put('/expenses/{expense}', [ExpenseController::class, 'update']);

Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy']);

Route::get('/data', [DataController::class, 'index']);

Route::post('/data/backup', [DataController::class, 'backup']);

Route::delete('/data', [DataController::class, 'destroy']);

Route::get('/data/restore', [DataController::class, 'restoreForm']);

Route::post('/data/restore', [DataController::class, 'restore']);

Route::get('/backups', [BackupArchiveController::class, 'index'])
    ->name('backups.index');

Route::get('/backups/{month}/share/{type}', [BackupArchiveController::class, 'share'])
    ->where([
        'month' => '\d{4}-\d{2}',
        'type' => 'json|txt',
    ])
    ->name('backups.share');

    Route::delete('/backups/{month}', [BackupArchiveController::class, 'delete'])
    ->where('month', '\d{4}-\d{2}')
    ->name('backups.delete');

    Route::get('/debug-backup-schema', function () {
    return response()->json([
        'backup_archives_exists' => Schema::hasTable('backup_archives'),
        'deleted_transaction_marker_exists' =>
            Schema::hasColumn(
                'backup_archives',
                'deleted_transaction_marker'
            ),
    ]);
});

Route::post('/expenses/{expense}/update', [ExpenseController::class, 'update'])
    ->name('expenses.update');