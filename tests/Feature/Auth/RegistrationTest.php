<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Mail\VerificationCodeMail;
use App\Models\Company;
use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

describe('Registration', function () {

    it('can register a user and company', function () {
        Mail::fake();

        $payload = [
            'company_name' => 'Test Corp',
            'company_slug' => 'test-corp',
            'name' => 'John Doe',
            'email' => 'john@test-corp.com',
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

        $this->assertDatabaseHas('companies', ['slug' => 'test-corp']);
        $this->assertDatabaseHas('users', ['email' => 'john@test-corp.com']);

        Mail::assertSent(VerificationCodeMail::class, fn ($mail) => $mail->hasTo('john@test-corp.com'));
    });

    it('still creates the account when SMTP sending fails', function () {
        $this->mock(\App\Services\EmailVerificationService::class, function ($mock) {
            $mock->shouldReceive('sendCode')->once()->andThrow(new \Exception('smtp down'));
        });

        $payload = [
            'company_name' => 'Smtp Fail Corp',
            'company_slug' => 'smtp-fail-corp',
            'name' => 'Jane Doe',
            'email' => 'jane@smtp-fail-corp.com',
            'password' => 'Test@12345',
            'password_confirmation' => 'Test@12345',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonPath('email_sent', false);

        $this->assertDatabaseHas('companies', ['slug' => 'smtp-fail-corp']);
        $this->assertDatabaseHas('users', ['email' => 'jane@smtp-fail-corp.com']);
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['company_name', 'company_slug', 'name', 'email', 'password']);
    });

    it('validates password confirmation', function () {
        $payload = [
            'company_name' => 'Test Corp',
            'company_slug' => 'test-corp',
            'name' => 'John Doe',
            'email' => 'john@test-corp.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['password']);
    });

    it('does not return a token until the email is verified', function () {
        $payload = [
            'company_name' => 'Token Corp',
            'company_slug' => 'token-corp',
            'name' => 'Token User',
            'email' => 'token@test.com',
            'password' => 'Test@12345',
            'password_confirmation' => 'Test@12345',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonPath('requires_verification', true);

        expect($response->json('access_token'))->toBeNull();

        $user = User::withoutGlobalScopes()->where('email', 'token@test.com')->first();
        expect($user->email_verified_at)->toBeNull();
    });

    it('rejects duplicate company slug', function () {
        Company::factory()->create(['slug' => 'duplicate-corp']);

        $payload = [
            'company_name' => 'Duplicate Corp',
            'company_slug' => 'duplicate-corp',
            'name' => 'John Doe',
            'email' => 'john@duplicate.com',
            'password' => 'Test@12345',
            'password_confirmation' => 'Test@12345',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['company_slug']);
    });

});

describe('Email verification', function () {

    it('verifies the email with a valid code and returns a token', function () {
        $company = Company::factory()->create();
        $user = User::factory()->unverified()->create([
            'company_id' => $company->id,
            'email' => 'verify@test.com',
        ]);

        EmailVerification::create([
            'email' => 'verify@test.com',
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        $response = $this->postJson('/api/v1/auth/verify-email', [
            'email' => 'verify@test.com',
            'code' => '123456',
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['user', 'access_token', 'token_type']);

        $this->assertDatabaseHas('users', ['email' => 'verify@test.com', 'email_verified_at' => $user->fresh()->email_verified_at]);
        expect($user->fresh()->email_verified_at)->not->toBeNull();
        $this->assertDatabaseMissing('email_verifications', ['email' => 'verify@test.com']);
    });

    it('rejects an invalid code', function () {
        $company = Company::factory()->create();
        User::factory()->unverified()->create([
            'company_id' => $company->id,
            'email' => 'badcode@test.com',
        ]);

        EmailVerification::create([
            'email' => 'badcode@test.com',
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        $response = $this->postJson('/api/v1/auth/verify-email', [
            'email' => 'badcode@test.com',
            'code' => '000000',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        expect(User::withoutGlobalScopes()->where('email', 'badcode@test.com')->first()->email_verified_at)->toBeNull();
    });

    it('resends the verification code', function () {
        Mail::fake();

        $company = Company::factory()->create();
        User::factory()->unverified()->create([
            'company_id' => $company->id,
            'email' => 'resend@test.com',
        ]);

        $response = $this->postJson('/api/v1/auth/resend-verification', [
            'email' => 'resend@test.com',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        Mail::assertSent(VerificationCodeMail::class, fn ($mail) => $mail->hasTo('resend@test.com'));
    });

    it('blocks login when the email is not verified', function () {
        $company = Company::factory()->create();
        User::factory()->unverified()->create([
            'company_id' => $company->id,
            'email' => 'unverified@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'unverified@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email_verification']);
    });

});
