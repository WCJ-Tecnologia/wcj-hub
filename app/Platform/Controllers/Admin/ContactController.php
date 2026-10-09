<?php

namespace App\Platform\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Requests\Admin\AnonymizeContactRequest;
use App\Platform\Requests\Admin\StoreContactRequest;
use App\Platform\Services\ContactPresenter;
use App\Platform\Services\ContactService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Contatos (F1; N8, N11, N12). A regra mora no ContactService; quem pode, na ContactPolicy. */
class ContactController extends Controller
{
    public function __construct(
        private readonly ContactService $contacts,
        private readonly ContactPresenter $presenter,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Contact::class);

        $user = $request->user();
        $clients = $this->contacts->clientOptions($user);
        $clientId = $request->integer('client_id');
        $filters = [
            'search' => (string) $request->string('search'),
            // Só os clientes que a pessoa vê (F1 §09): outro id não filtra.
            'client_id' => in_array($clientId, array_column($clients, 'id'), true) ? $clientId : null,
            'show_inactive' => $request->boolean('show_inactive'),
        ];

        return Inertia::render('admin/contacts/index', [
            'contacts' => $this->contacts->paginate($filters, $user)->withQueryString()
                ->through(fn (Contact $contact) => $this->presenter->row($contact, $user)),
            'filters' => $filters,
            'clients' => $clients,
        ]);
    }

    public function store(StoreContactRequest $request, Client $client): RedirectResponse
    {
        ['contact' => $contact, 'warning' => $warning] = $this->contacts->create($client, $request->validated(), $request->user());

        return $this->withWarning(back()->with('success', "Contato {$contact->name} criado."), $warning);
    }

    public function update(StoreContactRequest $request, Contact $contact): RedirectResponse
    {
        // Anonimizado no meio (a Policy já tinha passado): o Service recusa e vira toast, não 500.
        return $this->attempt(
            fn () => $this->contacts->update($contact, $request->validated(), $request->user()),
            fn (?string $warning) => $this->withWarning(back()->with('success', 'Contato atualizado.'), $warning),
        );
    }

    public function deactivate(Request $request, Contact $contact): RedirectResponse
    {
        $this->authorize('deactivate', $contact);

        return $this->attempt(fn () => $this->contacts->deactivate($contact, $request->user()), "Contato {$contact->name} desativado.");
    }

    public function reactivate(Request $request, Contact $contact): RedirectResponse
    {
        $this->authorize('reactivate', $contact);

        return $this->attempt(fn () => $this->contacts->reactivate($contact, $request->user()), "Contato {$contact->name} reativado.");
    }

    // Já usado (G2): toast de erro com a mensagem e nada muda, não 403 (D6 do Lucas, 07/10).
    public function destroy(Request $request, Contact $contact): RedirectResponse
    {
        $this->authorize('delete', $contact);

        return $this->attempt(fn () => $this->contacts->delete($contact, $request->user()), 'Contato excluído.');
    }

    public function anonymize(AnonymizeContactRequest $request, Contact $contact): RedirectResponse
    {
        return $this->attempt(fn () => $this->contacts->anonymize($contact, $request->user()), 'Contato anonimizado.');
    }

    /** N8: o e-mail repetido não recusa; o aviso vai junto do sucesso, num toast próprio. */
    private function withWarning(RedirectResponse $response, ?string $warning): RedirectResponse
    {
        return $warning === null ? $response : $response->with('warning', $warning);
    }
}
