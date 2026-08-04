<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', '9k5osama@gmail.com')->first();

        if (!$user) {
            return;
        }

        $user->update(['is_super_admin' => true]);

        $companyId = $user->company_id;

        app(PermissionRegistrar::class)->setPermissionsTeamId($companyId);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionSeeder::allPermissions() as $permission) {
            \Spatie\Permission\Models\Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['name' => $permission, 'guard_name' => 'web'],
            );
        }

        $adminRole = Role::updateOrCreate(
            ['name' => 'admin', 'guard_name' => 'web', 'company_id' => $companyId],
            ['name' => 'admin', 'guard_name' => 'web', 'company_id' => $companyId],
        );

        $adminRole->givePermissionTo(PermissionSeeder::adminPermissions());

        $user->assignRole($adminRole);
    }
}
