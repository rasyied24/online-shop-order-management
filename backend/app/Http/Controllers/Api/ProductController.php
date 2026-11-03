<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function index(ProductIndexRequest $request): JsonResponse
    {
        $products = Product::query()
            ->search($request->validated('search'))
            ->sortBy($request->validated('sort'))
            ->paginate((int) $request->validated('per_page', 10));

        return ApiResponse::paginated($products, ProductResource::class);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return ApiResponse::success(new ProductResource($product), 'Produk berhasil ditambahkan', 201);
    }

    public function show(Product $product): JsonResponse
    {
        return ApiResponse::success(new ProductResource($product));
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return ApiResponse::success(new ProductResource($product), 'Produk berhasil diperbarui');
    }

    public function destroy(Product $product): Response
    {
        $product->delete();

        return response()->noContent();
    }
}
