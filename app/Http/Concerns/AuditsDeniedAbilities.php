<?php

namespace App\Http\Concerns;

use App\Platform\Services\AuditService;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

/**
 * A checagem que barra a ação e grava a tentativa como denied (G3), num lugar só: o authorize()
 * do Controller base e o dos FormRequests que validam payload (A1 da review de 07/10) passam por
 * aqui. Só a checagem que barra é tentativa: o can() que monta a tela não entra na auditoria.
 */
trait AuditsDeniedAbilities
{
    protected function inspectAndAuditDenial(string $ability, mixed $arguments): Response
    {
        $response = Gate::inspect($ability, $arguments);

        if ($response->denied()) {
            app(AuditService::class)->denied($ability, $this->deniedTarget($ability, $arguments));
        }

        return $response;
    }

    /** O registro ou a classe que a Policy recebeu; gate sem Model fica pelo próprio nome. */
    private function deniedTarget(string $ability, mixed $arguments): Model|string
    {
        $target = Arr::wrap($arguments)[0] ?? null;

        return $target instanceof Model || (is_string($target) && is_subclass_of($target, Model::class))
            ? $target
            : $ability;
    }
}
