<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\DTOs\CategoryDTO;
use App\Models\Category;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreateCategoryAction
{
    public function execute(CategoryDTO $dto): Category
    {
        return DB::transaction(function () use ($dto) {
            $category = Category::create([
                'company_id' => TenantContext::getCompanyId(),
                'name' => $dto->name,
                'slug' => $dto->slug,
                'description' => $dto->description,
                'parent_id' => $dto->parent_id,
                'is_active' => $dto->is_active,
            ]);

            return $category;
        });
    }
}
