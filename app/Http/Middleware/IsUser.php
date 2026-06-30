<?php

namespace App\Http\Middleware;

use App\Services\B2BCompanyService;
use Closure;
use Auth;

class IsUser
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
            session(['link' => url()->current()]);
            return redirect()->route('user.login');
        }

        $user = Auth::user();
        $adminAllowed = in_array($allowAdmin, ['allow-admin', '1', 1, true], true);

        if ($adminAllowed && in_array($user->user_type, ['admin', 'staff'], true)) {
            return $next($request);
        }

        if ($this->b2bCompanyService->isBuyerPortalUser($user->id) || $this->b2bCompanyService->isSupplierPortalUser($user->id)) {
            return redirect()->to($this->b2bCompanyService->getPortalHomeUrl($user->id));
        }

        if (
            $user->user_type == 'customer' ||
            $user->user_type == 'seller' ||
            $user->user_type == 'delivery_boy'
        ) {
            return $next($request);
        }

        session(['link' => url()->current()]);
        return redirect()->route('user.login');
    }
}
