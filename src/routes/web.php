<?php

Route::get('/test-pagarme', function () {

    $pagarme = new \Lojazone\Pagarme\Pagarme();
    $pagarme->getCustomerList();
    return view('pagarme::card');

});
