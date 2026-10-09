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
    ) {}

    public function envelope(): Envelope
    {
        // Convite e "Reenviar convite" mandam o mesmo e-mail (CR-1-13).
        return new Envelope(subject: 'Seu acesso ao '.config('app.name').', '.$this->user->firstName());
    }

    public function content(): Content
    {
        return new Content(
            html: 'mail.temporary-password',
            text: 'mail.temporary-password-text',
            with: [
                'firstName' => $this->user->firstName(),
                // N1: o convite leva o endereço do Hub, o login e a senha provisória.
                'login' => $this->user->email,
                'temporaryPassword' => $this->temporaryPassword,
                'loginUrl' => route('login'),
                'appName' => config('app.name'),
                'brandName' => config('app.brand.name'),
            ],
        );
    }
}
