<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'status')) {
                $table->string('status', 50)->default('active')->after('slug');
            }
            if (!Schema::hasColumn('companies', 'size')) {
                $table->string('size', 50)->nullable()->after('status');
            }
            if (!Schema::hasColumn('companies', 'industry')) {
                $table->string('industry', 100)->nullable()->after('size');
            }
            if (!Schema::hasColumn('companies', 'phone')) {
                $table->string('phone', 50)->nullable()->after('industry');
            }
            if (!Schema::hasColumn('companies', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('companies', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable()->after('address');
            }
            if (!Schema::hasColumn('companies', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('trial_ends_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $columns = ['status', 'size', 'industry', 'phone', 'address', 'trial_ends_at', 'suspended_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
