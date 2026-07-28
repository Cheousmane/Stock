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
            $table->integer('unit_cost_xof')->default(0)->after('unit_price_xof');
            $table->integer('total_cost_xof')->default(0)->after('total_xof');
            $table->integer('margin_xof')->default(0)->after('total_cost_xof');
            $table->decimal('margin_rate', 5, 2)->default(0)->after('margin_xof');
        });

        Schema::table('quote_items', function (Blueprint $table) {
            $table->integer('unit_cost_xof')->default(0)->after('unit_price_xof');
            $table->integer('total_cost_xof')->default(0)->after('total_xof');
            $table->integer('margin_xof')->default(0)->after('total_cost_xof');
            $table->decimal('margin_rate', 5, 2)->default(0)->after('margin_xof');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['unit_cost_xof', 'total_cost_xof', 'margin_xof', 'margin_rate']);
        });

        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropColumn(['unit_cost_xof', 'total_cost_xof', 'margin_xof', 'margin_rate']);
        });
    }
};
