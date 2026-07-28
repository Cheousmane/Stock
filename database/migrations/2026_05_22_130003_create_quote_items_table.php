<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->integer('quantity');
            $table->unsignedBigInteger('unit_price_xof');
            $table->unsignedBigInteger('subtotal_xof');
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->unsignedBigInteger('tax_xof')->default(0);
            $table->unsignedBigInteger('total_xof');
            $table->timestamps();

            $table->index(['company_id', 'quote_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_items');
    }
};
