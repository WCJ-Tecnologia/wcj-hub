<x-mail-layout :app-name="$appName" :brand-name="$brandName">
    <p style="margin:0;font-size:22px;line-height:1.3;font-weight:800;">
        Oi, {{ $firstName }}.
    </p>
    <p style="margin:16px 0 0;font-size:15px;line-height:1.65;color:#374151;">
        Acontece. A gente te manda um link para criar uma senha nova — sem perguntas, sem constrangimento.
        Ele vale por uma hora.
    </p>
    <p style="margin:28px 0 0;">
        <a href="{{ $resetUrl }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;padding:14px 22px;border-radius:12px;">
            Escolher minha senha
        </a>
    </p>
    <p style="margin:20px 0 0;font-size:12px;line-height:1.6;color:#6b7280;word-break:break-all;">
        Se o botão não abrir: {{ $resetUrl }}
    </p>
</x-mail-layout>
