<?php

namespace App\Platform\Requests\Concerns;

use App\Http\Concerns\AuditsDeniedAbilities;
use Illuminate\Auth\Access\Response;

/**
 * O Laravel valida o FormRequest antes de o controller rodar. Autorizando aqui, quem não pode
 * recebe 403 em vez do erro de validação (que revelaria, por exemplo, "e-mail já em uso") e a
 * tentativa vira denied mesmo com payload inválido (A1 da review de 07/10; G3). O controller não
 * autoriza de novo: depois de uma negação aqui ele nem roda, então a checagem seria só repetida.
 */
trait AuthorizesBeforeValidation
{
    use AuditsDeniedAbilities;

    public function authorize(): Response
    {
        [$ability, $arguments] = $this->abilityAndArguments();

        return $this->inspectAndAuditDenial($ability, $arguments);
    }

    /**
     * A ability da Policy e o alvo, lidos da rota (o registro já veio pelo route binding).
     *
     * @return array{0: string, 1: mixed}
     */
    abstract protected function abilityAndArguments(): array;
}
