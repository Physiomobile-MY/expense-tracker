<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HandleImpersonation
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->session()->has('impersonating_user_id') && Auth::check()) {
            $target = User::find($request->session()->get('impersonating_user_id'));

            if ($target && $target->status === 'active' && ! $target->must_change_password && ! $target->isDirector()) {
                Auth::setUser($target);
            } else {
                $request->session()->forget(['impersonating_user_id', 'impersonator_id']);
            }
        }

        return $next($request);
    }
}
