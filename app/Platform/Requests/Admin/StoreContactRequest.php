<?php

namespace App\Platform\Requests\Admin;

use App\Platform\Models\Contact;
use App\Platform\Requests\Concerns\AuthorizesBeforeValidation;
use App\Platform\Services\ContactService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Criar e editar contato (F1 §02; N8). As regras são as do ContactService, como no cliente: um
 * lugar só para a regra. O telefone é texto, gravado como a pessoa digitou (D3 do Lucas, 07/10).
 */
class StoreContactRequest extends FormRequest
{
    use AuthorizesBeforeValidation;

    /** Criar no cliente da rota (clients/{client}/contacts) ou editar (contacts/{contact}). */
    protected function abilityAndArguments(): array
    {
        $contact = $this->route('contact');

        return $contact === null ? ['create', [Contact::class, $this->route('client')]] : ['update', $contact];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ContactService::rules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ContactService::attributes();
    }
}
