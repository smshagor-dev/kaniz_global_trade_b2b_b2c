<?php

namespace App\Http\Middleware;

use App\Services\B2BCompanyService;
use Closure;
use Auth;

class IsCustomer
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
    public function handle($request, Closure $next)
    {
        if (Auth::check() && (Auth::user()->user_type == 'customer')) {
            $user = Auth::user();

            if ($this->b2bCompanyService->isBuyerPortalUser($user->id) || $this->b2bCompanyService->isSupplierPortalUser($user->id)) {
                return redirect()->to($this->b2bCompanyService->getPortalHomeUrl($user->id));
            }

            return $next($request);
        }
        else{
            session(['link' => url()->current()]);
            return redirect()->route('user.login');
        }
    }
}
