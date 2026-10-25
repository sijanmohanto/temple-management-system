<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_can_be_assigned_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('treasurer');

        $this->assertTrue($user->hasRole('treasurer'));
        $this->assertFalse($user->hasRole('volunteer'));
    }

    public function test_super_admin_has_all_permissions_implicitly(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->assertTrue($admin->hasPermission('manage_users'));
        $this->assertTrue($admin->hasPermission('verify_transactions'));
        $this->assertTrue($admin->hasPermission('non_existent_fake_permission'));
    }

    public function test_super_admin_can_access_audit_logs(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)->get('/admin/audit-logs');

        $response->assertStatus(200);
        $response->assertSee(__('nav.audit_logs'));
    }

    public function test_devotee_cannot_access_audit_logs(): void
    {
        $devotee = User::factory()->create(['status' => 'active']);
        $devotee->assignRole('devotee');

        $response = $this->actingAs($devotee)->get('/admin/audit-logs');

        $response->assertStatus(403);
    }

    public function test_treasurer_can_access_finance_overview(): void
    {
        $treasurer = User::factory()->create(['status' => 'active']);
        $treasurer->assignRole('treasurer');

        $response = $this->actingAs($treasurer)->get('/finance/overview');

        $response->assertStatus(200);
    }

    public function test_devotee_cannot_access_finance_overview(): void
    {
        $devotee = User::factory()->create(['status' => 'active']);
        $devotee->assignRole('devotee');

        $response = $this->actingAs($devotee)->get('/finance/overview');

        $response->assertStatus(403);
    }
}
