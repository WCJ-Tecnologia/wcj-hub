<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Enums\RoleEnum;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** B1 T3: toda negação por Policy num controller vira linha denied (Auditoria AC 2, G3). */
class NegacaoAuditadaTest extends TestCase
{
    use RefreshDatabase;

    public function test_dev_na_lista_de_usuarios_recebe_403_e_a_tentativa_fica_na_auditoria(): void
    {
        $dev = User::factory()->developer()->create();

        $this->actingAs($dev)->get('/admin/users')->assertForbidden();

        $log = AuditLog::query()->sole();
        $this->assertSame($dev->id, $log->user_id);
        $this->assertSame('viewed', $log->action);
        $this->assertSame(AuditLog::OUTCOME_DENIED, $log->outcome);
        $this->assertSame('user', $log->auditable_type);
        $this->assertNull($log->auditable_id);
    }

    public function test_negacao_sobre_um_registro_conhecido_grava_o_id_dele_e_nada_muda(): void
    {
        $manager = User::factory()->projectManager()->create();
        $target = User::factory()->developer()->create(['name' => 'Davi Dev']);

        $this->actingAs($manager)
            ->put('/admin/users/'.$target->id, [
                'role_id' => RoleEnum::ADMIN->value,
                'name' => 'Davi Admin',
                'email' => $target->email,
            ])
            ->assertForbidden();

        $log = AuditLog::query()->sole();
        $this->assertSame($manager->id, $log->user_id);
        $this->assertSame('updated', $log->action);
        $this->assertSame(AuditLog::OUTCOME_DENIED, $log->outcome);
        $this->assertSame('user', $log->auditable_type);
        $this->assertSame($target->id, $log->auditable_id);
        $target->refresh();
        $this->assertSame('Davi Dev', $target->name);
        $this->assertSame(RoleEnum::DEVELOPER->value, $target->role_id);
    }

    // T20: a AuditLogPolicy substituiu o gate view-audit-logs; a negação passa a ter o tipo do
    // morph map, como as outras.
    public function test_negacao_da_auditoria_grava_o_tipo_audit_log_sem_id(): void
    {
        $dev = User::factory()->developer()->create();

        $this->actingAs($dev)->get('/admin/audit-logs')->assertForbidden();

        $log = AuditLog::query()->sole();
        $this->assertSame($dev->id, $log->user_id);
        $this->assertSame('viewed', $log->action);
        $this->assertSame(AuditLog::OUTCOME_DENIED, $log->outcome);
        $this->assertSame('audit_log', $log->auditable_type);
        $this->assertNull($log->auditable_id);
    }

    public function test_rota_permitida_nao_grava_negacao(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/audit-logs')->assertOk();

        $this->assertSame(0, AuditLog::query()->where('outcome', AuditLog::OUTCOME_DENIED)->count());
    }
}
