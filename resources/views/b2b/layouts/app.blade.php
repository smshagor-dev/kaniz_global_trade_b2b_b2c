@php
    use App\Services\B2BCompanyService;
    use Illuminate\Support\Facades\Auth;

    $resolvedCompany = Auth::check() ? app(B2BCompanyService::class)->getCompanyByUser(Auth::id()) : null;
    $layout = ($resolvedCompany && $resolvedCompany->isSupplierSide()) ? 'b2b.layouts.supplier' : 'b2b.layouts.buyer';
@endphp
@extends($layout)
