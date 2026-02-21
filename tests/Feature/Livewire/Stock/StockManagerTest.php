<?php

namespace Tests\Feature\Livewire\Stock;

use App\Livewire\Stock\StockManager;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_successfully()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertStatus(200)
            ->assertSee('Manajemen Stok');
    }

    public function test_displays_products()
    {
        Product::factory()->create(['name' => 'Test Product']);
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSee('Test Product');
    }

    public function test_stats_loading()
    {
        $user = User::factory()->create();
        Product::factory()->count(5)->create();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('init') // Trigger lazy load
            ->assertSet('readyToLoad', true)
            ->assertSee('5'); // Check if total product count is visible
    }

    public function test_quick_adjustment()
    {
        $product = Product::factory()->create(['stock' => 10]);
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('quickAdjust', $product->id, 'in')
            ->set('adjustQuantity', 5)
            ->set('adjustReason', 'Restock')
            ->call('saveAdjustment');

        $this->assertEquals(15, $product->refresh()->stock);
    }
}
