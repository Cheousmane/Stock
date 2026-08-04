<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\CategoryController;
use App\Http\Controllers\Api\v1\CustomerController;
use App\Http\Controllers\Api\v1\DashboardController;
use App\Http\Controllers\Api\v1\DeliveryNoteController;
use App\Http\Controllers\Api\v1\DeliveryNotePdfController;
use App\Http\Controllers\Api\v1\EventController;
use App\Http\Controllers\Api\v1\ExpenseController;
use App\Http\Controllers\Api\v1\ExportController;
use App\Http\Controllers\Api\v1\InvoiceController;
use App\Http\Controllers\Api\v1\InvoicePdfController;
use App\Http\Controllers\Api\v1\PaymentController;
use App\Http\Controllers\Api\v1\ProductController;
use App\Http\Controllers\Api\v1\ProductVariantController;
use App\Http\Controllers\Api\v1\QuoteController;
use App\Http\Controllers\Api\v1\QuotePdfController;
use App\Http\Controllers\Api\v1\StockMovementController;
use App\Http\Controllers\Api\v1\StockTransferController;
use App\Http\Controllers\Api\v1\StockValuationController;
use App\Http\Controllers\Api\v1\TaxController;
use App\Http\Controllers\Api\v1\SubscriptionController;
use App\Http\Controllers\Api\v1\UserController;
use App\Http\Controllers\Api\v1\ActivityLogController;
use App\Http\Controllers\Api\v1\CompanyController;
use App\Http\Controllers\Api\v1\SettingController;
use App\Http\Controllers\Api\v1\UnitController;
use App\Http\Controllers\Api\v1\WarehouseController;
use App\Http\Controllers\Api\v1\WebhookEndpointController;
use App\Http\Controllers\Api\v1\ProfitController;
use App\Http\Controllers\Api\v1\AnalyticsController;
use App\Http\Controllers\Api\v1\CapitalController;
use App\Http\Controllers\Api\v1\PosController;
use App\Http\Controllers\Api\v1\RoleAndPermissionController;
use App\Http\Controllers\Api\v1\Admin\SuperAdminDashboardController;
use App\Http\Controllers\Api\v1\Admin\AdminCompanyController;
use App\Http\Controllers\Api\v1\Admin\AdminLoginLogController;
use App\Http\Controllers\Api\v1\Admin\AdminUserController;
use App\Http\Controllers\Api\v1\Admin\AdminActivityLogController;
use App\Http\Controllers\Api\WebhookController;

// Stripe webhooks (no auth, no CSRF)
Route::post('stripe/webhook', [WebhookController::class, 'handleWebhook'])->name('stripe.webhook');

// Internal webhooks prefix
Route::prefix('webhooks')->group(function (): void {
    Route::post('stripe', [WebhookController::class, 'handleWebhook']);
});

Route::name('api.v1.')->prefix('v1')->group(function () {

    // Registration does not require tenant context (creates new tenant)
    Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register')->middleware('throttle:10,10');

    // Login detects tenant from user email (no tenant middleware required)
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login')->middleware('throttle:10,60');

    // Password reset (no auth required)
    Route::post('/auth/forgot-password', [\App\Http\Controllers\Api\v1\PasswordResetController::class, 'forgot'])->name('auth.forgot-password');
    Route::post('/auth/reset-password', [\App\Http\Controllers\Api\v1\PasswordResetController::class, 'reset'])->name('auth.reset-password');

    // Authenticated routes with tenant context
    Route::middleware(['auth:sanctum', 'tenant', 'bindings', 'quota', 'throttle:100,1'])->group(function () {

        // Auth profile
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/auth/ping', [AuthController::class, 'ping'])->name('auth.ping');
        Route::put('/auth/locale', [AuthController::class, 'updateLocale'])->name('auth.locale');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // Products
        Route::apiResource('products', ProductController::class);
        Route::post('products/bulk-delete', [ProductController::class, 'bulkDelete'])->name('products.bulk-delete');
        Route::post('products/{product}/duplicate', [ProductController::class, 'duplicate'])->name('products.duplicate');
        Route::apiResource('products.variants', ProductVariantController::class)->shallow();

        // Categories
        Route::apiResource('categories', CategoryController::class);

        // Warehouses
        Route::apiResource('warehouses', WarehouseController::class);

        // Customers
        Route::apiResource('customers', CustomerController::class);

        // Suppliers
        Route::apiResource('suppliers', \App\Http\Controllers\Api\v1\SupplierController::class);

        // Purchase Orders
        Route::apiResource('purchase-orders', \App\Http\Controllers\Api\v1\PurchaseOrderController::class);
        Route::post('purchase-orders/{purchase_order}/receive', [\App\Http\Controllers\Api\v1\PurchaseOrderController::class, 'markAsReceived'])->name('purchase-orders.receive');

        // Credit Notes
        Route::apiResource('credit-notes', \App\Http\Controllers\Api\v1\CreditNoteController::class);
        Route::post('credit-notes/{credit_note}/validate', [\App\Http\Controllers\Api\v1\CreditNoteController::class, 'validateCreditNote'])->name('credit-notes.validate');

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Invoices
        Route::apiResource('invoices', InvoiceController::class);
        Route::post('invoices/bulk-delete', [InvoiceController::class, 'bulkDelete'])->name('invoices.bulk-delete');
        Route::patch('invoices/{invoice}/mark-as-sent', [InvoiceController::class, 'markAsSent'])->name('invoices.mark-as-sent');
        Route::patch('invoices/{invoice}/mark-as-cancelled', [InvoiceController::class, 'markAsCancelled'])->name('invoices.mark-as-cancelled');
        Route::get('invoices/{invoice}/pdf', [InvoicePdfController::class, 'download'])->name('invoices.pdf');
        Route::get('invoices/{invoice}/pdf/preview', [InvoicePdfController::class, 'preview'])->name('invoices.pdf.preview');
        Route::post('invoices/{invoice}/pdf/async', [InvoicePdfController::class, 'exportAsync'])->name('invoices.pdf.async');

        // Payments
        Route::apiResource('payments', PaymentController::class);

        // Stock Transfers
        Route::apiResource('stock-transfers', StockTransferController::class)->only(['index', 'store', 'show']);

        // Stock Movements
        Route::apiResource('stock-movements', StockMovementController::class)->only(['index', 'store']);

        // Stock Valuation
        Route::prefix('stock-valuation')->name('stock-valuation.')->group(function () {
            Route::get('/summary', [StockValuationController::class, 'summary'])->name('summary');
            Route::get('/products/{product}', [StockValuationController::class, 'product'])->name('product');
            Route::get('/products/{product}/average-cost', [StockValuationController::class, 'averageCost'])->name('average-cost');
        });

        // Delivery Notes
        Route::apiResource('delivery-notes', DeliveryNoteController::class);
        Route::patch('delivery-notes/{delivery_note}/mark-as-shipped', [DeliveryNoteController::class, 'markAsShipped'])->name('delivery-notes.mark-as-shipped');
        Route::patch('delivery-notes/{delivery_note}/mark-as-delivered', [DeliveryNoteController::class, 'markAsDelivered'])->name('delivery-notes.mark-as-delivered');
        Route::patch('delivery-notes/{delivery_note}/mark-as-returned', [DeliveryNoteController::class, 'markAsReturned'])->name('delivery-notes.mark-as-returned');
        Route::get('delivery-notes/{delivery_note}/pdf', [DeliveryNotePdfController::class, 'download'])->name('delivery-notes.pdf');
        Route::get('delivery-notes/{delivery_note}/pdf/preview', [DeliveryNotePdfController::class, 'preview'])->name('delivery-notes.pdf.preview');
        Route::post('delivery-notes/{delivery_note}/pdf/async', [DeliveryNotePdfController::class, 'exportAsync'])->name('delivery-notes.pdf.async');

        // Taxes
        Route::apiResource('taxes', TaxController::class);

        // Quotes
        Route::apiResource('quotes', QuoteController::class);
        Route::patch('quotes/{quote}/mark-as-sent', [QuoteController::class, 'markAsSent'])->name('quotes.mark-as-sent');
        Route::patch('quotes/{quote}/mark-as-accepted', [QuoteController::class, 'markAsAccepted'])->name('quotes.mark-as-accepted');
        Route::patch('quotes/{quote}/mark-as-rejected', [QuoteController::class, 'markAsRejected'])->name('quotes.mark-as-rejected');
        Route::post('quotes/{quote}/convert-to-invoice', [QuoteController::class, 'convertToInvoice'])->name('quotes.convert-to-invoice');
        Route::get('quotes/{quote}/pdf', [QuotePdfController::class, 'download'])->name('quotes.pdf');
        Route::get('quotes/{quote}/pdf/preview', [QuotePdfController::class, 'preview'])->name('quotes.pdf.preview');
        Route::post('quotes/{quote}/pdf/async', [QuotePdfController::class, 'exportAsync'])->name('quotes.pdf.async');

        // Subscriptions
        Route::get('subscriptions/plans', [SubscriptionController::class, 'plans'])->name('subscriptions.plans');
        Route::get('subscriptions/current', [SubscriptionController::class, 'current'])->name('subscriptions.current');
        Route::post('subscriptions/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscriptions.subscribe');
        Route::post('subscriptions/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
        Route::post('subscriptions/resume', [SubscriptionController::class, 'resume'])->name('subscriptions.resume');
        Route::post('subscriptions/swap/{planSlug}', [SubscriptionController::class, 'swap'])->name('subscriptions.swap');
        Route::get('subscriptions/invoice-portal', [SubscriptionController::class, 'invoicePortal'])->name('subscriptions.invoice-portal');

        // Users
        Route::apiResource('users', UserController::class);

        // Roles & Permissions
        Route::get('roles', [RoleAndPermissionController::class, 'roles']);
        Route::get('permissions', [RoleAndPermissionController::class, 'permissions']);

        // Activity Logs
        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

        // Company
        Route::get('/company', [CompanyController::class, 'show'])->name('company.show');
        Route::put('/company', [CompanyController::class, 'update'])->name('company.update');
        Route::post('company/logo', [CompanyController::class, 'uploadLogo'])->name('company.logo');
        Route::delete('company/logo', [CompanyController::class, 'deleteLogo'])->name('company.logo.delete');
        Route::put('/company/plan', [CompanyController::class, 'switchPlan'])->name('company.plan');

        // Units
        Route::apiResource('units', UnitController::class);

        // Events
        Route::apiResource('events', EventController::class)->only(['index', 'store', 'show', 'update', 'destroy']);

        // Expenses
        Route::apiResource('expenses', ExpenseController::class)->only(['index', 'store', 'destroy']);

        // Webhook Endpoints
        Route::get('webhook-events', [WebhookEndpointController::class, 'events'])->name('webhook-events');
        Route::apiResource('webhook-endpoints', WebhookEndpointController::class);

        // Settings
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-mail', [SettingController::class, 'testMail'])->name('settings.test-mail');

        // Analytics
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/overview', [AnalyticsController::class, 'overview'])->name('overview');
            Route::get('/revenue-growth', [AnalyticsController::class, 'revenueGrowth'])->name('revenue-growth');
            Route::get('/top-products', [AnalyticsController::class, 'topProducts'])->name('top-products');
            Route::get('/top-customers', [AnalyticsController::class, 'topCustomers'])->name('top-customers');
            Route::get('/recent-invoices', [AnalyticsController::class, 'recentInvoices'])->name('recent-invoices');
            Route::get('/expenses-by-category', [AnalyticsController::class, 'expensesByCategory'])->name('expenses-by-category');
        });

        // Profits
        Route::prefix('profits')->name('profits.')->group(function () {
            Route::get('/summary', [ProfitController::class, 'summary'])->name('summary');
            Route::get('/by-product', [ProfitController::class, 'byProduct'])->name('by-product');
            Route::get('/by-customer', [ProfitController::class, 'byCustomer'])->name('by-customer');
            Route::get('/by-invoice', [ProfitController::class, 'byInvoice'])->name('by-invoice');
            Route::post('/recalculate/{invoice}', [ProfitController::class, 'recalculate'])->name('recalculate');
        });

        // Capital
        Route::get('capital', [CapitalController::class, 'show'])->name('capital.show');
        Route::put('capital', [CapitalController::class, 'update'])->name('capital.update');

        // POS (Point of Sale)
        Route::prefix('pos')->name('pos.')->group(function () {
            Route::get('/session/current', [PosController::class, 'currentSession'])->name('session.current');
            Route::post('/session/open', [PosController::class, 'openSession'])->name('session.open');
            Route::post('/session/{session}/close', [PosController::class, 'closeSession'])->name('session.close');
            Route::get('/catalog', [PosController::class, 'catalog'])->name('catalog');
            Route::post('/sale', [PosController::class, 'processSale'])->name('sale');
            Route::get('/sessions', [PosController::class, 'sessions'])->name('sessions');
            Route::get('/sessions/{session}', [PosController::class, 'sessionSales'])->name('sessions.sales');
        });

        // Exports (sync - immediate download)
        Route::get('exports/products', [ExportController::class, 'exportProducts'])->name('exports.products');
        Route::get('exports/products/csv', [ExportController::class, 'exportProductsCsv'])->name('exports.products.csv');
        Route::get('exports/customers', [ExportController::class, 'exportCustomers'])->name('exports.customers');
        Route::get('exports/customers/csv', [ExportController::class, 'exportCustomersCsv'])->name('exports.customers.csv');
        Route::get('exports/invoices', [ExportController::class, 'exportInvoices'])->name('exports.invoices');
        Route::get('exports/invoices/csv', [ExportController::class, 'exportInvoicesCsv'])->name('exports.invoices.csv');
        Route::get('exports/stock', [ExportController::class, 'exportStock'])->name('exports.stock');

        // Exports (async - queued)
        Route::post('exports/async/{type}', [ExportController::class, 'exportAsync'])->name('exports.async');
        Route::get('exports/async/{export}/status', [ExportController::class, 'status'])->name('exports.async.status');
        Route::get('exports/async/{export}/download', [ExportController::class, 'download'])->name('exports.async.download');
    });

    // Super admin routes (global, no tenant scope)
    Route::middleware(['auth:sanctum', 'admin', 'bindings'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/companies/stats', [AdminCompanyController::class, 'stats'])->name('companies.stats');
        Route::get('/companies', [AdminCompanyController::class, 'index'])->name('companies.index');
        Route::get('/companies/{company}', [AdminCompanyController::class, 'show'])->name('companies.show');
        Route::put('/companies/{company}', [AdminCompanyController::class, 'update'])->name('companies.update');
        Route::post('/companies/{company}/suspend', [AdminCompanyController::class, 'suspend'])->name('companies.suspend');
        Route::post('/companies/{company}/activate', [AdminCompanyController::class, 'activate'])->name('companies.activate');
        Route::get('/login-logs', [AdminLoginLogController::class, 'index'])->name('login-logs');
        Route::get('/activity-logs', [AdminActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users/{id}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
        Route::post('/users/{id}/activate', [AdminUserController::class, 'activate'])->name('users.activate');
    });
});
