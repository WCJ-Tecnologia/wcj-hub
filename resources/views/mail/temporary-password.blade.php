<x-mail-layout :app-name="$appName" :brand-name="$brandName">
    <p style="margin:0;font-size:22px;line-height:1.3;font-weight:800;">
        Oi, {{ $firstName }}.
    </p>
    <p style="margin:16px 0 0;font-size:15px;line-height:1.65;color:#374151;">
        Seu acesso ao {{ $appName }} está pronto. A senha abaixo é só para o primeiro login —
        depois a gente te pede para trocar, combinado?
    </p>
    <p style="margin:24px 0 8px;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#6b7280;font-weight:700;">
        Login
    </p>
    <p style="margin:0;font-size:16px;font-weight:700;color:#111827;word-break:break-all;">
        {{ $login }}
    </p>
    <p style="margin:24px 0 8px;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#6b7280;font-weight:700;">
        Senha temporária
    </p>
    <p style="margin:0;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:22px;letter-spacing:.18em;font-weight:800;color:#2563eb;">
        {{ $temporaryPassword }}
    </p>
    <p style="margin:28px 0 0;">
        <a href="{{ $loginUrl }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;padding:14px 22px;border-radius:12px;">
            Entrar agora
        </a>
    </p>
</x-mail-layout>
