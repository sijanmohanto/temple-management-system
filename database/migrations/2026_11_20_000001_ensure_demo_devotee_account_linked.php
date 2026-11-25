<?php

use App\Models\Classification;
use App\Models\CollectionPayment;
use App\Models\DevoteeCredential;
use App\Models\Event;
use App\Models\PaymentAllocation;
use App\Models\Person;
use App\Models\Pledge;
use App\Models\ReceiverAccount;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $devoteeUser = User::where('email', 'devotee@temple.local')->first();
        if (! $devoteeUser) {
            return;
        }

        // 1. Ensure Subir Roy exists as a Person profile in Directory
        $person = Person::where('email', 'devotee@temple.local')
            ->orWhere('phone', '+8801700000006')
            ->first();

        $adminUser = User::where('email', 'admin@temple.local')->first() ?? User::first();
        $adminId = $adminUser?->id ?? 1;

        if (! $person) {
            $person = Person::create([
                'person_code' => Person::generatePersonCode(),
                'name_en' => 'Subir Roy',
                'name_bn' => 'সুবীর রায়',
                'phone' => '+8801700000006',
                'email' => 'devotee@temple.local',
                'gender' => 'male',
                'status' => 'active',
                'present_address' => 'Road 12, Devotee Enclave',
                'permanent_address' => 'Road 12, Devotee Enclave',
                'occupation' => 'Devotee & Donor',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]);
        }

        // Assign donor classification if available
        $donorClass = Classification::where('slug', 'donor')->first();
        if ($donorClass && ! $person->classifications()->where('classification_id', $donorClass->id)->exists()) {
            $person->assignClassification($donorClass);
        }

        // 2. Link devotee@temple.local User to Person
        $devoteeUser = User::where('email', 'devotee@temple.local')->first();
        if ($devoteeUser) {
            if (! $devoteeUser->person_id) {
                $devoteeUser->person_id = $person->id;
                $devoteeUser->save();
            }

            DevoteeCredential::updateOrCreate(
                ['user_id' => $devoteeUser->id],
                ['encrypted_password' => Crypt::encryptString('Devotee@123456')]
            );

            // 3. Ensure demo pledge & verified payment exist for running event
            $runningEvent = Event::published()->where('status', Event::STATUS_ACTIVE)->first();
            if ($runningEvent) {
                $existingPledge = Pledge::where('person_id', $person->id)->where('event_id', $runningEvent->id)->first();
                if (! $existingPledge) {
                    $pledge = Pledge::create([
                        'pledge_code' => Pledge::generatePledgeCode($runningEvent->event_year ?: 2026),
                        'event_id' => $runningEvent->id,
                        'person_id' => $person->id,
                        'pledge_year' => $runningEvent->event_year ?: 2026,
                        'pledged_amount' => 5000.00,
                        'pledged_on' => now(),
                        'status' => Pledge::STATUS_PARTIAL,
                        'created_by' => $adminId,
                    ]);

                    $receiver = ReceiverAccount::where('is_active', true)->where('is_verified', true)->first();

                    $payment = CollectionPayment::create([
                        'payment_code' => 'PAY-DEMO-SUBIR01',
                        'collection_type' => CollectionPayment::TYPE_EVENT_PLEDGE,
                        'payer_person_id' => $person->id,
                        'event_id' => $runningEvent->id,
                        'pledge_id' => $pledge->id,
                        'amount' => 2000.00,
                        'payment_method' => 'bkash',
                        'transaction_reference' => 'BK_SUBIR_DEMO_01',
                        'sender_mobile' => '01700000006',
                        'status' => CollectionPayment::STATUS_VERIFIED,
                        'payment_date' => now(),
                        'payment_year' => $runningEvent->event_year ?: 2026,
                        'receiver_account_id' => $receiver?->id,
                        'submitted_by' => $devoteeUser->id,
                        'created_by' => $adminId,
                        'verified_by' => $adminId,
                        'verified_at' => now(),
                    ]);

                    PaymentAllocation::create([
                        'collection_payment_id' => $payment->id,
                        'event_id' => $runningEvent->id,
                        'pledge_id' => $pledge->id,
                        'target_type' => PaymentAllocation::TARGET_PLEDGE,
                        'amount' => 2000.00,
                        'is_active' => true,
                    ]);
                }
            }
        }

        // 4. Link any other demo devotee accounts that match unique Person
        $unlinkedUsers = User::whereNull('person_id')->get();
        foreach ($unlinkedUsers as $u) {
            $matchedPerson = $u->person();
            if ($matchedPerson) {
                $u->person_id = $matchedPerson->id;
                $u->save();

                if ($u->hasRole('devotee') && ! DevoteeCredential::where('user_id', $u->id)->exists()) {
                    DevoteeCredential::create([
                        'user_id' => $u->id,
                        'encrypted_password' => Crypt::encryptString('Donor@123456'),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe non-destructive down
    }
};
