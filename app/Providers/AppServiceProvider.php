<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Category;
use App\Models\Company;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\Event;
use App\Models\Expense;
use App\Models\Export;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\WebhookEndpoint;
use App\Observers\CompanyObserver;
use App\Observers\CreditNoteObserver;
use App\Observers\ExpenseObserver;
use App\Observers\InvoiceObserver;
use App\Observers\PaymentObserver;
use App\Observers\PosSaleObserver;
use App\Observers\ProductObserver;
use App\Observers\PurchaseOrderObserver;
use App\Observers\UserObserver;
use App\Observers\WarehouseStockObserver;
use App\Policies\CategoryPolicy;
use App\Policies\CreditNotePolicy;
use App\Policies\CustomerPolicy;
use App\Policies\DeliveryNotePolicy;
use App\Policies\EventPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\ExportPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PosSalePolicy;
use App\Policies\PosSessionPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ProductVariantPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\QuotePolicy;
use App\Policies\StockMovementPolicy;
use App\Policies\StockTransferPolicy;
use App\Policies\SupplierPaymentPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\TaxPolicy;
use App\Policies\UnitPolicy;
use App\Policies\WarehousePolicy;
use App\Policies\WebhookEndpointPolicy;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Eloquent\InvoiceRepository;
use App\Repositories\Eloquent\ProductRepository;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Exceptions\PermissionDoesNotExist as PermissionDoesNotExistException;
use Spatie\Permission\PermissionRegistrar;

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
        SupplierPayment::class => SupplierPaymentPolicy::class,
        PurchaseOrder::class => PurchaseOrderPolicy::class,
        CreditNote::class => CreditNotePolicy::class,
        PosSession::class => PosSessionPolicy::class,
        PosSale::class => PosSalePolicy::class,
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
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
        Product::observe(ProductObserver::class);
        Invoice::observe(InvoiceObserver::class);
        Company::observe(CompanyObserver::class);
        User::observe(UserObserver::class);
        WarehouseStock::observe(WarehouseStockObserver::class);
        PosSale::observe(PosSaleObserver::class);
        Payment::observe(PaymentObserver::class);
        Expense::observe(ExpenseObserver::class);
        CreditNote::observe(CreditNoteObserver::class);
        PurchaseOrder::observe(PurchaseOrderObserver::class);

        Gate::before(function (User $user) {
            // Bypass strictement limité aux tests automatisés, jamais via APP_ENV.
            if (app()->runningUnitTests()) {
                return true;
            }
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->company_id);

            return $user->hasRole('admin') ? true : null;
        });

        Gate::before(function (User $user, string $ability) {
            // Résout les abilities string (export_data, view_profits, view_capital...)
            // via Spatie. Sans cela, toute ability sans policy dédiée vaut 403
            // pour les non-admins, même avec la permission. Les policies classes
            // (viewAny/view/create/...) restent inchangées : elles ne portent
            // jamais un nom de permission, le try/catch retombe à null.
            try {
                return $user->hasPermissionTo($ability) ? true : null;
            } catch (PermissionDoesNotExistException) {
                return null;
            }
        });
    }
}
