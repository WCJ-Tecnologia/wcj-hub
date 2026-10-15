<?php

namespace App\Domains\Projetos\Controllers;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Requests\AllocateMembersRequest;
use App\Domains\Projetos\Services\ProjectMemberService;
use App\Http\Controllers\Controller;
use App\Platform\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Pessoas do projeto (F2 §04; P5). A regra mora no ProjectMemberService. */
class ProjectMemberController extends Controller
{
    public function __construct(private readonly ProjectMemberService $members) {}

    public function store(AllocateMembersRequest $request, Project $project): RedirectResponse
    {
        $count = $this->members->allocate($project, $request->userIds());

        return back()->with('success', $count === 1 ? 'Pessoa alocada. Ela já vê o projeto.' : "{$count} pessoas alocadas. Elas já veem o projeto.");
    }

    public function destroy(Request $request, Project $project, User $user): RedirectResponse
    {
        $this->authorize('manage', $project);

        $counts = $this->members->remove($project, $user, $request->user());
        if ($counts === null) {
            return back()->with('error', "{$user->name} não está no projeto.");
        }

        $detail = $counts['dev'] + $counts['qa'] > 0 ? " {$counts['dev']} tarefas ficaram sem dev e {$counts['qa']} sem QA." : '';

        return back()->with('success', "{$user->name} saiu do projeto.{$detail}");
    }
}
