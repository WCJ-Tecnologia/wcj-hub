@props([
    'appName' => config('app.name'),
    'brandName' => config('app.brand.name'),
    'preheader' => null,
    'footerNote' => 'Se você não pediu isso, pode ignorar este e-mail. Nada muda na sua conta.',
])

{{--
    Cores da marca WCJ (docs/brand/cores.json) em hex inline: o cliente de e-mail não lê CSS
    externo nem variável. É a única exceção à regra de "só token" (ADR-005). Ink #0C0E13,
    fundo #F5F7FB, texto suave #5B6273, rodapé #AEB6C6 sobre o ink. Logo: o ícone PNG (SVG
    é bloqueado no Gmail e no Outlook) com o nome em texto.
--}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light only">
    <title>{{ $appName }}</title>
</head>
<body style="margin:0;padding:0;background:#F5F7FB;font-family:'Hanken Grotesk',Arial,Helvetica,sans-serif;color:#0C0E13;">
    @if ($preheader)
        <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
            {{ $preheader }}
        </div>
    @endif
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#F5F7FB;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;border-collapse:separate;">
                    <tr>
                        <td style="background:#0C0E13;border-radius:10px 10px 0 0;padding:24px 32px;">
                            <table role="presentation" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="padding-right:12px;vertical-align:middle;">
                                        <img src="{{ asset('brand/wcj-hub-icone-64.png') }}" width="32" height="32" alt="" style="display:block;border:0;">
                                    </td>
                                    <td style="vertical-align:middle;">
                                        <p style="margin:0;font-family:'Space Grotesk','Hanken Grotesk',Arial,sans-serif;font-size:18px;font-weight:700;color:#FFFFFF;letter-spacing:-.01em;">
                                            {{ $brandName }}
                                        </p>
                                        @if ($brandName !== $appName)
                                            <p style="margin:4px 0 0;font-family:'Space Mono',Consolas,monospace;font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:#4D90FF;">
                                                {{ $appName }}
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#FFFFFF;padding:28px 32px 8px;">
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#0C0E13;border-radius:0 0 10px 10px;padding:20px 32px;">
                            <p style="margin:0;font-size:12px;line-height:1.65;color:#AEB6C6;">
                                {{ $footerNote }}
                            </p>
                            <p style="margin:12px 0 0;font-family:'Space Mono',Consolas,monospace;font-size:11px;color:#AEB6C6;">
                                {{ $brandName }}@if ($brandName !== $appName) · {{ $appName }}@endif
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
