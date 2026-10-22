# Nota de segurança para o B8 (OAuth e MCP)

De: B9 (Lucas Scholz com IA, 21/10/2026). Para: quem abrir o B8 (Cauã, pela ordem do plano). Requisitos SEC-19 e SEC-20 da [spec do B9](spec.md).

O B9 não escreveu código de OAuth nem de MCP: as rotas `/oauth/*` estão desligadas (`Passport::ignoreRoutes`, `app/Providers/AppServiceProvider.php`) e o `laravel/mcp` não está instalado. O que segue é regra para a spec do B8, que é trilho completo. Vale junto com o `.ai/core/mcp.md` inteiro e com o `docs/poc-mcp/README.md` da branch `poc/mcp`.

## 1. Limitador do `/oauth/token`

- Limitador nomeado `oauth-token` (`RateLimiter::for` no `AppServiceProvider`, ao lado do `login` e do `senha-atual`), com duas chaves, como o do login (`seguranca.md` §3):
  - IP + `client_id` do corpo: 5 por minuto (`config('auth.throttle.per_account')`);
  - teto por IP: 20 por minuto (`config('auth.throttle.per_ip')`).
- Resposta 429 em JSON no formato de erro do OAuth (`{"error": "slow_down"}` ou `"too_many_requests"`, com `Retry-After`), nunca a tela Inertia do login.
- Prova dinâmica, não leitura de config: teste que manda a 6ª troca de código no mesmo minuto e recebe 429 (o mesmo molde do `tests/Feature/LoginThrottleTest.php`, com `freezeTime()`).
- Se o `/oauth/authorize` passar pelo login do Hub, o limitador do login já cobre a senha; a tela de aprovar não precisa de outro.
- O `TRUSTED_PROXIES` do `docs/DEPLOY.md` vale aqui também: sem ele, o teto por IP vira um teto para todo mundo.

## 2. Limite do `/mcp`

- Por autorização (`ai_authorizations.id`), nunca por IP: o assistente chama da nuvem dele (`mcp.md` §7).
- 60 leituras e 20 escritas por minuto, de `config('hub.ai.reads_per_minute')` e `config('hub.ai.writes_per_minute')` (T5, já no `config/hub.php` e no `.env.example`).
- Ao estourar, erro legível para o modelo ("limite de chamadas atingido, tente em N segundos"), sem derrubar a sessão do usuário na tela.
- Teste estourando de verdade, uma vez para leitura e uma para escrita (`mcp.md` §12, pergunta 8).

## 3. Headers e erro

- O `SecurityHeaders` é global desde o B9 (`bootstrap/app.php`): `/oauth/*`, `/.well-known/*` e `/mcp` já saem com CSP, `nosniff`, HSTS em produção e sem `X-Powered-By`. Não registrar outro middleware de header nem tirar estas rotas dele. Se a tela de autorização precisar de algo fora da CSP atual, é ADR (`SecurityHeaders.php`).
- Erro do `/mcp` e do `/oauth/token` sem stack, classe nem caminho (`mcp.md` §4), também com `APP_DEBUG=false`: o `tests/Feature/ErroSemStackTest.php` é o molde.
- O Nginx do `docs/DEPLOY.md` deixa passar `/.well-known/` (os metadados do OAuth e do recurso, RFC 9728) e bloqueia o resto que começa com ponto. Não afrouxar o bloqueio.

## 4. Teste de arquitetura

- **A5** (`tests/Arch/RotasAutorizadasTest.php`): as rotas do Passport são de pacote (`Laravel\Passport\…`) e o A5 as ignora; as rotas próprias do B8 (`/perfil/conexoes`, `/admin/conexoes`, o controller de aprovar, o `/mcp`) são `App\` e precisam autorizar. Rota pública nova do B8 (metadados em `/.well-known/…`, se for `App\`) entra em `ROTAS_PUBLICAS`, uma a uma, com o motivo. Nunca uma exceção por prefixo.
- **A10** (mesmo arquivo): escrita valida em `FormRequest`. Vale para o aprovar e o revogar da tela; a ferramenta MCP valida pelo schema da própria ferramenta, com regras explícitas (`mcp.md` §4).
- O `/mcp` exige token (`auth:api`); no A5, o middleware de login do token entra em `MIDDLEWARE_DE_LOGIN` se não for subclasse do `Authenticate`.

## 5. As nove perguntas do `mcp.md` §12

A spec do B8 responde cada uma por escrito, ferramenta por ferramenta, antes de publicar o servidor (revisão do mesmo tipo da `revisao-seguranca.md` do B9):

1. Toda ferramenta passa pela Policy da tela e pela mesma camada de serviço?
2. Existe alguma ferramenta genérica (consulta livre, atualizar qualquer registro, lote)?
3. O escopo do token é derivado do papel, no servidor, e o cliente que só lê recebe token só de leitura?
4. O servidor recusa token emitido para outro recurso e nunca repassa o token recebido?
5. Desativar usuário ou mudar papel revoga os tokens na hora? O usuário consegue revogar sozinho?
6. Toda escrita e toda tentativa negada aparecem na auditoria com canal e autorização?
7. Texto de cliente volta delimitado como dado externo, em todas as ferramentas que o devolvem?
8. Os limites por token estão ligados e foram testados estourando de verdade?
9. A superfície pública passou pela revisão de `seguranca.md`, seção 11?

E os oito cenários de aceite do `mcp.md` §11 entram na spec do B8 em EARS.
