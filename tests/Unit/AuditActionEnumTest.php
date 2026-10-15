<?php

namespace Tests\Unit;

use App\Platform\Enums\AuditActionEnum;
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
            // Sprints (B5).
            'close' => ['close', AuditActionEnum::FINISHED],
            'reopen' => ['reopen', AuditActionEnum::REOPENED],
            'manageTasks' => ['manageTasks', AuditActionEnum::UPDATED],
            'viewCapacity' => ['viewCapacity', AuditActionEnum::VIEWED],
            // Quadro e card (B3).
            'createSupport' => ['createSupport', AuditActionEnum::CREATED],
            'move' => ['move', AuditActionEnum::MOVED],
            'manageSprint' => ['manageSprint', AuditActionEnum::UPDATED],
            'comment' => ['comment', AuditActionEnum::UPDATED],
            'attach' => ['attach', AuditActionEnum::UPDATED],
            'archive' => ['archive', AuditActionEnum::ARCHIVED],
        ];
    }

    #[DataProvider('abilities')]
    public function test_for_ability_devolve_a_acao_pretendida(string $ability, AuditActionEnum $esperada): void
    {
        $this->assertSame($esperada, AuditActionEnum::forAbility($ability));
    }

    // T41 (A6 da review de 07/10): sem ação mapeada não há ação a inventar; o AuditService grava
    // o nome da ability, e o rótulo fica genérico.
    public function test_ability_sem_acao_mapeada_nao_vira_acao_do_enum(): void
    {
        $this->assertNull(AuditActionEnum::forAbility('abilitySemMapa'));
        $this->assertSame('Outra ação', AuditActionEnum::labelOf('abilitySemMapa'));
        $this->assertSame('Desativado', AuditActionEnum::labelOf('deactivated'));
    }
}
