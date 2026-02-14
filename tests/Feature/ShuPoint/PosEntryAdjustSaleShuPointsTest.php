<?php

namespace Tests\Feature\ShuPoint;

use App\Livewire\Cashier\PosEntry;
use App\Models\Sale;
use App\Models\Student;
use App\Models\User;
use App\Services\ShuPointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PosEntryAdjustSaleShuPointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_entry_can_adjust_shu_points_for_a_sale(): void
    {
        Permission::create(['name' => 'kelola_poin_shu']);
        Role::create(['name' => 'Super Admin']);

        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $user->givePermissionTo('kelola_poin_shu');
        $this->actingAs($user);

        $student = Student::factory()->create(['points_balance' => 0]);

        $sale = Sale::create([
            'cashier_id' => $user->id,
            'invoice_number' => 'INV-20260131-0001',
            'date' => now()->format('Y-m-d'),
            'total_amount' => 10000,
            'payment_method' => 'cash',
            'payment_amount' => 10000,
            'change_amount' => 0,
            'shu_points_earned' => 0,
            'shu_percentage_bps' => 0,
        ]);

        $earned = app(ShuPointService::class)->awardPointsForSale($sale, $student, 100);
        $this->assertSame(100, $earned);
        $student->refresh();
        $this->assertSame(100, $student->points_balance);

        Livewire::actingAs($user)
            ->test(PosEntry::class)
            ->call('openShuAdjustment', $sale->id)
            ->set('shuAdjustNewPoints', 50)
            ->set('shuAdjustNotes', 'koreksi manual')
            ->call('saveShuAdjustment')
            ->assertDispatched('toast', type: 'success');

        $sale->refresh();
        $student->refresh();

        $this->assertSame(50, (int) $sale->shu_points_earned);
        $this->assertSame(50, $student->points_balance);

        $this->assertDatabaseHas('shu_point_transactions', [
            'sale_id' => $sale->id,
            'type' => 'earn',
            'points' => 50,
        ]);
    }
}
