<?php

namespace Tests\Feature\ShuPoint;

use App\Livewire\ShuPoint\Students;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ShuStudentsModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_opens_modal_state(): void
    {
        Permission::create(['name' => 'kelola_poin_shu']);

        $user = User::factory()->create();
        $user->givePermissionTo(['kelola_poin_shu']);
        $this->actingAs($user);

        Livewire::actingAs($user)
            ->test(Students::class)
            ->call('create')
            ->assertSet('showModal', true);
    }
}
