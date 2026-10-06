<?php

namespace App\Http\Controllers;

use App\Platform\Services\AuditService;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

abstract class Controller
{
    use AuthorizesRequests;
    use ValidatesRequests;

    /**
     * O authorize() do Laravel, que antes do 403 grava a tentativa como denied (G3). Fica aqui,
     * e não num Gate::after, porque só a checagem que barra a ação é tentativa: o can() que
     * monta a tela não entra na auditoria.
     *
     * @param  mixed  $ability
     * @param  mixed  $arguments
     */
    public function authorize($ability, $arguments = []): Response
    {
        [$ability, $arguments] = $this->parseAbilityAndArguments($ability, $arguments);

        $response = Gate::inspect($ability, $arguments);

        if ($response->denied()) {
            app(AuditService::class)->denied($ability, $this->deniedTarget($ability, $arguments));
        }

        return $response->authorize();
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
