<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Requests\Concerns\ActsOnCard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * Anexar ao card (P24; F3 §04): até `hub.attachments.max_files` arquivos por envio, cada um até
 * `hub.attachments.max_kb`, nos tipos de `hub.attachments.mimes`, conferidos pelos bytes
 * (`mimes` lê o conteúdo; seguranca.md §7). Com `criterion_id`, é a evidência do QA no item (B4;
 * F4 §09: a mesma ability `attach`); o AttachmentService confere que o critério é deste card.
 */
class StoreAttachmentsRequest extends FormRequest
{
    use ActsOnCard;

    protected function abilityAndArguments(): array
    {
        return ['attach', $this->card()];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:'.config('hub.attachments.max_files')],
            'files.*' => ['file', 'max:'.config('hub.attachments.max_kb'), 'mimes:'.config('hub.attachments.mimes')],
            'criterion_id' => ['nullable', 'integer'],
        ];
    }

    /** O item do roteiro da evidência, ou null para o anexo comum. */
    public function criterionId(): ?int
    {
        $id = $this->validated('criterion_id');

        return $id === null ? null : (int) $id;
    }

    /** @return list<UploadedFile> */
    public function uploads(): array
    {
        return array_values($this->file('files', []));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $mb = intdiv((int) config('hub.attachments.max_kb'), 1024);

        return [
            'files.max' => 'Envie até '.config('hub.attachments.max_files').' arquivos por vez.',
            'files.*.max' => "Cada arquivo pode ter até {$mb} MB.",
            'files.*.mimes' => 'Tipo de arquivo não aceito: imagem, PDF, Office, texto, CSV ou zip.',
        ];
    }
}
