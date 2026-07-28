<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->index(['company_id', 'product_id'], 'idx_invoice_items_company_product');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['company_id', 'status', 'issue_date'], 'idx_invoices_company_status_date');
        });

        Schema::table('warehouse_stock', function (Blueprint $table) {
            $table->index(['product_id', 'quantity'], 'idx_warehouse_stock_product_quantity');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['company_id', 'min_stock'], 'idx_products_company_minstock');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['company_id', 'created_at'], 'idx_payments_company_created');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index(['company_id', 'created_at'], 'idx_expenses_company_created');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex('idx_invoice_items_company_product');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_company_status_date');
        });

        Schema::table('warehouse_stock', function (Blueprint $table) {
            $table->dropIndex('idx_warehouse_stock_product_quantity');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_company_minstock');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_company_created');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('idx_expenses_company_created');
        });
    }
};
