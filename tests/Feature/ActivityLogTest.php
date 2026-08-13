<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

describe('Activity log tenant isolation', function () {

    it('stamps company_id on activity when created in tenant context', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user);
        app(\App\Support\TenantContext::class)->setCompany($company);

        activity()->event('created')->log('Invoice created');

        $activity = Activity::latest()->first();
        expect($activity->company_id)->toBe($company->id);
    });

    it('only returns logs of the current tenant', function () {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $tokenA = $userA->createToken('auth_token')->plainTextToken;

        Activity::create(['company_id' => $companyA->id, 'description' => 'Log A', 'event' => 'created', 'log_name' => 'default']);
        Activity::create(['company_id' => $companyB->id, 'description' => 'Log B', 'event' => 'created', 'log_name' => 'default']);
        Activity::create(['company_id' => null, 'description' => 'Log système', 'event' => 'created', 'log_name' => 'default']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $tokenA,
        ])->getJson('/api/v1/activity-logs');

        $response->assertStatus(Response::HTTP_OK);

        $descriptions = collect($response->json('data'))->pluck('description')->all();
        expect($descriptions)->toContain('Log A');
        expect($descriptions)->not->toContain('Log B');
        expect($descriptions)->not->toContain('Log système');
    });

});
