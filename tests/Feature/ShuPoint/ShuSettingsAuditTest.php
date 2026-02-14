<?php

namespace Tests\Feature\ShuPoint;

use App\Livewire\ShuPoint\Settings;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ShuSettingsAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_setting_change_creates_audit_log(): void
    {
        Permission::create(['name' => 'kelola_pengaturan']);

        $user = User::factory()->create();
        $user->givePermissionTo(['kelola_pengaturan']);
        $this->actingAs($user);

        Setting::set('shu_point_percentage_bps', '100');

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->set('percentage', '2.50')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('250', Setting::get('shu_point_percentage_bps'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'update',
            'model' => \App\Models\Setting::class,
        ]);
    }
}

