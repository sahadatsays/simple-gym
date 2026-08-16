<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScanPosProductRequest;
use App\Http\Requests\Admin\SearchPosProductsRequest;
use App\Http\Requests\Admin\StorePosSaleRequest;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Product;
use App\Services\PosService;
use App\Support\Flash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
                ->map(fn (Product $product): array => $product->toPosArray())
                ->values()
                ->all(),
        ]);
    }

    public function search(SearchPosProductsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $products = $this->products->searchForPos(
            $validated['search'] ?? null,
            $validated['category'] ?? null,
        );

        return response()->json([
            'data' => $products->map(fn (Product $product): array => $product->toPosArray())->values(),
        ]);
    }

    public function scan(ScanPosProductRequest $request): JsonResponse
    {
        $code = trim($request->validated('code'));
        $product = $this->products->findActiveForPosByBarcode($code)
            ?? $this->products->findActiveForPosBySku($code);

        if ($product === null) {
            return response()->json([
                'message' => 'Product not found or out of stock.',
            ], 404);
        }

        return response()->json([
            'data' => $product->toPosArray(),
        ]);
    }

    public function store(StorePosSaleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $member = isset($data['member_id']) ? Member::query()->find($data['member_id']) : null;

        try {
            $result = $this->posService->checkout($member, $data['items'], [
                'payment_method' => $data['payment_method'] ?? 'cash',
                'amount_paid' => (float) $data['amount_paid'],
                'discount_amount' => (float) ($data['discount_amount'] ?? 0),
                'reference' => $data['payment_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'due_at' => $data['due_at'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['cart' => $exception->getMessage()]);
        }

        $invoice = $result['invoice'];
        $isFullyPaid = $invoice->isPaid();

        Flash::success($isFullyPaid
            ? 'Sale completed successfully.'
            : 'Order created successfully. Outstanding balance can be collected later.');

        $redirect = redirect()->route('admin.orders.show', [
            'invoice' => $invoice,
            'print' => $isFullyPaid ? 1 : 0,
        ]);

        return $redirect;
    }
}
