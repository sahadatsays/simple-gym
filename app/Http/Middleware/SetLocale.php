<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', config('locale.default', 'en'));

        if (! in_array($locale, config('locale.supported', ['en']), true)) {
            $locale = config('locale.default', 'en');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
