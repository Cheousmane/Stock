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
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                $exists = DB::select("
                    SELECT 1 FROM information_schema.statistics
                    WHERE table_schema = ? AND table_name = ? AND index_name = ?
                ", [config('database.connections.mysql.database'), $table, $indexName]);

                if (empty($exists)) {
                    Schema::table($table, function ($blueprint) use ($columns, $indexName) {
                        $blueprint->index($columns, $indexName);
                    });
                }
            } else {
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
            Schema::table($table, function ($blueprint) use ($indexName) {
                $blueprint->dropIndex($indexName);
            });
        } catch (\Exception $e) {
            return;
        }
    }

    public function up(): void
    {
        $this->addIndexIfNotExists('products', ['company_id', 'is_active', 'category_id'], 'idx_products_tenant_lookup');
        $this->addIndexIfNotExists('stock_valuations', ['company_id', 'warehouse_id', 'product_id', 'quantity'], 'idx_stock_valuations_fifo');
        $this->addIndexIfNotExists('invoices', ['company_id', 'customer_id', 'status'], 'idx_invoices_customer_status');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('products', 'idx_products_tenant_lookup');
        $this->dropIndexIfExists('stock_valuations', 'idx_stock_valuations_fifo');
        $this->dropIndexIfExists('invoices', 'idx_invoices_customer_status');
    }
};
