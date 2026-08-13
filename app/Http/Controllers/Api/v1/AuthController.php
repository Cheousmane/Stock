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
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
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
    public function register(
        RegisterCompanyRequest $request,
        RegisterCompanyAction $action,
        EmailVerificationService $verification,
    ): JsonResponse {
        $dto = RegisterCompanyDTO::fromRequest($request->validated());

        $result = $action->execute($dto);

        // Envoi du code de vérification e-mail (anti-bots) : pas de token tant que l'e-mail n'est pas confirmé
        $verification->sendCode($result['user']->email, $result['user']->name);

        return response()->json([
            'user' => new UserResource($result['user']->load('company', 'roles')),
            'requires_verification' => true,
            'message' => 'Compte créé. Un code de vérification a été envoyé à votre adresse e-mail.',
        ], Response::HTTP_CREATED);
    }

    /**
     * Confirm the registered account with the emailed code.
     */
    public function verifyEmail(Request $request, EmailVerificationService $verification): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = User::withoutGlobalScopes()
            ->where('email', $validated['email'])
            ->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => ['Aucun compte ne correspond à cette adresse e-mail.'],
            ]);
        }

        if ($user->email_verified_at !== null) {
            throw ValidationException::withMessages([
                'email' => ['Cette adresse e-mail est déjà vérifiée.'],
            ]);
        }

        $result = $verification->verify($validated['email'], $validated['code']);

        if ($result['status'] !== 'ok') {
            throw ValidationException::withMessages([
                'code' => [$result['message']],
            ]);
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        // Generate Sanctum API token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load('company', 'roles')),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], Response::HTTP_OK);
    }

    /**
     * Resend the verification code (rate-limited per email).
     */
    public function resendVerification(Request $request, EmailVerificationService $verification): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $key = 'resend-verification:' . strtolower($validated['email']);

        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'email' => ['Trop de demandes. Réessayez dans quelques minutes.'],
            ]);
        }

        RateLimiter::hit($key, 300);

        if (! $verification->canResend($validated['email'])) {
            throw ValidationException::withMessages([
                'email' => ['Un code a déjà été envoyé récemment. Attendez quelques secondes.'],
            ]);
        }

        $user = User::withoutGlobalScopes()
            ->where('email', $validated['email'])
            ->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => ['Aucun compte ne correspond à cette adresse e-mail.'],
            ]);
        }

        if ($user->email_verified_at !== null) {
            throw ValidationException::withMessages([
                'email' => ['Cette adresse e-mail est déjà vérifiée.'],
            ]);
        }

        $verification->sendCode($user->email, $user->name);

        return response()->json([
            'message' => 'Un nouveau code de vérification a été envoyé.',
        ], Response::HTTP_OK);
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

        $user->loadMissing('company', 'roles', 'permissions');

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
