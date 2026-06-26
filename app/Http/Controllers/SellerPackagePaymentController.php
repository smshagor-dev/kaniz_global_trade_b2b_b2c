<?php

namespace App\Http\Controllers;

use App\Models\SellerPackage;
use App\Models\SellerPackagePayment;
use Illuminate\Http\Request;

class SellerPackagePaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_all_offline_seller_package_payments'])->only('offline_payment_request');
    }

    public function index()
    {
        //
    }

    public function offline_payment_request()
    {
        $package_payment_requests = SellerPackagePayment::where('offline_payment', 1)
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('manual_payment_methods.seller_package_payment_request', compact('package_payment_requests'));
    }

    public function offline_payment_approval(Request $request)
    {
        $package_payment = SellerPackagePayment::findOrFail($request->id);
        $packageDetails = SellerPackage::findOrFail($package_payment->seller_package_id);
        $package_payment->approval = $request->status;

        if ($package_payment->save()) {
            $seller = $package_payment->user->shop;
            $seller->seller_package_id = $package_payment->seller_package_id;
            $seller->product_upload_limit = $packageDetails->product_upload_limit;

            if (addon_is_activated('preorder')) {
                $seller->preorder_product_upload_limit = $packageDetails->preorder_product_upload_limit;
            }

            $seller->package_invalid_at = date('Y-m-d', strtotime($seller->package_invalid_at . ' +' . $packageDetails->duration . 'days'));

            if ($seller->save()) {
                return 1;
            }
        }

        return 0;
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
