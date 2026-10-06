# Roteiro de teste: prova de conceito do servidor MCP do WCJ Hub

Executor: Lucas Scholz. Data da pesquisa: 06/10/2026. As telas dos assistentes mudam por mês: onde está "conferir na hora", a documentação divergiu ou pode ter mudado.

O que a prova responde: as 4 perguntas da seção 4 (as pendências "Prova de conceito" da `necessidade.md`, seção "Em aberto", e os pontos 1 a 3 do F8, seção 10).

## 0. Quem testa o quê (decisão de Lucas Scholz, 06/10/2026)

- **Lucas Scholz, com as contas dele:** 2.1 Claude app e 2.2 Claude Code (variantes A e B). Respondem P1, boa parte de P2 e P4, que são as que travam o B8.
- **Conta emprestada (Lucas não tem ChatGPT Plus/Pro):** 2.3 Codex CLI e 2.4 Codex no app por um dev que já use Codex (Cauã ou Vitinho); 2.5 ChatGPT Pro pelo Wilson, usuário real do ChatGPT. Quem testar recebe a URL do túnel no dia, segue o bloco e preenche a linha da tabela.
- **Regra:** se o ChatGPT der trabalho (o Pro não cria conector, ou exige remendo grande no servidor), o ChatGPT sai da fatia 1 e vai para a v2 com o GPT do Wilson; o cartão "ChatGPT" do F8 fica condicional a esta prova. O Codex, se funcionar, segue na fatia 1.
- Servidor: o `start.sh --cimd` anuncia registro dinâmico e CIMD juntos (ver `README.md` desta pasta). Para a rodada (a) do Claude app, suba sem `--cimd`; para a (b), com.

## 1. Antes de começar

- [ ] Servidor de pé: `poc/mcp/start.sh`. Anote a URL pública na tabela de resultados. A URL `trycloudflare.com` muda a cada subida: se reiniciar, remova e adicione o conector de novo em todo assistente.
- [ ] De fora da sua rede (celular em 4G), abra `<URL>/.well-known/oauth-protected-resource` e `<URL>/.well-known/oauth-authorization-server`. Os dois devem devolver JSON 200. `curl -i <URL>/mcp` sem token deve dar **401** com `WWW-Authenticate: Bearer resource_metadata="..."`.
- [ ] `curl -sI <URL>/mcp` não pode mostrar redirecionamento (3xx) para outro host: o Claude derruba o token no redirecionamento.
- [ ] Login do Lucas no Hub funcionando no navegador onde os assistentes vão abrir a autorização (a tela de autorização depende do cookie de sessão).
- [ ] O servidor está gravando o log de registro (`client_name`, `redirect_uris`, `client_uri`) e o log de chamadas (`ping`, `anotar`). Deixe `tail -f` aberto numa janela.
- [ ] Anote o plano real em cada assistente: Claude (Pro ou Max), ChatGPT (Plus ou Pro), Codex no app (plano do ChatGPT).
- [ ] Ordem sugerida: 1 Claude app, 2 Claude Code (em duas variantes), 3 Codex CLI, 4 Codex no app, 5 ChatGPT Pro. Entre um e outro, rode `ping` no anterior (teste de derrubada).
- [ ] Rodada nova de cada assistente: anote a hora para achar a linha no log.

Chamadas de teste, iguais em todos:
1. "Rode a ferramenta ping do conector WCJ Hub."
2. "Use a ferramenta anotar para gravar: teste 1 do <assistente>."
3. Só no ChatGPT: a mesma chamada de `anotar`, esperando **recusa**.

O que o servidor precisa fazer para qualquer um funcionar (checar antes, vindo da documentação):
- 401 com `resource_metadata` na primeira chamada sem token (um 200 com o header não serve).
- Metadados de recurso com `resource` igual à URL digitada (com `/mcp`) e `authorization_servers` com um só issuer.
- Metadados do servidor de autorização com `registration_endpoint`, `code_challenge_methods_supported: ["S256"]`, `token_endpoint_auth_methods_supported` com `none`.
- PKCE S256, parâmetro `resource` (RFC 8707) aceito e conferido como audiência, endpoint `/token` aceitando `application/x-www-form-urlencoded`, `/register` aceitando JSON.
- Redirect URIs aceitos: `https://claude.ai/api/mcp/auth_callback`; `http://localhost:<porta>/callback` e `http://127.0.0.1:<porta>/callback` com qualquer porta; `https://chatgpt.com/connector_platform_oauth_redirect` e `https://chatgpt.com/connector/oauth/<id>`.
- Respostas de descoberta, registro e token em menos de 10 s; `invalid_grant` ao recusar refresh.
- Servidor sem checagem de `Host` que barre o domínio do túnel.

## 2. Um bloco por assistente

### 2.1 Claude app (web ou desktop, plano Pro ou Max)

Passos:
1. Abra claude.ai (ou o app) e entre. Vá em **Customize > Connectors**.
2. Clique em **Add custom connector**.
3. Nome: `WCJ Hub PoC`. URL do servidor MCP: `<URL>/mcp`.
4. A caixa varia por conta. Se aparecer **Authentication**, escolha **Sign in now**. Se aparecer **OAuth client**, faça duas rodadas: (a) **Register automatically** (registro dinâmico, é o que o servidor loga); (b) depois **Use Claude's published identity** (documento de metadados do cliente, CIMD). Se só houver **Advanced settings**, deixe client ID e secret **vazios**.
5. Clique **Add**, depois **Connect**. Não se pode editar a autenticação depois: para trocar de modo, remova e adicione de novo.
6. O navegador abre a tela de autorização do Hub. Deve mostrar: assistente, pessoa, escopos pedidos. Autorize.
7. Volte a **Customize > Connectors**: o conector deve estar **Connected**. Abra a página dele e veja **Tool permissions**: devem aparecer `ping` e `anotar`.
8. Num chat novo, **+ > Connectors**, ligue `WCJ Hub PoC`. Rode as chamadas 1 e 2.

Observar e anotar:
- Conectou? Quanto demorou? A tela do Hub mostrou o nome certo do assistente?
- Pediu confirmação antes de `ping`? Antes de `anotar`? (Botões esperados: **Allow once** / **Always allow**; a permissão por ferramenta fica em **Tool permissions**: Always allow, Needs approval, Blocked.) O servidor marca `ping` como `readOnlyHint`? Isso mudou o comportamento? Conferir na hora.
- No log de registro: `client_name`, `redirect_uris` (esperado `https://claude.ai/api/mcp/auth_callback`), `client_uri`. Se o servidor recebeu um `client_id` em forma de URL, foi CIMD: anote a URL.
- O `ping` devolveu o assistente detectado certo? (`clientInfo` varia: `claude-ai`, `Anthropic`, `claude-code`. A documentação diz para nunca usar como autorização.)

Problemas prováveis:
- "Couldn't reach the MCP server": DNS do túnel ainda não propagou (espere 1 min), a URL tem redirecionamento, ou o host do túnel é barrado pelo servidor. Repita o checklist da seção 1.
- "Authorization with the MCP server failed": audiência (o token precisa ter `aud` igual à URL canônica: host minúsculo, sem barra final), PKCE sem S256 anunciado, ou `/token` demorou mais que 10 s. A URL de erro traz um `flow_id=ofid_...`: anote.
- Redirecionou para `/authorize` no host do MCP: os metadados do recurso não foram lidos. Teste o `curl` da seção 1.
- Tela do Hub pede login de novo ou dá 419: cookie de sessão (SameSite, domínio do túnel). Entre no Hub no mesmo navegador antes.
- Túnel caiu: o conector fica **Reconnect**. Suba o servidor, pegue a URL nova, remova e adicione.

### 2.2 Claude Code (CLI ou desktop), duas variantes

Variante A, conexão do próprio Claude Code:
1. Terminal: `claude mcp add --transport http wcj-hub-poc <URL>/mcp`. Escopo padrão é local (só esta pasta, em `~/.claude.json`). Para valer em qualquer pasta: acrescente `--scope user`. Para compartilhar com o time (`.mcp.json`): `--scope project`. Use `user` na prova.
2. Abra `claude` e digite `/mcp`. O servidor aparece "needs authentication": escolha **Authenticate**.
3. O navegador abre a autorização do Hub. Autorize. O retorno é em `http://localhost:<porta>/callback`. Se o redirecionamento falhar, cole a URL completa do endereço no prompt do Claude Code. Sem navegador: `claude mcp login wcj-hub-poc --no-browser`.
4. Rode as chamadas 1 e 2.
5. Para limpar: `claude mcp logout wcj-hub-poc` (ou **Clear authentication** em `/mcp`).

Variante B, conexão do app, vista pelo Claude Code:
1. Com o conector da seção 2.1 conectado, abra o Claude Code logado **com a mesma conta do claude.ai**.
2. `/mcp`: o conector deve aparecer marcado como vindo do claude.ai, sem novo login. A documentação diz que os conectores do claude.ai ficam disponíveis no Claude Code automaticamente.
3. Rode `ping`. Se aparecer em `/mcp` mas pedir login, anote.
4. Se atrapalhar a variante A, desligue: `ENABLE_CLAUDEAI_MCP_SERVERS=false claude`.

Observar e anotar:
- Variante A: pediu autorização separada da do app? Conectar o Claude Code derrubou o conector do app (rode `ping` no app)? E o contrário?
- Variante B: usou o mesmo token do app (log do servidor: mesmo `client_id` e mesmo token) ou pediu outro?
- Nome no registro: a documentação diz que o Claude Code se identifica por documento de metadados do cliente (`https://claude.ai/oauth/claude-code-client-metadata`), com loopback em qualquer porta. O servidor recebeu isso ou registro dinâmico? Anote `client_id`, `client_name`, `redirect_uris`.
- Pediu confirmação para `anotar`? (O Claude Code pede permissão por ferramenta; veja também o modo de permissão em uso.)

Problemas prováveis: redirect `localhost` ou `127.0.0.1` com porta diferente da registrada (o servidor precisa ignorar a porta); conflito de nome com outra conexão chamada igual; token em `~/.claude.json` antigo depois de trocar a URL do túnel (`mcp remove` e `add` de novo).

### 2.3 Codex CLI

1. Terminal: `codex mcp add wcj-hub-poc --url <URL>/mcp`.
2. `codex mcp login wcj-hub-poc`. Abre o navegador e volta em `http://127.0.0.1:<porta>/callback` (padrão). O Codex mostra o callback para cadastro e aceita os dois métodos: `--oauth-client-registration dcr` ou `cimd` (padrão `auto`). Use `dcr` numa rodada e `auto` na outra.
3. Conferir a configuração: `~/.codex/config.toml` deve ter `[mcp_servers.wcj-hub-poc]` com `url`. `codex mcp list` mostra o estado.
4. Abra `codex`, digite `/mcp` (lista servidores) e peça as chamadas 1 e 2.
5. Aprovação de ferramenta: `default_tools_approval_mode` no servidor da config aceita `auto`, `prompt`, `writes`, `approve`. Rode `anotar` uma vez no padrão e outra com `prompt`.

Observar e anotar: nome e `redirect_uris` no registro; se o Codex pediu confirmação no padrão; se o login do Codex dividiu a conexão com o Codex do app (2.4); se a conexão do Codex derrubou a do Claude Code.

Problemas prováveis: callback em porta bloqueada (fixe com `mcp_oauth_callback_port` no `config.toml`); servidor que só aceita registro quando o `redirect_uri` é exatamente igual (use comparação por esquema, host e caminho, sem a porta, para loopback).

### 2.4 Codex dentro do app do ChatGPT (desktop, plano Plus)

1. No app do ChatGPT para computador: **Settings > MCP servers > Add server**. (O menu antigo era Configurações > Integrações > Servidores MCP: conferir na hora o nome.)
2. Nome `wcj-hub-poc`. Transporte **Streamable HTTP**. URL `<URL>/mcp`.
3. Salve. Aparece **Authenticate**: clique e autorize no Hub. Depois **Restart** para ativar.
4. No campo de mensagem digite `/mcp`: deve listar o servidor como habilitado e autenticado.
5. Rode as chamadas 1 e 2 numa conversa do Codex.

Observar e anotar: usou a mesma configuração do Codex CLI (o app e o CLI leem o mesmo `config.toml`?) ou pediu login novo? Nome no registro. Confirmou antes de `anotar`? Aprovação fina só vem do `config.toml`, não da tela: anote se foi suficiente.

Problemas prováveis: o app não oferece transporte HTTP no plano (conferir na hora); não reinicia sozinho; callback bloqueado por firewall local.

### 2.5 ChatGPT Pro (app web, só leitura)

A documentação diverge: a ajuda da OpenAI diz que o Pro conecta MCP com leitura e busca no modo desenvolvedor, e que escrita completa é só de Business, Enterprise e Edu; outras fontes dizem que conector próprio é recurso de workspace. **Conferir na hora** se o Pro sequer consegue criar o conector. Isso é resultado da prova.

1. chatgpt.com (web): **Settings > Apps > Advanced settings > Developer mode** (ligar). Na documentação nova, o caminho é chatgpt.com/plugins > botão + > **Add custom MCP server**. Use o que existir.
2. Nome `WCJ Hub PoC`. Conexão: URL pública `<URL>/mcp` (não use "Secure MCP Tunnel", é outro recurso).
3. Autenticação: OAuth. Se houver campos de client ID e secret, deixe vazios. Aceite o aviso de risco e crie.
4. Autorize no Hub. O ChatGPT lista as ferramentas descobertas: confira `ping` e `anotar`. Se mudar algo no servidor, use **Refresh**.
5. Numa conversa, ative o conector e rode as chamadas 1 e 3 (leitura e escrita esperando recusa). Rode também a 2 e anote o que aconteceu.

Observar e anotar:
- O Pro conseguiu criar o conector? Se não, a mensagem exata, a tela e o plano.
- No registro: o ChatGPT tende a usar documento de metadados do cliente (a documentação o chama de preferido) ou registro dinâmico. Anote `client_id`, `client_name`, `redirect_uris` (esperado `https://chatgpt.com/connector_platform_oauth_redirect` ou `https://chatgpt.com/connector/oauth/<id>`). O servidor precisa devolver o parâmetro `iss` na resposta de autorização para o redirect estável. Anote se houve erro.
- A chamada de escrita: o ChatGPT recusou sozinho, pediu confirmação, ou o servidor recusou (token só de leitura)? O texto de recusa chegou claro ao usuário?
- O servidor detectou "ChatGPT" e emitiu token só de leitura sem depender do ChatGPT?

Problemas prováveis: a lista de ferramentas não atualiza (use Refresh); o ChatGPT exige `WWW-Authenticate` com `resource_metadata` para abrir a tela de login; `aud` ausente no token; modo agente e app móvel não usam conector próprio (não é falha do servidor).

## 3. Tabela de resultados

| Assistente | Plano | Conectou (OAuth) | `ping` | `anotar` | Pediu confirmação | Registro: `client_name` / `client_id` / redirect | Observações |
|---|---|---|---|---|---|---|---|
| Claude app | | | | | | | |
| Claude Code A (`claude mcp add`) | | | | | | | |
| Claude Code B (conector do app) | | | | | | | |
| Codex CLI | | | | | | | |
| Codex no app (Plus) | | | | | | | |
| ChatGPT Pro | | | | n/a: esperado recusa | | | |

Teste de derrubada (preencha depois de rodar `ping` em cada um após conectar o outro):

| Conectei | Derrubou | Sim/não |
|---|---|---|
| Claude Code A | Claude app | |
| Claude app | Claude Code A | |
| Codex CLI | Codex no app | |
| Codex no app | Codex CLI | |

## 4. As 4 perguntas da prova

**P1. Claude app e Claude Code dividem a conexão?**
Pesquisa: o conector do app aparece no Claude Code (mesma conta) com autorização compartilhada; `claude mcp add` é outro sistema, com OAuth próprio.
Resposta: ______
Consequência: se dividem (variante B), mantém os 3 cartões do F8 (Claude, Codex, ChatGPT) e a doc do cartão Claude diz "app ou Claude Code". Se só a variante A existe para o dev, ou se conectar uma derruba a outra, o Claude Code vira o 4º cartão (`claude_code`) no F8, seção 01 e no B8. Mesma conta para Codex CLI e Codex no app: idem.

**P2. Como cada assistente se identifica, e isso prova identidade?**
Pesquisa: Claude (app e Code) e ChatGPT preferem documento de metadados do cliente (`client_id` é uma URL do fornecedor, o que prova); registro dinâmico só dá `client_name` declarado, que não prova nada. Codex aceita `cimd`, `dcr` ou `auto`. O `clientInfo` do `initialize` nunca prova.
Resposta (por assistente: URL de metadados, pré-cadastro ou registro dinâmico): ______
Consequência: onde veio URL do fornecedor, o servidor pode usar o `client_id` para `ia:claude`/`ia:chatgpt` e para a regra "ChatGPT só lê". Onde veio só `client_name`, isso é rótulo: a garantia fica no escopo pelo papel (IA5) e na marca "IA pode escrever" do usuário (F8, ponto 3); registrar a decisão em `core/mcp.md` §3. Assistente não reconhecido continua "não homologado" (F8, ponto 2).

**P3. ChatGPT Pro só lê, e o servidor precisou garantir?**
Resposta: ______
Consequência: se o Pro nem cria o conector, o cartão ChatGPT (só leitura) sai da v1 e vai para "Fora da primeira versão"; se cria e o ChatGPT recusou sozinho, o servidor ainda emite token só de leitura (core/mcp.md §3); se o ChatGPT deixou escrever, o servidor precisa recusar (identificação por P2); se não houver identificação confiável, o ChatGPT só lê para todos ou fica fora.

**P4. `laravel/mcp` + Passport funciona sem gambiarra?**
Resposta: ______
Lista de remendos (rotas de metadados, `resource_metadata` no 401, registro dinâmico, audiência `aud`, documento de metadados do cliente, `iss`): ______
Consequência: sem remendo, B8 usa os dois como estão. Com remendo pequeno e documentado, B8 inclui a tarefa. Remendo grande (por exemplo, sem registro dinâmico no Passport): decidir entre pacote auxiliar ou servidor de autorização próprio, antes de abrir o B8.

## 5. Modelo de ata (`docs/atas/2026-10-XX-poc-mcp.md`)

```
# Prova de conceito do MCP: <data>
Quem testou: Lucas Scholz. URL usada: <trycloudflare>. Servidor: commit <hash>.
Planos: Claude <Pro/Max>; ChatGPT <Plus/Pro>; Codex <plano>.

## Resultados
(colar a tabela da seção 3)

## Respostas
P1 ...  P2 ...  P3 ...  P4 ...

## Decisões para o B8
- Cartões do F8: <3 ou 4>
- Identificação: <metadados do cliente / registro / pré-cadastro> por assistente
- ChatGPT Pro: <entra só leitura / fora da v1>
- Remendos no laravel/mcp + Passport: <lista>

## Em aberto que fecharam
- <itens da necessidade.md, seção Em aberto>

## Em aberto que surgiram
- <ex.: modo de voz do ChatGPT (v2), Codex no Plus>
```

## 6. Fontes (lidas em 06/10/2026)

- Claude, adicionar conector próprio: https://claude.com/docs/connectors/custom/add-unlisted
- Claude, requisitos de OAuth (DCR, documento de metadados do cliente, callbacks, PKCE, discovery): https://claude.com/docs/connectors/building/authentication
- Claude, testar conector e túnel; `clientInfo`: https://claude.com/docs/connectors/building/testing
- Claude, diagnóstico de erros: https://claude.com/docs/connectors/building/troubleshooting
- Claude, usar o conector e aprovação de ferramenta: https://claude.com/docs/connectors/getting-started
- Claude Code, MCP: escopos, `/mcp`, callback, conectores do claude.ai: https://code.claude.com/docs/en/mcp
- Codex, MCP (CLI e app): https://learn.chatgpt.com/docs/extend/mcp.md?surface=cli e `?surface=app` (a URL antiga developers.openai.com/codex/mcp redireciona para cá)
- ChatGPT, OAuth do cliente (redirects, documento de metadados, `iss`, PKCE): https://developers.openai.com/apps-sdk/build/auth
- ChatGPT, anotações de ferramenta: https://developers.openai.com/apps-sdk/build/mcp-server
- ChatGPT, conectar servidor próprio: https://developers.openai.com/apps-sdk/deploy/connect-chatgpt
- ChatGPT, modo desenvolvedor e planos (não abriu por 403; só o resumo da busca): https://help.openai.com/en/articles/12584461-developer-mode-and-mcp-apps-in-chatgpt. Divergências entre blogs de terceiros e a ajuda da OpenAI sobre Pro/Plus: **conferir na hora**.
- Interno: `wcj-hub/docs/necessidade.md` (Assistentes de IA, Em aberto), `wcj-hub/docs/funcional/f8-conexoes-de-ia.md` (01, 10), `ai-systems/.ai/core/mcp.md` (3, 8, 10).
