<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Api\V2\MyfatoorahController as ApiMyfatoorahController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MyfatoorahController extends Controller
{
    public function callback(Request $request)
    {
        if (get_setting('myfatoorah') != 1) {
            abort(404);
        }

        return app(ApiMyfatoorahController::class)->callback($request);
    }
}
