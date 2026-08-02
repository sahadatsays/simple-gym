<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateGymSettingRequest;
use App\Services\GymSettingService;
use App\Support\CurrencyRegistry;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GymSettingController extends Controller
{
    public function __construct(private GymSettingService $gymSettingService) {}

    public function edit(): View
    {
        $settings = $this->gymSettingService->get();

        $this->authorize('view', $settings);

        return view('admin.settings.edit', [
            'settings' => $settings,
            'timezones' => timezone_identifiers_list(),
            'currencies' => CurrencyRegistry::options(),
        ]);
    }

    public function update(UpdateGymSettingRequest $request): RedirectResponse
    {
        $settings = $this->gymSettingService->get();

        $this->authorize('update', $settings);

        $this->gymSettingService->update([
            ...$request->validated(),
            'is_open' => $request->boolean('is_open'),
        ]);

        Flash::success('Gym settings updated successfully.');

        return redirect()->route('admin.settings.edit');
    }
}
