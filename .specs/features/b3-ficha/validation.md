# B3 PR 2 (a ficha do card: horas, comentário do autor, exclusões de verdade e as colunas de "quem") Validation

## Validation: FAIL

**Result**: FAIL na rodada 1 (20/10). Nenhum defeito de comportamento achado; cinco lacunas de teste em FCH-03 e MIG-04.

O que está provado: as duas Policies (horas e comentário), o `authorize()` de cada uma das 14 rotas da ficha (15 pernas), a negação com ação e alvo exatos, o "só A testar" do critério e o cascade dos subitens, o arquivo do anexo que sai, as regras de card pai, tarefa de origem e capa, a perna `manageSprint` do PATCH, o aviso a quem se define e o benchmark da ficha. FCH-01, FCH-02 e FCH-04 estão ✅ Verified.

As lacunas, todas só de teste (o código fez a coisa certa em toda checagem manual):
- **FCH-03 AC 5, auditoria da exclusão**: a linha `deleted` do link de PR e a do anexo não são afirmadas (e6b, e6d sobrevivem).
- **FCH-03 AC 5, "na mesma transação"**: nenhuma das quatro exclusões prova a transação; a auditoria levada para depois do commit sobrevive nas quatro (e7a a e7d). O arquivo do anexo apagado antes do commit, ou antes da linha, também sobrevive (e4, e4b).
- **MIG-04 AC 2 / P2 AC 2**: a ficha não tem asserção do nome de quem colou, enviou ou propôs, nem do nulo das linhas antigas (k1, k2, k3 sobrevivem).
- **MIG-04, `down()` da 000040**: nenhum teste roda o `down()`. O `down()` sem `dropForeign` sobrevive na suíte (h1); à mão, o `migrate:rollback` com esse mutante falha com o erro 1828 do MySQL. A migração real vai e volta sem erro.
- **Filho de outro card, PR e anexo** (sonda de 2ª ordem): sem o scoped binding e sem o `belongsToCard`, excluir PR, excluir anexo e baixar anexo de outro card passam sem teste que os pegue (d10c, d10d, d10e). O caso "baixar anexo de outro card" que existe dá 404 pelo motivo errado (o arquivo não está no disco).

Sensor: 66 mutantes pedidos + 8 de confirmação = 74; 48 mortos, 11 equivalentes, 15 sobreviventes (48/63 contados).

---

## Rodada 1 (20/10)

**Date**: 2026-10-20
**Spec**: `.specs/features/b3-ficha/spec.md` (FCH-01 a FCH-04, MIG-04), aprovada por Lucas Scholz em 20/10/2026, com todas as premissas confirmadas
**Diff range**: `9476165..bd33e54` (a `main` com o #16 e o #17 até o head do PR WCJ-Tecnologia/wcj-hub#18, já com os 7 achados do code review ciclo 1). Escopo do trilho completo:
- `TaskPolicy::proposeHours` e `TaskCommentPolicy` (nova), com o registro no `AppServiceProvider` e o `proposeHours` no `AuditActionEnum::forAbility`;
- os pontos de autorização das 14 rotas da ficha: `TaskController::show`, `UpdateTaskRequest` (pernas `update` e `manageSprint`), `ProposeHoursRequest`, `TaskDetailController::approveHours/rejectHours`, `CardTextRequest` (pernas `comment`, `update` do comentário e `update` do card), `destroyCriterion`, `destroyPullRequest`, `destroyComment`, `StoreAttachmentsRequest`, `destroyAttachment`, `downloadAttachment`;
- as quatro exclusões de verdade: `CriterionService::delete`, `TaskPullRequestService::remove`, `TaskCommentService::delete`, `AttachmentService::remove`;
- a migração `2026_10_16_000040_add_quem_to_task_records` e quem grava as colunas;
- de apoio, pedidos no prompt: `TaskEditService::assertRelations`, o aviso `TaskAssigned` do `TaskAssignmentService`, o `TaskDetailPresenter` (benchmark).

O resto da ficha é trilho leve e ficou fora do veredito.
**Verifier**: sessão separada de quem implementou (Cauã Soares, com os ajustes do ciclo 1), sem acesso ao raciocínio dela

### Gate Check (árvore real, HEAD `bd33e54`, sem edição)

| Comando | Resultado |
|---|---|
| `composer lint` (`pint --test`) | `{"tool":"pint","result":"passed"}`, exit 0 |
| `php artisan test --compact` (MySQL 8, `wcj-hub-mysql-1`, `wcj_hub_test`) | **659 passed (5489 assertions)**, 122,83 s, exit 0 |
| `npm run typecheck` (`tsc --noEmit`) | exit 0 |
| `npm run lint` (`eslint .`) | exit 0 |
| `npm run specs:check` (antes deste `validation.md`) | `conferir-specs: 5 feature(s), 0 problema(s)`; 2 avisos antigos do `b1-nucleo` (T35, T50) |
| Base do sensor | `TaskDetailPolicyTest` + `TaskDetailTest` + `MigracaoCardDoQuadroTest`: 26 passed (353 assertions) |
| `migrate:rollback --step=1` + `migrate` (`--env=testing`) | a 000040 desce `DONE` (as 4 colunas somem: `Schema::hasColumn` = 0 nas quatro) e sobe `DONE` (as 4 voltam, `tasks` com as FKs). Sem teste automatizado que faça isso (Fix 4) |

Não rodei Playwright, Storybook nem build: estão fora do trilho completo (o commit `bd33e54` declara e2e da ficha e do quadro verdes).

### Spec-Anchored Acceptance Criteria

#### FCH-01: horas, o dev propõe e o gestor decide (P1 "Horas", AC 1 a 4)

| Critério | Valor da spec | Evidência (`arquivo:linha` + asserção) | Resultado |
|---|---|---|---|
| AC 1: o dev alocado propõe → grava `proposed_hours` e `proposed_by`, sem mexer em `estimated_hours` nem em `due_on` | gravado; horas e data iguais | `TaskDetailTest.php:216-217` `assertSame(['8.00', '12.00', $dev->id], [estimated_hours, proposed_hours, proposed_by])`. `due_on` só muda quando `estimated_hours` fica sujo (`TaskHoursService::write`); o k8 (propor também troca as horas) morre em `:217` | PASS |
| AC 2: quem gere tenta propor → 403 | 403 | `TaskDetailPolicyTest.php:153-160` `assertOneDenied(..., 'updated', 'task', $task->id, 'gestor propõe')->assertForbidden()` e `:159` proposta intacta (`'5.00'`); `TaskDetailTest.php:220` | PASS |
| AC 3: o dev define horas direto, aprova ou recusa → 403, nada muda | 403; retrato igual | `TaskDetailPolicyTest.php:141-151`: PATCH `estimated_hours` `:146`, aprovar `:147`, recusar `:148`, os três `assertOneDenied(..., 'updated', 'task', null, ...)->assertForbidden()`; `:150` retrato igual. `TaskDetailTest.php:184`, `:219` | PASS |
| AC 4: quem gere aprova → proposta vira `estimated_hours` e refaz a data prevista; recusar só limpa | aprovado; data refeita; recusa limpa | `TaskDetailTest.php:221-222` `assertSame(['12.00', null, null], [...])`; `:225-226` recusa: `['12.00', null]`. A data refeita é o `TaskHoursService::write` do B5, provado em `DueDateTest.php:160` e `:182` (`approveProposal`); o k9 (sem refazer a data) morre em `DueDateTest.php:125` | PASS |

#### FCH-02: comentário só do autor (P1, AC 1 a 3)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: o autor, ainda no projeto, edita e exclui → grava, o antes na auditoria | gravado; antes em `changes` | `TaskDetailTest.php:313-318`: editar `assertSame('Olhei o layout e o fluxo', ...)`; excluir `:317` 0 comentários; `:318` a linha `deleted` `success` com `changes['body'][0]` = o texto de antes. Ability `can` por comentário: `:80` `['update' => true, 'delete' => true]` | PASS |
| AC 2: outra pessoa, inclusive Administrador e gestor → 403, comentário mantido | 403 | `TaskDetailPolicyTest.php:162-171` (admin e gestor, editar e excluir, `assertOneDenied(..., 'task_comment', $c, ...)`), `:170` texto intacto; `TaskDetailTest.php:311-312` | PASS |
| AC 3: o autor que saiu do projeto → 403 | 403 | `TaskDetailPolicyTest.php:173-183` (membro removido; editar e excluir negados, `:182` intacto) | PASS |

#### FCH-03: exclusões de verdade só do que a regra deixa (P1, AC 1 a 6)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: alocado exclui anexo → arquivo e linha saem; a capa fica nula | arquivo fora; `cover_attachment_id` nulo | `TaskDetailTest.php:203-207`: `assertNull(cover_attachment_id)` `:205`, `assertMissing($png->path)` `:206`, o outro arquivo fica `:207` | PASS |
| AC 2: alocado exclui critério A testar → ele e os subitens saem, sem renumerar | 0 critérios; números estáveis | `TaskDetailTest.php:268-275` (pai e filho pendentes, `assertSame(0, count)`); sem renumerar: `:231-247` (`[['Dois', null, 2], ['Três', null, 3], ...]`) | PASS |
| AC 3: critério ou subitem já testado → "CA_3 já foi testado: edite em vez de excluir." e nada sai | a mensagem exata; 2 linhas | `TaskDetailTest.php:258-266`: `assertSessionHas('error', 'CA_3 já foi testado: edite em vez de excluir.')` `:263`, `:265` 2 critérios | PASS |
| AC 4: alocado exclui link de PR → linha sai | 0 PRs | `TaskDetailTest.php:302-303` | PASS |
| AC 5: em qualquer das quatro, a linha `deleted` vai **na mesma transação**, com o antes | linha `deleted` com o antes; mesma transação | Critério: `TaskDetailTest.php:276-277` (`sole()` e o antes do pai e do filho). Comentário: `:318`. **PR e anexo: nenhuma asserção da linha `deleted`** (e6b, e6d sobrevivem). **Mesma transação: sem prova em nenhuma das quatro** (e7a a e7d sobrevivem; o `RefreshDatabase` envolve o teste numa transação e esconde a falta dela, L-017). O arquivo apagado antes do commit também sobrevive (e4, e4b) | ❌ GAP (Fix 1, Fix 2) |
| AC 6: quem não vê o projeto tenta qualquer exclusão → 403, nada muda | 403; retrato igual; arquivo no disco | `TaskDetailPolicyTest.php:127`, `:129`, `:132`, `:135` (`assertOneDenied` + `assertForbidden()`), `:137` retrato igual, `:138` `assertExists('tasks/a.pdf')` | PASS |

#### FCH-04: negação auditada com a ação certa

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| Toda negação das rotas da ficha → exatamente uma linha nova `denied`, desta pessoa, com a ação e o alvo; nenhuma `success` | uma linha; `outcome`, `action`, `auditable_type`, `auditable_id`, `user_id` | Helper `TaskDetailPolicyTest.php:90-104` (`assertCount(1, $rows)` e `assertSame(['denied', $action, $type, $id, $user->id], ...)`), aplicado nas 16 requisições de `:120-135` (provider `quemNaoVe`: dev não alocado, gestor que não gere, Comercial), em `:146-148`, `:157`, `:166-167`, `:179-180`. `task` com o id do card; `task_comment` com o id do comentário; `task` com id nulo em `manageSprint` (recebe a classe e o projeto, L-021). Mapeamento `proposeHours → updated`: `tests/Unit/AuditActionEnumTest.php:60` e o l1 morto em `:122` | PASS |

#### MIG-04: as 4 colunas de "quem" (P2, AC 1 e 2)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: colar link, enviar anexo, propor horas → grava quem | `user_id`, `user_id`, `proposed_by` | PR `TaskDetailTest.php:301`; anexo `:341`; proposta `:217`, e a aprovação limpa `:222` (k4 a k7 mortos) | PASS |
| AC 2: a ficha mostra o nome, ou "—" nas linhas de antes | `added_by`, `uploaded_by`, `proposed_by` com nome; nulo nas antigas | **Nenhuma asserção.** O contrato (`TaskDetailTest.php:63-89`) não tem PR, anexo nem proposta; `added_by → null`, `uploaded_by → null` e `proposed_by → null` no `TaskDetailPresenter` sobrevivem (k1, k2, k3) | ❌ GAP (Fix 3) |
| Migração aditiva, nula, FK `restrict`, idempotente, com `down()` | as 4 colunas; vai e volta | À mão: `rollback --step=1` + `migrate` sem erro (Gate Check). **Sem teste**: o `down()` sem `dropForeign` (h1) passa na suíte; à mão quebra com `SQLSTATE[HY000]: General error: 1828 Cannot drop column 'user_id': needed in a foreign key constraint` | ⚠️ GAP (Fix 4) |

#### Edge cases e premissas do PR

| Caso | Evidência | Resultado |
|---|---|---|
| Filho (critério, PR, comentário, anexo) de outro card → 404 | Comentário `TaskDetailPolicyTest.php:190-191`; critério `TaskDetailTest.php:285-286`. São duas guardas: o `Route::scopeBindings()` (`routes/web.php:129`) resolve o filho pela relação do card, e o `belongsToCard` repete. Tirar uma só é equivalente (d1 a d9, d11); tirar as duas morre no comentário e no critério (d10a, d10b, d10f, d10g), **não no PR nem no anexo** (d10c, d10d, d10e); o "baixar anexo de outro card" (`TaskDetailPolicyTest.php:192-193`) dá 404 porque `tasks/b.pdf` não está no disco | ⚠️ GAP (Fix 5, sonda) |
| Card pai e tarefa de origem: mesmo projeto, nunca o próprio, um nível | `TaskDetailTest.php:150-155` (f1 a f4 mortos) | PASS |
| Capa: imagem do próprio card | `TaskDetailTest.php:198-199` (f5, f6 mortos) | PASS |
| Aviso `TaskAssigned`: não a quem se define | `TaskDetailTest.php:176` (i1 morto) | PASS |
| Benchmark da ficha (CR-1-01) | `TaskDetailTest.php:92-109` (j1, j2 mortos em `:107`) | PASS |

---

### Discrimination Sensor

**Método.** Na árvore real, um mutante por vez, pelo script `/tmp/b3-ficha-sensor-r1/run.py` (resultados em `results.json`, saída inteira em `out-<id>.txt`). Para cada mutante, o script:
1. confere os arquivos limpos (`git status --porcelain -- <arquivo>`);
2. aplica a troca exata, que falha se o trecho não for único;
3. roda `php artisan test --compact --stop-on-failure <testes>`;
4. reverte com `git checkout -- <arquivo>` e confere limpo de novo.

Sem `git reset` nem `stash`. O h1 também foi aplicado à mão para o `migrate:rollback` e revertido do mesmo jeito; o l2 foi conferido no `tinker` (`Gate::getPolicyFor(TaskComment::class)` sem o registro). `git status --porcelain` saiu vazio no fim, e a base (26 testes) passou de novo depois de tudo.

Conjuntos: **POL** = `TaskDetailPolicyTest`; **DET** = `TaskDetailTest`; **MIG** = `MigracaoCardDoQuadroTest`; **PROJ** = `tests/Feature/Projetos` inteiro. Os `authorize()` (grupo c) rodaram só contra o POL, como pedido, sem o A5.

| # | Mutante | Testes | Resultado | Quem matou |
|---|---|---|---|---|
| a1 | `TaskPolicy::proposeHours` → `true` | POL+DET | morto | `TaskDetailPolicyTest.php:122` ("propor horas", quem não vê) |
| a2 | `proposeHours` → `false` | POL+DET | morto | `TaskDetailTest.php:83` (`abilities.propose_hours`) |
| a3 | `proposeHours` sem o `! manageSprint` | POL+DET | morto | `TaskDetailPolicyTest.php:157` ("gestor propõe") |
| b1 | `TaskCommentPolicy::ownsAndSees` → `true` | POL+DET | morto | `TaskDetailPolicyTest.php:131` ("editar comentário") |
| b2 | só `user_id ===`, sem `can('view')` | POL+DET | morto | `TaskDetailPolicyTest.php:179` ("autor que saiu edita") |
| b3 | só `can('view')`, sem o dono | POL+DET | morto | `TaskDetailPolicyTest.php:166` ("admin edita") |
| c01 | `TaskController::show` sem `authorize('view')` | POL | morto | `TaskDetailPolicyTest.php:120` ("ficha", nenhuma linha) |
| c02 | `UpdateTaskRequest`, perna `update`, sem autorizar | POL | morto | `:121` ("editar") |
| c03 | `UpdateTaskRequest`, perna `manageSprint`, sem autorizar | POL | morto | `:146` ("definir horas") |
| c04 | `ProposeHoursRequest` sem autorizar | POL | morto | `:122` |
| c05 | `approveHours` sem `authorize` | POL | morto | `:123` |
| c06 | `rejectHours` sem `authorize` | POL | morto | `:124` |
| c07 | `CardTextRequest`, perna `comment`, sem autorizar | POL | morto | `:130` ("comentar") |
| c08 | `CardTextRequest`, perna `update` do comentário, sem autorizar | POL | morto | `:131` |
| c09 | `CardTextRequest`, perna `update` do card (critério, PR), sem autorizar | POL | morto | `:125` ("critério novo") |
| c10 | `destroyCriterion` sem `authorize` | POL | morto | `:127` |
| c11 | `destroyPullRequest` sem `authorize` | POL | morto | `:129` |
| c12 | `destroyComment` sem `authorize` | POL | morto | `:132` |
| c13 | `StoreAttachmentsRequest` sem autorizar | POL | morto | `:133` |
| c14 | `destroyAttachment` sem `authorize` | POL | morto | `:135` |
| c15 | `downloadAttachment` sem `authorize` | POL | morto | `:134` ("baixar anexo", nenhuma linha) |
| d1 | `destroyCriterion` sem `belongsToCard` | POL+DET | equivalente | o scoped binding dá o 404 antes (confirmado por d10b) |
| d2 | `destroyPullRequest` sem `belongsToCard` | POL+DET | equivalente | idem (d10c) |
| d3 | `destroyComment` sem `belongsToCard` | POL+DET | equivalente | idem (d10a) |
| d4 | `destroyAttachment` sem `belongsToCard` | POL+DET | equivalente | idem (d10d) |
| d5 | `downloadAttachment` sem `belongsToCard` | POL+DET | equivalente | idem (d10e) |
| d6 | `CardTextRequest` sem o 404 do filho (o controller fica) | POL+DET | equivalente | o scoped binding (e o `belongsToCard` do controller) |
| d7 | `updateCriterion` sem `belongsToCard` no controller e no `CardTextRequest` | POL+DET | equivalente | o scoped binding (d10g) |
| d8 | `updateComment` sem `belongsToCard` no controller e no `CardTextRequest` | POL+DET | equivalente | o scoped binding (d10f) |
| d9 | `belongsToCard()` vira no-op e `CardTextRequest` sem 404 | POL+DET | equivalente | o scoped binding |
| d10a | [confirma] `destroyComment` com `withoutScopedBindings()` e sem `belongsToCard` | POL+DET | morto | `TaskDetailPolicyTest.php:191` |
| d10b | [confirma] `destroyCriterion`, idem | POL+DET | morto | `TaskDetailTest.php:286` |
| d10c | [sonda] `destroyPullRequest`, idem | POL+DET | **sobrevivente** | nenhum: Fix 5 |
| d10d | [sonda] `destroyAttachment`, idem | POL+DET | **sobrevivente** | nenhum: Fix 5 |
| d10e | [sonda] `downloadAttachment`, idem | POL+DET | **sobrevivente** | nenhum: o caso de `:193` dá 404 pelo arquivo ausente. Fix 5 |
| d10f | [confirma] `updateComment` sem scoped binding, `belongsToCard` e 404 do Request | POL+DET | morto | `TaskDetailPolicyTest.php:190` |
| d10g | [confirma] `updateCriterion`, idem | POL+DET | morto | `TaskDetailTest.php:285` |
| d11 | [confirma] `destroyComment` só sem scoped binding | POL+DET | equivalente | o `belongsToCard` dá o 404 |
| e1 | `CriterionService::delete` sem o "só A testar" | DET | morto | `TaskDetailTest.php:263` |
| e2 | confere só o critério, não os subitens | DET | morto | `:263` |
| e3 | FK `parent_id` da 000340 `cascade` → `restrict` | DET | morto | `:275` |
| e4 | `AttachmentService::remove` apaga o arquivo antes do commit (sem `afterCommit`) | POL+DET | **sobrevivente** | nenhum: Fix 2 |
| e4b | apaga o arquivo antes da linha | POL+DET | **sobrevivente** | nenhum: Fix 2 |
| e5 | não apaga o arquivo | POL+DET | morto | `TaskDetailTest.php:206` |
| e6a | `CriterionService::delete` sem a auditoria `deleted` | DET | morto | `:276` (`sole()`) |
| e6b | `TaskPullRequestService::remove` sem a auditoria `deleted` | POL+DET | **sobrevivente** | nenhum: Fix 1 |
| e6c | `TaskCommentService::delete` sem a auditoria `deleted` | POL+DET | morto | `TaskDetailTest.php:318` |
| e6d | `AttachmentService::remove` sem a auditoria `deleted` | POL+DET | **sobrevivente** | nenhum: Fix 1 |
| e7a | [sonda AC 5] critério: auditoria em `DB::afterCommit` | PROJ | **sobrevivente** | nenhum: Fix 2 |
| e7b | [sonda AC 5] PR, idem | PROJ | **sobrevivente** | nenhum: Fix 2 |
| e7c | [sonda AC 5] comentário, idem | PROJ | **sobrevivente** | nenhum: Fix 2 |
| e7d | [sonda AC 5] anexo, idem | PROJ | **sobrevivente** | nenhum: Fix 2 |
| f1 | `assertRelations` sem "mesmo projeto" | DET | morto | `TaskDetailTest.php:150` |
| f2 | sem "nunca o próprio card" | DET | morto | `:151` |
| f3 | sem "o pai não é filho" | DET | morto | `:153` |
| f4 | sem "quem vira filho não tem filhos" | DET | morto | `:155` |
| f5 | capa sem `isImage` | DET | morto | `:198` |
| f6 | capa sem `task_id` | DET | morto | `:199` |
| g1 | `UpdateTaskRequest`: perna `manageSprint` trocada por `update` | POL+DET | morto | `TaskDetailPolicyTest.php:146` |
| h1 | 000040: `down()` sem `dropForeign` | MIG | **sobrevivente** | nenhum na suíte (à mão: erro 1828 no rollback). Fix 4 |
| i1 | `TaskAssigned` também a quem se define | DET | morto | `TaskDetailTest.php:176` |
| j1 | `TaskDetailPresenter` sem `loadMissing('members')` | DET | morto | `TaskDetailTest.php:107` (benchmark) |
| j2 | sem `setRelation('task')` nos comentários | DET | morto | `:107` |
| k1 | ficha: `added_by` sempre nulo | DET | **sobrevivente** | nenhum: Fix 3 |
| k2 | ficha: `uploaded_by` sempre nulo | DET | **sobrevivente** | nenhum: Fix 3 |
| k3 | ficha: `proposed_by` sempre nulo | DET | **sobrevivente** | nenhum: Fix 3 |
| k4 | PR colado sem `user_id` | DET | morto | `TaskDetailTest.php:301` |
| k5 | anexo sem `user_id` | DET | morto | `:341` |
| k6 | `propose()` sem `proposed_by` | DET | morto | `:217` |
| k7 | `approveProposal` sem limpar `proposed_by` | DET | morto | `:222` |
| k8 | `propose()` também troca `estimated_hours` | DET | morto | `:217` |
| k9 | `TaskHoursService::write` não refaz a data prevista | PROJ | morto | `DueDateTest.php:125` |
| l1 | `AuditActionEnum`: `proposeHours` sem mapeamento | POL | morto | `TaskDetailPolicyTest.php:122` |
| l2 | `Gate::policy(TaskComment…)` tirado do `AppServiceProvider` | POL+DET | equivalente | o Laravel acha a `TaskCommentPolicy` sozinho (`Models` → `Policies`; conferido no `tinker`) |

**Total**: 74 mutantes (66 pedidos e de apoio + 8 de confirmação do grupo d). 48 mortos, 11 equivalentes (d1 a d9, d11, l2), 15 sobreviventes (d10c, d10d, d10e, e4, e4b, e6b, e6d, e7a, e7b, e7c, e7d, h1, k1, k2, k3). Contados: 48/63.
**Sensor depth**: P0, manual.

### Code Quality (escopo do trilho completo)

| Princípio | Status |
|---|---|
| Minimum code / Surgical changes | ✅ Uma Policy nova de 30 linhas, uma ability nova, quatro Services de exclusão curtos, migração com uma lista de pares (CR-1-07) |
| Matches patterns | ✅ Autorização no FormRequest antes da validação (`ActsOnCard`); `AuditService::log` dentro da transação; projeto → card → filho travados; `deleteIfUnused` não se aplica (exclusão de verdade aprovada) |
| Defense in depth | ✅ Filho de outro card: scoped binding + `belongsToCard` + o 404 do `CardTextRequest` (por isso d1 a d9 são equivalentes) |
| Spec-anchored outcome check | ⚠️ FCH-03 AC 5 e MIG-04 AC 2 sem asserção (Fix 1 a 3) |
| Every test maps to a spec requirement or review finding | ✅ `TaskDetailPolicyTest` (FCH-01, 02, 04), `TaskDetailTest` (FCH-01, 03, MIG-04 AC 1, CR-1-01) |

⚠️ Nota que não bloqueia: `TaskPullRequestService::remove` apaga o `$pr` recebido sem relê-lo com `lockForUpdate` (os outros três releem). Com o card travado antes, não há corrida prática; registro só pela coerência.

### Fix Plans

Todos só de teste. Nenhum código de produção precisa mudar.

**Fix 1 — FCH-03 AC 5: a linha `deleted` do PR e do anexo** (mata e6b, e6d)
- Em `TaskDetailTest::test_pull_request_links_are_unique_per_card_and_record_who` (`:302-303`), afirmar `AuditLog::query()->where('auditable_type', 'task_pull_request')->where('action', 'deleted')->where('outcome', 'success')->sole()` com `changes['url'][0]` = o link.
- Em `test_cover_is_an_image_attachment_of_the_card_and_removing_it_clears_the_cover` (`:203-207`), o mesmo para `task_attachment`, com `changes['original_name'][0]` e `changes['path'][0]`.

**Fix 2 — FCH-03 AC 5: "na mesma transação" e o arquivo depois do commit** (mata e7a a e7d, e4, e4b; L-017, L-020)
- Um teste no molde do `B3TransacaoTest` (`DatabaseTruncation` + `UsaBancoDeVerdade`, sem `RefreshDatabase`), com um `AuditService` que lança no `log(AuditActionEnum::DELETED, …)`.
- Para cada uma das quatro exclusões, chamar o Service e afirmar que a linha continua (o critério com os subitens; o comentário com o texto; o PR; o anexo com o **arquivo ainda no disco**).

**Fix 3 — MIG-04 AC 2: a ficha mostra quem** (mata k1, k2, k3)
- No contrato da ficha (`TaskDetailTest::test_the_detail_page_follows_the_contract`), um PR com `user_id`, um anexo com `user_id` e uma proposta com `proposed_by`: afirmar `task.pull_requests.0.added_by.name`, `task.attachments.0.uploaded_by.name`, `task.proposed_by.name`.
- Um PR e um anexo sem `user_id` (linha de antes da migração): afirmar `added_by` e `uploaded_by` nulos (a tela mostra "—").

**Fix 4 — MIG-04: o `down()` da 000040** (mata h1; L-019)
- No molde do `MigracaoCardDoQuadroTest`, com um PR, um anexo e uma tarefa com as colunas preenchidas: rodar `down()` (as 4 colunas fora, `Schema::hasColumn` falso), `up()` de novo (as 4 de volta) e `up()` outra vez (idempotente).

**Fix 5 — filho de outro card, PR e anexo** (sonda; mata d10c, d10d, d10e)
- `DELETE …/prs/{id}` e `DELETE …/anexos/{id}` com o id de outro card do mesmo projeto, pelo dev alocado → 404, a linha e o arquivo ficam.
- No caso de `TaskDetailPolicyTest.php:192-193`, gravar `tasks/b.pdf` no disco antes do `GET`, para o 404 vir do vínculo e não do arquivo ausente.

#### Para o humano decidir (não é Fix Plan)

- Nada de regra. As duas perguntas da spec já foram decididas em 20/10. O Fix 5 é sonda de 2ª ordem (precisa tirar duas guardas juntas): se o Lucas Scholz achar que não vale, pode dispensá-lo registrando na spec; os Fix 1 a 4 seguem obrigatórios pelo AC.

### Requirement Traceability Update

| Requirement ID | Status | Teste |
|---|---|---|
| FCH-01 | ✅ Verified | `TaskDetailPolicyTest.php:141-160`; `TaskDetailTest.php:182-188`, `:212-227`; `DueDateTest.php:160`, `:182` |
| FCH-02 | ✅ Verified | `TaskDetailPolicyTest.php:162-183`; `TaskDetailTest.php:306-319` |
| FCH-03 | ❌ Needs Fix: AC 1–4 e 6 PASS; AC 5 sem a linha `deleted` do PR e do anexo e sem prova da transação (Fix 1, Fix 2); filho de outro card no PR e no anexo (Fix 5) | `TaskDetailTest.php:190-208`, `:231-288`, `:292-319`; `TaskDetailPolicyTest.php:106-139`, `:185-196` |
| FCH-04 | ✅ Verified | `TaskDetailPolicyTest.php:90-104` (`assertOneDenied`), usado em `:120-135`, `:146-148`, `:157`, `:166-167`, `:179-180` |
| MIG-04 | ❌ Needs Fix: AC 1 PASS; AC 2 sem asserção (Fix 3); `down()` sem teste (Fix 4) | `TaskDetailTest.php:217`, `:222`, `:301`, `:341` |

### Summary

**Overall**: ❌ Not ready. Cinco Fix Plans, todos de teste.

**Spec-anchored check**: 3 de 5 requisitos verificados (FCH-01, FCH-02, FCH-04). FCH-03 e MIG-04 com lacuna de asserção.
**Sensor**: 48/63 mortos; 11 equivalentes, cada um com a guarda que o cobre nomeada e conferida.
**Gate**: 659 passed (5489 assertions); Pint, typecheck, lint e specs:check verdes antes deste relatório.

**Lições** (`lessons.py`): L-017 e L-020 (transação só se prova com `DatabaseTruncation` e um passo que falha) e L-019 (todo `down()` precisa de teste) se repetem nesta feature; uma nova sobre asserção de auditoria de exclusão em cada Service e outra sobre o 404 de filho com duas guardas.

**Next steps**:
1. Quem implementou aplica os Fix 1 a 5 (só testes) num commit local.
2. Verifier rodada 2 em agente separado: rodar de novo os 15 sobreviventes e uma amostra dos mortos.
