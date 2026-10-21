<?php

namespace App\Mail;

use App\Platform\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Na fila: o "esqueci a senha" responde sem esperar o SMTP. Quem iguala o tempo de resposta
 * entre conta que existe e que não existe é o Timebox do controller (seguranca.md §3; B9,
 * SEC-07); a fila tira do caminho o SMTP, que passaria do Timebox. Sem o worker rodando, o
 * e-mail atrasa (docs/DEPLOY.md).
 */
class ResetPasswordMail extends Mailable implements ShouldQueue
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
