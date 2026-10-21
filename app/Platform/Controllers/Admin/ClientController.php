<?php

namespace App\Platform\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use App\Platform\Requests\Admin\StoreClientRequest;
use App\Platform\Services\ClientPresenter;
use App\Platform\Services\ClientService;
use App\Platform\Services\ContactPresenter;
use App\Support\Busca;
use App\Support\Paises;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Clientes (F1; N5 a N7, N11). A regra mora no ClientService; o gestor, na ClientPolicy. */
class ClientController extends Controller
{
    public function __construct(
        private readonly ClientService $clients,
        private readonly ClientPresenter $presenter,
        private readonly ContactPresenter $contactPresenter,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Client::class);

        $country = strtoupper((string) $request->string('country'));
        $filters = [
            'search' => Busca::termo($request),
            'country' => in_array($country, Paises::codigos(), true) ? $country : null,
            'show_inactive' => $request->boolean('show_inactive'),
        ];
        $user = $request->user();
        $page = $this->clients->paginate($filters)->withQueryString();
        $openProjects = $this->presenter->openProjects($page->items(), $user);

        return Inertia::render('admin/clients/index', [
            'clients' => $page->through(fn (Client $client) => $this->presenter->row($client, $user, $openProjects)),
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
            'client' => $this->presenter->row($client, $user, $this->presenter->openProjects([$client], $user)),
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

    // Já usado (G2): toast de erro com a mensagem e nada muda, não 403 (D6 do Lucas, 07/10).
    public function destroy(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        return $this->attempt(fn () => $this->clients->delete($client, $request->user()), 'Cliente excluído.');
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
            ->map(fn (Contact $contact) => $this->contactPresenter->row($contact, $user))
            ->all();
    }
}
