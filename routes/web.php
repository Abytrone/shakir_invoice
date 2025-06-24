<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::redirect('/', '/admin');

Route::middleware(['auth'])->group(function () {
    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
});

Route::get('/payments/process', [PaymentController::class, 'process'])->name('payments.process');
Route::get('/payments/{invoice}', [PaymentController::class, 'initialize'])->name('payments.initialize');

Route::get('preview-invoice/{invoice}', function () {
    return (new \App\Mail\InvoiceSent(\App\Models\Invoice::with('client')->first()))->render();
})->name('preview-invoice');

Route::get('/test-mail', function () {

    Mail::to('mahmudsheikh25@gmail.com')
        ->queue(new \App\Mail\TestMail());
})->name('deploy-fresh');

Route::get('/cache-clear', function () {
    \Artisan::call('config:clear');
    \Artisan::call('cache:clear');
    \Artisan::call('view:clear');
    \Artisan::call('route:clear');

    return 'Cache cleared successfully!';
})->name('cache-clear');


Route::get('/migrate-force', function () {
    \Artisan::call('migrate', ['--force' => true]);
    return 'migration run successfully!';
})->name('cache-clear');