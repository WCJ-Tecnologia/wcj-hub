<?php

namespace App\Domains\Projetos\Controllers;

use App\Domains\Projetos\Models\ReworkReason;
use App\Domains\Projetos\Requests\StoreReworkReasonRequest;
use App\Domains\Projetos\Services\ListService;
use App\Domains\Projetos\Services\ProjectSettingsPresenter;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Administração → Listas (F2 §02 a §04; G7, P40): os motivos de retrabalho, só do
 * Administrador (ReworkReasonPolicy). A regra mora no ListService.
 */
class ReworkReasonController extends Controller
{
    public function __construct(
        private readonly ListService $lists,
        private readonly ProjectSettingsPresenter $presenter,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ReworkReason::class);

        $user = $request->user();
        $showInactive = $request->boolean('show_inactive');

        return Inertia::render('admin/lists/index', [
            'reworkReasons' => array_map(fn (ReworkReason $reason) => $this->presenter->reworkReason($reason, $user), $this->lists->reworkReasons($showInactive)),
            'filters' => ['show_inactive' => $showInactive],
            'can' => ['create' => $user->can('create', ReworkReason::class)],
        ]);
    }

    public function store(StoreReworkReasonRequest $request): RedirectResponse
    {
        $reason = $this->lists->createReworkReason($request->reasonData());

        return back()->with('success', "Motivo {$reason->label} criado.");
    }

    public function update(StoreReworkReasonRequest $request, ReworkReason $reworkReason): RedirectResponse
    {
        return $this->attempt(fn () => $this->lists->updateReworkReason($reworkReason, $request->reasonData()), 'Motivo atualizado.');
    }

    public function deactivate(Request $request, ReworkReason $reworkReason): RedirectResponse
    {
        $this->authorize('deactivate', $reworkReason);

        return $this->attempt(fn () => $this->lists->deactivateReworkReason($reworkReason, $request->user()), "Motivo {$reworkReason->label} desativado.");
    }

    public function reactivate(Request $request, ReworkReason $reworkReason): RedirectResponse
    {
        $this->authorize('reactivate', $reworkReason);

        return $this->attempt(fn () => $this->lists->reactivateReworkReason($reworkReason, $request->user()), "Motivo {$reworkReason->label} reativado.");
    }

    public function destroy(Request $request, ReworkReason $reworkReason): RedirectResponse
    {
        $this->authorize('delete', $reworkReason);

        return $this->attempt(fn () => $this->lists->deleteReworkReason($reworkReason, $request->user()), "Motivo {$reworkReason->label} excluído.");
    }
}
