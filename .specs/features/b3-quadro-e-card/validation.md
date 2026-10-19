# B3 PR 1 (TaskPolicy e as três migrações 2026_10_16_*) Validation (rodada 1)

## Validation: FAIL

**Result**: FAIL na rodada 1. Um defeito de código e quatro lacunas de teste.

O defeito: o `down()` da migração `2026_10_16_000010` não roda. O MySQL recusa voltar `tasks.task_type_id` a not null com a FK ligada (erro 1832), mesmo num banco sem nenhuma tarefa, e a migração fica pela metade: as cores e `task_participants` já saíram, a linha da migração segue em `migrations` e o `migrate` seguinte quebra. Isso reprova o MIG-01 AC 5 e o critério de sucesso "`migrate:rollback` + `migrate` sem erro".

As lacunas, todas só de teste:
- `TaskPolicy::view(Task)` sem teste: a rota `GET /projetos/{id}/cards/{chave}` não tem teste de comportamento, e `view → true` e `view → false` sobrevivem.
- `comment`, `attach`, `archive` e `delete` sem chamador nem teste: as quatro trocadas por `true` sobrevivem. A spec pede que cada delegação trocada por `true` morra.
- "Na mesma transação" do MIG-02 AC 6 sem prova: o `createDefaults` levado para depois do commit sobrevive.
- A negação não afirma `auditable_type` (TSK-04 AC 1, lacuna de precisão).

O núcleo da permissão está provado:
- O "entre o Backlog e outra coluna" trocado por "qualquer movimento", por "nenhum" e pelas variantes de cada lado morreu.
- Todo `authorize()` tirado dos FormRequests morreu no teste de comportamento.
- As delegações com chamador, trocadas por `true` e por `false`, morreram.
- Os mapeamentos novos do `AuditActionEnum` morreram.
- A migração dos tipos (cópia por projeto, `UPDATE` das tarefas, `delete` dos gerais, not null, unique por projeto, idempotência, `down()` da 000030) morreu nos 16 mutantes.

Gate verde na árvore real (HEAD `1828dff`): `composer lint` passou, `php vendor/bin/phpunit` **623 passed (4962 assertions)**, `npm run typecheck` exit 0, `npm run lint` exit 0.

**Date**: 2026-10-19
**Spec**: `.specs/features/b3-quadro-e-card/spec.md` (TSK-01 a TSK-04, MIG-01 a MIG-03), aprovada por Lucas Scholz em 19/10/2026 (11 premissas)
**Diff range**: `fc8d3ac..1828dff` (merge-base com `origin/main` até o head do PR WCJ-Tecnologia/wcj-hub#17). Escopo do trilho completo:
- `app/Domains/Projetos/Policies/TaskPolicy.php` e o registro no `AppServiceProvider`;
- `AuditActionEnum::forAbility` (`move`, `createSupport`, `manageSprint`, `comment`, `attach`, `archive`);
- os pontos de autorização: `BoardController::show`, `TaskController::show`, os FormRequests `StoreTask`, `StoreSupportTask`, `MoveTask`, `UpdateTaskTags`, `TaskParticipant` (`ActsOnCard`/`AuthorizesBeforeValidation`), `StoreTaskType` e `StoreProjectArea` (`ManagesProject`);
- as migrações `2026_10_16_000010`, `000020` e `000030`;
- `ProjectService::create` + `TaskTypeService::createDefaults` (MIG-02).

O resto do PR 1 (quadro, mover, pausa, retrabalho, etiquetas, aviso) é trilho leve e ficou fora.
**Verifier**: sessão separada de quem implementou (Cauã Soares), sem acesso ao raciocínio dela

---

## Spec-Anchored Acceptance Criteria

### TSK-01: qualquer alocado usa o quadro (P1 "Qualquer alocado", AC 1, 3 e 6)

| Critério | Valor da spec | Evidência (`arquivo:linha` + asserção) | Resultado |
|---|---|---|---|
| AC 1: admin, quem gere e dev alocado abrem `/projetos/{id}/quadro` → 200 com colunas e cards | 200 | Dev: `TaskPolicyTest.php:124-132` `assertOk()`, `component('projects/board')`, `has('cards', 2)`, `abilities` exatas. Admin, gestor responsável e gestor alocado: `:136-143` (provider `quemGere`, `:63-66`) `assertOk()` e `abilities` todas `true` | PASS |
| AC 3: dev alocado cria card rápido, move entre colunas fora do Backlog, reordena (inclusive no Backlog), troca tipo, área, prioridade, participante → grava | gravado | `TaskPolicyTest.php:165-180`: criar `:167` `assertRedirect()` + `:170` `sole()`; mover To do → Desenvolvimento `:168` `assertRedirect()` + `:176` etapa `DEVELOPMENT`; reordenar no Backlog `:172` + `:177` `assertSame([1, 2], posições)`; prioridade `:178` `'high'`; participante `:179` `[$dev->id]`. Tipo e área pelo PATCH: `TaskServiceTest.php:320-338` (`assertSame([$area->id, tipo development, 'urgent'], ...)`, via Service; pela rota só a prioridade) | PASS |
| AC 6: admin e quem gere fazem tudo → grava | gravado | `TaskPolicyTest.php:212-227` (provider `quemGere`): pôr na sprint `:223` `[$sprint->id, SPRINT]`, levar ao Backlog `:224` `[null, QUEUE]`, card de suporte `:225`, tipo `:226` `'pink'`, área `:227` `'red'` | PASS |
| Abilities da premissa: `view(Task)`, `comment`, `attach` (alocado); `archive`, `delete` (quem gere) | cada uma decide pela `ProjectPolicy` | **Nenhum teste.** `view(Task)` só é chamada em `TaskController::show` (`TaskController.php:39`), e nenhum teste faz `GET /projetos/{id}/cards/{chave}` (`grep` em `tests/` e `e2e/`). `comment`, `attach`, `archive` e `delete` não têm chamador: o `archive` do quadro sai de `ProjectPolicy::manage` direto (`TaskPresenter.php:107,114`), não de `TaskPolicy::archive`. Os mutantes p4, p16, p7, p8, p9 e p10 sobrevivem | ❌ GAP (Fix 2, Fix 3) |

### TSK-02: o dev não mexe na sprint nem cria suporte, tipo ou área (AC 4 e 5)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 4: dev alocado move do Backlog para outra coluna, ou de outra coluna para o Backlog → 403, etapa, sprint e posição mantidas | 403; `board_stage_id`, `sprint_id`, `position` iguais | `TaskPolicyTest.php:182-192`: Backlog → To do `:186`, Backlog → Desenvolvimento `:187`, To do → Backlog `:188`, os três `assertForbidden()`; `:190` `assertSame($antes, $this->retrato())`, com o retrato de `:102-111` (`board_stage_id`, `sprint_id`, `position`, movimentos) | PASS |
| AC 5: dev cria card de suporte, cria ou edita tipo ou área → 403, nada muda | 403; nada novo | `TaskPolicyTest.php:194-209`: suporte `:200`, tipo novo `:201`, editar tipo `:202`, área nova `:203`, editar área `:204`, todos `assertForbidden()`; `:206` retrato igual (conta tipos e áreas) | PASS |

### TSK-03: quem não vê o projeto recebe 403 e nada muda (AC 2)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 2: dev não alocado, gestor que não gere, Comercial abrem o quadro ou mandam escrita de card → 403, nada muda | 403; nada muda | `TaskPolicyTest.php:146-163` (provider `quemNaoVe`, `:57-60`): quadro `:152`, criar `:153`, mover `:154`, etiquetas `:155`, participante `:156`, todos `assertForbidden()`; `:158` retrato igual | PASS |

### TSK-04: negação auditada com a ação certa (P1 "Negação auditada", AC 1 e 2)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: negação → `denied` com `auditable_type = task` (ou `project`) e a ação `created`/`viewed`/`updated`/`moved`/`archived`/`deleted` | ação por ability; `auditable_type` | Ação: `TaskPolicyTest.php:113-120` `assertNegado` (`where('action', ...)->exists()`), chamado em `:159-162` (`viewed`, `created`, `moved`, `updated`), `:191` (`moved`), `:207-208` (`created`, `updated`). Unitário: `AuditActionEnumTest.php:53-58`. **`auditable_type` não é afirmado em lugar nenhum:** a sonda pd1, que grava `'outro'` no lugar do tipo, passa no `TaskPolicyTest` inteiro. **A spec também é imprecisa:** "ou `project`, nas abilities que recebem o projeto". O código grava `task` com id nulo em `create` e `createSupport`, porque o primeiro argumento é `Task::class` (`StoreTaskRequest.php:16`, `AuditService::deniedTarget`). Grava `project` com o id em tipo e área (`ManagesProject`). Falta decidir qual dos dois a spec quer e afirmar. Notas: `assertNegado` usa `exists()`, sem conferir uma linha nova por requisição (L-015 do B5); `archived` e `deleted` não têm caminho que negue | ⚠️ Spec-precision gap (Fix 4) |
| AC 2: na negação, nenhuma linha `success` de `task` nem de `task_stage_movement` | 0 linhas `success`; nenhum movimento | `TaskPolicyTest.php:119` `assertFalse(...where('outcome','success')->exists())` por usuário; o retrato (`:106`) conta `task_stage_movements` | PASS |

### MIG-01: tipos do projeto sem perder dado (AC 1 a 5)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: cada projeto com cópia de cada tipo geral (slug, rótulo, desativação) + "Desenvolvimento"; cada tarefa na cópia do próprio projeto | os rótulos, a desativação, a tarefa no próprio projeto | `MigracaoTiposDoProjetoTest.php:45-80`: `:66` os 6 rótulos por projeto, em ordem; `:72-73` `assertEquals((object) ['project_id' => $a->id, 'label' => 'Suporte', 'slug' => 'support'], ...)`, e o mesmo para a "Pesquisa" em B; `:74` tarefa sem tipo continua nula; `:77-78` cor, `deactivated_by` e `deactivated_at` copiados; `:79` um `development` | PASS |
| AC 2: nenhum tipo sem projeto; `project_id` not null | 0; `nullable = false` | `:68` `assertSame(0, ...whereNull('project_id')->count())`; `:87-88` `assertFalse($coluna['nullable'])` | PASS |
| AC 3: sem projeto, os gerais saem sem erro | 0 tipos | `:106-114` `assertSame(0, DB::table('task_types')->count())` | PASS |
| AC 4: rodar de novo não duplica | rótulos iguais | `:97-104` `up()` de novo, `assertSame([...5 rótulos], ...)` | PASS |
| AC 5: `down()` volta à lista geral **e remove as colunas e a tabela novas** | um tipo por rótulo, tarefas nele; `parent_task_id`, `cover_attachment_id`, `task_participants`, as cores fora | A parte da 000030: `:116-127` `assertSame(5, count)` e as duas tarefas no mesmo geral. **A parte da 000010 falha:** `migrate:rollback --step=3` sai com `SQLSTATE[HY000]: General error: 1832 Cannot change column 'task_type_id': used in a foreign key constraint 'tasks_task_type_id_foreign'`. Reproduzido num banco sem tarefa (`migrate:fresh` e três `rollback --step=1`: a 000030 e a 000020 voltam, a 000010 falha). Depois da falha, `migrate:status` mostra a 000010 `Ran` e as cores e `task_participants` já fora; o `migrate` seguinte quebra em `Undefined property: stdClass::$color`. Nenhum teste roda o `down()` da 000010 nem o da 000020 | ❌ FAIL (Fix 1) |

### MIG-02: projeto novo nasce com os 5 tipos (AC 6)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 6: projeto novo grava os 5 tipos padrão, **na mesma transação** | os 5 (slug, rótulo), na ordem; mesma transação | Os 5: `ProjectTest.php:71-75` `assertSame([['improvement','Melhoria'], [null,'Correção de bug'], ['support','Suporte'], [null,'Mudança (GMUD)'], ['development','Desenvolvimento']], ...)`. **Mesma transação: sem prova.** `ProjectTest.php:146` confere 5 tipos depois do prefixo repetido, mas a falha acontece no `Project::create`, antes do `createDefaults`. O mutante s3, que tira o `createDefaults` da transação e o roda num `tap()` depois do commit, sobrevive | ⚠️ GAP de precisão (Fix 5) |

### MIG-03: card sem tipo e sem prioridade (AC 7)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 7: o banco aceita `task_type_id` e `task_priority_id` nulos | nulos gravados | `TaskServiceTest.php:74-81` `assertSame([17, 'Tela de login', null, null, null, null], [number, title, task_type_id, task_priority_id, project_area_id, sprint_id])` | PASS |

### Edge cases

| Edge case | Evidência | Resultado |
|---|---|---|
| Gestor perde o papel → negado na próxima requisição | `TaskPolicyTest.php:240-246` (`role_id` → dev; levar ao Backlog `assertForbidden()`) | PASS |
| Pessoa tirada do projeto → negado na próxima requisição | Sem teste no `TaskPolicyTest`. Coberto por equivalência: toda ability passa pela `ProjectPolicy::view`, que lê `project_members` a cada chamada. O "dev não alocado" do `quemNaoVe` (`:57-60`) é o mesmo estado, e o "saiu do projeto" pela `ProjectPolicy` está provado em `TimeEntryPolicyTest.php:146` | PASS (por equivalência; nota) |
| Chave de outro projeto ou inexistente → 404 antes da Policy | `TaskPolicyTest.php:230-238` `assertNotFound()` ×2 e `:237` `assertSame(0, ...denied...count())` | PASS |
| Rótulo igual em dois projetos → aceito | `MigracaoTiposDoProjetoTest.php:89` `assertSame(2, ...where('label','Melhoria')->count())` | PASS |

---

## Discrimination Sensor

**Isolamento.** Cópia descartável `git worktree add /tmp/b3-verifier HEAD --detach` em `1828dff`, removida no fim com `git worktree remove --force`. O `vendor/` foi montado assim:
- um symlink por pacote do `vendor` do checkout principal (o `composer.lock` é o mesmo da `main`: `git diff origin/main...HEAD -- composer.lock` sai vazio);
- cópias reais de `vendor/composer`, `vendor/autoload.php` e `vendor/bin`;
- `composer dump-autoload` na cópia.

O autoload foi conferido por `ReflectionClass::getFileName()` = `/private/tmp/b3-verifier/app/Domains/Projetos/Policies/TaskPolicy.php`. Os testes rodaram por `php vendor/bin/phpunit --stop-on-defect <arquivos>` no MySQL 8 (`wcj-hub-mysql-1`, banco `wcj_hub_test`), um mutante por vez. Cada mutante foi aplicado por script com troca exata, que falha se o trecho não for único, e restaurado com `git checkout -- <arquivo>` na cópia. Script e resultados ficaram em `/tmp/b3-sensor/` (`run.py`, `results.json`).

Base na cópia: 86 testes OK (`TaskPolicyTest`, `BoardTest`, `TaskServiceTest`, `AuditActionEnumTest`, `tests/Arch`) e 37 OK (`MigracaoTiposDoProjetoTest`, `ProjectTest`, `SementesEExemploTest`, `BancoTest`). A árvore real ficou com `git status --porcelain` vazio antes e depois, e só este `validation.md` e a spec foram editados.

Conjuntos de teste:
- **Policy, FormRequests, controllers e enum**: `TaskPolicyTest` + `BoardTest` + `TaskServiceTest`, sem o A5, para provar que o comportamento mata. Para o enum, mais o `AuditActionEnumTest`. Os nove `authorize()` tirados rodaram também só contra o `RotasAutorizadasTest` (A5).
- **Migração 000030**: `MigracaoTiposDoProjetoTest`.
- **MIG-02**: `ProjectTest` + `MigracaoTiposDoProjetoTest`.
- **MIG-03**: `TaskServiceTest`.

### Mutantes pedidos pela spec

| # | Mutante | Resultado | Quem matou |
|---|---|---|---|
| p1 | `create` → `true` | morto | `test_who_does_not_see_the_project_gets_403…` (dev não alocado) |
| p2 | `createSupport` → `true` | morto | `test_allocated_developer_sees_the_board_with_dev_abilities` (`create_support`) |
| p3 | `manageSprint` → `true` | morto | idem (`manage_sprint`) |
| p4 | **`view(Task)` → `true`** | **sobrevivente** | nenhum: Fix 2 |
| p5 | `update` → `true` | morto | `…gets_403_everywhere…` |
| p6 | `move`: a checagem de `view` → nunca nega | morto | `…gets_403_everywhere…` |
| p7 | **`comment` → `true`** | **sobrevivente** (sem chamador) | nenhum: Fix 3 |
| p8 | **`attach` → `true`** | **sobrevivente** (sem chamador) | nenhum: Fix 3 |
| p9 | **`archive` → `true`** | **sobrevivente** (sem chamador) | nenhum: Fix 3 |
| p10 | **`delete` → `true`** | **sobrevivente** (sem chamador) | nenhum: Fix 3 |
| b1 | "entre o Backlog e outra coluna" → qualquer movimento (`$crossesBacklog = true`) | morto | `test_allocated_developer_creates_moves_reorders_and_tags` |
| b1b | → qualquer movimento com destino (`$to !== null`) | morto | idem |
| b2 | → nenhum (`$crossesBacklog = false`) | morto | `test_developer_does_not_put_on_the_sprint_nor_take_back_to_the_backlog` |
| a1 | sem `authorize('view')` no `BoardController::show` | morto | `…gets_403_everywhere…`; também o A5 |
| a2 | sem `authorize('view')` no `TaskController::show` | morto **só pelo A5** (`RotasAutorizadasTest`: "GET /projetos/{project}/cards/{card} … não autoriza"); o teste de comportamento passa (51 OK) | A5. Lacuna de comportamento: Fix 2 |
| a3 | `StoreTaskRequest::authorize()` → `return true` | morto | `…gets_403_everywhere…`; também o A5 |
| a4 | `StoreSupportTaskRequest` idem | morto | `test_developer_does_not_create_support_card_nor_types_nor_areas`; também o A5 |
| a5 | `MoveTaskRequest` idem | morto | `…gets_403_everywhere…`; também o A5 |
| a6 | `UpdateTaskTagsRequest` idem | morto | `…gets_403_everywhere…`; também o A5 |
| a7 | `TaskParticipantRequest` idem | morto | `…gets_403_everywhere…`; também o A5 |
| a8 | `StoreTaskTypeRequest` (`ManagesProject`) idem | morto | `…does_not_create_support_card_nor_types_nor_areas`; também o A5 |
| a9 | `StoreProjectAreaRequest` (`ManagesProject`) idem | morto | idem; também o A5 |

### Mutantes extras (direção oposta, bordas do Backlog, argumentos, enum)

| # | Mutante | Resultado | Quem matou |
|---|---|---|---|
| p11 | `create` → `false` | morto | `…sees_the_board_with_dev_abilities` |
| p12 | `createSupport` → `false` | morto | `test_who_manages_sees_the_board_with_every_ability` (admin) |
| p13 | `manageSprint` → `false` | morto | idem |
| p14 | `update` → `false` | morto | `…creates_moves_reorders_and_tags` |
| p15 | `move` → sempre `deny` | morto | idem |
| p16 | **`view(Task)` → `false`** | **sobrevivente** | nenhum: Fix 2 |
| b3 | sem `$to->id !== $from->id` (reordenar no Backlog vira "cruzar") | morto | `…creates_moves_reorders_and_tags` (posições em `:177`) |
| b4 | só "sai do Backlog" conta (`to` fora) | morto | `…does_not_put_on_the_sprint…` (To do → Backlog) |
| b5 | só "entra no Backlog" conta (`from` fora) | morto | idem (Backlog → To do) |
| b6 | `! manageSprint` invertido | morto | idem |
| f1 | `StoreSupportTaskRequest` pede `create` em vez de `createSupport` | morto | `…does_not_create_support_card…` |
| f2 | `MoveTaskRequest` sem o destino (`['move', [$card]]`) | morto | `…does_not_put_on_the_sprint…` |
| f3 | `MoveTaskRequest`: destino sempre nulo | morto | idem |
| f4 | `StoreTaskRequest` pede `view` do projeto em vez de `create` | morto | `…gets_403_everywhere…` (a ação deixa de ser `created`) |
| e1 | `forAbility` sem `move` | morto | `…gets_403_everywhere…` (`moved`) |
| e1b | `move` → `UPDATED` | morto | idem |
| e2 | `forAbility` sem `createSupport` | morto | `…does_not_create_support_card…` (`created`) |
| e3 | `forAbility` sem `archive` | morto | só `AuditActionEnumTest` (data set `archive`) |
| e4 | `forAbility` sem `manageSprint` | morto | só `AuditActionEnumTest` (data set `manageSprint`) |
| m1 | cópia com `deactivated_at` nulo | morto | `test_each_project_gets_a_copy…` (`:78`) |
| m2 | sem acrescentar "Desenvolvimento" | morto | idem (`:66`) |
| m3 | sem o `UPDATE` das tarefas | morto | idem (FK 1451 no `delete` dos gerais) |
| m4 | `UPDATE` sem `c.project_id = t.project_id` (cópia de qualquer projeto) | morto | idem (`:72-73`) |
| m5 | sem o filtro `$missing` (copia tudo de novo) | morto | `test_running_up_again_duplicates_nothing` |
| m6 | sem o `delete` dos gerais | morto | `test_each_project…` (1138 no not null) |
| m7 | sem `project_id` not null | morto | `test_project_id_is_not_null…` (`:88`) |
| m8 | sem o unique `(project_id, label)` | morto | `test_each_project…` (o `down()` não acha o índice); só com os testes que não chamam `down()`, também morre pela asserção certa: `test_project_id_is_not_null…` "Failed asserting that exception of type UniqueConstraintViolationException is thrown" |
| m9 | sem o unique `(project_id, slug)` | morto **só pelo `down()`** (1091 "Can't DROP 'task_types_project_id_slug_unique'"), em todos os testes que chamam `down()` | nota: nenhuma asserção direta do unique por slug |
| m10 | cópia sem o slug | morto | `test_each_project…` (`:72`) |
| m11 | cópia sem a cor | morto | idem (`:77`) |
| m12 | cópia sem `deactivated_by` | morto | idem (`:77`) |
| m13 | sem a guarda `hasColumn` (idempotência) | morto | `test_running_up_again…` (1060 coluna repetida) |
| m14 | `down()` sem repor as tarefas no geral | morto | `test_down_goes_back…` |
| m15 | `down()` sem apagar as cópias repetidas | morto | `test_each_project…` (1062 no unique `slug`) |
| m16 | cópia só para o primeiro projeto | morto | `test_each_project…` (FK 1451) |
| s1 | `ProjectService::create` sem `createDefaults` | morto | `ProjectTest::test_project_manager_creates_a_project_with_the_default_board_and_defaults` |
| s2 | `DEFAULTS` sem "Mudança (GMUD)" | morto | idem |
| s3 | **`createDefaults` fora da transação** (num `tap()` depois do commit) | **sobrevivente** (20 OK) | nenhum: Fix 5 |
| n1 | 000010 sem `task_priority_id` nulo | morto | `TaskServiceTest::test_quick_card_is_born_in_the_backlog…` |
| n2 | 000010 sem `task_type_id` nulo | morto | idem |

**Sonda de precisão (fora da contagem; mexe em código do Núcleo, que o B1 já cobre).** pd1, `AuditService::denied` gravando `auditable_type = 'outro'`: o `TaskPolicyTest` inteiro passa (15 OK). Nenhum teste da feature afirma o tipo da linha `denied`: Fix 4.

**Total**: 62 mutantes distintos na feature (10 pedidos de delegação + 3 do Backlog + 9 `authorize()` + 40 extras).
- **55 mortos.** O a2 está entre eles, morto só pelo A5.
- **7 sobreviventes**: p4, p16, p7, p8, p9, p10, s3.
- Nenhum equivalente.

Dos 22 pedidos pela spec (p1 a p10, b1, b1b, b2, a1 a a9), 17 morreram: 16 no teste de comportamento e o a2 só no A5. Os outros 5 sobreviveram: p4, p7, p8, p9 e p10.

**Sensor depth**: P0 (permissão e migração que reescreve dado), manual, ≥5 por ramo.
**Result**: 55/62 mortos. FAIL.

---

## Gate Check (árvore real, HEAD `1828dff`, sem edição)

| Comando | Resultado |
|---|---|
| `composer lint` (`pint --test`) | passed |
| `php vendor/bin/phpunit` (MySQL 8, `wcj-hub-mysql-1`, `wcj_hub_test`) | **623 passed (4962 assertions)**, 1:44 |
| `npm run typecheck` | exit 0 |
| `npm run lint` | exit 0 |
| Testes antes (`fc8d3ac`, `phpunit --list-tests`) → depois | 559 → 623 (+64). Nenhum arquivo de teste apagado (`git diff --diff-filter=D origin/main...HEAD -- tests` vazio) |
| `migrate:fresh --seed` + `db:seed --class=ExemploSeeder` (`--env=testing`, `wcj_hub_test`) | sem erro. "Dado de exemplo criado (cliente, 3 pessoas, projeto EXE com 3 cards)". 3 tarefas, as 3 com tipo do próprio projeto, 0 tipos sem projeto, 5 tipos |
| `migrate:rollback --step=3` | **falha** na 000010: `1832 Cannot change column 'task_type_id': used in a foreign key constraint 'tasks_task_type_id_foreign'` (Fix 1) |
| `migrate:rollback --step=1` da 000030 + `migrate` | sem erro, ida e volta |

`php artisan test` não roda neste worktree, porque o `vendor` é por symlink e o Collision chama o phpunit do checkout principal. O gate usou `php vendor/bin/phpunit`, o mesmo do B5. Não rodei o Playwright, o Storybook nem o build: estão fora do trilho completo.

---

## Code Quality (escopo do trilho completo)

| Princípio | Status |
|---|---|
| Minimum code | ✅ A Policy só delega à `ProjectPolicy`/`SprintPolicy`, sem conta própria de "alocado" |
| Surgical changes | ✅ |
| No scope creep | ⚠️ `comment`, `attach`, `archive` e `delete` entram sem chamador, por decisão da spec. Só se sustentam com teste direto (Fix 3) |
| Matches patterns | ⚠️ O `archive` do quadro vem de `ProjectPolicy::manage` direto (`TaskPresenter.php:107,114`), não de `TaskPolicy::archive`. É um segundo jeito de dizer "quem arquiva", e a mutação do `archive` passa (nota do Fix 3) |
| Spec-anchored outcome check | ⚠️ TSK-04 AC 1 (`auditable_type`) e MIG-02 AC 6 (transação) |
| Per-layer coverage | ❌ `GET /projetos/{id}/cards/{chave}` sem teste de rota; `down()` da 000010 e da 000020 sem teste |
| Every test maps to a spec requirement | ✅ |
| Documented guidelines | `AGENTS.md` ("Trilhos", "O que exige aprovação humana", "Um jeito só"), `.ai/core/especificacao.md` §5–6 |

---

## Fix Plans

O Fix 1 é de código: a migração. Os Fix 2 a 5 são só de teste (o 4 também pede uma linha na spec). Fica tudo com quem implementou: o Verifier não corrige.

### Fix 1 (MIG-01 AC 5 e critério de sucesso; Major): o `down()` da 000010 não roda

- **Root cause**: `2026_10_16_000010_add_card_do_quadro_to_tasks.php:69-71` faz `->nullable(false)->change()` em `task_type_id` e `task_priority_id` com a FK ligada, e o MySQL 8.4 recusa (1832). Como o DDL não é transacional, as três instruções anteriores do `down()` já rodaram (as duas cores e o `dropIfExists('task_participants')`). A linha da migração fica em `migrations`, e o `migrate` seguinte quebra na 000030 (`Undefined property: stdClass::$color`).
- **Fix task**: no `down()` da 000010, fazer o `change()` das duas colunas com as FKs desligadas: `Schema::withoutForeignKeyConstraints(fn () => Schema::table('tasks', ...change()...))`, ou soltar e refazer as duas FKs. Depois, o `dropForeign`/`dropIndex`/`dropColumn` do `parent_task_id` como está. A ordem do `down()` também deve deixar o banco consistente se um passo falhar: o `change()` primeiro, o `drop` das cores e da tabela por último. **Conferido pelo Verifier só na cópia descartável:** com o `withoutForeignKeyConstraints` em volta do `change()`, `migrate:fresh` → `migrate:rollback --step=3` → `migrate` roda sem erro.
- **Teste que falta**: um `MigracaoCardDoQuadroTest` (`DatabaseTruncation` + `UsaBancoDeVerdade`, no molde do `MigracaoTiposDoProjetoTest`). Ele roda o `down()` da 000030, da 000020 e da 000010, nessa ordem, e afirma:
  - `parent_task_id`, `cover_attachment_id`, `task_participants`, `task_types.color` e `project_areas.color` fora;
  - `task_type_id` e `task_priority_id` not null.

  Depois roda o `up()` das três e afirma tudo de volta. O `tearDown` repõe o schema.
- **Priority**: Major (só dev, mas é AC da spec e o critério "`migrate:rollback` + `migrate` sem erro").

### Fix 2 (TSK-01, mata p4, p16 e o a2 no comportamento): `view(Task)` e a rota do card

- **Fix task**: em `TaskPolicyTest`, `GET /projetos/{id}/cards/{prefixo}-{n}`:
  - dev alocado e os três do `quemGere` → `assertRedirect('/projetos/{id}/quadro')` + `assertSessionHas('warning', 'A ficha de {chave} ainda não está pronta: chega no próximo PR do quadro.')`;
  - os três do `quemNaoVe` → `assertForbidden()` + uma linha `denied` `viewed` com `auditable_type = 'task'` e `auditable_id` do card.

  O 404 da chave de outro projeto já existe para o PATCH (`:230-238`); repetir para o GET.

### Fix 3 (premissa "Abilities da TaskPolicy", mata p7, p8, p9 e p10)

- **Fix task**: as quatro abilities não têm chamador. Duas saídas, como no Fix 2 do B5:
  - **Teste direto da Policy.** Para `comment` e `attach`: `Gate::forUser($user)->allows(..., $task)` verdadeiro para o dev alocado e os três do `quemGere`, falso para os três do `quemNaoVe`. Para `archive` e `delete`: verdadeiro para os três do `quemGere`, falso para o dev alocado e os do `quemNaoVe`.
  - **Tirar as quatro até o PR 2 e o PR 3 terem as rotas.** Mudar a Policy pede o "ok" do Lucas Scholz.
- **Nota**: o `abilities.archive` do quadro (`TaskPresenter.php:114`) usa `$manages`, não `can('archive', ...)`. Se as abilities ficarem, o presenter pode perguntar a elas, para haver um jeito só.

### Fix 4 (TSK-04 AC 1, lacuna de precisão)

- **Spec**: decidir o `auditable_type` da negação de `create` e `createSupport`. Hoje grava `task` com `auditable_id` nulo. Ou a spec troca "(ou `project`, nas abilities que recebem o projeto)" por "`task` com id nulo em `create` e `createSupport`; `project` em tipo e área", ou o código passa o projeto.
- **Teste**: em `TaskPolicyTest::assertNegado` (`:113-120`), afirmar `auditable_type` e `auditable_id` da linha. E contar exatamente uma linha `denied` nova por requisição, pegando o `max('id')` antes, como o `assertOneDenied` do `SprintPolicyTest` (L-015).

### Fix 5 (MIG-02 AC 6, mata s3): os 5 tipos na mesma transação do projeto

- **Fix task**: um teste sem `RefreshDatabase` (`DatabaseTruncation` + `UsaBancoDeVerdade`, molde `B5TransacaoTest`). Ele injeta no `ProjectService` um `TaskTypeService` que cria o primeiro tipo e lança, chama `create()` e afirma pelo banco:
  - `Project::count() === 0`;
  - `task_types` vazio;
  - nenhuma linha `created` de `project`.

  Uma falha na auditoria não serve, porque ela também impede o `createDefaults` no mutante. A falha tem de vir de dentro do `createDefaults`.

### Notas (não bloqueiam)

- m9: o unique `(project_id, slug)` só morre porque o `down()` não acha o índice. Nenhum teste insere slug repetido no mesmo projeto. Uma linha a mais em `test_project_id_is_not_null_and_labels_are_unique_per_project_only` fecha.
- e3 e e4 (`archive` e `manageSprint` no enum) só morrem no unitário, porque nenhum caminho da feature nega essas abilities. Basta enquanto as abilities não tiverem rota.
- `TaskPolicyTest.php:172` (reordenar no Backlog) usa `assertSessionHasNoErrors()`, que passa num 403. Quem prova é a posição em `:177`, e o b3 morreu por ela. Um `assertRedirect()` deixaria o teste mais direto.
- A janela entre a Policy (`move`, que lê a etapa antes da transação) e a trava do `TaskMoveService` já foi registrada sem achado no ciclo 1 da `wcj-code-review` e não foi mutada.

---

## Requirement Traceability Update

| Requirement | Previous Status | New Status |
|---|---|---|
| TSK-01 | Pending | ❌ Needs Fix (AC 1, 3 e 6 PASS; `view(Task)`, `comment`, `attach`, `archive` e `delete` sem teste: Fix 2, Fix 3) |
| TSK-02 | Pending | ✅ Verified |
| TSK-03 | Pending | ✅ Verified |
| TSK-04 | Pending | ⚠️ Needs Fix (`auditable_type`: Fix 4) |
| MIG-01 | Pending | ❌ Needs Fix (AC 1–4 PASS; AC 5 falha no `down()` da 000010: Fix 1) |
| MIG-02 | Pending | ⚠️ Needs Fix (os 5 tipos PASS; "mesma transação" sem prova: Fix 5) |
| MIG-03 | Pending | ✅ Verified |

---

## Summary

**Overall**: ❌ Not Ready

**Spec-anchored check**: 12 dos 15 ACs batem com o valor da spec. 1 AC falha (MIG-01 AC 5). 2 têm lacuna de precisão (TSK-04 AC 1, MIG-02 AC 6). Há ainda a lacuna das abilities sem teste (TSK-01).
**Sensor**: 55/62 mortos, 7 sobreviventes (p4, p16, p7, p8, p9, p10, s3).
**Gate**: 623 passed, Pint, typecheck e lint verdes. `migrate:rollback --step=3` falha.

**What works**: quem vê o projeto usa o quadro e quem não vê recebe 403 sem mudar nada. O dev não cruza o Backlog nem cria suporte, tipo ou área. Todo FormRequest autoriza antes de validar, e a negação vira `denied` com a ação certa. A migração dos tipos copia, repõe, apaga, trava o not null e o unique por projeto, roda duas vezes sem duplicar e volta pelo `down()`. O projeto novo nasce com os 5 tipos. O card rápido aceita tipo e prioridade nulos.

**Next steps**: o Cauã fecha os Fix 1 a 5, com a decisão da spec no Fix 4 e, se escolher tirar as abilities no Fix 3, o "ok" do Lucas Scholz. Depois, rodada 2 do Verifier sobre o diff dos fixes, com os 7 sobreviventes, a sonda pd1 e uma amostra dos mortos.
