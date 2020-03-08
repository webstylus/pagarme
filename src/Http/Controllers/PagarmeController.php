<?php

namespace Lojazone\Pagarme\Http\Controllers;

use App\Http\Controllers\Controller;

use Lojazone\Pagarme\Pagarme;

class PagarmeController extends Controller
{
    /**
     * List all customers
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $pagarme = new Pagarme();
        $customers = $pagarme->getCustomerList();
        return view('pagarme::index', [
            'customers' => $customers
        ]);
    }
}
