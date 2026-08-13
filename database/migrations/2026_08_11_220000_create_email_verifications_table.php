<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();
        });

        // Les comptes existants sont considérés comme vérifiés (pas de blocage rétroactif)
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()->toDateTimeString()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('email_verifications');
    }
};
