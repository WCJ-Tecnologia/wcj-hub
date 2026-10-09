<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B1 T27: o ip_address da linha vem da requisição (Auditoria AC 1 e 2). Um IP que não é o
 * padrão dos testes (127.0.0.1) prova que a linha lê a requisição, e não um valor fixo.
 */
class IpNaAuditoriaTest extends TestCase
{
    use RefreshDatabase;

    private const IP = '203.0.113.7';

    public function test_a_linha_de_sucesso_grava_o_ip_da_requisicao(): void
    {
        $admin = User::factory()->admin()->create();

        $this->withServerVariables(['REMOTE_ADDR' => self::IP])->actingAs($admin)
            ->post('/admin/clients', ['name' => 'Padaria Pão Quente', 'country' => 'BR', 'document' => '12ABC34501DE35'])
            ->assertSessionHasNoErrors();

        $log = AuditLog::query()->where('action', 'created')->where('auditable_type', 'client')->sole();
        $this->assertSame(AuditLog::OUTCOME_SUCCESS, $log->outcome);
        $this->assertSame(self::IP, $log->ip_address);
    }

    public function test_a_linha_de_negacao_grava_o_ip_da_requisicao(): void
    {
        $dev = User::factory()->developer()->create();

        $this->withServerVariables(['REMOTE_ADDR' => self::IP])->actingAs($dev)->get('/admin/clients')->assertForbidden();

        $log = AuditLog::query()->sole();
        $this->assertSame(AuditLog::OUTCOME_DENIED, $log->outcome);
        $this->assertSame(self::IP, $log->ip_address);
    }
}
