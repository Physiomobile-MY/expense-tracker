<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function start(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isDirector(), 403);
        abort_if($user->isDirector(), 403, 'Cannot impersonate another director.');
        abort_if($request->user()->id === $user->id, 403, 'Cannot impersonate yourself.');
        abort_if($request->session()->has('impersonating_user_id'), 403, 'Already impersonating a user.');

        $request->session()->put('impersonator_id', $request->user()->id);
        $request->session()->put('impersonating_user_id', $user->id);

        return redirect()->route('dashboard')->with('status', 'Now impersonating '.$user->name.'.');
    }

    public function stop(Request $request): RedirectResponse
    {
        abort_unless($request->session()->has('impersonator_id'), 403);

        $request->session()->forget(['impersonating_user_id', 'impersonator_id']);

        return redirect()->route('dashboard')->with('status', 'Stopped impersonating. Back to your account.');
    }
}
