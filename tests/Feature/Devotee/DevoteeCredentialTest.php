<?php

namespace Tests\Feature\Devotee;

use App\Models\DevoteeCredential;
use App\Models\Person;
use App\Models\User;
use App\Services\DevoteeCredentialService;
use Database\Seeders\RoleAndPermissionSeeder;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DevoteeCredentialTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $regularAdmin;

    protected Person $devoteePerson;

    protected User $devoteeUser;

    protected string $plainPassword = 'TestDevotee@2026';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->superAdmin = User::factory()->create([
            'email' => 'superadmin@temple.local',
            'status' => 'active',
        ]);
        $this->superAdmin->assignRole('super_admin');

        $this->regularAdmin = User::factory()->create([
            'email' => 'volunteer@temple.local',
            'status' => 'active',
        ]);
        $this->regularAdmin->assignRole('volunteer');

        $this->devoteePerson = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Chinmoy Roy',
            'phone' => '01711223344',
            'status' => 'active',
        ]);

        $service = app(DevoteeCredentialService::class);
        $this->devoteeUser = $service->provisionDevoteeAccount($this->devoteePerson, $this->plainPassword, $this->superAdmin);
    }

    public function test_password_hash_and_encrypted_copy_are_consistent_and_valid(): void
    {
        $this->devoteeUser->refresh();

        // 1. users.password contains valid one-way hash
        $this->assertTrue(Hash::check($this->plainPassword, $this->devoteeUser->password));

        // 2. devotee_credentials contains encrypted copy
        $credential = DevoteeCredential::where('user_id', $this->devoteeUser->id)->first();
        $this->assertNotNull($credential);
        $this->assertNotEquals($this->plainPassword, $credential->encrypted_password);

        // 3. Decrypts to exact plain password
        $decrypted = Crypt::decryptString($credential->encrypted_password);
        $this->assertEquals($this->plainPassword, $decrypted);
    }

    public function test_password_update_synchronizes_both_storage_values_atomically(): void
    {
        $service = app(DevoteeCredentialService::class);
        $newPassword = 'BrandNewSecret@2027';

        $service->updateCredentials($this->devoteeUser, $newPassword, $this->superAdmin, 'Devotee requested reset');

        $this->devoteeUser->refresh();

        // Hash updated
        $this->assertTrue(Hash::check($newPassword, $this->devoteeUser->password));
        $this->assertFalse(Hash::check($this->plainPassword, $this->devoteeUser->password));

        // Encrypted copy updated
        $credential = DevoteeCredential::where('user_id', $this->devoteeUser->id)->first();
        $this->assertEquals($newPassword, Crypt::decryptString($credential->encrypted_password));
    }

    public function test_failed_transaction_does_not_leave_inconsistent_credentials(): void
    {
        $initialHash = $this->devoteeUser->password;
        $initialEncrypted = DevoteeCredential::where('user_id', $this->devoteeUser->id)->value('encrypted_password');

        try {
            DB::transaction(function () {
                $this->devoteeUser->update(['password' => Hash::make('FailPassword@123')]);
                throw new Exception('Simulated atomic failure');
            });
        } catch (Exception $e) {
            // Expected
        }

        $this->devoteeUser->refresh();
        $currentEncrypted = DevoteeCredential::where('user_id', $this->devoteeUser->id)->value('encrypted_password');

        $this->assertEquals($initialHash, $this->devoteeUser->password);
        $this->assertEquals($initialEncrypted, $currentEncrypted);
    }

    public function test_unauthorized_user_cannot_reveal_devotee_password(): void
    {
        $response = $this->actingAs($this->regularAdmin)->postJson(
            route('people.credentials.reveal', $this->devoteePerson),
            ['reason' => 'Unauthorized attempt']
        );

        $response->assertStatus(403);
    }

    public function test_authorized_admin_can_reveal_devotee_password(): void
    {
        $response = $this->actingAs($this->superAdmin)->postJson(
            route('people.credentials.reveal', $this->devoteePerson),
            ['reason' => 'Administrative inspection']
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'password' => $this->plainPassword,
            'login_identifier' => '01711223344',
        ]);
    }

    public function test_authorized_admin_can_reset_devotee_password(): void
    {
        $response = $this->actingAs($this->superAdmin)->postJson(
            route('people.credentials.reset', $this->devoteePerson),
            [
                'new_password' => 'AdminReset@999',
                'reason' => 'Devotee assistance',
            ]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'new_password' => 'AdminReset@999',
        ]);

        $this->devoteeUser->refresh();
        $this->assertTrue(Hash::check('AdminReset@999', $this->devoteeUser->password));
    }

    public function test_passwords_never_appear_in_normal_datatable_or_search_responses(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('people.index'), [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringNotContainsString($this->plainPassword, $content);
        $this->assertStringNotContainsString('encrypted_password', $content);
    }
}
