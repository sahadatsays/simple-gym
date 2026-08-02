<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('dashboard.view'), 403);

        return view('admin.dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'active_users' => User::query()->where('is_active', true)->count(),
            ],
        ]);
    }
}
