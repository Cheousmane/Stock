<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = '9k5osama@gmail.com';

        $user = User::withoutGlobalScopes()->where('email', $email)->first();

        if (! $user) {
            $this->command?->warn("SuperAdminSeeder : aucun utilisateur avec l'e-mail {$email}.");

            return;
        }

        // is_super_admin n'est volontairement pas dans $fillable (sécurité) :
        // un update() de masse l'ignorerait silencieusement.
        $user->forceFill(['is_super_admin' => true])->save();

        $companyId = $user->company_id;

        if ($companyId === null) {
            $this->command?->warn("SuperAdminSeeder : {$email} n'a pas d'entreprise, rôle admin non assigné.");

            return;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($companyId);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionSeeder::allPermissions() as $permission) {
            Permission::updateOrCreate(
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

        $this->command?->info("Super admin activé pour {$email}.");
    }
}
