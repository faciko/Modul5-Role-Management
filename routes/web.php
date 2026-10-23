<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthMeController;
use App\Http\Controllers\CartSimulationController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

// Katalog: boleh dilihat semua role (termasuk guest).
Route::get('/shop', [DemoController::class, 'shop'])->name('shop');
Route::get('/api/products', [DemoController::class, 'products'])->name('api.products');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // Login berbasis JSON untuk halaman cart stateless (W06).
    Route::post('/api/login', [AuthController::class, 'apiLogin'])->name('api.login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Ringkasan identitas + ability user yang sedang login.
    Route::get('/api/me', AuthMeController::class)->name('api.me');
});

// Lihat stok: staff + admin.
Route::get('/staff/stock',
    [DemoController::class, 'stock'])
    ->middleware(['auth', 'can:adjust-stock'])
    ->name('staff.stock');

// Kelola katalog: admin.
Route::get('/admin/products',
    [DemoController::class, 'manageProducts'])
    ->middleware(['auth', 'can:manage-products'])
    ->name('admin.products');

// Role management: hanya admin (Gate manage-roles).
Route::get('/admin/users',
    [RoleController::class, 'index'])
    ->middleware(['auth', 'can:manage-roles'])
    ->name('admin.users.index');

Route::patch('/admin/users/{user}/role',
    [RoleController::class, 'update'])
    ->middleware(['auth', 'can:manage-roles'])
    ->name('admin.users.role.update');

// Simulasi W06 stateless cart: cart dari request, akses dari session + Gate.
Route::post('/cart/add',
    [CartSimulationController::class, 'add'])
    ->middleware(['auth', 'can:use-cart'])
    ->name('cart.add');
