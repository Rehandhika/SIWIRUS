<?php

namespace Tests\Feature\ShuPoint;

use App\Livewire\ShuPoint\StudentDetail;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RedeemShuPointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_redeem_creates_transaction_and_decreases_balance(): void
    {
        Permission::create(['name' => 'lihat_poin_shu']);
        Permission::create(['name' => 'kelola_poin_shu']);

        $user = User::factory()->create();
        $user->givePermissionTo(['lihat_poin_shu', 'kelola_poin_shu']);
        $this->actingAs($user);

        $student = Student::factory()->create(['points_balance' => 500]);

        Livewire::actingAs($user)
            ->test(StudentDetail::class, ['student' => $student])
            ->set('redeemPoints', 200)
            ->set('redeemCashAmount', 10000)
            ->set('redeemNotes', 'Test pencairan')
            ->call('redeem');

        $student->refresh();
        $this->assertSame(300, $student->points_balance);

        $this->assertDatabaseHas('shu_point_transactions', [
            'student_id' => $student->id,
            'type' => 'redeem',
            'points' => -200,
            'cash_amount' => 10000,
            'notes' => 'Test pencairan',
        ]);
    }

    public function test_redeem_fails_when_balance_insufficient(): void
    {
        Permission::create(['name' => 'lihat_poin_shu']);
        Permission::create(['name' => 'kelola_poin_shu']);

        $user = User::factory()->create();
        $user->givePermissionTo(['lihat_poin_shu', 'kelola_poin_shu']);
        $this->actingAs($user);

        $student = Student::factory()->create(['points_balance' => 50]);

        Livewire::actingAs($user)
            ->test(StudentDetail::class, ['student' => $student])
            ->set('redeemPoints', 200)
            ->call('redeem');

        $student->refresh();
        $this->assertSame(50, $student->points_balance);
        $this->assertDatabaseCount('shu_point_transactions', 0);
    }
}

