<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Actions\Product\CreateProductVariantAction;
use App\Actions\Product\UpdateProductVariantAction;
use App\DTOs\ProductVariantDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductVariantRequest;
use App\Http\Resources\ProductVariantResource;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductVariantController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        $this->authorize('viewAny', ProductVariant::class);
        $variants = $product->variants()->orderBy('sort_order')->get();
        return response()->json(ProductVariantResource::collection($variants), Response::HTTP_OK);
    }

    public function store(ProductVariantRequest $request, Product $product, CreateProductVariantAction $action): JsonResponse
    {
        $this->authorize('create', ProductVariant::class);
        $data = $request->validated();
        $data['product_id'] = $product->id;
        $dto = ProductVariantDTO::fromArray($data);
        $variant = $action->execute($dto);
        $variant->load('product');
        return response()->json(new ProductVariantResource($variant), Response::HTTP_CREATED);
    }

    public function show(ProductVariant $productVariant): JsonResponse
    {
        $this->authorize('view', $productVariant);
        return response()->json(new ProductVariantResource($productVariant), Response::HTTP_OK);
    }

    public function update(ProductVariantRequest $request, ProductVariant $productVariant, UpdateProductVariantAction $action): JsonResponse
    {
        $this->authorize('update', $productVariant);
        $dto = ProductVariantDTO::fromArray($request->validated());
        $variant = $action->execute($productVariant, $dto);
        return response()->json(new ProductVariantResource($variant), Response::HTTP_OK);
    }

    public function destroy(ProductVariant $productVariant): JsonResponse
    {
        $this->authorize('delete', $productVariant);
        $productVariant->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
