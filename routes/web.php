<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\OrganisationController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    return redirect('/login');
});

// Authentication routes (Laravel Breeze/Fortify)
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);

    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');
    Route::post('/register', [\App\Http\Controllers\Auth\RegisterController::class, 'register']);

    Route::get('/forgot-password', function () {
        return view('auth.forgot-password');
    })->name('password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', function () {
        return view('auth.reset-password');
    })->name('password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\Auth\NewPasswordController::class, 'store'])->name('password.update');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/switch', [DashboardController::class, 'switchOrganisation'])->name('dashboard.switch');

    // Organisations
    Route::resource('organisations', OrganisationController::class);

    // Bank Accounts (nested under organisations)
    Route::get('organisations/{organisation}/bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts.index');
    Route::get('organisations/{organisation}/bank-accounts/create', [BankAccountController::class, 'create'])->name('bank-accounts.create');
    Route::post('organisations/{organisation}/bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');
    Route::get('organisations/{organisation}/bank-accounts/{bankAccount}', [BankAccountController::class, 'show'])->name('bank-accounts.show');
    Route::get('organisations/{organisation}/bank-accounts/{bankAccount}/edit', [BankAccountController::class, 'edit'])->name('bank-accounts.edit');
    Route::put('organisations/{organisation}/bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->name('bank-accounts.update');
    Route::delete('organisations/{organisation}/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');

    // Imports (nested under organisations and bank accounts)
    Route::get('organisations/{organisation}/imports', [ImportController::class, 'index'])->name('imports.index');
    Route::get('organisations/{organisation}/bank-accounts/{bankAccount}/import', [ImportController::class, 'create'])->name('imports.create');
    Route::post('organisations/{organisation}/bank-accounts/{bankAccount}/import', [ImportController::class, 'store'])->name('imports.store');
    Route::get('organisations/{organisation}/imports/{import}', [ImportController::class, 'show'])->name('imports.show');

    // Predictions (nested under organisations)
    Route::get('organisations/{organisation}/predictions', [PredictionController::class, 'index'])->name('predictions.index');
    Route::get('organisations/{organisation}/predictions/create', [PredictionController::class, 'create'])->name('predictions.create');
    Route::post('organisations/{organisation}/predictions', [PredictionController::class, 'store'])->name('predictions.store');
    Route::get('organisations/{organisation}/predictions/{prediction}', [PredictionController::class, 'show'])->name('predictions.show');
    Route::delete('organisations/{organisation}/predictions/{prediction}', [PredictionController::class, 'destroy'])->name('predictions.destroy');

    // Analytics
    Route::get('organisations/{organisation}/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // Transactions
    Route::post('organisations/{organisation}/bank-accounts/{bankAccount}/transactions/{transaction}/toggle-exclusion', [TransactionController::class, 'toggleExclusion'])->name('transactions.toggle-exclusion');
});

// Logout route
Route::post('/logout', function () {
    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/');
})->name('logout')->middleware('auth');
