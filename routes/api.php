<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\REST\V1 as RESTV1;
use App\Http\Controllers\REST\Errors;

## Base API Route
Route::post('/', [RESTV1\Home::class, 'index']);

## Authorization
Route::prefix('auth')->group(function () {
    Route::post('account', [RESTV1\Auth\Account::class, 'index']);
});

Route::prefix('sales')->group(function () {
    Route::get('/', [RESTV1\Sales\Get::class, 'index']);
    Route::get('{id}', [RESTV1\Sales\Get::class, 'index']);
    Route::post('/', [RESTV1\Sales\Insert::class, 'index']);
});

Route::prefix('warranty-check')->group(function () {
    Route::get('/', [RESTV1\WarrantyCheck\Get::class, 'index']);
    Route::get('{id}', [RESTV1\WarrantyCheck\Get::class, 'index']);
});

## Manage
Route::prefix('manage')->group(function () {

    Route::get('/summary', [RESTV1\Manage\Summary\Get::class, 'index']);

    Route::prefix('accounts')->group(function () {
        Route::get('/', [RESTV1\Manage\Accounts\Get::class, 'index']);
        Route::get('{id}', [RESTV1\Manage\Accounts\Get::class, 'index']);
        Route::post('/', [RESTV1\Manage\Accounts\Insert::class, 'index']);
        Route::put('{id}', [RESTV1\Manage\Accounts\Update::class, 'index']);
        Route::delete('{id}', [RESTV1\Manage\Accounts\Delete::class, 'index']);
    });

    Route::prefix('product-categories')->group(function () {
        Route::get('/', [RESTV1\Manage\ProductCategories\Get::class, 'index']);
        Route::get('{id}', [RESTV1\Manage\ProductCategories\Get::class, 'index']);
        Route::post('/', [RESTV1\Manage\ProductCategories\Insert::class, 'index']);
        Route::put('{id}', [RESTV1\Manage\ProductCategories\Update::class, 'index']);
        Route::delete('{id}', [RESTV1\Manage\ProductCategories\Delete::class, 'index']);
    });

    Route::prefix('products')->group(function () {
        Route::get('/', [RESTV1\Manage\Products\Get::class, 'index']);
        Route::get('{id}', [RESTV1\Manage\Products\Get::class, 'index']);
        Route::post('/', [RESTV1\Manage\Products\Insert::class, 'index']);
        Route::put('{id}', [RESTV1\Manage\Products\Update::class, 'index']);
        Route::delete('{id}', [RESTV1\Manage\Products\Delete::class, 'index']);
    });

    Route::prefix('warranties')->group(function () {
        Route::get('/', [RESTV1\Manage\Warranties\Get::class, 'index']);
        Route::get('{id}', [RESTV1\Manage\Warranties\Get::class, 'index']);
        Route::put('{id}', [RESTV1\Manage\Warranties\Update::class, 'index']);
    });
});


Route::get('setup-application/a1b2c3d4e5f6g7h8i9j0', function () {
    // Cek apakah ini di lingkungan produksi untuk keamanan tambahan
    if (app()->environment('production')) {
        // Jalankan perintah storage:link
        Artisan::call('storage:link');

        return 'Application setup complete: Storage link created.';
    } else {
        return 'This endpoint is only for production environment.';
    }
});

Route::get('setup-application/q3rn4vt3w923r2u', function () {
    // Cek apakah ini di lingkungan produksi untuk keamanan tambahan
    if (app()->environment('production')) {
        // Jalankan perintah migrate
        Artisan::call('migrate');

        return 'Application setup complete: Migration success.';
    } else {
        return 'This endpoint is only for production environment.';
    }
});

Route::get('setup-application/q23p09guj3v03u983', function () {
    // Cek apakah ini di lingkungan produksi untuk keamanan tambahan
    if (app()->environment('production')) {
        // Jalankan perintah route:cache
        Artisan::call('route:cache');

        return 'Application setup complete: Routes cached.';
    } else {
        return 'This endpoint is only for production environment.';
    }
});

Route::get('setup-application/5230nb42th09fh3', function () {
    // Cek apakah ini di lingkungan produksi untuk keamanan tambahan
    if (app()->environment('production')) {
        phpinfo();
        echo "<h3>PHP_INT_SIZE: " . PHP_INT_SIZE . "</h3>";
        echo "<h3>PHP_INT_MAX: " . PHP_INT_MAX . "</h3>";
        echo "<h3>Test 10000000000: ";
        var_dump(10000000000);
        echo "</h3>";
    } else {
        return 'This endpoint is only for production environment.';
    }
});
