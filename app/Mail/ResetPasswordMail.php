<?php

namespace App\Mail;

use App\Platform\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $resetUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vamos te colocar de volta, '.$this->user->firstName(),
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'mail.reset-password',
            text: 'mail.reset-password-text',
            with: [
                'firstName' => $this->user->firstName(),
                'resetUrl' => $this->resetUrl,
                'appName' => config('app.name'),
                'brandName' => config('app.brand.name'),
            ],
        );
    }
}
