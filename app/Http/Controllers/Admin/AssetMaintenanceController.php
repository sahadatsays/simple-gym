<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\AssetMaintenanceRepositoryInterface;
use App\Enums\AssetStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexAssetMaintenanceRequest;
use App\Http\Requests\Admin\StoreAssetMaintenanceRequest;
use App\Http\Requests\Admin\UpdateAssetMaintenanceRequest;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Services\AssetMaintenanceService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssetMaintenanceController extends Controller
{
    public function __construct(
        private AssetMaintenanceRepositoryInterface $maintenances,
        private AssetMaintenanceService $assetMaintenanceService,
    ) {}

    public function index(IndexAssetMaintenanceRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.asset-maintenances.index', [
            'maintenances' => $this->maintenances->paginateWithFilters($filters, config('gym.pagination.per_page')),
            'filters' => $filters,
            'assets' => Asset::query()->maintainable()->orderBy('name')->get(['id', 'name', 'asset_code']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', AssetMaintenance::class);

        $selectedAssetId = request()->integer('asset_id');

        return view('admin.asset-maintenances.create', [
            'assets' => Asset::query()->maintainable()->orderBy('name')->get(['id', 'name', 'asset_code', 'status']),
            'selectedAssetId' => $selectedAssetId > 0 ? $selectedAssetId : null,
        ]);
    }

    public function store(StoreAssetMaintenanceRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $assetStatus = filled($validated['asset_status'] ?? null)
            ? AssetStatus::from($validated['asset_status'])
            : null;

        $maintenance = $this->assetMaintenanceService->create(
            data: collect($validated)->except(['attachment', 'asset_status'])->all(),
            attachment: $request->file('attachment'),
            createdBy: $request->user()?->id,
            assetStatus: $assetStatus,
        );

        Flash::success('Maintenance record saved successfully.');

        return redirect()->route('admin.assets.show', $maintenance->asset_id);
    }

    public function show(AssetMaintenance $assetMaintenance): View
    {
        $this->authorize('view', $assetMaintenance);

        $assetMaintenance->load(['asset.category', 'creator']);

        return view('admin.asset-maintenances.show', [
            'maintenance' => $assetMaintenance,
        ]);
    }

    public function edit(AssetMaintenance $assetMaintenance): View
    {
        $this->authorize('update', $assetMaintenance);

        $assetMaintenance->load('asset');

        return view('admin.asset-maintenances.edit', [
            'maintenance' => $assetMaintenance,
        ]);
    }

    public function update(UpdateAssetMaintenanceRequest $request, AssetMaintenance $assetMaintenance): RedirectResponse
    {
        $validated = $request->validated();
        $assetStatus = filled($validated['asset_status'] ?? null)
            ? AssetStatus::from($validated['asset_status'])
            : null;

        $this->assetMaintenanceService->update(
            maintenance: $assetMaintenance,
            data: collect($validated)->except(['attachment', 'remove_attachment', 'asset_status'])->all(),
            attachment: $request->file('attachment'),
            removeAttachment: (bool) ($validated['remove_attachment'] ?? false),
            assetStatus: $assetStatus,
        );

        Flash::success('Maintenance record updated successfully.');

        return redirect()->route('admin.asset-maintenances.show', $assetMaintenance);
    }

    public function destroy(AssetMaintenance $assetMaintenance): RedirectResponse
    {
        $this->authorize('delete', $assetMaintenance);

        $assetId = $assetMaintenance->asset_id;

        $this->assetMaintenanceService->delete($assetMaintenance);

        Flash::success('Maintenance record deleted successfully.');

        return redirect()->route('admin.assets.show', $assetId);
    }
}
