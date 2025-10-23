<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateProductAction;
use App\Actions\DeleteProductAction;
use App\Actions\UpdateProductAction;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\Tax;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

final class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Product::query()
            ->with(['defaultTax', 'stockInCurrentWorkspace'])
            ->orderBy('created_at', 'desc');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->get('track_stock') !== null) {
            $query->where('track_stock', $request->boolean('track_stock'));
        }

        if ($request->get('low_stock')) {
            $query->whereHas('stockInCurrentWorkspace', function ($q): void {
                $q->whereColumn('quantity', '<=', 'minimum_quantity');
            });
        }

        if ($request->get('tax_id')) {
            $query->where('default_tax_id', $request->get('tax_id'));
        }

        $products = $query->paginate(15)->withQueryString();

        return Inertia::render('products/index', [
            'products' => $products,
            'filters' => $request->only(['search', 'track_stock', 'low_stock']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('products/create', [
            'taxes' => Tax::query()->select('id', 'name', 'rate', 'is_default')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateProductRequest $request, CreateProductAction $action): RedirectResponse
    {
        $product = $action->handle(Auth::user(), $request->validated());

        return redirect()->route('products.show', $product)
            ->with('success', 'Product created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): Response
    {
        $product->load(['defaultTax', 'stockInCurrentWorkspace', 'stockMovements.createdBy']);

        return Inertia::render('products/show', [
            'product' => $product,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product): Response
    {
        $product->load(['defaultTax']);

        return Inertia::render('products/edit', [
            'product' => $product,
            'taxes' => Tax::query()->select('id', 'name', 'rate')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product, UpdateProductAction $action): RedirectResponse
    {
        $product = $action->handle($product, $request->validated());

        return redirect()->route('products.show', $product)
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product, DeleteProductAction $action): RedirectResponse
    {
        try {
            $action->handle($product);

            return redirect()->route('products.index')
                ->with('success', 'Product deleted successfully.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
