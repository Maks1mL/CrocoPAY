<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\ExpressRestApi\Http\Controllers\Api\ExpressRestApiController;

Route::namespace('Api')->group(function () {
    Route::post('/access-token', [ExpressRestApiController::class, 'createAccessToken']);
    
    Route::middleware('merchant.auth')->group(function () {
        Route::post('/initiate-payment', [ExpressRestApiController::class, 'initiatePayment']);
        Route::post('/payment-verify', [ExpressRestApiController::class, 'paymentVerify']);
        
        Route::post('/merchant/payment', [ExpressRestApiController::class, 'generatedUrl']);
        Route::post('/merchant/payment/cancel', [ExpressRestApiController::class, 'cancelPayment'])->name('cancel-payment');
    });
});
