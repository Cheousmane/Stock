<?php

declare(strict_types=1);

namespace Tests\Feature\Api\v1;

use App\Models\Company;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

describe('POS sales', function () {
    function posAuthContext(): array
    {
        $company = Company::factory()->create(['slug' => 'pos-corp']);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'cashier@pos-corp.com',
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;
        $warehouse = Warehouse::create([
            'company_id' => $company->id,
            'name' => 'Main Warehouse',
            'code' => 'MAIN',
        ]);
        $session = PosSession::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'status' => 'open',
            'opening_cash_xof' => 0,
            'opened_at' => now(),
        ]);

        test()->withHeaders([
            'X-Tenant-Slug' => 'pos-corp',
            'Authorization' => 'Bearer '.$token,
        ]);

        return [$company, $user, $warehouse, $session];
    }

    it('rejects an underpaid sale without changing stock', function () {
        [$company, , $warehouse, $session] = posAuthContext();
        $product = Product::factory()->create(['company_id' => $company->id]);
        $stock = WarehouseStock::create([
            'company_id' => $company->id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'available_quantity' => 5,
        ]);

        $response = $this->postJson('/api/v1/pos/sale', [
            'pos_session_id' => $session->id,
            'payment_method' => 'cash',
            'amount_paid_xof' => 999,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price_xof' => 1000,
            ]],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('amount_paid_xof');
        expect($stock->fresh()->quantity)->toBe(5);
        $this->assertDatabaseCount('pos_sales', 0);
    });

    it('checks combined quantities for repeated product lines', function () {
        [$company, , $warehouse, $session] = posAuthContext();
        $product = Product::factory()->create(['company_id' => $company->id]);
        $stock = WarehouseStock::create([
            'company_id' => $company->id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'available_quantity' => 3,
        ]);

        $response = $this->postJson('/api/v1/pos/sale', [
            'pos_session_id' => $session->id,
            'payment_method' => 'cash',
            'amount_paid_xof' => 4000,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price_xof' => 1000],
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price_xof' => 1000],
            ],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('stock');
        expect($stock->fresh()->quantity)->toBe(3);
        $this->assertDatabaseCount('pos_sales', 0);
    });

    it('completes a sale and decrements stock once for repeated product lines', function () {
        [$company, , $warehouse, $session] = posAuthContext();
        $product = Product::factory()->create(['company_id' => $company->id]);
        $stock = WarehouseStock::create([
            'company_id' => $company->id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'available_quantity' => 5,
        ]);

        $response = $this->postJson('/api/v1/pos/sale', [
            'pos_session_id' => $session->id,
            'payment_method' => 'cash',
            'amount_paid_xof' => 3000,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price_xof' => 1000],
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price_xof' => 1000],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.total_xof', 3000);
        expect($stock->fresh()->quantity)->toBe(2);
        $this->assertDatabaseCount('pos_sales', 1);
        $this->assertDatabaseHas('stock_movements', [
            'company_id' => $company->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'before_quantity' => 5,
            'after_quantity' => 2,
        ]);
    });

    it('does not close the same cash session twice', function () {
        [, , , $session] = posAuthContext();

        $this->postJson('/api/v1/pos/session/'.$session->id.'/close', [
            'closing_cash_xof' => 0,
        ])->assertOk();

        $this->postJson('/api/v1/pos/session/'.$session->id.'/close', [
            'closing_cash_xof' => 0,
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('session');
    });
});
