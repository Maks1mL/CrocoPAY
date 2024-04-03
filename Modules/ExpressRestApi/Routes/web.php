<?php

use Illuminate\Support\Facades\Route;
use Modules\ExpressRestApi\Http\Controllers\ExpressRestApiController;

Route::prefix('restapi')->group(function() {
    Route::get('/payment', [ExpressRestApiController::class, 'index'])->name('makepayment');
    Route::get('/payment-form', [ExpressRestApiController::class, 'create'])->name('merchant.payment_form');
    Route::get('/payment/initiate', [ExpressRestApiController::class, 'initiate'])->name('merchant.payment.initiate');
    Route::get('/payment/success', [ExpressRestApiController::class, 'store'])->name('restapi.payment.success');
    Route::get('/payment/redirectUrl', [ExpressRestApiController::class, 'redirectUrl'])->name('restapi.payment.redirectUrl');
    Route::get('/payment/verification', [ExpressRestApiController::class, 'paymentVerification'])->name('restapi.payment.verification');
});
