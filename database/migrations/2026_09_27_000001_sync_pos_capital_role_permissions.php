<?php

declare(strict_types=1);

use App\Models\Company;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Ajoute les nouvelles permissions (access_pos, view_capital,
     * validate_quote, send_invoice) aux rôles des entreprises existantes.
     * Additif uniquement : aucune permission existante n'est retirée.
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        foreach (PermissionSeeder::allPermissions() as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['name' => $permission, 'guard_name' => 'web'],
            );
        }

        $roles = [
            'admin' => PermissionSeeder::adminPermissions(),
            'manager' => PermissionSeeder::managerPermissions(),
            'accountant' => PermissionSeeder::accountantPermissions(),
            'warehouse_manager' => PermissionSeeder::warehouseManagerPermissions(),
            'sales' => PermissionSeeder::salesPermissions(),
            'employee' => PermissionSeeder::employeePermissions(),
        ];

        Company::query()->select('id')->chunkById(100, function ($companies) use ($roles) {
            foreach ($companies as $company) {
                app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);

                foreach ($roles as $name => $permissions) {
                    $role = Role::where('name', $name)
                        ->where('company_id', $company->id)
                        ->first();

                    if ($role !== null) {
                        $role->givePermissionTo($permissions);
                    }
                }
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'access_pos')->where('guard_name', 'web')->first();

        if ($permission !== null) {
            DB::table('role_has_permissions')->where('permission_id', $permission->id)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permission->id)->delete();
            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
