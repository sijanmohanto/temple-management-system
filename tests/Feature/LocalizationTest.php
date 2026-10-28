<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_locale_is_english(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertEquals('en', app()->getLocale());
    }

    public function test_user_can_switch_locale_to_bengali(): void
    {
        $response = $this->get('/lang/bn');

        $response->assertSessionHas('locale', 'bn');
        $response->assertRedirect();
    }

    public function test_user_can_switch_locale_to_english(): void
    {
        $this->withSession(['locale' => 'bn']);

        $response = $this->get('/lang/en');

        $response->assertSessionHas('locale', 'en');
        $response->assertRedirect();
    }

    public function test_authenticated_user_preferred_locale_is_saved_on_switch(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'preferred_locale' => 'en',
        ]);

        $this->actingAs($user)->get('/lang/bn');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'preferred_locale' => 'bn',
        ]);
    }

    public function test_all_language_files_maintain_key_parity(): void
    {
        $files = [
            'common',
            'nav',
            'dashboard',
            'people',
            'families',
            'committees',
            'classifications',
            'events',
            'donations',
            'finance',
            'datatables',
            'select2',
            'roles',
        ];

        foreach ($files as $file) {
            $enPath = resource_path("../lang/en/{$file}.php");
            $bnPath = resource_path("../lang/bn/{$file}.php");

            $this->assertFileExists($enPath);
            $this->assertFileExists($bnPath);

            $en = require $enPath;
            $bn = require $bnPath;

            $enKeys = array_keys($en);
            $bnKeys = array_keys($bn);

            sort($enKeys);
            sort($bnKeys);

            $this->assertEquals($enKeys, $bnKeys, "Language keys in {$file}.php must have 100% parity between EN and BN.");
        }
    }
}
