<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('barcode', 100)->nullable()->after('sku');
            $table->unsignedBigInteger('purchase_price_xof')->nullable()->after('price');
            $table->unsignedBigInteger('wholesale_price_xof')->nullable()->after('purchase_price_xof');
            $table->integer('min_stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'sku']);
            $table->index(['company_id', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropConstrainedForeignId('unit_id');
            $table->dropColumn(['barcode', 'purchase_price_xof', 'wholesale_price_xof', 'min_stock', 'is_active', 'metadata']);
        });
    }
};
