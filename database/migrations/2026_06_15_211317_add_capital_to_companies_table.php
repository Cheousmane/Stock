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
        Schema::table('companies', function (Blueprint $table) {
            $table->bigInteger('manual_capital')->nullable()->after('currency_code');
            $table->timestamp('capital_updated_at')->nullable()->after('manual_capital');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['manual_capital', 'capital_updated_at']);
        });
    }
};
