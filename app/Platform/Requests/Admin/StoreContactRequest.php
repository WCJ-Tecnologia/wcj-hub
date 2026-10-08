<?php

namespace App\Platform\Requests\Admin;

use App\Platform\Models\Contact;
use App\Platform\Requests\Concerns\AuthorizesBeforeValidation;
use App\Platform\Requests\Concerns\NormalizesMaskedInput;
use App\Platform\Services\ContactService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Criar e editar contato (F1 §02; N8). As regras são as do ContactService, como no cliente: um
 * lugar só para a regra. O telefone chega com a máscara da tela e é gravado só com os dígitos.
 */
class StoreContactRequest extends FormRequest
{
    use AuthorizesBeforeValidation, NormalizesMaskedInput;

    /** Criar no cliente da rota (clients/{client}/contacts) ou editar (contacts/{contact}). */
    protected function abilityAndArguments(): array
    {
        $contact = $this->route('contact');

        return $contact === null ? ['create', [Contact::class, $this->route('client')]] : ['update', $contact];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDigits(['phone']);
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
