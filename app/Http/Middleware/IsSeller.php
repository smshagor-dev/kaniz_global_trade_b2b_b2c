<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class IsSeller
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
            abort(404);
        }

        $user = Auth::user();
        $adminAllowed = in_array($allowAdmin, ['allow-admin', '1', 1, true], true);

        if ($adminAllowed && in_array($user->user_type, ['admin', 'staff'], true)) {
            return $next($request);
        }

        if ($user->user_type == 'seller' && !$user->banned) {
            return $next($request);
        }

        abort(404);
    }
}
