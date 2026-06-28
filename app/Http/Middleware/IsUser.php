<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class IsUser
{
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

        if (
            $user->user_type == 'customer' ||
            $user->user_type == 'seller' ||
            $user->user_type == 'delivery_boy' ||
            ($adminAllowed && in_array($user->user_type, ['admin', 'staff'], true))
        ) {
            return $next($request);
        }

        session(['link' => url()->current()]);
        return redirect()->route('user.login');
    }
}
