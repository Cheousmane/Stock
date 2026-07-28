<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Actions\Auth\LoginAction;
use App\Actions\Auth\RegisterCompanyAction;
use App\DTOs\LoginDTO;
use App\DTOs\RegisterCompanyDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterCompanyRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

/**
 * Class AuthController
 * Manages tenant registration and authentication API endpoints.
 */
class AuthController extends Controller
{
    /**
     * Register a new company (tenant) and its owner.
     */
    public function register(RegisterCompanyRequest $request, RegisterCompanyAction $action): JsonResponse
    {
        $dto = RegisterCompanyDTO::fromRequest($request->validated());

        $result = $action->execute($dto);

        // Generate Sanctum API token
        $token = $result['user']->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($result['user']->load('company', 'roles')),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], Response::HTTP_CREATED);
    }

    /**
     * Authenticate a user scoped by the active tenant.
     */
    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $dto = LoginDTO::fromRequest($request->validated());

        $user = $action->execute($dto);

        // Generate Sanctum API token
        $tokenExpiration = $dto->remember ? Carbon::now()->addDays(30) : null;
        $token = $user->createToken('auth_token', ['*'], $tokenExpiration)->plainTextToken;

        $user->load('company', 'roles');

        return response()->json([
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], Response::HTTP_OK);
    }

    /**
     * Retrieve the authenticated user's details.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()->load('company', 'roles')),
        ]);
    }

    /**
     * Heartbeat endpoint to update last_seen_at.
     */
    public function ping(Request $request): JsonResponse
    {
        $request->user()->withoutEvents(fn () => $request->user()->updateQuietly(['last_seen_at' => now()]));

        return response()->json(['status' => 'ok']);
    }

    /**
     * Update the authenticated user's locale preference.
     */
    public function updateLocale(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'max:10'],
        ]);

        $request->user()->update(['locale' => $validated['locale']]);

        return response()->json([
            'locale' => $validated['locale'],
            'message' => 'Locale updated.',
        ]);
    }

    /**
     * Log out the user and revoke their token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie.',
        ]);
    }
}
