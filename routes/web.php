<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Models\Invoice;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/update-uuid',function(){
    foreach (Invoice::all() as $invoice) {
        $invoice->invoice_uuid = Ramsey\Uuid\Uuid::uuid4()->toString();
        $invoice->save();
    }
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

Route::middleware('signed')->group(function () {
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])
        ->name('invoices.download');

    Route::get('/payments/{invoice}', [PaymentController::class, 'initialize'])
        ->name('payments.initialize');

    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])
        ->name('payments.receipt');
});

Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');

Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
Route::get('/payments/process', [PaymentController::class, 'process'])->name('payments.process');

Route::get('preview-invoice/{invoice}', function () {
    return (new \App\Mail\InvoiceSent(Invoice::with('client')->first()))->render();
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
