<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Autorizar assistente · {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light dark; --fundo: #f5f6f8; --cartao: #fff; --texto: #0c0e13; --suave: #5b6270; --borda: #dfe2e8; --azul: #1a6cf3; --erro: #c62828; }
        @media (prefers-color-scheme: dark) { :root { --fundo: #0c0e13; --cartao: #151922; --texto: #eef0f4; --suave: #9aa3b2; --borda: #262c38; } }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: var(--fundo); color: var(--texto); font: 16px/1.5 system-ui, sans-serif; padding: 16px; box-sizing: border-box; }
        .cartao { width: 100%; max-width: 440px; background: var(--cartao); border: 1px solid var(--borda); border-radius: 12px; padding: 28px; box-sizing: border-box; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        p, li { color: var(--suave); }
        .caixa { border: 1px solid var(--borda); border-radius: 8px; padding: 12px 14px; margin: 16px 0; }
        .rotulo { font-size: 13px; color: var(--suave); }
        .valor { color: var(--texto); font-weight: 600; }
        .erro { color: var(--erro); font-weight: 600; }
        .botoes { display: flex; gap: 12px; margin-top: 20px; }
        .botoes form { flex: 1; }
        button { width: 100%; height: 44px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; border: 1px solid var(--borda); background: transparent; color: var(--texto); }
        button.principal { background: var(--azul); border-color: var(--azul); color: #fff; }
        code { font-size: 13px; }
    </style>
</head>
<body>
<main class="cartao">
    <h1>Autorizar {{ $assistente?->label ?? $client->name }}</h1>
    <p>O assistente vai agir no WCJ Hub como você, só com o que está abaixo. Prova de conceito do MCP.</p>

    <div class="caixa">
        <div class="rotulo">Você</div>
        <div class="valor">{{ $user->name }} · {{ $user->email }}</div>
        <div class="rotulo" style="margin-top:8px">Cliente que pediu</div>
        <div class="valor">{{ $client->name }}</div>
        <div class="rotulo">volta para <code>{{ $redirectHost }}</code></div>
    </div>

    @if ($assistente === null)
        <p class="erro">Assistente não homologado. O Hub aceita Claude, Codex e ChatGPT; este cliente não se identificou como nenhum deles.</p>
    @else
        <div class="caixa">
            <div class="rotulo">A conexão vai poder</div>
            <ul>
                @foreach ($escopos as $escopo)
                    <li><span class="valor">{{ $escopo['descricao'] }}</span> <code>{{ $escopo['id'] }}</code></li>
                @endforeach
            </ul>
            @if (count($escopos) === 1)
                <div class="rotulo">Só leitura: {{ $assistente->write_allowed ? 'seu papel ou o seu cadastro não permite escrita pela IA' : 'o '.$assistente->label.' só lê no Hub' }}.</div>
            @endif
            @if ($pedidos !== [])
                <div class="rotulo">O cliente pediu: <code>{{ implode(' ', $pedidos) }}</code>. Quem decide é o Hub.</div>
            @endif
        </div>
        @if ($anterior)
            <p>Isto substitui a conexão do {{ $assistente->label }} feita em {{ $anterior->created_at->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }}.</p>
        @endif
    @endif

    <div class="botoes">
        <form method="POST" action="{{ route('passport.authorizations.deny') }}">
            @csrf
            @method('DELETE')
            <input type="hidden" name="state" value="{{ $state }}">
            <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button type="submit">Recusar</button>
        </form>
        @if ($assistente !== null)
            <form method="POST" action="{{ route('passport.authorizations.approve') }}">
                @csrf
                <input type="hidden" name="state" value="{{ $state }}">
                <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="principal">Autorizar</button>
            </form>
        @endif
    </div>
</main>
</body>
</html>
