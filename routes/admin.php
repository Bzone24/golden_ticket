<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DrawController;
use App\Http\Controllers\Admin\ShopKeeperController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::get('login', 'showLogin')->name('admin.login');
    Route::post('login', 'login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/', fn () => redirect()->route('admin.dashboard'));

    Route::controller(DashboardController::class)->prefix('dashboard')->group(function () {
        Route::get('/', 'index')->name('admin.dashboard');
        Route::get('cross-abc', 'crossAbc')->name('admin.dashboard.cross.abc');
        Route::get('get-cross-ac', 'getCrossAcList')->name('admin.dashboard.cross.get.ac');
        Route::get('get-cross-bc', 'getCrossBcList')->name('admin.dashboard.cross.get.bc');
        Route::get('total_qty_details/{drawDetail}/{user_id?}', 'totalQtyDetailList')->name('admin.dashboard.total.qty.details.list');
    });

    // Shopkeeper's Routes
    Route::controller(ShopKeeperController::class)->prefix('shopkeepers')->group(function () {
        Route::get('/user/{user_id?}', 'index')->name('admin.shopkeepers');
        Route::get('shopkeeper-form/{user_id?}', 'addEditShopKeeper')->name('admin.shopkeeper_form');
        Route::get('shopkeeper-details', 'view')->name('admin.shopkeeper.view');
        Route::get('shopkeeper-drawlist/{user_id}', 'getShopKeeperDrawList')->name('admin.shopkeeper.drawlist');
        Route::get('get-user/{user_id}', 'getUserDetailsStats')->name('admin.shopkeeper.get.user.details');
    });

    // Draw Routes
    Route::controller(DrawController::class)->prefix('draw')->group(function () {
        Route::get('/', 'index')->name('admin.draw');
        Route::get('add-draw', 'addDraw')->name('admin.add.draw');
        Route::get('draw-details-list/{drawDetail}/{user_id?}', 'drawDetails')->name('admin.draw.detail.list');
        Route::get('draw-details-of-shopkeeper/{drawDetail}/{user?}', 'shopKeeperDrawDetails')->name('admin.draw.details.shopkeeper');
        Route::get('draw-number-details-list', 'numberList')->name('admin.draw.number.details.list');
        Route::get('draw-tikcet-details-list/{drawDetail}/{ticket}/{user}', 'ticketDetailsList')->name('admin.draw.ticke.details.list');
        Route::get('cross-table/{drawDetail}/{ticket}/{user}', 'getCrossDataTable')->name('admin.draw.cross.ticket.details.list');
    });

    // Logout Admin
       Route::post('logout', function () {
        Auth::guard('admin')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('admin.login');
    })->name('admin.logout');
});
