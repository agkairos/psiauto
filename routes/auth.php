<?php

use App\Http\Controllers\Auth\AceitarConviteController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\RegisteredCompanyController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('registrar', [RegisteredCompanyController::class, 'create'])->name('registrar');
    Route::post('registrar', [RegisteredCompanyController::class, 'store']);

    Route::get('registrar/completar', [GoogleAuthController::class, 'completar'])->name('registrar.completar');
    Route::post('registrar/completar', [GoogleAuthController::class, 'completarStore']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

    // Assinado: só um link com assinatura válida (enviado por e-mail) passa.
    // O POST usa a mesma URL completa (com assinatura) — ver
    // AceitarConviteController.
    Route::middleware('signed')->group(function () {
        Route::get('convite/{usuario}/aceitar', [AceitarConviteController::class, 'show'])->name('convite.aceitar');
        Route::post('convite/{usuario}/aceitar', [AceitarConviteController::class, 'store']);
    });
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
