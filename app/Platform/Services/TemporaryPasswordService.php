<?php

namespace App\Platform\Services;

use App\Mail\TemporaryPasswordMail;
use App\Platform\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Senha provisória do convite (N1). Gravar e enviar são passos separados: quem grava está na
 * transação do usuário, e o e-mail só sai depois do commit, para uma falha de SMTP não
 * desfazer o usuário nem o e-mail sair com uma senha que não chegou ao banco.
 */
class TemporaryPasswordService
{
    public function generate(): string
    {
        $letters = strtoupper(Str::random(4));
        $digits = (string) random_int(10, 99);
        $tail = Str::lower(Str::random(3));

        return $letters.$digits.$tail;
    }

    /** Grava a senha nova (a anterior deixa de valer) e devolve o texto puro para o envio. */
    public function issue(User $user): string
    {
        $plain = $this->generate();

        $user->forceFill([
            'password' => $plain,
            'must_change_password' => true,
        ])->save();

        return $plain;
    }

    /** Devolve false se o e-mail não saiu; a falha fica no log, sem a senha. */
    public function send(User $user, #[\SensitiveParameter] string $plain): bool
    {
        try {
            Mail::to($user->email)->send(new TemporaryPasswordMail($user, $plain));

            return true;
        } catch (Throwable $e) {
            Log::error('Convite com senha provisória não enviado.', [
                'user_id' => $user->getKey(),
                'erro' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
