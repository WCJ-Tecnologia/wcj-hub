<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Models\AuditLog;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use App\Platform\Services\ContactService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\Support\Concorrencia;
use Tests\Support\UsaBancoDeVerdade;
use Tests\TestCase;

/**
 * B1 T40 (A4 da review de 07/10): anonimizado é estado final (N12, D5). Uma edição ou uma
 * reativação que checou "não anonimizado" antes de a anonimização terminar não pode regravar
 * dado pessoal nem reativar o contato. A anonimização é forçada logo depois da checagem da
 * principal, noutra conexão (tests/Support/Concorrencia.php).
 */
class ContactAnonimizadoConcorrenciaTest extends TestCase
{
    use DatabaseTruncation, UsaBancoDeVerdade;

    private User $admin;

    private Contact $contact;

    protected function setUp(): void
    {
        $this->exigirBancoDeVerdade();
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $client = Client::factory()->create();
        ['contact' => $this->contact] = app(ContactService::class)
            ->create($client, ['name' => 'Ana Lima', 'email' => 'ana@padaria.test', 'phone' => '11988880000'], $this->admin);
    }

    protected function tearDown(): void
    {
        $this->limparOQueFicou();
        parent::tearDown();
    }

    /** A checagem de anonimizado: a linha anonymized na auditoria do contato. */
    private function anonimizarDepoisDaChecagem(): object
    {
        return Concorrencia::intercalarDepoisDe(
            fn (QueryExecuted $consulta) => str_contains($consulta->sql, 'from `audit_logs`') && in_array('anonymized', $consulta->bindings, true),
            fn () => app(ContactService::class)->anonymize(Contact::query()->findOrFail($this->contact->id), $this->admin),
        );
    }

    private function anonimizado(): bool
    {
        return AuditLog::query()->where('auditable_type', 'contact')->where('auditable_id', $this->contact->id)
            ->where('action', 'anonymized')->exists();
    }

    public function test_edicao_que_checou_antes_da_anonimizacao_nao_regrava_dado_pessoal(): void
    {
        $corrida = $this->anonimizarDepoisDaChecagem();

        try {
            app(ContactService::class)->update($this->contact, ['name' => 'Ana Lima Souza', 'email' => 'ana.souza@padaria.test', 'phone' => '11977770000'], $this->admin);
        } catch (\DomainException) {
            // Recusar também serve: o que não pode é gravar depois da anonimização.
        }

        $this->assertTrue($corrida->rodou, 'A checagem de anonimizado não rodou.');
        $this->assertNull($corrida->outroErro);
        $contact = $this->contact->fresh();
        if ($this->anonimizado()) {
            $this->assertSame([Contact::ANONYMIZED_NAME, null, null], [$contact->name, $contact->email, $contact->phone]);
            $changes = DB::table('audit_logs')->where('auditable_type', 'contact')->where('auditable_id', $this->contact->id)->pluck('changes')->implode("\n");
            $this->assertStringNotContainsString('Ana Lima', $changes);
            $this->assertStringNotContainsString('padaria.test', $changes);
        }
        // Com a trava, a edição termina primeiro e a anonimização espera (e cai por tempo).
        $this->assertTrue($corrida->bloqueada, 'A anonimização entrou entre a checagem e a gravação da edição.');
        $this->assertSame('Ana Lima Souza', $contact->name);
    }

    public function test_reativacao_que_checou_antes_da_anonimizacao_nao_reativa_o_contato(): void
    {
        app(ContactService::class)->deactivate($this->contact, $this->admin);
        $this->contact->refresh();
        $corrida = $this->anonimizarDepoisDaChecagem();

        try {
            app(ContactService::class)->reactivate($this->contact, $this->admin);
        } catch (\DomainException) {
            // Recusar também serve: o que não pode é reativar o anonimizado.
        }

        $this->assertTrue($corrida->rodou, 'A checagem de anonimizado não rodou.');
        $this->assertNull($corrida->outroErro);
        if ($this->anonimizado()) {
            $this->assertFalse($this->contact->fresh()->isActive(), 'O contato anonimizado voltou a ficar ativo.');
        }
        $this->assertTrue($corrida->bloqueada, 'A anonimização entrou entre a checagem e a reativação.');
        $this->assertTrue($this->contact->fresh()->isActive());
    }
}
