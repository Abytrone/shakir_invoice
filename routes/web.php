<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Models\Invoice;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('schedule-tasks', function(){
    \Log::info('Scheduled tasks are running');
});



Route::get('signed-url', function () {
    //    route('invoices.download', $invoice)
    return \Illuminate\Support\Facades\URL::signedRoute('invoices.download', Invoice::first());
})->name('throw');

Route::get('throw', function () {
    throw new \Exception('This is a test exception');
})->name('throw');

Route::redirect('/laravel/login', '/admin/login')->name('login');

Route::redirect('/', '/admin');

//Route::post('/payments/webhook', [PaymentController::class, 'handleWebhook'])->name('payments.webhook');
Route::get('/payments/process', [PaymentController::class, 'process'])->name('payments.process');
Route::get('/payments/auth', [PaymentController::class, 'auth'])
    ->name('payments.auth');
Route::middleware('signed')->group(function () {
    Route::get('/invoices/{invoice:invoice_uuid}/download', [InvoiceController::class, 'download'])
        ->name('invoices.download');



    Route::get('/payments/{invoice:invoice_uuid}', [PaymentController::class, 'initialize'])
        ->name('payments.initialize');

    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])
        ->name('payments.receipt');
});

Route::get('/invoices/{invoice:invoice_uuid}/print', [InvoiceController::class, 'print'])->name('invoices.print');

Route::post('/invoices/{invoice:invoice_uuid}/send', [InvoiceController::class, 'send'])->name('invoices.send');

Route::get('preview-invoice/{invoice}', function (Invoice $invoice) {
    return (new \App\Mail\InvoiceSent($invoice))->render();
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
