<?php

namespace Tests\Feature\Devotee;

use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use App\Services\DevoteeCredentialService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DevoteeAccountAndAuthTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Role $devoteeRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->adminUser = User::factory()->create([
            'email' => 'admin@temple.local',
            'phone' => '01711999999',
            'status' => 'active',
        ]);
        $this->adminUser->assignRole('super_admin');

        $this->devoteeRole = Role::where('slug', 'devotee')->firstOrFail();
    }

    public function test_admin_creates_devotee_and_corresponding_user_account_is_provisioned(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('people.store'), [
            'name_en' => 'Subhashish Roy',
            'name_bn' => 'সুভাশীষ রায়',
            'phone' => '01712345678',
            'email' => 'subhashish@example.com',
            'status' => 'active',
            'gender' => 'male',
        ]);

        $response->assertRedirect();

        $person = Person::where('phone', '01712345678')->first();
        $this->assertNotNull($person);

        // User account provisioned automatically
        $user = User::where('person_id', $person->id)->first();
        $this->assertNotNull($user);
        $this->assertEquals('Subhashish Roy', $user->name);
        $this->assertEquals('01712345678', $user->phone);
        $this->assertEquals('subhashish@example.com', $user->email);
        $this->assertTrue($user->hasRole('devotee'));
        $this->assertTrue($user->isActive());

        // Credential record exists
        $this->assertNotNull($user->devoteeCredential);
    }

    public function test_one_person_cannot_receive_duplicate_login_accounts(): void
    {
        $person = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Anuradha Devi',
            'phone' => '01811223344',
            'status' => 'active',
        ]);

        $service = app(DevoteeCredentialService::class);
        $user1 = $service->provisionDevoteeAccount($person, 'Secret@12345', $this->adminUser);
        $user2 = $service->provisionDevoteeAccount($person, 'AnotherSecret@123', $this->adminUser);

        $this->assertEquals($user1->id, $user2->id);
        $this->assertEquals(1, User::where('person_id', $person->id)->count());
    }

    public function test_existing_devotee_account_linking_works_safely(): void
    {
        $person = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Existing Devotee',
            'phone' => '01911888999',
            'email' => 'existing@temple.local',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('people.credentials.provision', $person));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue(User::where('person_id', $person->id)->exists());
    }

    public function test_ambiguous_phone_numbers_prevent_silent_incorrect_linking(): void
    {
        // Two users with same phone
        User::factory()->create([
            'name' => 'User A',
            'phone' => '01700112233',
            'status' => 'active',
        ]);
        User::factory()->create([
            'name' => 'User B',
            'phone' => '01700112233',
            'status' => 'active',
        ]);

        $person = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Target Devotee',
            'phone' => '01700112233',
            'status' => 'active',
        ]);

        $service = app(DevoteeCredentialService::class);
        $this->expectException(\RuntimeException::class);
        $service->provisionDevoteeAccount($person, null, $this->adminUser);
    }

    public function test_phone_number_login_works_with_variants(): void
    {
        $person = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Pramod Kumar',
            'phone' => '01733445566',
            'status' => 'active',
        ]);

        $service = app(DevoteeCredentialService::class);
        $user = $service->provisionDevoteeAccount($person, 'Pramod@123456', $this->adminUser);

        // 1. Standard 11-digit login
        $response1 = $this->post('/login', [
            'login' => '01733445566',
            'password' => 'Pramod@123456',
        ]);
        $response1->assertRedirect(route('devotee.dashboard'));
        $this->assertAuthenticatedAs($user);

        // Logout
        $this->post('/logout');
        $this->assertGuest();

        // 2. International +880 variant login
        $response2 = $this->post('/login', [
            'login' => '+8801733445566',
            'password' => 'Pramod@123456',
        ]);
        $response2->assertRedirect(route('devotee.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_incorrect_password_is_rejected(): void
    {
        $person = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Devotee WrongPass',
            'phone' => '01611223344',
            'status' => 'active',
        ]);

        app(DevoteeCredentialService::class)->provisionDevoteeAccount($person, 'CorrectPass@1', $this->adminUser);

        $response = $this->post('/login', [
            'login' => '01611223344',
            'password' => 'WrongPass@99',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_inactive_account_is_rejected(): void
    {
        $person = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Inactive Devotee',
            'phone' => '01511223344',
            'status' => 'inactive',
        ]);

        $user = app(DevoteeCredentialService::class)->provisionDevoteeAccount($person, 'Pass@123456', $this->adminUser);
        $user->update(['status' => 'inactive']);

        $response = $this->post('/login', [
            'login' => '01511223344',
            'password' => 'Pass@123456',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_unlinked_account_is_rejected_from_devotee_portal(): void
    {
        // Devotee user without Person profile
        $unlinkedDevotee = User::factory()->create([
            'name' => 'Unlinked Devotee',
            'phone' => '01311223344',
            'password' => Hash::make('Pass@123456'),
            'status' => 'active',
        ]);
        $unlinkedDevotee->assignRole('devotee');

        $response = $this->actingAs($unlinkedDevotee)->get(route('devotee.dashboard'));
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_initial_password_does_not_force_first_login_password_change(): void
    {
        $person = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'No Force Change Devotee',
            'phone' => '01411223344',
            'status' => 'active',
        ]);

        $user = app(DevoteeCredentialService::class)->provisionDevoteeAccount($person, 'InitialPass@123', $this->adminUser);

        $response = $this->post('/login', [
            'login' => '01411223344',
            'password' => 'InitialPass@123',
        ]);

        $response->assertRedirect(route('devotee.dashboard'));

        // Can access dashboard immediately without forced password reset redirect
        $this->actingAs($user)->get(route('devotee.dashboard'))->assertStatus(200);
    }
}
