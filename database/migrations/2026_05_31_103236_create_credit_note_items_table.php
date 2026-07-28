<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_note_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('credit_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('product_variant_id')->nullable()->constrained();
            $table->string('name');
            $table->string('description')->nullable();
            $table->integer('quantity');
            $table->unsignedBigInteger('unit_price_xof');
            $table->foreignId('tax_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('tax_amount_xof')->default(0);
            $table->unsignedBigInteger('discount_amount_xof')->default(0);
            $table->unsignedBigInteger('subtotal_xof');
            $table->unsignedBigInteger('total_xof');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_note_items');
    }
};
