<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_products(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Product::factory()->count(25)->create(['category_id' => $category->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/products?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'price', 'stock', 'category']],
                'meta' => ['current_page', 'total', 'per_page'],
            ]);
    }

    public function test_index_requires_auth(): void
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(401);
    }

    public function test_index_filters_by_name(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Product::factory()->create(['name' => 'Laptop Pro', 'category_id' => $category->id]);
        Product::factory()->create(['name' => 'Phone Mini', 'category_id' => $category->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/products?q=Laptop');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Laptop Pro');
    }

    public function test_index_filters_by_category(): void
    {
        $user = User::factory()->create();
        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();
        Product::factory()->count(5)->create(['category_id' => $cat1->id]);
        Product::factory()->count(3)->create(['category_id' => $cat2->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/products?category_id={$cat2->id}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_store_creates_product(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/products', [
            'name' => 'Test Product',
            'description' => 'A test product',
            'price' => 99.99,
            'stock' => 50,
            'category_id' => $category->id,
            'status' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'Test Product')
            ->assertJsonPath('price', '99.99');
    }

    public function test_store_validation_requires_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/products', [
            'name' => 'Test Product',
            'price' => 99.99,
            'stock' => 50,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_show_returns_product_with_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $product->id)
            ->assertJsonStructure(['category']);
    }

    public function test_update_modifies_product(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/products/{$product->id}", [
            'price' => 149.99,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('price', '149.99');
    }

    public function test_delete_removes_product(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
