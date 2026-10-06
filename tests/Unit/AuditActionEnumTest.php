<?php

namespace Tests\Unit;

use App\Platform\Enums\AuditActionEnum;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** B1 T1: as ações novas da auditoria (NUC-11) e a ação pretendida de cada ability negada. */
class AuditActionEnumTest extends TestCase
{
    /** @return array<string, array{AuditActionEnum, string, string}> */
    public static function novasAcoes(): array
    {
        return [
            'desativar' => [AuditActionEnum::DEACTIVATED, 'deactivated', 'Desativado'],
            'reativar' => [AuditActionEnum::REACTIVATED, 'reactivated', 'Reativado'],
            'anonimizar' => [AuditActionEnum::ANONYMIZED, 'anonymized', 'Anonimizado'],
            'consultar' => [AuditActionEnum::VIEWED, 'viewed', 'Consultado'],
        ];
    }

    #[DataProvider('novasAcoes')]
    public function test_acao_nova_tem_valor_gravado_e_rotulo_em_portugues(AuditActionEnum $acao, string $valor, string $rotulo): void
    {
        $this->assertSame($valor, $acao->value);
        $this->assertSame($rotulo, $acao->label());
    }

    /** @return array<string, array{string, AuditActionEnum}> */
    public static function abilities(): array
    {
        return [
            'create' => ['create', AuditActionEnum::CREATED],
            'update' => ['update', AuditActionEnum::UPDATED],
            'delete' => ['delete', AuditActionEnum::DELETED],
            'deactivate' => ['deactivate', AuditActionEnum::DEACTIVATED],
            'reactivate' => ['reactivate', AuditActionEnum::REACTIVATED],
            'anonymize' => ['anonymize', AuditActionEnum::ANONYMIZED],
            'resendInvite' => ['resendInvite', AuditActionEnum::PASSWORD_RESET],
            // Nome da mesma ability na UserPolicy do template, ainda ligado à rota reset-password (D4).
            'resetPassword' => ['resetPassword', AuditActionEnum::PASSWORD_RESET],
            'viewAny' => ['viewAny', AuditActionEnum::VIEWED],
            'view' => ['view', AuditActionEnum::VIEWED],
            'viewChoiceList' => ['viewChoiceList', AuditActionEnum::VIEWED],
            'gate de leitura' => ['view-audit-logs', AuditActionEnum::VIEWED],
        ];
    }

    #[DataProvider('abilities')]
    public function test_for_ability_devolve_a_acao_pretendida(string $ability, AuditActionEnum $esperada): void
    {
        $this->assertSame($esperada, AuditActionEnum::forAbility($ability));
    }

    public function test_ability_sem_acao_mapeada_falha_em_vez_de_gravar_acao_errada(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AuditActionEnum::forAbility('archive');
    }
}
