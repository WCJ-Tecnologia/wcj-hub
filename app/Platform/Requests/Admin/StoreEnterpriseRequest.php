<?php

namespace App\Platform\Requests\Admin;

use App\Platform\Requests\Concerns\NormalizesMaskedInput;
use App\Rules\Document;
use Illuminate\Foundation\Http\FormRequest;

class StoreEnterpriseRequest extends FormRequest
{
    use NormalizesMaskedInput;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDigits(['document']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('enterprise')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'nickname' => ['required', 'string', 'max:80'],
            'document' => ['nullable', 'string', new Document(Document::CNPJ), 'unique:enterprises,document,'.($id ?? 'NULL')],
            'parent_enterprise_id' => ['nullable', 'integer', 'exists:enterprises,id'],
            'active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'razão social',
            'nickname' => 'apelido',
            'document' => 'CNPJ',
        ];
    }
}
