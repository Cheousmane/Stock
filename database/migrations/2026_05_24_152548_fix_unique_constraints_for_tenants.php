<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_number_unique');
            $table->unique(['company_id', 'number'], 'invoices_company_id_number_unique');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropUnique('quotes_number_unique');
            $table->unique(['company_id', 'number'], 'quotes_company_id_number_unique');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_sku_unique');
            $table->unique(['company_id', 'sku'], 'products_company_id_sku_unique');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_company_id_number_unique');
            $table->unique('number', 'invoices_number_unique');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropUnique('quotes_company_id_number_unique');
            $table->unique('number', 'quotes_number_unique');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_company_id_sku_unique');
            $table->unique('sku', 'products_sku_unique');
        });
    }
};
