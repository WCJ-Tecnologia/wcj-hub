<?php

namespace App\Platform\Requests\Admin;

use App\Platform\Enums\UserTypeEnum;
use App\Platform\Requests\Concerns\NormalizesMaskedInput;
use App\Rules\Document;
use App\Rules\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    use NormalizesMaskedInput;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDigits(['document', 'phone']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('user')?->id;

        return [
            'user_type_id' => ['required', 'integer', Rule::in(UserTypeEnum::values())],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.($id ?? 'NULL')],
            'document' => ['nullable', 'string', new Document],
            'phone' => ['nullable', 'string', new PhoneNumber],
            'active' => ['boolean'],
            'enterprise_ids' => ['array'],
            'enterprise_ids.*' => ['integer', 'exists:enterprises,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = (int) $this->input('user_type_id');

            if ($type === UserTypeEnum::FINANCIAL->value && count((array) $this->input('enterprise_ids', [])) === 0) {
                $validator->errors()->add('enterprise_ids', 'Selecione ao menos uma empresa para o Financeiro.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'user_type_id' => 'perfil',
            'name' => 'nome',
            'email' => 'e-mail',
            'enterprise_ids' => 'empresas',
        ];
    }
}
