<?php

namespace App\Domains\Projetos\Controllers;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Services\TaskHistoryScreen;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A linha do tempo do card, `/projetos/{id}/cards/{chave}/historico` (F7 §01 a §03; escopo B7):
 * o resumo no topo e um movimento por linha. Vê quem vê o card (TaskPolicy::view = vê o projeto,
 * F7 §09); o *Ver na auditoria* só o Administrador.
 */
class TaskHistoryController extends Controller
{
    public function __construct(private readonly TaskHistoryScreen $screen) {}

    public function show(Request $request, Project $project, Task $card): Response
    {
        $this->authorize('view', $card);

        return Inertia::render('projects/task-history', $this->screen->props($card, $request->user()));
    }
}
