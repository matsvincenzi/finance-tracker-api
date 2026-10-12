<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_names_are_unique_per_user_and_type(): void
    {
        $user = $this->signIn();
        Category::factory()->for($user)->create(['name' => 'Gifts', 'type' => 'expense']);
        Category::factory()->create(['name' => 'Bonus', 'type' => 'income']);

        $this->postJson('/api/v1/categories', ['name' => 'Gifts', 'type' => 'expense'])
            ->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->postJson('/api/v1/categories', ['name' => 'Gifts', 'type' => 'income'])->assertCreated();
        $this->postJson('/api/v1/categories', ['name' => 'Bonus', 'type' => 'income'])->assertCreated();
    }

    public function test_type_cannot_change_after_creation(): void
    {
        $user = $this->signIn();
        $category = Category::factory()->for($user)->create(['type' => 'expense']);

        $response = $this->patchJson("/api/v1/categories/{$category->id}", ['type' => 'income']);

        $response->assertUnprocessable()->assertJsonValidationErrors('type');
    }
}
