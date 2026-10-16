<?php

namespace App\Domains\Projetos\Requests;

use App\Domains\Projetos\Models\TaskComment;
use App\Domains\Projetos\Requests\Concerns\ActsOnCard;
use Illuminate\Foundation\Http\FormRequest;

/**
 * As escritas de texto da ficha (F3 §04): critério (`description`, com `parent_id` no subitem),
 * link de PR (`url`) e comentário (`body`). A ability segue a rota: comentar é `comment`, editar
 * comentário é do autor (TaskCommentPolicy), o resto é editar o card (`update`).
 */
class CardTextRequest extends FormRequest
{
    use ActsOnCard;

    protected function abilityAndArguments(): array
    {
        // O filho da rota (critério, comentário) é do card da rota: de outro card é 404, antes da Policy.
        foreach (['acceptanceCriterion', 'comment'] as $child) {
            $model = $this->route($child);
            abort_if($model !== null && (int) $model->task_id !== $this->card()->id, 404);
        }

        $comment = $this->route('comment');
        if ($comment instanceof TaskComment) {
            return ['update', $comment];
        }

        return [$this->routeIs('projects.cards.comments.store') ? 'comment' : 'update', $this->card()];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return match (true) {
            $this->routeIs('projects.cards.criteria.*') => [
                'description' => ['required', 'string', 'max:5000'],
                'parent_id' => ['sometimes', 'nullable', 'integer'],
            ],
            $this->routeIs('projects.cards.prs.*') => ['url' => ['required', 'string', 'max:500', 'url:http,https']],
            default => ['body' => ['required', 'string', 'max:10000']],
        };
    }

    public function text(string $field): string
    {
        return trim($this->validated($field));
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['description' => 'descrição', 'url' => 'link do PR', 'body' => 'comentário'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['url.url' => 'Cole o link do PR, começando por http:// ou https://.'];
    }
}
