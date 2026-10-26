<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Database\Seeders\SuperAdminSeeder;

it('active le super admin pour l’adresse configurée', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create([
        'company_id' => $company->id,
        'email' => '9k5osama@gmail.com',
    ]);

    expect($user->fresh()->is_super_admin)->toBeFalse();

    $this->seed(SuperAdminSeeder::class);

    expect($user->fresh()->is_super_admin)->toBeTrue()
        ->and($user->fresh()->hasRole('admin'))->toBeTrue();
});

it('ne promeut pas les autres comptes', function () {
    $company = Company::factory()->create();
    $other = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'other@example.com',
    ]);

    $this->seed(SuperAdminSeeder::class);

    expect($other->fresh()->is_super_admin)->toBeFalse();
});
