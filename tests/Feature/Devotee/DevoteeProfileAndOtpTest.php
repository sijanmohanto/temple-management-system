<?php

namespace Tests\Feature\Devotee;

use App\Mail\DevoteeOtpMail;
use App\Models\DevoteeCredential;
use App\Models\EmailOtp;
use App\Models\Person;
use App\Models\User;
use App\Services\DevoteeCredentialService;
use App\Services\EmailOtpService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DevoteeProfileAndOtpTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Person $devoteePerson;

    protected User $devoteeUser;

    protected string $initialPassword = 'OldPassword@2026';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->adminUser = User::factory()->create([
            'email' => 'admin@temple.local',
            'status' => 'active',
        ]);
        $this->adminUser->assignRole('super_admin');

        $this->devoteePerson = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Amitabh Sen',
            'phone' => '01711334455',
            'email' => 'amitabh@temple.local',
            'occupation' => 'Software Engineer',
            'present_address' => 'Mirpur, Dhaka',
            'status' => 'active',
        ]);

        $service = app(DevoteeCredentialService::class);
        $this->devoteeUser = $service->provisionDevoteeAccount($this->devoteePerson, $this->initialPassword, $this->adminUser);
    }

    public function test_otp_is_generated_and_sent_to_registered_email(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->devoteeUser)->postJson(route('devotee.profile.otp'), [
            'purpose' => 'profile_update',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        Mail::assertSent(DevoteeOtpMail::class, function ($mail) {
            return $mail->hasTo('amitabh@temple.local')
                && $mail->purpose === 'profile_update'
                && strlen($mail->otpCode) === 6;
        });

        // OTP stored hashed, not plaintext
        $otp = EmailOtp::where('user_id', $this->devoteeUser->id)->latest()->first();
        $this->assertNotNull($otp);
        $this->assertNotEquals(6, strlen($otp->otp_hash));
    }

    public function test_valid_otp_permits_authorized_profile_update(): void
    {
        $otpService = app(EmailOtpService::class);
        $otp = $otpService->generateAndSendOtp($this->devoteeUser, EmailOtp::PURPOSE_PROFILE_UPDATE);

        // Manually override hash with a known 6-digit code for testing
        $code = '123456';
        $otp->update(['otp_hash' => Hash::make($code)]);

        $response = $this->actingAs($this->devoteeUser)->put(route('devotee.profile.update'), [
            'otp_code' => $code,
            'occupation' => 'Senior Architect',
            'present_address' => 'Gulshan 2, Dhaka',
            'permanent_address' => 'Sylhet Sadar',
            'email' => 'amitabh.updated@temple.local',
        ]);

        $response->assertRedirect(route('devotee.profile'));
        $response->assertSessionHas('success');

        $this->devoteePerson->refresh();
        $this->devoteeUser->refresh();

        $this->assertEquals('Senior Architect', $this->devoteePerson->occupation);
        $this->assertEquals('Gulshan 2, Dhaka', $this->devoteePerson->present_address);
        $this->assertEquals('amitabh.updated@temple.local', $this->devoteePerson->email);
        $this->assertEquals('amitabh.updated@temple.local', $this->devoteeUser->email);

        // OTP is marked used
        $otp->refresh();
        $this->assertTrue($otp->isUsed());
    }

    public function test_invalid_otp_is_rejected(): void
    {
        $otpService = app(EmailOtpService::class);
        $otp = $otpService->generateAndSendOtp($this->devoteeUser, EmailOtp::PURPOSE_PROFILE_UPDATE);
        $otp->update(['otp_hash' => Hash::make('654321')]);

        $response = $this->actingAs($this->devoteeUser)->put(route('devotee.profile.update'), [
            'otp_code' => '999999', // Incorrect code
            'occupation' => 'Hacker',
        ]);

        $response->assertSessionHas('error');
        $this->devoteePerson->refresh();
        $this->assertNotEquals('Hacker', $this->devoteePerson->occupation);
    }

    public function test_expired_otp_is_rejected(): void
    {
        $otpService = app(EmailOtpService::class);
        $otp = $otpService->generateAndSendOtp($this->devoteeUser, EmailOtp::PURPOSE_PROFILE_UPDATE);
        $otp->update([
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->subMinutes(1), // Expired
        ]);

        $response = $this->actingAs($this->devoteeUser)->put(route('devotee.profile.update'), [
            'otp_code' => '123456',
            'occupation' => 'New Occupation',
        ]);

        $response->assertSessionHas('error');
    }

    public function test_reused_otp_is_rejected(): void
    {
        $otpService = app(EmailOtpService::class);
        $otp = $otpService->generateAndSendOtp($this->devoteeUser, EmailOtp::PURPOSE_PROFILE_UPDATE);
        $otp->update([
            'otp_hash' => Hash::make('123456'),
            'used_at' => now()->subMinutes(2), // Already used
        ]);

        $response = $this->actingAs($this->devoteeUser)->put(route('devotee.profile.update'), [
            'otp_code' => '123456',
            'occupation' => 'Reused Code Attempt',
        ]);

        $response->assertSessionHas('error');
    }

    public function test_otp_for_profile_cannot_authorize_password_change(): void
    {
        $otpService = app(EmailOtpService::class);
        // OTP created for profile_update, NOT password_change
        $otp = $otpService->generateAndSendOtp($this->devoteeUser, EmailOtp::PURPOSE_PROFILE_UPDATE);
        $otp->update(['otp_hash' => Hash::make('123456')]);

        $response = $this->actingAs($this->devoteeUser)->put(route('devotee.password.update'), [
            'current_password' => $this->initialPassword,
            'otp_code' => '123456',
            'password' => 'NewPassword@999',
            'password_confirmation' => 'NewPassword@999',
        ]);

        $response->assertSessionHasErrors(['otp_code']);
        $this->devoteeUser->refresh();
        $this->assertTrue(Hash::check($this->initialPassword, $this->devoteeUser->password));
    }

    public function test_password_change_requires_current_password_and_valid_otp_and_synchronizes_credentials(): void
    {
        $otpService = app(EmailOtpService::class);
        $otp = $otpService->generateAndSendOtp($this->devoteeUser, EmailOtp::PURPOSE_PASSWORD_CHANGE);
        $code = '789123';
        $otp->update(['otp_hash' => Hash::make($code)]);

        $newPassword = 'BrandNewDevoteePass@2027';

        $response = $this->actingAs($this->devoteeUser)->put(route('devotee.password.update'), [
            'current_password' => $this->initialPassword,
            'otp_code' => $code,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertRedirect(route('devotee.profile'));
        $response->assertSessionHas('success');

        $this->devoteeUser->refresh();

        // Hash updated
        $this->assertTrue(Hash::check($newPassword, $this->devoteeUser->password));

        // Encrypted copy updated in sync
        $credential = DevoteeCredential::where('user_id', $this->devoteeUser->id)->first();
        $this->assertEquals($newPassword, Crypt::decryptString($credential->encrypted_password));
    }

    public function test_devotee_without_registered_email_cannot_self_update(): void
    {
        $noEmailPerson = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Devotee Without Email',
            'phone' => '01799887766',
            'email' => null,
            'status' => 'active',
        ]);

        $service = app(DevoteeCredentialService::class);
        $noEmailUser = $service->provisionDevoteeAccount($noEmailPerson, 'Pass@123456', $this->adminUser);

        // Attempt OTP request
        $response = $this->actingAs($noEmailUser)->postJson(route('devotee.profile.otp'), [
            'purpose' => 'profile_update',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }
}
