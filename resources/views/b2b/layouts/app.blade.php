@php
    use App\Services\B2BCompanyService;
    use Illuminate\Support\Facades\Auth;

    $resolvedCompany = Auth::check() ? app(B2BCompanyService::class)->getCompanyByUser(Auth::id()) : null;
    $portal = $portal
        ?? (($resolvedCompany && $resolvedCompany->isSupplierSide()) ? 'supplier' : 'buyer');
@endphp
@extends('b2b.layouts.portal')
