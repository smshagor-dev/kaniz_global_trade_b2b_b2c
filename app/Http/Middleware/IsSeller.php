<?php

namespace App\Http\Middleware;

use App\Services\B2BCompanyService;
use Closure;
use Auth;

class IsSeller
{
    public function __construct(protected B2BCompanyService $b2bCompanyService)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $allowAdmin = null)
    {
        if (!Auth::check()) {
            abort(404);
        }

        $user = Auth::user();
        $adminAllowed = in_array($allowAdmin, ['allow-admin', '1', 1, true], true);

        if ($adminAllowed && in_array($user->user_type, ['admin', 'staff'], true)) {
            return $next($request);
        }

        if ($user->user_type == 'seller' && !$user->banned) {
            if ($this->b2bCompanyService->isSupplierPortalUser($user->id) || $this->b2bCompanyService->isBuyerPortalUser($user->id)) {
                return redirect()->to($this->b2bCompanyService->getPortalHomeUrl($user->id));
            }

            return $next($request);
        }

        abort(404);
    }
}
