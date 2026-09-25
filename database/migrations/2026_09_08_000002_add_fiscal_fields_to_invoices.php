<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('fiscal_regime')->default('standard'); // standard, eMECeF, FNC, etc.
            $table->string('tax_id_number')->nullable(); // TVA number, RC, etc.
            $table->string('fiscal_reference')->nullable(); // Reference fiscale du document
            $table->boolean('is_fiscal')->default(false); // Indique si le document est un document fiscal
            $table->timestamp('fiscal_issue_date')->nullable(); // Date de passage en status fiscal
            $table->json('fiscal_metadata')->nullable(); // Métadonnées fiscales supplémentaires
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('fiscal_regime');
            $table->dropColumn('tax_id_number');
            $table->dropColumn('fiscal_reference');
            $table->dropColumn('is_fiscal');
            $table->dropColumn('fiscal_issue_date');
            $table->dropColumn('fiscal_metadata');
        });
    }
};