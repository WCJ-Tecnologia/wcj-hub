# B3 PR 3 (arquivar, desarquivar, excluir o card nunca usado e o sino de avisos) Validation

## Validation: FAIL

**Result**: FAIL na rodada 1 (20/10). Nenhum defeito no código de produção: em toda checagem, inclusive nas três sondas de banco de verdade (seção "Sondas"), o código fez o que a spec pede. O que falta é teste. Cinco mutantes não equivalentes sobrevivem, e dois critérios do P1 ficam sem prova completa.

O que está provado:
- as nove condições do "nunca usado", uma por uma;
- a recusa com a mensagem exata, sem mudar nada;
- o que sai junto com o card e a chave que não volta;
- os avisos dos outros cards, que continuam com o link;
- o `authorize()` das três rotas, com a negação auditada com a ação certa;
- o escopo por dono do sino (ler, marcar um, marcar todos, contagem);
- arquivar, desarquivar, a lista de Arquivados e o atraso.

EXC-01 (fora AC 1), AVI-01 e ARQ-01 estão ✅ Verified.

As lacunas, todas só de teste:
- **EXC-01, a trava da linha do card** (d1): sem o `lockForUpdate` do `delete`, a suíte passa. A sonda mostra a consequência: um comentário gravado no meio é commitado e depois apagado pelo CASCADE junto com o card.
- **EXC-01 AC 1 e EXC-02 AC 1, "na mesma transação"** (d6, d7): a auditoria `deleted` e o tirar do link levados para depois do commit sobrevivem (L-017: o `RefreshDatabase` esconde).
- **EXC-02 AC 1, o `TaskReturned`** (d8): o teste só tem `TaskAssigned`, e tirar o `TaskReturned` da lista sobrevive. "O mesmo texto" é afirmado por `assertStringContainsString`.
- **O sino mostra 10** (w5, sonda): o teste conta pela constante `NotificationPresenter::LIMIT`, e não pelo 10 da spec, por isso `LIMIT = 11` sobrevive.

Sensor: 57 mutantes (30 pedidos + 27 extras e sondas). 50 mortos, 2 equivalentes (a6, b2), 5 sobreviventes (d1, d6, d7, d8, w5). Contados: 50/55.

---

**Date**: 2026-10-20
**Spec**: `.specs/features/b3-arquivar-e-avisos/spec.md` (EXC-01, EXC-02, AVI-01, ARQ-01). **Approved by: aguardando Lucas Scholz**, com uma open question (os avisos do card excluído ficam sem link ou saem junto).
**Diff range**: `4a1f695..40e4e2e` (6 commits; branch `feat/b3-arquivar-e-avisos`).
**Escopo do trilho completo**:
- a exclusão de verdade: `TaskArchiveService::delete`, `TaskUsageQuery`, a ability `delete` da ficha;
- o escopo por dono do sino: `NotificationController`, `NotificationPresenter`, `HandleInertiaRequests`;
- o `authorize()` das três rotas do card.

Trilho leve, só para o contrato: arquivar, desarquivar, a lista de Arquivados, `LateQuery` e `BoardQuery`.
**Verifier**: sessão separada de quem implementou, sem acesso ao raciocínio dela; julgado só pelo repositório.

### Gate Check (árvore real, HEAD `40e4e2e`, sem edição)

| Comando | Saída |
|---|---|
| `./vendor/bin/pint --test` | `{"tool":"pint","result":"passed"}`, exit 0 |
| `php artisan test --compact` (MySQL 8, `wcj-hub-mysql-1`, `wcj_hub_test`) | **702 passed (5768 assertions)**, 132,87 s, exit 0, nenhum pulado. Os 665 do `b3-ficha` (rodada 2) + 37 dos quatro arquivos novos: `TaskArchiveTest` 23 (9 do provider `usos`), `TaskArchivePolicyTest` 6 (4 do provider), `TaskUsageQueryTest` 2, `NotificationsTest` 6. Não rodei a suíte em `4a1f695`; a conta fecha com os 665 da rodada 2 do PR 2 |
| `npm run typecheck` (`tsc --noEmit`) | exit 0 |
| `npm run lint` (`eslint .`) | exit 0 |
| `npm run specs:check` (antes deste relatório) | `conferir-specs: 6 feature(s), 0 problema(s)`, exit 0. Avisos: `b3-arquivar-e-avisos` "open questions do not read as resolved"; os dois antigos do `b1-nucleo` (T35, T50) |
| `npm run specs:check` (depois deste relatório) | `conferir-specs: 6 feature(s), 15 problema(s)`. Com o `validation.md`, a feature passa da especificação, e o script cobra: 1) o FAIL (`validate_state.py`, esperado); 2) `spec.md` sem `**Approved by**: <nome>, <AAAA-MM-DD>`; 3) 13 premissas sem `y` em *Confirmed?* (`spec.md` L38 a L51, menos a L42). O `specs:check` fica vermelho até o Lucas Scholz aprovar a spec, mesmo depois dos Fix |
| Base do sensor | `TaskArchiveTest` + `TaskArchivePolicyTest` = 29 passed (187 assertions); `NotificationsTest` 6 passed (27); `TaskArchiveTest` + `BoardTest` 34 passed (365) |

Não rodei Playwright (`e2e/arquivar.spec.ts`) nem Storybook, por instrução. A tela (trilho leve) fica sem verificação minha; os prints estão em `prints/`.

### Spec-Anchored Acceptance Criteria

#### EXC-01: só o card nunca usado se exclui (P1, AC 1 a 7)

| Critério | Valor da spec | Evidência (`arquivo:linha` + asserção) | Resultado |
|---|---|---|---|
| AC 1: quem gere exclui o nunca usado → apaga card, critérios, participações e o movimento de criação, e a linha `deleted` **na mesma transação** | tudo fora; `deleted` com o retrato e `criteria_count` | `TaskArchiveTest.php:204` `assertNull(Task::find)`, `:205` 0 critérios, `:206` 0 participações, `:207` 0 movimentos, `:210-212` `sole()` de `deleted` com `['task', id, gestor]` e `[['SGM-2', null], [1, null], [$pai->id, null]]` (d5 morto em `:210`). **Mesma transação: sem prova.** A auditoria em `DB::afterCommit` sobrevive (d6) | ❌ GAP (Fix 2) |
| AC 2: segundo movimento, horas, comentário, anexo, PR, teste cruzado, sprint, filho ou bug de produção → "SGM-17 já foi usado: arquive em vez de excluir." e nada muda | a mensagem exata; card, movimento de criação e auditoria iguais | Provider `TaskArchiveTest.php:237-249` (as 9 condições); `:258` a ficha esconde o *Excluir*; `:260` `assertSessionHas('error', 'SGM-1 já foi usado: arquive em vez de excluir.')`; `:262-264` o card fica, 1 movimento de criação, 0 `deleted`. Cada condição morre no seu caso (u1 a u9 em `:258`; d2 em `:260`) | PASS |
| AC 3: participante, card pai ou critério → exclui mesmo assim | excluído | `TaskArchiveTest.php:193-208` (pai, critério e participante; `:208` o pai fica). O u12 (o movimento de criação vira uso) morre em `:201` | PASS |
| AC 4: o próximo card nasce com o número seguinte, sem reaproveitar | 3 | `TaskArchiveTest.php:224` `assertSame(3, …number)` | PASS |
| AC 5: o dev ou quem não vê o projeto → 403, negação `deleted`, nada muda | 403; uma linha `denied`/`deleted`/`task`/id/pessoa | `TaskArchivePolicyTest.php:101` `assertOneDenied($user, …delete, 'deleted', $nunca->id, 'excluir')->assertForbidden()` com o helper `:71-81` (uma linha nova, os 5 campos), para dev alocado, dev de fora, gestor que não gere e Comercial (`:46-54`); `:103` retrato de `tasks` igual; `:104` nenhuma `success`. Positivo (L-016): `:116`, `:119` (gestor alocado exclui) | PASS (z3 e z8 mortos em `:78`, "excluir") |
| AC 6: `abilities.delete` só para quem gere e só no nunca usado | true / false | `TaskArchiveTest.php:271` gestor `[true, true]`; `:272` dev `[false, false]`; `:258` card usado `false` (z4 morto em `:258`, z5 em `:272`) | PASS |
| AC 7: sucesso → volta ao quadro com "SGM-17 excluído." | redirect para o quadro; a mensagem | `TaskArchiveTest.php:200-202` `assertRedirect("/projetos/{id}/quadro")->assertSessionHas('success', 'SGM-2 excluído.')` | PASS |
| Goal: "conferido com a linha travada" (Success Criteria: mutação na trava) | a linha do card travada da checagem até o delete | **Nenhum teste.** Sem o `lockForUpdate` (`TaskArchiveService.php:97`), os 29 testes passam (d1). A sonda prova que não é equivalente (seção "Sondas") | ❌ GAP (Fix 1) |
| Toda FK para `tasks` classificada | falha com FK nova sem decisão | `TaskUsageQueryTest.php:33` (`assertSame($classified, $fks)`; o u10, `time_entries` fora da lista, morre aqui sozinho); `:48` os de `GOES_ALONG` são CASCADE | PASS |

#### EXC-02: os avisos do card excluído ficam sem link (P1, AC 1 e 2)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: os `TaskAssigned` **e `TaskReturned`** do card → `url` nula, o **mesmo texto**, **na mesma transação** | `url` nula; texto igual | `TaskArchiveTest.php:281` só um `TaskAssigned`; `:287` `assertNull($data['SGM-1']['url'])` (d3 morto); `:288` `assertStringContainsString('Você é o dev de SGM-1', …)`, que é mais fraco que "o mesmo texto". **`TaskReturned` sem caso**: tirá-lo do `whereIn` (`TaskArchiveService.php:111`) sobrevive (d8). **Mesma transação sem prova**: o update em `DB::afterCommit` sobrevive (d7) | ❌ GAP (Fix 2, Fix 3) |
| AC 2: os avisos dos outros cards continuam com o link | o link do outro | `TaskArchiveTest.php:289` `assertSame($outro->url(), $data['SGM-2']['url'])` (d4 morto em `:289`; d9, apagar em vez de tirar o link, morto em `:287`) | PASS |

#### AVI-01: cada pessoa só vê e marca os próprios avisos (P1, AC 1 a 4)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: as props trazem só os avisos dela, com a contagem dos não lidos | os dela; `unread_count` dela | `NotificationsTest.php:61` `assertSame(2, unread_count)` com um não lido da Bia no banco (w6 morto em `:61`); `:62` só os três da Ana (w7 morto). Lido em `/projetos`; o `share()` vale para toda resposta Inertia (`HandleInertiaRequests.php:53`) | PASS |
| AC 2: marca um aviso dela → `read_at` só nele | um lido, o outro não | `NotificationsTest.php:90-91` (w8, marcar todos os meus, morto em `:91`) | PASS |
| AC 3: id de aviso de outra pessoa → 404 e continua não lido | 404; `read_at` nulo | `NotificationsTest.php:98` `assertNotFound()`, `:99` id que não é UUID também 404, `:101` `assertNull(read_at)` (w1 morto em `:98`, 302 em vez de 404) | PASS |
| AC 4: marca todos → só os dela | os dela lidos; o já lido intacto; o da Bia não | `NotificationsTest.php:112-115` (w2 morto em `:114`; w9, regravar o já lido, morto em `:113`) | PASS |
| Premissa "O sino": os 10 mais recentes, não lidos primeiro, `AAAA-MM-DD HH:MM` em São Paulo | ordem; 10; fuso | Ordem e fuso: `:62-67` (w3 e w10 mortos em `:62`; `'2026-10-19 10:00'` de `13:00` UTC). **10**: `:79` `assertCount(NotificationPresenter::LIMIT, …)` afirma a constante, não o 10: `LIMIT = 11` sobrevive (w5); `LIMIT = 20` só morre porque o teste tem 12 avisos (w4) | ⚠️ GAP (Fix 4) |
| Login | rota pede login | `NotificationsTest.php:122-125` | PASS |

#### ARQ-01: arquivar e desarquivar (P2, AC 1 a 6)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: arquiva com motivo → `archived_at`, `archive_reason`, `archived_by`; etapa, sprint e filhos iguais; sai do quadro e do atraso | os três campos; o resto igual | `TaskArchiveTest.php:85-88` (a1 morto em `:85`, a2 em `:87`); `:90-94` auditoria só dos três campos (a4 morto em `:90`); `:105` `cards` vazio; `:107` `late` falso (l1 morto em `:107`) | PASS |
| AC 2: motivo vazio ou > 500 → recusa no campo | erro em `archive_reason` | `TaskArchiveTest.php:115-117` `assertSessionHasErrors('archive_reason')`; `:118-120` 500 aceito (a5 morto em `:117`) | PASS |
| AC 3: já arquivado → "Card arquivado não muda: desarquive antes." | a mensagem exata | `TaskArchiveTest.php:128`; `:130-131` motivo e auditoria iguais (a3 morto em `:128`) | PASS |
| AC 4: desarquiva → limpa arquivamento e sprint, fim do Backlog, movimento sem retrabalho | os 6 campos; movimento sem `rework_*` | `TaskArchiveTest.php:148` `[null, null, null, null, queue, 10]` (n1, n2, n4, n6 mortos); `:150` movimento `[code, queue, gestor, null, null]`; `:152-156` auditoria `unarchived` (n7 morto); `:167-168` já no Backlog: fim e sem movimento novo (n3 morto em `:168`); `:178` etapa removida → Backlog; `:185` o não arquivado (n5 morto) | PASS |
| AC 5: o dev arquiva ou desarquiva → 403 e nada muda | 403; `denied`/`archived` | `TaskArchivePolicyTest.php:98-100` (`assertOneDenied`, ação `archived` nas três, `:99` antes da validação), `:103-104` (z1 morto em `:78` "arquivar"; z2 e z7 em `:78` "desarquivar") | PASS |
| AC 6: filtro *Arquivados* → lista do mais recente ao mais antigo, com chave, título, tipo, data e motivo | as 5 colunas; a ordem | `TaskArchiveTest.php:304` `['SGM-2', 'SGM-1']` (b3 morto); `:305-308` as colunas, data em São Paulo (p1 morto em `:305`); `:309` card de etapa removida (b1 morto em `:304`); `:310-311` a busca vale; `BoardTest.php:141-142` `cards` vazio e a chave em `archived` | PASS |

#### Edge cases e premissas

| Caso | Evidência | Resultado |
|---|---|---|
| Card de outro projeto pela rota deste → 404 | `TaskArchivePolicyTest.php:128-131` | PASS |
| Admin e gestor alocado arquivam, desarquivam e excluem (L-016) | `TaskArchivePolicyTest.php:113-119` | PASS |
| Card arquivado nunca usado também se exclui | `TaskArchiveTest.php:227-234` | PASS |
| Card excluído com o projeto encerrado: pode | sem teste; o código não confere encerramento (`TaskArchiveService::delete`, `TaskPolicy::delete` → `ProjectPolicy::manage`) | ⚠️ premissa sem teste (não é AC) |
| Desarquivar vale para o card de suporte | sem teste próprio; o `unarchive` não distingue o tipo | ⚠️ premissa sem teste (não é AC) |

---

### Discrimination Sensor

**Método.** O script `/tmp/verifier-b3-pr3/run.py` grava os resultados em `results.json`, a saída inteira em `out-<id>.txt` e o log em `run-log.txt`. Ele roda na árvore real, um mutante por vez:
1. confere o arquivo limpo (`git status --porcelain -- <arquivo>`);
2. aplica a troca exata, que falha se o trecho não for único;
3. roda `php -l` e `php artisan test --compact --stop-on-failure <testes>`;
4. reverte com `git checkout -- <arquivo>` e confere limpo de novo.

Não usei `git reset` nem `stash`. `git status --porcelain` estava vazio antes e saiu vazio no fim. O "quem matou" vem da linha `at tests/…:N` da saída; no provider `usos`, o caso sai da contagem de testes que passaram antes da falha.

Conjuntos: **ARCH** = `TaskArchiveTest`; **POL** = `TaskArchivePolicyTest`; **USE** = `TaskUsageQueryTest`; **NOT** = `NotificationsTest`; **BOARD** = `BoardTest`.

| # | Mutante | `arquivo:linha` | Testes | Resultado | Quem matou |
|---|---|---|---|---|---|
| u1 | `isUnused` sem `cross_tested_at` | `TaskUsageQuery.php:41` | ARCH+USE | morto | `TaskArchiveTest.php:258` ("teste cruzado") |
| u2 | `isUnused` sem `sprint_id` | `TaskUsageQuery.php:41` | ARCH+USE | morto | `:258` ("na sprint") |
| u3 | `movements_count > 1` → `> 2` | `TaskUsageQuery.php:51` | ARCH+USE | morto | `:258` ("segundo movimento") |
| u4 | checagem sem `timeEntries` | `TaskUsageQuery.php:55` | ARCH+USE | morto | `:258` ("horas lançadas") |
| u5 | checagem sem `comments` | `TaskUsageQuery.php:55` | ARCH+USE | morto | `:258` ("comentário") |
| u6 | checagem sem `attachments` | `TaskUsageQuery.php:55` | ARCH+USE | morto | `:258` ("anexo") |
| u7 | checagem sem `pullRequests` | `TaskUsageQuery.php:55` | ARCH+USE | morto | `:258` ("link de PR") |
| u8 | checagem sem `productionBugs` (`origin_task_id`) | `TaskUsageQuery.php:55` | ARCH+USE | morto | `:258` ("bug de produção") |
| u9 | checagem sem `children` (`parent_task_id`) | `TaskUsageQuery.php:55` | ARCH+USE | morto | `:258` ("card filho") |
| u10 | [extra] `time_entries.task_id` fora de `USES` | `TaskUsageQuery.php:27` | ARCH+USE | morto | `TaskArchiveTest.php:258`; só com USE, `TaskUsageQueryTest.php:33` ("FK para tasks sem decisão") |
| u11 | [extra] `task_comments` movido de `USES` para `GOES_ALONG` (é CASCADE) | `TaskUsageQuery.php:19-28` | USE+ARCH | morto | USE passa (classificada e CASCADE); `TaskArchiveTest.php:258` ("comentário") |
| u12 | [extra] `movements_count > 1` → `> 0` | `TaskUsageQuery.php:51` | ARCH+USE | morto | `TaskArchiveTest.php:201` |
| d1 | `delete` sem `lockForUpdate` da linha do card | `TaskArchiveService.php:97` | ARCH+POL | **sobreviveu** | nenhum (29 passed). Não equivalente: sonda s1. Fix 1 |
| d2 | `delete` sem a checagem de uso | `TaskArchiveService.php:99` | ARCH+POL | morto | `TaskArchiveTest.php:260` |
| d3 | `delete` sem o update dos avisos | `TaskArchiveService.php:110-113` | ARCH | morto | `:287` |
| d4 | update dos avisos sem `where('data->key', $key)` | `TaskArchiveService.php:112` | ARCH | morto | `:289` (o aviso do SGM-2 perdeu o link) |
| d5 | `delete` sem a auditoria | `TaskArchiveService.php:116` | ARCH+POL | morto | `:210` (`sole()`) |
| d6 | [sonda AC 1] auditoria `deleted` em `DB::afterCommit` | `TaskArchiveService.php:116` | ARCH+POL | **sobreviveu** | nenhum (29 passed); a sonda s2 mata. Fix 2 |
| d7 | [sonda EXC-02 AC 1] update dos avisos em `DB::afterCommit` | `TaskArchiveService.php:110-113` | ARCH | **sobreviveu** | nenhum (23 passed); a sonda s3 mata. Fix 2 |
| d8 | [extra EXC-02 AC 1] `whereIn` sem `TaskReturned` | `TaskArchiveService.php:111` | ARCH | **sobreviveu** | nenhum (23 passed). Fix 3 |
| d9 | [extra] apaga os avisos em vez de tirar o link | `TaskArchiveService.php:113` | ARCH | morto | `:287` (`Undefined array key "SGM-1"`) |
| n1 | `unarchive` não limpa `sprint_id` | `TaskArchiveService.php:74` | ARCH+POL | morto | `:148` |
| n2 | `unarchive` não move ao Backlog | `TaskArchiveService.php:75` | ARCH+POL | morto | `:148` |
| n3 | `unarchive` grava o movimento sempre | `TaskArchiveService.php:78` | ARCH+POL | morto | `:168` |
| n4 | `unarchive` no início da fila (`position` 0) | `TaskArchiveService.php:76` | ARCH+POL | morto | `:148` |
| n5 | [extra] `unarchive` sem a checagem de arquivado | `TaskArchiveService.php:62` | ARCH+POL | morto | `:185` |
| n6 | [extra] `unarchive` não limpa `archived_by` | `TaskArchiveService.php:73` | ARCH+POL | morto | `:148` |
| n7 | [extra] `unarchive` sem auditoria | `TaskArchiveService.php:82` | ARCH+POL | morto | `:152` (`sole()`) |
| a1 | `archive` não grava `archived_by` | `TaskArchiveService.php:46` | ARCH+POL | morto | `:85` |
| a2 | `archive` limpa `sprint_id` | `TaskArchiveService.php:46` | ARCH+POL | morto | `:87` |
| a3 | [extra] `archive` sem `lockTask` (aceita arquivado) | `TaskArchiveService.php:43` | ARCH+POL | morto | `:128` |
| a4 | [extra] `archive` sem auditoria | `TaskArchiveService.php:47` | ARCH+POL | morto | `:90` |
| a5 | [extra] motivo até 501 | `ArchiveTaskRequest.php:23` | ARCH | morto | `:117` |
| a6 | [extra] motivo sem `trim()` | `ArchiveTaskRequest.php:28` | ARCH | equivalente | o `TrimStrings` global (`vendor/…/Foundation/Configuration/Middleware.php:461`, não tirado em `bootstrap/app.php`) já apara antes; `:85` passa com `'  Cliente desistiu  '` |
| z1 | `ArchiveTaskRequest` com ability `view` | `ArchiveTaskRequest.php:15` | POL+ARCH | morto | `TaskArchivePolicyTest.php:78` ("arquivar") |
| z2 | `unarchive` sem `authorize('archive')` | `TaskController.php:95` | POL | morto | `:78` ("desarquivar") |
| z3 | `destroy` sem `authorize('delete')` | `TaskController.php:103` | POL | morto | `:78` ("excluir") |
| z4 | `abilities.delete` sem `isUnused` | `TaskDetailPresenter.php:162` | ARCH+POL | morto | `TaskArchiveTest.php:258` |
| z5 | [extra] `abilities.delete` sem `can('delete')` | `TaskDetailPresenter.php:162` | ARCH+POL | morto | `:272` |
| z6 | [extra] `abilities.archive` com `view` | `TaskDetailPresenter.php:161` | ARCH+POL | morto | `:272` |
| z7 | [extra] `unarchive` com `authorize('view')` | `TaskController.php:95` | POL | morto | `TaskArchivePolicyTest.php:78` ("desarquivar") |
| z8 | [extra] `destroy` com `authorize('archive')` (a mesma regra hoje) | `TaskController.php:103` | POL | morto | `:78` ("excluir": a negação grava `archived`, não `deleted`) |
| w1 | `read` em `DatabaseNotification::query()` | `NotificationController.php:18` | NOT | morto | `NotificationsTest.php:98` (302 em vez de 404) |
| w2 | `readAll` sem o escopo da pessoa | `NotificationController.php:25` | NOT | morto | `:114` |
| w3 | sino sem "não lidos primeiro" | `NotificationPresenter.php:26` | NOT | morto | `:62` |
| w4 | `LIMIT` 10 → 20 | `NotificationPresenter.php:17` | NOT | morto | `:79` (12 em vez de 20: só porque o teste tem 12 avisos) |
| w5 | [sonda] `LIMIT` 10 → 11 | `NotificationPresenter.php:17` | NOT | **sobreviveu** | nenhum: `:79` compara com a constante. Fix 4 |
| w6 | [extra] `unread_count` sem o escopo | `NotificationPresenter.php:23` | NOT | morto | `:61` (3 em vez de 2) |
| w7 | [extra] itens sem o escopo | `NotificationPresenter.php:24` | NOT | morto | `:62` |
| w8 | [extra] `read` marca todos os meus | `NotificationController.php:18` | NOT | morto | `:91` |
| w9 | [extra] `readAll` regrava o `read_at` dos já lidos | `NotificationController.php:25` | NOT | morto | `:113` |
| w10 | [extra] mais antigos primeiro | `NotificationPresenter.php:27` | NOT | morto | `:62` |
| l1 | `LateQuery` sem `archived_at === null` | `LateQuery.php:29` | ARCH+BOARD | morto | `TaskArchiveTest.php:107` |
| b1 | `BoardQuery`: `whereHas` da etapa ativa também na lista de arquivados | `BoardQuery.php:38` | ARCH+BOARD | morto | `TaskArchiveTest.php:304` |
| b2 | [extra] quadro sem o `whereHas` da etapa ativa | `BoardQuery.php:62` | ARCH+BOARD | equivalente | nenhum card ativo fica em etapa removida: `BoardStageService::archive` move os ativos antes (`BoardStageService.php:193-200`), o `TaskMoveService` recusa etapa removida (`TaskMoveService.php:61`), e o desarquivar vai ao Backlog |
| b3 | [extra] arquivados do mais antigo ao mais novo | `BoardQuery.php:61` | ARCH+BOARD | morto | `TaskArchiveTest.php:304` |
| p1 | [extra] "Arquivado em" em UTC | `TaskPresenter.php:108` | ARCH+BOARD | morto | `TaskArchiveTest.php:305` |

**Total**: 57 mutantes (30 pedidos + 27 extras e sondas): 50 mortos, 2 equivalentes (a6, b2), 5 sobreviventes (d1, d6, d7, d8, w5). Contados: 50/55.
**Sensor depth**: P0, manual.

### Sondas (rascunho em `/tmp/verifier-b3-pr3/`, fora do repositório)

Rodadas com `php artisan test --compact /tmp/verifier-b3-pr3/<arquivo>`, com `DatabaseTruncation` + `UsaBancoDeVerdade` e sem `RefreshDatabase`. Cada uma rodou contra o código real e contra o mutante, revertido com `git checkout --` (porcelain vazio depois).

| Sonda | O que faz | Código real | Mutante |
|---|---|---|---|
| s1 `SondaTravaExclusaoTest.php` | `Concorrencia::intercalarDepoisDe` dispara na consulta do `isUnused` (dentro da transação, `transactionLevel` 1). Outra conexão relê o card e grava um comentário pelo `TaskCommentService::add`, que não trava projeto nem card (`TaskCommentService.php:23`) | `bloqueada=true codigo=1205`; card excluído, 0 comentários, 0 auditorias `created` de comentário | d1: `bloqueada=false`. O comentário é commitado (1 auditoria `created`) e depois **apagado pelo CASCADE** de `task_comments.task_id` (`2026_10_06_000360…:22`) junto com o card: o card usado sai e o comentário some, com a auditoria apontando para nada |
| s2 `SondaTransacaoExclusaoTest::…audit_fails` | `AuditService` que lança no `log` → o card e o link do aviso continuam | 2 passed | d6: falha em `:72` (o card saiu antes da auditoria) |
| s3 `SondaTransacaoExclusaoTest::…same_transaction` | dentro de uma transação de fora, o link do aviso já está nulo antes do commit | 2 passed | d7: falha em `:80` (o link só sairia depois do commit de fora) |

Um detalhe que custou uma volta na s1: a 1ª versão gravou o comentário pelo `$task` da conexão principal (o model Eloquent carrega a conexão em que nasceu), e o comentário entrou na própria transação da exclusão. A versão válida relê o card na outra conexão. Quem escrever o Fix 1 precisa do mesmo cuidado.

---

### Code Quality (escopo do trilho completo)

| Princípio | Status |
|---|---|
| Minimum code / Surgical changes | ✅ Um Service de 129 linhas, uma Query de 63, um FormRequest, dois métodos de controller, um Presenter; sem Policy nova nem migração, como a spec diz |
| Matches patterns | ✅ Projeto → card travados (`WritesTasks`), auditoria pelo `AuditService::log` na transação, `ActsOnCard` autoriza antes de validar, negação pelo `authorize()` do Controller base, Presenter para a linha (`archivedRow`, `NotificationPresenter`) |
| Defense in depth | ✅ A ficha esconde o *Excluir* e o Service recusa pela mesma conta (`TaskUsageQuery`); o aviso é procurado só entre os da pessoa (`NotificationController.php:18`) |
| Spec-anchored outcome check | ⚠️ EXC-01 AC 1 e EXC-02 AC 1 sem prova da transação; EXC-02 AC 1 sem `TaskReturned` e com "contém" no lugar do mesmo texto; o "10" do sino afirmado pela constante (Fix 1 a 4) |
| Every test maps to a spec requirement | ✅ Cada arquivo cita EXC/AVI/ARQ no docblock |

Achados que não bloqueiam (detalhe):
- O nome `TaskUsageQueryTest::test_what_goes_along_is_cascade_and_nothing_else_is` promete "nothing else is", mas o teste só afirma que os de `GOES_ALONG` são CASCADE. `task_comments.task_id` e `task_pull_requests.task_id` estão em `USES` e também são CASCADE (`000360:22`, `000350:22`), e é por isso que a trava do card importa (s1). Vale trocar o nome.
- `TaskPolicy.php:98` diz que o "só o nunca usado é do TaskService". Hoje são o `TaskArchiveService` e o `TaskUsageQuery`. A Policy está fora do diff.
- O `AGENTS.md`, em "Um jeito só" (linha do "Card (B3)"), ainda diz que mudar de etapa e de posição é "só pelo `TaskMoveService`". O `unarchive` muda as duas no `TaskArchiveService` (`TaskArchiveService.php:75-76`), pelo trait `WritesTasks`. A linha nova "Tirar do quadro" deixa isso implícito; vale nomear a exceção.
- O código faz duas coisas que a spec não diz, as duas coerentes com o kit:
  - desarquivar um card não arquivado recusa com "O card não está arquivado." (`TaskArchiveService.php:62-64`, teste `:181-189`);
  - com o filtro, `cards` vem vazio (`TaskPresenter.php:42`).

### Fix Plans

Todos só de teste. Nenhum código de produção precisa mudar.

**Fix 1: EXC-01, a trava da linha do card no `delete`** (mata d1; Major)
- Um teste de banco de verdade no molde da sonda s1 (`DatabaseTruncation` + `UsaBancoDeVerdade` + `Concorrencia::intercalarDepoisDe`):
  - o gatilho é a consulta do `isUnused`;
  - a outra requisição grava um comentário com o card **relido na outra conexão**, ou pela rota HTTP do comentário;
  - afirmar `rodou`, `outroErro === null`, `bloqueada` (1205), o card excluído e nenhum comentário nem auditoria `created` de comentário.
- Alternativa no molde do `TravaDeleteIfUnusedTest`: um `TaskUsageQuery` de teste que, no `isUnused`, pergunta `Concorrencia::linhaEstaTravada('tasks', id)`.

**Fix 2: EXC-01 AC 1 e EXC-02 AC 1, "na mesma transação"** (mata d6, d7; Major; L-017, L-020)
- Com `DatabaseTruncation`, sem `RefreshDatabase`, e `transactionLevel()` 0 no início: um `TaskArchiveService` com um `AuditService` que lança no `log`. Afirmar que o card, os critérios, as participações, o movimento de criação e o `url` do aviso continuam.
- Dentro de uma transação de fora: depois do `delete`, o `url` do aviso já está nulo antes do commit (molde do `B3FichaTransacaoTest.php:134-150`; a sonda s3 faz isso).

**Fix 3: EXC-02 AC 1, `TaskReturned` e o mesmo texto** (mata d8; Major)
- Em `TaskArchiveTest::test_the_notices_of_a_deleted_card_stay_without_the_link_and_the_others_keep_it` (`:277-290`), um aviso `TaskReturned` do card excluído e um de outro card: `url` nula no primeiro, link no segundo.
- Trocar `assertStringContainsString` (`:288`) por `assertSame` com o `text` gravado antes da exclusão, nos dois tipos.

**Fix 4: o sino mostra 10** (mata w5; Minor)
- Em `NotificationsTest::test_the_bell_shows_the_ten_most_recent` (`:79`), afirmar `assertCount(10, …)` com o número da spec, não `NotificationPresenter::LIMIT`, e que `Aviso 2` não está na lista.

#### Para o humano decidir (não é Fix Plan)

- **A spec não está aprovada.** `Approved by: aguardando Lucas Scholz`, e o trilho completo pede aprovação antes do código (`AGENTS.md`, "Trilhos"). Com este relatório, o `specs:check` cobra a aprovação e as 13 premissas sem `y` (ver Gate Check). A open question, se os avisos do card excluído ficam sem link ou saem junto, segue aberta, e o código implementa o padrão escolhido (ficam, sem link). Se o Lucas decidir "saem junto", o d9 deixa de ser mutante e vira o comportamento, e o EXC-02 muda.
- Duas premissas sem teste, que não são AC: "card excluído com o projeto encerrado: pode" e "desarquivar vale para o card de suporte". O código as cumpre: não confere encerramento e não distingue o tipo.

### Requirement Traceability Update

| Requirement ID | Status | Teste |
|---|---|---|
| EXC-01 | ❌ Needs Fix: AC 2 a 7 PASS; AC 1 sem prova da transação (Fix 2); a trava da linha sem teste (Fix 1) | `TaskArchiveTest.php:193-273`; `TaskArchivePolicyTest.php:71-120`; `TaskUsageQueryTest.php:33`, `:48` |
| EXC-02 | ❌ Needs Fix: AC 2 PASS; AC 1 sem `TaskReturned`, com "contém" e sem prova da transação (Fix 2, Fix 3) | `TaskArchiveTest.php:277-290` |
| AVI-01 | ✅ Verified (AC 1 a 4); o "10" da premissa do sino no Fix 4 | `NotificationsTest.php:52-126` |
| ARQ-01 | ✅ Verified | `TaskArchiveTest.php:76-189`, `:294-312`; `TaskArchivePolicyTest.php:98-104`; `BoardTest.php:141-142` |

O `spec.md` não foi editado: o Verifier só reporta.

### Summary

**Overall**: ❌ Not ready. Quatro Fix Plans, todos de teste.

**Spec-anchored check**: 2 de 4 requisitos verificados (AVI-01, ARQ-01). EXC-01 e EXC-02 com lacuna de prova.
**Sensor**: 50/55 mortos; 2 equivalentes, cada um com a guarda nomeada; 5 sobreviventes, com 3 deles (d1, d6, d7) confirmados não equivalentes por sonda.
**Gate**: 702 passed (5768 assertions), de novo 702 passed depois do sensor e das sondas (111,06 s, exit 0); Pint, typecheck e lint verdes; specs:check verde antes deste relatório e com 15 problemas depois (o FAIL, a aprovação e as 13 premissas).

**Lições** (`lessons.py`): não gravei, porque a instrução desta rodada era deixar só este arquivo novo na árvore. As candidatas, para quem conduzir o fechamento:
- L-017 se repete numa 3ª feature: a exclusão e a auditoria "na mesma transação", provadas só com `RefreshDatabase`.
- Nova: a prova de trava com `Concorrencia` precisa reler o model na outra conexão, porque a instância Eloquent carrega a conexão em que nasceu.
- Nova: o número que a spec fixa (o 10 do sino) se afirma literal, não pela constante do código.
- Nova: o AC que nomeia dois tipos (`TaskAssigned` e `TaskReturned`) pede um caso de cada.

**Next steps**:
1. Quem implementou aplica os Fix 1 a 4 (só testes) num commit local.
2. Verifier rodada 2 em agente separado: os 5 sobreviventes de novo e uma amostra dos mortos.
3. Lucas Scholz aprova a spec e responde à open question antes do PR.
