<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CybersourceController extends Controller
{
    public function process(Request $request)
    {
        abort(404);
    }

    public function callback(Request $request)
    {
        abort(404);
    }

    public function webhook(Request $request)
    {
        abort(404);
    }

    public function getCancel(Request $request)
    {
        abort(404);
    }
}
