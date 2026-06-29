<?php

use App\Http\Controllers\AccountCardQrController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountVehicleController;
use App\Http\Controllers\AccountVehicleDocumentController;
use App\Http\Controllers\Admin\AdminCardController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MercadoPagoWebhookController;
use App\Http\Controllers\PublicCardController;
use App\Http\Controllers\PublicCardQrController;
use App\Http\Controllers\PublicVehicleController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('landing');

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::get('/account', [AccountController::class, 'show'])->name('account.show');
Route::get('/account/cards/{card}/qr.svg', [AccountCardQrController::class, 'show'])->name('account.cards.qr');
Route::post('/account/vehicles', [AccountVehicleController::class, 'store'])->name('account.vehicles.store');
Route::post('/account/vehicles/{vehicle}/documents/{documentType}', [AccountVehicleDocumentController::class, 'store'])->name('account.vehicles.documents.store');
Route::get('/billing', [BillingController::class, 'show'])->name('billing.show');
Route::post('/billing/mercado-pago/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
Route::get('/billing/mercado-pago/success', [BillingController::class, 'success'])->name('billing.mercado-pago.success');
Route::get('/billing/mercado-pago/failure', [BillingController::class, 'failure'])->name('billing.mercado-pago.failure');
Route::get('/billing/mercado-pago/pending', [BillingController::class, 'pending'])->name('billing.mercado-pago.pending');
Route::post('/webhooks/mercado-pago', MercadoPagoWebhookController::class)->name('webhooks.mercado-pago');

Route::get('/admin', AdminDashboardController::class)->name('admin.dashboard');
Route::post('/admin/cards', [AdminCardController::class, 'store'])->name('admin.cards.store');
Route::post('/admin/cards/{card}/attach', [AdminCardController::class, 'attach'])->name('admin.cards.attach');
Route::post('/admin/cards/{card}/detach', [AdminCardController::class, 'detach'])->name('admin.cards.detach');

Route::get('/t/{shortCode}', [PublicCardController::class, 'show'])->name('public.cards.show');
Route::get('/t/{shortCode}/qr.svg', [PublicCardQrController::class, 'show'])->name('public.cards.qr');
Route::get('/v/{publicToken}', [PublicVehicleController::class, 'show'])->name('public.vehicles.show');
Route::get('/v/{publicToken}/documents/{document:public_token}', [PublicVehicleController::class, 'document'])->name('public.vehicles.documents.show');
