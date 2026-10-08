<?php

namespace App\Platform\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Platform\Contracts\ClientManagement;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use App\Platform\Requests\Admin\StoreClientRequest;
use App\Platform\Services\ClientService;
use App\Platform\Services\ContactService;
use App\Support\LocalTime;
use App\Support\Paises;
use Closure;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Clientes (F1; N5 a N7, N11). A regra mora no ClientService; o gestor, na ClientPolicy. */
class ClientController extends Controller
{
    public function __construct(
        private readonly ClientService $clients,
        private readonly ClientManagement $management,
        private readonly ContactService $contactService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Client::class);

        $country = strtoupper((string) $request->string('country'));
        $filters = [
            'search' => (string) $request->string('search'),
            'country' => in_array($country, Paises::codigos(), true) ? $country : null,
            'show_inactive' => $request->boolean('show_inactive'),
        ];
        $user = $request->user();
        $page = $this->clients->paginate($filters)->withQueryString();
        $openProjects = $this->openProjects($page->items(), $user);

        return Inertia::render('admin/clients/index', [
            'clients' => $page->through(fn (Client $client) => $this->row($client, $user, $openProjects)),
            'filters' => $filters,
            'countries' => Paises::opcoes(),
            'can' => ['create' => $user->can('create', Client::class)],
        ]);
    }

    public function show(Request $request, Client $client): Response
    {
        $this->authorize('view', $client);

        $user = $request->user();
        $client->load('deactivatedBy:id,name');

        return Inertia::render('admin/clients/show', [
            'client' => $this->row($client, $user, $this->openProjects([$client], $user)),
            'contacts' => $user->can('viewAny', [Contact::class, $client]) ? $this->contacts($client, $user) : null,
            // Cliente desativado não ganha contato (N11): o botão some em vez de dar erro.
            'can_create_contact' => $client->isActive() && $user->can('create', [Contact::class, $client]),
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $client = $this->clients->create($request->validated(), $request->user());

        return back()->with('success', "Cliente {$client->name} criado.");
    }

    public function update(StoreClientRequest $request, Client $client): RedirectResponse
    {
        return $this->attempt(fn () => $this->clients->update($client, $request->validated(), $request->user()), 'Cliente atualizado.');
    }

    public function deactivate(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('deactivate', $client);

        return $this->attempt(fn () => $this->clients->deactivate($client, $request->user()), "Cliente {$client->name} desativado.");
    }

    public function reactivate(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('reactivate', $client);

        return $this->attempt(fn () => $this->clients->reactivate($client, $request->user()), "Cliente {$client->name} reativado.");
    }

    // SPEC_DEVIATION: Clientes AC 12 pede 403; segue o padrão de UserController::destroy (toast
    // de erro com a mesma mensagem). Reason: um jeito só nos três cadastros; decisão para o Lucas.
    public function destroy(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        return $this->attempt(fn () => $this->clients->delete($client, $request->user()), 'Cliente excluído.');
    }

    /** Recusa de regra (já usado, WCJ) vira toast de erro. */
    private function attempt(Closure $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $success);
    }

    /**
     * Para a confirmação de desativar (F1 §04): só quem pode desativar precisa do número, e a
     * página inteira vai numa chamada ao contrato (A7 da review de 07/10).
     *
     * @param  array<int, Client>  $clients
     * @return array<int, int> id do cliente → projetos abertos
     */
    private function openProjects(array $clients, User $user): array
    {
        $ids = array_values(array_map(
            fn (Client $client) => $client->id,
            array_filter($clients, fn (Client $client) => $this->canDeactivate($client, $user)),
        ));

        return $ids === [] ? [] : $this->management->openProjectsCountByClient($ids);
    }

    private function canDeactivate(Client $client, User $user): bool
    {
        return $client->isActive() && $user->can('deactivate', $client);
    }

    /**
     * @param  array<int, int>  $openProjects
     * @return array<string, mixed>
     */
    private function row(Client $client, User $user, array $openProjects): array
    {
        $canDeactivate = $this->canDeactivate($client, $user);

        return [
            ...$client->only(['id', 'name', 'country', 'document']),
            'country_name' => Paises::nome($client->country),
            'active' => $client->isActive(),
            'deactivated_at' => LocalTime::format($client->deactivated_at),
            'deactivated_by' => $client->deactivatedBy?->name,
            'open_projects' => $canDeactivate ? ($openProjects[$client->id] ?? 0) : null,
            'can' => [
                'update' => $user->can('update', $client),
                'deactivate' => $canDeactivate,
                'reactivate' => ! $client->isActive() && $user->can('reactivate', $client),
                'delete' => $user->can('delete', $client),
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function contacts(Client $client, User $user): array
    {
        return $client->contacts()
            ->withAnonymized()
            ->with(['client:id,name', 'deactivatedBy:id,name'])
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(fn (Contact $contact) => $this->contactService->row($contact, $user))
            ->all();
    }
}
