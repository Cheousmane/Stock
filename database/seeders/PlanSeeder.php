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
                'description' => 'Pour les petites entreprises qui démarrent.',
                'stripe_price_id' => null,
                'price_xof' => 0,
                'currency' => 'XOF',
                'trial_days' => 0,
                'features' => [
                    'Facturation de base',
                    'Gestion des produits',
                    'Gestion des clients',
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
                'description' => 'Pour les entreprises en croissance avec plus de besoins.',
                'stripe_price_id' => null,
                'price_xof' => 15000,
                'currency' => 'XOF',
                'trial_days' => 14,
                'features' => [
                    'Tout le plan Free',
                    'Jusqu\'à 3 utilisateurs',
                    'Transferts de stock',
                    'Bons de livraison',
                    'Devis',
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
                'description' => 'Pour les entreprises établies à fort volume.',
                'stripe_price_id' => null,
                'price_xof' => 35000,
                'currency' => 'XOF',
                'trial_days' => 14,
                'features' => [
                    'Tout le plan Starter',
                    'Jusqu\'à 20 utilisateurs',
                    'Produits illimités',
                    'Factures illimitées',
                    'Multi-entrepôts',
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
                'description' => 'Pour les grandes organisations avec des besoins spécifiques.',
                'stripe_price_id' => null,
                'price_xof' => 100000,
                'currency' => 'XOF',
                'trial_days' => 14,
                'features' => [
                    'Tout le plan Pro',
                    'Utilisateurs illimités',
                    'Produits illimités',
                    'Factures illimitées',
                    'Entrepôts illimités',
                    'Support prioritaire',
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
