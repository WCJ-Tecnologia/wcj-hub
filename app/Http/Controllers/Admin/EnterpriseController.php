<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditActionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEnterpriseRequest;
use App\Models\Enterprise;
use App\Services\AuditService;
use App\Services\Scoping;
use App\Support\LocalTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class EnterpriseController extends Controller
{
    public function __construct(
        private readonly Scoping $scoping,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Enterprise::class);

        $search = (string) $request->string('search');
        $user = $request->user();

        $enterprises = $this->scoping->enterprises(Enterprise::query(), $user)
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('nickname', 'like', "%{$search}%")
                    ->orWhere('document', 'like', "%{$search}%");
            }))
            ->with('deactivatedBy:id,name')
            ->withCount('users')
            ->orderBy('nickname')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Enterprise $enterprise) => [
                'id' => $enterprise->id,
                'name' => $enterprise->name,
                'nickname' => $enterprise->nickname,
                'document' => $enterprise->document,
                'active' => $enterprise->isActive(),
                'users_count' => $enterprise->users_count,
                // Who deactivated only reaches whoever can edit (seguranca.md §6).
                ...($user->can('update', $enterprise) ? [
                    'deactivated_at' => LocalTime::format($enterprise->deactivated_at),
                    'deactivated_by' => $enterprise->deactivatedBy?->name,
                ] : []),
            ]);

        return Inertia::render('admin/enterprises/index', [
            'enterprises' => $enterprises,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(StoreEnterpriseRequest $request): RedirectResponse
    {
        $this->authorize('create', Enterprise::class);

        $data = $request->validated();

        $enterprise = new Enterprise(Arr::except($data, 'active'));
        $enterprise->setActive((bool) ($data['active'] ?? true), $request->user())->save();

        $this->audit->log(AuditActionEnum::CREATED, $enterprise, null, $this->snapshot($enterprise));

        return back()->with('success', 'Empresa cadastrada com sucesso.');
    }

    public function update(StoreEnterpriseRequest $request, Enterprise $enterprise): RedirectResponse
    {
        $this->authorize('update', $enterprise);

        $data = $request->validated();
        $before = $this->snapshot($enterprise);

        $enterprise->fill(Arr::except($data, 'active'));
        $enterprise->setActive((bool) ($data['active'] ?? $enterprise->isActive()), $request->user())->save();

        $this->audit->log(AuditActionEnum::UPDATED, $enterprise, $before, $this->snapshot($enterprise));

        return back()->with('success', 'Empresa atualizada.');
    }

    public function destroy(Enterprise $enterprise): RedirectResponse
    {
        $this->authorize('delete', $enterprise);

        $before = $this->snapshot($enterprise);

        $deleted = $enterprise->deleteIfUnused(
            fn (Enterprise $deleted) => $this->audit->log(AuditActionEnum::DELETED, $deleted, $before, null),
        );

        if (! $deleted) {
            return back()->with('error', 'Esta empresa já tem usuários ou filiais vinculados. Para tirá-la de uso, edite e desmarque "Empresa ativa".');
        }

        return back()->with('success', 'Empresa excluída.');
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Enterprise $enterprise): array
    {
        return [...$enterprise->only(['name', 'nickname', 'document']), 'active' => $enterprise->isActive()];
    }
}
