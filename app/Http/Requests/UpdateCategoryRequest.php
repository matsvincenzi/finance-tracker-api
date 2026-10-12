<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Category|null $category */
        $category = $this->route('category');

        return [
            'name' => [
                'sometimes', 'required', 'string', 'max:50',
                Rule::unique(Category::class)
                    ->where('user_id', $this->user()?->id)
                    ->where('type', $category?->type)
                    ->ignore($category),
            ],
            // Transactions copy their type from the category, so changing it would corrupt history.
            'type' => ['prohibited'],
            'color' => ['sometimes', 'nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
