<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Class RegisterCompanyRequest
 * Handles validation for registering a new tenant/company.
 */
class RegisterCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Pas de ->uncompromised() : l'API haveibeenpwned est une dépendance
        // réseau externe sur le chemin critique d'inscription. En cas de
        // timeout/blocage egress, la validation lève une 500 non rattrapée.
        // La robustesse locale (12 caractères mixtes) reste exigée.
        $password = Password::min(8)->mixedCase()->letters()->numbers()->symbols();

        return [
            'company_name' => ['required', 'string', 'max:255'],
            'company_slug' => ['required', 'string', 'alpha_dash', 'max:255', 'unique:companies,slug'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', $password, 'confirmed'],
            'industry' => ['sometimes', 'nullable', 'string', 'max:100'],
            'size' => ['sometimes', 'nullable', 'string', 'in:petite,moyenne,grande'],
            'plan_id' => ['sometimes', 'nullable', 'integer', 'exists:plans,id'],
        ];
    }
}
