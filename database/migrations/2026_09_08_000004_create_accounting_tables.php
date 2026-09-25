<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 15)->unique();
            $table->string('name', 100);
            $table->string('type', 30); // asset, liability, equity, income, expense
            $table->string('subtype')->nullable(); // cash, bank, sales, purchases, etc.
            $table->boolean('is_active')->default(true);
            $table->integer('parent_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'code']);
            $table->index(['company_id', 'parent_id']);
        });

        Schema::create('accounting_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounting_accounts')->cascadeOnDelete();
            $table->string('entry_type', 20); // debit, credit
            $table->unsignedBigInteger('amount_xof');
            $table->date('entry_date');
            $table->string('description', 255);
            $table->string('reference_type', 50)->nullable(); // invoice, purchase_order, etc.
            $table->unsignedBigInteger('reference_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'entry_date']);
            $table->index(['company_id', 'account_id']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('accounting_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100); // Journal des ventes, Journal des achats, etc.
            $table->string('code', 10)->unique;
            $table->string('type', 30); // sales, purchases, cash, bank
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'code']);
        });

        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50); // Exercice 2024, Période 1, etc.
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->index(['company_id', 'is_closed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
        Schema::dropIfExists('accounting_journals');
        Schema::dropIfExists('accounting_entries');
        Schema::dropIfExists('accounting_accounts');
    }
};