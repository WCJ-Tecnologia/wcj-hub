# Prova de conceito do servidor MCP (D-GER-17)

Branch `poc/mcp`, descartável, sem PR. Servidor MCP mínimo do WCJ Hub, publicado por túnel HTTPS, com OAuth 2.1 (Passport 13 + `laravel/mcp` 1.0.1), uma ferramenta de leitura (`ping`) e uma de escrita (`anotar`). Objetivo: responder as 4 perguntas da prova (`core/mcp.md`, seção 10; `necessidade.md`, "Em aberto"; F8, seção 10) antes do B8. O roteiro por assistente está em `roteiro.md`, escrito à parte.

Não entra: tela de conexões, revogação pela tela, as 6 ferramentas, limite por token (IA7). Tudo isso é B8.

## O que foi montado

| Peça | Onde |
|---|---|
| Rotas: metadados, registro dinâmico, `/mcp` | `routes/ai.php` (`Mcp::oauthRoutes()` + `Mcp::web('/mcp')` com `auth:api`) |
| Servidor e ferramentas | `app/Platform/Mcp/HubPocServer.php`, `Tools/Ping.php`, `Tools/Anotar.php` |
| Regras da conexão (assistente, escopos, conectar/revogar) | `app/Platform/Services/ConexaoIaService.php` |
| Ajustes no Passport e no `laravel/mcp` | `app/Platform/Mcp/Oauth/*`, registrados em `app/Providers/McpPocServiceProvider.php` |
| PKCE S256, `resource`, CIMD, `iss`, log do OAuth | `app/Http/Middleware/McpPoc/OauthDaProva.php` (em todas as rotas `/oauth/*`, via `config/passport.php`) |
| Log de toda chamada ao `/mcp` e conferência da conexão | `app/Http/Middleware/McpPoc/RegistraChamadaMcp.php`, `ConexaoDaIa.php` |
| Tela "Autorizar <assistente>" | `app/Platform/Mcp/Oauth/TelaDeAutorizacao.php` + `resources/views/mcp-poc/authorize.blade.php` |
| Parâmetros da prova | `config/mcp_poc.php` (`POC_MCP_*` no `.env`) |
| Testes | `tests/Feature/McpPocTest.php` (16, MySQL, fluxo OAuth completo) |
| Túnel | `poc/mcp/start.sh`, `poc/mcp/stop.sh` |
| Log da prova | `storage/logs/mcp.log` |

Regras aplicadas:

- **Escopos:** `poc:read` e `poc:write` (`Passport::tokensCan`). O servidor ignora o que o cliente pede e decide: leitura sempre; escrita só se o papel escreve (admin, gestor, dev), `users.ai_write_allowed` ligado e o assistente não é só leitura. ChatGPT (`ai_assistants.write_allowed = false`) recebe só `poc:read`. A decisão vale na troca do código e no refresh (`HubScopeRepository::finalizeScopes`).
- **Assistente:** detectado pelo `client_name` (registro dinâmico) ou pelo `client_name` do documento (CIMD), por palavra-chave: `chatgpt` → ChatGPT, `codex` → Codex, `claude` → Claude, nessa ordem (nome com "chatgpt" e "codex" fica com a regra mais restritiva). Sem palavra-chave: a tela mostra "Assistente não homologado" sem o botão Autorizar, e a troca do código devolve `access_denied`.
- **`ai_authorizations`:** uma linha por autorização concedida (troca do código). Reconectar o mesmo assistente revoga a conexão anterior e os tokens dela (IA6). O `/mcp` exige conexão ativa para o par usuário + cliente OAuth.
- **`anotar`:** até 200 caracteres; grava `audit_logs` com `action = poc_note`, `outcome = success`, `auditable` = a conexão, `ai_authorization_id` e `changes = {"nota": [null, texto]}`. Anotação MCP `readOnlyHint=false`, `destructiveHint=true`. Sem `poc:write`: "escopo insuficiente: esta conexão só lê (falta poc:write)" e nada grava.
- **`/mcp` sem token:** 401 JSON com `WWW-Authenticate: Bearer realm="mcp", resource_metadata="<URL>/.well-known/oauth-protected-resource/mcp"`, nunca 3xx. O metadado com caminho é o da RFC 9728 (seção 3.1) para o recurso `<URL>/mcp`; o da raiz também responde.
- **Token:** acesso 60 min, refresh 30 dias (rotativo). `aud = [client_id, <URL>/mcp]` e claim `client_id`; o `/mcp` recusa token sem o recurso no `aud`. A resposta do `/oauth/token` traz `scope` com o que foi concedido.

## Como subir

Pré-requisitos (já feitos neste Mac em 06/10): worktree em `WCJ/wcj-hub-poc`, `composer install`, `npm install && npm run build`, `.env` próprio, chaves do Passport (`storage/oauth-*.key`), `cloudflared` pelo Homebrew. O MySQL é o do `docker compose` do checkout principal (`docker compose up -d mysql` em `WCJ/wcj-hub`), banco `wcj_hub` (o mesmo de dev).

```bash
cd ~/Documents/Projetos/WCJ/wcj-hub-poc
./poc/mcp/start.sh                    # registro dinâmico
./poc/mcp/start.sh --cimd             # também anuncia o CIMD
./poc/mcp/start.sh --esconder-escrita # "anotar" some do tools/list da conexão só leitura
tail -f storage/logs/mcp.log          # em outro terminal, durante o teste
./poc/mcp/stop.sh
```

O `start.sh` sobe `cloudflared tunnel --url http://127.0.0.1:8010`, pega a URL `https://<nome>.trycloudflare.com`, grava no `.env` `APP_URL` = essa URL, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `TRUSTED_PROXIES=127.0.0.1` e as chaves `POC_MCP_*`, sobe `php artisan serve` na 8010 (4 workers), confere os metadados pela URL pública e imprime:

```
Servidor MCP:   https://<nome>.trycloudflare.com/mcp
Metadados:      https://<nome>.trycloudflare.com/.well-known/oauth-protected-resource/mcp
                https://<nome>.trycloudflare.com/.well-known/oauth-authorization-server
Registro:       https://<nome>.trycloudflare.com/oauth/register
Login do Hub:   https://<nome>.trycloudflare.com/admin/login
```

O `stop.sh` para túnel e servidor pelos PIDs gravados em `storage/logs/poc-mcp/` (e os workers do `php -S` na porta 8010, pelo PID) e devolve o `.env` para `http://localhost:8010`.

A URL muda a cada `start.sh`: conector criado num assistente com a URL antiga deixa de funcionar e precisa ser refeito.

Se o navegador disser que o endereço não existe logo depois do start, é o DNS do Mac com a resposta negativa em cache; espere um ou dois minutos.

## O que precisa estar pronto antes de testar

- Docker com o MySQL de pé.
- Um usuário seu, ativo, no banco de dev `wcj_hub`, com senha que você sabe e sem troca pendente. Se não houver: `php artisan app:create-admin <seu e-mail>` e troque a senha em `<URL>/admin/login`. Para testar escrita, o papel precisa ser Administrador, Gestor ou Desenvolvedor, com "IA pode escrever" ligado (`users.ai_write_allowed`, padrão ligado).
- O terminal com `tail -f storage/logs/mcp.log` aberto.
- Claude (app) no plano Pro ou superior, Claude Code, Codex CLI, Codex no app do ChatGPT e ChatGPT Pro com o modo desenvolvedor.

## As 4 perguntas: o que o servidor deixa observar

Tudo em `storage/logs/mcp.log`, um evento por linha.

| Evento | Quando | O que traz |
|---|---|---|
| `cliente.registrado` | registro do cliente | `metodo` (`dcr` ou `cimd`), `client_id`, `client_name`, `redirect_uris`, `client_uri`, `logo_uri`, `software_id`, `software_version`, `campos` enviados, `user_agent` |
| `authorize.pedido` | o assistente abre o login | `client_id`, `redirect_uri`, `scope` pedido, `resource`, `code_challenge_method`, `prompt` |
| `escopo.decidido` | troca do código e refresh | assistente detectado, escopos pedidos × concedidos |
| `conexao.criada` / `conexao.substituida` | troca do código | id da `ai_authorizations`; se revogou outra, qual cliente era e se era o mesmo |
| `token.pedido` / `token.resposta` | `/oauth/token` | grant, `resource`, content type, status, erro |
| `mcp.chamada` | toda chamada ao `/mcp` | método JSON-RPC, ferramenta, `client_info` e `protocol_version` do `initialize`, usuário, conexão, assistente, status, ms |
| `mcp.negado` | `/mcp` recusado depois do token | motivo (conexão revogada, usuário desativado, aud) |

1. **Claude app e Claude Code dividem a conexão?** Compare os `cliente.registrado` dos dois: mesmo `client_id` (um registro só) ou dois. Se forem dois, conectar o segundo gera `conexao.substituida` com `mesmo_cliente: false`, e o primeiro passa a levar 401 (`mcp.negado`, "conexão revogada"): é a consequência da IA6 que a necessidade descreve. O mesmo para Codex CLI × Codex no app. Se o dev precisar dos dois ao mesmo tempo, `claude_code` vira assistente separado no B8.
2. **Como cada assistente se identifica?** `cliente.registrado` (`client_name`, `redirect_uris`, `client_uri`, `metodo`) e o `client_info` do `initialize` em `mcp.chamada`. Rodar uma vez sem e outra com `--cimd` mostra quem usa CIMD quando ele é anunciado. Lembrete da `core/mcp.md`, seção 3: nome declarado no registro dinâmico é rótulo, não prova; CIMD (URL no domínio do fornecedor) prova.
3. **ChatGPT Pro só lê?** `escopo.decidido` com `concedidos: ["poc:read"]`, `ping` mostrando `escopos: ["poc:read"]` e `anotar` devolvendo "escopo insuficiente". Observar também se o ChatGPT aceita o conector com uma ferramenta de escrita listada; se recusar, repetir com `--esconder-escrita`.
4. **`laravel/mcp` + Passport funcionam sem gambiarra?** Resposta abaixo.

## Achados para o B8

Validado nos testes de feature e num fluxo de navegador local (Chromium headless: login do Hub → tela de autorização → Autorizar → volta para o loopback com `code` e `iss` → token → `initialize`, `tools/list`, `ping`, `anotar`, refresh). Os assistentes reais ainda não passaram: é o roteiro.

**Veio pronto e foi usado sem mudança**

- `laravel/mcp`: `Mcp::web` (POST JSON-RPC sem estado, GET/DELETE 405, conferência dos cabeçalhos `MCP-*`), `Mcp::oauthRoutes()` com os dois `.well-known` (raiz e com caminho) e `POST /oauth/register` (RFC 7591, cliente público), `WWW-Authenticate` com `resource_metadata` no 401, anotações de ferramenta (`IsReadOnly`, `IsDestructive`, `IsIdempotent`, `IsOpenWorld`), injeção de dependência no `handle`, `validate()` virando erro de ferramenta, `shouldRegister()`.
- Passport 13: authorize, aprovar, recusar, token, PKCE obrigatório para cliente público, rotação do refresh, `invalid_grant` no refresh inválido, porta ignorada no redirect de loopback `127.0.0.1`/`[::1]`, `auth:api`, `tokenCan`.

**Exigiu ajuste (feito à mão nesta branch)**

1. **Escopo próprio.** O pacote anuncia e concede só `mcp:use` (a documentação diz que escopo customizado não é suportado). Os metadados foram sobrescritos estendendo o `Registrar` (métodos estáticos protegidos: frágil em atualização do pacote). O escopo concedido sai de `ScopeRepository::finalizeScopes`, ponto de extensão limpo do league, que vale também no refresh.
2. **RFC 8707.** Passport e league ignoram `resource`; o `aud` do JWT é o `client_id`. Feito: entidade de token própria (`Passport::useAccessTokenEntity`) com `aud = [client_id, recurso]` e conferência no `/mcp`. O `client_id` tem de continuar em `aud[0]` porque o `BearerTokenValidator` do league lê o cliente dali: detalhe interno.
3. **RFC 9207 (`iss`).** Não existe no Passport; middleware acrescenta `iss` no redirect do authorize, do aprovar e do recusar.
4. **PKCE só S256.** O league aceita `plain`; middleware recusa.
5. **Loopback `localhost`.** O league só ignora a porta para IP literal; Claude Code e Codex usam `localhost` com porta aleatória. `Bridge\ClientRepository` estendido (lê o `redirect_uri` da requisição).
6. **CIMD.** Não existe no Passport nem no pacote, e o `oauth_clients.id` é uuid. Feito o mínimo: `client_id` em URL vira uuid v5 determinístico, o documento é buscado (só https com nome de host, 5 s, 10 kB, sem redirect) e cria ou atualiza o cliente. Para o B8: decidir se vale manter, e a proteção de SSRF de verdade (resolver o IP e barrar faixa privada).
7. **CSP.** O `form-action 'self'` do Hub faz o Chrome bloquear o 302 do Autorizar para o domínio do assistente. A tela de autorização libera no `form-action` a origem do `redirect_uri` daquela requisição (`SecurityHeaders` aceita origens extras por requisição). Sem isso, nenhum assistente conecta pelo navegador.
8. **Login do Hub é Inertia.** A volta do login para o `/oauth/authorize` chega por XHR; o middleware responde `Inertia::location` para virar navegação de página inteira.
9. **401 em vez de 302.** Sem `shouldRenderJsonWhen` em `bootstrap/app.php`, o `auth:api` redireciona para o login quando o cliente não manda `Accept: application/json`.
10. **Tela de autorização.** A do pacote é em inglês, usa `@vite` de outro CSS e mostra o escopo pedido, não o concedido. Feita uma Blade mínima.
11. **`ai_authorizations`.** Nada liga Passport e conexão. Feito por ouvinte de `AccessTokenCreated` que lê o `grant_type` da requisição. Para o B8: gravar a conexão no aprovar (controller próprio) e revogar os tokens na mesma transação.
12. **`client_uri` e `logo_uri`** não ficam gravados: o `oauth_clients` não tem as colunas (o pacote só grava se existirem). Hoje só o log guarda; migração só se o B8 precisar.
13. **Resposta do token sem `scope`.** O league não devolve; quando o Hub concede menos que o pedido (ChatGPT), o cliente não fica sabendo. Middleware acrescenta.
14. **Padrões do Passport 13 a mudar:** device code ligado (desligado aqui), token de 1 ano (60 min aqui).
15. **`APP_DEBUG=true`** faz o servidor MCP do pacote relançar a exceção para o cliente; o `start.sh` desliga.

**Não existe e fica para o B8**

- Limite por token (IA7, 60/20 por minuto).
- Auditoria da tentativa negada (G3): o `anotar` sem escopo não grava nada, como a prova pediu; o B8 grava `outcome = denied`.
- Restringir `mcp.redirect_domains` (hoje `*`, padrão do pacote) aos domínios dos assistentes homologados + loopback.
- Revogação pela tela, perfil e administração (IA6) e desligamento na troca de papel e de "IA pode escrever".

**Resumo da pergunta 4:** o caminho do pacote (um escopo `mcp:use`, OAuth como tradução para o usuário) funciona sem ajuste. Os requisitos da casa (escopo pelo papel e pelo assistente, RFC 8707, RFC 9207, S256, loopback `localhost`, CIMD, CSP) pedem 15 ajustes, todos pequenos, três apoiados em detalhe interno de biblioteca (itens 1, 2 e 5). Nenhum exige trocar de pacote.

## Dados que a prova deixa no banco de dev

`oauth_clients`, `oauth_access_tokens`, `oauth_refresh_tokens`, `ai_authorizations` e linhas `poc_note` em `audit_logs` no `wcj_hub`. O teste de navegador de 06/10 criou o usuário "[POC] Dev de teste" (`poc-mcp@exemplo.wcj.test`), já desativado e com as conexões revogadas.
