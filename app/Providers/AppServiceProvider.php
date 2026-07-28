<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Quote;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WebhookEndpoint;
use App\Models\Event;
use App\Models\Expense;
use App\Models\Export;
use App\Observers\InvoiceObserver;
use App\Observers\PosSaleObserver;
use App\Observers\ProductObserver;
use App\Observers\WarehouseStockObserver;
use App\Policies\CategoryPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\DeliveryNotePolicy;
use App\Policies\EventPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\ExportPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ProductVariantPolicy;
use App\Policies\QuotePolicy;
use App\Policies\StockMovementPolicy;
use App\Policies\StockTransferPolicy;
use App\Policies\TaxPolicy;
use App\Policies\UnitPolicy;
use App\Policies\WarehousePolicy;
use App\Policies\WebhookEndpointPolicy;
use App\Policies\SupplierPolicy;
use App\Models\Supplier;
use App\Policies\PurchaseOrderPolicy;
use App\Models\PurchaseOrder;
use App\Policies\CreditNotePolicy;
use App\Models\CreditNote;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Eloquent\InvoiceRepository;
use App\Repositories\Eloquent\ProductRepository;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Product::class => ProductPolicy::class,
        ProductVariant::class => ProductVariantPolicy::class,
        Invoice::class => InvoicePolicy::class,
        Customer::class => CustomerPolicy::class,
        Warehouse::class => WarehousePolicy::class,
        Category::class => CategoryPolicy::class,
        Quote::class => QuotePolicy::class,
        DeliveryNote::class => DeliveryNotePolicy::class,
        Payment::class => PaymentPolicy::class,
        Tax::class => TaxPolicy::class,
        Unit::class => UnitPolicy::class,
        StockMovement::class => StockMovementPolicy::class,
        StockTransfer::class => StockTransferPolicy::class,
        WebhookEndpoint::class => WebhookEndpointPolicy::class,
        Export::class => ExportPolicy::class,
        Event::class => EventPolicy::class,
        Expense::class => ExpensePolicy::class,
        Supplier::class => SupplierPolicy::class,
        PurchaseOrder::class => PurchaseOrderPolicy::class,
        CreditNote::class => CreditNotePolicy::class,
    ];

    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->alias(TenantContext::class, 'tenant');

        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\URL::forceScheme('https');
        Product::observe(ProductObserver::class);
        Invoice::observe(InvoiceObserver::class);
        \App\Models\WarehouseStock::observe(WarehouseStockObserver::class);
        \App\Models\PosSale::observe(PosSaleObserver::class);

        Gate::before(function (User $user) {
            if (app()->environment('testing')) {
                return true;
            }
            app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($user->company_id);
            return $user->hasRole('admin') ? true : null;
        });
    }
}
