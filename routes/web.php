<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\WalletController as AdminWalletController;
use App\Http\Controllers\User\WalletController as UserWalletController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

Route::controller(AuthController::class)->group(function () {
    Route::get('login', 'showLoginForm')->name('login');
    Route::post('login', 'login');
});

// Authentication Routes
Route::middleware('auth:web')->group(function () {

    Route::controller(DashboardController::class)->prefix('dashboard')->group(function () {
        Route::get('/', 'index')->name('dashboard');
        Route::get('add-ticket', 'addTicket')->name('ticket.add');
        // Route::get('/option-list', 'optionList')->name('dashboard.option.list');
        Route::get('/draw-details-list', 'drawDetailsList')->name('dashboard.draw.details.list');
        Route::get('/total-qty-detail-list/{drawDetail}', 'totalQtyDetailList')->name('dashboard.draw.total.qty.list.details');
        Route::get('cross-abc-detail-list', 'crossAbcList')->name('dashboard.draw.cross.abc.details.list');
        Route::get('cross-ab-list', 'getCrossAcList')->name('dashboard.draw.cross.ac.list');
        Route::get('cross-bc-list', 'getCrossBcList')->name('dashboard.draw.cross.bc.list');
    });

    // Logout User
    Route::get('logout', function () {
        Auth::logout();
        // request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    })->name('logout');
});

/*
|--------------------------------------------------------------------------
| Admin: Wallet routes
|--------------------------------------------------------------------------
|
| Routes for admin to manage wallets and transactions.
| Adjust middleware 'admin' to match your actual admin middleware name
| (e.g. 'is_admin', 'role:admin', 'can:access-admin') if required.
|
*/
Route::prefix('admin')->middleware(['web','auth'])->group(function () {
    // Admin wallet pages (views handled by controller)
    Route::get('wallet/transactions', [AdminWalletController::class, 'transactionsPage'])
        ->name('admin.wallet.transactions'); // renders admin.wallet.transactions view

    Route::get('wallet/transfer', [AdminWalletController::class, 'transferPage'])
        ->name('admin.wallet.transfer'); // renders admin.wallet.transfer view

    // Plain transfer form + post handler (keeps your prior plain form routes)
    Route::get('wallet/transfer/plain', [AdminWalletController::class, 'plainForm'])
        ->name('admin.wallet.transfer.plain');

    Route::post('wallet/transfer/plain', [AdminWalletController::class, 'plainTransfer'])
        ->name('admin.wallet.transfer.plain.post');

    // API-like endpoints used by admin UI (optional)
    Route::post('wallet/transfer', [AdminWalletController::class, 'transfer'])
        ->name('admin.wallet.transfer.post');
});

/*
|--------------------------------------------------------------------------
| User: Wallet routes
|--------------------------------------------------------------------------
|
| Routes for regular authenticated users to view their own wallet,
| list their transactions, and request withdrawals/transfers.
|
*/
// Route::prefix('wallet')->middleware(['web', 'auth'])->group(function () {
//     Route::get('/', [UserWalletController::class, 'index'])
//         ->name('user.wallet.index');

//     Route::get('transactions', [UserWalletController::class, 'transactions'])
//         ->name('user.wallet.transactions');

//     Route::get('transfer', [UserWalletController::class, 'showTransferForm'])
//         ->name('user.wallet.transfer');

//     Route::post('transfer', [UserWalletController::class, 'submitTransfer'])
//         ->name('user.wallet.transfer.post');
// });

Route::middleware(['auth'])->prefix('wallet')->name('user.wallet.')->group(function () {
    Route::get('transactions', [\App\Http\Controllers\User\WalletController::class, 'transactions'])->name('transactions');
    Route::get('transfer', [\App\Http\Controllers\User\WalletController::class, 'showTransferForm'])->name('transfer');
    Route::post('transfer', [\App\Http\Controllers\User\WalletController::class, 'submitTransfer'])->name('transfer.post');
});

// ---------- ADD these alias routes for clarity: shopkeeper.* ----------
Route::middleware(['auth'])->prefix('shopkeeper/wallet')->name('shopkeeper.wallet.')->group(function () {
    // note: URL is shopkeeper/wallet/transfer — different URL but same controller method
    Route::get('transactions', [UserWalletController::class, 'transactions'])->name('transactions');
    Route::get('transfer', [UserWalletController::class, 'showTransferForm'])->name('transfer');
    Route::post('transfer', [UserWalletController::class, 'submitTransfer'])->name('transfer.post');
});

/*
|---------------------------------------------------------------------------
| Fallback: If you do not have an 'admin' middleware registered, and you
| prefer to allow admin routes to work with just 'auth', you can replace
| the admin middleware group above with the following (uncomment if needed):
|
| Route::prefix('admin')->middleware(['web','auth'])->group(function () {
|     // ...same admin routes as above...
| });
|
| But it's recommended to protect admin pages with a dedicated middleware.
|---------------------------------------------------------------------------
*/

