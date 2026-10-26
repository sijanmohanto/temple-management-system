<?php

namespace Tests\Feature;

use App\Models\CollectionPayment;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Family;
use App\Models\MonthlyCommitteeContribution;
use App\Models\PaymentAllocation;
use App\Models\Person;
use App\Models\Pledge;
use App\Models\Receipt;
use App\Models\User;
use Database\Seeders\EventCategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonationAndPledgeTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $templeAdmin;

    protected User $accountant;

    protected User $volunteer;

    protected User $devotee;

    protected Event $durgaPuja;

    protected Event $kaliPuja;

    protected Person $person;

    protected Family $family;

    protected Committee $executiveCommittee;

    protected CommitteeMembership $membership;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(EventCategorySeeder::class);

        // Users & Roles
        $this->superAdmin = User::factory()->create(['name' => 'Super Admin', 'email' => 'super@example.com']);
        $this->superAdmin->assignRole('super_admin');

        $this->templeAdmin = User::factory()->create(['name' => 'Temple Admin', 'email' => 'admin@example.com']);
        $this->templeAdmin->assignRole('temple_admin');

        $this->accountant = User::factory()->create(['name' => 'Accountant', 'email' => 'accountant@example.com']);
        $this->accountant->assignRole('treasurer');

        $this->volunteer = User::factory()->create(['name' => 'Volunteer', 'email' => 'volunteer@example.com']);
        $this->volunteer->assignRole('volunteer');

        $this->devotee = User::factory()->create(['name' => 'Devotee', 'email' => 'devotee@example.com']);
        $this->devotee->assignRole('devotee');

        // Events
        $durgaCat = EventCategory::where('slug', 'durga-puja')->firstOrFail();
        $kaliCat = EventCategory::where('slug', 'kali-puja')->firstOrFail();

        $this->durgaPuja = Event::create([
            'event_code' => 'EVT-2026-0001',
            'name_en' => 'Sharadiya Durga Puja 2026',
            'name_bn' => 'শারদীয় দুর্গাপূজা ২০২৬',
            'event_category_id' => $durgaCat->id,
            'event_year' => 2026,
            'venue' => 'Main Mandap',
            'start_date' => '2026-10-18',
            'end_date' => '2026-10-23',
            'collection_start_date' => '2026-06-01',
            'collection_end_date' => '2026-10-31',
            'status' => Event::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->kaliPuja = Event::create([
            'event_code' => 'EVT-2026-0002',
            'name_en' => 'Shyama Kali Puja 2026',
            'name_bn' => 'শ্যামা কালীপূজা ২০২৬',
            'event_category_id' => $kaliCat->id,
            'event_year' => 2026,
            'venue' => 'Temple Courtyard',
            'start_date' => '2026-11-08',
            'end_date' => '2026-11-09',
            'collection_start_date' => '2026-08-01',
            'collection_end_date' => '2026-11-20',
            'status' => Event::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        // Person & Family
        $this->person = Person::create([
            'person_code' => 'PER-0001',
            'name_en' => 'Subhash Chandra Bose',
            'name_bn' => 'সুভাষ চন্দ্র বসু',
            'phone' => '01711000001',
            'email' => 'subhash@example.com',
            'status' => 'active',
        ]);

        $this->family = Family::create([
            'family_code' => 'FAM-0001',
            'family_name' => 'Bose Paribar',
            'address' => 'Dhaka, Bangladesh',
            'status' => 'active',
        ]);

        // Committee & Membership
        $this->executiveCommittee = Committee::create([
            'code' => 'COM-2026-01',
            'name_en' => 'Executive Committee 2026',
            'name_bn' => 'কার্যনির্বাহী পরিষদ ২০২৬',
            'term_start_date' => '2026-01-01',
            'term_end_date' => '2027-12-31',
            'status' => 'active',
        ]);

        $this->membership = CommitteeMembership::create([
            'committee_id' => $this->executiveCommittee->id,
            'person_id' => $this->person->id,
            'designation_en' => 'General Secretary',
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);
    }

    public function test_guest_cannot_access_pledges_or_collections(): void
    {
        $this->get(route('collections.dashboard'))->assertRedirect(route('login'));
        $this->get(route('pledges.index'))->assertRedirect(route('login'));
        $this->get(route('collections.index'))->assertRedirect(route('login'));
        $this->get(route('receipts.index'))->assertRedirect(route('login'));
        $this->get(route('contributions.index'))->assertRedirect(route('login'));
        $this->get(route('collections.reports.index'))->assertRedirect(route('login'));
    }

    public function test_devotee_without_permissions_cannot_access_or_manage_collections(): void
    {
        $this->actingAs($this->devotee)->get(route('collections.dashboard'))->assertForbidden();
        $this->actingAs($this->devotee)->get(route('pledges.index'))->assertForbidden();
        $this->actingAs($this->devotee)->get(route('pledges.create'))->assertForbidden();
        $this->actingAs($this->devotee)->post(route('pledges.store'), [])->assertForbidden();
        $this->actingAs($this->devotee)->get(route('collections.create'))->assertForbidden();
        $this->actingAs($this->devotee)->get(route('receipts.index'))->assertForbidden();
        $this->actingAs($this->devotee)->get(route('contributions.index'))->assertForbidden();
    }

    public function test_authorized_user_can_create_pledge_for_person_and_family(): void
    {
        // 1. Individual Devotee Pledge
        $response = $this->actingAs($this->templeAdmin)->post(route('pledges.store'), [
            'event_id' => $this->durgaPuja->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-07-01',
            'purpose' => 'Maha Saptami Bhog & Prasad',
            'notes' => 'Devotee promised during committee meeting',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pledges', [
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->durgaPuja->id,
            'person_id' => $this->person->id,
            'family_id' => null,
            'pledged_amount' => 5000.00,
            'status' => Pledge::STATUS_UNPAID,
        ]);

        $pledge = Pledge::where('pledge_code', 'PLG-2026-0001')->firstOrFail();
        $this->assertEquals(0.00, $pledge->verified_paid_amount);
        $this->assertEquals(5000.00, $pledge->outstanding_amount);
        $this->assertEquals($this->person->name_en, $pledge->owner_name);

        // 2. Family Pledge
        $responseFamily = $this->actingAs($this->templeAdmin)->post(route('pledges.store'), [
            'event_id' => $this->durgaPuja->id,
            'family_id' => $this->family->id,
            'pledge_year' => 2026,
            'pledged_amount' => 10000.00,
            'pledged_on' => '2026-07-02',
            'purpose' => 'Sandhi Puja 108 Lotuses',
        ]);

        $responseFamily->assertRedirect();
        $this->assertDatabaseHas('pledges', [
            'pledge_code' => 'PLG-2026-0002',
            'family_id' => $this->family->id,
            'person_id' => null,
            'pledged_amount' => 10000.00,
            'status' => Pledge::STATUS_UNPAID,
        ]);

        $familyPledge = Pledge::where('pledge_code', 'PLG-2026-0002')->firstOrFail();
        $this->assertEquals('Bose Paribar (Family)', $familyPledge->owner_name);
    }

    public function test_pledge_validation_requires_strictly_single_owner(): void
    {
        // Missing both person and family
        $response = $this->actingAs($this->templeAdmin)->post(route('pledges.store'), [
            'event_id' => $this->durgaPuja->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-07-01',
        ]);

        $response->assertSessionHasErrors(['person_id', 'family_id']);
    }

    public function test_concurrent_event_pledges_for_same_person(): void
    {
        // Person pledges for Durga Puja
        $this->actingAs($this->templeAdmin)->post(route('pledges.store'), [
            'event_id' => $this->durgaPuja->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 8000.00,
            'pledged_on' => '2026-07-10',
            'purpose' => 'Durga Puja Pratima Sponsorship',
        ]);

        // Person pledges for Kali Puja concurrently
        $this->actingAs($this->templeAdmin)->post(route('pledges.store'), [
            'event_id' => $this->kaliPuja->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 4000.00,
            'pledged_on' => '2026-07-15',
            'purpose' => 'Deepavali Oil Lamps & Fireworks',
        ]);

        $durgaPledge = Pledge::where('event_id', $this->durgaPuja->id)->where('person_id', $this->person->id)->first();
        $kaliPledge = Pledge::where('event_id', $this->kaliPuja->id)->where('person_id', $this->person->id)->first();

        $this->assertNotNull($durgaPledge);
        $this->assertNotNull($kaliPledge);
        $this->assertEquals(8000.00, (float) $durgaPledge->pledged_amount);
        $this->assertEquals(4000.00, (float) $kaliPledge->pledged_amount);
        $this->assertNotEquals($durgaPledge->pledge_code, $kaliPledge->pledge_code);
    }

    public function test_voluntary_donation_does_not_create_fictitious_pledge_or_balance(): void
    {
        $response = $this->actingAs($this->volunteer)->post(route('collections.store'), [
            'collection_type' => CollectionPayment::TYPE_EVENT_DONATION,
            'event_id' => $this->durgaPuja->id,
            'payer_name_override' => 'Anonymous Walk-in Devotee',
            'payment_date' => '2026-10-19',
            'payment_method' => CollectionPayment::METHOD_CASH,
            'amount' => 1500.00,
            'is_direct_cash_entry' => true,
            'payment_notes' => 'Cash received at puja counter',
        ]);

        $response->assertRedirect();
        $payment = CollectionPayment::where('collection_type', CollectionPayment::TYPE_EVENT_DONATION)->firstOrFail();

        $this->assertEquals('pending_verification', $payment->status);
        $this->assertEquals(1500.00, (float) $payment->amount);
        $this->assertEquals($this->volunteer->id, $payment->submitted_by);

        // Verification: MUST NOT create any pledge records
        $this->assertEquals(0, Pledge::count());

        // Has allocation targeting voluntary event
        $this->assertDatabaseHas('payment_allocations', [
            'collection_payment_id' => $payment->id,
            'target_type' => PaymentAllocation::TARGET_VOLUNTARY_EVENT,
            'event_id' => $this->durgaPuja->id,
            'amount' => 1500.00,
            'is_active' => true,
        ]);
    }

    public function test_payment_submission_workflow_sets_pending_and_does_not_affect_balances(): void
    {
        // 1. Create a pledge of 6,000
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'pledge_year' => 2026,
            'event_id' => $this->durgaPuja->id,
            'person_id' => $this->person->id,
            'pledged_amount' => 6000.00,
            'pledged_on' => '2026-08-01',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->templeAdmin->id,
        ]);

        // 2. Submit payment of 2,500
        $response = $this->actingAs($this->volunteer)->post(route('collections.store'), [
            'collection_type' => CollectionPayment::TYPE_EVENT_PLEDGE,
            'pledge_id' => $pledge->id,
            'payer_person_id' => $this->person->id,
            'payment_date' => '2026-08-10',
            'payment_method' => CollectionPayment::METHOD_BKASH,
            'transaction_reference' => 'BKASH99281X',
            'amount' => 2500.00,
            'payment_notes' => 'First installment via bKash',
        ]);

        $response->assertRedirect();
        $payment = CollectionPayment::where('payment_code', 'PAY-2026-0001')->firstOrFail();

        $this->assertTrue($payment->isPending());
        $this->assertNull($payment->verified_by);
        $this->assertNull($payment->receipt); // No receipt when pending!

        // Pledge MUST remain unchanged until verified
        $pledge->refresh();
        $this->assertEquals(Pledge::STATUS_UNPAID, $pledge->status);
        $this->assertEquals(0.00, $pledge->verified_paid_amount);
        $this->assertEquals(6000.00, $pledge->outstanding_amount);
    }

    public function test_strict_separation_of_duties_prevents_self_verification(): void
    {
        // Temple Admin submits payment
        $payment = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0001',
            'payment_year' => 2026,
            'collection_type' => CollectionPayment::TYPE_GENERAL_DONATION,
            'payer_person_id' => $this->person->id,
            'payment_date' => '2026-08-15',
            'payment_method' => CollectionPayment::METHOD_CASH,
            'amount' => 1000.00,
            'status' => CollectionPayment::STATUS_PENDING,
            'submitted_by' => $this->templeAdmin->id,
            'created_by' => $this->templeAdmin->id,
        ]);

        PaymentAllocation::create([
            'collection_payment_id' => $payment->id,
            'target_type' => PaymentAllocation::TARGET_VOLUNTARY_GENERAL,
            'amount' => 1000.00,
            'is_active' => true,
        ]);

        // 1. Submitter (templeAdmin) tries to verify their own payment -> MUST BE FORBIDDEN / VALIDATION ERROR
        $response = $this->actingAs($this->templeAdmin)->post(route('collections.verify', $payment), [
            'verification_note' => 'Self verification attempt',
        ]);

        $response->assertSessionHasErrors(['verification']);
        $payment->refresh();
        $this->assertTrue($payment->isPending());
        $this->assertNull($payment->verified_by);

        // 2. An independent authorized verifier (accountant) CAN verify it!
        $verifyResponse = $this->actingAs($this->accountant)->post(route('collections.verify', $payment), [
            'verification_note' => 'Voucher verified and bank matched.',
        ]);

        $verifyResponse->assertRedirect();
        $payment->refresh();
        $this->assertTrue($payment->isVerified());
        $this->assertEquals($this->accountant->id, $payment->verified_by);
        $this->assertNotNull($payment->receipt);
    }

    public function test_independent_verifier_can_verify_payment_and_atomically_issue_receipt(): void
    {
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'pledge_year' => 2026,
            'event_id' => $this->durgaPuja->id,
            'person_id' => $this->person->id,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-08-01',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->templeAdmin->id,
        ]);

        $payment = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0001',
            'payment_year' => 2026,
            'collection_type' => CollectionPayment::TYPE_EVENT_PLEDGE,
            'event_id' => $this->durgaPuja->id,
            'payer_person_id' => $this->person->id,
            'payment_date' => '2026-08-20',
            'payment_method' => CollectionPayment::METHOD_BANK,
            'bank_name' => 'Sonali Bank PLC, Temple Branch',
            'transaction_reference' => 'TXN778899',
            'amount' => 5000.00,
            'status' => CollectionPayment::STATUS_PENDING,
            'submitted_by' => $this->volunteer->id,
            'created_by' => $this->volunteer->id,
        ]);

        PaymentAllocation::create([
            'collection_payment_id' => $payment->id,
            'target_type' => PaymentAllocation::TARGET_PLEDGE,
            'pledge_id' => $pledge->id,
            'event_id' => $this->durgaPuja->id,
            'amount' => 5000.00,
            'is_active' => true,
        ]);

        // Verifier approves
        $response = $this->actingAs($this->templeAdmin)->post(route('collections.verify', $payment), [
            'verification_note' => 'Bank credit confirmed in passbook.',
        ]);

        $response->assertRedirect(route('collections.show', $payment));

        $payment->refresh();
        $this->assertTrue($payment->isVerified());
        $this->assertEquals($this->templeAdmin->id, $payment->verified_by);

        // Receipt issued atomically
        $this->assertNotNull($payment->receipt);
        $receipt = $payment->receipt;
        $this->assertEquals('RCP-2026-0001', $receipt->receipt_number);
        $this->assertTrue($receipt->isValid());
        $this->assertEquals($this->templeAdmin->id, $receipt->issued_by);
        $this->assertNotNull($receipt->issued_at);

        // Pledge status updated atomically to fully paid
        $pledge->refresh();
        $this->assertEquals(Pledge::STATUS_PAID, $pledge->status);
        $this->assertEquals(5000.00, $pledge->verified_paid_amount);
        $this->assertEquals(0.00, $pledge->outstanding_amount);
    }

    public function test_partial_payments_and_installment_tracking(): void
    {
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'pledge_year' => 2026,
            'event_id' => $this->durgaPuja->id,
            'person_id' => $this->person->id,
            'pledged_amount' => 10000.00,
            'pledged_on' => '2026-08-01',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->templeAdmin->id,
        ]);

        // Installment 1: 4,000
        $pay1 = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0001',
            'payment_year' => 2026,
            'collection_type' => CollectionPayment::TYPE_EVENT_PLEDGE,
            'event_id' => $this->durgaPuja->id,
            'payer_person_id' => $this->person->id,
            'payment_date' => '2026-08-05',
            'payment_method' => CollectionPayment::METHOD_CASH,
            'amount' => 4000.00,
            'status' => CollectionPayment::STATUS_PENDING,
            'submitted_by' => $this->volunteer->id,
            'created_by' => $this->volunteer->id,
        ]);
        PaymentAllocation::create([
            'collection_payment_id' => $pay1->id,
            'target_type' => PaymentAllocation::TARGET_PLEDGE,
            'pledge_id' => $pledge->id,
            'event_id' => $this->durgaPuja->id,
            'amount' => 4000.00,
            'is_active' => true,
        ]);

        $this->actingAs($this->accountant)->post(route('collections.verify', $pay1));
        $pledge->refresh();
        $this->assertEquals(Pledge::STATUS_PARTIAL, $pledge->status);
        $this->assertEquals(4000.00, $pledge->verified_paid_amount);
        $this->assertEquals(6000.00, $pledge->outstanding_amount);

        // Installment 2: 6,000
        $pay2 = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0002',
            'payment_year' => 2026,
            'collection_type' => CollectionPayment::TYPE_EVENT_PLEDGE,
            'event_id' => $this->durgaPuja->id,
            'payer_person_id' => $this->person->id,
            'payment_date' => '2026-09-01',
            'payment_method' => CollectionPayment::METHOD_BKASH,
            'amount' => 6000.00,
            'status' => CollectionPayment::STATUS_PENDING,
            'submitted_by' => $this->volunteer->id,
            'created_by' => $this->volunteer->id,
        ]);
        PaymentAllocation::create([
            'collection_payment_id' => $pay2->id,
            'target_type' => PaymentAllocation::TARGET_PLEDGE,
            'pledge_id' => $pledge->id,
            'event_id' => $this->durgaPuja->id,
            'amount' => 6000.00,
            'is_active' => true,
        ]);

        $this->actingAs($this->accountant)->post(route('collections.verify', $pay2));
        $pledge->refresh();
        $this->assertEquals(Pledge::STATUS_PAID, $pledge->status);
        $this->assertEquals(10000.00, $pledge->verified_paid_amount);
        $this->assertEquals(0.00, $pledge->outstanding_amount);

        // Receipts check: two distinct sequential receipts
        $this->assertDatabaseHas('receipts', ['receipt_number' => 'RCP-2026-0001', 'collection_payment_id' => $pay1->id]);
        $this->assertDatabaseHas('receipts', ['receipt_number' => 'RCP-2026-0002', 'collection_payment_id' => $pay2->id]);
    }

    public function test_payment_exceeding_pledged_amount_allocates_pledge_and_additional_voluntary_donation(): void
    {
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'pledge_year' => 2026,
            'event_id' => $this->durgaPuja->id,
            'person_id' => $this->person->id,
            'pledged_amount' => 3000.00,
            'pledged_on' => '2026-08-01',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->templeAdmin->id,
        ]);

        // Devotee pledged 3,000 but contributes 5,000
        $response = $this->actingAs($this->volunteer)->post(route('collections.store'), [
            'collection_type' => CollectionPayment::TYPE_EVENT_PLEDGE,
            'pledge_id' => $pledge->id,
            'payer_person_id' => $this->person->id,
            'payment_date' => '2026-08-05',
            'payment_method' => CollectionPayment::METHOD_CASH,
            'amount' => 5000.00,
        ]);

        $response->assertRedirect();
        $payment = CollectionPayment::firstOrFail();
        $this->assertEquals(5000.00, (float) $payment->amount);

        // Verify allocations: 3,000 to pledge, 2,000 to voluntary event donation
        $allocations = $payment->allocations()->orderBy('id')->get();
        $this->assertCount(2, $allocations);

        $pledgeAlloc = $allocations->where('target_type', PaymentAllocation::TARGET_PLEDGE)->first();
        $excessAlloc = $allocations->where('target_type', PaymentAllocation::TARGET_VOLUNTARY_EVENT)->first();

        $this->assertNotNull($pledgeAlloc);
        $this->assertNotNull($excessAlloc);
        $this->assertEquals(3000.00, (float) $pledgeAlloc->amount);
        $this->assertEquals(2000.00, (float) $excessAlloc->amount);
        $this->assertEquals($pledge->id, $pledgeAlloc->pledge_id);
        $this->assertEquals($pledge->id, $excessAlloc->pledge_id);

        // Verify payment
        $this->actingAs($this->accountant)->post(route('collections.verify', $payment));

        $pledge->refresh();
        $this->assertEquals(Pledge::STATUS_PAID, $pledge->status);
        $this->assertEquals(3000.00, $pledge->verified_paid_amount);
        $this->assertEquals(0.00, $pledge->outstanding_amount);
        $this->assertTrue($pledge->has_excess_contribution);
        $this->assertEquals(2000.00, $pledge->excess_contributed_amount);
        $this->assertEquals(5000.00, $pledge->total_contributed_amount);

        // Pledge details page shows respectful notice
        $showResponse = $this->actingAs($this->templeAdmin)->get(route('pledges.show', $pledge));
        $showResponse->assertStatus(200);
        $showResponse->assertSee(__('donations.additional_contribution'));
        $showResponse->assertSee('2,000.00');
    }

    public function test_payment_exceeding_monthly_contribution_allocates_dues_and_general_voluntary(): void
    {
        $contribution = MonthlyCommitteeContribution::create([
            'contribution_code' => 'MCC-202610-0001',
            'committee_membership_id' => $this->membership->id,
            'contribution_month' => '2026-10-01',
            'expected_amount' => 1000.00,
            'status' => MonthlyCommitteeContribution::STATUS_UNPAID,
            'created_by' => $this->templeAdmin->id,
        ]);

        // Member expected 1,000 but pays 1,500
        $response = $this->actingAs($this->volunteer)->post(route('collections.store'), [
            'collection_type' => CollectionPayment::TYPE_MONTHLY_CONTRIBUTION,
            'monthly_contribution_id' => $contribution->id,
            'payer_person_id' => $this->membership->person_id,
            'payment_date' => '2026-10-15',
            'payment_method' => CollectionPayment::METHOD_BKASH,
            'amount' => 1500.00,
        ]);

        $response->assertRedirect();
        $payment = CollectionPayment::firstOrFail();
        $this->assertEquals(1500.00, (float) $payment->amount);

        // Allocations: 1,000 to monthly contribution, 500 to general voluntary
        $allocations = $payment->allocations()->orderBy('id')->get();
        $this->assertCount(2, $allocations);

        $dueAlloc = $allocations->where('target_type', PaymentAllocation::TARGET_MONTHLY_CONTRIBUTION)->first();
        $excessAlloc = $allocations->where('target_type', PaymentAllocation::TARGET_VOLUNTARY_GENERAL)->first();

        $this->assertNotNull($dueAlloc);
        $this->assertNotNull($excessAlloc);
        $this->assertEquals(1000.00, (float) $dueAlloc->amount);
        $this->assertEquals(500.00, (float) $excessAlloc->amount);

        // Verifier approves
        $this->actingAs($this->accountant)->post(route('collections.verify', $payment));

        $contribution->refresh();
        $this->assertEquals(MonthlyCommitteeContribution::STATUS_PAID, $contribution->status);
        $this->assertEquals(1000.00, $contribution->verified_paid_amount);
        $this->assertEquals(0.00, $contribution->outstanding_amount);
        $this->assertTrue($contribution->has_excess_contribution);
        $this->assertEquals(500.00, $contribution->excess_contributed_amount);
        $this->assertEquals(1500.00, $contribution->total_contributed_amount);
    }

    public function test_submitting_pledge_payment_requires_matching_payer(): void
    {
        $otherPerson = Person::create([
            'person_code' => 'PER-999999',
            'name_en' => 'Other Devotee',
            'is_active' => true,
        ]);

        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'pledge_year' => 2026,
            'event_id' => $this->durgaPuja->id,
            'person_id' => $this->person->id,
            'pledged_amount' => 2000.00,
            'pledged_on' => '2026-08-01',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->templeAdmin->id,
        ]);

        // Attempting to submit payment for this pledge under a different person ID
        $response = $this->actingAs($this->volunteer)->post(route('collections.store'), [
            'collection_type' => CollectionPayment::TYPE_EVENT_PLEDGE,
            'pledge_id' => $pledge->id,
            'payer_person_id' => $otherPerson->id,
            'payment_date' => '2026-08-05',
            'payment_method' => CollectionPayment::METHOD_CASH,
            'amount' => 2000.00,
        ]);

        $response->assertSessionHasErrors(['payer_person_id']);
        $this->assertEquals(0, CollectionPayment::count());
    }

    public function test_payer_obligations_ajax_endpoint(): void
    {
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'pledge_year' => 2026,
            'event_id' => $this->durgaPuja->id,
            'person_id' => $this->person->id,
            'pledged_amount' => 2500.00,
            'pledged_on' => '2026-08-01',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->templeAdmin->id,
        ]);

        $response = $this->actingAs($this->volunteer)->getJson(route('collections.payer-obligations', [
            'person_id' => $this->person->id,
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'pledges' => [
                '*' => ['id', 'pledge_code', 'event_name', 'pledged_amount', 'outstanding_amount'],
            ],
            'contributions',
        ]);
        $response->assertJsonFragment(['pledge_code' => 'PLG-2026-0001']);
    }

    public function test_receipt_download_and_share_view(): void
    {
        $payment = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0001',
            'payment_year' => 2026,
            'collection_type' => CollectionPayment::TYPE_GENERAL_DONATION,
            'payer_person_id' => $this->person->id,
            'payment_date' => '2026-08-20',
            'payment_method' => CollectionPayment::METHOD_CASH,
            'amount' => 1000.00,
            'status' => CollectionPayment::STATUS_VERIFIED,
            'submitted_by' => $this->volunteer->id,
            'verified_by' => $this->accountant->id,
            'verified_at' => now(),
            'created_by' => $this->volunteer->id,
        ]);

        $receipt = Receipt::create([
            'receipt_number' => 'RCP-2026-0001',
            'collection_payment_id' => $payment->id,
            'receipt_year' => 2026,
            'status' => Receipt::STATUS_VALID,
            'issued_by' => $this->accountant->id,
            'issued_at' => now(),
            'created_by' => $this->accountant->id,
        ]);

        // Test receipt show view renders share modal and download button
        $showResponse = $this->actingAs($this->volunteer)->get(route('receipts.show', $receipt));
        $showResponse->assertStatus(200);
        $showResponse->assertSee(__('donations.share_receipt'));
        $showResponse->assertSee(__('donations.download_receipt_pdf'));

        // Volunteer without print_receipts permission is forbidden from downloading
        $this->actingAs($this->volunteer)->get(route('receipts.download', $receipt))->assertStatus(403);

        // Authorized treasurer can download official receipt
        $downloadResponse = $this->actingAs($this->accountant)->get(route('receipts.download', $receipt));
        $downloadResponse->assertStatus(200);
        $downloadResponse->assertSee($receipt->receipt_number);
    }

    public function test_rejection_workflow_with_mandatory_reason(): void
    {
        $payment = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0001',
            'payment_year' => 2026,
            'collection_type' => CollectionPayment::TYPE_GENERAL_DONATION,
            'payer_person_id' => $this->person->id,
            'payment_date' => '2026-08-15',
            'payment_method' => CollectionPayment::METHOD_BKASH,
            'amount' => 2000.00,
            'status' => CollectionPayment::STATUS_PENDING,
            'submitted_by' => $this->volunteer->id,
            'created_by' => $this->volunteer->id,
        ]);

        // Missing rejection reason
        $failResponse = $this->actingAs($this->templeAdmin)->post(route('collections.reject', $payment), [
            'rejection_reason' => '',
        ]);
        $failResponse->assertSessionHasErrors(['rejection_reason']);

        // With valid reason
        $response = $this->actingAs($this->templeAdmin)->post(route('collections.reject', $payment), [
            'rejection_reason' => 'Transaction ID not found in bKash merchant statement.',
        ]);

        $response->assertRedirect(route('collections.show', $payment));

        $payment->refresh();
        $this->assertTrue($payment->isRejected());
        $this->assertEquals($this->templeAdmin->id, $payment->rejected_by);
        $this->assertNotNull($payment->rejected_at);
        $this->assertEquals('Transaction ID not found in bKash merchant statement.', $payment->rejection_reason);

        // No receipt generated
        $this->assertNull($payment->receipt);
        $this->assertEquals(0, Receipt::count());
    }

    public function test_reversal_workflow_deactivates_allocations_and_voids_receipt(): void
    {
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'pledge_year' => 2026,
            'event_id' => $this->durgaPuja->id,
            'person_id' => $this->person->id,
            'pledged_amount' => 7000.00,
            'pledged_on' => '2026-08-01',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->templeAdmin->id,
        ]);

        $payment = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0001',
            'payment_year' => 2026,
            'collection_type' => CollectionPayment::TYPE_EVENT_PLEDGE,
            'event_id' => $this->durgaPuja->id,
            'payer_person_id' => $this->person->id,
            'payment_date' => '2026-08-20',
            'payment_method' => CollectionPayment::METHOD_CASH,
            'amount' => 7000.00,
            'status' => CollectionPayment::STATUS_PENDING,
            'submitted_by' => $this->volunteer->id,
            'created_by' => $this->volunteer->id,
        ]);

        $allocation = PaymentAllocation::create([
            'collection_payment_id' => $payment->id,
            'target_type' => PaymentAllocation::TARGET_PLEDGE,
            'pledge_id' => $pledge->id,
            'event_id' => $this->durgaPuja->id,
            'amount' => 7000.00,
            'is_active' => true,
        ]);

        // Verify it first
        $this->actingAs($this->accountant)->post(route('collections.verify', $payment));
        $payment->refresh();
        $pledge->refresh();
        $this->assertTrue($payment->isVerified());
        $this->assertEquals(Pledge::STATUS_PAID, $pledge->status);

        $receipt = $payment->receipt;
        $this->assertNotNull($receipt);
        $this->assertTrue($receipt->isValid());

        // Reverse the verified payment using authorized treasurer
        $response = $this->actingAs($this->accountant)->post(route('collections.reverse', $payment), [
            'reversal_reason' => 'Cheque bounced or counterfeit notes identified upon recount.',
        ]);

        $response->assertRedirect(route('collections.show', $payment));

        $payment->refresh();
        $this->assertTrue($payment->isReversed());
        $this->assertEquals($this->accountant->id, $payment->reversed_by);
        $this->assertNotNull($payment->reversed_at);

        // Allocation deactivated
        $allocation->refresh();
        $this->assertFalse((bool) $allocation->is_active);

        // Pledge recalculated back to unpaid
        $pledge->refresh();
        $this->assertEquals(Pledge::STATUS_UNPAID, $pledge->status);
        $this->assertEquals(0.00, $pledge->verified_paid_amount);
        $this->assertEquals(7000.00, $pledge->outstanding_amount);

        // Receipt voided, NOT deleted!
        $receipt->refresh();
        $this->assertTrue($receipt->isVoided());
        $this->assertEquals(Receipt::STATUS_VOIDED, $receipt->status);
        $this->assertEquals($this->accountant->id, $receipt->voided_by);
        $this->assertNotNull($receipt->voided_at);
        $this->assertEquals('Cheque bounced or counterfeit notes identified upon recount.', $receipt->void_reason);
        $this->assertEquals(1, Receipt::count());
    }

    public function test_monthly_committee_contributions_rules_and_bulk_generation(): void
    {
        // 1. Individual generation
        $response = $this->actingAs($this->templeAdmin)->post(route('contributions.store'), [
            'committee_membership_id' => $this->membership->id,
            'contribution_month' => '2026-05',
            'expected_amount' => 500.00,
            'notes' => 'May 2026 dues',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('monthly_committee_contributions', [
            'committee_membership_id' => $this->membership->id,
            'expected_amount' => 500.00,
            'status' => MonthlyCommitteeContribution::STATUS_UNPAID,
        ]);

        $contribution = MonthlyCommitteeContribution::firstOrFail();
        $this->assertEquals('MCC-202605-0001', $contribution->contribution_code);
        $this->assertEquals('2026-05-01', $contribution->contribution_month->toDateString());

        // 2. Duplicate month for same member MUST FAIL
        $dupResponse = $this->actingAs($this->templeAdmin)->post(route('contributions.store'), [
            'committee_membership_id' => $this->membership->id,
            'contribution_month' => '2026-05',
            'expected_amount' => 500.00,
        ]);
        $dupResponse->assertSessionHasErrors();

        // 3. Bulk generation for committee for month 2026-06
        // Create second member
        $person2 = Person::create([
            'person_code' => 'PER-0002',
            'name_en' => 'Jagadish Chandra Bose',
            'status' => 'active',
        ]);
        CommitteeMembership::create([
            'committee_id' => $this->executiveCommittee->id,
            'person_id' => $person2->id,
            'designation_en' => 'Treasurer',
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $bulkResponse = $this->actingAs($this->templeAdmin)->post(route('contributions.bulk-generate'), [
            'committee_id' => $this->executiveCommittee->id,
            'contribution_month' => '2026-06',
            'default_expected_amount' => 750.00,
        ]);

        $bulkResponse->assertRedirect();
        $this->assertEquals(2, MonthlyCommitteeContribution::whereDate('contribution_month', '2026-06-01')->count());

        // 4. Waive workflow
        $waiveResponse = $this->actingAs($this->templeAdmin)->post(route('contributions.waive', $contribution), [
            'waiver_reason' => 'Approved waiver due to overseas assignment.',
        ]);

        $waiveResponse->assertRedirect();
        $contribution->refresh();
        $this->assertEquals(MonthlyCommitteeContribution::STATUS_WAIVED, $contribution->status);
        $this->assertEquals('Approved waiver due to overseas assignment.', $contribution->waiver_reason);

        // 5. Concluding membership preserves historical contribution obligations
        $this->membership->update(['status' => 'concluded', 'end_date' => '2026-05-31']);
        $this->assertDatabaseHas('monthly_committee_contributions', [
            'id' => $contribution->id,
            'committee_membership_id' => $this->membership->id,
        ]);
    }

    public function test_datatable_server_side_json_response_and_collection_reports(): void
    {
        // Pledges DataTables AJAX
        $responsePledges = $this->actingAs($this->templeAdmin)->getJson(route('pledges.index', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));
        $responsePledges->assertOk();
        $responsePledges->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

        // Collections DataTables AJAX
        $responseCollections = $this->actingAs($this->templeAdmin)->getJson(route('collections.index', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));
        $responseCollections->assertOk();
        $responseCollections->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

        // Receipts DataTables AJAX
        $responseReceipts = $this->actingAs($this->templeAdmin)->getJson(route('receipts.index', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));
        $responseReceipts->assertOk();
        $responseReceipts->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

        // Reports views
        $this->actingAs($this->templeAdmin)->get(route('collections.reports.index'))->assertOk();
        $this->actingAs($this->templeAdmin)->get(route('collections.reports.event-summary'))->assertOk();
        $this->actingAs($this->templeAdmin)->get(route('collections.reports.outstanding-pledges'))->assertOk();
        $this->actingAs($this->templeAdmin)->get(route('collections.reports.committee-contributions'))->assertOk();
    }

    public function test_finance_phase_five_integration_boundary(): void
    {
        $payment = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0001',
            'payment_year' => 2026,
            'collection_type' => CollectionPayment::TYPE_GENERAL_DONATION,
            'payer_name_override' => 'Anonymous Donor',
            'payment_date' => '2026-08-25',
            'payment_method' => CollectionPayment::METHOD_CASH,
            'amount' => 500.00,
            'status' => CollectionPayment::STATUS_PENDING,
            'submitted_by' => $this->volunteer->id,
            'created_by' => $this->volunteer->id,
        ]);

        PaymentAllocation::create([
            'collection_payment_id' => $payment->id,
            'target_type' => PaymentAllocation::TARGET_VOLUNTARY_GENERAL,
            'amount' => 500.00,
            'is_active' => true,
        ]);

        $this->actingAs($this->templeAdmin)->post(route('collections.verify', $payment));
        $payment->refresh();

        $this->assertTrue($payment->isVerified());
        // Must remain null for Phase 5 integration
        $this->assertNull($payment->finance_posted_at);
        $this->assertNull($payment->finance_post_reference);
    }
}
