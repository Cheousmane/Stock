<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Données gelées lors d'un retour à un plan inférieur : elles restent en
     * base mais sont exclues de l'exploitation tant que le quota est dépassé.
     */
    public function up(): void
    {
        foreach (['products', 'warehouses', 'invoices', 'users'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'frozen_at')) {
                    $table->timestamp('frozen_at')->nullable()->index();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['products', 'warehouses', 'invoices', 'users'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'frozen_at')) {
                    $table->dropColumn('frozen_at');
                }
            });
        }
    }
};
