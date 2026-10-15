<?php

namespace App\Platform\Enums;

enum AuditActionEnum: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case DELETED = 'deleted';
    case LOGIN = 'login';
    case PASSWORD_RESET = 'password_reset';
    case DEACTIVATED = 'deactivated';
    case REACTIVATED = 'reactivated';
    case ANONYMIZED = 'anonymized';
    // Só aparece em linha negada: a leitura permitida não se audita.
    case VIEWED = 'viewed';
    case FINISHED = 'finished';
    case REOPENED = 'reopened';

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Criado',
            self::UPDATED => 'Atualizado',
            self::DELETED => 'Excluído',
            self::LOGIN => 'Login',
            self::PASSWORD_RESET => 'Senha redefinida',
            self::DEACTIVATED => 'Desativado',
            self::REACTIVATED => 'Reativado',
            self::ANONYMIZED => 'Anonimizado',
            self::VIEWED => 'Consultado',
            self::FINISHED => 'Encerrado',
            self::REOPENED => 'Reaberto',
        };
    }

    /** O rótulo de uma ação gravada; a que não é do enum (ability sem mapeamento) fica genérica. */
    public static function labelOf(string $action): string
    {
        return self::tryFrom($action)?->label() ?? 'Outra ação';
    }

    /**
     * A ação que a pessoa tentou, para a linha negada (G3). Nula para ability sem mapeamento: a
     * linha grava o nome dela em vez de dar 500 e perder a negação (A6 da review de 07/10). Toda
     * ability nova continua entrando aqui, para a linha ter a ação certa.
     */
    public static function forAbility(string $ability): ?self
    {
        return match (true) {
            $ability === 'create' => self::CREATED,
            $ability === 'update' => self::UPDATED,
            // Projetos (B2): editar, encerrar e reabrir passam pela mesma ability.
            $ability === 'manage' => self::UPDATED,
            // Sprints (B5): encerrar e reabrir a sprint; pôr, tirar e estimar card pela tela da sprint.
            $ability === 'close' => self::FINISHED,
            $ability === 'reopen' => self::REOPENED,
            $ability === 'manageTasks' => self::UPDATED,
            $ability === 'delete' => self::DELETED,
            $ability === 'deactivate' => self::DEACTIVATED,
            $ability === 'reactivate' => self::REACTIVATED,
            $ability === 'anonymize' => self::ANONYMIZED,
            in_array($ability, ['resendInvite', 'resetPassword'], true) => self::PASSWORD_RESET,
            str_starts_with($ability, 'view') => self::VIEWED,
            default => null,
        };
    }
}
