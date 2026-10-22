<?php

namespace Tests\Arch\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Ações de mentira para provar que o detector do A10 pega validação fora de FormRequest.
 * Não são rota de nada.
 */
final class AcoesDeExemploA10
{
    public function validaNoRequest(Request $request): void
    {
        $request->validate(['name' => ['required']]);
    }

    public function validaComValidator(Request $request): void
    {
        // Sem ->validate(): só o Validator::make( pode denunciar esta ação.
        if (Validator::make($request->all(), ['name' => ['required']])->fails()) {
            abort(422);
        }
    }

    public function usaOValidado(Request $request): void
    {
        // $request->validate([...]) num comentário não conta
        $request->string('name');
    }
}
