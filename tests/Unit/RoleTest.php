<?php

namespace Tests\Unit;

use App\Models\Role;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_role_returns_localized_name(): void
    {
        $role = Role::where('slug', 'super_admin')->first();

        app()->setLocale('en');
        $this->assertEquals('Super Administrator', $role->name);

        app()->setLocale('bn');
        $this->assertEquals('প্রধান প্রশাসক', $role->name);
    }
}
