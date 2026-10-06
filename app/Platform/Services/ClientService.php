<?php

namespace App\Platform\Services;

use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\Client;
use App\Platform\Models\User;
use App\Platform\Policies\ClientPolicy;
use App\Rules\AlphanumericCnpj;
use App\Support\Paises;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Clientes (N5 a N7, N11, G2). O gestor cria e edita pelo mesmo Service (N6; B2 chama daqui),
 * então a validação mora aqui e o FormRequest só a reaproveita. Toda escrita grava a auditoria
 * na mesma transação. Recusa de regra sai como DomainException, com a mensagem para a tela.
 */
class ClientService
{
    public const ALREADY_USED = 'Cliente já usado: desative em vez de excluir';

    public const CNPJ_REQUIRED = 'Informe o CNPJ.';

    public function __construct(private readonly AuditService $audit) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'country' => ['required', 'string', Rule::in(Paises::codigos())],
            // Fora do Brasil o documento é identificador fiscal livre e opcional (N5).
            'document' => [
                'nullable', 'string', 'max:30', 'required_if:country,'.Paises::BRASIL,
                Rule::when(fn (Fluent $input) => $input->get('country') === Paises::BRASIL, [new AlphanumericCnpj]),
            ],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return ['document.required_if' => self::CNPJ_REQUIRED];
    }

    /**
     * O rótulo do documento muda com o país (F1 §02).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    public static function attributes(array $data): array
    {
        return ['name' => 'nome', 'country' => 'país', 'document' => self::documentLabel($data['country'] ?? null)];
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data, User $actor): Client
    {
        $clean = $this->validated($data, null);

        return DB::transaction(function () use ($clean, $actor) {
            $client = Client::query()->create($clean);
            $this->audit->log(AuditActionEnum::CREATED, $client, null, $this->snapshot($client), $actor->id);

            return $client;
        });
    }

    /** @param  array<string, mixed>  $data */
    public function update(Client $client, array $data, User $actor): void
    {
        $clean = $this->validated($data, $client);

        DB::transaction(function () use ($client, $clean, $actor) {
            $before = $this->snapshot($client);
            $client->fill($clean)->save();
            $this->audit->log(AuditActionEnum::UPDATED, $client, $before, $this->snapshot($client), $actor->id);
        });
    }

    /** N11: projeto aberto não impede; o cliente só sai das listas de escolha. */
    public function deactivate(Client $client, User $actor): void
    {
        // A Policy já responde 403; a recusa aqui vale para quem chamar o Service direto (N7).
        if ($client->isWcj()) {
            throw new DomainException(ClientPolicy::WCJ_NOT_DEACTIVATABLE);
        }

        if (! $client->isActive()) {
            return;
        }

        DB::transaction(function () use ($client, $actor) {
            $client->setActive(false, $actor)->save();
            $this->audit->log(AuditActionEnum::DEACTIVATED, $client, ['active' => true], ['active' => false], $actor->id);
        });
    }

    public function reactivate(Client $client, User $actor): void
    {
        if ($client->isActive()) {
            return;
        }

        DB::transaction(function () use ($client, $actor) {
            $client->setActive(true, $actor)->save();
            $this->audit->log(AuditActionEnum::REACTIVATED, $client, ['active' => false], ['active' => true], $actor->id);
        });
    }

    /** G2: só cliente sem contato e sem projeto; o resto se desativa. */
    public function delete(Client $client, User $actor): void
    {
        $before = $this->snapshot($client);

        $deleted = $client->deleteIfUnused(function (Client $deleted) use ($before, $actor) {
            $this->audit->log(AuditActionEnum::DELETED, $deleted, $before, null, $actor->id);
        });

        if (! $deleted) {
            throw new DomainException(self::ALREADY_USED);
        }
    }

    /**
     * Lista da tela (F1 §03, §05): desativados só com "Mostrar desativados". A busca no
     * documento aceita o CNPJ com ou sem máscara.
     *
     * @param  array{search: string, country: string|null, show_inactive: bool}  $filters
     * @return LengthAwarePaginator<int, Client>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $search = $filters['search'];
        $unmasked = AlphanumericCnpj::normalize($search);

        return Client::query()
            ->with('deactivatedBy:id,name')
            ->when(! $filters['show_inactive'], fn ($query) => $query->active())
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$search}%")
                ->orWhere('document', 'like', "%{$search}%")
                ->when($unmasked !== '', fn ($masked) => $masked->orWhere('document', 'like', "%{$unmasked}%"))))
            ->when($filters['country'] !== null, fn ($query) => $query->where('country', $filters['country']))
            ->orderBy('name')
            ->paginate(20);
    }

    /**
     * Lista de escolha (NUC-13): só ativos, id e nome.
     *
     * @return list<array{id: int, name: string}>
     */
    public function choiceList(): array
    {
        return Client::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Client $client) => ['id' => $client->id, 'name' => $client->name])
            ->all();
    }

    /**
     * Valida, tira a máscara do CNPJ e confere o repetido no país (N5). A colação do banco
     * compara sem caixa, e o CNPJ já chega maiúsculo.
     *
     * @param  array<string, mixed>  $data
     * @return array{name: string, country: string, document: string|null}
     */
    private function validated(array $data, ?Client $ignore): array
    {
        $valid = Validator::make($data, self::rules(), self::messages(), self::attributes($data))->validate();

        $document = trim((string) ($valid['document'] ?? ''));
        if ($valid['country'] === Paises::BRASIL) {
            $document = AlphanumericCnpj::normalize($document);
        }
        $clean = ['name' => $valid['name'], 'country' => $valid['country'], 'document' => $document === '' ? null : $document];

        $existing = $clean['document'] === null ? null : Client::query()
            ->where('country', $clean['country'])
            ->where('document', $clean['document'])
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->first(['id', 'name']);

        if ($existing !== null) {
            throw ValidationException::withMessages([
                'document' => "Já existe o cliente {$existing->name} com este ".self::documentLabel($clean['country']),
            ]);
        }

        return $clean;
    }

    private static function documentLabel(mixed $country): string
    {
        return $country === Paises::BRASIL ? 'CNPJ' : 'identificador fiscal';
    }

    /** @return array<string, mixed> */
    private function snapshot(Client $client): array
    {
        return $client->only(['name', 'country', 'document']);
    }
}
