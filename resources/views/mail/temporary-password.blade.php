<x-mail-layout :app-name="$appName" :brand-name="$brandName">
    <p style="margin:0;font-family:'Space Grotesk','Hanken Grotesk',Arial,sans-serif;font-size:22px;line-height:1.3;font-weight:700;letter-spacing:-.02em;">
        Oi, {{ $firstName }}.
    </p>
    <p style="margin:16px 0 0;font-size:15px;line-height:1.65;color:#3B4253;">
        @if ($isReset)
            Geramos uma senha temporária para você. Entra com ela e, na hora, escolhe uma que seja só sua.
        @else
            Seu acesso ao {{ $appName }} está pronto. A senha abaixo é só para o primeiro login —
            depois a gente te pede para trocar, combinado?
        @endif
    </p>
    <p style="margin:24px 0 8px;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#5B6273;font-family:'Space Mono',Consolas,monospace;">
        Senha temporária
    </p>
    <p style="margin:0;font-family:'Space Mono',ui-monospace,SFMono-Regular,Menlo,monospace;font-size:22px;letter-spacing:.18em;font-weight:700;color:#1A6CF3;">
        {{ $temporaryPassword }}
    </p>
    <p style="margin:28px 0 0;">
        <a href="{{ $loginUrl }}" style="display:inline-block;background:#1A6CF3;color:#FFFFFF;text-decoration:none;font-weight:700;font-size:14px;padding:14px 22px;border-radius:5px;">
            Entrar agora
        </a>
    </p>
</x-mail-layout>
