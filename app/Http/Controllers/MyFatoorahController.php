<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Payment\MyfatoorahController as PaymentMyfatoorahController;
use Illuminate\Http\Request;

class MyFatoorahController extends Controller
{
    public function index(Request $request)
    {
        abort(404);
    }

    public function callback(Request $request)
    {
        return app(PaymentMyfatoorahController::class)->callback($request);
    }

    public function checkout(Request $request)
    {
        abort(404);
    }

    public function webhook(Request $request)
    {
        abort(404);
    }
}
