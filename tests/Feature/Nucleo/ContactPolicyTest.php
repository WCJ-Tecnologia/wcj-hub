<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Enums\AuditActionEnum;
use App\Platform\Enums\RoleEnum;
use App\Platform\Models\AuditLog;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use App\Platform\Policies\ContactPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * B1 T15: matriz ability × papel × situação da ContactPolicy (F1 §09; Contatos AC 4–6, 12;
 * NUC-08, NUC-09, NUC-10). O gestor vê, cria e edita só nos clientes de projeto que gere;
 * desativar, reativar, excluir e anonimizar são do Administrador; anonimizado é estado final.
 */
class ContactPolicyTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Client::factory()->create();
    }

    private function anonymized(): Contact
    {
        $contact = Contact::factory()->inactive()->create(['client_id' => $this->client->id, 'name' => Contact::ANONYMIZED_NAME, 'email' => null, 'phone' => null]);
        AuditLog::query()->create([
            'user_id' => User::factory()->admin()->create()->id,
            'action' => AuditActionEnum::ANONYMIZED->value,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'auditable_type' => 'contact',
            'auditable_id' => $contact->id,
        ]);

        return $contact;
    }

    public function test_registrada_no_gate(): void
    {
        $this->assertInstanceOf(ContactPolicy::class, Gate::getPolicyFor(Contact::class));
    }

    /**
     * @return array<string, array{RoleEnum, bool, bool, bool, bool}>
     *                                                                papel, gere um projeto do cliente, lista geral, vê/cria/edita no cliente, desativa/reativa/exclui/anonimiza
     */
    public static function papeis(): array
    {
        return [
            'administrador' => [RoleEnum::ADMIN, false, true, true, true],
            'gestor que gere o cliente' => [RoleEnum::PROJECT_MANAGER, true, true, true, false],
            'gestor que não gere o cliente' => [RoleEnum::PROJECT_MANAGER, false, true, false, false],
            // Alocado no projeto do cliente: lê "quem pediu" pelo card (B3), não pela tela de contatos.
            'desenvolvedor alocado' => [RoleEnum::DEVELOPER, true, false, false, false],
            'comercial' => [RoleEnum::SALES, false, false, false, false],
        ];
    }

    #[DataProvider('papeis')]
    public function test_matriz_de_abilities_por_papel_em_contato_ativo_e_desativado(RoleEnum $papel, bool $noProjeto, bool $lista, bool $noCliente, bool $admin): void
    {
        $actor = User::factory()->create(['role_id' => $papel->value, 'daily_hours' => 8]);
        if ($noProjeto) {
            $manager = $papel === RoleEnum::PROJECT_MANAGER ? $actor : User::factory()->projectManager()->create();
            $this->project($this->client, $manager, members: $papel === RoleEnum::PROJECT_MANAGER ? [] : [$actor]);
        }
        $active = Contact::factory()->create(['client_id' => $this->client->id]);
        $inactive = Contact::factory()->inactive()->create(['client_id' => $this->client->id]);

        $this->assertSame($lista, $actor->can('viewAny', Contact::class), "{$papel->slug()} viewAny");
        $this->assertSame($noCliente, $actor->can('viewAny', [Contact::class, $this->client]), "{$papel->slug()} viewAny do cliente");
        $this->assertSame($noCliente, $actor->can('create', [Contact::class, $this->client]), "{$papel->slug()} create");

        foreach ([$active, $inactive] as $contact) {
            $this->assertSame($noCliente, $actor->can('update', $contact), "{$papel->slug()} update");
            foreach (['deactivate', 'reactivate', 'delete', 'anonymize'] as $ability) {
                $this->assertSame($admin, $actor->can($ability, $contact), "{$papel->slug()} {$ability}");
            }
        }
    }

    // Contatos AC 4: gestor alocado num projeto do cliente com o papel de gestor também gere.
    public function test_gestor_alocado_como_gestor_cria_e_edita_no_cliente(): void
    {
        $allocated = User::factory()->projectManager()->create();
        $this->project($this->client, User::factory()->projectManager()->create(), members: [$allocated]);
        $other = Client::factory()->create();

        $this->assertTrue($allocated->can('create', [Contact::class, $this->client]));
        $this->assertTrue($allocated->can('update', Contact::factory()->create(['client_id' => $this->client->id])));
        $this->assertFalse($allocated->can('create', [Contact::class, $other]));
        $this->assertFalse($allocated->can('update', Contact::factory()->create(['client_id' => $other->id])));
    }

    // Contatos AC 12 (D5): anonimizado não se edita, não se reativa, não se anonimiza de novo.
    public function test_anonimizado_nega_editar_reativar_e_anonimizar_ate_ao_administrador(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->projectManager()->create();
        $this->project($this->client, $manager);
        $contact = $this->anonymized();

        foreach (['update', 'reactivate', 'anonymize'] as $ability) {
            $response = Gate::forUser($admin)->inspect($ability, $contact);
            $this->assertTrue($response->denied(), $ability);
            $this->assertSame('Contato anonimizado: não pode ser alterado', $response->message(), $ability);
        }
        $this->assertFalse($manager->can('update', $contact));
        // Continua na lista e pode sair de vez se nunca foi citado (G2).
        $this->assertTrue($admin->can('delete', $contact));
        $this->assertTrue($contact->isAnonymized());
        $this->assertFalse(Contact::factory()->create()->isAnonymized());
    }

    // D5: a lista carrega a situação numa consulta só e chega à mesma resposta.
    public function test_with_anonymized_carrega_a_situacao_da_auditoria(): void
    {
        $anonymized = $this->anonymized();
        $plain = Contact::factory()->create(['client_id' => $this->client->id]);
        // A negação de anonimizar não anonimiza.
        AuditLog::query()->create(['action' => 'anonymized', 'outcome' => AuditLog::OUTCOME_DENIED, 'auditable_type' => 'contact', 'auditable_id' => $plain->id]);

        $loaded = Contact::query()->withAnonymized()->get()->keyBy('id');

        $this->assertTrue($loaded[$anonymized->id]->isAnonymized());
        $this->assertFalse($loaded[$plain->id]->isAnonymized());
        $this->assertFalse($plain->isAnonymized());
    }

    // A negação grava a ação pretendida (G3): ability sem ação de auditoria viraria 500.
    public function test_toda_ability_da_policy_tem_acao_de_auditoria(): void
    {
        $abilities = array_map(
            fn (ReflectionMethod $method) => $method->getName(),
            array_filter(
                (new ReflectionClass(ContactPolicy::class))->getMethods(ReflectionMethod::IS_PUBLIC),
                fn (ReflectionMethod $method) => ! $method->isConstructor(),
            ),
        );

        $this->assertEqualsCanonicalizing(['viewAny', 'create', 'update', 'deactivate', 'reactivate', 'delete', 'anonymize'], $abilities);
        foreach ($abilities as $ability) {
            $this->assertInstanceOf(AuditActionEnum::class, AuditActionEnum::forAbility($ability));
        }
    }
}
