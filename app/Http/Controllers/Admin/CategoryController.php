<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $categoryService,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Category::class);

        return view('admin.categories.index', [
            'categories' => Category::query()
                ->withCount([
                    'products',
                    'products as active_products_count' => fn ($query) => $query->where('status', 'active'),
                ])
                ->ordered()
                ->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('admin.categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', Category::class);

        $this->categoryService->create($request->validated());

        Flash::success('Category created successfully.');

        return redirect()->route('admin.categories.index');
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('admin.categories.edit', [
            'category' => $category,
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $this->categoryService->update($category, $request->validated());

        Flash::success('Category updated successfully.');

        return redirect()->route('admin.categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        try {
            $this->categoryService->delete($category);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['category' => $exception->getMessage()]);
        }

        Flash::success('Category deleted successfully.');

        return redirect()->route('admin.categories.index');
    }
}
