# Code Review — `feat/b3-arquivar-e-avisos` · Ciclo 1

**Data:** 2026-10-22
**História:** b3-arquivar-e-avisos (PR WCJ-Tecnologia/wcj-hub#22, "B3 PR 3: arquivar, desarquivar, excluir o card nunca usado e o sino de avisos")
**Branch:** `feat/b3-arquivar-e-avisos` (head `3b3e9fd`, 10 commits do Cauã, com a `main` de 21/10 mergeada dentro) · **Base:** `origin/main` (branch do ambiente, `c532b37`, já com o B9, PR #21)
**Spec:** `.specs/features/b3-arquivar-e-avisos/spec.md` (trilho completo só para a exclusão de verdade e o escopo por dono do sino; **"Approved by: aguardando Lucas Scholz"**, 13 premissas sem `y`, uma pergunta aberta). `validation.md`: Verifier rodada 1 FAIL (50/55), rodada 2 **PASS** (consolidado 55/55, 2 equivalentes), só com correção de teste.
**Revisor:** Claude Code (sessão de revisão, contexto zerado; não é quem implementou nem quem verificou)
**Ciclos anteriores:** nenhum
**Escopo deste ciclo:** a branch inteira contra a `origin/main`: 40 arquivos (+2.096 / −34), sem mudança não commitada. Sem Policy nova nem alterada (`TaskPolicy::archive`/`delete` já estavam na `main`, aprovadas em 19/10), sem migração.

## Evidência colhida neste ciclo

Checkout do head do PR (`3b3e9fd`) num worktree próprio, com o Mac acordado (`caffeinate -i`), MySQL 8 `wcj-hub-mysql-1`, banco `wcj_hub_test`:

| Comando | Saída |
|---|---|
| `composer lint` | `{"tool":"pint","result":"passed"}`, exit 0 |
| `npm run typecheck` | exit 0 |
| `npm run lint` | exit 0 |
| `npm run build` | ok (`✓ built in 2.76s`), exit 0 |
| `caffeinate -i php artisan test --compact` | **739 passed (6225 assertions)**, 127,16 s, exit 0, nenhum pulado (os 739 que o corpo do PR cita depois do merge do B7) |
| `npm run test:guard` | 1053 verdes, 0 falhas, exit 0 |
| `npm run specs:check` | exit 1, **14 problemas, todos da aprovação da spec** (falta `**Approved by**: <nome>, <AAAA-MM-DD>`; 13 premissas sem `y` em *Confirmed?*; aviso da open question). As outras 5 features ok |
| e2e (`npm run e2e:prepare` + `npm run e2e:serve` + `caffeinate -i npx playwright test`, chromium, 1 worker) | `e2e/arquivar.spec.ts` sozinho: **3 passed** (setup + os 2 do PR, 8,7 s); a suíte inteira: **82 passed (2,2 min)**, exit 0, axe e guarda de console verdes. É o que o corpo do PR não tinha depois do merge do B7 (CR-1-01), mas ainda **sem o B9** |

Sobre a `main` atual (merge local de `origin/main` `c532b37` no head do PR, num branch descartável, só para a evidência; o único conflito é o `AGENTS.md`, resolvido mantendo os dois lados; `routes/web.php` e `RotasAutorizadasTest.php` automergeiam limpos, conferidos linha a linha): `caffeinate -i php -d memory_limit=1G artisan test --compact` → **779 passed (6643 assertions)**, 140 s, exit 0 (os 739 da `main` + os 40 do PR); `npm run typecheck`, `npm run lint` e `npm run build` exit 0. **Atenção para quem rodar no Mac:** com o `memory_limit` padrão do CLI (128M) a mesma suíte sobre o merge morre com "Allowed memory size of 134217728 bytes exhausted" antes de imprimir o resultado, enquanto a `origin/main` sozinha (739 testes) passa com 128M e o head do PR também; é o tamanho da suíte, não a branch. A partir deste merge, a escada local pede `php -d memory_limit=1G artisan test` ou o `php.ini` ajustado; o CI (sem limite) não é afetado.

CI do PR (`gh pr checks 22`, run 39653870804, 21/10): PHP (Pint + PHPUnit) **verde**; Segredos **verde**; JS **vermelho só no último passo**, o `npm run specs:check` (typecheck, lint, build, storybook e test:guard passaram); E2E **pulado** porque depende do JS. `gh pr view 22` em 22/10: **mergeable: CONFLICTING** (o #21 entrou na `main` depois do PR).

Primeira rodada da suíte PHP neste ciclo deu 39 falhas por "Class TaskUsageQuery not found": foi o `vendor/` do worktree, que estava em link simbólico e carregava o autoloader do outro checkout. Com o `vendor/` copiado, 739 passed. Registro para quem repetir a escada num worktree.

## O que foi olhado, com as seis perguntas de `seguranca.md` §11

1. **Toda rota de dados tem `auth` e Policy?** Sim. As três rotas do card (`routes/web.php:190-192`) estão no grupo de projetos (`auth`, `backoffice`, `password.current`): arquivar autoriza `archive` no `ArchiveTaskRequest` antes da validação (`ActsOnCard`, caminho auditado); desarquivar e excluir pelo `authorize()` do Controller base (`TaskController.php:95`, `:103`), que grava a negação com a ação certa (`archived`, `deleted`; `TaskArchivePolicyTest::assertOneDenied`, uma linha por requisição, CV-17). As duas do sino (`routes/web.php:75-81`) têm os mesmos três middlewares; são ação sobre a própria conta e entraram na lista do A5 (`RotasAutorizadasTest.php:61-62`).
2. **Toda rota que recebe ID valida o vínculo com a sessão?** Sim. `{card}` é a chave resolvida só entre os cards do projeto da rota (CV-15; `TaskArchivePolicyTest::test_a_card_of_another_project_is_not_found_through_this_project`). O aviso é procurado só entre os da pessoa logada (`NotificationController.php:18`, `$request->user()->notifications()->whereKey()`), 404 para o de outra pessoa e para id que não é UUID (`whereUuid`); marcar todos só atualiza `unreadNotifications()` da pessoa (`:25`). `NotificationsTest` cobre os quatro casos.
3. **Toda entrada passa por validação no servidor?** Sim. O motivo é `required|string|max:500` no `ArchiveTaskRequest`; desarquivar, excluir e o sino não têm corpo. Nenhum `$request->validate()` em ação de escrita (A10 do B9 passa sobre a branch).
4. **Alguma regra só na tela?** Não. `abilities.delete` esconde o *Excluir* pela mesma conta que o Service recusa (`TaskUsageQuery::isUnused`, `TaskDetailPresenter.php:162` e `TaskArchiveService.php:99`), e o Service decide pela linha travada (projeto → card, `WritesTasks`). "Já arquivado" e "não está arquivado" são do Service. A ordem de travas é a de toda escrita de card (CV-14).
5. **Algum segredo?** Não. O diff não traz chave, token, senha nem URL com credencial; o e2e cria o aviso por `tinker` sem segredo.
6. **Login e recuperação de senha com limite?** Não tocados.

**Inegociável 8 (duas camadas, escopo por ID):** atendido (1 e 2). **Inegociável 9 (regra no servidor):** atendido (4). **Inegociável 11 (porta de entrada), por entidade:** arquivamento: criar (arquivar com motivo), listar (filtro *Arquivados*, com os outros filtros valendo), desfazer (desarquivar); exclusão de verdade: só o nunca usado, irreversível por regra (F3 §04); aviso: o sistema cria (P22), a pessoa lista (sino) e marca um ou todos como lidos; "desmarcar" o F3 §07 não pede. Sim.

**O que o `AGENTS.md` exige do B3 neste PR:** arquivar e excluir são trilho completo. (a) **Policy aprovada:** a branch não cria nem altera Policy (`TaskPolicy.php` muda só um docblock); `archive` e `delete` = `manage` do projeto, aprovadas na spec `b3-quadro-e-card` em 19/10 e usadas como estão. (b) **Exclusão de verdade só do nunca usado:** sim, mas **não pelo `deleteIfUnused()`/`isReferenced()` do `Deactivatable`**, e com razão: o card tem FKs que saem junto por cascade (critérios, participações, movimento de criação) e condições que não são FK (`cross_tested_at`, `sprint_id`, "um movimento só"), então o genérico diria "referenciado" para todo card. O `TaskUsageQuery` é a lista fechada (`USES` e `GOES_ALONG`) com o `TaskUsageQueryTest` que falha quando o B4 ou o B6 criarem FK nova para `tasks` sem decidir; o mesmo desenho do projeto ("nunca usado" de projeto também não é o `isReferenced()`, CV-13). (c) **Verifier PASS com `validation.md` na spec:** sim, rodada 2 em 20/10, agente separado, 57 mutantes nas duas rodadas, as três sondas de banco de verdade e a trava da linha provada no `B3ArquivarTransacaoTest` (comentário gravado no meio espera a linha, 1205). (d) **Spec aprovada antes do código:** não; ver "Para o Lucas decidir".

**Ganchos do B5 e do B7 com card arquivado e desarquivado (pedido do ciclo):** continuam ligados. Capacidade, sprint, progresso do pai, *Minhas horas*, `IndicatorsQuery` (base e filtro), `PausedTasksQuery`, `SituationIndicators` e `SprintQuery` já ignoram o arquivado por `whereNull('archived_at')`; `DelayQuery` devolve nulo em arquivado; `EffortIndicators` mantém as horas lançadas e tira o previsto (P26); o `LateQuery` passou a ignorar também (a mudança deste PR, `LateQuery.php:29`, `TaskArchiveTest::test_the_archived_card_leaves_the_board_and_the_late_mark_but_the_card_opens`). O histórico do card (`/cards/{chave}/historico`) abre para o arquivado: o `Route::bind('card')` não filtra arquivamento e o `TaskHistoryScreen` mostra o bloco "arquivado" (`TaskHistoryScreen.php:66`). O desarquivar grava o movimento para o Backlog sem retrabalho (`banco.md`, "não contam"), que o `StageTime` lê como qualquer movimento; `rework_count` só conta `rework_origin_id`. Nada do B7 lê `task_stage_movements` por card nem soma por nome de etapa. Os 739 testes da `main` (B5 e B7 inclusos) passam na branch.

**Concorrência:** `archive` e `unarchive` travam projeto → card e decidem pela linha relida; `delete` trava as duas e confere o uso com a linha travada (toda FK de `USES` aponta para `tasks`, então o INSERT de comentário, hora, anexo, PR, filho ou bug de produção espera a trava X do card pelo S da FK, e `cross_tested_at`/`sprint_id` estão na própria linha). O update dos avisos e a linha `deleted` ficam na mesma transação (provado com `DatabaseTruncation`). Ordem de travas igual à dos outros Services (projeto, card, depois S em `users` pela auditoria): sem ciclo novo.

**Arquitetura:** `TaskArchiveService` (129 linhas), `TaskUsageQuery` (63) e `NotificationPresenter` (43) abaixo do limite A6; o Núcleo (`NotificationController`, `NotificationPresenter`, `HandleInertiaRequests`) só lê `notifications.data` e não importa nada de `App\Domains` (A9). Presenter para a linha (`archivedRow`, `payload`), controller só chama (CV-05). `UNARCHIVED` entrou no `AuditActionEnum` com rótulo ("Desarquivado"). Componente novo (`WArchivedCardsTableOrganism`) com `.tsx`, `.md`, story e linha na tabela do `AGENTS.md` (CV-07, CV-12).

---

## Achados do Ciclo 1

## [MÉDIO] CR-1-01 — A branch não está provada sobre a `main` de hoje: conflita com o `AGENTS.md` do B9 e o e2e não rodou depois do merge da `main` de 21/10

**Arquivo:** `AGENTS.md` (conflito com `origin/main` `c532b37`); corpo do PR #22, seção "Testes"
**Trecho atual:**
```text
$ gh pr view 22 --json mergeable        → "CONFLICTING"
$ git merge-tree --write-tree origin/main origin/feat/b3-arquivar-e-avisos
CONFLICT (content): Merge conflict in AGENTS.md
Corpo do PR: "npx playwright test 80 passed … rodado antes do merge do B7; depois do merge não rodei o e2e local …: o CI do PR roda"
CI run 39653870804: JS vermelho no specs:check → E2E "skipped"
```

**Ajuste necessário:**
Mergear a `origin/main` de novo na branch (o conflito é só o `AGENTS.md`: os dois lados acrescentaram linhas na tabela "Estado atual" e no "Um jeito só"; os dois ficam), rodar a suíte PHP e o e2e no resultado, e citar o run do CI no corpo. O Specs gate continua vermelho até a aprovação da spec (é o esperado, CV-18), então o E2E do CI vai seguir pulado: o e2e precisa de evidência local no corpo do PR.

**Motivo:**
O B9 (#21) entrou na `main` depois de o PR ser aberto e mexe em coisa global (headers em toda resposta, `trustHosts` e `UseAppUrl` só em produção, A10 que varre as ações de escrita). O `git merge-tree` mostra que `routes/web.php` e o `RotasAutorizadasTest.php` automergeiam, e a suíte sobre o merge local deste ciclo passou (779 testes, typecheck, lint e build; seção "Evidência"); mas isso é evidência minha num branch descartável, não da branch do PR. O e2e dos fluxos novos (`e2e/arquivar.spec.ts`) só rodou sobre a árvore anterior ao B7, e no CI foi pulado. É o sinal do AP-06 (corpo do PR com "verde" sem run), na versão honesta: o corpo diz que não rodou.

**Sugestão:**
`git merge origin/main`, resolver o `AGENTS.md` mantendo as duas linhas novas (B9 e B3 PR 3) na tabela e no "Um jeito só", `caffeinate -i php artisan test`, `npm run e2e:prepare && npm run e2e:serve` + `npx playwright test`, e colar as contagens e o link do run no corpo.

**Impacto:**
Merge pelo botão impossível até resolver; sem isso, a tela do PR 3 chega à `main` sem ter rodado sobre o B9.

## [MÉDIO] CR-1-02 — A linha `deleted` do card guarda um retrato parcial: a requisição, dev, QA e o texto dos critérios somem sem rastro

**Arquivo:** `app/Domains/Projetos/Services/TaskArchiveService.php:102-108`
**Trecho atual:**
```php
$before = [
    ...$this->snapshot($locked),
    'key' => $key,
    'parent_task_id' => $locked->parent_task_id,
    'criteria_count' => $locked->acceptanceCriteria()->count(),
];
```

**Ajuste necessário:**
Levar ao `before` o que o card tinha de conteúdo e que some com ele: `story_as`, `story_want`, `story_so_that`, `description` (a requisição), `developer_id`, `qa_id`, `requester_contact_id`, `contact_channel_id`, `archive_reason` (o card arquivado nunca usado também se exclui) e os critérios como lista (`number`, `description`), no lugar de só a contagem.

**Motivo:**
O `WritesTasks::snapshot()` é o retrato do movimento (projeto, número, título, etapa, posição, tipo, área, prioridade, sprint, teste cruzado, data prevista), feito para o `moved`. A exclusão é de verdade e, pela F3 §04, não se desfaz: a auditoria é o único rastro (G3, "o antes"). Um card nunca usado pode ter a requisição inteira escrita pelo gestor (Sendo / Quero / Para, informações adicionais) e `CA_1…CA_n`, que é justamente o conteúdo que alguém pode querer recuperar depois de um "excluí por engano". O padrão do projeto nas outras exclusões de verdade leva o registro e os dependentes: o `CriterionService::delete` grava o critério e os subitens (`CriterionService.php:90`), o `ProjectService::delete` o retrato do projeto. Aqui, `criteria_count: 1` não diz o que era o critério.

**Sugestão:**
Um `deletionSnapshot(Task $locked)` no próprio `TaskArchiveService`: `[...$this->snapshot($locked), 'key', os campos de texto e de pessoas acima, 'criteria' => $locked->acceptanceCriteria()->orderBy('number')->get(['number', 'description'])->map(...)->all()]`. O `AuditService::changes()` já transforma cada chave em `[antes, null]`. O teste `test_a_never_used_card_is_deleted_with_criteria_participants_and_the_creation_movement` passa a afirmar a requisição e a lista de critérios na linha `deleted`.

**Impacto:**
Exclusão por engano de um card com requisição escrita: o texto some sem cópia em lugar nenhum.

## [BAIXO] CR-1-03 — A releitura travada do card está copiada duas vezes no `TaskArchiveService`, fora do `lockTask()` do trait

**Arquivo:** `app/Domains/Projetos/Services/TaskArchiveService.php:61`, `:97`; `app/Domains/Projetos/Services/Concerns/WritesTasks.php:22-30`
**Trecho atual:**
```php
// unarchive
$locked = Task::query()->with('boardStage')->lockForUpdate()->findOrFail($task->id);
// delete
$locked = Task::query()->with('project')->lockForUpdate()->findOrFail($task->id);
// WritesTasks::lockTask
$locked = Task::query()->with('boardStage')->lockForUpdate()->findOrFail($task->id);
if ($locked->archived_at !== null) { throw new DomainException(self::ARCHIVED); }
```

**Ajuste necessário:** um `lockTaskRow(Task $task, array $with = ['boardStage']): Task` no `WritesTasks`, que relê com `lockForUpdate` e nada mais; `lockTask()` passa a chamá-lo e recusar o arquivado; `unarchive` e `delete` chamam o `lockTaskRow`.

**Motivo:** o motivo da cópia é legítimo (desarquivar e excluir precisam aceitar o card arquivado, que o `lockTask` recusa), mas a cópia é o sinal do AP-02: a próxima mudança na releitura (um `with` a mais, um `whereNull`) entra num lugar e não no outro, e o comentário do trait ("um jeito só para o TaskService, o TaskMoveService e o TaskTagService") deixa de ser verdade sem dizer.

**Impacto:** leitura e manutenção; nenhum comportamento errado hoje.

## [BAIXO] CR-1-04 — As decisões da spec não chegaram aos documentos que o próximo bloco lê (AP-04): `banco.md`, F3 §04 e §07, e o P57 contradiz o P26

**Arquivo:** `docs/banco.md:586-590`; `docs/funcional/f3-quadro-e-card.md:186` e §07; `docs/necessidade.md:245-251`
**Trecho atual:**
```text
banco.md:587  · excluir de verdade só o card nunca usado: nenhum movimento além da criação e nenhuma linha em
              time_entries, task_comments, task_attachments, task_pull_requests ou em tasks com origin_task_id
              ou parent_task_id apontando para ele, e cross_tested_at nulo.          ← sem "fora de sprint"
F3 §04 L186   Só o card nunca usado: nenhum movimento além da criação; sem horas, comentários, anexos nem PRs;
              nenhum bug de produção apontando para ele; sem teste cruzado marcado.   ← sem "card filho" nem "sprint"
F3 §07        (nada sobre o aviso de um card excluído)
P57           "Arquivar ou excluir o pai não mexe nos filhos: eles ficam no quadro, sem pai."
P26           "ter cards filhos conta [como uso]"                                      ← o pai com filho não se exclui
```

**Ajuste necessário:**
Quando o Lucas aprovar a spec, no mesmo PR: (a) `banco.md`, linha do "tirar do quadro": acrescentar "e fora de sprint (`sprint_id` nulo)" e uma frase sobre os avisos (`notifications.data.url` fica nula, o texto fica); (b) F3 §04, linha "Excluir card": a lista completa de uso (card filho, sprint) e, em §07, o que acontece com o aviso do card excluído; (c) P57: trocar "Arquivar ou excluir o pai não mexe nos filhos" por "Arquivar o pai não mexe nos filhos; o pai com filho não se exclui (P26)", ou o Lucas decide que o filho fica sem pai e aí a spec (EXC-01 AC 2, "card filho") e o `TaskUsageQuery::USES` mudam.

**Motivo:**
É o anti-padrão AP-04 do projeto, na quarta ocorrência em Projetos: a regra fica certa na spec e no código, e o documento que o B4 (teste cruzado, que marca `cross_tested_at`) e o B6 (pacote) vão ler segue com a versão anterior. O P57 × P26 é pior que incompleto: os dois textos dizem o contrário sobre o pai com filhos, e o código seguiu o P26 e o `banco.md` (o filho é uso; `TaskArchiveTest`, caso "card filho").

**Sugestão:** as três edições de uma linha, no commit da aprovação; `git grep "nunca usado"` em `docs/` antes de fechar.

**Impacto:** o B4 pode marcar teste cruzado sem saber que vira "uso"; o B6 cria FK nova e o `TaskUsageQueryTest` falha sem o documento dizer o que fazer.

---

## Para o Lucas decidir (não é achado de código; o PR pede o aval em cada um)

- **A spec não está aprovada e o código já existe.** `Approved by: aguardando Lucas Scholz`, 13 premissas sem `y`, uma pergunta aberta. O trilho completo pede a aprovação antes do código (`AGENTS.md`, "Trilhos"); o PR 1 e o PR 2 do B3 seguiram o mesmo caminho e foram aprovados depois. Enquanto não aprovar, o Specs gate do CI fica vermelho (CV-18) e o E2E do CI pulado.
- **A pergunta aberta (EXC-02):** os avisos `TaskAssigned`/`TaskReturned` do card excluído **ficam sem link** (o que o código faz, pelo contrato do kit) ou **saem junto**? Se "saem junto", o EXC-02 muda, o mutante d9 vira o comportamento e o `TaskArchiveTest::test_the_notices_of_a_deleted_card_stay_without_the_link_and_the_others_keep_it` inverte.
- **As 13 premissas da tabela *Assumptions*** da spec, em especial: "fora de sprint" conta como uso (card de suporte posto na sprint, P52); card arquivado nunca usado também se exclui; a negação do desarquivar grava a ação `archived` (sem ability nova, para não mexer na Policy); card de projeto encerrado se exclui; marcar aviso como lido não vai à auditoria; aviso de outra pessoa é 404 e não 403.
- **A ficha do card arquivado ainda mostra *Mover para…* e deixa editar os campos** (`abilities.move` e `update` não olham o arquivamento); o servidor recusa com "Card arquivado não muda: desarquive antes." (`WritesTasks::lockTask`). O Cauã não mexeu porque mudar `abilities` pede o seu ok. Se decidir esconder, é `TaskDetailPresenter` + `types/quadro.ts` no mesmo commit, sem Policy nova.
- **P57 × P26** (CR-1-04, item c): o pai com filhos se exclui ou não?

## Registrado sem achado no ciclo 1 (não é FP: o Lucas não foi consultado)

- **Avisos casados pela chave (`data->key`) e não pelo id do card:** `TaskAssigned::toArray` e `TaskReturned::toArray` gravam só `text`, `url` e `key`, não há `task_id`. É seguro por construção: `projects.key_prefix` é unique e congela depois da primeira tarefa (`ProjectService.php:65-66`), e o projeto que nunca teve tarefa não tem aviso; a chave nunca pertenceu a dois cards. Não reabrir sem um `task_id` no payload ou uma regra nova de prefixo.
- **`notifications.data` é `TEXT`** (migração `2026_10_06_000400`), e o `where('data->key')` varre a tabela: até 14 pessoas, aceitável; o B8 (MCP) não cria aviso novo.
- **Desarquivar um card que estava no Backlog o leva ao fim da fila sem movimento** (`TaskArchiveService.php:77-79`): fluxo aberto, e é o que o `banco.md` pede ("fim da ordem manual").
- **Tempo arquivado entra no intervalo da etapa depois de desarquivar** (um card arquivado 30 dias em Doing e desarquivado soma esses dias em Doing no F7): não há movimento de arquivar em `task_stage_movements`, só a auditoria. Enquanto arquivado, o card está fora dos indicadores. Fica para o F7, não é deste PR.
- **A ficha roda o `isUnused` (uma consulta) a cada abertura para quem gere** (`TaskDetailPresenter.php:162`, curto-circuito para o dev): preço do "um jeito só".
- **A lista de arquivados reaproveita a consulta do quadro inteira** (`BoardQuery::cards` com os `withCount` e `with` do card e o `planning`): não é N+1, são as mesmas consultas do quadro; o `LateQuery` já pula o arquivado.
- **`TaskPolicy.php` entra no diff só pelo docblock** do `delete`: sem Policy nova nem alterada, como o PR diz.
- **O `AGENTS.md` da branch, na linha do B7 e do B9, está com o texto da `main` de 21/10** (o B9 ainda como "commits locais"): o merge do CR-1-01 traz a versão nova; não é deste PR.
- **A suíte PHP passou do `memory_limit` de 128M do CLI do Mac depois do merge com a `main`** (779 testes): não é da branch (a `origin/main` e o head do PR passam com 128M); vale uma linha no `AGENTS.md` ("Como rodar") quando o próximo PR tocar nele.
- **Os mutantes equivalentes do Verifier (a6 `trim`, b2 `whereHas` do quadro)** têm a guarda nomeada no `validation.md`; conferidos por amostragem (o `TrimStrings` global apara antes; `BoardStageService::archive` move os cards ativos antes de remover a etapa). Não pedir de novo.

## Resumo executivo

| Criticidade | Achados | Na revisão em camadas |
|---|---|---|
| Crítico | 0 | bloqueante: 0 |
| Alto | 0 | bloqueante: 0 |
| Médio | 2 (CR-1-01, CR-1-02) | **deveria corrigir** (corrige ou justifica no PR) |
| Baixo | 2 (CR-1-03, CR-1-04) | detalhe |

Achados anteriores: não há (ciclo 1). Regressões: 0.

**Principais riscos:** nenhum de produção no código. O que pesa é de rito e de evidência: a branch conflita com a `main` de hoje e a tela não foi provada sobre o B9 (CR-1-01); e a exclusão de verdade apaga a requisição sem deixar o texto na auditoria (CR-1-02). As seis perguntas de segurança fecham; escopo por ID em toda rota nova; regra no Service com a linha travada; a prova de trava no banco de verdade e a exclusão numa transação só vieram do Verifier.

**Avaliação geral:** a branch segue o padrão da casa: autorização antes da validação no FormRequest e `authorize()` auditado nas ações sem corpo, negação com a ação certa e uma linha por requisição, card de outro projeto 404 antes da Policy, aviso de outra pessoa 404, "nunca usado" num lugar só com lista fechada de FKs e teste que acusa FK nova, auditoria e avisos na mesma transação do delete, `LateQuery` como um jeito só para quadro e ficha, Presenter para a linha, componente novo registrado. Verifier PASS em agente separado com 57 mutantes e três sondas. Os 739 testes, Pint, typecheck, lint, build e o guard passaram no Mac neste ciclo; o Specs gate fica vermelho só pela aprovação.

**Recomendação final: apta com ajustes**, condicionada ao rito do trilho completo que o próprio PR declara: (1) o "aprovo" do Lucas Scholz na spec, com a resposta à pergunta dos avisos e o `y` nas 13 premissas (o Specs gate do CI só fica verde depois disso); (2) o merge da `main` atual com a suíte e o e2e rodados no resultado, citados no corpo (CR-1-01); (3) CR-1-02 corrigido ou justificado no PR, e as três linhas de documento do CR-1-04 no commit da aprovação. Sem bloqueante em aberto.
