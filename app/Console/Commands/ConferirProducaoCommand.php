<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Passo zero do deploy (docs/DEPLOY.md): recusa a configuração carregada que não pode ir para
 * produção. Lê só a configuração, nunca o .env, e nunca imprime valor: só o nome da variável e
 * o que está errado (B9, SEC-12; seguranca.md §5 e §9).
 */
class ConferirProducaoCommand extends Command
{
    protected $signature = 'app:conferir-producao';

    protected $description = 'Confere se a configuração carregada pode ir para produção (sai com 1 e lista o que falta)';

    public function handle(): int
    {
        $errado = array_keys(array_filter([
            'APP_ENV não é production' => config('app.env') !== 'production',
            'APP_DEBUG ligado' => (bool) config('app.debug'),
            'APP_URL sem https://' => ! str_starts_with((string) config('app.url'), 'https://'),
            'APP_KEY vazio' => blank(config('app.key')),
            'SESSION_SECURE_COOKIE desligado (cookie de sessão sem secure)' => ! config('session.secure'),
            'MAIL_MAILER não envia e-mail de verdade (log ou array)' => in_array(config('mail.default'), ['log', 'array'], true),
            'QUEUE_CONNECTION síncrona (sync): os e-mails sairiam dentro da requisição' => config('queue.default') === 'sync',
        ]));

        if ($errado === []) {
            $this->info('Configuração de produção conferida.');

            return self::SUCCESS;
        }

        $this->error('A configuração não pode ir para produção:');
        foreach ($errado as $item) {
            $this->line('- '.$item);
        }

        return self::FAILURE;
    }
}
