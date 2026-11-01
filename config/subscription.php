<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Essai freemium
    |--------------------------------------------------------------------------
    | Tout nouveau compte démarre sur le plan gratuit avec un accès complet
    | pendant cette durée. À l'expiration, les quotas du plan gratuit
    | s'appliquent et les données excédentaires sont gelées.
    */
    'trial_days' => (int) env('SUBSCRIPTION_TRIAL_DAYS', 14),

    'free_plan_slug' => 'free',

    /*
    |--------------------------------------------------------------------------
    | Quotas de repli du plan gratuit
    |--------------------------------------------------------------------------
    | Utilisés uniquement si le plan Free n'est pas présent en base (ex. tests
    | ou installation partielle). La source de vérité reste la table plans.
    */
    'fallback_free_quotas' => [
        'max_users' => 5,
        'max_products' => 10,
        'max_invoices' => 5,
        'max_warehouses' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Ressources soumises aux quotas
    |--------------------------------------------------------------------------
    | La clé correspond à la clé "quotas" du plan et à la ressource interne.
    */
    'quota_resources' => [
        'users' => 'max_users',
        'products' => 'max_products',
        'invoices' => 'max_invoices',
        'warehouses' => 'max_warehouses',
    ],
];
