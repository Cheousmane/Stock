<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Actions\Category\CreateCategoryAction;
use App\Actions\Category\UpdateCategoryAction;
use App\DTOs\CategoryDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);
        $categories = Category::query()
            ->when($search = $request->input('search'), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($isActive = $request->input('is_active'), function ($query) use ($isActive) {
                $query->where('is_active', $isActive === '1');
            })
            ->paginate($request->integer('per_page', 15));

        return CategoryResource::collection($categories)->response();
    }

    public function store(CategoryRequest $request, CreateCategoryAction $action): JsonResponse
    {
        $this->authorize('create', Category::class);
        $dto = CategoryDTO::fromArray($request->validated());
        $category = $action->execute($dto);
        return response()->json(new CategoryResource($category), Response::HTTP_CREATED);
    }

    public function show(Category $category): JsonResponse
    {
        $this->authorize('view', $category);
        return response()->json(new CategoryResource($category), Response::HTTP_OK);
    }

    public function update(CategoryRequest $request, Category $category, UpdateCategoryAction $action): JsonResponse
    {
        $this->authorize('update', $category);
        $dto = CategoryDTO::fromArray($request->validated());
        $category = $action->execute($category, $dto);
        return response()->json(new CategoryResource($category), Response::HTTP_OK);
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('delete', $category);
        $category->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
