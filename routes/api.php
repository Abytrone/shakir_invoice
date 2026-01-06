<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('/payments/webhook', [PaymentController::class, 'handleWebhook'])
    ->name('api.payments.webhook');

