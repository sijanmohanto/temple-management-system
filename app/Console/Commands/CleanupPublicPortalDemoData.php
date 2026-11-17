<?php

namespace App\Console\Commands;

use App\Models\CollectionPayment;
use App\Models\Complaint;
use App\Models\ComplaintActivity;
use App\Models\Event;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\PaymentAllocation;
use App\Models\Person;
use App\Models\Pledge;
use App\Models\Receipt;
use App\Models\ReceiverAccount;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CleanupPublicPortalDemoData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:public-portal-cleanup {--force : Bypass user confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely removes demo dataset created by PublicPortalDemoSeeder without touching real application data.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('CRITICAL: CleanupPublicPortalDemoData cannot be executed in production environments!');

            return 1;
        }

        if (! $this->option('force') && ! $this->confirm('Are you sure you want to clean up all Public Portal demo data?')) {
            $this->info('Cleanup operation cancelled.');

            return 0;
        }

        $this->info('Starting Public Portal demo data cleanup...');
        $stats = $this->executeCleanup();

        $this->newLine();
        $this->info('Public Portal demo data cleanup completed successfully.');
        $this->table(['Entity', 'Deleted Count'], [
            ['Complaints & Activities', $stats['complaints']],
            ['Journal Entries & Lines', $stats['journals']],
            ['Receipts', $stats['receipts']],
            ['Payment Allocations', $stats['allocations']],
            ['Payments', $stats['payments']],
            ['Pledges', $stats['pledges']],
            ['Events', $stats['events']],
            ['Receiver Accounts', $stats['receivers']],
            ['Devotee Person Records', $stats['people']],
            ['Devotee User Accounts', $stats['users']],
            ['Storage Demo Files', $stats['files']],
        ]);

        return 0;
    }

    /**
     * Perform the actual database and storage cleanup.
     *
     * @return array<string, int>
     */
    public function executeCleanup(): array
    {
        return DB::transaction(function () {
            // 1. Identify Demo Entities
            $demoPersonCodes = [
                'DEV-DEMO-001', 'DEV-DEMO-002', 'DEV-DEMO-003', 'DEV-DEMO-004', 'DEV-DEMO-005',
                'DEV-DEMO-006', 'DEV-DEMO-007', 'DEV-DEMO-008', 'DEV-DEMO-009', 'DEV-DEMO-010',
            ];
            $demoEmails = [
                'subir.donor@temple.local', 'ananya.donor@temple.local', 'bikash.donor@temple.local',
                'sunil.donor@temple.local', 'prabir.donor@temple.local', 'tapash.donor@temple.local',
                'debabrata.donor@temple.local', 'madhuri.donor@temple.local', 'kalyan.donor@temple.local',
                'gopal.donor@temple.local',
            ];
            $demoEventCodes = ['EVT-DEMO-DP26', 'EVT-DEMO-KP26', 'EVT-DEMO-JM26', 'EVT-DEMO-SP27'];
            $demoReceiverNumbers = ['01711001122', '01811002233', '01911003344', '4401200033445'];

            $people = Person::whereIn('person_code', $demoPersonCodes)->get();
            $personIds = $people->pluck('id')->all();

            $events = Event::withTrashed()->whereIn('event_code', $demoEventCodes)->get();
            $eventIds = $events->pluck('id')->all();

            $users = User::whereIn('email', $demoEmails)->get();
            $userIds = $users->pluck('id')->all();

            // 2. Identify Payments
            $payments = collect();
            if (! empty($personIds) || ! empty($eventIds)) {
                $payments = CollectionPayment::withTrashed()
                    ->where(function ($q) use ($personIds, $eventIds) {
                        if (! empty($personIds)) {
                            $q->whereIn('payer_person_id', $personIds);
                        }
                        if (! empty($eventIds)) {
                            $q->orWhereIn('event_id', $eventIds);
                        }
                    })
                    ->get();
            }
            $paymentIds = $payments->pluck('id')->all();

            // 3. Clean Financial Ledgers for Demo Payments
            $journalsCount = 0;
            if (! empty($paymentIds)) {
                $journals = JournalEntry::where('source_type', CollectionPayment::class)
                    ->whereIn('source_id', $paymentIds)
                    ->get();
                $journalIds = $journals->pluck('id')->all();

                // Reversals pointing to these journals
                $reversalJournals = JournalEntry::whereIn('reversed_journal_id', $journalIds)->get();
                $allJournalIds = array_unique(array_merge($journalIds, $reversalJournals->pluck('id')->all()));

                if (! empty($allJournalIds)) {
                    JournalLine::whereIn('journal_entry_id', $allJournalIds)->delete();
                    $journalsCount = JournalEntry::whereIn('id', $allJournalIds)->delete();
                }
            }

            // 4. Receipts & Allocations
            $receiptsCount = 0;
            $allocationsCount = 0;
            if (! empty($paymentIds)) {
                $receiptsCount = Receipt::whereIn('collection_payment_id', $paymentIds)->delete();
                $allocationsCount = PaymentAllocation::whereIn('collection_payment_id', $paymentIds)->delete();
            }

            // 5. Complaints & Activities
            $complaints = collect();
            if (! empty($personIds) || ! empty($eventIds) || ! empty($paymentIds)) {
                $complaints = Complaint::withTrashed()
                    ->where(function ($q) use ($personIds, $eventIds, $paymentIds) {
                        $q->where('tracking_number', 'like', 'CMP-DEMO-%');
                        if (! empty($personIds)) {
                            $q->orWhereIn('person_id', $personIds);
                        }
                        if (! empty($eventIds)) {
                            $q->orWhereIn('event_id', $eventIds);
                        }
                        if (! empty($paymentIds)) {
                            $q->orWhereIn('collection_payment_id', $paymentIds);
                        }
                    })
                    ->get();
            } else {
                $complaints = Complaint::withTrashed()
                    ->where('tracking_number', 'like', 'CMP-DEMO-%')
                    ->get();
            }
            $complaintIds = $complaints->pluck('id')->all();

            $complaintsCount = 0;
            if (! empty($complaintIds)) {
                ComplaintActivity::whereIn('complaint_id', $complaintIds)->delete();
                $complaintsCount = Complaint::whereIn('id', $complaintIds)->forceDelete();
            }

            // 6. Delete Payments
            $paymentsCount = 0;
            if (! empty($paymentIds)) {
                $paymentsCount = CollectionPayment::whereIn('id', $paymentIds)->forceDelete();
            }

            // 7. Delete Pledges
            $pledgesCount = 0;
            if (! empty($personIds) || ! empty($eventIds)) {
                $pledges = Pledge::withTrashed()
                    ->where(function ($q) use ($personIds, $eventIds) {
                        if (! empty($personIds)) {
                            $q->whereIn('person_id', $personIds);
                        }
                        if (! empty($eventIds)) {
                            $q->orWhereIn('event_id', $eventIds);
                        }
                    })
                    ->get();
                if ($pledges->isNotEmpty()) {
                    $pledgesCount = Pledge::whereIn('id', $pledges->pluck('id'))->forceDelete();
                }
            }

            // 8. Delete Events
            $eventsCount = 0;
            if (! empty($eventIds)) {
                $eventsCount = Event::whereIn('id', $eventIds)->forceDelete();
            }

            // 9. Delete Receiver Accounts
            $receiversCount = ReceiverAccount::withTrashed()
                ->whereIn('account_number', $demoReceiverNumbers)
                ->forceDelete();

            // 10. Delete People & Classifications
            $peopleCount = 0;
            if (! empty($personIds)) {
                DB::table('person_classifications')->whereIn('person_id', $personIds)->delete();
                $peopleCount = Person::whereIn('id', $personIds)->delete();
            }

            // 11. Delete Users & Role assignments
            $usersCount = 0;
            if (! empty($userIds)) {
                DB::table('role_user')->whereIn('user_id', $userIds)->delete();
                $usersCount = User::whereIn('id', $userIds)->delete();
            }

            // 12. Delete Demo Files
            $filesCount = 0;
            $demoFiles = [
                'payment_proofs/demo_proof_prabir.jpg',
                'payment_proofs/demo_proof_tapash.jpg',
                'complaint_attachments/demo_complaint_tapash.pdf',
            ];
            foreach ($demoFiles as $file) {
                if (Storage::disk('local')->exists($file)) {
                    Storage::disk('local')->delete($file);
                    $filesCount++;
                }
            }

            return [
                'complaints' => $complaintsCount,
                'journals' => $journalsCount,
                'receipts' => $receiptsCount,
                'allocations' => $allocationsCount,
                'payments' => $paymentsCount,
                'pledges' => $pledgesCount,
                'events' => $eventsCount,
                'receivers' => $receiversCount,
                'people' => $peopleCount,
                'users' => $usersCount,
                'files' => $filesCount,
            ];
        });
    }
}
