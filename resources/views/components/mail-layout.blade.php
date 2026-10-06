@props([
    'appName' => config('app.name'),
    'brandName' => config('app.brand.name'),
    'preheader' => null,
    'footerNote' => 'Se você não pediu isso, pode ignorar este e-mail. Nada muda na sua conta.',
])

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light only">
    <title>{{ $appName }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,Segoe UI,Helvetica,Arial,sans-serif;color:#111827;">
    @if ($preheader)
        <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
            {{ $preheader }}
        </div>
    @endif
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;border-collapse:separate;">
                    <tr>
                        <td style="background:#111827;border-radius:16px 16px 0 0;padding:24px 32px;">
                            <p style="margin:0;font-size:18px;font-weight:800;color:#ffffff;letter-spacing:.02em;">
                                {{ $brandName }}
                            </p>
                            @if ($brandName !== $appName)
                                <p style="margin:8px 0 0;font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:#9ca3af;font-weight:700;">
                                    {{ $appName }}
                                </p>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#ffffff;padding:28px 32px 8px;">
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#111827;border-radius:0 0 16px 16px;padding:20px 32px;">
                            <p style="margin:0;font-size:12px;line-height:1.65;color:#d1d5db;">
                                {{ $footerNote }}
                            </p>
                            <p style="margin:12px 0 0;font-size:11px;color:#9ca3af;">
                                {{ $brandName }} · {{ $appName }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
