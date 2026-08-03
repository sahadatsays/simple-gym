<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustProductStockRequest;
use App\Http\Requests\Admin\IndexProductRequest;
use App\Http\Requests\Admin\ShowProductRequest;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductReportService;
use App\Services\ProductService;
use App\Support\Flash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class ProductController extends Controller
{
    public function __construct(
        private ProductRepositoryInterface $products,
        private ProductService $productService,
        private ProductReportService $productReports,
    ) {}

    public function index(IndexProductRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.products.index', [
            'products' => $this->products->paginateWithFilters($filters, config('gym.pagination.per_page')),
            'filters' => $filters,
            'categories' => $this->products->categories(),
        ]);
    }

    public function show(ShowProductRequest $request, Product $product): View
    {
        $filters = $request->validated();

        return view('admin.products.show', [
            'product' => $product,
            'filters' => $filters,
            'summary' => $this->productReports->summary($product, $filters),
            'monthlyBreakdown' => $this->productReports->monthlyBreakdown($product),
            'sales' => $this->productReports->paginateSales($product, $filters, config('gym.pagination.per_page')),
            'stockMovements' => $this->productReports->stockMovements($product),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('admin.products.create', [
            'categories' => $this->products->categories(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $this->productService->create($request->validated());

        Flash::success('Product created successfully.');

        return redirect()->route('admin.products.index');
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => $this->products->categories(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $this->productService->update($product, $request->validated());

        Flash::success('Product updated successfully.');

        return redirect()->route('admin.products.index');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $this->productService->delete($product);

        Flash::success('Product deleted successfully.');

        return redirect()->route('admin.products.index');
    }

    public function adjustStock(AdjustProductStockRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $data = $request->validated();

        try {
            $this->productService->adjustStock(
                $product,
                (int) $data['adjustment'],
                $data['notes'] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            Flash::error($exception->getMessage());

            return back();
        }

        Flash::success('Product stock updated successfully.');

        return back();
    }

    public function lookup(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $request->validate([
            'barcode' => ['nullable', 'string', 'max:100'],
            'sku' => ['nullable', 'string', 'max:50'],
        ]);

        $product = null;

        if ($request->filled('barcode')) {
            $product = $this->products->findByBarcode($request->string('barcode')->toString());
        } elseif ($request->filled('sku')) {
            $product = $this->products->findBySku($request->string('sku')->toString());
        }

        if ($product === null) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'category' => $product->category,
                'selling_price' => (float) $product->selling_price,
                'stock' => $product->stock,
                'status' => $product->status->value,
            ],
        ]);
    }
}
