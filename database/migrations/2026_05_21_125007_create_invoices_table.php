<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('draft');
            $table->date('issue_date');
            $table->date('due_date');
            $table->unsignedBigInteger('subtotal_xof');
            $table->unsignedBigInteger('tax_xof')->default(0);
            $table->unsignedBigInteger('discount_xof')->default(0);
            $table->string('discount_type')->nullable();
            $table->unsignedBigInteger('total_xof');
            $table->unsignedBigInteger('paid_xof')->default(0);
            $table->unsignedBigInteger('balance_due_xof');
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'customer_id']);
            $table->index(['company_id', 'issue_date']);
            $table->index(['company_id', 'due_date']);
            $table->index(['company_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
