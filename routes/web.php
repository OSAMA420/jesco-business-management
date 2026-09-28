<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect(auth()->check() ? route('dashboard') : route('login'));
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:products')->group(function () {
        Route::resource('products', ProductController::class);
    });

    Route::middleware('role:inventory')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/movements', [InventoryController::class, 'storeMovement'])->name('inventory.movements.store');
    });

    Route::middleware('role:customers')->group(function () {
        Route::resource('customers', CustomerController::class);
        Route::post('/customers/{customer}/payments', [CustomerController::class, 'storePayment'])->name('customers.payments.store');
    });

    Route::middleware('role:orders')->group(function () {
        Route::resource('orders', OrderController::class);
        Route::post('/orders/{order}/payments', [OrderController::class, 'storePayment'])->name('orders.payments.store');
        Route::delete('/orders/{order}/payments/{payment}', [OrderController::class, 'destroyPayment'])->name('orders.payments.destroy');
    });

    Route::middleware('role:purchases')->group(function () {
        Route::resource('suppliers', SupplierController::class);
        Route::post('/suppliers/{supplier}/payments', [SupplierController::class, 'storePayment'])->name('suppliers.payments.store');
        Route::resource('purchases', PurchaseController::class);
        Route::post('/purchases/{purchase}/payments', [PurchaseController::class, 'storePayment'])->name('purchases.payments.store');
        Route::delete('/purchases/{purchase}/payments/{payment}', [PurchaseController::class, 'destroyPayment'])->name('purchases.payments.destroy');
    });

    Route::middleware('role:finance')->group(function () {
        Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
        Route::post('/finance/expenses', [FinanceController::class, 'storeExpense'])->name('finance.expenses.store');
        Route::put('/finance/expenses/{expense}', [FinanceController::class, 'updateExpense'])->name('finance.expenses.update');
        Route::delete('/finance/expenses/{expense}', [FinanceController::class, 'destroyExpense'])->name('finance.expenses.destroy');
    });

    Route::middleware('role:reports')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}', [ReportController::class, 'show'])->whereIn('report', array_keys(ReportController::REPORTS))->name('reports.show');
        Route::get('/reports/{report}/csv', [ReportController::class, 'csv'])->whereIn('report', array_keys(ReportController::REPORTS))->name('reports.csv');
    });

    Route::middleware('role:users')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
