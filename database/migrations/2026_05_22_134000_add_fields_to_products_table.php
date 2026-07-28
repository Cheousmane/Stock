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
            $table->unsignedBigInteger('price_xof')->nullable()->after('price');
            $table->unsignedBigInteger('cost_price_xof')->nullable()->after('price_xof');
            $table->integer('quantity')->default(0)->after('cost_price_xof');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['price_xof', 'cost_price_xof', 'quantity']);
        });
    }
};
