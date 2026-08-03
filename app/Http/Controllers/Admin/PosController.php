<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePosSaleRequest;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Product;
use App\Services\PosService;
use App\Support\Flash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class PosController extends Controller
{
    public function __construct(
        private ProductRepositoryInterface $products,
        private PosService $posService,
    ) {}

    public function index(): View
    {
        $this->authorize('create', Payment::class);

        return view('admin.pos.index', [
            'categories' => $this->products->categories(),
            'members' => Member::query()->orderBy('name')->get(['id', 'name', 'member_code', 'phone']),
            'initialProducts' => $this->products->searchForPos(null, null, 24)
                ->map(fn (Product $product): array => $this->formatProduct($product))
                ->values()
                ->all(),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $this->authorize('create', Payment::class);

        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $products = $this->products->searchForPos(
            $request->string('search')->toString() ?: null,
            $request->string('category')->toString() ?: null,
        );

        return response()->json([
            'data' => $products->map(fn (Product $product): array => $this->formatProduct($product))->values(),
        ]);
    }

    public function scan(Request $request): JsonResponse
    {
        $this->authorize('create', Payment::class);

        $request->validate([
            'code' => ['required', 'string', 'max:100'],
        ]);

        $code = trim($request->string('code')->toString());
        $product = $this->products->findActiveForPosByBarcode($code)
            ?? $this->products->findActiveForPosBySku($code);

        if ($product === null) {
            return response()->json([
                'message' => 'Product not found or out of stock.',
            ], 404);
        }

        return response()->json([
            'data' => $this->formatProduct($product),
        ]);
    }

    public function store(StorePosSaleRequest $request): RedirectResponse
    {
        $this->authorize('create', Payment::class);

        $data = $request->validated();
        $member = isset($data['member_id']) ? Member::query()->find($data['member_id']) : null;

        try {
            $payment = $this->posService->checkout($member, $data['items'], [
                'payment_method' => $data['payment_method'],
                'discount_amount' => (float) ($data['discount_amount'] ?? 0),
                'reference' => $data['payment_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['cart' => $exception->getMessage()]);
        }

        Flash::success('Sale completed successfully.');

        return redirect()
            ->route('admin.payments.receipt', ['payment' => $payment, 'pos' => 1]);
    }

    /**
     * @return array{
     *     id: int,
     *     sku: string,
     *     barcode: ?string,
     *     name: string,
     *     category: ?string,
     *     selling_price: float,
     *     stock: int,
     *     is_low_stock: bool
     * }
     */
    private function formatProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'name' => $product->name,
            'category' => $product->category,
            'selling_price' => (float) $product->selling_price,
            'stock' => $product->stock,
            'is_low_stock' => $product->isLowStock(),
        ];
    }
}
