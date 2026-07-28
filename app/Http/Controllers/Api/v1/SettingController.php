<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('manage_settings');
        $companyId = TenantContext::getCompanyId();
        $settings = Setting::where('company_id', $companyId)->pluck('value', 'key');
        return response()->json($settings, Response::HTTP_OK);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorize('manage_settings');
        $companyId = TenantContext::getCompanyId();
        $data = $request->validate([
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|string|max:10',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'nullable|string|max:50',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
        ]);
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['company_id' => $companyId, 'key' => $key],
                ['value' => $value ?? ''],
            );
        }
        return response()->json(['message' => 'Paramètres mis à jour'], Response::HTTP_OK);
    }

    public function testMail(): JsonResponse
    {
        $this->authorize('manage_settings');
        try {
            $companyId = TenantContext::getCompanyId();
            $settings = Setting::where('company_id', $companyId)->pluck('value', 'key')->toArray();

            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $settings['mail_host'] ?? '',
                'mail.mailers.smtp.port' => $settings['mail_port'] ?? '587',
                'mail.mailers.smtp.username' => $settings['mail_username'] ?? '',
                'mail.mailers.smtp.password' => $settings['mail_password'] ?? '',
                'mail.mailers.smtp.encryption' => $settings['mail_encryption'] ?? 'tls',
                'mail.from.address' => $settings['mail_from_address'] ?? '',
                'mail.from.name' => $settings['mail_from_name'] ?? '',
            ]);

            \Illuminate\Support\Facades\Mail::raw('Test de configuration email réussi', function ($msg) {
                $msg->to(config('mail.from.address'))->subject('Test de configuration');
            });

            return response()->json(['message' => 'Email de test envoyé avec succès'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
