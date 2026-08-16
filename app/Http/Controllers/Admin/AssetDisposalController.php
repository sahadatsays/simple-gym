<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\AssetDisposalRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexAssetDisposalRequest;
use App\Http\Requests\Admin\StoreAssetDisposalRequest;
use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Services\AssetDisposalService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssetDisposalController extends Controller
{
    public function __construct(
        private AssetDisposalRepositoryInterface $disposals,
        private AssetDisposalService $assetDisposalService,
    ) {}

    public function index(IndexAssetDisposalRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.asset-disposals.index', [
            'disposals' => $this->disposals->paginateWithFilters($filters, config('gym.pagination.per_page')),
            'filters' => $filters,
            'assets' => Asset::query()->orderBy('name')->get(['id', 'name', 'asset_code']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', AssetDisposal::class);

        $selectedAssetId = request()->integer('asset_id');

        return view('admin.asset-disposals.create', [
            'assets' => Asset::query()->disposable()->orderBy('name')->get(['id', 'name', 'asset_code', 'status']),
            'selectedAssetId' => $selectedAssetId > 0 ? $selectedAssetId : null,
        ]);
    }

    public function confirm(StoreAssetDisposalRequest $request): View
    {
        $validated = $request->validated();
        $asset = Asset::query()->findOrFail($validated['asset_id']);

        return view('admin.asset-disposals.confirm', [
            'data' => $validated,
            'asset' => $asset,
        ]);
    }

    public function store(StoreAssetDisposalRequest $request): RedirectResponse
    {
        $disposal = $this->assetDisposalService->create(
            data: $request->validated(),
            createdBy: $request->user()?->id,
        );

        Flash::success('Asset disposal recorded successfully.');

        return redirect()->route('admin.assets.show', $disposal->asset_id);
    }

    public function show(AssetDisposal $assetDisposal): View
    {
        $this->authorize('view', $assetDisposal);

        $assetDisposal->load(['asset.category', 'creator']);

        return view('admin.asset-disposals.show', [
            'disposal' => $assetDisposal,
        ]);
    }
}
