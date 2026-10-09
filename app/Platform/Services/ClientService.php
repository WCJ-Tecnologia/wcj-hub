<?php

namespace App\Platform\Services;

use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\Client;
use App\Platform\Models\User;
use App\Platform\Policies\ClientPolicy;
use App\Platform\Services\Concerns\RefusesDuplicates;
use App\Rules\AlphanumericCnpj;
use App\Support\Like;
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
    use RefusesDuplicates;

    public const ALREADY_USED = 'Cliente já usado: desative em vez de excluir';

    public const CNPJ_REQUIRED = 'Informe o CNPJ.';

    public const WCJ_IDENTITY_LOCKED = 'O CNPJ e o país da WCJ não podem ser alterados';

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

        return $this->writeOrRefuseDuplicate(fn () => DB::transaction(function () use ($clean, $actor) {
            $client = Client::query()->create($clean);
            $this->audit->log(AuditActionEnum::CREATED, $client, null, $this->snapshot($client), $actor->id);

            return $client;
        }), fn () => $this->duplicateRefusal($clean, null));
    }

    /** @param  array<string, mixed>  $data */
    public function update(Client $client, array $data, User $actor): void
    {
        $clean = $this->validated($data, $client);

        // N7: a WCJ é reconhecida pelo país e pelo CNPJ (Client::isWcj); trocá-los tiraria dela a
        // trava de desativar e excluir. O nome continua editável.
        if ($client->isWcj() && ($clean['country'] !== $client->country || $clean['document'] !== $client->document)) {
            throw new DomainException(self::WCJ_IDENTITY_LOCKED);
        }

        $this->writeOrRefuseDuplicate(fn () => DB::transaction(function () use ($client, $clean, $actor) {
            $before = $this->snapshot($client);
            $client->fill($clean)->save();
            $this->audit->log(AuditActionEnum::UPDATED, $client, $before, $this->snapshot($client), $actor->id);
        }), fn () => $this->duplicateRefusal($clean, $client));
    }

    /** N11: projeto aberto não impede; o cliente só sai das listas de escolha. */
    public function deactivate(Client $client, User $actor): void
    {
        // A Policy já responde 403; a recusa aqui vale para quem chamar o Service direto (N7).
        if ($client->isWcj()) {
            throw new DomainException(ClientPolicy::WCJ_NOT_DEACTIVATABLE);
        }

        DB::transaction(function () use ($client, $actor) {
            // Decide pela linha travada, não pela instância em memória (CR-1-15).
            if (! Client::query()->lockForUpdate()->findOrFail($client->getKey())->isActive()) {
                return;
            }

            $client->setActive(false, $actor)->save();
            $this->audit->log(AuditActionEnum::DEACTIVATED, $client, ['active' => true], ['active' => false], $actor->id);
        });
    }

    public function reactivate(Client $client, User $actor): void
    {
        DB::transaction(function () use ($client, $actor) {
            // Pela linha travada, como o deactivate: dois "Reativar" juntos gravam uma linha só (CR-2-06).
            $locked = Client::query()->lockForUpdate()->findOrFail($client->getKey());
            if ($locked->isActive()) {
                return;
            }

            $locked->setActive(true, $actor)->save();
            $this->audit->log(AuditActionEnum::REACTIVATED, $locked, ['active' => false], ['active' => true], $actor->id);
        });
    }

    /** G2: só cliente sem contato e sem projeto; o resto se desativa. */
    public function delete(Client $client, User $actor): void
    {
        // Como em deactivate: a Policy já responde 403; isto vale para quem chamar o Service (N7).
        if ($client->isWcj()) {
            throw new DomainException(ClientPolicy::WCJ_NOT_DELETABLE);
        }

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
                ->where('name', 'like', Like::contains($search))
                ->orWhere('document', 'like', Like::contains($search))
                ->when($unmasked !== '', fn ($masked) => $masked->orWhere('document', 'like', Like::contains($unmasked)))))
            ->when($filters['country'] !== null, fn ($query) => $query->where('country', $filters['country']))
            ->orderBy('name')
            ->paginate(20);
    }

    /**
     * Lista de escolha (NUC-13): só ativos, id e nome. A Policy é conferida aqui, como no
     * UserService::choiceList() (decisão do Lucas Scholz, 09/10).
     *
     * @return list<array{id: int, name: string}>
     */
    public function choiceList(User $viewer): array
    {
        $this->audit->inspectFor($viewer, 'viewChoiceList', Client::class)->authorize();

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

        $duplicate = $this->duplicateMessage($clean, $ignore);
        if ($duplicate !== null) {
            throw ValidationException::withMessages(['document' => $duplicate]);
        }

        return $clean;
    }

    /**
     * "Já existe o cliente X com este CNPJ", ou nulo se o país e o documento estão livres.
     *
     * @param  array{name: string, country: string, document: string|null}  $clean
     */
    private function duplicateMessage(array $clean, ?Client $ignore): ?string
    {
        $existing = $clean['document'] === null ? null : Client::query()
            ->where('country', $clean['country'])
            ->where('document', $clean['document'])
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->first(['id', 'name']);

        return $existing === null ? null : "Já existe o cliente {$existing->name} com este ".self::documentLabel($clean['country']);
    }

    /**
     * O unique do país e documento barrou uma gravação que passou pelo validated(): relê e volta
     * como o mesmo erro de campo. Nulo se o repetido não aparece (outro unique): relança o original.
     *
     * @param  array{name: string, country: string, document: string|null}  $clean
     */
    private function duplicateRefusal(array $clean, ?Client $ignore): ?ValidationException
    {
        $duplicate = $this->duplicateMessage($clean, $ignore);

        return $duplicate === null ? null : ValidationException::withMessages(['document' => $duplicate]);
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
