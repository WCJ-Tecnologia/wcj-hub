<?php

namespace App\Platform\Services;

use App\Platform\Models\User;
use App\Support\LocalTime;
use Illuminate\Notifications\DatabaseNotification;

/**
 * O sino do shell (F3 §07; P22, R3): os avisos da própria pessoa, como o kit lê em
 * `props.notifications` (`NotificationsPayload`). O Núcleo só lê o que cada aviso gravou em
 * `data` (`text`, `url`): quem escreve o aviso é o módulo.
 */
class NotificationPresenter
{
    /** Quantos o sino mostra; o resto fica no banco, sem tela de "todos" (F3 §07 não pede). */
    public const LIMIT = 10;

    /** @return array{unread_count: int, items: list<array{id: string, text: string, url: ?string, created_at: string, read_at: ?string}>} */
    public function payload(User $user): array
    {
        return [
            'unread_count' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()
                ->reorder()
                ->orderByRaw('read_at IS NULL DESC')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(self::LIMIT)
                ->get()
                ->map(fn (DatabaseNotification $notification) => [
                    'id' => $notification->id,
                    'text' => (string) ($notification->data['text'] ?? ''),
                    // Nulo quando o card foi excluído (spec b3-arquivar-e-avisos): o texto fica, o link não.
                    'url' => $notification->data['url'] ?? null,
                    'created_at' => LocalTime::format($notification->created_at, 'Y-m-d H:i'),
                    'read_at' => LocalTime::format($notification->read_at, 'Y-m-d H:i'),
                ])
                ->values()
                ->all(),
        ];
    }
}
