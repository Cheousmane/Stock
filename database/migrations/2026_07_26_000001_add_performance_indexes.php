<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function addIndexIfNotExists(string $table, array $columns, string $indexName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        try {
            $exists = DB::select("
                SELECT 1 FROM information_schema.statistics
                WHERE table_schema = ? AND table_name = ? AND index_name = ?
            ", [config('database.connections.mysql.database'), $table, $indexName]);

            if (empty($exists)) {
                Schema::table($table, function ($blueprint) use ($columns, $indexName) {
                    $blueprint->index($columns, $indexName);
                });
            }
        } catch (\Exception $e) {
            return;
        }
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        try {
            $exists = DB::select("
                SELECT 1 FROM information_schema.statistics
                WHERE table_schema = ? AND table_name = ? AND index_name = ?
            ", [config('database.connections.mysql.database'), $table, $indexName]);

            if (!empty($exists)) {
                Schema::table($table, function ($blueprint) use ($indexName) {
                    $blueprint->dropIndex($indexName);
                });
            }
        } catch (\Exception $e) {
            return;
        }
    }

    public function up(): void
    {
        $this->addIndexIfNotExists('invoice_items', ['company_id', 'product_id'], 'idx_invoice_items_company_product');
        $this->addIndexIfNotExists('invoices', ['company_id', 'status', 'issue_date'], 'idx_invoices_company_status_date');
        $this->addIndexIfNotExists('warehouse_stock', ['product_id', 'quantity'], 'idx_warehouse_stock_product_quantity');
        $this->addIndexIfNotExists('products', ['company_id', 'min_stock'], 'idx_products_company_minstock');
        $this->addIndexIfNotExists('payments', ['company_id', 'created_at'], 'idx_payments_company_created');
        $this->addIndexIfNotExists('expenses', ['company_id', 'created_at'], 'idx_expenses_company_created');
        $this->addIndexIfNotExists('pos_sessions', ['company_id', 'status'], 'idx_pos_sessions_company_status');
        $this->addIndexIfNotExists('stock_transfers', ['company_id', 'to_warehouse_id'], 'idx_stock_transfers_company_to_warehouse');
        $this->addIndexIfNotExists('pos_sales', ['company_id', 'pos_session_id'], 'idx_pos_sales_company_session');
        $this->addIndexIfNotExists('pos_sales', ['company_id', 'status'], 'idx_pos_sales_company_status');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('invoice_items', 'idx_invoice_items_company_product');
        $this->dropIndexIfExists('invoices', 'idx_invoices_company_status_date');
        $this->dropIndexIfExists('warehouse_stock', 'idx_warehouse_stock_product_quantity');
        $this->dropIndexIfExists('products', 'idx_products_company_minstock');
        $this->dropIndexIfExists('payments', 'idx_payments_company_created');
        $this->dropIndexIfExists('expenses', 'idx_expenses_company_created');
        $this->dropIndexIfExists('pos_sessions', 'idx_pos_sessions_company_status');
        $this->dropIndexIfExists('stock_transfers', 'idx_stock_transfers_company_to_warehouse');
        $this->dropIndexIfExists('pos_sales', 'idx_pos_sales_company_session');
        $this->dropIndexIfExists('pos_sales', 'idx_pos_sales_company_status');
    }
};
