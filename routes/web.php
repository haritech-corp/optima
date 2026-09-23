<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\LeadController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/auth/google', [AuthController::class, 'google'])->name('auth.google');
    Route::get('/auth/callback', [AuthController::class, 'callback'])->name('auth.callback');
});

Route::middleware('optima.auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('leads', LeadController::class)->except(['destroy']);
    Route::post('/leads/{lead}/archive', [LeadController::class, 'archive'])->name('leads.archive');
    Route::post('/leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');
    Route::resource('clients', ClientController::class)->only(['index', 'show']);
    Route::post('/interactions', [InteractionController::class, 'store'])->name('interactions.store');
    Route::get('/follow-ups', [FollowUpController::class, 'index'])->name('follow-ups.index');
    Route::post('/follow-ups', [FollowUpController::class, 'store'])->name('follow-ups.store');
    Route::patch('/follow-ups/{followUp}', [FollowUpController::class, 'update'])->name('follow-ups.update');
});
