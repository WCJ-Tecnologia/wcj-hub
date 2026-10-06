{{ $brandName }} · {{ $appName }}

Oi, {{ $firstName }}.

@if ($isReset)
Geramos uma senha temporária para você. Entra com ela e, na hora, escolhe uma que seja só sua.
@else
Seu acesso ao {{ $appName }} está pronto. A senha abaixo é só para o primeiro login — depois você troca.
@endif

Senha temporária: {{ $temporaryPassword }}

Entrar: {{ $loginUrl }}

Se você não pediu isso, pode ignorar este e-mail. Nada muda na sua conta.
