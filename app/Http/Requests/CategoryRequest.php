<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'description' => 'description',
        ];
    }

    public function rules(): array
    {
        $routeParam = $this->route('category');
        $categoryId = $routeParam instanceof Model ? $routeParam->getKey() : $routeParam;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'alpha_dash', 'max:255', Rule::unique('categories', 'slug')->where('company_id', TenantContext::getCompanyId())->ignore($categoryId)],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('company_id', TenantContext::getCompanyId())],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('slug') || ! $this->input('slug')) {
            $this->merge([
                'slug' => Str::slug($this->input('name')),
            ]);
        }
    }
}
