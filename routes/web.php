<?php

use App\Platform\Controllers\Admin\AuditLogController;
use App\Platform\Controllers\Admin\ClientController;
use App\Platform\Controllers\Admin\ContactController;
use App\Platform\Controllers\Admin\DashboardController;
use App\Platform\Controllers\Admin\ProfileController;
use App\Platform\Controllers\Admin\UserController;
use App\Platform\Controllers\Auth\AuthenticatedSessionController;
use App\Platform\Controllers\Auth\ChangePasswordController;
use App\Platform\Controllers\Auth\ForgotPasswordController;
use App\Platform\Controllers\Auth\NewPasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'admin.dashboard' : 'login'));

/*
|--------------------------------------------------------------------------
| Public routes — the declared exception list
|--------------------------------------------------------------------------
| Every credential endpoint goes through the named "login" rate limiter.
*/

Route::middleware('guest')->group(function () {
    Route::get('admin/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('admin/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');

    Route::get('admin/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('admin/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:login')
        ->name('password.email');

    Route::get('admin/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('admin/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:login')
        ->name('password.store');
});

/*
|--------------------------------------------------------------------------
| Authenticated — everything else
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::post('admin/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('backoffice')->group(function () {
        Route::get('admin/first-access', [ChangePasswordController::class, 'edit'])->name('password.change');
        Route::put('admin/first-access', [ChangePasswordController::class, 'update'])
            ->middleware('throttle:login')
            ->name('password.change.update');
    });
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'backoffice', 'password.current'])
    ->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::resource('users', UserController::class)->except(['create', 'edit', 'show']);
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::post('users/{user}/reactivate', [UserController::class, 'reactivate'])->name('users.reactivate');

        Route::resource('clients', ClientController::class)->except(['create', 'edit']);
        Route::post('clients/{client}/deactivate', [ClientController::class, 'deactivate'])->name('clients.deactivate');
        Route::post('clients/{client}/reactivate', [ClientController::class, 'reactivate'])->name('clients.reactivate');

        Route::get('contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::post('clients/{client}/contacts', [ContactController::class, 'store'])->name('clients.contacts.store');
        Route::put('contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
        Route::delete('contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
        Route::post('contacts/{contact}/deactivate', [ContactController::class, 'deactivate'])->name('contacts.deactivate');
        Route::post('contacts/{contact}/reactivate', [ContactController::class, 'reactivate'])->name('contacts.reactivate');
        Route::post('contacts/{contact}/anonymize', [ContactController::class, 'anonymize'])->name('contacts.anonymize');

        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });
