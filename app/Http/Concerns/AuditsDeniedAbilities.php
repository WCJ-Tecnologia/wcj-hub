<?php

namespace App\Http\Concerns;

use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * O authorize() do Controller base e o dos FormRequests que validam payload (A1 da review de
 * 07/10) passam por aqui; a checagem e a gravação do denied (G3) moram no
 * AuditService::inspectFor(), o mesmo que os choiceList() dos Services usam (CR-1-05).
 */
trait AuditsDeniedAbilities
{
    protected function inspectAndAuditDenial(string $ability, mixed $arguments): Response
    {
        $actor = Auth::user();

        // Sem sessão não há de quem gravar a tentativa: as rotas que autorizam estão atrás do auth.
        return $actor instanceof User
            ? app(AuditService::class)->inspectFor($actor, $ability, $arguments)
            : Gate::inspect($ability, $arguments);
    }
}
