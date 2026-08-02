<?php

namespace App\Http\Middleware;

use App\Support\Flash;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isActive()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Flash::error('Your account has been deactivated. Please contact an administrator.');

            return redirect()->route('login');
        }

        return $next($request);
    }
}
