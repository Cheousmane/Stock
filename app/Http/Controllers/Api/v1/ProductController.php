<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Actions\Product\CreateProductAction;
use App\DTOs\ProductDTO;
use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Expense;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ProductController
 * Handles CRUD for products within the active tenant.
 */
class ProductController extends Controller
{
    /**
     * List all products for the current tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $perPage = (int) $request->input('per_page', 15);
        $query = Product::with('category');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($isActive = $request->input('is_active')) {
            $query->where('is_active', $isActive === '1');
        }

        $products = $query->paginate($perPage);

        return response()->json(ProductResource::collection($products), Response::HTTP_OK);
    }

    /**
     * Store a new product.
     */
    public function store(ProductRequest $request, CreateProductAction $action): JsonResponse
    {
        $this->authorize('create', Product::class);
        $dto = ProductDTO::fromArray($request->validated());
        $product = $action->execute($dto);

        if ($request->hasFile('image')) {
            $product->update(['image' => $request->file('image')->store('products', 'public')]);
        }

        Cache::forget("dashboard.aggregates.v2." . TenantContext::getCompanyId());
        return response()->json(new ProductResource($product->fresh()), Response::HTTP_CREATED);
    }

    /**
     * Show a specific product.
     */
    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);
        $product->load('category');
        return response()->json(new ProductResource($product), Response::HTTP_OK);
    }

    /**
     * Update an existing product.
     * Si la quantite change, un mouvement de stock est automatiquement consigne.
     */
    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $oldQuantity = $product->quantity;
        $data = $request->validated();

        DB::transaction(function () use ($product, $data, $request, $oldQuantity) {
            $product->update($data);

            // Si la quantite a change, creer un mouvement de stock d'ajustement
            $newQuantity = $data['quantity'] ?? $oldQuantity;
            if ($newQuantity !== $oldQuantity) {
                $this->recordStockAdjustment($product, $oldQuantity, $newQuantity);
            }

            if ($request->hasFile('image')) {
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }
                $product->update(['image' => $request->file('image')->store('products', 'public')]);
            }
        });

        Cache::forget("dashboard.aggregates.v2." . TenantContext::getCompanyId());
        return response()->json(new ProductResource($product->fresh()), Response::HTTP_OK);
    }

    /**
     * Enregistre un mouvement de stock lors d'un changement de quantite manuelle.
     */
    private function recordStockAdjustment(Product $product, int $oldQty, int $newQty): void
    {
        $companyId = TenantContext::getCompanyId();
        $difference = $newQty - $oldQty;
        $type = $difference > 0 ? StockMovementType::In : StockMovementType::Out;
        $absQty = abs($difference);

        // Recupere ou cree l'entrepot par defaut
        $warehouse = Warehouse::where('company_id', $companyId)
            ->orderBy('id')
            ->firstOrCreate([
                'company_id' => $companyId,
                'code' => 'PRINCIPAL',
            ], [
                'name' => 'Entrepot principal',
                'is_active' => true,
            ]);

        // Met a jour le stock dans l'entrepot
        $stock = WarehouseStock::firstOrCreate([
            'company_id' => $companyId,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
        ], [
            'quantity' => 0,
            'reserved_quantity' => 0,
            'available_quantity' => 0,
        ]);

        $beforeQty = $stock->quantity;
        $stock->quantity += $difference;
        $stock->available_quantity = max(0, $stock->available_quantity + $difference);
        $stock->save();

        $unitCost = $product->purchase_price_xof ?? $product->cost_price_xof ?? 0;

        StockMovement::create([
            'company_id' => $companyId,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $absQty,
            'before_quantity' => $beforeQty,
            'after_quantity' => $stock->quantity,
            'unit_cost' => $type === StockMovementType::In ? $unitCost : null,
            'total_cost' => $type === StockMovementType::In ? $absQty * $unitCost : null,
            'reason' => 'Ajustement manuel de quantite',
            'reference_type' => Product::class,
            'reference_id' => $product->id,
            'created_by' => auth()->id(),
        ]);

        if ($difference > 0) {
            Expense::create([
                'company_id' => $companyId,
                'description' => 'Ajustement stock : ' . $product->name . ' (+' . $absQty . ')',
                'category' => 'stock',
                'amount' => $absQty * $unitCost,
                'date' => now(),
                'created_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Delete a product (soft delete).
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);
        $product->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Suppression groupée de produits (utilisee par le frontend).
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $this->authorize('delete', Product::class);
        $validated = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:products,id']);
        $count = Product::whereIn('id', $validated['ids'])->delete();
        return response()->json(['deleted' => $count], Response::HTTP_OK);
    }

    /**
     * Dupliquer un produit avec un nouveau SKU unique.
     * Le stock initial est egalement duplique dans WarehouseStock et StockMovement.
     * On exclut l'uuid pour eviter un conflit de contrainte unique.
     */
    public function duplicate(Product $product): JsonResponse
    {
        $this->authorize('create', Product::class);

        $newProduct = DB::transaction(function () use ($product) {
            $copy = $product->replicate(['uuid', 'created_at', 'updated_at']);
            $copy->name = $product->name . ' (copie)';
            $copy->sku = $product->sku . '-COPY-' . strtoupper(Str::random(4));
            // L'uuid sera automatiquement regenere par le hook creating du modele
            $copy->push();

            // Duplique le stock si le produit original a une quantite
            $originalQty = $product->quantity ?? 0;
            if ($originalQty > 0) {
                $this->recordStockAdjustment($copy, 0, $originalQty);
            }

            return $copy;
        });

        return response()->json(new ProductResource($newProduct->fresh()), Response::HTTP_CREATED);
    }
}
