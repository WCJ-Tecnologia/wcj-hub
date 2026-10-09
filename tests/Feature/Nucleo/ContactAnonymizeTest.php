<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\AuditLog;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use App\Platform\Services\ContactService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

/**
 * B1 T17: anonimizar contato a pedido do titular (Contatos AC 10, 12; edge case da falha no
 * meio; Auditoria AC 9; NUC-10). A confirmação ANONIMIZAR (AC 11) é do FormRequest, nas rotas
 * (ContactControllerTest).
 */
class ContactAnonymizeTest extends TestCase
{
    use RefreshDatabase;

    private const OLD = ['Ana Lima', 'ana.lima@padaria.test', '11988880000'];

    private User $admin;

    private Contact $contact;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $client = Client::factory()->create(['name' => 'Padaria Pão Quente']);

        // Histórico real do contato, pelo Service: criado, editado, desativado e reativado.
        $service = app(ContactService::class);
        ['contact' => $this->contact] = $service->create($client, ['name' => 'Ana', 'email' => 'ana@padaria.test', 'phone' => '1133334444'], $this->admin);
        $service->update($this->contact, ['name' => self::OLD[0], 'email' => self::OLD[1], 'phone' => self::OLD[2]], $this->admin);
        $service->deactivate($this->contact, $this->admin);
        $service->reactivate($this->contact->fresh(), $this->admin);
        $this->contact->refresh();
    }

    /** O JSON cru de changes de toda linha de audit_logs do contato. */
    private function rawChanges(): string
    {
        return DB::table('audit_logs')
            ->where('auditable_type', 'contact')
            ->where('auditable_id', $this->contact->id)
            ->orderBy('id')
            ->pluck('changes')
            ->implode("\n");
    }

    // AC 10
    public function test_troca_o_nome_apaga_email_e_telefone_e_desativa(): void
    {
        app(ContactService::class)->anonymize($this->contact, $this->admin);

        $fresh = $this->contact->fresh();
        $this->assertSame('removido a pedido do titular', $fresh->name);
        $this->assertNull($fresh->email);
        $this->assertNull($fresh->phone);
        $this->assertFalse($fresh->isActive());
        $this->assertSame($this->admin->id, $fresh->deactivated_by);
        $this->assertFalse(Contact::query()->active()->whereKey($this->contact->id)->exists());
        $this->assertTrue($fresh->isAnonymized());
    }

    // AC 10, Auditoria AC 9: nenhuma linha do contato guarda o nome, o e-mail ou o telefone antigos.
    public function test_limpa_a_auditoria_do_contato_e_grava_anonymized_sem_dado_pessoal(): void
    {
        $other = Contact::factory()->create(['name' => 'Bia Reis']);
        AuditLog::query()->create(['user_id' => $this->admin->id, 'action' => 'created', 'outcome' => 'success', 'auditable_type' => 'contact', 'auditable_id' => $other->id, 'changes' => ['name' => [null, 'Bia Reis']]]);
        $this->assertStringContainsString(self::OLD[1], $this->rawChanges());

        app(ContactService::class)->anonymize($this->contact, $this->admin);

        $raw = $this->rawChanges();
        foreach ([...self::OLD, 'ana@padaria.test', '1133334444'] as $personal) {
            $this->assertStringNotContainsString($personal, $raw);
        }
        // Só o que não é pessoal fica: a ida e a volta da desativação e o vínculo com o cliente sai junto do nome.
        $this->assertSame(
            ['created' => null, 'updated' => null, 'deactivated' => ['active' => [true, false]], 'reactivated' => ['active' => [false, true]]],
            AuditLog::query()->where('auditable_type', 'contact')->where('auditable_id', $this->contact->id)
                ->where('action', '!=', 'anonymized')->orderBy('id')->get()
                ->mapWithKeys(fn (AuditLog $log) => [$log->action => $this->withoutClient($log->changes)])->all(),
        );

        $log = AuditLog::query()->where('auditable_type', 'contact')->where('auditable_id', $this->contact->id)->where('action', 'anonymized')->sole();
        $this->assertSame([$this->admin->id, 'success', null], [$log->user_id, $log->outcome, $log->ai_authorization_id]);
        $this->assertSame(['active' => [true, false]], $log->changes);

        // A limpeza não toca outro registro.
        $this->assertSame(['name' => [null, 'Bia Reis']], AuditLog::query()->where('auditable_id', $other->id)->sole()->changes);
    }

    /**
     * client_id não é dado pessoal e fica no created; o resto da linha precisa ter sumido.
     *
     * @param  array<string, mixed>|null  $changes
     * @return array<string, mixed>|null
     */
    private function withoutClient(?array $changes): ?array
    {
        $rest = array_diff_key($changes ?? [], ['client_id' => true]);

        return $rest === [] ? null : $rest;
    }

    // AC 10: contato já desativado fica com a desativação de antes; a linha anonymized vai sem changes.
    public function test_contato_ja_desativado_mantem_quem_desativou(): void
    {
        $other = User::factory()->admin()->create();
        app(ContactService::class)->deactivate($this->contact, $other);
        $stamp = $this->contact->fresh()->deactivated_at;

        app(ContactService::class)->anonymize($this->contact->fresh(), $this->admin);

        $fresh = $this->contact->fresh();
        $this->assertSame([$other->id, $stamp->toDateTimeString()], [$fresh->deactivated_by, $fresh->deactivated_at->toDateTimeString()]);
        $this->assertNull(AuditLog::query()->where('auditable_id', $this->contact->id)->where('action', 'anonymized')->sole()->changes);
    }

    // AC 12: estado final; anonimizar de novo é recusado e nada muda.
    public function test_anonimizar_de_novo_e_recusado(): void
    {
        app(ContactService::class)->anonymize($this->contact, $this->admin);
        $rows = AuditLog::query()->count();

        try {
            app(ContactService::class)->anonymize($this->contact->fresh(), $this->admin);
            $this->fail('Anonimizou duas vezes.');
        } catch (DomainException $e) {
            $this->assertSame('Contato anonimizado: não pode ser alterado', $e->getMessage());
        }

        $this->assertSame($rows, AuditLog::query()->count());
    }

    // Edge case: a falha no meio desfaz tudo (transação), inclusive a limpeza já feita na auditoria.
    public function test_falha_no_meio_desfaz_tudo(): void
    {
        $before = $this->rawChanges();
        $rows = AuditLog::query()->count();
        // A limpeza da auditoria roda de verdade; a gravação da linha anonymized, depois dela, falha.
        $this->partialMock(AuditService::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('log')->withSomeOfArgs(AuditActionEnum::ANONYMIZED)->andThrow(new RuntimeException('falha no meio')));

        try {
            app(ContactService::class)->anonymize($this->contact, $this->admin);
            $this->fail('Não falhou.');
        } catch (RuntimeException $e) {
            $this->assertSame('falha no meio', $e->getMessage());
        }

        $fresh = $this->contact->fresh();
        $this->assertSame(self::OLD, [$fresh->name, $fresh->email, $fresh->phone]);
        $this->assertTrue($fresh->isActive());
        $this->assertFalse($fresh->isAnonymized());
        $this->assertSame($before, $this->rawChanges());
        $this->assertSame($rows, AuditLog::query()->count());
    }
}
