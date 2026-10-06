<?php

namespace App\Mail;

use App\Platform\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemporaryPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $temporaryPassword,
        public readonly bool $isReset,
    ) {}

    public function envelope(): Envelope
    {
        $first = $this->user->firstName();

        return new Envelope(
            subject: $this->isReset
                ? 'Nova senha temporária, '.$first
                : 'Seu acesso ao '.config('app.name').', '.$first,
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'mail.temporary-password',
            text: 'mail.temporary-password-text',
            with: [
                'firstName' => $this->user->firstName(),
                'temporaryPassword' => $this->temporaryPassword,
                'loginUrl' => route('login'),
                'isReset' => $this->isReset,
                'appName' => config('app.name'),
                'brandName' => config('app.brand.name'),
            ],
        );
    }
}
