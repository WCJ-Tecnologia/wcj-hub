<?php

namespace App\Platform\Services;

use App\Platform\Contracts\ClientManagement;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use App\Platform\Policies\ContactPolicy;
use App\Support\LocalTime;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Contatos dos clientes (N8, N11, N12, G2). A validação mora aqui e o FormRequest só a
 * reaproveita, como no ClientService. Toda escrita grava a auditoria na mesma transação. Recusa
 * de regra sai como DomainException, com a mensagem para a tela.
 */
class ContactService
{
    public const ALREADY_USED = 'Contato já usado: desative em vez de excluir';

    public const CLIENT_INACTIVE = 'Cliente desativado: reative o cliente para cadastrar contato.';

    public const DUPLICATE_EMAIL_HIDDEN = 'Já existe um contato com este e-mail';

    public function __construct(
        private readonly AuditService $audit,
        private readonly ClientManagement $management,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'string', 'email', 'max:160'],
            // Texto como a pessoa digitou (F1 §02, D3): cabe o número de fora do Brasil, com o "+".
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    /** @return array<string, string> */
    public static function attributes(): array
    {
        return ['name' => 'nome', 'email' => 'e-mail', 'phone' => 'telefone'];
    }

    /**
     * N8: e-mail repetido não recusa; o aviso volta para a tela.
     *
     * @param  array<string, mixed>  $data
     * @return array{contact: Contact, warning: string|null}
     */
    public function create(Client $client, array $data, User $actor): array
    {
        $clean = $this->validated($data);

        // N11: cliente desativado sai das listas de escolha, e não ganha contato novo.
        if (! $client->isActive()) {
            throw ValidationException::withMessages(['client' => self::CLIENT_INACTIVE]);
        }

        $contact = DB::transaction(function () use ($client, $clean, $actor) {
            $contact = $client->contacts()->create($clean);
            $this->audit->log(AuditActionEnum::CREATED, $contact, null, $this->snapshot($contact), $actor->id);

            return $contact;
        });

        return ['contact' => $contact, 'warning' => $this->duplicateWarning($contact, $actor)];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return string|null o aviso de e-mail repetido (N8)
     */
    public function update(Contact $contact, array $data, User $actor): ?string
    {
        $clean = $this->validated($data);

        $updated = DB::transaction(function () use ($contact, $clean, $actor) {
            $locked = $this->lockedNotAnonymized($contact);
            $before = $this->snapshot($locked);
            $locked->fill($clean)->save();
            $this->audit->log(AuditActionEnum::UPDATED, $locked, $before, $this->snapshot($locked), $actor->id);

            return $locked;
        });

        return $this->duplicateWarning($updated, $actor);
    }

    /** N11: sai das listas de escolha; o card que já o cita continua citando. */
    public function deactivate(Contact $contact, User $actor): void
    {
        if (! $contact->isActive()) {
            return;
        }

        DB::transaction(function () use ($contact, $actor) {
            $contact->setActive(false, $actor)->save();
            $this->audit->log(AuditActionEnum::DEACTIVATED, $contact, ['active' => true], ['active' => false], $actor->id);
        });
    }

    public function reactivate(Contact $contact, User $actor): void
    {
        DB::transaction(function () use ($contact, $actor) {
            $locked = $this->lockedNotAnonymized($contact);

            if ($locked->isActive()) {
                return;
            }

            $locked->setActive(true, $actor)->save();
            $this->audit->log(AuditActionEnum::REACTIVATED, $locked, ['active' => false], ['active' => true], $actor->id);
        });
    }

    /** G2: só contato nunca citado em card de suporte; o resto se desativa. */
    public function delete(Contact $contact, User $actor): void
    {
        $before = $this->snapshot($contact);

        $deleted = $contact->deleteIfUnused(function (Contact $deleted) use ($before, $actor) {
            $this->audit->log(AuditActionEnum::DELETED, $deleted, $before, null, $actor->id);
        });

        if (! $deleted) {
            throw new DomainException(self::ALREADY_USED);
        }
    }

    /**
     * N12, a pedido do titular: numa transação só, troca o nome, apaga e-mail e telefone, tira
     * os mesmos campos da auditoria do contato e grava anonymized sem dado pessoal. Também
     * desativa (D5), para sair das listas de escolha. Irreversível.
     */
    public function anonymize(Contact $contact, User $actor): void
    {
        DB::transaction(function () use ($contact, $actor) {
            $locked = $this->lockedNotAnonymized($contact);
            $wasActive = $locked->isActive();

            $locked->forceFill(['name' => Contact::ANONYMIZED_NAME, 'email' => null, 'phone' => null])
                ->setActive(false, $actor)
                ->save();
            $this->audit->scrubPersonalData($locked, ['name', 'email', 'phone']);
            $this->audit->log(
                AuditActionEnum::ANONYMIZED,
                $locked,
                $wasActive ? ['active' => true] : null,
                $wasActive ? ['active' => false] : null,
                $actor->id,
            );
        });
    }

    /**
     * Lista geral (F1 §03, §05). O gestor só vê os clientes que o ClientManagement lista para ele
     * (F1 §09; nenhum até o B2). A busca no telefone compara só os dígitos dos dois lados.
     *
     * @param  array{search: string, client_id: int|null, show_inactive: bool}  $filters
     * @return LengthAwarePaginator<int, Contact>
     */
    public function paginate(array $filters, User $viewer): LengthAwarePaginator
    {
        $clientIds = $this->visibleClientIds($viewer);
        $search = $filters['search'];
        $digits = preg_replace('/\D/', '', $search) ?? '';

        return Contact::query()
            ->withAnonymized()
            ->with(['client:id,name', 'deactivatedBy:id,name'])
            ->when($clientIds !== null, fn ($query) => $query->whereIn('client_id', $clientIds))
            ->when(! $filters['show_inactive'], fn ($query) => $query->active())
            ->when($filters['client_id'] !== null, fn ($query) => $query->where('client_id', $filters['client_id']))
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->when($digits !== '', fn ($phone) => $phone->orWhereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') LIKE ?", ["%{$digits}%"]))))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20);
    }

    /** @return list<array{id: int, name: string}> o filtro Cliente: os mesmos clientes que a lista mostra */
    public function clientOptions(User $viewer): array
    {
        $clientIds = $this->visibleClientIds($viewer);

        return Client::query()
            ->when($clientIds !== null, fn ($query) => $query->whereIn('id', $clientIds))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Client $client) => ['id' => $client->id, 'name' => $client->name])
            ->all();
    }

    /** @return list<int>|null nulo: todos */
    private function visibleClientIds(User $viewer): ?array
    {
        return $viewer->isAdmin() ? null : $this->management->managedClientIds($viewer);
    }

    /**
     * A linha da lista geral e da ficha do cliente: as duas telas mostram o mesmo contato com os
     * mesmos botões, decididos pela ContactPolicy.
     *
     * @return array<string, mixed>
     */
    public function row(Contact $contact, User $viewer): array
    {
        $active = $contact->isActive();

        return [
            ...$contact->only(['id', 'name', 'email', 'phone', 'client_id']),
            'client_name' => $contact->client?->name,
            'active' => $active,
            'anonymized' => $contact->isAnonymized(),
            'deactivated_at' => LocalTime::format($contact->deactivated_at),
            'deactivated_by' => $contact->deactivatedBy?->name,
            'can' => [
                'update' => $viewer->can('update', $contact),
                'deactivate' => $active && $viewer->can('deactivate', $contact),
                'reactivate' => ! $active && $viewer->can('reactivate', $contact),
                'delete' => $viewer->can('delete', $contact),
                'anonymize' => $viewer->can('anonymize', $contact),
            ],
        ];
    }

    /**
     * N8, F1 §07, entre os contatos ativos. Nome e cliente só para o Administrador: o outro contato
     * pode ser de cliente que o gestor não gere (F1 §09; D2 do Lucas em 07/10).
     */
    private function duplicateWarning(Contact $contact, User $viewer): ?string
    {
        $other = $contact->email === null ? null : Contact::query()
            ->active()
            ->with('client:id,name')
            ->where('email', $contact->email)
            ->whereKeyNot($contact->getKey())
            ->orderBy('id')
            ->first();

        return match (true) {
            $other === null => null,
            $viewer->isAdmin() => "Já existe {$other->name} ({$other->client?->name}) com este e-mail",
            default => self::DUPLICATE_EMAIL_HIDDEN,
        };
    }

    /**
     * D5: anonimizado é estado final. A Policy já responde 403; isto vale para quem chama direto e
     * para quem passou pela Policy antes de uma anonimização terminar: com a linha travada até o
     * commit, a checagem e a escrita veem o mesmo contato (A4 da review de 07/10). Chamar dentro
     * da transação da escrita.
     */
    private function lockedNotAnonymized(Contact $contact): Contact
    {
        $locked = Contact::query()->lockForUpdate()->findOrFail($contact->getKey());

        if ($locked->isAnonymized()) {
            throw new DomainException(ContactPolicy::ANONYMIZED);
        }

        return $locked;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{name: string, email: string|null, phone: string|null}
     */
    private function validated(array $data): array
    {
        // Quem chama o Service direto também não grava as pontas em branco (o HTTP já as tira).
        $data['phone'] = is_string($data['phone'] ?? null) ? trim($data['phone']) : ($data['phone'] ?? null);
        $valid = Validator::make($data, self::rules(), [], self::attributes())->validate();

        return [
            'name' => $valid['name'],
            'email' => ($valid['email'] ?? '') === '' ? null : $valid['email'],
            'phone' => ($valid['phone'] ?? '') === '' ? null : $valid['phone'],
        ];
    }

    /** @return array<string, mixed> */
    private function snapshot(Contact $contact): array
    {
        return $contact->only(['client_id', 'name', 'email', 'phone']);
    }
}
