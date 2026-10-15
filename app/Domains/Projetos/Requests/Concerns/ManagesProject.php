<?php

namespace App\Domains\Projetos\Requests\Concerns;

use App\Domains\Projetos\Models\Project;
use App\Platform\Requests\Concerns\AuthorizesBeforeValidation;

/**
 * As escritas da configuração do projeto (F2 §09) passam todas pela ability `manage` da
 * ProjectPolicy, conferida (e a negação auditada) antes da validação, como no
 * StoreProjectRequest. É trait, não classe base: o teste A5 (RotasAutorizadasTest) exige o
 * authorize() declarado na própria classe do Request, e o método de um trait conta como dela.
 */
trait ManagesProject
{
    use AuthorizesBeforeValidation;

    protected function abilityAndArguments(): array
    {
        return ['manage', $this->route('project')];
    }

    protected function project(): Project
    {
        $project = $this->route('project');
        assert($project instanceof Project);

        return $project;
    }
}
