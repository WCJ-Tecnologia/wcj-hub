<?php

namespace App\Domains\Projetos\Models;

use App\Platform\Models\Concerns\Deactivatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tipo do card, DO PROJETO (P18; docs/banco.md, task_types e seção 7, 14/10): todo projeto nasce
 * com os 5 padrão (TaskTypeService::createDefaults) e cria ou edita os seus pela etiqueta do
 * quadro, sem propagar. O código acha 'improvement', 'support' e 'development' pelo slug, dentro
 * do projeto. Cor: um dos 10 nomes de TagColor (kit do quadro); nulo = cinza.
 */
class TaskType extends Model
{
    use Deactivatable;

    public const IMPROVEMENT = 'improvement';

    public const SUPPORT = 'support';

    public const DEVELOPMENT = 'development';

    protected $fillable = ['project_id', 'slug', 'label', 'color'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function hasHistory(): bool
    {
        return $this->isReferenced();
    }
}
