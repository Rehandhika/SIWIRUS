<?php

namespace Tests\Feature\ShuPoint;

use App\Livewire\ShuPoint\StudentDetail;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ShuPermissionBehaviorTest extends TestCase
{
    use RefreshDatabase;

    public function test_redeem_and_adjust_actions_show_toast_instead_of_403_when_not_allowed(): void
    {
        Permission::create(['name' => 'lihat_poin_shu']);
        Permission::create(['name' => 'kelola_poin_shu']);

        $user = User::factory()->create();
        $user->givePermissionTo(['lihat_poin_shu']);
        $this->actingAs($user);

        $student = Student::factory()->create(['points_balance' => 100]);

        Livewire::actingAs($user)
            ->test(StudentDetail::class, ['student' => $student])
            ->set('redeemPoints', 10)
            ->call('redeem')
            ->assertDispatched('toast', type: 'error')
            ->set('adjustPoints', 10)
            ->call('adjust')
            ->assertDispatched('toast', type: 'error');
    }
}

