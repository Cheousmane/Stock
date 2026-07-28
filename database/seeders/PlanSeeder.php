<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'For small businesses just getting started.',
                'stripe_price_id' => null,
                'price_xof' => 0,
                'currency' => 'XOF',
                'trial_days' => 0,
                'features' => [
                    'Basic invoicing',
                    'Product management',
                    'Customer management',
                ],
                'quotas' => [
                    'max_users' => 5,
                    'max_products' => 10,
                    'max_invoices' => 5,
                    'max_warehouses' => 1,
                ],
                'is_active' => true,
                'sort' => 0,
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'For growing businesses with more needs.',
                'stripe_price_id' => null,
                'price_xof' => 15000,
                'currency' => 'XOF',
                'trial_days' => 14,
                'features' => [
                    'Everything in Free',
                    'Up to 3 users',
                    'Stock transfers',
                    'Delivery notes',
                    'Quotes',
                ],
                'quotas' => [
                    'max_users' => 10,
                    'max_products' => 100,
                    'max_invoices' => 50,
                    'max_warehouses' => 2,
                ],
                'is_active' => true,
                'sort' => 1,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'For established businesses with high volume.',
                'stripe_price_id' => null,
                'price_xof' => 35000,
                'currency' => 'XOF',
                'trial_days' => 14,
                'features' => [
                    'Everything in Starter',
                    'Up to 20 users',
                    'Unlimited products',
                    'Unlimited invoices',
                    'Multiple warehouses',
                ],
                'quotas' => [
                    'max_users' => 20,
                    'max_products' => -1,
                    'max_invoices' => -1,
                    'max_warehouses' => 5,
                ],
                'is_active' => true,
                'sort' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'For large organizations with custom needs.',
                'stripe_price_id' => null,
                'price_xof' => 100000,
                'currency' => 'XOF',
                'trial_days' => 14,
                'features' => [
                    'Everything in Pro',
                    'Unlimited users',
                    'Unlimited products',
                    'Unlimited invoices',
                    'Unlimited warehouses',
                    'Priority support',
                ],
                'quotas' => [
                    'max_users' => -1,
                    'max_products' => -1,
                    'max_invoices' => -1,
                    'max_warehouses' => -1,
                ],
                'is_active' => true,
                'sort' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::firstOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
