<?php

namespace Database\Seeders;

use App\Models\Classification;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\Family;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Phase2DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First ensure classifications are seeded
        $this->call(ClassificationSeeder::class);

        $residentClass = Classification::where('slug', 'resident')->first();
        $donorClass = Classification::where('slug', 'donor')->first();
        $committeeClass = Classification::where('slug', 'committee_member')->first();
        $volunteerClass = Classification::where('slug', 'volunteer')->first();

        $adminUser = User::where('email', 'superadmin@temple.local')->first() ?? User::first();
        $userId = $adminUser?->id;

        DB::transaction(function () use ($residentClass, $donorClass, $committeeClass, $volunteerClass, $userId) {
            // 1. Create Families
            $chatterjeeFamily = Family::firstOrCreate(
                ['family_code' => 'FAM-0001'],
                [
                    'family_name' => 'Chatterjee Family (চট্টোপাধ্যায় পরিবার)',
                    'phone' => '+8801711000111',
                    'address' => 'House 14, Road 5, Temple Lane, Sector 3, Uttara',
                    'area' => 'Uttara',
                    'status' => 'active',
                    'notes' => 'Longstanding resident family supporting temple foundation.',
                ]
            );

            $majumderFamily = Family::firstOrCreate(
                ['family_code' => 'FAM-0002'],
                [
                    'family_name' => 'Majumder Family (মজুমদার পরিবার)',
                    'phone' => '+8801712000222',
                    'address' => 'Plot 8, Lake Road, Dhanmondi',
                    'area' => 'Dhanmondi',
                    'status' => 'active',
                    'notes' => 'Major temple patrons and active community organizers.',
                ]
            );

            $royChowdhuryFamily = Family::firstOrCreate(
                ['family_code' => 'FAM-0003'],
                [
                    'family_name' => 'Roy Chowdhury Family (রায়চৌধুরী পরিবার)',
                    'phone' => '+8801713000333',
                    'address' => 'Heritage Villa, Old Temple Road, Lalbagh',
                    'area' => 'Old Town',
                    'status' => 'active',
                    'notes' => 'Heritage patron family.',
                ]
            );

            // 2. Create People
            // Dipankar Chatterjee
            $dipankar = Person::firstOrCreate(
                ['person_code' => 'DEV-00001'],
                [
                    'name_en' => 'Dipankar Chatterjee',
                    'name_bn' => 'দীপঙ্কর চট্টোপাধ্যায়',
                    'phone' => '+8801711000111',
                    'email' => 'dipankar.chatterjee@temple.local',
                    'date_of_birth' => '1970-04-15',
                    'gender' => 'male',
                    'blood_group' => 'A+',
                    'father_name' => 'Late Nripendra Chatterjee',
                    'mother_name' => 'Late Shanti Chatterjee',
                    'spouse_name' => 'Ananya Chatterjee',
                    'gotra' => 'Shandilya (শাণ্ডিল্য)',
                    'occupation' => 'Architect & Builder',
                    'present_address' => 'House 14, Road 5, Temple Lane, Uttara',
                    'permanent_address' => 'House 14, Road 5, Temple Lane, Uttara',
                    'emergency_contact_name' => 'Ananya Chatterjee',
                    'emergency_contact_phone' => '+8801711000112',
                    'family_id' => $chatterjeeFamily->id,
                    'family_relationship' => 'head',
                    'status' => 'active',
                    'public_notes' => 'Senior respected elder and executive advisor.',
                    'private_notes' => 'Prefers communication via mobile calls on weekends.',
                    'created_by' => $userId,
                ]
            );
            $dipankar->assignClassification($residentClass);
            $dipankar->assignClassification($donorClass);
            $dipankar->assignClassification($committeeClass);

            // Ananya Chatterjee
            $ananya = Person::firstOrCreate(
                ['person_code' => 'DEV-00002'],
                [
                    'name_en' => 'Ananya Chatterjee',
                    'name_bn' => 'অনন্যা চট্টোপাধ্যায়',
                    'phone' => '+8801711000112',
                    'email' => 'ananya.chatterjee@temple.local',
                    'date_of_birth' => '1975-08-20',
                    'gender' => 'female',
                    'blood_group' => 'B+',
                    'father_name' => 'Hirendra Nath Mukherjee',
                    'mother_name' => 'Sumitra Mukherjee',
                    'spouse_name' => 'Dipankar Chatterjee',
                    'gotra' => 'Shandilya (শাণ্ডিল্য)',
                    'occupation' => 'Educationist & School Principal',
                    'present_address' => 'House 14, Road 5, Temple Lane, Uttara',
                    'permanent_address' => 'House 14, Road 5, Temple Lane, Uttara',
                    'emergency_contact_name' => 'Dipankar Chatterjee',
                    'emergency_contact_phone' => '+8801711000111',
                    'family_id' => $chatterjeeFamily->id,
                    'family_relationship' => 'spouse',
                    'status' => 'active',
                    'public_notes' => 'Leads puja bhog coordination team.',
                    'created_by' => $userId,
                ]
            );
            $ananya->assignClassification($residentClass);
            $ananya->assignClassification($volunteerClass);

            // Sourav Chatterjee
            $sourav = Person::firstOrCreate(
                ['person_code' => 'DEV-00003'],
                [
                    'name_en' => 'Sourav Chatterjee',
                    'name_bn' => 'সৌরভ চট্টোপাধ্যায়',
                    'phone' => '+8801711000113',
                    'email' => 'sourav.chatterjee@temple.local',
                    'date_of_birth' => '2001-11-05',
                    'gender' => 'male',
                    'blood_group' => 'O+',
                    'father_name' => 'Dipankar Chatterjee',
                    'mother_name' => 'Ananya Chatterjee',
                    'gotra' => 'Shandilya (শাণ্ডিল্য)',
                    'occupation' => 'Software Engineer',
                    'present_address' => 'House 14, Road 5, Temple Lane, Uttara',
                    'permanent_address' => 'House 14, Road 5, Temple Lane, Uttara',
                    'family_id' => $chatterjeeFamily->id,
                    'family_relationship' => 'son',
                    'status' => 'active',
                    'public_notes' => 'Youth volunteer coordinator.',
                    'created_by' => $userId,
                ]
            );
            $sourav->assignClassification($residentClass);
            $sourav->assignClassification($volunteerClass);

            // Subrata Majumder
            $subrata = Person::firstOrCreate(
                ['person_code' => 'DEV-00004'],
                [
                    'name_en' => 'Subrata Majumder',
                    'name_bn' => 'সুব্রত মজুমদার',
                    'phone' => '+8801712000222',
                    'email' => 'subrata.majumder@temple.local',
                    'date_of_birth' => '1968-12-10',
                    'gender' => 'male',
                    'blood_group' => 'B+',
                    'father_name' => 'Late Amalesh Majumder',
                    'mother_name' => 'Radharani Majumder',
                    'spouse_name' => 'Manoshi Majumder',
                    'gotra' => 'Kashyapa (কশ্যপ)',
                    'occupation' => 'Businessman & Importer',
                    'present_address' => 'Plot 8, Lake Road, Dhanmondi',
                    'permanent_address' => 'Plot 8, Lake Road, Dhanmondi',
                    'emergency_contact_name' => 'Manoshi Majumder',
                    'emergency_contact_phone' => '+8801712000223',
                    'family_id' => $majumderFamily->id,
                    'family_relationship' => 'head',
                    'status' => 'active',
                    'created_by' => $userId,
                ]
            );
            $subrata->assignClassification($donorClass);
            $subrata->assignClassification($committeeClass);

            // Manoshi Majumder
            $manoshi = Person::firstOrCreate(
                ['person_code' => 'DEV-00005'],
                [
                    'name_en' => 'Manoshi Majumder',
                    'name_bn' => 'মানসী মজুমদার',
                    'phone' => '+8801712000223',
                    'email' => 'manoshi.majumder@temple.local',
                    'date_of_birth' => '1972-03-25',
                    'gender' => 'female',
                    'blood_group' => 'AB+',
                    'spouse_name' => 'Subrata Majumder',
                    'gotra' => 'Kashyapa (কশ্যপ)',
                    'occupation' => 'Social Worker',
                    'present_address' => 'Plot 8, Lake Road, Dhanmondi',
                    'family_id' => $majumderFamily->id,
                    'family_relationship' => 'spouse',
                    'status' => 'active',
                    'created_by' => $userId,
                ]
            );
            $manoshi->assignClassification($residentClass);

            // Bimal Roy Chowdhury
            $bimal = Person::firstOrCreate(
                ['person_code' => 'DEV-00006'],
                [
                    'name_en' => 'Bimal Roy Chowdhury',
                    'name_bn' => 'বিমল রায়চৌধুরী',
                    'phone' => '+8801713000333',
                    'email' => 'bimal.roy@temple.local',
                    'date_of_birth' => '1958-06-12',
                    'gender' => 'male',
                    'blood_group' => 'O+',
                    'gotra' => 'Bharadwaja (ভরদ্বাজ)',
                    'occupation' => 'Industrialist',
                    'present_address' => 'Heritage Villa, Old Temple Road, Lalbagh',
                    'family_id' => $royChowdhuryFamily->id,
                    'family_relationship' => 'head',
                    'status' => 'active',
                    'created_by' => $userId,
                ]
            );
            $bimal->assignClassification($donorClass);
            $bimal->assignClassification($committeeClass);

            // Ratan Das (Individual volunteer without family unit)
            $ratan = Person::firstOrCreate(
                ['person_code' => 'DEV-00007'],
                [
                    'name_en' => 'Ratan Das',
                    'name_bn' => 'রতন দাস',
                    'phone' => '+8801714000444',
                    'email' => 'ratan.das@temple.local',
                    'date_of_birth' => '1985-09-14',
                    'gender' => 'male',
                    'blood_group' => 'A+',
                    'gotra' => 'Sandilya (শাণ্ডিল্য)',
                    'occupation' => 'Temple Electrician & Technician',
                    'present_address' => 'Temple Staff Quarters, Uttara',
                    'status' => 'active',
                    'public_notes' => 'Dedicated evening aarti seva assistant.',
                    'created_by' => $userId,
                ]
            );
            $ratan->assignClassification($volunteerClass);

            // Set primary contacts on families
            $chatterjeeFamily->update(['primary_contact_person_id' => $dipankar->id]);
            $majumderFamily->update(['primary_contact_person_id' => $subrata->id]);
            $royChowdhuryFamily->update(['primary_contact_person_id' => $bimal->id]);

            // 3. Create Committees
            $executiveCommittee = Committee::firstOrCreate(
                ['code' => 'COM-EXEC-2024'],
                [
                    'name_en' => 'Executive Committee 2024-2026',
                    'name_bn' => 'কার্যনির্বাহী পরিষদ ২০২৪-২০২৬',
                    'description_en' => 'Bi-annual governing executive committee responsible for temple policy, assets, and ceremonies.',
                    'description_bn' => 'মন্দির নীতি, স্থাবর সম্পদ এবং সার্বজনীন পূজাদি পরিচালনার দ্বিবার্ষিক কার্যনির্বাহী পরিষদ।',
                    'term_start_date' => '2024-01-01',
                    'term_end_date' => '2026-12-31',
                    'status' => 'active',
                ]
            );

            $pujaCommittee2025 = Committee::firstOrCreate(
                ['code' => 'COM-PUJA-2025'],
                [
                    'name_en' => 'Sharodiya Durga Puja Committee 2025',
                    'name_bn' => 'শারদীয় দুর্গোৎসব উদযাপন কমিটি ২০২৫',
                    'description_en' => 'Special ad-hoc committee for managing the 2025 grand Sharodiya Durga Puja.',
                    'description_bn' => '২০২৫ সালের শারদীয় দুর্গোৎসব বর্ণাঢ্যভাবে উদযাপনের বিশেষ পূজা কমিটি।',
                    'term_start_date' => '2025-07-01',
                    'term_end_date' => '2025-10-31',
                    'status' => 'inactive',
                ]
            );

            // 4. Assign Committee Memberships (Active and Historical)
            // Dipankar -> President (Active in Exec)
            CommitteeMembership::firstOrCreate(
                [
                    'committee_id' => $executiveCommittee->id,
                    'person_id' => $dipankar->id,
                ],
                [
                    'designation_en' => 'President',
                    'designation_bn' => 'সভাপতি',
                    'start_date' => '2024-01-01',
                    'end_date' => '2026-12-31',
                    'status' => 'active',
                    'notes' => 'Presiding officer of the executive council.',
                ]
            );

            // Subrata -> General Secretary (Active in Exec)
            CommitteeMembership::firstOrCreate(
                [
                    'committee_id' => $executiveCommittee->id,
                    'person_id' => $subrata->id,
                ],
                [
                    'designation_en' => 'General Secretary',
                    'designation_bn' => 'সাধারণ সম্পাদক',
                    'start_date' => '2024-01-01',
                    'end_date' => '2026-12-31',
                    'status' => 'active',
                    'notes' => 'Executive secretary responsible for administration.',
                ]
            );

            // Historical memberships in 2025 Puja Committee
            // Bimal -> Convenor (Historical/Ended in 2025 Puja committee)
            CommitteeMembership::firstOrCreate(
                [
                    'committee_id' => $pujaCommittee2025->id,
                    'person_id' => $bimal->id,
                ],
                [
                    'designation_en' => 'Chief Convenor',
                    'designation_bn' => 'প্রধান আহ্বায়ক',
                    'start_date' => '2025-07-01',
                    'end_date' => '2025-10-31',
                    'status' => 'ended',
                    'notes' => 'Successfully concluded 2025 festival tenure.',
                ]
            );

            // Sourav -> Youth Volunteer Lead (Historical/Ended)
            CommitteeMembership::firstOrCreate(
                [
                    'committee_id' => $pujaCommittee2025->id,
                    'person_id' => $sourav->id,
                ],
                [
                    'designation_en' => 'Youth Seva Coordinator',
                    'designation_bn' => 'যুব সেবা সমন্বয়ক',
                    'start_date' => '2025-07-01',
                    'end_date' => '2025-10-31',
                    'status' => 'ended',
                    'notes' => 'Coordinated volunteer crowd security.',
                ]
            );
        });
    }
}
