<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\AuditLog;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** B1 T2: a negação gravada (Auditoria AC 2) e a limpeza do dado pessoal (AC 9, N12). */
class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuditService $audit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->audit = app(AuditService::class);
    }

    public function test_negacao_sobre_um_registro_grava_quem_tentou_a_acao_pretendida_e_o_registro(): void
    {
        $dev = User::factory()->developer()->create();
        $client = Client::factory()->create();
        $this->actingAs($dev);

        $this->audit->denied('deactivate', $client);

        $log = AuditLog::query()->sole();
        $this->assertSame($dev->id, $log->user_id);
        $this->assertSame('deactivated', $log->action);
        $this->assertSame(AuditLog::OUTCOME_DENIED, $log->outcome);
        $this->assertSame('client', $log->auditable_type);
        $this->assertSame($client->id, $log->auditable_id);
        $this->assertNull($log->ai_authorization_id);
        $this->assertNull($log->changes);
        $this->assertSame('127.0.0.1', $log->ip_address);
    }

    public function test_negacao_sobre_a_classe_grava_o_tipo_pelo_morph_map_e_id_nulo(): void
    {
        $manager = User::factory()->projectManager()->create();
        $this->actingAs($manager);

        $this->audit->denied('create', User::class);

        $log = AuditLog::query()->sole();
        $this->assertSame($manager->id, $log->user_id);
        $this->assertSame('created', $log->action);
        $this->assertSame(AuditLog::OUTCOME_DENIED, $log->outcome);
        $this->assertSame('user', $log->auditable_type);
        $this->assertNull($log->auditable_id);
    }

    public function test_limpeza_tira_so_as_chaves_pedidas_e_so_das_linhas_do_registro(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $client = Client::factory()->create();
        $ana = Contact::query()->create(['client_id' => $client->id, 'name' => 'Ana Lima', 'email' => 'ana@cliente.test', 'phone' => '11999990000']);
        $bia = Contact::query()->create(['client_id' => $client->id, 'name' => 'Bia Reis', 'email' => 'bia@cliente.test', 'phone' => '11988880000']);

        $created = $this->audit->log(AuditActionEnum::CREATED, $ana, null, ['client_id' => $client->id, 'name' => 'Ana Lima', 'email' => 'ana@cliente.test', 'phone' => '11999990000']);
        $renamed = $this->audit->log(AuditActionEnum::UPDATED, $ana, ['name' => 'Ana Lima'], ['name' => 'Ana L. Souza']);
        $denied = $this->audit->denied('update', $ana);
        $other = $this->audit->log(AuditActionEnum::CREATED, $bia, null, ['name' => 'Bia Reis', 'email' => 'bia@cliente.test', 'phone' => '11988880000']);

        $changed = $this->audit->scrubPersonalData($ana, ['name', 'email', 'phone']);

        $this->assertSame(2, $changed);
        $this->assertSame(['client_id' => [null, $client->id]], $created->refresh()->changes);
        $this->assertNull($renamed->refresh()->changes);
        $this->assertNull($denied->refresh()->changes);
        $this->assertSame(
            ['name' => [null, 'Bia Reis'], 'email' => [null, 'bia@cliente.test'], 'phone' => [null, '11988880000']],
            $other->refresh()->changes,
        );
        $this->assertSame(4, AuditLog::query()->count());
    }

    public function test_senha_e_token_nunca_entram_em_changes(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $log = $this->audit->log(
            AuditActionEnum::UPDATED,
            $admin,
            ['name' => 'Ana', 'password' => 'hash-antigo', 'token' => 'abc'],
            ['name' => 'Ana Admin', 'password' => 'hash-novo', 'token' => 'def'],
        );

        $this->assertSame(['name' => ['Ana', 'Ana Admin']], $log->refresh()->changes);
    }
}
