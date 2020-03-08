<?php

use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Lojazone\Pagarme\Http\Controllers'], function () {

    Route::prefix('pagarme')->group(function () {

        Route::get('/', 'PagarmeController@index')->name('pagarme.index');

    });

});
