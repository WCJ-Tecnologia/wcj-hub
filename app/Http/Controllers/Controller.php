<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AuditsDeniedAbilities;
use Closure;
use DomainException;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\RedirectResponse;

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

    /**
     * Recusa de regra do Service (DomainException: já usado, último administrador, WCJ,
     * anonimizado) vira toast de erro; o resto, o toast de sucesso. Quando o sucesso depende do
     * que a ação devolve (o aviso do contato, N8), $success é a closure que monta a resposta.
     *
     * @param  string|Closure(mixed): RedirectResponse  $success
     */
    protected function attempt(Closure $action, string|Closure $success): RedirectResponse
    {
        try {
            $result = $action();
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return $success instanceof Closure ? $success($result) : back()->with('success', $success);
    }
}
