# Padrões do Projeto — aprendidos via code review

> Mantido pela skill `wcj-code-review`. Editável à mão.
> Atualizado ao final de cada ciclo de review.
>
> Esta cópia, na `feat/b2-projeto-e-configuracao`, é a da `main` mais o que os ciclos 1 (do lado do Vitinho) e 2 do PR-6 anotaram (CV-13, AP-05 ocorrência, DA-11, DA-12, FP-07 e o bloco do PR-6); a versão própria que a branch trazia (30 linhas) foi absorvida aqui. O ciclo 1 do PR-8 (`chore/marca-wcj`) acrescentou CV-12, AP-05, AP-06, DA-09, DA-10, FP-06 e o bloco "Registrado sem achado no ciclo 1 do PR-8"; o resto é do B1 (ciclos 1 a 6). Os caminhos de componente citados nos itens do B1 são os de antes do ADR-003 (`components/ui/`, `components/*.tsx`); desde o rebase do #8 valem os `w-*` da tabela do `AGENTS.md`.

**Última atualização:** 2026-10-22 · **Reviews acumulados:** 12 (b1-nucleo ciclos 1 a 6; pr-8 ciclo 1; pr-6 ciclos 1 e 2; b3-quadro-e-card ciclo 1 (PR-17); b3-ficha ciclo 1 (PR-18); b3-arquivar-e-avisos ciclo 1 (PR-22); a revisão de 07/10 em `docs/revisoes/` é anterior à skill)

---

## Convenções confirmadas

### CV-01 — Negação auditada passa só por `AuditsDeniedAbilities`
- **Escopo:** `app/Http/Controllers/Controller.php::authorize()` e os FormRequests com `AuthorizesBeforeValidation`
- **Evidência:** `app/Http/Concerns/AuditsDeniedAbilities.php:18-28`; `app/Platform/Requests/Concerns/AuthorizesBeforeValidation.php:18-23`
- **Visto em:** b1-nucleo (ciclos 1 e 2). FormRequest que valida payload autoriza antes de validar; o controller **não** chama `authorize()` de novo nessas ações. Desde o ciclo 2 a checagem + gravação do `denied` mora em `AuditService::inspectFor()` (`AuditService.php:52-61`), usada pelo trait e pelos `choiceList()` dos Services; `AuditService::denied()` direto é desvio

### CV-02 — Recusa de regra do Service é `DomainException` e vira toast pelo `attempt()`
- **Escopo:** controllers do Núcleo
- **Evidência:** `Controller.php:39-48` (`attempt(Closure $action, string|Closure $success)`: a closure de sucesso recebe o retorno da ação, ex. o aviso do contato em `ContactController.php:57-60`)
- **Visto em:** b1-nucleo. "Já usado", último administrador, WCJ e anonimizado respondem toast de erro, não 403 (D6 do Lucas, 07/10). Ação de escrita que pode lançar `DomainException` fora do `attempt()` é desvio (CR-1-03, resolvido no ciclo 2). Erro de banco (deadlock, unique não tratado) **não** passa pelo `attempt()`: vira 500 (CR-2-01)

### CV-03 — Trava "um só" é `lockForUpdate` dentro da transação, com teste de intercalação
- **Escopo:** `app/Platform/Services/**`
- **Evidência:** `UserService.php:230-241`; `ContactService.php:240-255` (`lockedNotAnonymized`, `lockedForWrite`); `tests/Support/Concorrencia.php`; `UltimoAdminConcorrenciaTest.php`, `ContactAnonimizadoConcorrenciaTest.php`, `ClientRepetidoConcorrenciaTest.php`
- **Visto em:** b1-nucleo (ciclos 1, 2 e 3). Decidir **e escrever** pela linha relida com `lockForUpdate()` (`$locked`), nunca pela instância em memória: os `reactivate` fazem isso desde o CR-2-06; os `deactivate` decidem pelo `$locked` mas ainda gravam pela memória (CR-3-01). Snapshot de `before` também sai da linha travada (CR-3-02). **Ordem das travas importa:** quem trava várias linhas trava sempre na mesma ordem (no `UserService`, os admins ativos antes do alvo); a FK `audit_logs.user_id` e `deactivated_by` pega S na linha do ator e conta como trava (CR-3-03). **O `Concorrencia::intercalarDepoisDe` não reproduz deadlock** (a principal fica parada no PHP) e `bloqueada` aceita 1205 e 1213 juntos: ordem de trava se prova pela sequência de consultas `for update` (ex.: `UserServiceTest::test_desativar_admin_trava_os_administradores_antes_do_alvo`), não pelo teste de corrida (CR-3-04). **Desde o ciclo 4:** os três `deactivate` e `reactivate` decidem, gravam e auditam pelo `$locked` (CR-3-01); o `ensureNotLastActiveAdmin` relê o papel do banco e decide pelo conjunto travado (`UserService.php:255-268`); as escritas do `UserService` usam `DB::transaction(..., self::ATTEMPTS)` (CR-3-03), e o Laravel repete no 1213 **e no 1205**. **Callback repetido tem de ser repetível:** instância em memória salva dentro dele precisa ser restaurada no início do callback (`update`, `UserService.php:102-108`) ou relida; senão o `syncOriginal` da tentativa desfeita faz a repetição não gravar a coluna (CR-4-01, `resendInvite`). E-mail e efeito fora do banco ficam depois do commit. **Desde o ciclo 5:** a repetição é o `RetriesDeadlock` (CV-11), não mais `DB::transaction(..., self::ATTEMPTS)`, e repete só o 1213; ativar e desativar passam pelo `setActiveLocked` (CV-10). Restaurar a memória deixa o callback repetível em relação à instância **de chegada**, não ao banco: se a decisão de gravar depende do "sujo" da instância (`forceFill` de valor que a memória já tem), releia com trava (CR-5-01, `resendInvite`). **Desde o ciclo 6:** o `resendInvite` relê com `lockForUpdate` dentro do callback (`UserService.php:83`), audita e manda o e-mail pelo `$locked` (`:86,91`); só o `update` ainda restaura a memória (registrado no ciclo 4). **Releitura com trava pede teste que discrimine a trava, não só a releitura:** teste que muda o banco antes da chamada passa com releitura sem `lockForUpdate`; contar a consulta `for update` (molde `UserServiceTest.php:534`) ou intercalar depois do `SELECT` (CR-6-02)

### CV-04 — Dado de outro domínio entra no Núcleo por contrato em lote, memorizado por requisição
- **Escopo:** `app/Platform/Contracts/ClientManagement`, `MemoizedClientManagement` (ligação `scoped`)
- **Evidência:** `ClientController.php:107-115`; `MemoizedClientManagement.php:27-30`; `ClientManagementPorRequisicaoTest.php`
- **Visto em:** b1-nucleo. Lista pede em lote (`openProjectsCountByClient`, `managedClientIds`), nunca por linha.

### CV-05 — Linha de lista num Presenter, com o `can` calculado pela Policy
- **Escopo:** `app/Platform/Services/{User,Client,Contact,AuditLog}Presenter.php`
- **Evidência:** `UserPresenter.php:28-34`; `ClientPresenter.php:52-57`; `ContactPresenter.php:28-34`; `AGENTS.md` "Um jeito só" (linha de lista)
- **Visto em:** b1-nucleo (ciclos 1, 2 e 3; Usuários alinhado no CR-1-08, os quatro no Presenter desde o CR-2-12). A tela lê `row.can.*`; ability global (`delete_records`, `reset_passwords`) não decide botão de linha; controller só chama `->row()`. Montar linha no controller é desvio

### CV-06 — Busca com `LIKE` só por `App\Support\Like::contains()`
- **Escopo:** toda busca textual dos Services e do `AuditLogQuery`
- **Evidência:** `app/Support/Like.php:13`; `UserService.php:189-190`, `ClientService.php:172-174`, `ContactService.php:186-188`, `AuditLogQuery.php:167-176`
- **Visto em:** b1-nucleo ciclo 2 (CR-1-18). `"%{$search}%"` à mão é desvio (o `%` e o `_` digitados viram curinga). Teste de busca fixa toda coluna que a busca lê (L-014; CNPJ aleatório da factory deixava o teste intermitente); desde o ciclo 6 o teste do CR-1-18 cobre só `name`, sem caso fixo no `document` (CR-6-03)

### CV-08 — Unique do banco capturado só pelo trait `RefusesDuplicates`
- **Escopo:** gravações dos Services que têm unique no banco
- **Evidência:** `app/Platform/Services/Concerns/RefusesDuplicates.php:25-31`; `UserService::emailTaken()` (confere o nome do índice); `ClientService::duplicateRefusal()` (relê o banco)
- **Visto em:** b1-nucleo ciclo 3 (CR-2-11). A `$refusal` devolve `null` para unique que não reconhece e o trait relança o original. `catch (UniqueConstraintViolationException)` à mão é desvio

### CV-09 — Ação de linha pelo `useRowAction`
- **Escopo:** botões de linha das listagens (Reativar, Desativar, Excluir…)
- **Evidência:** `resources/js/hooks/use-row-action.ts`; `RowActions busy`/`disabled`; `e2e/clique-duplo.spec.ts`
- **Visto em:** b1-nucleo ciclo 3 (CR-2-03, CR-2-07). Guarda por ref (dois cliques no mesmo quadro), outras linhas desabilitadas enquanto uma roda, liberação no `onFinish`. `useState` de `busy` copiado na página é desvio. Nota: a trava liga antes do `onStart`; ouvinte `router.on('before')` que cancele visita a prenderia. **PR-8 ciclo 1:** a `w-users-page.tsx` do #8 (base B0) usava `useState(busy)`; o rebase do #8 trouxe a versão do B1 com `useRowAction` (CR-1-02 do PR-8)

### CV-10 — Ativar e desativar só por `Deactivatable::setActiveLocked`
- **Escopo:** `deactivate`/`reactivate` de todo cadastro com `Deactivatable`
- **Evidência:** `app/Platform/Models/Concerns/Deactivatable.php:136-153`; chamadas em `UserService.php:143,155`, `ClientService.php:109,117`, `ContactService.php:104,113`; `SetActiveLockedTransacaoTest.php`
- **Visto em:** b1-nucleo ciclo 5 (CR-4-05). Relê com `lockForUpdate`, decide e grava pela linha travada e passa o `$locked` ao `$then` (auditoria e efeito próprio). Esqueleto trava-decide-`setActive`-`save`-`log` copiado num Service é desvio. Exceções conhecidas: `ContactService::anonymize` (outro fluxo) e `LimparExemploCommand` (CLI sem ator). Desde o ciclo 6 abre a própria `DB::transaction` (savepoint dentro da de quem chama), como o `deleteIfUnused` (CR-5-02); provado fora de transação, com `DatabaseTruncation` e `$then` que lança, pelo `SetActiveLockedTransacaoTest` (o `RefreshDatabase` esconde a falta de transação). Deadlock no savepoint sobe como `DeadlockException` com o 1213 dentro e o `RetriesDeadlock` repete (mesmo caminho do `UserDeadlockRepeteTest.php:108`). O `DB::transaction` de fora em `ClientService.php:109,117` e `ContactService.php:104` ficou redundante, sem efeito; o do `UserService` é o que repete. O `AGENTS.md:230` ainda diz "dentro da transação de quem chama" (CR-6-01)

### CV-11 — Repetição de transação só no deadlock, pelo `RetriesDeadlock`
- **Escopo:** escritas que travam linha de usuário (`UserService`)
- **Evidência:** `app/Platform/Services/Concerns/RetriesDeadlock.php:31-44`; `UserDeadlockRepeteTest.php:76,89,108,123,146`; `AGENTS.md:231`
- **Visto em:** b1-nucleo ciclo 5 (CR-4-04, Fix 6 da rodada 10). Lê `errorInfo[1]` (no `DeadlockException` aninhado, o do `getPrevious()`), repete só o 1213, até 3 tentativas, e relança dentro de transação aberta por fora. `DB::transaction(..., attempts)` do Laravel numa escrita de usuário é desvio (repete o 1205). Client e Contact sem repetição seguem coerentes (ciclo 4)

### CV-07 — Estado de lista pelo `ListBody`; texto de situação pelo `lib/situation.ts`
- **Escopo:** páginas de listagem em `resources/js/pages/admin/**`
- **Evidência:** `components/list-body.tsx`; `lib/situation.ts`; tabela "Componentes e hooks do projeto" no `AGENTS.md`
- **Visto em:** b1-nucleo ciclo 2 (CR-1-09, CR-1-22). Lista nova não reescreve erro/carregando; componente novo entra na tabela do `AGENTS.md` no mesmo commit. **PR-8 ciclo 1:** o #8 (base B0) não tinha `ListBody` nem `useListVisit`; o rebase portou como `WListBodyMolecule` (CR-1-02 do PR-8)

---

### CV-12 — Front em Atomic Design com shadcn em `components/shadcn/`; marca só por token, `text-primary-text` e `WBrandLogoAtom`
- **Escopo:** `resources/js/components/**`, `.storybook/`, `design/tokens.json`, `public/brand/`
- **Evidência:** `docs/adr/003-componentes-atomicos-e-storybook.md`; `docs/adr/005-marca-wcj.md`; `resources/js/components/README.md`; `AGENTS.md:112-131` (branch do #8); `rg "components/ui/|@/layouts/" resources/js e2e .storybook` vazio; `rg "text-primary([^-]|$)" resources/js` vazio; `cmp public/brand/*.svg docs/brand/logo/*` idênticos
- **Visto em:** pr-8 ciclo 1 (aprovado pelo Lucas Scholz em 09/10). Primitivo novo só por `npm run shadcn -- add`; componente do Hub em `components/{atoms,molecules,organisms,templates,pages}/w-<nome>-<nível>/` com `.tsx` + `.md` (+ `.stories.tsx` quando há o que ver); `resources/js/pages/*.tsx` é reexport de uma linha; layouts são os templates; Select, Sidebar, Table, Pagination, Sonner e `w-date-picker-molecule` são os do shadcn (nada de `<input type="date">`); texto azul é `text-primary-text`, nunca `text-primary`; logo só pelo `WBrandLogoAtom` com os SVG oficiais sem retoque; hex inline só nos Blade de e-mail. Desvio: `components/ui/`, `@/layouts/`, hex em componente, `text-[N]`, logo desenhado à mão, primitivo customizado sobrescrito pelo CLI. O `.ai/stacks/ui.md` e o `laravel-inertia.md` ainda citam o modelo antigo (são do template, não se editam aqui): o `AGENTS.md` prevalece

---

### CV-13 — Quadro padrão (P10) mora só em `BoardStageService::DEFAULT_BOARD`; "gere" de projeto é papel global + vínculo; "nunca usado" de projeto = sem tarefa, sprint e pacote
- **Escopo:** `app/Domains/Projetos/Services/BoardStageService.php`, `ProjectPolicy::manages`, `Project::scopeVisibleTo`, `ProjectService::delete`
- **Evidência:** PR-6 `BoardStageService.php:17-26` (`ProjectService`, `ExemploSeeder` e `ProjectFactory::withBoard()` o chamam); `ProjectPolicy.php:51-58`; `Project.php:116-128`; `ProjectService.php:88-103`
- **Visto em:** pr-6 ciclos 1 e 2. `project_members` **não tem coluna de papel**: "alocado com papel gestor" = papel global do usuário (`isProjectManager()`) + vínculo, como o `banco.md` diz; a lista (`scopeVisibleTo`) espelha a Policy `view`. "Nunca usado" de projeto não é o `isReferenced()` genérico (`board_stages`, `project_areas` e `project_members` sempre referenciam e saem por cascade). Lista de 8 etapas copiada em outro lugar é desvio

### CV-14 — Escrita de card só pelos três Services do B3, com a ordem de travas projeto → card
- **Escopo:** `app/Domains/Projetos/Services/{TaskService,TaskMoveService,TaskTagService}.php` e o trait `Services/Concerns/WritesTasks.php`
- **Evidência:** `TaskService.php:25-27` (ordem das travas), `WritesTasks.php:22-30` (`lockTask`: relê com `lockForUpdate`, arquivado não muda), `TaskMoveService.php:55-58`
- **Visto em:** b3-quadro-e-card ciclo 1. Criar pelo `TaskService`, mudar de etapa ou de posição só pelo `TaskMoveService` (que chama o `SprintTaskService` do B5 ao cruzar o Backlog), etiquetas e participante pelo `TaskTagService`. Os três travam a linha do projeto (`SprintService::lockProject`) e depois a do card; movimento (`task_stage_movements`) e auditoria na mesma transação; aviso (`TaskReturned`) só depois do commit. `board_stage_id` à mão fora disso é desvio

### CV-15 — Card na rota pela chave (`SGM-17`), resolvido pelo `Route::bind('card')` só entre os cards do projeto da rota
- **Escopo:** `app/Providers/AppServiceProvider.php::registerCardBinding()`; rotas `projects.cards.*`
- **Evidência:** `AppServiceProvider.php:124-135`; `TaskPolicyTest::test_a_card_key_of_another_project_is_404_before_the_policy`
- **Visto em:** b3-quadro-e-card ciclo 1. Chave de outro projeto, prefixo errado ou número inexistente é 404 antes da Policy (sem linha `denied`). O binder recebe `{project}` ainda como id (os binders explícitos rodam antes do implícito) e resolve o projeto ele mesmo. Os FormRequests do card usam o trait `Requests/Concerns/ActsOnCard` (`card()` + `AuthorizesBeforeValidation`)

### CV-16 — Tipo de card é do projeto: ache pelo slug dentro do projeto; projeto novo nasce com os 5 pelo `TaskTypeService::createDefaults`
- **Escopo:** `task_types.project_id` (not null desde a migração `2026_10_16_000030`), `Project::taskTypes()`, `TaskTypeService`
- **Evidência:** `TaskService.php:119-122` (`typeOfSlug`), `ProjectService.php:46`, `ProjectFactory::withBoard()`, `tests/Support/CriaProjeto::tipo()`
- **Visto em:** b3-quadro-e-card ciclo 1. `TaskType::where('slug', …)` solto é desvio (acharia o tipo de outro projeto). Não há `TaskTypeSeeder` geral; teste que precisa de tipo chama `createDefaults` (ou `withBoard()`)

### CV-17 — Negação auditada se prova com exatamente uma linha nova por requisição, com ação e alvo
- **Escopo:** testes de Policy de Projetos (`TaskPolicyTest::assertOneDenied`, `SprintPolicyTest::assertOneDenied`)
- **Evidência:** `tests/Feature/Projetos/TaskPolicyTest.php` (helper do Fix 4 do Verifier do B3 PR 1); spec b3-quadro-e-card, TSK-04 AC 1 (o alvo: `task` com id do card; `task` com id nulo antes de o card existir; `project` em tipo, área e `manageSprint`)
- **Visto em:** b3-quadro-e-card ciclo 1 e Verifier (mutante pd1); b3-ficha ciclo 1 (CR-1-02). `exists()`, `>= N` ou `latest('id')` são desvio: passam com linha errada, dupla ou ação trocada (L-015)

### CV-18 — O Specs gate do CI é literal: `**Approved by**: Nome, AAAA-MM-DD` sem nada depois, `y` exato em Confirmed?, `**Acceptance Criteria**:` antes da lista numerada, e um veredito só no `validation.md`
- **Escopo:** `.specs/features/*/spec.md` e `validation.md`; `npm run specs:check` (`.ai/skills/conferir-specs.mjs` → `validate_spec.py`, `validate_state.py`)
- **Evidência:** CI do #17 em 19/10 (run 39312637589) vermelho por formato; `validate_state.py::_verdict` junta todas as linhas `## Validation`/`**Result**:` e, com PASS e FAIL juntos, devolve "placeholder"
- **Visto em:** b3-quadro-e-card (correção `f41436f`), b3-ficha ciclo 1 (CR-1-05). Histórico de rodadas no `validation.md` sem a palavra `Result:` (`Sensor da rodada N`, `Veredito da rodada N`). **PR-22:** o `validation.md` do B3 PR 3 já nasceu no formato; o gate ficou vermelho só pela aprovação

### CV-19 — "Nunca usado" do card é a lista fechada do `TaskUsageQuery` (não o `isReferenced()`), conferida com a linha travada; FK nova para `tasks` entra em `USES` ou `GOES_ALONG`
- **Escopo:** `app/Domains/Projetos/Services/TaskUsageQuery.php`, `TaskArchiveService::delete`, `TaskDetailPresenter` (`abilities.delete`)
- **Evidência:** `TaskUsageQuery.php:19-33` (as duas listas), `:39-62` (`isUnused`: `cross_tested_at`, `sprint_id`, `movements_count > 1`, `withExists` das seis relações); `TaskUsageQueryTest.php:19-34` (toda FK do `information_schema` para `tasks` está numa das listas; falha com FK nova sem decisão); `B3ArquivarTransacaoTest.php:104-123` (o comentário gravado no meio espera a linha do card, 1205)
- **Visto em:** b3-arquivar-e-avisos ciclo 1. Como no projeto (CV-13), o genérico `Deactivatable::isReferenced()` não serve: critério, participação e movimento de criação saem por cascade e não são uso, e `cross_tested_at`/`sprint_id` não são FK. A trava do card basta porque toda FK de `USES` aponta para `tasks` (o INSERT do filho pega S na linha travada). **A prova de trava com `Concorrencia` relê o model na outra conexão** (`Task::query()->findOrFail($id)` dentro do closure): a instância Eloquent carrega a conexão em que nasceu, e gravar pelo `$task` da principal entra na própria transação (lição do Verifier, `validation.md` rodada 1). Uma segunda conta de "nunca usado" fora do `TaskUsageQuery`, ou `TaskType::isReferenced()`-like no card, é desvio

### CV-20 — Aviso do sino é da pessoa: procurado em `$request->user()->notifications()`, 404 para o de outra; marcar lido não audita
- **Escopo:** `app/Platform/Controllers/NotificationController.php`, `app/Platform/Services/NotificationPresenter.php`, `HandleInertiaRequests::share()` (`notifications`)
- **Evidência:** `NotificationController.php:18` (`whereKey()->firstOrFail()->markAsRead()`), `:25` (`unreadNotifications()->update`); `routes/web.php:75-81` (`whereUuid`); `RotasAutorizadasTest.php:61-62` (lista do A5, "ação sobre a própria conta"); `NotificationsTest.php` (404, continua não lido, só os meus); `NotificationPresenter.php:17-37` (10 mais recentes, não lidos primeiro, hora de São Paulo)
- **Visto em:** b3-arquivar-e-avisos ciclo 1. O Núcleo só lê `data.text`/`data.url`/`data.key` que o módulo gravou (A9). O aviso de um card excluído fica com `url` nula pelo `TaskArchiveService::delete`, casado por `data->key` (seguro: prefixo unique e congelado após a 1ª tarefa). `DatabaseNotification::query()` sem o escopo da pessoa é desvio (o mutante w1 do Verifier)

## Anti-padrões recorrentes

### AP-01 — Check-then-act fora da transação, e a correção que inverte a ordem das travas
- **Ocorrências:** revisão 07/10 (A3 último admin, A4 anonimizado; corrigidos), b1-nucleo ciclo 1 (CR-1-02 CNPJ/e-mail repetido → 500; CR-1-15 `deactivate` nos 3 cadastros e cliente ativo ao criar contato; ambos corrigidos no ciclo 2), b1-nucleo ciclo 2 (**CR-2-01**: a correção do CR-1-15 travou o alvo antes dos admins ativos → deadlock 1213 → 500; CR-2-06: o `reactivate` de Usuário e Cliente ficou de fora; ambos corrigidos no ciclo 3), b1-nucleo ciclo 3 (**CR-3-01**: os `deactivate` decidem pelo `$locked` e gravam e auditam pela instância em memória → auditoria de desativação que não aconteceu; CR-3-02: `before` do `delete` tirado antes da trava → anonimização no meio devolve dado pessoal à auditoria; CR-3-03: escrita em admin-alvo trava alvo → ator pela FK da auditoria, contra a trava dos admins em ordem de índice)
- **Ocorrência do ciclo 4:** **CR-4-01**: a correção do CR-3-03 (`attempts: 3`) repete o `resendInvite` com a instância já salva na tentativa desfeita; o `must_change_password = true` não fica sujo e não é regravado (reproduzido: banco `false`, auditoria `[false, true]`). O teste novo simulava o caminho e não conferia a coluna
- **Ocorrência do ciclo 5:** **CR-5-01**: o `resendInvite` grava pela instância de chegada; se o convidado conclui o primeiro acesso entre o route binding e o `UPDATE`, o `forceFill(must_change_password = true)` não fica sujo e a senha provisória vale sem troca (reproduzido: banco `false`, `changes = null`). Anterior ao ciclo: a correção do CR-4-01 escolheu restaurar a memória em vez de reler com trava
- **Regressões:** 0 de ID; de comportamento, 1 no ciclo 2 (CR-2-01, corrigido) e 1 no ciclo 4 (CR-4-01, vindo do CR-3-03; corrigido no ciclo 5). Nos ciclos 1 a 4 a correção de concorrência abriu um achado novo de concorrência; os ciclos 5 e 6 não abriram (o CR-5-01 é anterior; a correção dele, releitura com trava, manteve a ordem X alvo → S ator). Resta só lacuna de teste da trava (CR-6-02)
- **Sinal:** `DB::transaction(..., attempts)` cujo callback salva uma instância recebida de fora (`forceFill`/`fill` + `save`) sem restaurá-la; teste de repetição que confere a auditoria e não a linha do banco; o estado é lido do modelo em memória antes do `DB::transaction`; **decide pela linha travada e escreve pela instância em memória** (`setActive()` não faz nada se a memória já está no estado pedido); unique do banco segura o dado mas a exceção vira 500; correção de concorrência que acrescenta um `lockForUpdate()` **antes** de uma trava que já existia; FK para `users` na gravação da auditoria (trava S no ator) depois de escrever o alvo; correção aplicada a uma ação e não à ação espelho (`deactivate` × `reactivate`)
- **Ação sugerida:** começar o review pelos Services procurando `if (...)` antes de `DB::transaction` e por variável em memória usada **depois** de um `lockForUpdate()` (no `setActive`, no `log`, no `snapshot`); em cada correção de concorrência, listar as linhas travadas (inclusive as S das FKs) e a ordem, e comparar com as outras escritas que travam as mesmas linhas; conferir a ação espelho e pedir o teste espelho ("instância carregada antes da mudança"); `DB::transaction(..., attempts: 3)` onde a ordem não puder ser garantida, **com o callback repetível** (todo teste de repetição confere a linha com `fresh()`, coluna por coluna do que o callback grava)

### AP-02 — Mesma coisa em dois ou três lugares entre os cadastros
- **Ocorrências:** revisão 07/10 (A9 `attempt()` em 3 controllers; corrigido), b1-nucleo ciclo 1 (CR-1-09 esqueleto de lista em 4 páginas, 3 padrões de `row()`; CR-1-08; corrigidos no ciclo 2), b1-nucleo ciclo 2 (**CR-2-03**: a trava de clique duplo do CR-1-17 nasceu copiada em 3 telas; CR-2-11: captura do unique em dois formatos; CR-2-12: linha da lista em controller × Presenter e `situation()` duplicado; todos corrigidos no ciclo 3 com `useRowAction`, `RefusesDuplicates` e os Presenters: CV-05, CV-08, CV-09). Ciclo 3: nenhuma ocorrência nova. Ciclo 4: **CR-4-05**: o esqueleto de ativar/desativar (trava, decide, `setActive`, `save`, `log`) segue em seis métodos dos três Services; o CR-3-01 nasceu de uma cópia que ficou para trás. Molde já existente: `Deactivatable::deleteIfUnused(?Closure $then)`. Ciclo 5: CR-4-05 resolvido com `setActiveLocked` (CV-10); detalhe CR-5-02 (o método não abre a própria transação, ao contrário do `deleteIfUnused`)
- **Regressões:** 0
- **Sinal:** B1 nasceu com três CRUDs paralelos; B2 a B7 copiam. A própria correção de um achado de duplicação pode nascer em N cópias, quando é aplicada tela por tela
- **Ação sugerida:** quando a correção toca as três telas, pedir o hook ou o componente no mesmo achado (ex.: `useRowAction()` para `busy`/`busyRow`/`rowPost`); `ListBody` e `deactivation()` já existem (CV-07)

### AP-04 — Decisão registrada aplicada só em parte dos documentos
- **Ocorrências:** b1-nucleo ciclo 2 (CR-2-05: a D14 entrou no AC e na F1, não no N7 nem no escopo; corrigido no ciclo 3), b1-nucleo ciclo 3 (CR-3-05: a D-GER-31 corrigiu F2 a F7 e o escopo, mas não `plano-de-desenvolvimento.md:12,113`, `00-COMECE-AQUI.md:33` e `funcional/README.md:3,47`), b1-nucleo ciclo 6 (**CR-6-01**: as correções do CR-5-01 e do CR-5-02 mudaram o padrão no código, e o "Um jeito só" do `AGENTS.md:230-231` seguiu ensinando o antigo: cita o `resendInvite` como exemplo de "restaure a instância" e o `setActiveLocked` como dependente da transação de quem chama). **pr-8 ciclo 1:** CR-1-06 (ADR-003 e ADR-004 aprovados em 09/10 seguiam "proposta" e sem `AD-` no `STATE.md`), CR-1-07 (`APP_BRAND_LOGO` removido do `.env.example` e do `config`, não de `.env.testing` e `.env.e2e.example`), CR-1-03 (ADR-004 numerado nas duas branches abertas: #5 ficha do cliente, #8 marca)
- **Regressões:** 0
- **Sinal:** commit de decisão que lista os documentos tocados; a porta de leitura (`00-COMECE-AQUI.md`) e o plano ficam de fora; **correção de review que muda um padrão citado no "Um jeito só" do `AGENTS.md`** sem tocar o `AGENTS.md`
- **Ação sugerida:** em commit de decisão, `git grep` do termo antigo ("dono do módulo", o nome da regra) em `docs/` e `.specs/` antes de fechar
- **Ocorrência (b3-quadro-e-card ciclo 1, CR-1-04):** a spec e o `AGENTS.md` dizem que lançar horas fica na `TimeEntryPolicy` (sem `logTime` na `TaskPolicy`) e que as queries ficam em `Services/`; o `docs/escopo.md` B3 seguiu com `logTime` e `Queries/`
- **Ocorrência (b3-ficha ciclo 1, CR-1-06):** o `TaskEditService` virou a porta do `PATCH` da ficha e o `AGENTS.md` ainda diz que etiquetas e participante entram pelo `TaskTagService`; as 4 colunas de "quem" não estão no `docs/banco.md`
- **Ocorrência (b3-arquivar-e-avisos ciclo 1, CR-1-04):** a spec decide "fora de sprint" como uso, "card filho" como uso e "aviso do card excluído fica sem link", e o `docs/banco.md` (linha do "tirar do quadro"), a F3 §04 (linha "Excluir card") e a F3 §07 seguem sem; o P57 ("excluir o pai não mexe nos filhos: ficam sem pai") contradiz o P26 ("ter cards filhos conta"), que é o que o código segue. Sinal novo: **spec com premissa que estende uma regra do `banco.md` sem tocar o `banco.md`**

### AP-05 — Fundação de front aberta em paralelo a blocos funcionais, renomeando o que eles editam
- **Ocorrências:** pr-8 ciclo 1 (CR-1-02): o #8 parte do B0 e move/renomeia `components/ui/*`, `layouts/`, `data-table`, `row-actions`, `search-input`, `status-pill`, `user-form-dialog`, `app-sidebar`, que o #5 (B1) e o #6 (B2) editam; `git merge-tree` dá 9 conflitos com o #5 e 1 com o #6; 30 arquivos do B1 e 21 do B2 importam `@/components/ui/` ou `@/layouts/`; os componentes e hooks do B1 (CV-07, CV-09, `StatusPill` contact/audit, `DataTable rowClassName`, `SearchInput` do CR-1-10) não existem no #8
- **Ocorrência do PR-6 (ciclo 2, CR-2-01):** o B2 também nasceu da `main` antiga (antes do B1 e do #8): 9 conflitos com a `main` atual, o front inteiro em `components/ui/`/`layouts/`/Select nativo/`<input type="date">`, e dois add/add que substituiriam arquivos da `main` (`tests/Support/CriaProjeto.php`, criado pelo B1 com o mesmo nome e outros métodos; `PROJECT-PATTERNS.md`, versão própria de 30 linhas no lugar do acumulado). O #8 (resolvido 09/10) é o molde do porte
- **Regressões:** 0 (PR-8 integrado 09/10; PR-6 ainda não)
- **Sinal:** PR que renomeia ou move pasta de `resources/js/components/`; **branch de bloco aberta antes de um PR de fundação mergear**; add/add de arquivo que a `main` também criou (`tests/Support/*`, `.specs/code-reviews/PROJECT-PATTERNS.md`) enquanto há PR aberto de bloco funcional; corpo do PR sem citar os PRs abertos; `git merge-tree --write-tree <branch> <cada PR aberto>` com conflito
- **Ação sugerida:** rodar o `merge-tree` contra cada PR aberto antes de abrir o PR de fundação e colocar o resultado e a ordem proposta no corpo; quem é dono da estrutura porta os componentes dos outros (lista nominal no PR); rodar os e2e dos blocos portados depois da integração

### AP-06 — Corpo do PR afirma "verde" com CI vermelho ou sem rodar
- **Ocorrências:** pr-8 ciclo 1 (CR-1-01): "typecheck, lint e build verdes · E2E 13/13" com o job JS caído no `npm ci` em 9 s (lock fora de sincronia) e o E2E pulado
- **Ocorrência (b3-arquivar-e-avisos ciclo 1, CR-1-01), a versão honesta:** o corpo diz "80 passed, rodado antes do merge do B7; depois do merge não rodei o e2e local: o CI do PR roda", e o CI pulou o E2E porque o job JS cai no Specs gate (spec sem aprovação, CV-18). Enquanto a spec do PR estiver sem aprovação, **o E2E do CI nunca roda**: a evidência do e2e tem de ser local, no corpo. A branch ainda conflitava com a `main` (`AGENTS.md`, B9 mergeado depois do PR); o `git merge-tree --write-tree origin/main <branch>` no início do review mostra isso em segundos
- **Regressões:** 0
- **Sinal:** `gh pr checks <n>` com fail/skipping; lock gerado numa máquina e `npm ci` em outra; PR sem link do run
- **Ação sugerida:** o review começa por `gh pr checks` antes de ler o corpo; o corpo do PR cita o run; `npm ci` local reproduz o CI em segundos

### AP-03 — Correção no teste e2e sem correção no produto
- **Ocorrências:** b1-nucleo (A5 → CR-1-10: `SearchInput` cancela escrita em voo; o page object passou a esperar a busca; o produto foi corrigido no ciclo 2, com e2e que força a corrida)
- **Regressões:** 0
- **Sinal:** e2e verde com o mecanismo intacto
- **Ação sugerida:** quando um e2e falha por corrida, perguntar se o usuário real cai na mesma corrida

---

## Decisões arquiteturais

### DA-01 — Excluir o já usado responde toast de erro, não 403
- **Motivo:** decisão do Lucas, D6 da revisão de 07/10
- **Origem:** spec Usuários AC 12, Clientes AC 12, Contatos AC 9 (revisão de 08/10)
- **Implica:** não apontar "deveria ser 403" nos `destroy`

### DA-02 — Listas de escolha (NUC-13) sem rota JSON; o B2 entrega como props
- **Motivo:** D6 do `design.md` ("sem fetch direto: tudo via Inertia")
- **Origem:** design aprovado pelo Cauã; **aceita pelo Lucas em 09/10/2026** como desvio da spec (D13 do design; CR-1-05)
- **Implica:** o P2 AC 3 vale no nível da Policy; `choiceList(User $viewer)` confere `viewChoiceList` dentro do Service por `AuditService::inspectFor()` e audita a negação. O B2 não precisa chamar `authorize('viewChoiceList')` antes: o Service já o faz. Não apontar "falta rota JSON" nem "AC 3 sem rota"

### DA-03 — Rotas em inglês, abilities `view_clients`/`view_contacts`/`view_audit_logs`, `delete_records` e `reset_passwords` por `isAdmin()`
- **Motivo:** D4 e D5 do Lucas, 07/10
- **Origem:** `docs/escopo.md` atualizado em 08/10
- **Implica:** não apontar divergência com os nomes antigos do escopo

### DA-04 — Memória do `ClientManagement` é `scoped` + listener `RequestHandled`
- **Motivo:** Policies perguntam linha a linha; Octane e fila limpam o scoped sozinhos, o listener serve aos testes
- **Origem:** b1-nucleo, revisão do Vitinho (07/10)
- **Implica:** não apontar "singleton vaza entre requisições"

---

### DA-05 — CR-1-01 (reenviar convite derruba sessões) sai em PR próprio, não no PR #5
- **Motivo:** toca autenticação (inegociável 5); decisão do Lucas Scholz em 09/10/2026 (D15 do design)
- **Origem:** ciclo 1, CR-1-01; branch `fix/reenviar-convite-derruba-sessoes` a partir da `main`, PR [WCJ-Tecnologia/wcj-hub#7](https://github.com/WCJ-Tecnologia/wcj-hub/pull/7), aberto e sem merge
- **Implica:** na branch `feat/b1-nucleo` o `UserService::resendInvite` continua sem `terminateOthers` de propósito; o status do CR-1-01 nos ciclos seguintes é "tratado em PR próprio (#7), fora desta branch", não "não resolvido" nem "regressão". Ao integrar os dois PRs, a chamada vai para dentro da transação do `resendInvite` (nota "Atenção ao mergear" do #7). Quem marca o achado de segurança como corrigido é o Lucas, depois do merge e da implantação

### DA-06 — Trava do Service é a mesma da Policy para a WCJ (desativar e excluir)
- **Motivo:** decisão do Lucas Scholz em 09/10/2026 (D14 do design; CR-1-04): a regra "a WCJ não pode ser excluída" fica na Policy e no Service, como a de desativar
- **Origem:** ciclo 1; spec Clientes AC 10 e F1 §04 v1.2
- **Implica:** não apontar como "trava que ninguém pediu"

### DA-07 — Decisões pendentes do Lucas (não são achados novos)
- **Origem:** ciclo 1: CR-1-11 (prazo da senha provisória: a spec cita regra que o template não tem; prazo pede coluna ou leitura da auditoria), CR-1-12 (`min="0.25"` da carga diária na tela × `gt:0` no servidor), CR-1-23 (índice da auditoria por `created_at`: migração). Ciclo 2: CR-2-09 ("Reenviar convite" para usuário desativado × F1 §04) e a parte de `abilities` do CR-2-08 (`delete_records` e `reset_passwords` sem leitor no front; mexer em `abilities` exige aprovação)
- **Implica:** reverificar o status ("❌ Não resolvido, aguarda decisão"), sem reabrir como achado novo nem subir a criticidade; D16 do design. Ciclos 3, 4, 5 e 6: os cinco seguem sem decisão. Se o Lucas aceitar o comportamento atual do CR-2-09, ele vira `FP-`. O CR-4-04 (repetir só o 1213 ou baixar `innodb_lock_wait_timeout`) saiu desta lista no ciclo 5: "repetir só o 1213" foi escolhido no `596f37e`, autor Lucas Scholz ("decisão de quem integra"); não reabrir pedindo timeout de sessão

### DA-08 — Trilho completo do B1: correção de review também passa pelo Verifier
- **Motivo:** `AGENTS.md` ("Trilhos": Verifier em agente separado com PASS e mutação em B1, Policy e exclusão) e `spec.md:262`
- **Origem:** b1-nucleo ciclo 2 (CR-2-02): 22 commits de correção, incluindo Policy dentro do Service e trava de exclusão, sem rodada nova no `validation.md`
- **Implica:** no ciclo N > 1 de bloco de trilho completo, conferir se o `validation.md` tem rodada posterior às correções; se não, é achado (Médio), não detalhe. Ciclo 3: a Rodada 9 (20/20 mutantes) atendeu ao CR-2-02; conferir por amostragem as linhas de evidência e não pedir de novo o que ela provou. As correções do ciclo 3 (CR-3-01 mexe em desativação) pedem rodada nova. Ciclo 4: não houve rodada sobre `e9b2731..6dec4f4` → CR-4-02 (Médio). Referência: o `validation.md` mais recente cobre o intervalo citado na linha `Result` do topo; conferir o intervalo, não só o PASS. Ciclo 5: rodadas 10 (`fef3766..7b96b12`, FAIL Minor só pelo mutante g2) e 11 (`b0a8d6f..7c228a8`, PASS) atenderam ao CR-4-02. A correção do CR-5-01 mexe em senha e pede rodada nova focada (mutante "emite pela instância em memória"). Ciclo 6: rodadas 12 (`03ae0f3..7487b53`, FAIL Minor só pelo mutante d) e 13 (`56139b2..fc7b73d`, PASS; a linha `Result` do topo cobre só a 13, as duas juntas cobrem o ciclo) atenderam; mutantes a a d mortos. Nenhum mutante tirou só o `lockForUpdate()` do `resendInvite` (CR-6-02)

### DA-09 — Select e campo de data do shadcn no lugar dos nativos; Varanda decide escolhas de front
- **Motivo:** decisão do Lucas Scholz em 09/10/2026, comentário no PR #8: aprovou o ADR-003 e o ADR-004, inclusive a troca do Select nativo (`.ai/stacks/ui.md` L66) e do `<input type="date">` pelos do shadcn, e deu ao Lucas Varanda autonomia nesse tipo de escolha pela experiência dele
- **Origem:** pr-8 ciclo 1
- **Implica:** não apontar "contraria o ui.md: Select nativo" nem "date nativo"; o ui.md é do template e não se edita no projeto, o `AGENTS.md` registra a divergência. Decisão de componente de front do Varanda não precisa de aval ponto a ponto; o que segue precisando de humano é o inegociável 5 (banco, auth, Policy, produção). Se a decisão se confirmar boa, sobe para o template `ai-systems`

### DA-10 — PR #8 é fundação do front e precisa de ordem de integração com #5 e #6 decidida pelo Lucas
- **Motivo:** CR-1-02 do pr-8: 9 conflitos com o #5, 1 com o #6, 51 imports quebrados, componentes do B1 ausentes
- **Origem:** pr-8 ciclo 1; **decidido pelo Lucas Scholz em 09/10/2026:** #5 primeiro (mergeado, main `a1a052e`), #7 em seguida (`1a98616`), #8 faz o rebase e porta o B1 para a estrutura atômica, depois #6
- **Implica:** no ciclo 2 do PR-8, conferir se o rebase portou a lista nominal (ListBody, useListVisit, ShowInactiveToggle, useRowAction, RowActions busy/disabled, StatusPill contact/audit, DataTable rowClassName, SearchInput do CR-1-10, telas de clientes/contatos/auditoria, tabela do AGENTS.md)

### DA-11 — Criar e editar projeto por modal na lista, não as telas `/projetos/novo` e `/projetos/{id}/editar` do escopo B2
- **Motivo:** decisão do Lucas Scholz em 09/10/2026 ("segue o que o Vitinho fez"): o modal é o gabarito do `.ai/stacks/ui.md` §4 e o que o B1 fez em Usuários e Clientes
- **Origem:** pr-6 ciclo 1 (CR-1-05), decidido no ciclo 2
- **Implica:** não apontar "faltam as telas /projetos/novo e /editar"; o `docs/escopo.md` (B2, "Telas") precisa ser alinhado no rebase (AP-04 se ficar para trás); a configuração (`/projetos/{id}/configuracao`, abas) continua página própria

### DA-12 — `ProjectClientManagement` é do B2 e precisa entrar no passo 2 (ou antes)
- **Motivo:** com o B1 na `main`, `ClientPolicy`/`ContactPolicy` perguntam ao contrato `ClientManagement`, ligado a `NoClientManagement`: o gestor não gere cliente nem vê contato (F1 §09) até o B2 entregar a real (escopo B2)
- **Origem:** pr-6 ciclo 2 (CR-2-05)
- **Implica:** no ciclo seguinte do PR-6, conferir se o PR declara o passo em que entra ou já entrega; o `ClientManagementFalso` dos testes do Núcleo continua valendo

### DA-13 — Decisões pendentes do Lucas no B3 PR 3 (não são achados novos)
- **Origem:** b3-arquivar-e-avisos ciclo 1 (PR-22), seção "Para o Lucas decidir" do relatório: (1) a aprovação da spec com o `y` nas 13 premissas; (2) a pergunta aberta do EXC-02: aviso do card excluído fica sem link (o que o código faz) ou sai junto; (3) a ficha do card arquivado ainda mostra *Mover para…* e deixa editar (`abilities.move`/`update` não olham o arquivamento; o servidor recusa); (4) a negação do desarquivar gravada como `archived`, sem ability nova; (5) P57 × P26: o pai com filhos se exclui ou não (CR-1-04 c)
- **Implica:** reverificar como "aguarda decisão", sem reabrir como achado nem subir criticidade. Se o Lucas decidir "saem junto" no (2), o EXC-02, o `TaskArchiveService::delete` (`:110-113`) e o teste dos avisos invertem, e o mutante d9 do Verifier vira o comportamento. Se decidir esconder no (3), é `TaskDetailPresenter` + `types/quadro.ts` no mesmo commit, sem Policy nova. O que ele aceitar como está vira `FP-`

## Falsos positivos

### FP-01 — Gestor cria cliente em Administração → Clientes (não só pelo formulário do projeto)
- **Apontado em:** revisão 07/10 (D1)
- **Por que não é problema:** aprovado pelo Lucas em 07/10; F1 §04 e §09 ganharam a frase
- **Reabrir apenas se:** a F1 mudar

### FP-02 — Lista geral de contatos vazia com "Ir para Clientes" apontando ação que o gestor não tem
- **Apontado em:** revisão 07/10 (D10)
- **Por que não é problema:** aceito até o B2 ligar a `ProjectClientManagement`
- **Reabrir apenas se:** o B2 entrar e o botão continuar sem sentido para o gestor

### FP-03 — Aviso "Já existe um contato com este e-mail" revela existência em cliente que o gestor não gere
- **Apontado em:** b1-nucleo ciclo 1 (registrado sem achado)
- **Por que não é problema:** decisão consciente do Lucas (D2): sem nome nem cliente para não-admin
- **Reabrir apenas se:** a regra N8/F1 §09 mudar

### FP-04 — Dado pessoal na linha `deleted` do contato excluído
- **Apontado em:** revisão 07/10 (D11)
- **Por que não é problema:** registrado como limitação na `necessidade.md` (N12 cobre só o anonimizado)
- **Reabrir apenas se:** um titular pedir anonimização de contato já excluído

### FP-05 — Filtro "Situação" é só a chave "Mostrar desativados"
- **Apontado em:** b1-nucleo ciclo 1 (registrado sem achado)
- **Por que não é problema:** gabarito do template; a F1 §03 cita a chave
- **Reabrir apenas se:** alguém precisar listar só desativados ou só anonimizados

### FP-06 — Select e `<input type="date">` nativos trocados pelos do shadcn "contra o ui.md"
- **Apontado em:** pr-8 ciclo 1 (o próprio PR levantou como ponto que pedia aval)
- **Por que não é problema:** aprovado pelo Lucas Scholz em 09/10/2026 (DA-09); o ui.md é do template
- **Reabrir apenas se:** o template `ai-systems` incorporar a decisão de outro jeito, ou um uso real no celular pedir o seletor do sistema

### FP-07 — "Label sem `for` / input sem `id`" nos formulários com `WFormFieldMolecule`
- **Apontado em:** pr-6 ciclo 1 (registrado pelo revisor do lado do Vitinho)
- **Por que não é problema:** o `WFormFieldMolecule` injeta `id`, `aria-invalid` e `aria-describedby` no filho por `cloneElement` quando recebe `htmlFor` (`w-form-field-molecule.tsx:28-35`)
- **Reabrir apenas se:** o filho for um componente que não repassa `id`/`aria-*` ao controle (o `WDatePickerMolecule` passou a repassar no #8)

### Registrado sem achado no ciclo 3 (não é FP: o Lucas não foi consultado)
- Evidência de rodada antiga do `validation.md` (ex.: U3 da rodada 3 citando `isReset`) não se reescreve: o relatório é por rodadas e a mais nova é a vigente. Não apontar.
- Commit de governança do próprio Lucas dentro do PR do B1 (`f1bebc7`, D-GER-31): não contradiz código; só vira achado se um documento contradisser a decisão (aí é o AP-04).
- Push e CI do PR são do Lucas: registrar no resumo, não como achado de código (pedido do coordenador do ciclo 3).

### Registrado sem achado no ciclo 4 (não é FP: o Lucas não foi consultado)
- `UserService::update` e `ClientService::update` tiram o `before` da instância sem travar o alvo (o `ContactService::update` trava). Só edição simultânea do mesmo registro, sem dado anonimizável: pedir trava seria "trava que ninguém pediu". Não apontar sem caso de dado pessoal.
- Client e Contact sem `attempts`: coerente (travam a linha de negócio e depois o ator em S; não há ciclo). `invite` sem `attempts`: coerente. Não apontar "inconsistência".
- Comentários que prometem mais que o código (`UserService.php:258`, `Concorrencia.php:57`) e o `AGENTS.md` sem a convenção da repetição: registrar, não achado (documento não contradiz decisão).
- Asserção `assertSame(1205, $corrida->codigo)` do `UltimoAdminConcorrenciaTest` é fraca (1213 não se forma no harness), e o comentário diz isso: não reabrir o CR-3-04.

### Registrado sem achado no ciclo 5 (não é FP: o Lucas não foi consultado)
- `AGENTS.md:78` ("Verifier rodadas 9 e 10") e `spec.md:262` (rodadas 8 e 9) não citam a rodada 11, a vigente: incompleto, não contradiz. `AGENTS.md:231` "até 3 vezes" são 3 tentativas.
- `ContactService::reactivate` trava a mesma linha duas vezes (`lockedNotAnonymized` + `setActiveLocked`); o `bool` do `setActiveLocked` não é lido: sem efeito.
- 1205 sobe como 500 na primeira tentativa: coerente com o CV-02. 1213 no `COMMIT` não repete: não é caminho realista.
- O `$before` do `resendInvite` vem da memória restaurada: some com a correção do CR-5-01; não apontar à parte.

### Registrado sem achado no ciclo 6 (não é FP: o Lucas não foi consultado)
- Toast do reenvio (`UserController.php:96,99`) usa `$user->email` do route binding; a senha vai para `$locked->email`. Só diverge com troca de e-mail no mesmo instante; nada vaza. Cosmético; não apontar.
- Deadlock no `setActiveLocked` aninhado (desativar/reativar usuário) sem teste próprio: mesmo mecanismo do `deleteIfUnused`, provado. Não pedir.
- `DB::transaction` de fora redundante em Client e Contact depois do CR-5-02: savepoint a mais, sem efeito. No Client/Contact o deadlock aninhado sobe como `DeadlockException` em vez de `QueryException`: nada distingue, segue 500 (CV-02).
- Comentário `UserService.php:78` "como toda escrita de usuário (CV-03)": o `update` ainda restaura a memória. Imprecisão de comentário.
- Alvo excluído entre o binding e o reenvio agora dá 404 (`findOrFail` no `$locked`): ganho, não achado.
- `AGENTS.md:78` e `spec.md:262` seguem sem o ciclo 5 e as rodadas 12 e 13: incompleto (vai junto do CR-6-01).

### Registrado sem achado no ciclo 1 do PR-8 (não é FP: o Lucas não foi consultado)
- Sentinela `all` dos Selects: Usuários omite `role_id` (`UserController.php:37`, `integer()`); Auditoria manda `''` com `nullable` + `! empty` (`AuditLogController.php:20-31`). Mesmo comportamento do nativo. Não apontar.
- `WPaginationMolecule.kindOf` depende de "Previous/Next" ou `pagination.*`: o B0 tinha a mesma dependência, `locale` é `en`, não há `lang/`. Não é regressão.
- `WAuthLayoutTemplate`: `h2` da marca antes do `h1` da tela. `heading-order` é best-practice do axe, fora das tags WCAG do gate. Não apontar enquanto o gate for esse.
- Cookie `sidebar_state` sem `SameSite`: preferência de UI, `Lax` por padrão do navegador.
- Hex inline nos Blade de e-mail: exceção documentada do ADR-004. Ícone `brand/wcj-icone-64.png` existe no PR.
- `.storybook/main.ts` com `import.meta.resolve` e `staticDirs: ['../public']`: Node 22 no CI; servir `public/build` no Storybook é inofensivo.
- Ícones lucide sem `aria-hidden` explícito em `WStatCardMolecule`/`WEmptyStateMolecule`: o `lucide-react` emite `aria-hidden="true"` por padrão.
- "E2E 13/13 (axe)" do corpo do PR: não verificado neste ciclo (CI pulou, precisa de Laravel + MySQL). Registrar como "sem evidência", não como achado separado (está no CR-1-01).

### Registrado sem achado no ciclo 2 do PR-6 (não é FP: o Lucas não foi consultado)
- Rotas em português (`/projetos`, `/encerrar`, `/reabrir`) com nomes `projects.*`: é o que o escopo B2 define (o `/admin/*` do B1 é em inglês pela D4). Não apontar.
- `guardPrefix` envolve a transação inteira (CR-1-03 do PR-6): o único unique de `projects` é o do prefixo; convenção, não bug ativo. Não subir a criticidade sem um segundo unique na transação.
- Encerrar o já encerrado responde "encerrado" sem gravar: fluxo aberto. Delete audita com `auditable_id` do apagado: mesmo caminho do `deleteIfUnused`.
- O `ExemploSeeder` da `main` ainda tem a constante `ETAPAS`; o PR-6 a troca pelo `BoardStageService`, como o comentário do seeder pedia. Sem conflito.

### Registrado sem achado no ciclo 1 do PR-17 (b3-quadro-e-card; não é FP: o Lucas não foi consultado)
- Janela entre `TaskPolicy::move` (lê a etapa atual sem trava) e o `TaskMoveService` (relê travado, mas não refaz "cruza o Backlog?"): milissegundos, reversível, fluxo aberto. Não apontar; refazer no Service seria um segundo jeito da regra (AP-02).
- `constrained()` + `index()` na mesma coluna (`parent_task_id`, `cover_attachment_id`) **não** duplica índice: o MySQL reaproveita o explícito para a FK (conferido no `wcj_hub_test`); é o padrão do `origin_task_id` do B0. Não reabrir.
- Dias úteis em pausa pelo `between − 1` (`BoardQuery::pausedBusinessDays`): aproximação declarada na premissa 5 do PR #17; a conta fina é do F7.
- `down()` da migração dos tipos pode falhar no `unique('slug')` se dois projetos renomearam o tipo de mesmo slug: só dev; em produção a volta é o backup (B10).
- `TaskController::show` que redireciona ao quadro com aviso: provisório declarado, sai no PR 2 do B3.
- Spec do B3 só com `spec.md` e "Approved by: aguardando": o PR declara o Verifier depois da aprovação; é condição do trilho completo (recomendação do relatório), não achado de código.
- Contatos do cliente nas props do quadro (CR-1-01) e tipo sem desfazer (CR-1-02) foram apontados como Médio; se o Lucas decidir que ficam assim, registrar aqui como FP com o motivo.

### Registrado sem achado no ciclo 1 do PR-18 (b3-ficha; não é FP: o Lucas não foi consultado)
- Card pai ou tarefa de origem arquivado aceito pela API (`TaskEditService::assertRelations` não lê `archived_at`; a busca esconde): fluxo aberto; o PR 3 decide.
- Comentar card arquivado é permitido de propósito (F3 §11 item 12).
- `TaskTagService` delegando prioridade ao `TaskAssignmentService` gera duas linhas de auditoria no mesmo `PATCH`: preço do "um jeito só" do #16.
- Anexo de 20 MB pede `upload_max_filesize`/`post_max_size` no servidor: `docs/DEPLOY.md` no B10.
- Quem não vê o projeto recebe 404 num filho alheio pela edição (premissa 4 do PR): só a coerência entre rotas foi apontada (CR-1-04).
- As duas perguntas da spec (4 colunas de "quem"; número do critério após excluir o último) são decisão do Lucas, não achado; o código segue o recomendado (migração aditiva; "maior + 1" do banco.md).

### Registrado sem achado no ciclo 1 do PR-22 (b3-arquivar-e-avisos; não é FP: o Lucas não foi consultado)
- Avisos do card excluído casados por `data->key`, não por id: `TaskAssigned`/`TaskReturned::toArray` não gravam `task_id`; seguro por construção (prefixo unique e congelado após a 1ª tarefa, `ProjectService.php:65-66`; projeto sem tarefa não tem aviso). Não reabrir sem `task_id` no payload ou regra nova de prefixo.
- `notifications.data` é `TEXT` e o `where('data->key')` varre a tabela: até 14 pessoas. Não apontar.
- Desarquivar card que estava no Backlog vai ao fim da fila sem movimento: fluxo aberto e "fim da ordem manual" do `banco.md`.
- Tempo arquivado entra no intervalo da etapa quando o card volta (não há movimento de arquivar, só auditoria): é do F7, não do PR 3.
- A ficha roda `isUnused` (1 consulta) por abertura para quem gere; a lista de arquivados reaproveita a consulta do quadro inteira: não é N+1.
- O card pai ou a tarefa de origem arquivado continua aceito na ficha (pendência do PR-18 decidida na spec do PR 3, "Out of Scope"): fluxo aberto, P25.
- A escada num worktree com `vendor/` em link simbólico dá "Class … not found" nas classes novas da branch (o autoloader resolve `__DIR__` pelo caminho real e aponta para o `app/` do outro checkout): copiar o `vendor/`, não linkar. Falso negativo de ambiente, não da branch.
- A spec sem aprovação com o código pronto é condição do trilho (recomendação do relatório), como no PR-17 e no PR-18, não achado de código.
