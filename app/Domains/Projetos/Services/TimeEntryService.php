<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TimeEntry;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Horas realizadas (P30, P31; F5 §04; docs/banco.md, time_entries): quem trabalhou lança à mão,
 * por dia, em horas decimais, sem cronômetro. O lançamento é sempre de quem está logado: o gestor
 * não lança pelo dev, só corrige (F5 §10.14). Corrigir edita data e horas; excluir apaga de
 * verdade o lançamento feito por engano (F5 §10.13). Não confere a soma do dia nem compara com
 * contrato (P31). Quem pode o quê é da TimeEntryPolicy; as faixas (0 < horas ≤ 24, data até
 * hoje) são do FormRequest e do CHECK do banco. Toda escrita audita com o antes (G3).
 */
class TimeEntryService
{
    public const ARCHIVED_TASK = 'O card está arquivado: não recebe lançamento novo.';

    public function __construct(private readonly AuditService $audit) {}

    /** @param  array{worked_on: string, hours: string}  $data */
    public function create(Task $task, array $data, User $actor): TimeEntry
    {
        return DB::transaction(function () use ($task, $data, $actor) {
            // A linha da tarefa travada: o arquivamento (B3) e o lançamento não se cruzam.
            $locked = Task::query()->lockForUpdate()->findOrFail($task->id);
            if ($locked->archived_at !== null) {
                throw new DomainException(self::ARCHIVED_TASK);
            }

            $entry = $locked->timeEntries()->create([
                'user_id' => $actor->id,
                'worked_on' => $data['worked_on'],
                'hours' => $data['hours'],
            ]);
            $this->audit->log(AuditActionEnum::CREATED, $entry, null, $this->snapshot($entry), $actor->id);

            return $entry;
        });
    }

    /** @param  array{worked_on: string, hours: string}  $data */
    public function update(TimeEntry $entry, array $data, User $actor): TimeEntry
    {
        return DB::transaction(function () use ($entry, $data, $actor) {
            $locked = TimeEntry::query()->lockForUpdate()->findOrFail($entry->id);
            $before = $this->snapshot($locked);
            $locked->fill(['worked_on' => $data['worked_on'], 'hours' => $data['hours']])->save();

            if ($locked->wasChanged()) {
                $this->audit->log(AuditActionEnum::UPDATED, $locked, $before, $this->snapshot($locked), $actor->id);
            }

            return $locked;
        });
    }

    public function delete(TimeEntry $entry, User $actor): void
    {
        DB::transaction(function () use ($entry, $actor) {
            $locked = TimeEntry::query()->lockForUpdate()->find($entry->id);
            if ($locked === null) {
                return;
            }

            $before = $this->snapshot($locked);
            $locked->delete();
            $this->audit->log(AuditActionEnum::DELETED, $locked, $before, null, $actor->id);
        });
    }

    /** @return array<string, mixed> */
    private function snapshot(TimeEntry $entry): array
    {
        return [
            'task_id' => $entry->task_id,
            'user_id' => $entry->user_id,
            'worked_on' => $entry->worked_on?->toDateString(),
            'hours' => $entry->hours,
        ];
    }
}
