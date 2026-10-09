<?php

namespace App\Platform\Services;

use App\Platform\Models\Contact;
use App\Platform\Models\User;
use App\Support\LocalTime;

/**
 * A camada de exibição do contato (stacks/ui.md §2), como o AuditLogPresenter: a linha da lista
 * geral e da ficha do cliente. As duas telas mostram o mesmo contato com os mesmos botões,
 * decididos pela ContactPolicy.
 */
class ContactPresenter
{
    /** @return array<string, mixed> */
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
}
