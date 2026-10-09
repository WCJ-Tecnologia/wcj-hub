<?php

namespace App\Platform\Services;

use App\Platform\Enums\AuditActionEnum;
use App\Platform\Enums\RoleEnum;
use App\Platform\Models\AiAuthorization;
use App\Platform\Models\User;
use App\Platform\Services\Concerns\RefusesDuplicates;
use App\Support\Like;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * Usuários do Hub (N1 a N4, N11, G2, IA4, IA6). Toda escrita grava a auditoria na mesma
 * transação. Recusa de regra sai como DomainException, com a mensagem para a tela.
 */
class UserService
{
    use RefusesDuplicates;

    public const DAILY_HOURS_REQUIRED = 'Carga diária é obrigatória para Desenvolvedor';

    public const EMAIL_TAKEN = 'Este e-mail já está em uso.';

    public const ALREADY_USED = 'Usuário já usado: desative em vez de excluir';

    // O nome do índice unique de users.email; a mensagem do MySQL o cita (key 'users.users_email_unique').
    private const EMAIL_UNIQUE = 'users_email_unique';

    public function __construct(
        private readonly AuditService $audit,
        private readonly TemporaryPasswordService $passwords,
        private readonly SessionService $sessions,
    ) {}

    /**
     * Convite (N1). O e-mail sai depois do commit; se falhar, o usuário fica e mailed = false.
     *
     * @param  array{role_id: int|string, name: string, email: string, daily_hours?: mixed, ai_write_allowed?: bool}  $data
     * @return array{user: User, mailed: bool}
     */
    public function invite(array $data, User $actor): array
    {
        $this->ensureDailyHours($data);

        // Dois convites do mesmo e-mail ao mesmo tempo: os dois passaram pela validação.
        [$user, $plain] = $this->writeOrRefuseDuplicate(fn () => DB::transaction(function () use ($data, $actor) {
            $user = new User([
                'role_id' => (int) $data['role_id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'daily_hours' => $data['daily_hours'] ?? null,
                'ai_write_allowed' => (bool) ($data['ai_write_allowed'] ?? true),
            ]);
            // users.password é not null: a senha provisória nasce junto com a linha.
            $plain = $this->passwords->issue($user);
            $this->audit->log(AuditActionEnum::CREATED, $user, null, $this->snapshot($user), $actor->id);

            return [$user, $plain];
        }), $this->emailTaken(...));

        return ['user' => $user, 'mailed' => $this->passwords->send($user, $plain)];
    }

    /**
     * Reenviar convite (F1 §04): senha nova, a anterior deixa de valer, e sai o mesmo e-mail do
     * convite (premissa da spec). Devolve se o e-mail saiu.
     */
    public function resendInvite(User $user, User $actor): bool
    {
        $plain = DB::transaction(function () use ($user, $actor) {
            $before = ['must_change_password' => (bool) $user->must_change_password];
            $plain = $this->passwords->issue($user);
            $this->audit->log(AuditActionEnum::PASSWORD_RESET, $user, $before, ['must_change_password' => true], $actor->id);

            return $plain;
        });

        return $this->passwords->send($user, $plain);
    }

    /**
     * @param  array{role_id: int|string, name: string, email: string, daily_hours?: mixed, ai_write_allowed?: bool}  $data
     */
    public function update(User $user, array $data, User $actor): void
    {
        $this->ensureDailyHours($data);

        // O FormRequest confere o e-mail antes; outra gravação do mesmo e-mail pode entrar no meio.
        $this->writeOrRefuseDuplicate(fn () => DB::transaction(function () use ($user, $data, $actor) {
            if ((int) $data['role_id'] !== RoleEnum::ADMIN->value) {
                $this->ensureNotLastActiveAdmin($user, 'alterar o papel');
            }

            $before = $this->snapshot($user);

            $user->fill([
                'role_id' => (int) $data['role_id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'daily_hours' => $data['daily_hours'] ?? null,
                ...(array_key_exists('ai_write_allowed', $data) ? ['ai_write_allowed' => (bool) $data['ai_write_allowed']] : []),
            ]);
            $revoke = $user->isDirty(['role_id', 'ai_write_allowed']);
            $user->save();

            $this->audit->log(AuditActionEnum::UPDATED, $user, $before, $this->snapshot($user), $actor->id);

            // IA4, IA6: o token emitido valia para o papel e a marca antigos.
            if ($revoke) {
                $this->revokeAiAuthorizations($user, $actor);
            }
        }), $this->emailTaken(...));
    }

    /** N4, N11, IA6: perde o acesso agora, sessões e conexões de IA incluídas. */
    public function deactivate(User $user, User $actor): void
    {
        DB::transaction(function () use ($user, $actor) {
            // Primeiro os administradores ativos, depois o alvo: a mesma ordem de update e delete.
            // Na ordem inversa, dois admins que se desativam um ao outro entram em deadlock (CR-2-01).
            $this->ensureNotLastActiveAdmin($user, 'desativar');

            // Decide, grava e audita pela linha travada, não pela instância em memória: dois
            // cliques ou dois admins não gravam duas desativações (CR-1-15), e a instância velha
            // "desativada" não audita uma desativação que não houve (CR-3-01).
            $locked = User::query()->lockForUpdate()->findOrFail($user->getKey());
            if (! $locked->isActive()) {
                return;
            }

            $locked->setActive(false, $actor)->save();
            $this->audit->log(AuditActionEnum::DEACTIVATED, $locked, ['active' => true], ['active' => false], $actor->id);
            $this->revokeAiAuthorizations($locked, $actor);
            $this->sessions->terminateOthers($locked);
        });
    }

    /** As conexões de IA não voltam: a pessoa reconecta (IA6). */
    public function reactivate(User $user, User $actor): void
    {
        DB::transaction(function () use ($user, $actor) {
            // Pela linha travada, como o deactivate: dois "Reativar" juntos gravam uma linha só (CR-2-06).
            $locked = User::query()->lockForUpdate()->findOrFail($user->getKey());
            if ($locked->isActive()) {
                return;
            }

            $locked->setActive(true, $actor)->save();
            $this->audit->log(AuditActionEnum::REACTIVATED, $locked, ['active' => false], ['active' => true], $actor->id);
        });
    }

    /** G2: só quem nunca entrou e não é referenciado; o resto se desativa. */
    public function delete(User $user, User $actor): void
    {
        $before = $this->snapshot($user);

        $deleted = DB::transaction(function () use ($user, $before, $actor) {
            $this->ensureNotLastActiveAdmin($user, 'excluir');

            return $user->deleteIfUnused(function (User $deleted) use ($before, $actor) {
                Password::broker()->deleteToken($deleted);
                $this->audit->log(AuditActionEnum::DELETED, $deleted, $before, null, $actor->id);
            });
        });

        if (! $deleted) {
            throw new DomainException(self::ALREADY_USED);
        }
    }

    /**
     * Lista da tela (F1 §03, §05): desativados só com o filtro "Mostrar desativados".
     *
     * @param  array{search: string, role_id: int|null, show_inactive: bool}  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $search = $filters['search'];

        return User::query()
            ->withLastLogin()
            ->with('deactivatedBy:id,name')
            ->when(! $filters['show_inactive'], fn ($query) => $query->active())
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', Like::contains($search))
                ->orWhere('email', 'like', Like::contains($search))))
            ->when($filters['role_id'] !== null, fn ($query) => $query->where('role_id', $filters['role_id']))
            ->orderBy('name')
            ->paginate(20);
    }

    /**
     * Lista de escolha (NUC-13): só ativos, sem e-mail, carga nem marca da IA. A Policy é
     * conferida aqui (decisão do Lucas Scholz, 09/10): o B2 entrega a lista como prop, sem rota
     * própria, e quem não pode recebe AuthorizationException, com a negação auditada.
     *
     * @return list<array{id: int, name: string, role: string}>
     */
    public function choiceList(User $viewer): array
    {
        $this->audit->inspectFor($viewer, 'viewChoiceList', User::class)->authorize();

        return User::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'role_id', 'deactivated_at'])
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name, 'role' => $user->type()->slug()])
            ->all();
    }

    /** Só o unique do e-mail vira "já em uso"; outro unique relança a exceção original (CR-2-11). */
    private function emailTaken(UniqueConstraintViolationException $e): ?ValidationException
    {
        return str_contains($e->getMessage(), self::EMAIL_UNIQUE)
            ? ValidationException::withMessages(['email' => self::EMAIL_TAKEN])
            : null;
    }

    /** @param  array<string, mixed>  $data */
    private function ensureDailyHours(array $data): void
    {
        $hours = $data['daily_hours'] ?? null;

        if ((int) $data['role_id'] === RoleEnum::DEVELOPER->value && ($hours === null || $hours === '')) {
            throw ValidationException::withMessages(['daily_hours' => self::DAILY_HOURS_REQUIRED]);
        }
    }

    /**
     * Chamar dentro da transação da escrita. A trava nas linhas dos administradores ativos dura
     * até o commit: dois admins que se desativam, rebaixam ou excluem ao mesmo tempo esperam um
     * pelo outro, e o segundo já vê que sobrou só ele (A3 da review de 07/10).
     */
    private function ensureNotLastActiveAdmin(User $user, string $action): void
    {
        // Pelo banco, não só pela instância (CR-3-01). A releitura vai sem trava: a do alvo vem
        // depois da dos administradores (CR-2-01). Quem decide é o conjunto travado abaixo.
        if (! $user->isAdmin() && ! User::query()->findOrFail($user->getKey())->isAdmin()) {
            return;
        }

        $activeAdmins = User::query()->active()->where('role_id', RoleEnum::ADMIN->value)->lockForUpdate()->pluck('id');

        if ($activeAdmins->contains($user->getKey()) && $activeAdmins->doesntContain(fn (int $id) => $id !== $user->getKey())) {
            throw new DomainException("Este é o último administrador ativo. Promova outra pessoa antes de {$action}.");
        }
    }

    // A coluna gerada active_assistant_id nunca entra no update: o MySQL recusa.
    private function revokeAiAuthorizations(User $user, User $actor): void
    {
        $active = AiAuthorization::query()->where('user_id', $user->id)->whereNull('revoked_at')->lockForUpdate()->get();

        foreach ($active as $authorization) {
            $authorization->forceFill(['revoked_at' => now()])->save();
            // banco.txt (ai_authorizations): quem revogou fica na auditoria.
            $this->audit->log(AuditActionEnum::UPDATED, $authorization, ['revoked_at' => null], [
                'revoked_at' => $authorization->revoked_at->toDateTimeString(),
            ], $actor->id);
        }
    }

    /** @return array<string, mixed> */
    private function snapshot(User $user): array
    {
        return $user->only(['name', 'email', 'role_id', 'daily_hours', 'ai_write_allowed']);
    }
}
