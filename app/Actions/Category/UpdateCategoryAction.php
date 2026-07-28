<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\DTOs\CategoryDTO;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class UpdateCategoryAction
{
    public function execute(Category $category, CategoryDTO $dto): Category
    {
        return DB::transaction(function () use ($category, $dto) {
            $category->update([
                'name' => $dto->name,
                'slug' => $dto->slug,
                'description' => $dto->description,
                'parent_id' => $dto->parent_id,
                'is_active' => $dto->is_active,
            ]);

            return $category->fresh();
        });
    }
}
