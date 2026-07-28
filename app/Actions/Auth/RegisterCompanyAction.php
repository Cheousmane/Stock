<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTOs\RegisterCompanyDTO;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RegisterCompanyAction
{
    public function execute(RegisterCompanyDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $company = Company::create([
                'name' => $dto->companyName,
                'slug' => $dto->companySlug,
            ]);

            app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);

            $this->createGlobalPermissions();
            $this->createCompanyRoles($company);

            $user = User::create([
                'company_id' => $company->id,
                'name' => $dto->userName,
                'email' => $dto->userEmail,
                'password' => Hash::make($dto->userPassword),
            ]);

            $adminRole = Role::where('name', 'admin')
                ->where('company_id', $company->id)
                ->first();

            $user->assignRole($adminRole);

            return [
                'company' => $company,
                'user' => $user,
            ];
        });
    }

    private function createGlobalPermissions(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        foreach (PermissionSeeder::allPermissions() as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['name' => $permission, 'guard_name' => 'web'],
            );
        }
    }

    private function createCompanyRoles(Company $company): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            'admin' => PermissionSeeder::adminPermissions(),
            'manager' => PermissionSeeder::managerPermissions(),
            'accountant' => PermissionSeeder::accountantPermissions(),
            'warehouse_manager' => PermissionSeeder::warehouseManagerPermissions(),
            'sales' => PermissionSeeder::salesPermissions(),
            'employee' => PermissionSeeder::employeePermissions(),
        ];

        foreach ($roles as $name => $permissions) {
            $role = Role::create([
                'name' => $name,
                'guard_name' => 'web',
                'company_id' => $company->id,
            ]);
            $role->givePermissionTo($permissions);
        }
    }
}
