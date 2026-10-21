# Revisão de segurança dirigida — B9

Revisor: subagente de contexto zerado (Opus), 21/10/2026. Base: branch claude/b9-seguranca-6e8fcf, commit 550fde4. Método: as seis perguntas de .ai/core/seguranca.md §11, arquivo por arquivo.

**Veredito:** 6 achados: 1 alto, 2 médios, 3 baixos. Nenhum informativo na tabela; as observações menores estão em "Conferido de passagem".

Evidência dinâmica usada (nada gravado no repositório nem no banco):

- `php artisan test tests/Arch`: 10 testes verdes, entre eles A5 (`RotasAutorizadasTest`) e A10 (`FormRequest` na escrita).
- `php artisan route:list --json`: middleware de cada rota (pergunta 1).
- `gitleaks git --redact` no histórico: 358 commits, nenhum vazamento (pergunta 5).
- Script fora do repositório (no scratchpad da sessão), com o kernel HTTP do Laravel, `APP_KEY` descartável e sessão e cache em `array`. Ele confirmou os achados 1 e 5 e os headers.
- Microbenchmark com o `Illuminate\Support\Timebox` do vendor e bcrypt custo 12 (dá cerca de 267 ms nesta máquina). Ele confirmou o mecanismo do achado 2.

## 1. Toda rota de dados tem auth e Policy?

**Resposta:** sim.

- `routes/web.php:39-54`: grupo `guest`. Nele ficam login, esqueci a senha e redefinir senha, os três POST com `throttle:login`. É a lista de exceção declarada.
- `routes/web.php:62-71`: grupo `auth`. Leva `admin/logout` (só `auth`) e `admin/first-access` (`auth` + `backoffice`, PUT com `throttle:login`).
- `routes/web.php:73-110`: grupo `admin.`, com `auth`, `backoffice` e `password.current`.
- `routes/web.php:118-211`: grupo `projects.`, com os mesmos três middlewares e `scopeBindings()` em 133-210.
- `routes/web.php:219-223`: grupo das horas, com os mesmos três.
- `routes/api.php:6`: só `api/health`, que devolve `{"ok":true}`.
- Fora de `auth` ficam também `GET /` (`routes/web.php:30`, só redireciona) e `GET /up` (`bootstrap/app.php:18`, health check do framework). Nenhum dos dois devolve dado.
- O `route:list` confirma: toda rota com `{id}` ou de dados leva `auth`.
- Policy: toda ação chama `$this->authorize()` (o `Controller.php:25-30` audita a negação) ou recebe um `FormRequest` com `AuthorizesBeforeValidation` (`app/Platform/Requests/Concerns/AuthorizesBeforeValidation.php:18-23`). O A5 verde prova isso para todas as rotas.
- Exceções declaradas no A5 (`tests/Arch/RotasAutorizadasTest.php:38-62`):
  - ações sobre a própria conta: logout, primeiro acesso, perfil;
  - `DashboardController@index`, que só devolve as contagens de usuários (`app/Platform/Services/DashboardService.php:17-23`); a atividade recente vai só ao admin (`DashboardController.php:21`).

## 2. Toda rota que recebe ID valida o vínculo com a sessão?

**Resposta:** sim. Uma ressalva de exposição de dado ficou no achado 6.

- **Filhos do projeto.** `scopeBindings()` em `routes/web.php:133`. `{board_stage}`, `{area}`, `{sprint}`, `{task}`, `{taskType}` e `{user}` resolvem pelas relações `boardStages`, `areas`, `sprints`, `tasks`, `taskTypes` e `users` do `Project` (`app/Domains/Projetos/Models/Project.php:70-96`). Id de outro projeto dá 404 antes da Policy.
- **`{card}`.** `app/Providers/AppServiceProvider.php:134-143` procura a chave só entre os cards do projeto da rota, com `where('project_id', ...)`.
- **Filhos do card.** Critério, PR, comentário e anexo têm duas travas:
  - o binding escopado pelo `{card}`, pelas relações `acceptanceCriteria`, `pullRequests`, `comments` e `attachments` do `Task.php:177-192`;
  - mais `belongsToCard()` em `TaskDetailController.php:146-149` e o mesmo teste em `CardTextRequest.php:18-21`.
  
  O 404 vem antes da Policy em `TaskDetailController.php:74, 81, 94, 107, 114, 127, 136`.
- **Rotas sem projeto na URL.**
  - `horas/{time_entry}`: `TimeEntryPolicy::corrects` deriva o projeto do registro carregado (`TimeEntryPolicy.php:40-49`).
  - `contacts/{contact}`, `clients/{client}` e `users/{user}`: `ContactPolicy` (vínculo pelo `ClientManagement`), `ClientPolicy` e `UserPolicy` (só admin).
- **Vínculo entre dois registros**, conferindo no Service que os dois são do mesmo projeto:
  - etapa do movimento: `MoveTaskRequest.php:17, 28` e `TaskMoveService.php:59-60`;
  - área e tipo: `TaskTagService.php:103-104, 120-121`;
  - card pai, tarefa de origem e capa: `TaskEditService.php:84-85, 100-101`;
  - dev e QA: `TaskAssignmentService.php:54, 111` e `SprintTasksBatchRequest.php:66, 80-85`;
  - participante: `TaskTagService.php:77-78`;
  - critério pai: `CriterionService.php:38`;
  - etapa de destino ao remover etapa: `BoardStageService.php:249-253`;
  - ordem das etapas: `BoardStageService.php:145-150`;
  - card posto na sprint: `SprintTaskRequest.php:29` e `SprintTaskController.php:33`;
  - contato do card de suporte: `TaskService.php:79`, só os contatos do cliente do projeto;
  - destinos ao encerrar a sprint: `SprintService.php:123-124`, só as tarefas pendentes da sprint.
- **Filtros por id nas telas.** Ficam restritos ao escopo do usuário:
  - `ProjectQuery.php:32, 61` (`visibleTo`);
  - `ContactController.php:36`;
  - `TimeEntryQuery.php:29, 60`;
  - `IndicatorsScreen.php:32` (abas do gestor só com `viewIndicators`) e `IndicatorsScreen.php:71`;
  - `SprintScreen.php:37`.
- **Ressalva (achado 6).** Para avisar que alguém não está alocado no projeto, o `TaskAssignmentService` lê o nome de qualquer usuário pelo id enviado.

## 3. Toda entrada passa por FormRequest? Upload validado no servidor?

**Resposta:** em parte. Toda escrita passa por `FormRequest` (A10 verde). O upload é validado pelos bytes. Os GET de lista quebram com parâmetro em array (achado 5).

- **Escritas.** Os 32 `FormRequest` estão em `app/Domains/Projetos/Requests/` e `app/Platform/Requests/`. Os POST, PUT, PATCH e DELETE que não têm `FormRequest` recebem só o registro da rota e autorizam (`deactivate`, `reactivate`, `destroy`, `approve`/`reject`). O A10 (`tests/Arch/RotasAutorizadasTest.php`) passou.
- **GET de lista.** A coerção é manual (`Busca::termo` com teto de 200, `in_array`, `integer()`). O `$request->validate` só aparece em GET (`BoardController.php:45`, `IndicatorsController.php:36`) e o `Validator::make` em `AuditLogQuery.php:50`. Isso está de acordo com a spec.
- **Falha nos GET.** Um parâmetro de query enviado como array (`?search[]=x`) é convertido para string com `(string)`. O PHP emite o aviso "Array to string conversion" e o `HandleExceptions` do Laravel (`vendor/.../Bootstrap/HandleExceptions.php:75-76`) o transforma em exceção, o que dá 500. Medido na rota pública `GET /admin/reset-password/abc?email[]=x`: 500, sem stack no corpo com `APP_DEBUG=false` (achado 5).
- **Upload, regras.** `StoreAttachmentsRequest.php:26-27` aplica `files` (até `max_files`, 10), `max:` (20480 KB) e `mimes:` com a lista de `config/hub.php:24`: sem `svg`, `html`, `htm`, `xml` e `php`.
- **Upload, tipo pelos bytes.** O `mimes` do Laravel compara `guessExtension()`, que lê o conteúdo e não a extensão do cliente. `AttachmentService.php:39` grava com `$file->extension()` (também pelo conteúdo) e com nome UUID. `AttachmentService.php:44` grava o `mime_type` de `getMimeType()` (finfo).
- **Upload, disco.** É privado: `config/filesystems.php:35-36`, `root` em `storage/app/private`, com `serve => false`.
- **Download.** `TaskDetailController.php:134-144` faz o `belongsToCard`, autoriza `view` e chama `AttachmentService::download` (`AttachmentService.php:81-94`). A resposta leva:
  - o `Content-Type` gravado;
  - `X-Content-Type-Options: nosniff`;
  - `inline` só quando o `mime_type` começa com `image/` (`TaskAttachment.php:40-43`), e o resto sai como `attachment`.
  
  SVG e HTML nunca chegam ao disco, porque o `mimes` os recusa. A CSP global também vale para essa resposta.
- **Renderização.** Não há `dangerouslySetInnerHTML` nem `{!!` em `resources/`. O link do PR é validado com `url:http,https` (`CardTextRequest.php:41`), então não aceita `javascript:`.

## 4. Alguma regra de negócio está só na tela?

**Resposta:** não encontrei. Comparei cada `ability` enviada ao front com a Policy e o Service que efetivam a ação:

- **`HandleInertiaRequests.php:63-76`.** Cada ability vem de `$user->can(...)` sobre a Policy real; `delete_records` e `reset_passwords` vêm de `isAdmin()`. As Policies e as rotas exigem o mesmo: `UserPolicy.php:44-53` e `ProjectPolicy.php:55-58`.
- **Quadro (`TaskPresenter.php:124-134`).**
  - `create_card` e `create_support` vêm falsos com o projeto encerrado. O servidor recusa do mesmo jeito em `TaskService.php:111-113` (`lockOpenProject`).
  - `manage_sprint` corresponde a `TaskPolicy::move` (`TaskPolicy.php:63-68`): cruzar o Backlog pede `manageSprint`, conferido no `MoveTaskRequest.php:17-19`.
  - `archive` vem `false` até o PR 3, que não tem rota.
- **Ficha (`TaskDetailPresenter.php:150-164`).**
  - `set_hours`: `UpdateTaskRequest.php:18-20` exige `manageSprint` quando vem `estimated_hours`.
  - `propose_hours`: `ProposeHoursRequest.php:13` mais `TaskPolicy.php:77-80`.
  - `can` do comentário: `TaskCommentPolicy.php:26-29`.
  - Critério já testado: `CriterionService.php:85-88`.
  - Card arquivado: `WritesTasks::lockTask` em `app/Domains/Projetos/Services/Concerns/WritesTasks.php:22-29`.
- **Sprints (`SprintScreen.php:50-71`) e indicadores (`IndicatorsScreen.php:32`).** O servidor nem monta o que o usuário não pode ver.
- **Horas (data não futura, de 0 a 24 h).** Valem no `StoreTimeEntryRequest.php:29-30`.
- **Último administrador, rebaixamento e autoexclusão.** `UserService.php:112, 249-256` e `UserPolicy.php:44-47`.

## 5. Algum segredo no código, no front, no git ou em documento?

**Resposta:** não.

- **Histórico do git.** `gitleaks git --redact` nos 358 commits: nenhum vazamento, com `.gitleaks.toml` (allowlist só de `.env.testing` e `.env.e2e.example`).
- **`.env.example`.** Só valores vazios, `null` ou o banco local do `docker-compose` (`DB_PASSWORD=wcj_hub_local`, também em `.github/workflows/ci.yml:58, 121` para o MySQL efêmero do CI). `APP_KEY` e as chaves do Passport ficam vazias.
- **Código.** Nada de chave, token ou senha literal em `app/`, `config/`, `resources/js/` e `database/`. O `UserFactory.php:28` usa a senha `password` só em factory de teste. O `ExemploSeeder.php:130` usa `Str::password(32)`.
- **`docs/DEPLOY.md`.** Cita só o nome das variáveis e o item do cofre (linhas 37-67), sem valor, sem host interno e sem usuário de banco com senha.
- **`.specs/` e `.github/`.** Nada encontrado pela busca de padrões.
- **Bundle do front.** O `HandleInertiaRequests.php:56-77` compartilha só id, nome, e-mail, papel, `must_change_password` e as abilities. As telas recebem linhas montadas pelos Presenters, não o Model inteiro: por exemplo `TaskDetailPresenter.php:236-239` manda só `id` e `name` da pessoa. Não há Ziggy nem mapa de rotas. O único `import.meta.env` é o `VITE_APP_NAME` (`resources/js/app.tsx:8`).

## 6. Login e recuperação de senha têm limite de tentativas?

**Resposta:** sim para o limite. Não para "sem enumeração": o tempo de resposta e o link do e-mail ainda revelam a conta (achados 1 a 4).

- **Limitador `login`.** `AppServiceProvider.php:95-107` tem duas chaves: IP + conta (5/min) e IP (20/min), em `config/auth.php:128-131`. Está aplicado em:
  - `POST admin/login` (`routes/web.php:42`);
  - `POST admin/forgot-password` (`routes/web.php:47`);
  - `POST admin/reset-password` (`routes/web.php:52`);
  - `PUT admin/first-access` (`routes/web.php:68`).
- **Limitador `senha-atual`.** 5/min por usuário, só quando há senha nova (`AppServiceProvider.php:111-119`), em `PUT admin/profile` (`routes/web.php:81`).
- **Resposta 429.** `ThrottledAuthResponse.php:35-69` responde 429 com a mesma mensagem de credencial inválida.
- **Testes dinâmicos.**
  - `tests/Feature/LoginThrottleTest.php:31, 60, 72, 81, 90, 112`: 6ª tentativa, outra conta, teto de 20, esqueci, redefinir, primeiro acesso.
  - `tests/Feature/ProfilePasswordTest.php:28-137`: senha atual errada, 6ª troca dá 429, limite por usuário.
- **Mensagens.** São iguais para quem tem e quem não tem conta: `AuthenticatedSessionController.php:26, 43, 51`, `ForgotPasswordController.php:19, 37` e `NewPasswordController.php:19, 36, 56`.
- **Token de redefinição.** Expira em 60 minutos (`config/auth.php:99`). É conferido com hash dentro do broker. A redefinição encerra todas as sessões (`NewPasswordController.php:49`). O perfil e o primeiro acesso encerram as outras sessões (`ProfileController.php:58-60`, `ChangePasswordController.php:53`).
- **Tempo.**
  - O login ficou mais lento justamente para o e-mail sem conta (achado 2).
  - O "esqueci a senha" e o "redefinir" só gastam o Timebox e o bcrypt para conta ativa (achado 3).
  - A conta desativada com senha certa muda o cookie de sessão (achado 4).
- **Link do e-mail.** O link de redefinição segue o header `Host` da requisição (achado 1).

## Achados

| # | Severidade | Onde | Cenário | Correção proposta | Estado |
|---|---|---|---|---|---|
| 1 | alta | `app/Platform/Models/User.php:63-66` (`url(route(..., false))`). Não há `trustHosts` nem `forceRootUrl` em `bootstrap/app.php:21-46` e `AppServiceProvider.php`. O bloco Nginx de referência em `docs/DEPLOY.md:122-161` não tem `server_name` nem servidor padrão que recuse outro Host. Mesma causa em `TaskAssigned.php:34`, `TaskReturned.php:36` (`Task::url()`, `Task.php:212-215`) e `TemporaryPasswordMail.php:37` | Sem login, alguém faz `POST /admin/forgot-password` com o e-mail da vítima e o header `Host: atacante.example`. O e-mail legítimo chega com `https://atacante.example/admin/reset-password/<token>?email=...`, e um clique entrega o token: a conta é tomada, inclusive a de administrador. Medido: com `APP_URL=https://hub.example.com` e `Host: evil.example`, o link sai `https://evil.example/admin/reset-password/TOKEN?...`. Os avisos TaskAssigned e TaskReturned também herdam o Host de quem age, e um usuário logado consegue mandar a um colega um link para outro domínio | (a) Em produção, fixar a raiz das URLs no `APP_URL`: `URL::forceRootUrl(config('app.url'))` e `URL::forceScheme('https')` no `AppServiceProvider::boot`. Ou montar o link com `config('app.url')` em `sendPasswordResetNotification`. (b) `$middleware->trustHosts(at: [<host do APP_URL>])` no `bootstrap/app.php`. (c) No `DEPLOY.md`, `server_name <host>;` e um `default_server` que responde 444. (d) Teste de feature: `POST forgot-password` com `Host` estranho e `Mail::assertQueued` conferindo que o link começa com `config('app.url')` | em aberto, com correção proposta. Dono: Lucas Scholz com IA, no próprio B9 (a correção mexe em autenticação: espera o ok do Lucas Scholz) |
| 2 | média | `app/Platform/Controllers/Auth/AuthenticatedSessionController.php:40-41, 70-80`, combinado com `vendor/laravel/framework/src/Illuminate/Auth/SessionGuard.php:431-454` (`attempt` em Timebox de 200 ms, sem retorno antecipado na falha) | O hash descartável roda depois que o `Auth::attempt` já esperou o Timebox. Com e-mail sem conta, o tempo é Timebox (≥ 200 ms) + bcrypt. Com e-mail cadastrado e senha errada, é max(200 ms, bcrypt). Medido com o Timebox do vendor e bcrypt custo 12: cerca de 470-500 ms sem conta contra cerca de 275-300 ms com conta. A correção do SEC-06 inverteu o oráculo e o deixou maior (antes era cerca de 70 ms, agora cerca de 200 ms). Uma requisição por e-mail basta, e o teto de 20/min por IP só reduz o ritmo | Uma conferência de bcrypt em todos os caminhos, dentro de um Timebox só. Buscar o usuário com `Auth::getProvider()->retrieveByCredentials(['email' => ...])` e conferir com `Hash::check($senha, $user?->getAuthPassword() ?? $dummy)`. Só então fazer `Auth::login`, ou usar `Auth::attemptWhen`. Envolver o caminho de falha num `Timebox` acima do custo do bcrypt (por exemplo 500 ms), com `returnEarly()` só no sucesso. O teste troca o `Timebox` por um dublê e confere que a falha com e sem conta passa pelo mesmo `call` | em aberto, com correção proposta. Dono: Lucas Scholz com IA, no próprio B9 (a correção mexe em autenticação: espera o ok do Lucas Scholz) |
| 3 | média | `app/Platform/Controllers/Auth/ForgotPasswordController.php:28-35` e `NewPasswordController.php:33-37`, com `vendor/.../Auth/Passwords/PasswordBroker.php:84, 124` (Timebox de 200 ms) e `vendor/.../Auth/Passwords/DatabaseTokenRepository.php:71` (bcrypt do token). O comentário de `app/Mail/ResetPasswordMail.php:14-16` e o `docs/DEPLOY.md:112` afirmam o contrário | O controller só chama o broker quando a conta existe e está ativa. No "esqueci a senha", a conta ativa custa Timebox + bcrypt do token (cerca de 260 ms nesta máquina), e o e-mail sem conta custa um `SELECT`. No "redefinir", a conta ativa custa pelo menos o Timebox de 200 ms, e a inexistente ou inativa volta na hora com `INVALID_LINK`. Pôr o e-mail na fila (SEC-07) tirou o SMTP, mas a diferença de cerca de 200 ms ou mais continua. As duas rotas são públicas | Envolver a ação inteira, e não só o broker, num `Illuminate\Support\Timebox` com duração acima do pior caso (por exemplo 500 ms), igual para conta ativa, inativa e inexistente. Corrigir o comentário do `ResetPasswordMail` e a linha 112 do `DEPLOY.md`. Teste com o `Timebox` dublê | em aberto, com correção proposta. Dono: Lucas Scholz com IA, no próprio B9 (a correção mexe em autenticação: espera o ok do Lucas Scholz) |
| 4 | baixa | `app/Platform/Controllers/Auth/AuthenticatedSessionController.php:40-51`, com `SessionGuard.php:600-605` (`regenerate(true)` no `login`) | Conta desativada ou sem papel de backoffice com a senha certa: o `Auth::attempt` loga, o que troca o id da sessão, e o controller desloga em seguida. A mensagem é a mesma, mas o cookie de sessão volta com outro id, o que não acontece com senha errada. Quem compara o cookie confirma que a senha é a certa para uma conta desativada (senha reaproveitada em outro lugar). Lido no código, não medido | Trocar por `Auth::attemptWhen($credentials, fn (User $u) => $u->isBackofficeUser() && $u->isActive())`, que não loga quando a condição falha. Junto com o achado 2, o caminho fica um só | em aberto, com correção proposta. Dono: Lucas Scholz com IA, no próprio B9 (a correção mexe em autenticação: espera o ok do Lucas Scholz) |
| 5 | baixa | `app/Support/Busca.php:18`, `app/Platform/Controllers/Auth/NewPasswordController.php:26`, `app/Domains/Projetos/Controllers/TimeEntryController.php:77`, `app/Platform/Controllers/Admin/ClientController.php:33`, `app/Providers/AppServiceProvider.php:96` (chave do limitador `login` com `email` em array) | Parâmetro enviado como array (`?search[]=x`, `?email[]=x`, `email[]=x` no POST do login): `(string)` de array vira `ErrorException` e a resposta é 500. Medido sem login: `GET /admin/reset-password/abc?email[]=x` dá 500, e com `email=a@b.com` dá 200. Sem vazamento: com `APP_DEBUG=false`, o corpo não traz classe nem stack. O efeito é um 500 por requisição no log de erro e um alarme falso de incidente. O resto é leitura do código (lista, horas, clientes, limitador) | Ler texto de query com `$request->query($k)` testando `is_string()` antes, ou com `$request->string()` só depois de validar. No `Busca::termo`, devolver `''` quando o valor não for string. No limitador, `is_string($request->input('email')) ? ... : ''`. Um teste por rota pública com parâmetro em array esperando 200, 302 ou 422, nunca 500 | em aberto, com correção proposta. Dono: Lucas Scholz com IA, no próprio B9 (a correção mexe em autenticação: espera o ok do Lucas Scholz) |
| 6 | baixa | `app/Domains/Projetos/Services/TaskAssignmentService.php:111-114` | Quem pode editar a ficha (qualquer alocado, `TaskPolicy::update`) manda `PATCH /projetos/{id}/cards/{chave}` com `developer_id = N`. A recusa devolve "<Nome do usuário N> não está alocado neste projeto". Percorrendo N, um dev lista nome e existência de todos os usuários, inclusive desativados e de outros projetos, embora não tenha acesso à lista de usuários (`UserPolicy::viewAny` = admin) | Mensagem sem o nome quando a pessoa não é do projeto ("A pessoa escolhida não está alocada neste projeto"), ou o nome lido só entre os membros do projeto | em aberto, com correção proposta. Dono: Lucas Scholz com IA, no próprio B9 (a correção mexe em autenticação: espera o ok do Lucas Scholz) |

Dono preenchido em 21/10/2026 por quem conduz o B9 (Lucas Scholz com IA). As correções entram no próprio B9, depois do ok do Lucas Scholz, como tarefas T14 em diante do `tasks.md`.

## Conferido de passagem

- **Headers.** `app/Http/Middleware/SecurityHeaders.php` é global (`bootstrap/app.php:26`, `$middleware->append`).
  - Medido no 200 e no 500 de `/admin/reset-password/*` e no `/admin/login`: CSP presente e nenhum `X-Powered-By`.
  - A classe põe CSP com `default-src 'self'` e `script-src` com nonce, `Referrer-Policy`, `Permissions-Policy`, `X-Frame-Options: SAMEORIGIN` e `nosniff`, e o HSTS só em produção (`SecurityHeaders.php:26-44`). Ela remove `X-Powered-By` do objeto e do SAPI (`SecurityHeaders.php:34-38`).
  - `style-src 'unsafe-inline'` é decisão registrada na spec (fora do escopo).
- **CORS.** `config/cors.php`: só `api/health`, `GET`, origens vindas de `CORS_ALLOWED_ORIGINS` (vazio por padrão), sem `*` e sem credenciais.
- **Sessão.** `config/session.php`:
  - `secure` vem ligado por padrão em `production` (linha 172);
  - `http_only` é `true` (185) e `same_site` é `lax` (202);
  - o driver é `database` (21), o que o `SessionService::terminateOthers` exige (`SessionService.php:17-25`);
  - o `app:conferir-producao` recusa `SESSION_SECURE_COOKIE` desligado (`docs/DEPLOY.md:55, 78`).
- **TrustProxies.** `app/Http/Middleware/TrustProxies.php:16-36` lê `TRUSTED_PROXIES` (`config/app.php:46`). Sem valor, não confia em ninguém, e `X-Forwarded-Host` e `X-Forwarded-For` são ignorados. Observação, não achado: `docs/DEPLOY.md:166` manda pôr o IP do Nginx quando Nginx e PHP-FPM ficam em containers separados. Por FastCGI, o PHP recebe `REMOTE_ADDR` do cliente e não do Nginx, então esse valor não tem efeito. O risco aparece se alguém, por falta de efeito, trocar para `*`: com isso, o `X-Forwarded-For` forjado escapa do teto por IP. Vale reescrever a frase no B10.
- **`docs/DEPLOY.md`.**
  - Sem valor de segredo, host interno ou usuário com senha.
  - Bloqueio de caminho com ponto e de backup (`DEPLOY.md:135-145`), `server_tokens off`, `fastcgi_hide_header X-Powered-By` e `expose_php = Off`.
  - Falta o `server_name` e o servidor padrão (achado 1).
  - A linha 117 já registra que o token de redefinição fica em claro no payload do job até o envio.
- **Rota `storage/{path}`.** Desligada (`config/filesystems.php:36`). O `route:list` não a lista.
- **Mensagem do 403 do `EnsureBackofficeUser`.** Texto fixo, sem dado (`EnsureBackofficeUser.php:28`).

## Não conferido

- **`.env.testing` e `.env.e2e.example`.** Não lidos, pela regra da tarefa de não ler `.env*`. Estão na allowlist do gitleaks, e o `AGENTS.md` os descreve como chave de teste e senha do MySQL local.
- **Tempo de ponta a ponta dos endpoints de login, esqueci e redefinir.** Não medido, porque precisaria de banco e gravaria sessão e token. Os achados 2 e 3 vêm do código do controller e do vendor, mais o microbenchmark do Timebox e do bcrypt nesta máquina. O achado 4 é leitura do código.
- **Achado 5 nas rotas autenticadas.** Não exercitado por HTTP, porque precisaria de usuário e banco. A rota pública foi medida e as demais vêm da leitura das mesmas linhas.
- **Rotas `/oauth/*` e `/mcp`.** Não existem nesta base (`Passport::ignoreRoutes`, `AppServiceProvider.php:68`). Ficam para o B8 (`nota-para-o-b8.md`).
- **Nginx, TLS, firewall, `real_ip` e sonda de `.env` e `.git` em servidor de verdade.** É o B10. Só o bloco de referência do `DEPLOY.md` foi lido.
- **SCA e SAST.** Não refeitos. Estão no `sca-2026-10-21.md`, fora desta revisão.
- **Conteúdo dos templates de e-mail** (`resources/views/mail/*`). Não lido além da busca por `{!!`, que não achou nada.
- **Templates Blade e componentes React fora dos citados.** Lidos só pela busca por `dangerouslySetInnerHTML`, `{!!`, `VITE_` e padrões de segredo.
