<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CybersourceController extends Controller
{
    public function pay(Request $request)
    {
        return response()->json(['result' => false, 'message' => translate('Cybersource is not available right now.')]);
    }

    public function process(Request $request)
    {
        return response()->json(['result' => false, 'message' => translate('Cybersource is not available right now.')]);
    }

    public function callback(Request $request)
    {
        return response()->json(['result' => false, 'message' => translate('Cybersource is not available right now.')]);
    }

    public function webhook(Request $request)
    {
        return response()->json(['result' => false, 'message' => translate('Cybersource is not available right now.')]);
    }
}
