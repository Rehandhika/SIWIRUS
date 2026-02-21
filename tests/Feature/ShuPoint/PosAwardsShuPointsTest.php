<?php

namespace Tests\Feature\ShuPoint;

use App\Livewire\Cashier\PointOfSale;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Sale;
use App\Models\ShuPointTransaction;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class PosAwardsShuPointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_awards_shu_points_when_student_nim_provided(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Set conversion amount: 10000 rupiah = 1 point
        // So buying 10000 will earn 1 point
        Setting::set('shu_point_conversion_amount', '10000');
        Cache::forget('shu_point_conversion_amount');

        $student = Student::factory()->create([
            'nim' => '222413550',
            'points_balance' => 0,
        ]);

        $product = Product::create([
            'name' => 'Produk Test',
            'price' => 10000,
            'cost_price' => 0,
            'stock' => 10,
            'min_stock' => 1,
            'status' => 'active',
            'has_variants' => false,
            'is_public' => true,
            'is_featured' => false,
            'display_order' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(Pos::class)
            ->call('addToCart', $product->id)
            ->call('openPayment')
            ->set('paymentMethod', 'cash')
            ->set('paymentAmount', 10000)
            ->set('studentNim', $student->nim)
            ->call('processPayment')
            ->assertHasNoErrors();

        $sale = Sale::first();
        $this->assertNotNull($sale);

        // With conversion_amount = 10000, buying 10000 earns 1 point
        $this->assertDatabaseHas('shu_point_transactions', [
            'student_id' => $student->id,
            'sale_id' => $sale->id,
            'type' => 'earn',
            'amount' => 10000,
            'percentage_bps' => 10000, // Stores conversion amount, not percentage
            'points' => 1,
        ]);

        $student->refresh();
        $this->assertSame(1, $student->points_balance);

        $this->assertDatabaseHas('sales', [
            'student_id' => $student->id,
            'shu_points_earned' => 1,
            'shu_percentage_bps' => 10000, // Stores conversion amount
        ]);
    }

    public function test_pos_does_not_award_points_when_student_nim_empty(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Set conversion amount: 10000 rupiah = 1 point
        Setting::set('shu_point_conversion_amount', '10000');
        Cache::forget('shu_point_conversion_amount');

        $product = Product::create([
            'name' => 'Produk Test 2',
            'price' => 5000,
            'cost_price' => 0,
            'stock' => 10,
            'min_stock' => 1,
            'status' => 'active',
            'has_variants' => false,
            'is_public' => true,
            'is_featured' => false,
            'display_order' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(Pos::class)
            ->call('addToCart', $product->id)
            ->call('openPayment')
            ->set('paymentMethod', 'cash')
            ->set('paymentAmount', 5000)
            ->set('studentNim', '')
            ->call('processPayment')
            ->assertHasNoErrors();

        $this->assertSame(0, ShuPointTransaction::count());
    }
}
