<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->integer('expected_closing_cash_xof')->nullable()->after('closing_cash_xof');
            $table->integer('cash_difference_xof')->nullable()->after('expected_closing_cash_xof');
        });
    }

    public function down(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->dropColumn(['expected_closing_cash_xof', 'cash_difference_xof']);
        });
    }
};
