<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_stock_movements_by_product(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        StockMovement::factory()->count(5)->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/products/{$product->id}/stock-movements");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'type', 'quantity', 'user']],
                'meta' => ['current_page', 'total'],
            ]);
    }

    public function test_create_entrada_increases_stock(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/products/{$product->id}/stock-movements", [
                'type' => 'entrada',
                'quantity' => 5,
                'reason' => 'Restock',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('type', 'entrada')
            ->assertJsonPath('quantity', 5);

        $this->assertEquals(15, $product->fresh()->stock);
    }

    public function test_create_salida_decreases_stock(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/products/{$product->id}/stock-movements", [
                'type' => 'salida',
                'quantity' => 3,
                'reason' => 'Sale',
            ]);

        $response->assertStatus(201);
        $this->assertEquals(7, $product->fresh()->stock);
    }

    public function test_salida_rejected_when_stock_insufficient(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'stock' => 5,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/products/{$product->id}/stock-movements", [
                'type' => 'salida',
                'quantity' => 15,
                'reason' => 'Trying to sell too much',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);

        $this->assertEquals(5, $product->fresh()->stock);
    }

    public function test_stock_movement_requires_auth(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this->getJson("/api/products/{$product->id}/stock-movements");

        $response->assertStatus(401);
    }

    public function test_stock_movement_validation(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/products/{$product->id}/stock-movements", [
                'type' => 'invalid',
                'quantity' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'quantity']);
    }
}
