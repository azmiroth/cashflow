<?php
/**
 * Web Routes
 * Version: 1.0
 * Created: 2026-01-13 20:30 GMT+11
 */

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrganisationController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PredictionController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return redirect('/login');
});

// Authentication routes (Laravel default)
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login'])->name('login.post');

    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');

    Route::post('/register', [\App\Http\Controllers\Auth\RegisterController::class, 'register'])->name('register.post');

    Route::get('/forgot-password', function () {
        return view('auth.forgot-password');
    })->name('password.request');

    Route::post('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', function ($token) {
        return view('auth.reset-password', ['token' => $token]);
    })->name('password.reset');

    Route::post('/reset-password', [\App\Http\Controllers\Auth\NewPasswordController::class, 'store'])->name('password.store');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/switch-organisation', [DashboardController::class, 'switchOrganisation'])->name('dashboard.switch');

    // Organisations
    Route::resource('organisations', OrganisationController::class);

    // Bank Accounts
    Route::resource('bank-accounts', BankAccountController::class);

    // Predictions
    Route::resource('predictions', PredictionController::class);

    // Imports
    Route::get('/bank-accounts/{bankAccount}/import', [ImportController::class, 'create'])->name('imports.create');
    Route::post('/bank-accounts/{bankAccount}/import', [ImportController::class, 'store'])->name('imports.store');
    Route::get('/bank-accounts/{bankAccount}/import-history', [ImportController::class, 'history'])->name('imports.history');

    // Logout
    Route::post('/logout', [\App\Http\Controllers\Auth\LogoutController::class, 'logout'])->name('logout');
});
