<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->string('contract_type', 30)->default('cdi'); // cdi, cdd, stage, freelance
            $table->date('hire_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('base_salary_xof')->default(0);
            $table->string('payment_method', 30)->default('bank'); // bank, cash, mobile_money
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('status', 20)->default('active'); // active, suspended, terminated
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
