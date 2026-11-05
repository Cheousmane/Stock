<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Purchases\MarkPurchaseOrderAsReceivedAction;
use App\Actions\Sales\ValidateCreditNoteAction;
use App\Models\Company;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(fn () => TenantContext::clear());

it('does not receive the same purchase order or add supplier debt twice', function () {
    $company = Company::factory()->create();
    TenantContext::set($company);
    $supplier = Supplier::create([
        'company_id' => $company->id,
        'name' => 'Supplier',
        'balance_xof' => 0,
    ]);
    $warehouse = Warehouse::create([
        'company_id' => $company->id,
        'name' => 'Main Warehouse',
        'code' => 'MAIN',
    ]);
    $product = Product::factory()->create(['company_id' => $company->id]);
    $purchaseOrder = PurchaseOrder::create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'number' => 'PO-001',
        'status' => 'ordered',
        'issue_date' => now()->toDateString(),
        'subtotal_xof' => 2000,
        'total_xof' => 2000,
    ]);
    PurchaseOrderItem::create([
        'company_id' => $company->id,
        'purchase_order_id' => $purchaseOrder->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'quantity' => 2,
        'unit_price_xof' => 1000,
        'subtotal_xof' => 2000,
        'total_xof' => 2000,
    ]);

    $action = app(MarkPurchaseOrderAsReceivedAction::class);
    $action->execute($purchaseOrder);

    expect(fn () => $action->execute($purchaseOrder))->toThrow(\Exception::class);
    expect(WarehouseStock::where('warehouse_id', $warehouse->id)->value('quantity'))->toBe(2);
    expect($supplier->fresh()->balance_xof)->toBe(2000);
    $this->assertDatabaseCount('stock_movements', 1);
});

it('does not validate the same credit note or credit the customer twice', function () {
    $company = Company::factory()->create();
    TenantContext::set($company);
    $customer = Customer::factory()->create([
        'company_id' => $company->id,
        'balance_xof' => 5000,
    ]);
    $warehouse = Warehouse::create([
        'company_id' => $company->id,
        'name' => 'Main Warehouse',
        'code' => 'MAIN',
    ]);
    $product = Product::factory()->create(['company_id' => $company->id]);
    $creditNote = CreditNote::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'number' => 'CN-001',
        'status' => 'draft',
        'issue_date' => now()->toDateString(),
        'subtotal_xof' => 1000,
        'total_xof' => 1000,
    ]);
    CreditNoteItem::create([
        'company_id' => $company->id,
        'credit_note_id' => $creditNote->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'quantity' => 1,
        'unit_price_xof' => 1000,
        'subtotal_xof' => 1000,
        'total_xof' => 1000,
    ]);

    $action = app(ValidateCreditNoteAction::class);
    $action->execute($creditNote, $warehouse->id);

    expect(fn () => $action->execute($creditNote, $warehouse->id))->toThrow(\Exception::class);
    expect($customer->fresh()->balance_xof)->toBe(4000);
    expect(WarehouseStock::where('warehouse_id', $warehouse->id)->value('quantity'))->toBe(1);
    $this->assertDatabaseCount('stock_movements', 1);
});
