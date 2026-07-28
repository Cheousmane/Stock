<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PasswordResetController extends Controller
{
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return response()->json(['message' => 'Si un compte est associé à cette adresse, un lien de réinitialisation vous a été envoyé.'], Response::HTTP_OK);
        }

        $token = Str::random(60);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['email' => $user->email, 'token' => Hash::make($token), 'created_at' => now()],
        );

        $user->notify(new ResetPasswordNotification($token, $user->email));

        return response()->json(['message' => 'Si un compte est associé à cette adresse, un lien de réinitialisation vous a été envoyé.'], Response::HTTP_OK);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->input('email'))
            ->first();

        if (!$record || !Hash::check($request->input('token'), $record->token)) {
            return response()->json(['message' => 'Token de réinitialisation invalide ou expiré.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->input('email'))->delete();
            return response()->json(['message' => 'Token de réinitialisation expiré.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $user->update(['password' => Hash::make($request->input('password'))]);

        DB::table('password_reset_tokens')->where('email', $request->input('email'))->delete();

        return response()->json(['message' => 'Mot de passe réinitialisé avec succès.'], Response::HTTP_OK);
    }
}
