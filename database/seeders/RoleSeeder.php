<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $companies = \App\Models\Company::all();
        foreach ($companies as $company) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);

            $roles = [
                'admin' => PermissionSeeder::adminPermissions(),
                'manager' => PermissionSeeder::managerPermissions(),
                'accountant' => PermissionSeeder::accountantPermissions(),
                'warehouse_manager' => PermissionSeeder::warehouseManagerPermissions(),
                'sales' => PermissionSeeder::salesPermissions(),
                'employee' => PermissionSeeder::employeePermissions(),
            ];

            foreach ($roles as $name => $permissions) {
                $role = Role::updateOrCreate(
                    ['name' => $name, 'guard_name' => 'web', 'company_id' => $company->id],
                    ['name' => $name, 'guard_name' => 'web', 'company_id' => $company->id],
                );
                $role->syncPermissions($permissions);
            }
        }
    }
}
