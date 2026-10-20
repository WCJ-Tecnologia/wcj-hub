<?php

namespace App\Domains\Projetos\Notifications;

use App\Domains\Projetos\Models\Task;
use App\Platform\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A pessoa passou a ser dev ou QA do card (F3 §07, P22, R3): aviso no sistema (sino) e por e-mail.
 * Quem define a si mesmo não é avisado (F3 §10.5): quem dispara confere. O texto é montado na hora
 * e guardado pronto, como o do TaskReturned.
 */
class TaskAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public const DEVELOPER = 'dev';

    public const QA = 'QA';

    private readonly string $text;

    private readonly string $url;

    private readonly string $key;

    public function __construct(Task $task, User $actor, string $role)
    {
        $this->key = $task->key();
        $this->url = $task->url();
        $this->text = "Você é o {$role} de {$this->key} — {$task->title} (projeto {$task->project->name}), definido por {$actor->name}.";
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
            ->subject("{$this->key} é seu agora")
            ->view(['mail.task-notice', 'mail.task-notice-text'], [
                'text' => $this->text,
                'url' => $this->url,
                'appName' => config('app.name'),
                'brandName' => config('app.brand.name'),
            ]);
    }
}
