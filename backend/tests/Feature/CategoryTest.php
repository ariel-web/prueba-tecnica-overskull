<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_categories(): void
    {
        $user = User::factory()->create();
        Category::factory()->count(25)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/categories?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'description', 'status']],
                'meta' => ['current_page', 'total', 'per_page'],
            ]);
    }

    public function test_index_requires_auth(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertStatus(401);
    }

    public function test_store_creates_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/categories', [
            'name' => 'Electronics',
            'description' => 'Electronic items',
            'status' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'Electronics');
    }

    public function test_store_validation_unique_name(): void
    {
        $user = User::factory()->create();
        Category::factory()->create(['name' => 'Existing']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/categories', [
            'name' => 'Existing',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_show_returns_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $category->id);
    }

    public function test_update_modifies_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/categories/{$category->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Updated Name');
    }

    public function test_delete_removes_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
