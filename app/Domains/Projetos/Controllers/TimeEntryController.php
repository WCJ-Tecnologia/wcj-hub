<?php

namespace App\Domains\Projetos\Controllers;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TimeEntry;
use App\Domains\Projetos\Requests\StoreTimeEntryRequest;
use App\Domains\Projetos\Services\TimeEntryPresenter;
use App\Domains\Projetos\Services\TimeEntryQuery;
use App\Domains\Projetos\Services\TimeEntryService;
use App\Http\Controllers\Controller;
use App\Support\Busca;
use App\Support\DiasUteis;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Horas realizadas (F5 §02 a §05; P30): a tela *Minhas horas* (`/minhas-horas`), lançar numa
 * tarefa (`/projetos/{id}/tarefas/{task}/horas`, a mesma rota que a ficha do card do B3 usa),
 * corrigir e excluir. A TimeEntryPolicy decide quem; as faixas são do FormRequest.
 */
class TimeEntryController extends Controller
{
    public function __construct(
        private readonly TimeEntryService $entries,
        private readonly TimeEntryQuery $query,
        private readonly TimeEntryPresenter $presenter,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TimeEntry::class);

        $user = $request->user();
        $filters = $this->filters($request);
        $entries = $this->query->mine($user, $filters);

        return Inertia::render('time-entries/index', [
            'entries' => $entries->map(fn (TimeEntry $entry) => $this->presenter->row($entry, $user))->all(),
            'totals' => $this->presenter->totals($entries),
            'projects' => $this->query->projects($user),
            'tasks' => $this->query->tasksToLog($user)->map(fn (Task $task) => $this->presenter->taskOption($task, $user))->all(),
            'filters' => $filters,
            'today' => DiasUteis::today(),
        ]);
    }

    public function store(StoreTimeEntryRequest $request, Project $project, Task $task): RedirectResponse
    {
        return $this->attempt(fn () => $this->entries->create($task, $request->entry(), $request->user()), 'Horas lançadas.');
    }

    public function update(StoreTimeEntryRequest $request, TimeEntry $timeEntry): RedirectResponse
    {
        return $this->attempt(fn () => $this->entries->update($timeEntry, $request->entry(), $request->user()), 'Lançamento corrigido.');
    }

    public function destroy(Request $request, TimeEntry $timeEntry): RedirectResponse
    {
        $this->authorize('delete', $timeEntry);

        return $this->attempt(fn () => $this->entries->delete($timeEntry, $request->user()), 'Lançamento excluído.');
    }

    /**
     * Período padrão: a semana atual, de segunda a domingo (F5 §03).
     *
     * @return array{from: string, to: string, project_id: ?int, search: string}
     */
    private function filters(Request $request): array
    {
        $today = CarbonImmutable::parse(DiasUteis::today());
        $date = fn (string $key, string $default) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query($key)) === 1 && strtotime((string) $request->query($key)) !== false
            ? (string) $request->query($key) : $default;
        $from = $date('from', $today->startOfWeek(CarbonImmutable::MONDAY)->toDateString());
        $to = $date('to', $today->endOfWeek(CarbonImmutable::SUNDAY)->toDateString());

        return [
            'from' => min($from, $to),
            'to' => max($from, $to),
            'project_id' => $request->integer('project_id') > 0 ? $request->integer('project_id') : null,
            'search' => Busca::termo($request),
        ];
    }
}
