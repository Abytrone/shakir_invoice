<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::redirect('/', '/admin');

Route::middleware(['auth'])->group(function () {
    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
});

Route::get('/payments/process', [PaymentController::class, 'process'])->name('payments.process');
Route::get('/payments/{invoice}', [PaymentController::class, 'initialize'])->name('payments.initialize');

Route::get('preview-invoice/{invoice}', function () {
    return (new \App\Mail\InvoiceSent(\App\Models\Invoice::with('client')->first()))->render();
})->name('preview-invoice');
