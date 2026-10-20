<?php

namespace App\Domains\Projetos\Notifications;

use App\Domains\Projetos\Models\ReworkReason;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\ValidationType;
use App\Platform\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * O card voltou para sprint ou desenvolvimento (retrabalho; F3 §07, P22, R3): avisa o dev do
 * card no sistema (sino) e por e-mail, pelo SMTP do .env. O texto é montado na hora do envio e
 * guardado pronto: o sino não refaz a frase se o card ou o motivo mudarem depois.
 */
class TaskReturned extends Notification implements ShouldQueue
{
    use Queueable;

    private readonly string $text;

    private readonly string $url;

    private readonly string $key;

    /** @param  array{rework_origin_id: ?int, rework_reason_id: ?int, reason_text: ?string}  $fields */
    public function __construct(Task $task, User $actor, string $stageName, array $fields)
    {
        $origin = ValidationType::query()->whereKey($fields['rework_origin_id'])->value('label');
        $reason = $fields['reason_text'] ?? ReworkReason::query()->whereKey($fields['rework_reason_id'])->value('label');

        $this->key = $task->key();
        $this->url = $task->url();
        $this->text = "{$this->key} — {$task->title} voltou para {$stageName}: origem {$origin}, motivo {$reason}, por {$actor->name}.";
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /** @return array{text: string, url: string, key: string} */
    public function toArray(object $notifiable): array
    {
        return ['text' => $this->text, 'url' => $this->url, 'key' => $this->key];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->key} voltou para você")
            ->view(['mail.task-notice', 'mail.task-notice-text'], [
                'text' => $this->text,
                'url' => $this->url,
                'appName' => config('app.name'),
                'brandName' => config('app.brand.name'),
            ]);
    }
}
