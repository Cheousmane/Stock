<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:500'],
            'category' => ['required', 'string', 'in:fournitures,loyer,utilities,salaire,transport,marketing,autre'],
            'amount' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date'],
        ];
    }
}
