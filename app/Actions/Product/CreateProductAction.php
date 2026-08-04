<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\DTOs\ProductDTO;
use App\Enums\StockMovementType;
use App\Models\Expense;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * Action to create a new product within the active tenant.
 */
class CreateProductAction
{
    /**
     * Execute the creation.
     *
     * @param ProductDTO $dto
     * @return Product
     */
    public function execute(ProductDTO $dto): Product
    {
        return DB::transaction(function () use ($dto) {
            $companyId = TenantContext::getCompanyId();
            $data = [
                'company_id' => $companyId,
                'name' => $dto->name,
                'sku' => $dto->sku,
                'price_xof' => $dto->priceXof,
                'price' => $dto->priceXof,
                'description' => $dto->description,
                'barcode' => $dto->barcode,
                'category_id' => $dto->categoryId,
            ];

            if ($dto->purchasePriceXof !== null) {
                $data['purchase_price_xof'] = $dto->purchasePriceXof;
            }
            if ($dto->costPriceXof !== null) {
                $data['cost_price_xof'] = $dto->costPriceXof;
            }
            if ($dto->wholesalePriceXof !== null) {
                $data['wholesale_price_xof'] = $dto->wholesalePriceXof;
            }
            if ($dto->quantity !== null) {
                $data['quantity'] = $dto->quantity;
            }
            if ($dto->minStock !== null) {
                $data['min_stock'] = $dto->minStock;
            }
            if ($dto->isActive !== null) {
                $data['is_active'] = $dto->isActive;
            } else {
                $data['is_active'] = true;
            }

            /** @var Product $product */
            $product = Product::create($data);

            // Si une quantite initiale est fournie, creer l'entree de stock
            $initialQty = $dto->quantity ?? 0;
            if ($initialQty > 0) {
                $cost = $dto->costPriceXof ?? 0;
                $this->createInitialStockEntry($product, $companyId, $initialQty, $cost);
                $this->createExpenseForStockEntry($product, $companyId, $initialQty, $cost);
            }

            return $product;
        });
    }

    /**
     * Cree un enregistrement WarehouseStock et un mouvement de stock pour le stock initial.
     */
    private function createInitialStockEntry(Product $product, int $companyId, int $quantity, ?int $unitCost): void
    {
        // Recupere ou cree l'entrepot par defaut
        $warehouse = Warehouse::where('company_id', $companyId)
            ->orderBy('id')
            ->first();

        if (!$warehouse) {
            $warehouse = Warehouse::create([
                'company_id' => $companyId,
                'name' => 'Entrepot principal',
                'code' => 'PRINCIPAL',
                'is_active' => true,
            ]);
        }

        // Cree ou met a jour le stock dans l'entrepot
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
        $stock->quantity += $quantity;
        $stock->available_quantity += $quantity;
        $stock->save();

        $cost = $unitCost ?? $product->purchase_price_xof ?? $product->cost_price_xof ?? 0;

        // Consigne le mouvement de stock entrant
        StockMovement::create([
            'company_id' => $companyId,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'type' => StockMovementType::In,
            'quantity' => $quantity,
            'before_quantity' => $beforeQty,
            'after_quantity' => $stock->quantity,
            'unit_cost' => $cost,
            'total_cost' => $quantity * $cost,
            'reason' => 'Stock initial a la creation du produit',
            'reference_type' => Product::class,
            'reference_id' => $product->id,
            'created_by' => auth()->id(),
        ]);
    }

    private function createExpenseForStockEntry(Product $product, int $companyId, int $quantity, int $unitCost): void
    {
        $total = $quantity * $unitCost;
        if ($total <= 0) return;

        Expense::create([
            'company_id' => $companyId,
            'description' => 'Achat stock initial : ' . $product->name . ' (x' . $quantity . ')',
            'category' => 'stock',
            'amount' => $total,
            'date' => now(),
            'created_by' => auth()->id(),
        ]);
    }
}
