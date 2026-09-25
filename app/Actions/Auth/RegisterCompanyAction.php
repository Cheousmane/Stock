<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTOs\GoogleRegisterDTO;
use App\DTOs\RegisterCompanyDTO;
use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RegisterCompanyAction
{
    public function execute(RegisterCompanyDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $plan = $dto->planId
                ? Plan::find($dto->planId)
                : Plan::where('slug', 'free')->first();

            // Toute inscription démarre en essai gratuit de 14 jours, accès complet.
            // Le scheduler `companies:manage-statuses` suspend les essais expirés.
            $company = Company::create([
                'name' => $dto->companyName,
                'slug' => $dto->companySlug,
                'industry' => $dto->companyIndustry,
                'size' => $dto->companySize,
                'plan_id' => $plan?->id,
                'status' => 'trial',
                'trial_ends_at' => now()->addDays($plan?->trial_days > 0 ? $plan->trial_days : 14),
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

    /**
     * Create a company + owner from a verified Google identity.
     * The e-mail is pre-verified by Google: random unusable password,
     * email_verified_at set immediately, no verification code sent.
     */
    public function executeWithGoogle(GoogleRegisterDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $plan = $dto->planId
                ? Plan::find($dto->planId)
                : Plan::where('slug', 'free')->first();

            $company = Company::create([
                'name' => $dto->companyName,
                'slug' => $dto->companySlug,
                'industry' => $dto->companyIndustry,
                'size' => $dto->companySize,
                'plan_id' => $plan?->id,
                'status' => 'trial',
                'trial_ends_at' => now()->addDays($plan?->trial_days > 0 ? $plan->trial_days : 14),
            ]);

            app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);

            $this->createGlobalPermissions();
            $this->createCompanyRoles($company);

            $user = User::create([
                'company_id' => $company->id,
                'name' => $dto->userName,
                'email' => $dto->userEmail,
                // Mot de passe aléatoire inutilisable : connexion via Google uniquement.
                // L'utilisateur pourra définir un mot de passe via « mot de passe oublié ».
                'password' => Hash::make(Str::random(40)),
            ]);
            // Assignation directe (hors $fillable volontairement restreint).
            $user->google_id = $dto->googleId;
            $user->email_verified_at = now();
            if ($dto->avatar !== null) {
                $user->avatar = $dto->avatar;
            }
            $user->save();

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
