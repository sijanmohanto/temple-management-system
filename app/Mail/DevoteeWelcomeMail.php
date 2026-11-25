<?php

namespace App\Mail;

use App\Models\Person;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DevoteeWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Person $person,
        public string $loginIdentifier,
        public string $plainPassword,
        public string $loginUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('app.name').' — '.__('people.welcome_mail_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.devotee_welcome',
        );
    }
}
