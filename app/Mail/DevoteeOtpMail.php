<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DevoteeOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otpCode,
        public string $purpose,
        public int $expiryMinutes = 10
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->purpose) {
            'password_change' => __('devotee.otp_mail_subject_password'),
            'email_change' => __('devotee.otp_mail_subject_email'),
            default => __('devotee.otp_mail_subject_profile'),
        };

        return new Envelope(
            subject: config('app.name').' — '.$subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.devotee_otp',
        );
    }
}
