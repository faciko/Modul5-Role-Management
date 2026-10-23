<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartSimulationController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

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
