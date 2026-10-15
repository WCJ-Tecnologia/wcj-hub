<x-mail-layout :app-name="$appName" :brand-name="$brandName">
    <p style="margin:0;font-size:15px;line-height:1.65;color:#3B4253;">
        {{ $text }}
    </p>
    <p style="margin:28px 0 0;">
        <a href="{{ $url }}" style="display:inline-block;background:#1A6CF3;color:#FFFFFF;text-decoration:none;font-weight:700;font-size:14px;padding:14px 22px;border-radius:5px;">
            Abrir o card
        </a>
    </p>
</x-mail-layout>
