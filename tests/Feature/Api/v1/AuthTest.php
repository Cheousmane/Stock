<?php

declare(strict_types=1);

namespace Tests\Feature\Api\v1;

use App\Models\Company;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

describe('Auth', function () {

    it('can register a company and user, and requires email verification', function () {
        $payload = [
            'company_name' => 'Acme Corp',
            'company_slug' => 'acme-corp',
            'name' => 'John Doe',
            'email' => 'john@acme.com',
            'password' => 'Test@12345',
            'password_confirmation' => 'Test@12345',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonPath('requires_verification', true)
            ->assertJsonStructure([
                'user' => ['uuid', 'name', 'email', 'company'],
                'requires_verification',
                'message',
            ]);

        $this->assertDatabaseHas('companies', ['slug' => 'acme-corp']);
        $this->assertDatabaseHas('users', ['email' => 'john@acme.com']);
    });

    it('can login with valid credentials', function () {
        $company = Company::create(['name' => 'Acme', 'slug' => 'acme']);
        User::factory()->create([
            'company_id' => $company->id,
            'email' => 'john@acme.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->withHeaders(['X-Tenant-Slug' => 'acme'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'john@acme.com',
                'password' => 'password123',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'user' => ['uuid', 'name', 'email', 'company'],
                'access_token',
                'token_type',
            ]);
    });

    it('returns 422 when logging in with wrong password', function () {
        $company = Company::create(['name' => 'Acme', 'slug' => 'acme']);
        User::factory()->create([
            'company_id' => $company->id,
            'email' => 'john@acme.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->withHeaders(['X-Tenant-Slug' => 'acme'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'john@acme.com',
                'password' => 'wrong-password',
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });

    it('authenticated user can access /api/v1/auth/me', function () {
        $company = Company::create(['name' => 'Acme', 'slug' => 'acme']);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'john@acme.com',
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/auth/me');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('user.email', 'john@acme.com');
    });

    it('automatically verifies users created by a company admin', function () {
        $company = Company::create(['name' => 'Acme', 'slug' => 'acme']);
        $admin = User::factory()->create(['company_id' => $company->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $adminRole = Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
            'company_id' => $company->id,
        ]);
        $admin->assignRole($adminRole);
        $token = $admin->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/v1/users', [
            'name' => 'Jane Doe',
            'email' => 'jane@acme.com',
            'password' => 'Test@12345',
            'password_confirmation' => 'Test@12345',
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas('users', [
            'email' => 'jane@acme.com',
            'company_id' => $company->id,
        ]);
        expect(User::where('email', 'jane@acme.com')->first()->email_verified_at)->not->toBeNull();
    });

    it('can logout and revoke token', function () {
        $company = Company::create(['name' => 'Acme', 'slug' => 'acme']);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'john@acme.com',
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'acme',
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('message', 'Déconnexion réussie.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    });

    it('sends password reset links to the configured frontend URL', function () {
        config(['app.frontend_url' => 'https://app.example.com/']);

        $notification = new ResetPasswordNotification('reset-token', 'john+test@acme.com');
        $mail = $notification->toMail(new User);

        expect($mail->actionUrl)
            ->toBe('https://app.example.com/reset-password?token=reset-token&email=john%2Btest%40acme.com');
    });

    it('expires password reset tokens after the configured duration', function () {
        config(['auth.passwords.users.expire' => 10]);
        $company = Company::create(['name' => 'Acme', 'slug' => 'acme']);
        User::factory()->create([
            'company_id' => $company->id,
            'email' => 'john@acme.com',
        ]);
        $token = 'reset-token';

        DB::table('password_reset_tokens')->insert([
            'email' => 'john@acme.com',
            'token' => Hash::make($token),
            'created_at' => now()->subMinutes(10),
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'john@acme.com',
            'token' => $token,
            'password' => 'NewPassword@123',
            'password_confirmation' => 'NewPassword@123',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonPath('message', 'Token de réinitialisation expiré.');
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'john@acme.com']);
    });

});
