<?php

namespace App\Platform\Services;

use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use App\Platform\Policies\ContactPolicy;
use DomainException;
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

    public function __construct(private readonly AuditService $audit) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'string', 'email', 'max:160'],
            // Sem máscara (o FormRequest tira); cabe número de fora do Brasil.
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

        return ['contact' => $contact, 'warning' => $this->duplicateWarning($contact)];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return string|null o aviso de e-mail repetido (N8)
     */
    public function update(Contact $contact, array $data, User $actor): ?string
    {
        $this->ensureNotAnonymized($contact);
        $clean = $this->validated($data);

        DB::transaction(function () use ($contact, $clean, $actor) {
            $before = $this->snapshot($contact);
            $contact->fill($clean)->save();
            $this->audit->log(AuditActionEnum::UPDATED, $contact, $before, $this->snapshot($contact), $actor->id);
        });

        return $this->duplicateWarning($contact);
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
        $this->ensureNotAnonymized($contact);

        if ($contact->isActive()) {
            return;
        }

        DB::transaction(function () use ($contact, $actor) {
            $contact->setActive(true, $actor)->save();
            $this->audit->log(AuditActionEnum::REACTIVATED, $contact, ['active' => false], ['active' => true], $actor->id);
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

    /** "Já existe Fulano (Cliente X) com este e-mail" (N8, F1 §07), entre os contatos ativos. */
    private function duplicateWarning(Contact $contact): ?string
    {
        if ($contact->email === null) {
            return null;
        }

        $other = Contact::query()
            ->active()
            ->with('client:id,name')
            ->where('email', $contact->email)
            ->whereKeyNot($contact->getKey())
            ->orderBy('id')
            ->first();

        return $other === null ? null : "Já existe {$other->name} ({$other->client?->name}) com este e-mail";
    }

    /** D5: anonimizado é estado final. A Policy já responde 403; isto vale para quem chama direto. */
    private function ensureNotAnonymized(Contact $contact): void
    {
        if ($contact->isAnonymized()) {
            throw new DomainException(ContactPolicy::ANONYMIZED);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{name: string, email: string|null, phone: string|null}
     */
    private function validated(array $data): array
    {
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
