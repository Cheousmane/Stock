<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('stripe_id')->nullable()->change();
            $table->string('stripe_status')->nullable()->change();

            if (! Schema::hasColumn('subscriptions', 'status')) {
                $table->string('status', 30)->default('active')->after('plan_id');
            }
            if (! Schema::hasColumn('subscriptions', 'starts_at')) {
                $table->timestamp('starts_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('subscriptions', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('ends_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('subscriptions', 'notes')) {
                $table->text('notes')->nullable()->after('created_by');
            }

            $table->index(['company_id', 'status'], 'subscriptions_company_status_index');
        });

        // Les abonnements existants (héritage Stripe) sont normalisés vers les
        // statuts internes afin que l'administration puisse les reprendre.
        DB::table('subscriptions')->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
            ->update(['status' => 'active']);
        DB::table('subscriptions')->where('stripe_status', 'canceled')
            ->update(['status' => 'canceled']);
        DB::table('subscriptions')->whereNull('status')->update(['status' => 'expired']);
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex('subscriptions_company_status_index');

            if (Schema::hasColumn('subscriptions', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('subscriptions', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
            if (Schema::hasColumn('subscriptions', 'starts_at')) {
                $table->dropColumn('starts_at');
            }
            if (Schema::hasColumn('subscriptions', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
