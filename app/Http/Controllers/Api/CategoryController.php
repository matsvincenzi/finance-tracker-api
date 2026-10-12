<?php

namespace App\Http\Controllers\Api;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Validation\Rule;

final class CategoryController extends Controller
{
    /**
     * List categories, optionally only the income or expense ones.
     */
    public function index(Request $request, #[CurrentUser] User $user): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'type' => ['sometimes', Rule::enum(TransactionType::class)],
        ]);

        $categories = $user->categories()
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $category = $user->categories()->create($request->validated());

        return (new CategoryResource($category))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    #[Authorize('view', 'category')]
    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category);
    }

    /**
     * Rename or recolor a category. Its type cannot change once created.
     */
    #[Authorize('update', 'category')]
    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $category->update($request->validated());

        return new CategoryResource($category);
    }

    /**
     * Delete a category. Categories that still have transactions are rejected with 409.
     */
    #[Authorize('delete', 'category')]
    public function destroy(Category $category): Response
    {
        abort_if(
            $category->transactions()->exists(),
            Response::HTTP_CONFLICT,
            'This category has transactions. Delete or recategorize them before deleting the category.',
        );

        $category->delete();

        return response()->noContent();
    }
}
