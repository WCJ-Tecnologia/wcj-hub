<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Enums\RoleEnum;
use App\Platform\Models\AuditLog;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use App\Platform\Requests\Admin\AnonymizeContactRequest;
use App\Platform\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * B1 T37 (A1 da review de 07/10): quem não pode recebe 403 antes da validação do formulário. Sem
 * isso, o 422 de "e-mail já em uso" enumerava os logins para o gestor e o dev (F1 §09), e a
 * tentativa com dado inválido não virava denied (Auditoria AC 2, G3). Payload sempre inválido;
 * nos usuários, só o e-mail (o de outra pessoa).
 */
class AutorizaAntesDeValidarTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    /** @return array<string, array{RoleEnum}> */
    public static function semPermissao(): array
    {
        return ['gestor de projetos' => [RoleEnum::PROJECT_MANAGER], 'desenvolvedor' => [RoleEnum::DEVELOPER]];
    }

    private function as(RoleEnum $papel): void
    {
        $this->actor = User::factory()->create(['role_id' => $papel->value, 'daily_hours' => 8]);
        $this->actingAs($this->actor);
    }

    /** 403, nenhum erro de validação devolvido e exatamente uma linha denied, com o alvo certo. */
    private function assertNegadoUmaVez(TestResponse $response, string $action, string $type, ?int $id): void
    {
        $response->assertForbidden();
        $response->assertSessionHasNoErrors();

        $log = AuditLog::query()->sole();
        $this->assertSame($this->actor->id, $log->user_id);
        $this->assertSame($action, $log->action);
        $this->assertSame(AuditLog::OUTCOME_DENIED, $log->outcome);
        $this->assertSame($type, $log->auditable_type);
        $this->assertSame($id, $log->auditable_id);
    }

    #[DataProvider('semPermissao')]
    public function test_convidar_com_email_existente_e_payload_invalido_responde_403_sem_revelar_o_email(RoleEnum $papel): void
    {
        $existente = User::factory()->admin()->create(['email' => 'ana@wcj.test']);
        $this->as($papel);
        $antes = User::query()->count();

        $response = $this->post('/admin/users', ['email' => $existente->email, 'name' => 'Outra Pessoa', 'role_id' => RoleEnum::ADMIN->value]);

        $this->assertNegadoUmaVez($response, 'created', 'user', null);
        $response->assertDontSee(UserService::EMAIL_TAKEN);
        $this->assertSame($antes, User::query()->count());
    }

    #[DataProvider('semPermissao')]
    public function test_editar_usuario_com_email_existente_e_payload_invalido_responde_403_sem_revelar_o_email(RoleEnum $papel): void
    {
        $existente = User::factory()->admin()->create(['email' => 'ana@wcj.test']);
        $alvo = User::factory()->developer()->create(['name' => 'Davi Dev']);
        $this->as($papel);

        $response = $this->put("/admin/users/{$alvo->id}", ['email' => $existente->email, 'name' => 'Davi Admin', 'role_id' => RoleEnum::ADMIN->value]);

        $this->assertNegadoUmaVez($response, 'updated', 'user', $alvo->id);
        $response->assertDontSee(UserService::EMAIL_TAKEN);
        $this->assertSame('Davi Dev', $alvo->fresh()->name);
        $this->assertSame(RoleEnum::DEVELOPER->value, $alvo->fresh()->role_id);
    }

    // O gestor pode criar cliente (D1); quem nega aqui é o dev. A edição nega aos dois (o gestor
    // não gere o cliente: implementação vazia do B1).
    public function test_dev_criando_cliente_com_cnpj_existente_e_payload_invalido_responde_403(): void
    {
        $this->as(RoleEnum::DEVELOPER);
        $antes = Client::query()->count();

        $response = $this->post('/admin/clients', ['name' => '', 'country' => 'BR', 'document' => Client::WCJ_CNPJ]);

        $this->assertNegadoUmaVez($response, 'created', 'client', null);
        $response->assertDontSee('Já existe o cliente');
        $this->assertSame($antes, Client::query()->count());
    }

    #[DataProvider('semPermissao')]
    public function test_editar_cliente_com_payload_invalido_responde_403(RoleEnum $papel): void
    {
        $cliente = Client::factory()->create(['name' => 'Padaria Pão Quente']);
        $this->as($papel);

        $response = $this->put("/admin/clients/{$cliente->id}", ['name' => '', 'country' => 'BR', 'document' => Client::WCJ_CNPJ]);

        $this->assertNegadoUmaVez($response, 'updated', 'client', $cliente->id);
        $this->assertSame('Padaria Pão Quente', $cliente->fresh()->name);
    }

    #[DataProvider('semPermissao')]
    public function test_criar_contato_com_payload_invalido_responde_403(RoleEnum $papel): void
    {
        $cliente = Client::factory()->create();
        $this->as($papel);

        $response = $this->post("/admin/clients/{$cliente->id}/contacts", ['name' => '', 'email' => 'nao-e-email']);

        $this->assertNegadoUmaVez($response, 'created', 'contact', null);
        $this->assertSame(0, Contact::query()->count());
    }

    #[DataProvider('semPermissao')]
    public function test_editar_contato_com_payload_invalido_responde_403(RoleEnum $papel): void
    {
        $contato = Contact::factory()->create(['name' => 'Rita Recepção']);
        $this->as($papel);

        $response = $this->put("/admin/contacts/{$contato->id}", ['name' => '', 'email' => 'nao-e-email']);

        $this->assertNegadoUmaVez($response, 'updated', 'contact', $contato->id);
        $this->assertSame('Rita Recepção', $contato->fresh()->name);
    }

    #[DataProvider('semPermissao')]
    public function test_anonimizar_com_a_palavra_errada_responde_403(RoleEnum $papel): void
    {
        $contato = Contact::factory()->create(['name' => 'Rita Recepção']);
        $this->as($papel);

        $response = $this->post("/admin/contacts/{$contato->id}/anonymize", ['confirmation' => 'errado']);

        $this->assertNegadoUmaVez($response, 'anonymized', 'contact', $contato->id);
        $response->assertDontSee(AnonymizeContactRequest::MISMATCH);
        $this->assertSame('Rita Recepção', $contato->fresh()->name);
    }

    // O Administrador continua recebendo a validação de sempre, sem linha denied.
    public function test_admin_com_payload_invalido_recebe_o_erro_de_validacao(): void
    {
        $existente = User::factory()->admin()->create(['email' => 'ana@wcj.test']);
        $this->actingAs($existente);

        $this->post('/admin/users', ['email' => $existente->email, 'name' => 'Outra', 'role_id' => RoleEnum::ADMIN->value])
            ->assertSessionHasErrors(['email' => UserService::EMAIL_TAKEN]);

        $this->assertSame(0, AuditLog::query()->where('outcome', AuditLog::OUTCOME_DENIED)->count());
    }
}
