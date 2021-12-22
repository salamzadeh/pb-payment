<?php

use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Salamzadeh\PBPayment\Gateways\Test'], function () {
    Route::get('/pbpayment/test/{reference}', 'TestGatewayController@paymentView')
        ->name('pbpayment.test.pay');

    Route::get('/pbpayment/test/{code}/verify',  'TestGatewayController@verify')
        ->name('pbpayment.test.verify');
});
