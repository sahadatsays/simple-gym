<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        if (! in_array($locale, config('locale.supported', ['en']), true)) {
            $locale = config('locale.default', 'en');
        }

        $request->session()->put('locale', $locale);

        return redirect()->back(fallback: $this->fallbackUrl());
    }

    private function fallbackUrl(): string
    {
        return auth()->check()
            ? route('admin.dashboard')
            : route('login');
    }
}
