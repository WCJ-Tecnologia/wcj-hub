<?php

namespace App\Platform\Requests\Admin;

use App\Platform\Requests\Concerns\AuthorizesBeforeValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Anonimizar é irreversível (N12): a pessoa digita a palavra, exatamente, para confirmar (F1 §04).
 */
class AnonymizeContactRequest extends FormRequest
{
    use AuthorizesBeforeValidation;

    public const WORD = 'ANONIMIZAR';

    public const MISMATCH = 'Digite ANONIMIZAR para confirmar.';

    protected function abilityAndArguments(): array
    {
        return ['anonymize', $this->route('contact')];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['confirmation' => ['required', 'string', Rule::in([self::WORD])]];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['confirmation.required' => self::MISMATCH, 'confirmation.in' => self::MISMATCH];
    }
}
