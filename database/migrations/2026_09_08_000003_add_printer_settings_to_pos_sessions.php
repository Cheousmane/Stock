<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->string('printer_model')->nullable()->after('notes');
            $table->string('printer_ip')->nullable()->after('printer_model');
            $table->boolean('printer_auto_print')->default(true)->after('printer_ip');
        });
    }

    public function down(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->dropColumn('printer_model');
            $table->dropColumn('printer_ip');
            $table->dropColumn('printer_auto_print');
        });
    }
};