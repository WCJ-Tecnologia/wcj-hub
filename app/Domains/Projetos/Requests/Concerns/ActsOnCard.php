<?php

namespace App\Domains\Projetos\Requests\Concerns;

use App\Domains\Projetos\Models\Task;
use App\Platform\Requests\Concerns\AuthorizesBeforeValidation;

/**
 * As escritas num card do quadro (B3) autorizam na TaskPolicy, com o card da rota, antes da
 * validação (mesmo caminho auditado do AuthorizesBeforeValidation). Trait, não classe base: o
 * teste A5 exige o authorize() na própria classe do Request.
 */
trait ActsOnCard
{
    use AuthorizesBeforeValidation;

    protected function card(): Task
    {
        $card = $this->route('card');
        assert($card instanceof Task);

        return $card;
    }
}
