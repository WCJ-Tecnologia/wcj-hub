<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Models\Task;
use App\Platform\Requests\Concerns\AuthorizesBeforeValidation;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Card de suporte (F3 §02; P52): título, quem pediu, por onde, quando (`AAAA-MM-DD HH:MM` no
 * fuso de quem usa, gravado em UTC) e a prioridade. "Contato ativo do cliente do projeto" é do
 * TaskService, com a linha do projeto travada.
 */
class StoreSupportTaskRequest extends FormRequest
{
    use AuthorizesBeforeValidation;

    protected function abilityAndArguments(): array
    {
        return ['createSupport', [Task::class, $this->route('project')]];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'requester_contact_id' => ['required', 'integer'],
            'contact_channel_id' => ['required', 'integer', Rule::exists('contact_channels', 'id')],
            'requested_at' => ['required', 'date_format:Y-m-d H:i'],
            'priority' => ['required', Rule::exists('task_priorities', 'slug')],
        ];
    }

    /**
     * @return array{title: string, requester_contact_id: int, contact_channel_id: int, requested_at: string, priority: string}
     */
    public function payload(): array
    {
        return [
            'title' => trim($this->validated('title')),
            'requester_contact_id' => (int) $this->validated('requester_contact_id'),
            'contact_channel_id' => (int) $this->validated('contact_channel_id'),
            'requested_at' => CarbonImmutable::createFromFormat('Y-m-d H:i', $this->validated('requested_at'), LocalTime::zone())->utc()->toDateTimeString(),
            'priority' => $this->validated('priority'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['title' => 'título', 'requester_contact_id' => 'quem pediu', 'contact_channel_id' => 'por onde', 'requested_at' => 'quando', 'priority' => 'prioridade'];
    }
}
