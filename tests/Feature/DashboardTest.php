<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
        ]);
        $user->assignRole('devotee');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee($user->name);
    }

    public function test_user_can_view_and_update_profile(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'phone' => '+8801711111111',
            'preferred_locale' => 'en',
        ]);

        $response = $this->actingAs($user)->get('/profile');
        $response->assertStatus(200);

        $updateResponse = $this->actingAs($user)->put('/profile', [
            'name' => 'Updated Temple Member',
            'email' => $user->email,
            'phone' => '+8801722222222',
            'preferred_locale' => 'bn',
            'address' => 'Updated Temple Street',
        ]);

        $updateResponse->assertRedirect(route('profile.edit'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Temple Member',
            'phone' => '+8801722222222',
            'preferred_locale' => 'bn',
        ]);
    }
}
