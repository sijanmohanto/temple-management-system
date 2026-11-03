<?php

namespace Tests\Feature\PublicPortal;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\FinancialAccount;
use App\Models\ReceiverAccount;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiverAccountTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $adminUser;

    protected User $devoteeUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('public');

        // Super Admin
        $this->superAdmin = User::factory()->create(['status' => 'active']);
        $this->superAdmin->roles()->attach(Role::where('slug', 'super_admin')->firstOrFail());

        // Temple Admin (has manage_receiver_accounts & verify_receiver_accounts)
        $this->adminUser = User::factory()->create(['status' => 'active']);
        $this->adminUser->roles()->attach(Role::where('slug', 'temple_admin')->firstOrFail());

        // Devotee User (no staff/admin permissions)
        $this->devoteeUser = User::factory()->create(['status' => 'active']);
        $this->devoteeUser->roles()->attach(Role::where('slug', 'devotee')->firstOrFail());
    }

    public function test_guest_cannot_access_receiver_accounts_admin(): void
    {
        $response = $this->get(route('portal.receivers.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('portal.receivers.create'));
        $response->assertRedirect(route('login'));
    }

    public function test_devotee_without_permission_cannot_access_receiver_accounts(): void
    {
        $response = $this->actingAs($this->devoteeUser)->get(route('portal.receivers.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->devoteeUser)->get(route('portal.receivers.create'));
        $response->assertStatus(403);
    }

    public function test_authorized_user_can_view_receiver_accounts_index_and_create_form(): void
    {
        FinancialAccount::create([
            'account_code' => '1020',
            'account_name_en' => 'Sonali Bank Current Account',
            'account_name_bn' => 'সোনালী ব্যাংক চলতি হিসাব',
            'account_type' => FinancialAccount::TYPE_ASSET,
            'normal_balance' => FinancialAccount::BALANCE_DEBIT,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('portal.receivers.index'));
        $response->assertStatus(200);
        $response->assertSee(__('portal.receiver_accounts'));

        $response = $this->actingAs($this->adminUser)->get(route('portal.receivers.create'));
        $response->assertStatus(200);
        $response->assertSee(__('portal.receivers.add_account'));
        $response->assertSee('Sonali Bank Current Account');
        $response->assertSee('1020');
    }

    public function test_authorized_user_can_view_edit_form_with_financial_accounts(): void
    {
        $finAccount = FinancialAccount::create([
            'account_code' => '1020',
            'account_name_en' => 'Sonali Bank Current Account',
            'account_name_bn' => 'সোনালী ব্যাংক চলতি হিসাব',
            'account_type' => FinancialAccount::TYPE_ASSET,
            'normal_balance' => FinancialAccount::BALANCE_DEBIT,
            'is_active' => true,
        ]);

        $account = ReceiverAccount::create([
            'payment_method' => ReceiverAccount::METHOD_BANK_TRANSFER,
            'account_name' => 'Main Temple Bank Account',
            'account_number' => '1234567890',
            'account_type' => ReceiverAccount::TYPE_CURRENT,
            'bank_name' => 'Sonali Bank PLC',
            'financial_account_id' => $finAccount->id,
            'is_active' => true,
            'is_verified' => true,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('portal.receivers.edit', $account));
        $response->assertStatus(200);
        $response->assertSee('Main Temple Bank Account');
        $response->assertSee('Sonali Bank Current Account');
    }

    public function test_datatable_server_side_response_strictly_has_no_row_number_column(): void
    {
        ReceiverAccount::create([
            'payment_method' => ReceiverAccount::METHOD_BKASH,
            'account_name' => 'Sri Sri Kali Mandir bKash',
            'account_number' => '01711000000',
            'account_type' => ReceiverAccount::TYPE_MERCHANT,
            'is_active' => true,
            'is_verified' => true,
            'display_order' => 1,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('portal.receivers.index'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data' => [
                '*' => [
                    'payment_method',
                    'account_name',
                    'account_details',
                    'qr_code',
                    'status',
                    'display_order',
                    'actions',
                ],
            ],
        ]);

        $firstRow = $response->json('data.0');
        $this->assertArrayNotHasKey('#', $firstRow);
        $this->assertArrayNotHasKey('sl', $firstRow);
        $this->assertArrayNotHasKey('id', $firstRow);
        $this->assertStringContainsString('01711000000', $firstRow['account_details']);
    }

    public function test_filter_receiver_accounts_by_method_and_status(): void
    {
        ReceiverAccount::create([
            'payment_method' => ReceiverAccount::METHOD_BKASH,
            'account_name' => 'bKash Account',
            'account_number' => '01711111111',
            'account_type' => ReceiverAccount::TYPE_MERCHANT,
            'is_active' => true,
            'is_verified' => true,
            'created_by' => $this->adminUser->id,
        ]);

        ReceiverAccount::create([
            'payment_method' => ReceiverAccount::METHOD_BANK_TRANSFER,
            'account_name' => 'Bank Account',
            'account_number' => '1234567890',
            'account_type' => ReceiverAccount::TYPE_CURRENT,
            'bank_name' => 'Janata Bank PLC',
            'is_active' => false,
            'is_verified' => false,
            'created_by' => $this->adminUser->id,
        ]);

        // Filter by method bkash
        $response = $this->actingAs($this->adminUser)->getJson(route('portal.receivers.index', [
            'payment_method' => ReceiverAccount::METHOD_BKASH,
        ]));
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('recordsFiltered'));

        // Filter by inactive status
        $response = $this->actingAs($this->adminUser)->getJson(route('portal.receivers.index', [
            'is_active' => '0',
        ]));
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('recordsFiltered'));
    }

    public function test_authorized_user_can_create_receiver_account_with_qr_code(): void
    {
        $qrFile = UploadedFile::fake()->image('bkash_qr.png', 300, 300);

        $payload = [
            'payment_method' => ReceiverAccount::METHOD_BKASH,
            'account_name' => 'Kali Mandir Official bKash',
            'account_number' => '01711223344',
            'account_type' => ReceiverAccount::TYPE_MERCHANT,
            'qr_code' => $qrFile,
            'instructions_en' => 'Use merchant pay or send money with devotee name in counter',
            'instructions_bn' => 'ভক্তের নাম কাউন্টারে উল্লেখ করে সেন্ড মানি বা পেমেন্ট করুন',
            'is_active' => '1',
            'is_verified' => '1',
            'display_order' => 1,
        ];

        $response = $this->actingAs($this->adminUser)->post(route('portal.receivers.store'), $payload);
        $response->assertRedirect(route('portal.receivers.index'));

        $account = ReceiverAccount::where('account_number', '01711223344')->first();
        $this->assertNotNull($account);
        $this->assertEquals('Kali Mandir Official bKash', $account->account_name);
        $this->assertTrue($account->is_active);
        $this->assertTrue($account->is_verified);
        $this->assertNotNull($account->verified_at);
        $this->assertNotNull($account->qr_code_image_path);
        $this->assertTrue(Storage::disk('public')->exists($account->qr_code_image_path));
    }

    public function test_bank_transfer_requires_bank_name_validation(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('portal.receivers.store'), [
            'payment_method' => ReceiverAccount::METHOD_BANK_TRANSFER,
            'account_name' => 'Temple Main Bank',
            'account_number' => '9876543210',
            'account_type' => ReceiverAccount::TYPE_CURRENT,
            // bank_name omitted
        ]);

        $response->assertSessionHasErrors('bank_name');
    }

    public function test_authorized_user_can_edit_receiver_account_and_remove_qr_code(): void
    {
        $qrFile = UploadedFile::fake()->image('old_qr.png', 200, 200);
        $storedQrPath = $qrFile->store('receiver_qr_codes', 'public');

        $account = ReceiverAccount::create([
            'payment_method' => ReceiverAccount::METHOD_NAGAD,
            'account_name' => 'Nagad Personal',
            'account_number' => '01811223344',
            'account_type' => ReceiverAccount::TYPE_PERSONAL,
            'qr_code_image_path' => $storedQrPath,
            'is_active' => true,
            'is_verified' => false,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->put(route('portal.receivers.update', $account), [
            'payment_method' => ReceiverAccount::METHOD_NAGAD,
            'account_name' => 'Nagad Official Updated',
            'account_number' => '01811223344',
            'account_type' => ReceiverAccount::TYPE_MERCHANT,
            'remove_qr_code' => '1',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('portal.receivers.index'));
        $account->refresh();

        $this->assertEquals('Nagad Official Updated', $account->account_name);
        $this->assertEquals(ReceiverAccount::TYPE_MERCHANT, $account->account_type);
        $this->assertNull($account->qr_code_image_path);
        $this->assertFalse(Storage::disk('public')->exists($storedQrPath));
    }

    public function test_authorized_user_can_toggle_active_status(): void
    {
        $account = ReceiverAccount::create([
            'payment_method' => ReceiverAccount::METHOD_ROCKET,
            'account_name' => 'Rocket Channel',
            'account_number' => '019112233449',
            'account_type' => ReceiverAccount::TYPE_PERSONAL,
            'is_active' => true,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->postJson(route('portal.receivers.toggle-active', $account));
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'is_active' => false]);

        $account->refresh();
        $this->assertFalse($account->is_active);

        // Toggle back to active
        $response = $this->actingAs($this->adminUser)->postJson(route('portal.receivers.toggle-active', $account));
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'is_active' => true]);

        $account->refresh();
        $this->assertTrue($account->is_active);
    }

    public function test_authorized_user_with_permission_can_verify_and_unverify_account(): void
    {
        $account = ReceiverAccount::create([
            'payment_method' => ReceiverAccount::METHOD_BKASH,
            'account_name' => 'Temple bKash',
            'account_number' => '01700000001',
            'account_type' => ReceiverAccount::TYPE_MERCHANT,
            'is_active' => true,
            'is_verified' => false,
            'created_by' => $this->adminUser->id,
        ]);

        // Verify
        $response = $this->actingAs($this->adminUser)->postJson(route('portal.receivers.verify', $account));
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'is_verified' => true]);

        $account->refresh();
        $this->assertTrue($account->is_verified);
        $this->assertNotNull($account->verified_at);
        $this->assertEquals($this->adminUser->id, $account->verified_by);

        // Unverify
        $response = $this->actingAs($this->adminUser)->postJson(route('portal.receivers.unverify', $account));
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'is_verified' => false]);

        $account->refresh();
        $this->assertFalse($account->is_verified);
        $this->assertNull($account->verified_at);
        $this->assertNull($account->verified_by);
    }

    public function test_user_without_verify_permission_cannot_verify_account(): void
    {
        $account = ReceiverAccount::create([
            'payment_method' => ReceiverAccount::METHOD_BKASH,
            'account_name' => 'Temple bKash',
            'account_number' => '01700000002',
            'account_type' => ReceiverAccount::TYPE_MERCHANT,
            'is_active' => true,
            'is_verified' => false,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->devoteeUser)->postJson(route('portal.receivers.verify', $account));
        $response->assertStatus(403);
    }

    public function test_authorized_user_can_soft_delete_receiver_account(): void
    {
        $account = ReceiverAccount::create([
            'payment_method' => ReceiverAccount::METHOD_OTHER,
            'account_name' => 'Deprecated Account',
            'account_number' => '01500000000',
            'account_type' => ReceiverAccount::TYPE_PERSONAL,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->delete(route('portal.receivers.destroy', $account));
        $response->assertRedirect(route('portal.receivers.index'));

        $this->assertSoftDeleted('receiver_accounts', ['id' => $account->id]);
    }

    public function test_bengali_locale_renders_cleanly_without_missing_keys(): void
    {
        app()->setLocale('bn');

        $account = ReceiverAccount::create([
            'payment_method' => ReceiverAccount::METHOD_BKASH,
            'account_name' => 'শ্রী শ্রী কালী মন্দির বিকাশ',
            'account_number' => '01712345678',
            'account_type' => ReceiverAccount::TYPE_MERCHANT,
            'instructions_en' => 'Send money',
            'instructions_bn' => 'রেফারেন্সে ভক্তের নাম উল্লেখ করুন',
            'is_active' => true,
            'is_verified' => true,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('portal.receivers.show', $account));
        $response->assertStatus(200);
        $response->assertSee('শ্রী শ্রী কালী মন্দির বিকাশ');
        $response->assertSee('রেফারেন্সে ভক্তের নাম উল্লেখ করুন');
        $response->assertDontSee('portal.receivers.title');
    }

    public function test_receiver_account_creation_with_associated_event(): void
    {
        $category = EventCategory::firstOrCreate(
            ['slug' => 'puja'],
            ['name_en' => 'Puja', 'name_bn' => 'পূজা', 'is_active' => true]
        );

        $event = Event::create([
            'event_category_id' => $category->id,
            'name_en' => 'Saraswati Puja 2026',
            'name_bn' => 'সরস্বতী পূজা ২০২৬',
            'event_code' => 'EVT-SP-2026',
            'event_year' => 2026,
            'start_date' => '2026-02-10',
            'end_date' => '2026-02-12',
            'status' => Event::STATUS_ACTIVE,
            'created_by' => $this->adminUser->id,
        ]);

        $payload = [
            'payment_method' => ReceiverAccount::METHOD_NAGAD,
            'account_name' => 'Saraswati Puja Official Nagad',
            'account_number' => '01899001122',
            'account_type' => ReceiverAccount::TYPE_MERCHANT,
            'event_id' => $event->id,
            'is_active' => '1',
            'is_verified' => '1',
            'display_order' => 2,
        ];

        $response = $this->actingAs($this->adminUser)->post(route('portal.receivers.store'), $payload);
        $response->assertRedirect(route('portal.receivers.index'));

        $account = ReceiverAccount::where('account_number', '01899001122')->first();
        $this->assertNotNull($account);
        $this->assertEquals($event->id, $account->event_id);
        $this->assertEquals('Saraswati Puja 2026', $account->event->name_en);
    }

    public function test_receiver_account_deactivation_preserves_historical_records(): void
    {
        $account = ReceiverAccount::create([
            'payment_method' => ReceiverAccount::METHOD_BKASH,
            'account_name' => 'Historical Account',
            'account_number' => '01700998877',
            'account_type' => ReceiverAccount::TYPE_MERCHANT,
            'is_active' => true,
            'is_verified' => true,
            'created_by' => $this->adminUser->id,
        ]);

        // Toggle active status to inactive
        $response = $this->actingAs($this->adminUser)->postJson(route('portal.receivers.toggle-active', $account));
        $response->assertStatus(200);

        $account->refresh();
        $this->assertFalse($account->is_active);
        // Record is preserved (not deleted)
        $this->assertNull($account->deleted_at);
    }
}
