<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\AssetRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexAssetRequest;
use App\Http\Requests\Admin\StoreAssetRequest;
use App\Http\Requests\Admin\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Services\AssetService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function __construct(
        private AssetRepositoryInterface $assets,
        private AssetService $assetService,
    ) {}

    public function index(IndexAssetRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.assets.index', [
            'assets' => $this->assets->paginateWithFilters($filters, config('gym.pagination.per_page')),
            'filters' => $filters,
            'categories' => AssetCategory::query()->active()->ordered()->get(['id', 'name']),
            'locations' => $this->assets->distinctLocations(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Asset::class);

        return view('admin.assets.create', [
            'categories' => AssetCategory::query()->active()->ordered()->get(['id', 'name']),
        ]);
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        $asset = $this->assetService->create(
            data: $request->validated(),
            createdBy: $request->user()?->id,
        );

        Flash::success('Asset created successfully.');

        return redirect()->route('admin.assets.show', $asset);
    }

    public function show(Asset $asset): View
    {
        $this->authorize('view', $asset);

        $asset->load([
            'category',
            'creator',
            'disposal',
            'maintenances' => fn ($query) => $query->latest('maintained_at')->latest('id'),
        ]);

        return view('admin.assets.show', [
            'asset' => $asset,
        ]);
    }

    public function edit(Asset $asset): View
    {
        $this->authorize('update', $asset);

        return view('admin.assets.edit', [
            'asset' => $asset,
            'categories' => AssetCategory::query()
                ->where(function ($query) use ($asset): void {
                    $query->active()->orWhere('id', $asset->asset_category_id);
                })
                ->ordered()
                ->get(['id', 'name']),
        ]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): RedirectResponse
    {
        $this->assetService->update($asset, $request->validated());

        Flash::success('Asset updated successfully.');

        return redirect()->route('admin.assets.show', $asset);
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        $this->authorize('delete', $asset);

        $this->assetService->delete($asset);

        Flash::success('Asset deleted successfully.');

        return redirect()->route('admin.assets.index');
    }
}
