<?php

namespace Tests\Feature\PublicPortal;

use App\Models\CollectionPayment;
use App\Models\Complaint;
use App\Models\Event;
use App\Models\Person;
use App\Models\Pledge;
use App\Models\Receipt;
use App\Models\ReceiverAccount;
use App\Models\User;
use App\Services\EventCollectionReportService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\ClassificationSeeder;
use Database\Seeders\EventCategorySeeder;
use Database\Seeders\PublicPortalDemoSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DemoDataSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(ClassificationSeeder::class);
        $this->seed(EventCategorySeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_public_portal_demo_seeder_executes_cleanly_and_idempotently(): void
    {
        // 1. First Run
        $this->seed(PublicPortalDemoSeeder::class);

        // Verify Events
        $this->assertEquals(4, Event::where('event_code', 'like', 'EVT-DEMO-%')->count());
        $durga = Event::where('slug', 'durga-puja-2026')->first();
        $this->assertNotNull($durga);
        $this->assertEquals(Event::STATUS_ACTIVE, $durga->status);
        $this->assertEquals(Event::PUB_STATUS_PUBLISHED, $durga->publication_status);

        // Verify Unpublished Event is draft
        $draft = Event::where('slug', 'saraswati-puja-planning-2027')->first();
        $this->assertNotNull($draft);
        $this->assertEquals(Event::PUB_STATUS_DRAFT, $draft->publication_status);

        // Verify Receiver Accounts
        $this->assertEquals(4, ReceiverAccount::whereIn('account_number', [
            '01711001122', '01811002233', '01911003344', '4401200033445',
        ])->count());

        // Verify Devotees
        $this->assertEquals(10, Person::where('person_code', 'like', 'DEV-DEMO-%')->count());
        $this->assertEquals(10, User::where('email', 'like', '%.donor@temple.local')->count());

        // Verify Financial Metrics for Durga Puja
        $reportService = app(EventCollectionReportService::class);
        $metrics = $reportService->getMetrics($durga);

        $this->assertEquals(86000.00, $metrics['total_pledged']);
        $this->assertEquals(45000.00, $metrics['verified_pledge_collections']);
        $this->assertEquals(2000.00, $metrics['event_excess_contributions']);
        $this->assertEquals(3000.00, $metrics['voluntary_event_donations']);
        $this->assertEquals(50000.00, $metrics['total_verified_collections']);
        $this->assertEquals(8000.00, $metrics['pending_collections']);
        $this->assertEquals(41000.00, $metrics['outstanding_pledges']);

        // Verify Privacy Modes
        $bikashPledge = Pledge::whereHas('person', fn ($q) => $q->where('person_code', 'DEV-DEMO-003'))->first();
        $this->assertTrue($bikashPledge->isAnonymous());
        $this->assertEquals(__('portal.public.anonymous_donor'), $bikashPledge->public_display_name);

        $sunilPledge = Pledge::whereHas('person', fn ($q) => $q->where('person_code', 'DEV-DEMO-004'))->first();
        $this->assertTrue($sunilPledge->isPrivate());
        $this->assertEquals(__('portal.public.private_donor'), $sunilPledge->public_display_name);

        // Verify Reversal Integrity (Debabrata)
        $debabrataPayment = CollectionPayment::whereHas('payerPerson', fn ($q) => $q->where('person_code', 'DEV-DEMO-007'))->first();
        $this->assertEquals(CollectionPayment::STATUS_REVERSED, $debabrataPayment->status);
        $this->assertEquals(Receipt::STATUS_VOIDED, $debabrataPayment->receipt->status);

        // Verify Complaints
        $this->assertEquals(5, Complaint::where('tracking_number', 'like', 'CMP-DEMO-%')->count());

        // 2. Second Run (Idempotency)
        $this->seed(PublicPortalDemoSeeder::class);
        $this->assertEquals(4, Event::where('event_code', 'like', 'EVT-DEMO-%')->count());
        $this->assertEquals(10, Person::where('person_code', 'like', 'DEV-DEMO-%')->count());

        // 3. Cleanup Command
        Artisan::call('demo:public-portal-cleanup', ['--force' => true]);

        $this->assertEquals(0, Event::where('event_code', 'like', 'EVT-DEMO-%')->count());
        $this->assertEquals(0, Person::where('person_code', 'like', 'DEV-DEMO-%')->count());
        $this->assertEquals(0, User::where('email', 'like', '%.donor@temple.local')->count());
        $this->assertEquals(0, ReceiverAccount::whereIn('account_number', [
            '01711001122', '01811002233', '01911003344', '4401200033445',
        ])->count());
        $this->assertEquals(0, Complaint::where('tracking_number', 'like', 'CMP-DEMO-%')->count());
    }
}
