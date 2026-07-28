<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pos_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('receipt_number')->unique();
            $table->integer('subtotal_xof')->default(0);
            $table->integer('tax_xof')->default(0);
            $table->integer('discount_xof')->default(0);
            $table->integer('total_xof')->default(0);
            
            $table->string('payment_method')->default('cash'); // cash, card, mobile_money
            $table->integer('amount_paid_xof')->default(0);
            $table->integer('change_returned_xof')->default(0);
            
            $table->string('status')->default('completed'); // completed, refunded
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_sales');
    }
};
