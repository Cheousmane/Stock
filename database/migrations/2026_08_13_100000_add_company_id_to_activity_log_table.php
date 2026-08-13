<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->index('company_id', 'activity_log_company_id_index');
        });

        // Backfill : les logs existants sont rattachés à l'entreprise de leur auteur (utilisateur)
        DB::table('activity_log')
            ->whereNull('company_id')
            ->where('causer_type', 'App\\Models\\User')
            ->update([
                'company_id' => DB::raw('(SELECT u.company_id FROM users u WHERE u.id = activity_log.causer_id)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex('activity_log_company_id_index');
            $table->dropColumn('company_id');
        });
    }
};
