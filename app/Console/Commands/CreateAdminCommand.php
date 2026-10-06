<?php

namespace App\Console\Commands;

use App\Platform\Enums\UserTypeEnum;
use App\Platform\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Replaces the seeder-with-a-fixed-password anti-pattern. The password is
 * random, printed exactly once and must be changed on first login.
 */
class CreateAdminCommand extends Command
{
    protected $signature = 'app:create-admin
        {email : E-mail the administrator will sign in with}
        {--name= : Display name (defaults to the e-mail local part)}
        {--password= : Fixed password. ONLY for automated test environments; refused in production and skips the forced change}';

    protected $description = 'Create an administrator with a one-time random password';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Informe um e-mail válido.');

            return self::INVALID;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error("Já existe um usuário com o e-mail {$email}.");

            return self::FAILURE;
        }

        $fixed = $this->option('password');

        if ($fixed !== null && app()->isProduction()) {
            $this->error('--password não é aceito em produção. Gere a senha aleatória e troque no primeiro acesso.');

            return self::INVALID;
        }

        $password = $fixed ?? Str::password(16);

        $user = User::query()->create([
            'user_type_id' => UserTypeEnum::ADMIN->value,
            'name' => $this->option('name') ?: Str::headline(Str::before($email, '@')),
            'email' => $email,
            'password' => $password,
            'must_change_password' => $fixed === null,
        ]);

        $this->info("Administrador criado: {$user->name} <{$user->email}>");

        if ($fixed === null) {
            $this->newLine();
            $this->line('Senha temporária (mostrada uma única vez, não fica em log):');
            $this->line("  {$password}");
            $this->newLine();
            $this->comment('No primeiro acesso a pessoa é obrigada a escolher uma senha própria.');
        } else {
            $this->warn('Senha fixa informada por --password: uso restrito a ambiente de teste automatizado.');
        }

        return self::SUCCESS;
    }
}
