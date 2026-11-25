<?php

namespace Database\Seeders;

use App\Models\Classification;
use App\Models\CollectionPayment;
use App\Models\Complaint;
use App\Models\ComplaintActivity;
use App\Models\DevoteeCredential;
use App\Models\Event;
use App\Models\FinancialAccount;
use App\Models\PaymentAllocation;
use App\Models\Person;
use App\Models\Pledge;
use App\Models\ReceiverAccount;
use App\Models\Role;
use App\Models\User;
use App\Services\CollectionPaymentService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class PublicPortalDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('PublicPortalDemoSeeder cannot be executed in production environments!');

            return;
        }

        $this->command->info('Resetting previous demo data for idempotent setup...');
        Artisan::call('demo:public-portal-cleanup', ['--force' => true]);

        DB::transaction(function () {
            // 1. Roles & Classifications
            $donorClass = Classification::firstOrCreate(
                ['slug' => 'donor'],
                ['name_en' => 'Donor', 'name_bn' => 'দাতা']
            );

            $devoteeRole = Role::firstOrCreate(
                ['slug' => 'devotee'],
                ['name_en' => 'Devotee / Member', 'name_bn' => 'ভক্ত / সাধারণ সদস্য', 'is_system' => true]
            );

            $superAdminRole = Role::firstOrCreate(
                ['slug' => 'super_admin'],
                ['name_en' => 'Super Administrator', 'name_bn' => 'প্রধান প্রশাসক', 'is_system' => true]
            );

            $templeAdminRole = Role::firstOrCreate(
                ['slug' => 'temple_admin'],
                ['name_en' => 'Temple Administrator', 'name_bn' => 'মন্দির প্রশাসক', 'is_system' => true]
            );

            // 2. Ensure / Update Admin Accounts
            $superAdmin = User::updateOrCreate(
                ['email' => 'admin@temple.local'],
                [
                    'name' => 'Pradhan Sebak (Super Admin)',
                    'password' => Hash::make('Admin@123456'),
                    'phone' => '+8801700000001',
                    'status' => 'active',
                    'preferred_locale' => 'bn',
                    'address' => 'Central Temple Complex, Main Road',
                    'email_verified_at' => now(),
                ]
            );
            $superAdmin->roles()->sync([$superAdminRole->id]);

            $templeAdmin = User::updateOrCreate(
                ['email' => 'manager@temple.local'],
                [
                    'name' => 'Mandir Karjonirbahi (Temple Admin)',
                    'password' => Hash::make('Manager@123456'),
                    'phone' => '+8801700000002',
                    'status' => 'active',
                    'preferred_locale' => 'bn',
                    'address' => 'Temple Office Building',
                    'email_verified_at' => now(),
                ]
            );
            $templeAdmin->roles()->sync([$templeAdminRole->id]);

            // 3. Locate Financial Accounts
            $bankFinAccount = FinancialAccount::where('account_code', '1020')->firstOrFail();
            $mfsFinAccount = FinancialAccount::where('account_code', '1030')->firstOrFail();

            // 4. Create 4 Active Receiver Accounts
            $bkashAccount = ReceiverAccount::create([
                'payment_method' => ReceiverAccount::METHOD_BKASH,
                'account_name' => 'Central Temple bKash Merchant',
                'account_number' => '01711001122',
                'account_type' => ReceiverAccount::TYPE_MERCHANT,
                'financial_account_id' => $mfsFinAccount->id,
                'instructions_en' => 'Select "Make Payment" from bKash App or dial *247#. Enter counter: 1, reference: your pledge code.',
                'instructions_bn' => 'বিকাশ অ্যাপ থেকে "পেমেন্ট" সিলেক্ট করুন অথবা *২৪৭# ডায়াল করুন। কাউন্টার নম্বর ১ দিন এবং রেফারেন্সে আপনার মানত কোড লিখুন।',
                'is_active' => true,
                'is_verified' => true,
                'verified_at' => now(),
                'verified_by' => $superAdmin->id,
                'display_order' => 1,
                'created_by' => $superAdmin->id,
                'updated_by' => $superAdmin->id,
            ]);

            $nagadAccount = ReceiverAccount::create([
                'payment_method' => ReceiverAccount::METHOD_NAGAD,
                'account_name' => 'Central Temple Nagad Merchant',
                'account_number' => '01811002233',
                'account_type' => ReceiverAccount::TYPE_MERCHANT,
                'financial_account_id' => $mfsFinAccount->id,
                'instructions_en' => 'Select "Merchant Pay" in Nagad App. Enter counter: 1 and reference code.',
                'instructions_bn' => 'নগদ অ্যাপে "মার্চেন্ট পে" নির্বাচন করুন। কাউন্টার নম্বর ১ ও রেফারেন্স কোড দিন।',
                'is_active' => true,
                'is_verified' => true,
                'verified_at' => now(),
                'verified_by' => $superAdmin->id,
                'display_order' => 2,
                'created_by' => $superAdmin->id,
                'updated_by' => $superAdmin->id,
            ]);

            $rocketAccount = ReceiverAccount::create([
                'payment_method' => ReceiverAccount::METHOD_ROCKET,
                'account_name' => 'Central Temple Rocket Merchant',
                'account_number' => '01911003344',
                'account_type' => ReceiverAccount::TYPE_MERCHANT,
                'financial_account_id' => $mfsFinAccount->id,
                'instructions_en' => 'Select "Merchant Payment" via Rocket App or dial *322#.',
                'instructions_bn' => 'রকেট অ্যাপ অথবা *৩২২# ডায়াল করে "মার্চেন্ট পেমেন্ট" নির্বাচন করুন।',
                'is_active' => true,
                'is_verified' => true,
                'verified_at' => now(),
                'verified_by' => $superAdmin->id,
                'display_order' => 3,
                'created_by' => $superAdmin->id,
                'updated_by' => $superAdmin->id,
            ]);

            $bankTransferAccount = ReceiverAccount::create([
                'payment_method' => ReceiverAccount::METHOD_BANK_TRANSFER,
                'account_name' => 'Uttara Central Temple Management Fund',
                'account_number' => '4401200033445',
                'account_type' => ReceiverAccount::TYPE_CURRENT,
                'bank_name' => 'Sonali Bank PLC',
                'branch_name' => 'Uttara Model Town Branch',
                'routing_number' => '200271563',
                'swift_code' => 'BSONBDDH',
                'financial_account_id' => $bankFinAccount->id,
                'instructions_en' => 'Transfer via BEFTN/NPSB/RTGS or deposit at any Sonali Bank branch. Include your name and pledge code in reference.',
                'instructions_bn' => 'সোনালী ব্যাংকের যেকোনো শাখা অথবা অনলাইন ব্যাংকিং (BEFTN/NPSB)-এর মাধ্যমে জমা দিন। রেফারেন্সে নাম ও মানত কোড লিখুন।',
                'is_active' => true,
                'is_verified' => true,
                'verified_at' => now(),
                'verified_by' => $superAdmin->id,
                'display_order' => 4,
                'created_by' => $superAdmin->id,
                'updated_by' => $superAdmin->id,
            ]);

            // 5. Create 4 Distinct Events
            // Event 1: Active, Published (Durga Puja 2026) - Open for payments today
            $durgaPuja = Event::create([
                'name_en' => 'Sharodiya Durga Puja 2026',
                'name_bn' => 'শারদীয় দুর্গাপূজা ২০২৬',
                'event_code' => 'EVT-DEMO-DP26',
                'slug' => 'durga-puja-2026',
                'event_category_id' => 1,
                'event_year' => 2026,
                'status' => Event::STATUS_ACTIVE,
                'publication_status' => Event::PUB_STATUS_PUBLISHED,
                'published_at' => now()->subDays(30),
                'published_by' => $superAdmin->id,
                'start_date' => '2026-10-15',
                'end_date' => '2026-11-20',
                'collection_start_date' => '2026-10-01',
                'collection_end_date' => '2026-11-30',
                'venue' => 'Central Temple Complex & Mandap Grounds, Sector 3, Uttara',
                'short_description_en' => 'The grand annual Sharodiya Durga Puja celebration with daily puja, anjali, cultural programs, and maha prasadam.',
                'short_description_bn' => 'মহাধুমধামে বার্ষিক শারদীয় দুর্গাপূজা মহোৎসব, প্রতিদিনের পুজো, অঞ্জলি, সাংস্কৃতিক অনুষ্ঠান ও মহাপ্রসাদ বিতরণ।',
                'description_en' => 'Join us in the divine celebration of Sharodiya Durga Puja. Devotees may contribute towards Anandabazar, Bhog seva, idol ornamentation, light decoration, and community prasadam.',
                'description_bn' => 'শারদীয় দুর্গোৎসবে শ্রীশ্রী মায়ের চরণে অংশ নিন। ভক্তবৃন্দ আনন্দবাজার, ভোগ সেবা, প্রতিমা অলংকরণ, আলোকসজ্জা এবং মহাপ্রসাদ সেবায় অংশগ্রহণ করতে পারেন।',
                'created_by' => $superAdmin->id,
                'updated_by' => $superAdmin->id,
            ]);

            // Event 2: Upcoming, Published (Shyama Kali Puja 2026)
            $kaliPuja = Event::create([
                'name_en' => 'Shyama Kali Puja & Deepavali 2026',
                'name_bn' => 'শ্রীশ্রী শ্যামাপূজা ও দীপাবলি ২০২৬',
                'event_code' => 'EVT-DEMO-KP26',
                'slug' => 'shyama-kali-puja-2026',
                'event_category_id' => 3,
                'event_year' => 2026,
                'status' => Event::STATUS_UPCOMING,
                'publication_status' => Event::PUB_STATUS_PUBLISHED,
                'published_at' => now()->subDays(10),
                'published_by' => $superAdmin->id,
                'start_date' => '2026-11-20',
                'end_date' => '2026-11-25',
                'collection_start_date' => '2026-11-01',
                'collection_end_date' => '2026-12-15',
                'venue' => 'Main Temple Sanctum',
                'short_description_en' => 'Auspicious Shyama Kali Puja at midnight with 1,008 deepa lighting and night-long bhajan.',
                'short_description_bn' => 'অমাবস্যা তিথিতে মধ্যরাতে ১,০০৮ প্রদীপ প্রজ্বালন ও সারারাতব্যাপী ভজন সহযোগে শ্রীশ্রী শ্যামাপূজা।',
                'description_en' => 'Celebrate the sacred festival of light and spiritual awakening during Deepavali and Shyama Puja.',
                'description_bn' => 'দীপাবলি ও শ্যামাপূজার পুণ্যলগ্নে আলোক মহোৎসবে অংশগ্রহণ করুন।',
                'created_by' => $superAdmin->id,
                'updated_by' => $superAdmin->id,
            ]);

            // Event 3: Closed, Published (Janmashtami 2026)
            $janmashtami = Event::create([
                'name_en' => 'Shri Krishna Janmashtami 2026',
                'name_bn' => 'শ্রীকৃষ্ণ জন্মাষ্টমী মহোৎসব ২০২৬',
                'event_code' => 'EVT-DEMO-JM26',
                'slug' => 'janmashtami-festival-2026',
                'event_category_id' => 6,
                'event_year' => 2026,
                'status' => Event::STATUS_CLOSED,
                'publication_status' => Event::PUB_STATUS_PUBLISHED,
                'published_at' => now()->subDays(90),
                'published_by' => $superAdmin->id,
                'closed_at' => now()->subDays(60),
                'closed_by' => $superAdmin->id,
                'closing_mode' => Event::MODE_CLOSE_AND_LOCK,
                'closing_reason' => 'Celebration completed and audited successfully.',
                'start_date' => '2026-08-20',
                'end_date' => '2026-08-26',
                'collection_start_date' => '2026-08-01',
                'collection_end_date' => '2026-08-31',
                'venue' => 'Radha Krishna Mandir Hall',
                'short_description_en' => 'Sacred appearance day celebration of Bhagavan Shri Krishna with grand abhishekam.',
                'short_description_bn' => 'শ্রীশ্রী রাধাকৃষ্ণ মন্দিরে মহাধুমধামে ভগবান শ্রীকৃষ্ণের আবির্ভাব তিথি মহোৎসব ও অভিষেক।',
                'description_en' => 'Historical celebration archive of Janmashtami 2026.',
                'description_bn' => '২০২৬ সালের জন্মাষ্টমী উৎসবের সফল সমাপন ও আর্কাইভ।',
                'created_by' => $superAdmin->id,
                'updated_by' => $superAdmin->id,
            ]);

            // Event 4: Draft, Unpublished (Saraswati Puja 2027) - Must be hidden from public portal
            $saraswatiPuja = Event::create([
                'name_en' => 'Saraswati Puja Planning 2027',
                'name_bn' => 'সরস্বতী পূজা প্রস্তুতি ২০২৭',
                'event_code' => 'EVT-DEMO-SP27',
                'slug' => 'saraswati-puja-planning-2027',
                'event_category_id' => 4,
                'event_year' => 2027,
                'status' => Event::STATUS_DRAFT,
                'publication_status' => Event::PUB_STATUS_DRAFT,
                'start_date' => '2027-02-10',
                'end_date' => '2027-02-15',
                'collection_start_date' => '2027-01-01',
                'collection_end_date' => '2027-02-28',
                'venue' => 'Temple Courtyard',
                'short_description_en' => 'Internal draft event for Saraswati Puja 2027 committee planning.',
                'short_description_bn' => 'সরস্বতী পূজা ২০২৭ অভ্যন্তরীণ প্রস্তুতি ও খসড়া ইভেন্ট।',
                'description_en' => 'Internal draft planning. Not accessible to public.',
                'description_bn' => 'অভ্যন্তরীণ খসড়া। জনসাধারণের প্রবেশাধিকার নেই।',
                'created_by' => $superAdmin->id,
                'updated_by' => $superAdmin->id,
            ]);

            // 6. Create Dummy Storage Proof & Attachment Files
            Storage::disk('local')->put(
                'payment_proofs/demo_proof_prabir.jpg',
                'DEMO PAYMENT PROOF IMAGE CONTENT FOR PRABIR SENGUPTA (BKASH TRINARY DATA)'
            );
            Storage::disk('local')->put(
                'payment_proofs/demo_proof_tapash.jpg',
                'DEMO PAYMENT PROOF IMAGE CONTENT FOR TAPASH PAUL (NAGAD TRINARY DATA)'
            );
            Storage::disk('local')->put(
                'complaint_attachments/demo_complaint_tapash.pdf',
                '%PDF-1.4 DEMO COMPLAINT ATTACHMENT BANK STATEMENT FOR TAPASH PAUL'
            );

            // 7. Seed 10 Fictional Donors with Linked Person Profiles and Users
            $donorDefinitions = [
                [
                    'code' => 'DEV-DEMO-001',
                    'name_en' => 'Subir Ghosh',
                    'name_bn' => 'সুবীর ঘোষ',
                    'email' => 'subir.donor@temple.local',
                    'phone' => '+8801711100001',
                    'gender' => 'male',
                    'visibility' => 'public',
                    'address' => 'House 12, Road 4, Sector 3, Uttara, Dhaka',
                    'pledge_amount' => 10000.00,
                    'pay_amount' => 10000.00,
                    'action' => 'verify',
                    'method' => 'bkash',
                    'receiver' => $bkashAccount,
                    'trx_id' => 'BK927164901',
                ],
                [
                    'code' => 'DEV-DEMO-002',
                    'name_en' => 'Ananya Roy',
                    'name_bn' => 'অনন্যা রায়',
                    'email' => 'ananya.donor@temple.local',
                    'phone' => '+8801711100002',
                    'gender' => 'female',
                    'visibility' => 'public',
                    'address' => 'Apartment 4B, Green Road, Dhanmondi, Dhaka',
                    'pledge_amount' => 15000.00,
                    'pay_amount' => 8000.00,
                    'action' => 'verify',
                    'method' => 'nagad',
                    'receiver' => $nagadAccount,
                    'trx_id' => 'NG81928401',
                ],
                [
                    'code' => 'DEV-DEMO-003',
                    'name_en' => 'Bikash Chandra Das',
                    'name_bn' => 'বিকাশ চন্দ্র দাস',
                    'email' => 'bikash.donor@temple.local',
                    'phone' => '+8801711100003',
                    'gender' => 'male',
                    'visibility' => 'anonymous',
                    'address' => 'Old Temple Lane, Lalbagh, Old Dhaka',
                    'pledge_amount' => 12000.00,
                    'pay_amount' => 12000.00,
                    'action' => 'verify',
                    'method' => 'bank_transfer',
                    'receiver' => $bankTransferAccount,
                    'trx_id' => 'SB4401928374',
                ],
                [
                    'code' => 'DEV-DEMO-004',
                    'name_en' => 'Sunil Kumar Sen',
                    'name_bn' => 'সুনীল কুমার সেন',
                    'email' => 'sunil.donor@temple.local',
                    'phone' => '+8801711100004',
                    'gender' => 'male',
                    'visibility' => 'private',
                    'address' => 'Block C, Bashundhara R/A, Dhaka',
                    'pledge_amount' => 20000.00,
                    'pay_amount' => 10000.00,
                    'action' => 'verify',
                    'method' => 'rocket',
                    'receiver' => $rocketAccount,
                    'trx_id' => 'RK99201481',
                ],
                [
                    'code' => 'DEV-DEMO-005',
                    'name_en' => 'Prabir Sengupta',
                    'name_bn' => 'প্রবীর সেনগুপ্ত',
                    'email' => 'prabir.donor@temple.local',
                    'phone' => '+8801711100005',
                    'gender' => 'male',
                    'visibility' => 'public',
                    'address' => 'House 5, Road 11, Sector 4, Uttara, Dhaka',
                    'pledge_amount' => 8000.00,
                    'pay_amount' => 8000.00,
                    'action' => 'pending',
                    'method' => 'bkash',
                    'receiver' => $bkashAccount,
                    'trx_id' => 'BK771092441',
                    'proof' => 'payment_proofs/demo_proof_prabir.jpg',
                ],
                [
                    'code' => 'DEV-DEMO-006',
                    'name_en' => 'Tapash Paul',
                    'name_bn' => 'তাপস পাল',
                    'email' => 'tapash.donor@temple.local',
                    'phone' => '+8801711100006',
                    'gender' => 'male',
                    'visibility' => 'public',
                    'address' => 'Plot 18, Shantinagar, Dhaka',
                    'pledge_amount' => 5000.00,
                    'pay_amount' => 5000.00,
                    'action' => 'reject',
                    'method' => 'nagad',
                    'receiver' => $nagadAccount,
                    'trx_id' => 'NG44109211',
                    'proof' => 'payment_proofs/demo_proof_tapash.jpg',
                ],
                [
                    'code' => 'DEV-DEMO-007',
                    'name_en' => 'Debabrata Bhattacharya',
                    'name_bn' => 'দেবব্রত ভট্টাচার্য',
                    'email' => 'debabrata.donor@temple.local',
                    'phone' => '+8801711100007',
                    'gender' => 'male',
                    'visibility' => 'public',
                    'address' => 'Road 7, Banani, Dhaka',
                    'pledge_amount' => 6000.00,
                    'pay_amount' => 6000.00,
                    'action' => 'reverse',
                    'method' => 'bank_transfer',
                    'receiver' => $bankTransferAccount,
                    'trx_id' => 'SB881029412',
                ],
                [
                    'code' => 'DEV-DEMO-008',
                    'name_en' => 'Madhuri Chakraborty',
                    'name_bn' => 'মাধুরী চক্রবর্তী',
                    'email' => 'madhuri.donor@temple.local',
                    'phone' => '+8801711100008',
                    'gender' => 'female',
                    'visibility' => 'public',
                    'address' => 'Road 2, Sector 1, Uttara, Dhaka',
                    'pledge_amount' => null, // Voluntary walk-in / ad-hoc donation without prior pledge commitment
                    'pay_amount' => 3000.00,
                    'action' => 'verify',
                    'method' => 'bkash',
                    'receiver' => $bkashAccount,
                    'trx_id' => 'BK330198274',
                ],
                [
                    'code' => 'DEV-DEMO-009',
                    'name_en' => 'Kalyan Kumar Bose',
                    'name_bn' => 'কল্যাণ কুমার বসু',
                    'email' => 'kalyan.donor@temple.local',
                    'phone' => '+8801711100009',
                    'gender' => 'male',
                    'visibility' => 'public',
                    'address' => 'House 45, Lake Drive, Uttara Sector 7, Dhaka',
                    'pledge_amount' => 5000.00,
                    'pay_amount' => 7000.00, // Excess 2,000 BDT automatically allocated to voluntary donation
                    'action' => 'verify',
                    'method' => 'bkash',
                    'receiver' => $bkashAccount,
                    'trx_id' => 'BK550192837',
                ],
                [
                    'code' => 'DEV-DEMO-010',
                    'name_en' => 'Gopal Chandra Dey',
                    'name_bn' => 'গোপাল চন্দ্র দে',
                    'email' => 'gopal.donor@temple.local',
                    'phone' => '+8801711100010',
                    'gender' => 'male',
                    'visibility' => 'public',
                    'address' => 'Old Post Office Road, Sutrapur, Dhaka',
                    'pledge_amount' => 5000.00,
                    'pay_amount' => 0.00,
                    'action' => 'none', // Fully unpaid pledge
                    'method' => null,
                    'receiver' => null,
                    'trx_id' => null,
                ],
            ];

            $paymentService = app(CollectionPaymentService::class);
            $seededEntities = [];

            foreach ($donorDefinitions as $index => $def) {
                // Create Person
                $person = Person::create([
                    'person_code' => $def['code'],
                    'name_en' => $def['name_en'],
                    'name_bn' => $def['name_bn'],
                    'phone' => $def['phone'],
                    'email' => $def['email'],
                    'gender' => $def['gender'],
                    'status' => 'active',
                    'visibility' => $def['visibility'],
                    'present_address' => $def['address'],
                    'permanent_address' => $def['address'],
                    'created_by' => $superAdmin->id,
                    'updated_by' => $superAdmin->id,
                ]);
                $person->assignClassification($donorClass);

                // Create User
                $user = User::create([
                    'person_id' => $person->id,
                    'name' => $def['name_en'],
                    'email' => $def['email'],
                    'phone' => $def['phone'],
                    'password' => Hash::make('Donor@123456'),
                    'status' => 'active',
                    'preferred_locale' => 'en',
                    'address' => $def['address'],
                    'email_verified_at' => now(),
                ]);
                $user->roles()->sync([$devoteeRole->id]);

                DevoteeCredential::create([
                    'user_id' => $user->id,
                    'encrypted_password' => Crypt::encryptString('Donor@123456'),
                ]);

                // Create Pledge if defined
                $pledge = null;
                if ($def['pledge_amount'] !== null) {
                    $pledge = Pledge::create([
                        'pledge_code' => Pledge::generatePledgeCode(2026),
                        'event_id' => $durgaPuja->id,
                        'person_id' => $person->id,
                        'pledge_year' => 2026,
                        'pledged_amount' => $def['pledge_amount'],
                        'pledged_on' => '2026-10-01',
                        'purpose' => 'Sharodiya Durga Puja Maha Bhog & Mandap Seva',
                        'notes' => 'Devotee annual pledge commitment',
                        'status' => Pledge::STATUS_UNPAID,
                        'visibility' => $def['visibility'],
                        'created_by' => $superAdmin->id,
                        'updated_by' => $superAdmin->id,
                    ]);
                }

                // Process Payment if amount > 0
                $payment = null;
                if ($def['pay_amount'] > 0.0 && $def['method'] && $def['receiver']) {
                    $isVoluntary = ($pledge === null);
                    $paymentDate = Carbon::parse('2026-10-18');

                    $payment = CollectionPayment::create([
                        'payment_code' => CollectionPayment::generatePaymentCode(2026),
                        'payer_person_id' => $person->id,
                        'public_donor_name' => $person->name_en,
                        'public_donor_phone' => $person->phone,
                        'public_donor_email' => $person->email,
                        'collection_type' => $isVoluntary ? CollectionPayment::TYPE_EVENT_DONATION : CollectionPayment::TYPE_EVENT_PLEDGE,
                        'event_id' => $durgaPuja->id,
                        'pledge_id' => $pledge?->id,
                        'receiver_account_id' => $def['receiver']->id,
                        'payment_date' => $paymentDate->toDateString(),
                        'payment_year' => 2026,
                        'amount' => $def['pay_amount'],
                        'payment_method' => $def['method'],
                        'transaction_reference' => $def['trx_id'],
                        'sender_mobile' => $person->phone,
                        'payment_notes' => 'Public portal online submission',
                        'payment_proof_path' => $def['proof'] ?? null,
                        'status' => CollectionPayment::STATUS_PENDING,
                        'is_public_submission' => true,
                        'submitted_by' => $user->id,
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);

                    PaymentAllocation::create([
                        'collection_payment_id' => $payment->id,
                        'target_type' => $isVoluntary ? PaymentAllocation::TARGET_VOLUNTARY_EVENT : PaymentAllocation::TARGET_PLEDGE,
                        'pledge_id' => $pledge?->id,
                        'event_id' => $durgaPuja->id,
                        'amount' => $payment->amount,
                        'is_active' => true,
                    ]);

                    // Apply Action
                    if ($def['action'] === 'verify') {
                        $payment = $paymentService->verifyPayment($payment, $superAdmin->id, 'Verified via online merchant payment verification');
                    } elseif ($def['action'] === 'reverse') {
                        $payment = $paymentService->verifyPayment($payment, $superAdmin->id, 'Initial merchant gateway verification');
                        $payment = $paymentService->reversePayment($payment, $superAdmin->id, 'Disputed bank chargeback / erroneous duplicate transfer');
                    } elseif ($def['action'] === 'reject') {
                        $payment = $paymentService->rejectPayment($payment, $superAdmin->id, 'MFS Transaction ID could not be matched with bank statement. Please submit valid TrxID.');
                    }
                    // 'pending' remains in pending_verification status
                }

                $seededEntities[$def['code']] = [
                    'person' => $person,
                    'user' => $user,
                    'pledge' => $pledge,
                    'payment' => $payment,
                ];
            }

            // 8. Create 5 Distinct Complaints
            // Complaint 1: Prabir Sengupta (submitted) - payment_not_updated
            $prabirData = $seededEntities['DEV-DEMO-005'];
            $c1 = Complaint::create([
                'tracking_number' => 'CMP-DEMO-001',
                'complainant_name' => $prabirData['person']->name_en,
                'complainant_phone' => $prabirData['person']->phone,
                'complainant_email' => $prabirData['person']->email,
                'person_id' => $prabirData['person']->id,
                'event_id' => $durgaPuja->id,
                'collection_payment_id' => $prabirData['payment']->id,
                'category' => Complaint::CATEGORY_PAYMENT_NOT_UPDATED,
                'subject' => 'bKash Payment Not Updated on Portal',
                'description' => 'I submitted 8,000 BDT via bKash yesterday (TrxID: BK771092441), but it still shows pending verification in my portal dashboard. Please confirm receipt.',
                'attachment_path' => null,
                'status' => Complaint::STATUS_SUBMITTED,
                'priority' => Complaint::PRIORITY_MEDIUM,
            ]);
            ComplaintActivity::create([
                'complaint_id' => $c1->id,
                'actor_id' => $prabirData['user']->id,
                'activity_type' => ComplaintActivity::TYPE_SUBMISSION,
                'previous_status' => null,
                'new_status' => Complaint::STATUS_SUBMITTED,
                'content' => 'Devotee submitted payment update inquiry via public portal.',
                'visibility' => ComplaintActivity::VISIBILITY_DONOR,
            ]);

            // Complaint 2: Tapash Paul (under_review) - wrong_paid_amount with attachment
            $tapashData = $seededEntities['DEV-DEMO-006'];
            $c2 = Complaint::create([
                'tracking_number' => 'CMP-DEMO-002',
                'complainant_name' => $tapashData['person']->name_en,
                'complainant_phone' => $tapashData['person']->phone,
                'complainant_email' => $tapashData['person']->email,
                'person_id' => $tapashData['person']->id,
                'event_id' => $durgaPuja->id,
                'collection_payment_id' => $tapashData['payment']->id,
                'category' => Complaint::CATEGORY_WRONG_PAID_AMOUNT,
                'subject' => 'Rejection of Valid Nagad Payment',
                'description' => 'My payment submission of 5,000 BDT was rejected citing TrxID mismatch. I have attached my bank SMS and payment screenshot showing successful debit.',
                'attachment_path' => 'complaint_attachments/demo_complaint_tapash.pdf',
                'status' => Complaint::STATUS_UNDER_REVIEW,
                'priority' => Complaint::PRIORITY_HIGH,
                'assigned_to' => $templeAdmin->id,
            ]);
            ComplaintActivity::create([
                'complaint_id' => $c2->id,
                'actor_id' => $tapashData['user']->id,
                'activity_type' => ComplaintActivity::TYPE_SUBMISSION,
                'previous_status' => null,
                'new_status' => Complaint::STATUS_SUBMITTED,
                'content' => 'Devotee submitted payment dispute inquiry with bank attachment.',
                'visibility' => ComplaintActivity::VISIBILITY_DONOR,
            ]);
            ComplaintActivity::create([
                'complaint_id' => $c2->id,
                'actor_id' => $superAdmin->id,
                'activity_type' => ComplaintActivity::TYPE_ASSIGNMENT,
                'previous_status' => Complaint::STATUS_SUBMITTED,
                'new_status' => Complaint::STATUS_UNDER_REVIEW,
                'content' => 'Assigned to Temple Manager for bank statement reconciliation.',
                'visibility' => ComplaintActivity::VISIBILITY_INTERNAL,
            ]);

            // Complaint 3: Ananya Roy (replied) - wrong_due_amount with official reply & internal note
            $ananyaData = $seededEntities['DEV-DEMO-002'];
            $c3 = Complaint::create([
                'tracking_number' => 'CMP-DEMO-003',
                'complainant_name' => $ananyaData['person']->name_en,
                'complainant_phone' => $ananyaData['person']->phone,
                'complainant_email' => $ananyaData['person']->email,
                'person_id' => $ananyaData['person']->id,
                'event_id' => $durgaPuja->id,
                'collection_payment_id' => $ananyaData['payment']->id,
                'category' => Complaint::CATEGORY_WRONG_DUE_AMOUNT,
                'subject' => 'Clarification on Outstanding Pledge Due',
                'description' => 'I noticed my due shows 7,000 BDT. I wanted to verify if my installment from earlier was recorded correctly.',
                'attachment_path' => null,
                'status' => Complaint::STATUS_REPLIED,
                'priority' => Complaint::PRIORITY_MEDIUM,
                'assigned_to' => $superAdmin->id,
                'admin_response' => 'Namaskar Ananya Roy. We verified your ledger entry. Your total pledge was 15,000 BDT and your verified payment was 8,000 BDT, leaving an exact remaining balance of 7,000 BDT. You can pay the remainder anytime before the festival ends. Thank you.',
                'internal_notes' => 'Audited against Journal Voucher and Nagad Merchant Statement. Calculations match.',
            ]);
            ComplaintActivity::create([
                'complaint_id' => $c3->id,
                'actor_id' => $ananyaData['user']->id,
                'activity_type' => ComplaintActivity::TYPE_SUBMISSION,
                'previous_status' => null,
                'new_status' => Complaint::STATUS_SUBMITTED,
                'content' => 'Devotee submitted due amount verification request.',
                'visibility' => ComplaintActivity::VISIBILITY_DONOR,
            ]);
            ComplaintActivity::create([
                'complaint_id' => $c3->id,
                'actor_id' => $superAdmin->id,
                'activity_type' => ComplaintActivity::TYPE_REPLY,
                'previous_status' => Complaint::STATUS_SUBMITTED,
                'new_status' => Complaint::STATUS_REPLIED,
                'content' => $c3->admin_response,
                'visibility' => ComplaintActivity::VISIBILITY_DONOR,
            ]);
            ComplaintActivity::create([
                'complaint_id' => $c3->id,
                'actor_id' => $superAdmin->id,
                'activity_type' => ComplaintActivity::TYPE_INTERNAL_NOTE,
                'previous_status' => Complaint::STATUS_REPLIED,
                'new_status' => Complaint::STATUS_REPLIED,
                'content' => $c3->internal_notes,
                'visibility' => ComplaintActivity::VISIBILITY_INTERNAL,
            ]);

            // Complaint 4: Kalyan Kumar Bose (resolved) - other with resolution
            $kalyanData = $seededEntities['DEV-DEMO-009'];
            $c4 = Complaint::create([
                'tracking_number' => 'CMP-DEMO-004',
                'complainant_name' => $kalyanData['person']->name_en,
                'complainant_phone' => $kalyanData['person']->phone,
                'complainant_email' => $kalyanData['person']->email,
                'person_id' => $kalyanData['person']->id,
                'event_id' => $durgaPuja->id,
                'collection_payment_id' => $kalyanData['payment']->id,
                'category' => Complaint::CATEGORY_OTHER,
                'subject' => 'Prasadam Coupons Distribution Schedule',
                'description' => 'Could you please inform me where and when family prasadam coupons can be collected for Maha Ashtami?',
                'attachment_path' => null,
                'status' => Complaint::STATUS_RESOLVED,
                'priority' => Complaint::PRIORITY_LOW,
                'assigned_to' => $superAdmin->id,
                'admin_response' => 'Namaskar Kalyan Bose. Prasadam coupons are available at the Temple Information Desk starting Oct 18 between 10:00 AM and 6:00 PM. Hare Krishna.',
                'internal_notes' => 'Informed devotee via phone call and portal reply.',
                'resolved_at' => now()->subDays(1),
                'resolved_by' => $superAdmin->id,
            ]);
            ComplaintActivity::create([
                'complaint_id' => $c4->id,
                'actor_id' => $kalyanData['user']->id,
                'activity_type' => ComplaintActivity::TYPE_SUBMISSION,
                'previous_status' => null,
                'new_status' => Complaint::STATUS_SUBMITTED,
                'content' => 'Devotee submitted inquiry regarding prasadam coupon schedule.',
                'visibility' => ComplaintActivity::VISIBILITY_DONOR,
            ]);
            ComplaintActivity::create([
                'complaint_id' => $c4->id,
                'actor_id' => $superAdmin->id,
                'activity_type' => ComplaintActivity::TYPE_RESOLUTION,
                'previous_status' => Complaint::STATUS_SUBMITTED,
                'new_status' => Complaint::STATUS_RESOLVED,
                'content' => 'Devotee informed and query marked resolved.',
                'visibility' => ComplaintActivity::VISIBILITY_DONOR,
            ]);

            // Complaint 5: Gopal Chandra Dey (rejected) - general_inquiry
            $gopalData = $seededEntities['DEV-DEMO-010'];
            $c5 = Complaint::create([
                'tracking_number' => 'CMP-DEMO-005',
                'complainant_name' => $gopalData['person']->name_en,
                'complainant_phone' => $gopalData['person']->phone,
                'complainant_email' => $gopalData['person']->email,
                'person_id' => $gopalData['person']->id,
                'event_id' => $durgaPuja->id,
                'collection_payment_id' => null,
                'category' => Complaint::CATEGORY_GENERAL_INQUIRY,
                'subject' => 'Request for Commercial Catering Banner on Website',
                'description' => 'Can I put my private catering service advertisement banner on the Durga Puja event page?',
                'attachment_path' => null,
                'status' => Complaint::STATUS_REJECTED,
                'priority' => Complaint::PRIORITY_LOW,
                'assigned_to' => $superAdmin->id,
                'admin_response' => 'Namaskar Gopal Dey. The temple management guidelines strictly prohibit commercial business advertisements on the devotional portal. Thank you for your understanding.',
                'internal_notes' => 'Outside temple non-commercial sponsorship policy.',
            ]);
            ComplaintActivity::create([
                'complaint_id' => $c5->id,
                'actor_id' => $gopalData['user']->id,
                'activity_type' => ComplaintActivity::TYPE_SUBMISSION,
                'previous_status' => null,
                'new_status' => Complaint::STATUS_SUBMITTED,
                'content' => 'Commercial banner inquiry submitted.',
                'visibility' => ComplaintActivity::VISIBILITY_DONOR,
            ]);
            ComplaintActivity::create([
                'complaint_id' => $c5->id,
                'actor_id' => $superAdmin->id,
                'activity_type' => ComplaintActivity::TYPE_REJECTION,
                'previous_status' => Complaint::STATUS_SUBMITTED,
                'new_status' => Complaint::STATUS_REJECTED,
                'content' => 'Proposal rejected according to commercial advertisement guidelines.',
                'visibility' => ComplaintActivity::VISIBILITY_DONOR,
            ]);
        });

        $this->command->info('PublicPortalDemoSeeder executed successfully.');
    }
}
