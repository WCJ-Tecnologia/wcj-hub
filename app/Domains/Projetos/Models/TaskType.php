<?php

namespace App\Domains\Projetos\Models;

use App\Platform\Models\Concerns\Deactivatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Cadastrável, lista geral (P18, G7; docs/banco.md, task_types). O código depende só dos slugs
 * 'improvement' (padrão do card rápido, P17, R4) e 'support' (card de suporte, P52).
 */
class TaskType extends Model
{
    use Deactivatable;

    public const IMPROVEMENT = 'improvement';

    public const SUPPORT = 'support';

    protected $fillable = ['slug', 'label'];

    public function hasHistory(): bool
    {
        return $this->isReferenced();
    }
}
