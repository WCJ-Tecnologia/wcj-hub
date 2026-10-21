# B9 Segurança Tasks

## Execution Protocol (MANDATORY -- do not skip)

Implement these tasks with the `tlc-spec-driven` skill: **activate it by name and follow its Execute flow and Critical Rules.** Do not search for skill files by filesystem path. The skill is the source of truth for the full flow (per-task cycle, sub-agent delegation, adequacy review, Verifier, discrimination sensor).

**If the skill cannot be activated, STOP and tell the user - do not proceed without it.**

---

**Spec**: `.specs/features/b9-seguranca/spec.md` (aprovada por Lucas Scholz em 21/10/2026)
**Design**: sem `design.md`: nenhuma decisão de arquitetura nova; cada tarefa reaproveita o padrão que o Hub já tem (middleware, `FormRequest`, `RateLimiter::for`, `ThrottledAuthResponse`, Service).
**Status**: Approved (Lucas Scholz, 21/10/2026, junto com a spec)
**Execução**: inline, pelo mesmo orquestrador que escreveu a spec (13 tarefas, quase todas pequenas e no mesmo punhado de arquivos de autenticação); o Verifier é um agente separado em Opus.

---

## Test Coverage Matrix

> Generated from codebase, project guidelines, and spec. Guidelines found: `AGENTS.md` (Testes técnicos, Trilhos), `.ai/core/testes.md`, `.ai/core/especificacao.md` §6, `.ai/core/seguranca.md` §3 (limite provado por sequência de tentativas, não por leitura de config), `phpunit.xml`.

| Code Layer | Required Test Type | Coverage Expectation | Location Pattern | Run Command |
| ---------- | ------------------ | -------------------- | ---------------- | ----------- |
| Middleware, rota de autenticação | integration (HTTP, MySQL 8) | Todo AC da spec com o valor exato (status, header, mensagem); limite provado por sequência real de requisições | `tests/Feature/*Test.php` | `php artisan test --filter=<Classe>` |
| Service (anexo) | integration (HTTP) | Feliz + 403 auditado + 404 de outro card + 404 de arquivo sumido | `tests/Feature/Projetos/TaskDetailTest.php` | `php artisan test --filter=TaskDetail` |
| Comando artisan | integration | Cada item errado listado pelo nome, código de saída, nenhum valor impresso | `tests/Feature/*Test.php` | `php artisan test --filter=ConferirProducao` |
| Arquitetura | arch | A10 novo falha contra fixture com `$request->validate(`; A5 continua verde | `tests/Arch/*` | `php artisan test tests/Arch` |
| Tela React (perfil) | none (typecheck + lint; uso no navegador com print) | Campo da senha atual com erro | `resources/js/components/pages/w-profile-page/` | `npm run typecheck && npm run lint` |
| CI, docs, `.specs`, relatórios | none | Conferidor de specs; `npm audit` local | `.github/workflows/*`, `docs/*`, `.specs/*` | `node .ai/skills/conferir-specs.mjs` |

## Gate Check Commands

| Gate Level | When to Use | Command |
| ---------- | ----------- | ------- |
| Quick | Tarefa só de backend | `composer lint && php artisan test --filter=<Classe>` |
| Full | Fim de fase | `composer lint && caffeinate -i php artisan test` |
| Build | Tarefa que toca tela, CI ou dependência; fim da feature | `composer lint && caffeinate -i php artisan test && npm run typecheck && npm run lint && npm run build && npm run test:guard` |

---

## Execution Plan

### Phase 1: Headers

```
T1 → T2
```

### Phase 2: Senha e conta

```
T3 → T4 → T5
```

### Phase 3: Entrada e anexo

```
T6 → T7
```

### Phase 4: Produção, CI e runbook

```
T8 → T9 → T10 → T11
```

### Phase 5: Relatórios

```
T12 → T13
```

### Phase 6: Correções da revisão dirigida (aprovadas por Lucas Scholz em 21/10/2026)

```
T14 → T15 → T16 → T17 → T18 → T19
```

---

## Task Breakdown

### Phase 1: Headers

### T1: Headers em toda resposta, sem `X-Powered-By`

**What**: `SecurityHeaders` sai do grupo `web` e entra como middleware global (`$middleware->append`); remove `X-Powered-By` da resposta e chama `header_remove('X-Powered-By')`. Nenhum valor de header muda.
**Where**: `bootstrap/app.php`, `app/Http/Middleware/SecurityHeaders.php`, `tests/Feature/SecurityHeadersTest.php`
**Depends on**: None
**Reuses**: o middleware existente
**Requirement**: SEC-01, SEC-02

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] 404 sem rota, `GET /up` e `GET /api/health` trazem CSP, `nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy`
- [x] Com `APP_ENV=production`, o 404 traz o HSTS
- [x] Nenhuma resposta traz `X-Powered-By`, mesmo quando a rota o define
- [x] Gate check passes: `composer lint && php artisan test --filter=SecurityHeaders`

**Tests**: integration
**Gate**: quick

**Commit**: `feat(seguranca): headers de seguranca em toda resposta e sem x-powered-by`

---

### T2: Desligar a rota `storage/{path}`

**What**: `'serve' => false` no disco `local`.
**Where**: `config/filesystems.php`, `tests/Feature/SecurityHeadersTest.php`
**Depends on**: T1
**Reuses**: NONE
**Requirement**: SEC-03

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] `GET /storage/qualquer-coisa` responde 404 e nenhuma rota `storage/{path}` está registrada
- [x] Gate check passes: `composer lint && caffeinate -i php artisan test`

**Tests**: integration
**Gate**: full

**Commit**: `fix(seguranca): desliga a rota publica do disco local`

---

### Phase 2: Senha e conta

### T3: Login em FormRequest e hash descartável

**What**: `LoginRequest` com as regras de hoje; quando não há usuário com o e-mail, o login confere a senha contra um hash descartável antes de responder `INVALID_CREDENTIALS`.
**Where**: `app/Platform/Requests/Auth/LoginRequest.php` (novo), `app/Platform/Controllers/Auth/AuthenticatedSessionController.php`, `tests/Feature/LoginTimingTest.php` (novo)
**Depends on**: T2
**Reuses**: `INVALID_CREDENTIALS`
**Requirement**: SEC-06, SEC-08

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] E-mail sem conta: `Hash::check` é chamado uma vez e a resposta traz `INVALID_CREDENTIALS` no campo `email`
- [x] E-mail com conta e senha errada: a mesma mensagem
- [x] Login certo continua entrando (testes que já existem verdes)
- [x] Gate check passes: `composer lint && php artisan test --filter='Login|BackofficeAccess'`

**Tests**: integration
**Gate**: quick

**Commit**: `feat(seguranca): login confere hash descartavel quando o e-mail nao tem conta`

---

### T4: Esqueci, redefinir e primeiro acesso em FormRequest; e-mail de redefinição na fila

**What**: `ForgotPasswordRequest`, `ResetPasswordRequest`, `FirstAccessPasswordRequest` com as regras de hoje; `ResetPasswordMail` implementa `ShouldQueue`.
**Where**: `app/Platform/Requests/Auth/*.php` (novos), `ForgotPasswordController.php`, `NewPasswordController.php`, `ChangePasswordController.php`, `app/Mail/ResetPasswordMail.php`, `tests/Feature/PasswordFlowTest.php`
**Depends on**: T3
**Reuses**: as mensagens `GENERIC_MESSAGE` e `INVALID_LINK`
**Requirement**: SEC-07, SEC-08

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] E-mail com conta ativa: `ResetPasswordMail` na fila (`Mail::assertQueued`) e nada enviado na requisição (`assertNothingSent`)
- [x] E-mail sem conta: `GENERIC_MESSAGE` e nada na fila
- [x] Os testes de senha que já existem continuam verdes
- [x] Gate check passes: `composer lint && php artisan test --filter='PasswordFlow|LoginThrottle'`

**Tests**: integration
**Gate**: quick

**Commit**: `feat(seguranca): fluxos de senha em formrequest e e-mail de redefinicao na fila`

---

### T5: Perfil pede a senha atual, com limite

**What**: `UpdateProfileRequest` (nome, senha nova opcional e `current_password` obrigatório só quando há senha nova, regra `current_password`, mensagem "A senha atual não confere"); limitador `senha-atual` (5/min por usuário, só quando há senha nova, `Limit::none()` no resto) no `PUT admin/profile`; `ThrottledAuthResponse` re-renderiza o perfil com 429 e o erro em `current_password`; campo "Senha atual" na tela.
**Where**: `app/Platform/Requests/Admin/UpdateProfileRequest.php` (novo), `ProfileController.php`, `AppServiceProvider.php`, `routes/web.php`, `app/Http/Responses/ThrottledAuthResponse.php`, `resources/js/components/pages/w-profile-page/w-profile-page.tsx`, `tests/Feature/ProfilePasswordTest.php` (novo), `tests/Arch/divida.php` (sai a dívida A6 do perfil, se era ela)
**Depends on**: T4
**Reuses**: `SessionService::terminateOthers`, `ThrottledAuthResponse`
**Requirement**: SEC-04, SEC-05, SEC-08

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Senha atual errada: erro "A senha atual não confere" em `current_password`, senha gravada igual, outra sessão continua no banco
- [x] Senha atual certa + nova válida: senha nova gravada, outras sessões apagadas
- [x] Só o nome: grava sem pedir a senha atual
- [x] 6ª troca de senha no mesmo minuto: 429, sessão de pé; 6 saves só de nome não dão 429
- [x] Gate check passes: `composer lint && caffeinate -i php artisan test && npm run typecheck && npm run lint`

**Tests**: integration
**Gate**: build

**Commit**: `feat(seguranca): perfil pede a senha atual e limita a troca de senha`

---

### Phase 3: Entrada e anexo

### T6: A10 e teto da busca

**What**: `tests/Arch` ganha o A10 (ação de rota que não é GET não chama `$request->validate(` nem `Validator::make(`); a busca das seis listas ignora texto com mais de 200 caracteres.
**Where**: `tests/Arch/RotasAutorizadasTest.php` ou `tests/Arch/ArquiteturaTest.php`, os seis controllers de lista, `tests/Feature/BuscaTetoTest.php` (novo)
**Depends on**: T5
**Reuses**: a enumeração de rotas do A5
**Requirement**: SEC-09, SEC-10

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] A10 verde na árvore e vermelho contra uma ação de mentira com `$request->validate(` (teste do próprio verificador do A10)
- [x] Busca de 201 caracteres nas seis listas devolve a lista sem filtro de texto; 200 caracteres filtra
- [x] A5 verde, sem exceção nova
- [x] Gate check passes: `composer lint && php artisan test tests/Arch && php artisan test --filter=BuscaTeto`

**Tests**: arch, integration
**Gate**: quick

**Commit**: `feat(seguranca): a10 barra validacao fora de formrequest e busca ganha teto`

---

### T7: Download do anexo pelo Service

**What**: `AttachmentService::download(TaskAttachment): StreamedResponse` com o comportamento de hoje; o controller só autoriza e chama.
**Where**: `app/Domains/Projetos/Services/AttachmentService.php`, `TaskDetailController.php`, `tests/Feature/Projetos/TaskDetailTest.php`
**Depends on**: T6
**Reuses**: `TaskAttachment::isImage`, `TaskDetailPresenter::DISK`
**Requirement**: SEC-11

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Não imagem: `attachment`, nome original, `Content-Type` gravado, `nosniff`; imagem: `inline`
- [x] 403 auditado para quem não vê o projeto; 404 de outro card; 404 de arquivo sumido
- [x] O controller não usa `Storage`
- [x] Gate check passes: `composer lint && caffeinate -i php artisan test`

**Tests**: integration
**Gate**: full

**Commit**: `refactor(projetos): download do anexo pelo attachmentservice`

---

### Phase 4: Produção, CI e runbook

### T8: `app:conferir-producao` e erro 500 sem stack

**What**: comando que confere a configuração carregada e lista o que está errado pelo nome; teste de que um 500 com `app.debug = false` não traz classe, caminho nem stack.
**Where**: `app/Console/Commands/ConferirProducaoCommand.php` (novo), `tests/Feature/ConferirProducaoTest.php` (novo), `tests/Feature/ErroSemStackTest.php` (novo)
**Depends on**: T7
**Reuses**: NONE
**Requirement**: SEC-12, SEC-13

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Cada um dos sete itens errados aparece pelo nome da variável e o código de saída é 1
- [x] Configuração certa: código 0
- [x] Nenhum valor (APP_KEY, URL, mailer) aparece na saída
- [x] 500 com debug desligado: sem o nome da classe da exceção, sem `.php`, sem `#0`
- [x] Gate check passes: `composer lint && php artisan test --filter='ConferirProducao|ErroSemStack'`

**Tests**: integration
**Gate**: quick

**Commit**: `feat(seguranca): comando que confere a configuracao de producao`

---

### T9: Auditoria de dependências no CI e `npm audit fix`

**What**: passo `composer audit` no job `php` e `npm audit --omit=dev --audit-level=high` no job `js` do `ci.yml`; `npm audit fix` sem `--force`.
**Where**: `.github/workflows/ci.yml`, `package-lock.json`
**Depends on**: T8
**Reuses**: NONE
**Requirement**: SEC-16, SEC-17

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] `npm audit` local sem aviso alto
- [x] Os dois passos no `ci.yml`, sem job novo
- [x] Gate check passes: build

**Tests**: none
**Gate**: build

**Commit**: `ci(seguranca): auditoria de dependencias no ci e correcao do npm audit`

---

### T10: `DEPLOY.md` completo e "Ambientes" do `AGENTS.md`

**What**: runbook completo (variáveis por nome e onde buscar, conferência, backup, passo a passo, nginx, fila, verificação, voltar atrás) e a seção "Ambientes".
**Where**: `docs/DEPLOY.md`, `AGENTS.md`
**Depends on**: T9
**Reuses**: o esboço do B0
**Requirement**: SEC-14, SEC-15

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Toda variável que produção usa listada com onde buscar; nenhum valor
- [x] A ordem do AC 7 de SEC-14 e o bloco de nginx do AC 8
- [x] "Ambientes" aponta para o `DEPLOY.md` e diz o que falta no B10
- [x] Gate check passes: `npm run test:guard`

**Tests**: none
**Gate**: build

**Commit**: `docs(deploy): runbook de publicacao do b9`

---

### T11: Nota para o B8

**What**: `nota-para-o-b8.md` com o limitador do `/oauth/token`, o limite do `/mcp`, os headers, o A5 e as nove perguntas do `mcp.md` §12.
**Where**: `.specs/features/b9-seguranca/nota-para-o-b8.md`
**Depends on**: T10
**Reuses**: NONE
**Requirement**: SEC-19, SEC-20

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Os cinco itens do AC 1 de SEC-19
- [x] Gate check passes: `node .ai/skills/conferir-specs.mjs`

**Tests**: none
**Gate**: build

**Commit**: `docs(seguranca): nota de seguranca para o b8`

---

### Phase 5: Relatórios

### T12: SCA e SAST

**What**: `scan.sh <repo> sca` e `sast` do `wcj-pentest`; relatório com cada aviso classificado por alcance.
**Where**: `.specs/features/b9-seguranca/sca-2026-10-21.md`
**Depends on**: T11
**Reuses**: `wcj-pentest`
**Requirement**: SEC-16

**Tools**:

- MCP: NONE
- Skill: wcj-pentest

**Done when**:

- [x] Cada aviso com pacote, versão, identificador, severidade e alcance
- [x] Zero "pendência" sem correção ou dono
- [x] Gate check passes: `node .ai/skills/conferir-specs.mjs`

**Tests**: none
**Gate**: build

**Commit**: `docs(seguranca): relatorio do sca e do sast do b9`

---

### T13: Revisão de segurança dirigida

**What**: subagente de contexto zerado (Opus) responde as seis perguntas de `seguranca.md` §11 arquivo por arquivo; achado novo vira correção ou fica "em aberto com correção proposta" com dono.
**Where**: `.specs/features/b9-seguranca/revisao-seguranca.md`
**Depends on**: T12
**Reuses**: NONE
**Requirement**: SEC-18

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] As seis perguntas respondidas com arquivo e linha
- [x] Zero achado "em aberto" sem dono
- [x] Gate check passes: `node .ai/skills/conferir-specs.mjs`

**Tests**: none
**Gate**: build

**Commit**: `docs(seguranca): revisao de seguranca dirigida do b9`

### Phase 6: Correções da revisão dirigida

---

### T14: Links no domínio do Hub (achado 1)

**What**: Middleware global que, em produção, fixa a raiz das URLs no `APP_URL` (`URL::forceRootUrl` + esquema); `trustHosts` com o host do `APP_URL` só em produção; `server_name` e servidor padrão 444 no `DEPLOY.md`.
**Where**: `app/Http/Middleware/UseAppUrl.php` (novo), `bootstrap/app.php`, `tests/Feature/HostDoHubTest.php` (novo), `docs/DEPLOY.md`
**Depends on**: T13
**Reuses**: `TrustHosts` do Laravel
**Requirement**: SEC-21

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Em produção, `Host` estranho: 404 e nada na fila
- [ ] Em produção, pedido por `http`: o link do e-mail começa com o `APP_URL` em `https`
- [ ] Em produção, URL gerada na requisição começa com o `APP_URL`
- [ ] `server_name` e `default_server` 444 no `DEPLOY.md`
- [ ] Gate check passes: `composer lint && php artisan test --filter='HostDoHub|PasswordFlow|SecurityHeaders'`

**Tests**: integration
**Gate**: quick

**Commit**: `fix(seguranca): links e host presos ao APP_URL em produção`

---

### T15: Login por attemptWhen, dentro do Timebox de 500 ms (achados 2 e 4)

**What**: Tira o hash descartável; `Auth::attemptWhen` com o callback de conta ativa e de backoffice; `auth.timebox_duration` em `config/auth.php` por `AUTH_TIMEBOX_MS` (500), no `.env.example`.
**Where**: `app/Platform/Controllers/Auth/AuthenticatedSessionController.php`, `config/auth.php`, `.env.example`, `tests/Feature/LoginTimingTest.php`
**Depends on**: T14
**Reuses**: `SessionGuard::attemptWhen` e o `Timebox` do Laravel
**Requirement**: SEC-06, SEC-22

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] E-mail sem conta, senha errada e conta desativada: resposta recusada só depois de `auth.timebox_duration` (medido com o valor do teste)
- [ ] Conta desativada com a senha certa: `INVALID_CREDENTIALS` e o id da sessão não muda
- [ ] Login certo continua entrando
- [ ] Gate check passes: `composer lint && php artisan test --filter='Login|BackofficeAccess'`

**Tests**: integration
**Gate**: quick

**Commit**: `fix(seguranca): login recusado leva sempre o tempo do Timebox`

---

### T16: Esqueci e redefinir dentro do Timebox (achado 3)

**What**: A action inteira do "esqueci a senha" e do "redefinir" roda num `Timebox` de `auth.timebox_duration`; ajusta o comentário do `ResetPasswordMail`.
**Where**: `ForgotPasswordController.php`, `NewPasswordController.php`, `app/Mail/ResetPasswordMail.php`, `tests/Feature/PasswordFlowTest.php`
**Depends on**: T15
**Reuses**: `Illuminate\Support\Timebox`
**Requirement**: SEC-07

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] "Esqueci" para conta ativa, inativa e inexistente: resposta só depois de `auth.timebox_duration`
- [ ] "Redefinir" com conta inexistente e com token errado: idem
- [ ] Gate check passes: `composer lint && php artisan test --filter=PasswordFlow`

**Tests**: integration
**Gate**: quick

**Commit**: `fix(seguranca): esqueci e redefinir respondem no tempo do Timebox`

---

### T17: Parâmetro em array não dá 500 (achado 5)

**What**: `Busca::termo`, a chave do limitador `login`, a tela de redefinir, o país dos clientes e as datas de *Minhas horas* só aceitam texto.
**Where**: `app/Support/Busca.php`, `app/Providers/AppServiceProvider.php`, `NewPasswordController.php`, `ClientController.php`, `TimeEntryController.php`, `tests/Feature/EntradaEmArrayTest.php` (novo)
**Depends on**: T16
**Reuses**: NONE
**Requirement**: SEC-23

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Login, esqueci e redefinir com `email[]`: erro de validação, sem 500
- [ ] `GET reset-password/x?email[]=y`: 200 com e-mail vazio
- [ ] `search[]`, `country[]`, `from[]`, `to[]`: 200 com o filtro no padrão
- [ ] Gate check passes: `composer lint && php artisan test --filter='EntradaEmArray|BuscaTeto'`

**Tests**: integration
**Gate**: quick

**Commit**: `fix(seguranca): parametro em array nao derruba a requisicao`

---

### T18: Recusa de alocação sem o nome (achado 6)

**What**: A recusa de dev ou QA fora do projeto passa a "A pessoa escolhida não está alocada neste projeto.".
**Where**: `app/Domains/Projetos/Services/TaskAssignmentService.php`, testes que conferem a mensagem
**Depends on**: T17
**Reuses**: `NOT_ALLOCATED`
**Requirement**: SEC-24

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] `developer_id` de quem não está alocado: a mensagem nova, sem o nome
- [ ] Gate check passes: `composer lint && caffeinate -i php artisan test`

**Tests**: integration
**Gate**: full

**Commit**: `fix(projetos): recusa de alocacao sem o nome da pessoa`

---

### T19: `TRUSTED_PROXIES` e o tempo no `DEPLOY.md`

**What**: Reescreve a orientação de `TRUSTED_PROXIES` para FastCGI; acerta a frase da fila (quem iguala o tempo é o Timebox); `AUTH_TIMEBOX_MS` na tabela de variáveis.
**Where**: `docs/DEPLOY.md`
**Depends on**: T18
**Reuses**: NONE
**Requirement**: SEC-25

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] A frase nova sobre FastCGI e nunca `*`
- [ ] `AUTH_TIMEBOX_MS` na tabela
- [ ] Gate check passes: `composer lint && caffeinate -i php artisan test && npm run typecheck && npm run lint && npm run build && npm run test:guard`

**Tests**: none
**Gate**: build

**Commit**: `docs(deploy): trusted_proxies com fastcgi e o tempo do timebox`

