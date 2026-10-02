<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\AssetCategoryRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignAssetCategoryRequest;
use App\Http\Requests\Admin\IndexAssetCategoryRequest;
use App\Http\Requests\Admin\StoreAssetCategoryRequest;
use App\Http\Requests\Admin\UpdateAssetCategoryRequest;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Services\AssetCategoryService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class AssetCategoryController extends Controller
{
    public function __construct(
        private AssetCategoryRepositoryInterface $categories,
        private AssetCategoryService $assetCategoryService,
    ) {}

    public function index(IndexAssetCategoryRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.asset-categories.index', [
            'categories' => $this->categories->paginateWithFilters($filters, config('gym.pagination.per_page')),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', AssetCategory::class);

        return view('admin.asset-categories.create');
    }

    public function store(StoreAssetCategoryRequest $request): RedirectResponse
    {
        $this->assetCategoryService->create(
            data: $request->validated(),
            createdBy: $request->user()?->id,
        );

        Flash::success('Asset category created successfully.');

        return redirect()->route('admin.asset-categories.index');
    }

    public function show(AssetCategory $assetCategory): View
    {
        $this->authorize('view', $assetCategory);

        return view('admin.asset-categories.show', [
            'category' => $assetCategory,
            'assets' => $this->categories->paginateAssignedAssets($assetCategory, config('gym.pagination.per_page')),
            'assignableAssets' => $assetCategory->is_active
                ? $this->categories->assignableAssets($assetCategory)
                : collect(),
        ]);
    }

    public function edit(AssetCategory $assetCategory): View
    {
        $this->authorize('update', $assetCategory);

        return view('admin.asset-categories.edit', [
            'category' => $assetCategory,
        ]);
    }

    public function update(UpdateAssetCategoryRequest $request, AssetCategory $assetCategory): RedirectResponse
    {
        $this->assetCategoryService->update($assetCategory, $request->validated());

        Flash::success('Asset category updated successfully.');

        return redirect()->route('admin.asset-categories.index');
    }

    public function assign(AssignAssetCategoryRequest $request, AssetCategory $assetCategory): RedirectResponse
    {
        $asset = Asset::query()->findOrFail($request->integer('asset_id'));
        $this->authorize('update', $asset);

        try {
            $this->assetCategoryService->assign($assetCategory, $asset);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['asset_id' => $exception->getMessage()]);
        }

        Flash::success('Asset assigned to this category.');

        return redirect()->route('admin.asset-categories.show', $assetCategory);
    }

    public function destroy(AssetCategory $assetCategory): RedirectResponse
    {
        $this->authorize('delete', $assetCategory);

        try {
            $this->assetCategoryService->delete($assetCategory);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['category' => $exception->getMessage()]);
        }

        Flash::success('Asset category deleted successfully.');

        return redirect()->route('admin.asset-categories.index');
    }
}
