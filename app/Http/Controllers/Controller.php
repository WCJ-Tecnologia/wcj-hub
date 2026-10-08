<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AuditsDeniedAbilities;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller
{
    use AuditsDeniedAbilities;
    use AuthorizesRequests;
    use ValidatesRequests;

    /**
     * O authorize() do Laravel, que antes do 403 grava a tentativa como denied (G3).
     *
     * @param  mixed  $ability
     * @param  mixed  $arguments
     */
    public function authorize($ability, $arguments = []): Response
    {
        [$ability, $arguments] = $this->parseAbilityAndArguments($ability, $arguments);

        return $this->inspectAndAuditDenial($ability, $arguments)->authorize();
    }
}
