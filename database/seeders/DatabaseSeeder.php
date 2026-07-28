<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            CurrencySeeder::class,
            PlanSeeder::class,
            PermissionSeeder::class,
        ]);

        $company = \App\Models\Company::factory()->create();

        $this->call(RoleSeeder::class);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create([
            'company_id' => $company->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $user->assignRole('admin');
    }
}
