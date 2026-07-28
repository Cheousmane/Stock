<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number');
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('draft'); // draft, ordered, received, cancelled
            $table->date('issue_date');
            $table->date('expected_delivery_date')->nullable();
            $table->unsignedBigInteger('subtotal_xof');
            $table->unsignedBigInteger('tax_xof')->default(0);
            $table->unsignedBigInteger('discount_xof')->default(0);
            $table->string('discount_type')->nullable();
            $table->unsignedBigInteger('total_xof');
            $table->unsignedBigInteger('paid_xof')->default(0);
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'issue_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
