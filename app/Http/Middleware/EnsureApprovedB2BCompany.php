<?php

namespace App\Http\Middleware;

use App\Services\B2BCompanyService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureApprovedB2BCompany
{
    public function __construct(protected B2BCompanyService $b2bCompanyService)
    {
    }

    public function handle(Request $request, Closure $next, string $mode = 'any', string $packageRequirement = 'none')
    {
        if (!Auth::check()) {
            abort(403);
        }

        $allowed = match ($mode) {
            'buyer' => $this->b2bCompanyService->isApprovedBuyer(Auth::id()),
            'supplier' => $this->b2bCompanyService->isApprovedSupplier(Auth::id()),
            default => (bool) $this->b2bCompanyService->getCompanyByUser(Auth::id()),
        };

        if (!$allowed) {
            flash(translate('Your approved B2B company profile is required for this action.'))->warning();
            return redirect()->route('b2b.company.show');
        }

        $hasRequiredPackage = match ($packageRequirement) {
            'package' => match ($mode) {
                'buyer' => $this->b2bCompanyService->hasActiveBuyerPackage(Auth::id()),
                'supplier' => $this->b2bCompanyService->hasActiveSupplierPackage(Auth::id()),
                default => $this->b2bCompanyService->hasActivePackage(Auth::id()),
            },
            default => true,
        };

        if (!$hasRequiredPackage) {
            flash(translate('An active B2B package for your company role is required for this action.'))->warning();
            return redirect()->route('b2b.packages.index');
        }

        return $next($request);
    }
}
