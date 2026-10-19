# B3 PR 1 (TaskPolicy e as três migrações 2026_10_16_*) Validation

## Veredito vigente: PASS (rodada 3, 19/10)

**Rodada 3**: PASS. O Fix 6 fechou. Os 3 sobreviventes da rodada 2 (u2, u3 e u4, recusa de rótulo repetido do tipo) morrem no teste novo do `TaskTypeTest`. O ac2 (`reactivate` sem `authorize`) agora morre no teste de comportamento, não só no A5. O gate está verde, com 634 testes. Somando as três rodadas, todo mutante contado está morto.

Fica uma decisão humana, fora do veredito: a exclusão de verdade do tipo do projeto (`DELETE …/tipos/{taskType}`) está na spec como "aguardando o ok do Lucas Scholz".

**Rodada 2**: FAIL, 24/27 (seção "Rodada 2" abaixo, mantida como estava). Os 7 sobreviventes da rodada 1 morreram, as 2 sondas de precisão morreram e os 5 Fix Plans estão fechados. Os 7 requisitos da spec (TSK-01 a TSK-04, MIG-01 a MIG-03) estão ✅ Verified. O gate está verde.

**Rodada 1**: FAIL, 55/62 (seção "Rodada 1" abaixo, mantida como estava).

---

## Rodada 3 (19/10)

**Date**: 2026-10-19
**Diff range**: `b29d357..66c9907` (o commit `66c9907`, "rótulo repetido do tipo e negação do reactivate"), sobre a árvore real em `feat/b3-quadro-e-card`.
**Verifier**: sessão separada de quem implementou, sem acesso ao raciocínio dela.
**Escopo**: só o Fix 6 da rodada 2 e a nota do ac2. O `66c9907` mexe em dois testes e na spec:
- `tests/Feature/Projetos/TaskTypeTest.php:57-76`, `test_a_repeated_label_is_refused_with_the_name_ignoring_accent_and_case_and_is_free_in_another_project`;
- `tests/Feature/Projetos/TaskPolicyTest.php:261`, a linha do `reactivate` pelo dev;
- `spec.md`, a premissa da exclusão de tipo, registrada como aguardando o ok.

Nenhum arquivo de `app/` nem de `database/` mudou (`git show --stat 66c9907`).

### Gate Check (árvore real, HEAD `66c9907`, sem edição)

| Comando | Resultado |
|---|---|
| `composer lint` (`pint --test`) | `{"tool":"pint","result":"passed"}`, exit 0 |
| `php artisan test --compact` (MySQL 8, `wcj-hub-mysql-1`, `wcj_hub_test`) | **634 passed (5121 assertions)**, 110,50 s, exit 0 |
| `npm run typecheck` (`tsc --noEmit`) | exit 0 |
| `npm run lint` (`eslint .`) | exit 0 |
| Testes rodada 2 → rodada 3 | 633 → 634 (+1, o teste novo do `TaskTypeTest`); asserções 5106 → 5121 |
| Base do sensor | `php artisan test --compact tests/Feature/Projetos/TaskTypeTest.php tests/Feature/Projetos/TaskPolicyTest.php`: 22 passed (256 assertions) |

Não rodei Playwright, Storybook nem build: estão fora do trilho completo. A migração não mudou desde a rodada 2, onde `rollback --step=3` + `migrate` passou.

### Fix 6 contra o Fix Plan

| Pedido do Fix Plan | Evidência (`arquivo:linha` + asserção) | Resultado |
|---|---|---|
| `POST` com o rótulo repetido, em caixa e acento diferentes → a mensagem da recusa, nada criado | `TaskTypeTest.php:59-64`: `melhoria`, `MELHORIA` e `Melhoría` → `assertSessionHasErrors(['name' => 'Já existe o tipo Melhoria neste projeto.'])` (`:62`); `:64` `assertSame(5, …count())` | PASS |
| `PUT` de "Correção de bug" para "suporte" → o mesmo erro, rótulo intacto | `TaskTypeTest.php:66-70`: `assertSessionHasErrors(['name' => 'Já existe o tipo Suporte neste projeto.'])` (`:69`); `:70` o rótulo segue "Correção de bug" | PASS |
| O mesmo rótulo em outro projeto não trava este | `TaskTypeTest.php:72-75`: "Pesquisa" criado em outro projeto, `POST` neste → `assertSessionHasNoErrors()` (`:74`); `:75` um "Pesquisa" neste projeto | PASS |
| Nota do ac2: o dev reativando tipo → 403 e uma `denied` | `TaskPolicyTest.php:261`: `assertOneDenied($dev, …post("/tipos/{$bug}/reactivate"), 'updated', 'project', $projeto, 'reativar tipo')->assertForbidden()` | PASS |

### Discrimination Sensor (rodada 3)

**Método.** O mesmo da rodada 2. O script `/tmp/b3-sensor-r2/run.py` foi copiado para `/tmp/b3-sensor-r3/run.py`, com as mesmas trocas exatas e só a lista de testes mudada: u2 a u4 contra o `TaskTypeTest`, ac2 contra o `TaskPolicyTest` (o A5 roda à parte). Para cada mutante, o script:
1. confere o arquivo limpo;
2. aplica a troca, que falha se o trecho não for único;
3. roda `php artisan test --compact --stop-on-failure <teste>`;
4. reverte com `git checkout -- <arquivo>` e confere o arquivo limpo de novo.

Rodei duas vezes, a segunda guardando a saída inteira em `/tmp/b3-sensor-r3/out-<id>.txt` para achar a linha da falha. O resultado foi o mesmo nas duas. Não usei `git reset` nem `stash`. `git status --porcelain` saiu vazio no fim.

| # | Mutante | Resultado | Quem matou |
|---|---|---|---|
| u2 | `TaskTypeService::duplicate()` sem `where('project_id', …)`: recusa rótulo que só existe em outro projeto | **morto** (rodada 2: sobrevivente) | `TaskTypeTest.php:74`, `assertSessionHasNoErrors()` ("Failed asserting that true is false") |
| u3 | `create` sem `refuseDuplicate` e `duplicateRefusal` devolvendo `null`: rótulo repetido vira 500 | **morto** (rodada 2: sobrevivente) | `TaskTypeTest.php:62`, "Session is missing expected key [errors]" |
| u4 | `update` sem `refuseDuplicate` e `duplicateRefusal` devolvendo `null` | **morto** (rodada 2: sobrevivente) | `TaskTypeTest.php:69`, idem |
| ac2 | `TaskTypeController::reactivate` sem `authorize('manage')` | **morto no comportamento** (rodada 2: só pelo A5) | `TaskPolicyTest.php:261` → `:126` `assertCount(1, $rows)` ("actual size 0 matches expected size 1"); o A5 (`RotasAutorizadasTest`) também segue matando |

O u1 segue equivalente (rodada 2) e não foi rodado de novo: o código de `app/` não mudou.

**Total da rodada 3**: 4 mutantes, 4 mortos.
**Acumulado**: as 27 contagens da rodada 2 passam a 27 mortos de 27, mais os 55 da rodada 1 que já estavam mortos. Nenhum sobrevivente aberto.
**Sensor depth**: P0, manual.
**Result**: 4/4 mortos. PASS.

### Code Quality (rodada 3)

| Princípio | Status |
|---|---|
| Minimum code / Surgical changes | ✅ Só teste e spec. Nenhum código de produção mudou |
| Matches patterns | ✅ A negação usa o `assertOneDenied` do próprio arquivo; o teste do tipo usa as rotas e o gestor do `setUp` |
| Spec-anchored outcome check | ✅ A mensagem afirmada é a exata do `TaskTypeService` |
| Per-layer coverage | ✅ Criar, editar e outro projeto, pela rota; a recusa sem 500 está provada pelo u3 e pelo u4 |
| Every test maps to a spec requirement or review finding | ✅ Fix 6 (P18, CR-1-02) |

⚠️ A nota da rodada 2 continua e não bloqueia: `abilities.archive` do quadro vem de `$manages` (`TaskPresenter.php:116`), não de `can('archive', …)`.

### Fix Plans (rodada 3)

Nenhum. O Fix 6 está **fechado**.

#### Para o humano decidir (não é Fix Plan, segue da rodada 2)

- **Exclusão de verdade do tipo do projeto** (`DELETE /projetos/{id}/tipos/{taskType}`). Desde o `66c9907`, a spec registra a premissa como "**aguardando o ok do Lucas Scholz**". A regra está provada (t2, t3 e ac3 mortos na rodada 2), mas o ok é do humano, pelo AGENTS.md. O PR não deve mergear sem esse ok registrado.

### Requirement Traceability Update (rodada 3)

Os 7 requisitos seguem ✅ Verified. Só as linhas do `TaskPolicyTest` depois da `:261` andaram uma posição. Na spec: TSK-01 `:268-285` → `:269-286`, TSK-02 `:237-266` → `:237-267`, TSK-04 `:257-263` → `:257-264`.

### Summary (rodada 3)

**Overall**: ✅ Ready, pelo lado do Verifier. Falta só o ok humano da exclusão de tipo.

**Spec-anchored check**: os 7 requisitos verificados. As 4 asserções do Fix 6 batem com a mensagem e o efeito do Service.
**Sensor**: 4/4 nesta rodada (u2, u3, u4, ac2). Acumulado sem sobrevivente.
**Gate**: 634 passed (5121 assertions); Pint, typecheck e lint verdes.

**Lições**: nenhuma nova. O L-022 (duplicado por escopo) e o L-023 (porta em par) foram aplicados e funcionaram. O `lessons.py` conta recorrência por feature distinta, então registrar de novo nesta feature não muda nada.

**Next steps**:
1. O Lucas Scholz dá o ok na exclusão de verdade do tipo, na spec ou no PR.
2. Depois disso, o PR 1 do B3 pode seguir para abertura e merge. Pela regra da org, perguntar antes do `gh pr create`.

---

## Rodada 2 (19/10)

**Date**: 2026-10-19
**Diff range**: `24d8489..83879c4` (o commit `83879c4`, "os 5 ajustes do Verifier e os 6 achados da revisão do B3 PR 1"), sobre a árvore real em `feat/b3-quadro-e-card`.
**Verifier**: sessão separada de quem implementou, sem acesso ao raciocínio dela.
**Escopo**: os 5 Fix Plans da rodada 1, mais o que o commit acrescentou e entrou no sensor:
- tipo do projeto com desativar, reativar e excluir o nunca usado (CR-1-02): `TaskTypeService::deactivate/reactivate/delete`, `refuseProtected`, `TaskTypeController::deactivate/reactivate/destroy`, três rotas novas;
- contatos e canais só a quem cria card de suporte (CR-1-01, `TaskPresenter::options`);
- aviso de retrabalho só a dev ativo (CR-1-06, `TaskMoveService`);
- `down()` da 000010 com `withoutForeignKeyConstraints` e o `change()` antes dos drops (Fix 1).

### Gate Check (árvore real, HEAD `83879c4`, sem edição)

| Comando | Resultado |
|---|---|
| `composer lint` (`pint --test`) | `{"tool":"pint","result":"passed"}`, exit 0 |
| `php artisan test --compact` (MySQL 8, `wcj-hub-mysql-1`, `wcj_hub_test`) | **633 passed (5106 assertions)**, 105,73 s, exit 0 |
| `npm run typecheck` (`tsc --noEmit`) | exit 0 |
| `npm run lint` (`eslint .`) | exit 0 |
| Testes rodada 1 → rodada 2 | 623 → 633 (+10): `MigracaoCardDoQuadroTest` (2), `B3TransacaoTest` (1), `TaskTypeTest` (4), `TaskPolicyTest` (+2), `TaskServiceTest` (+1) |
| `migrate:fresh --seed` + `db:seed --class=ExemploSeeder` + `migrate:rollback --step=3` + `migrate` (`--env=testing`) | sem erro. "Dado de exemplo criado (cliente, 3 pessoas, projeto EXE com 3 cards)". A 000030, a 000020 e a 000010 descem `DONE` com os 3 cards tipados e sobem `DONE`. `migrate:status`: as três `[2] Ran`. O critério de sucesso da spec, que falhava na rodada 1, passa |

Não rodei Playwright, Storybook nem build: estão fora do trilho completo.

### Spec-Anchored Acceptance Criteria (rodada 2)

Linhas do HEAD `83879c4`. Os critérios que passaram na rodada 1 seguem com o mesmo teste; abaixo estão as linhas novas e os que estavam Needs Fix.

| Req | Critério | Evidência (`arquivo:linha` + asserção) | Resultado |
|---|---|---|---|
| TSK-01 | AC 1: admin, quem gere e dev alocado abrem o quadro → 200 | `TaskPolicyTest.php:136-147` (dev, `assertOk()`, `has('cards', 2)`, `abilities` exatas); `:149-158` (provider `quemGere`, as 7 `abilities` `true`) | PASS |
| TSK-01 | AC 3: dev cria, move fora do Backlog, reordena no Backlog, troca prioridade e participante | `TaskPolicyTest.php:220-235`: `:222` `assertRedirect()`, `:231` etapa `DEVELOPMENT`, `:232` `assertSame([1, 2], posições)`, `:233` `'high'`, `:234` `[$dev->id]`. Tipo e área: `TaskServiceTest.php:347-349` `assertSame([$area->id, tipo development, 'urgent'], …)` | PASS |
| TSK-01 | AC 6: quem gere faz tudo | `TaskPolicyTest.php:268-285`: sprint `:280`, Backlog `:281`, suporte `:282`, tipo `:283` `'pink'`, área `:284` `'red'` | PASS |
| TSK-01 | **`view(Task)` pela rota do card** (Fix 2) | Positivo: `TaskPolicyTest.php:178-193`, dev alocado, admin, gestor responsável e gestor alocado em `GET /projetos/{id}/cards/{chave}` → `assertRedirect('/projetos/{id}/quadro')` + `assertSessionHas('warning', "A ficha de {chave} ainda não está pronta: …")` (`:183-185`); `:187` nenhuma `denied`; `:191-192` chave de outro projeto e número inexistente → `assertNotFound()`. Negativo: `:168`, os três do `quemNaoVe` → `assertForbidden()` + `assertOneDenied(…, 'viewed', 'task', $todo, 'ficha')`. Mata p4 (`:168`) e p16 (`:183-188`) | PASS |
| TSK-01 | **`comment`, `attach`, `archive`, `delete` pelo Gate** (Fix 3) | `TaskPolicyTest.php:199-218`: `Gate::forUser($user)->allows($ability, $this->todo)` para 7 pessoas. `comment`/`attach` `:213` `assertSame([true, true, true, true, false, false, false], …)` (dev alocado, admin, gestor responsável, gestor alocado; dev, gestor e comercial de fora). `archive`/`delete` `:216` `[false, true, true, true, false, false, false]`. Mata p7, p8 (`:213`), p9, p10 (`:216`) | PASS |
| TSK-02 | AC 4: dev cruzando o Backlog → 403, nada muda | `TaskPolicyTest.php:237-246`: os três casos `assertOneDenied(…, 'moved', 'task', $task->id, $caso)->assertForbidden()` (`:242`); `:245` retrato igual | PASS |
| TSK-02 | AC 5: dev cria suporte, tipo ou área → 403, nada muda | `TaskPolicyTest.php:248-266`: suporte `:257` (`created`, `task`, `null`), tipo novo `:258`, editar tipo `:259`, **desativar tipo `:260`, excluir tipo `:261`** (rotas novas), área nova `:262`, editar área `:263`, todos `('updated', 'project', $projeto)` + `assertForbidden()`; `:265` retrato igual | PASS |
| TSK-03 | AC 2: quem não vê → 403, nada muda | `TaskPolicyTest.php:160-175` (provider `quemNaoVe`): quadro `:167`, ficha `:168`, card rápido `:169`, mover `:170`, etiqueta `:171`, participante `:172`, todos `assertForbidden()` dentro de `assertOneDenied`; `:174` retrato igual | PASS |
| TSK-04 | **AC 1: exatamente uma linha `denied` por requisição, com a ação e o alvo** (Fix 4) | Spec fechada em `83879c4` (`task` + id do card em `view`/`update`/`move`; `task` + id nulo em `create`/`createSupport`; `project` + id em tipo e área). Helper `TaskPolicyTest.php:120-134` `assertOneDenied`: `max('id')` antes da requisição, `:126` `assertCount(1, $rows)`, `:127-131` `assertSame(['denied', $action, $type, $id, $user->id], [outcome, action, auditable_type, auditable_id, user_id])`. Chamado nos 16 casos de `:167-172`, `:242` e `:257-263`, que cobrem os três alvos. Unitário do enum: `tests/Unit/AuditActionEnumTest.php:55,58` (`manageSprint`, `archive`). Mata pd1 e pd2 (`:167`) | PASS |
| TSK-04 | AC 2: nenhuma `success` na negação | o `assertCount(1, …)` de `:126` sobre as linhas novas exclui qualquer `success` na mesma requisição; o retrato (`:104-113`) conta `task_stage_movements` | PASS |
| MIG-01 | AC 1 a 4 | sem mudança: `MigracaoTiposDoProjetoTest.php:45-114` (rodada 1) | PASS |
| MIG-01 | **AC 5: `down()` volta à lista geral e remove colunas e tabela novas** (Fix 1) | 000030: `MigracaoTiposDoProjetoTest.php:116-127`. 000010 e 000020: `MigracaoCardDoQuadroTest.php:66-99`, que desce as três na ordem inversa e afirma `parent_task_id`, `cover_attachment_id`, `task_participants`, `task_types.color`, `task_types.project_id` e `project_areas.color` fora (`:72-77`); `task_type_id` e `task_priority_id` not null (`:78-79`); as duas FKs mantidas (`:80-81`). Depois sobe as três e afirma tudo de volta (`:87-98`). `:102-121`: com um card sem tipo, o `down()` da 000010 lança `QueryException` citando `task_type_id` (`:113`) e nada mais sai: `task_type_id` segue nulo, `parent_task_id`, `task_participants` e as duas cores ficam (`:116-120`). Mata g1 (`:70`, 1832) e g2 (`:118`) | PASS |
| MIG-02 | AC 6: os 5 tipos, **na mesma transação** (Fix 5) | Os 5: `ProjectTest.php:71-75`. Transação: `B3TransacaoTest.php:44-75`, com `DatabaseTruncation` (sem `RefreshDatabase`), `:46` `assertSame(0, DB::transactionLevel())`. Um `TaskTypeService` que grava "Melhoria" e lança injetado no `ProjectService` (`:50-58`). `:70` a mensagem da falha; `:71` 0 projetos, `:72` 0 etapas, `:73` 0 tipos (o gravado antes da falha voltou), `:74` 0 linhas de auditoria de `project`. Mata s3 (`:71`) | PASS |
| MIG-03 | AC 7 | sem mudança: `TaskServiceTest.php:74-81` | PASS |

**Edge cases**: os quatro seguem PASS (rodada 1). O 404 da chave de outro projeto agora também vale para o GET da ficha (`TaskPolicyTest.php:191-192`), além do PATCH (`:287-295`).

**Achados do code review conferidos pelo sensor** (trilho leve, fora dos requisitos da spec):
- CR-1-01, contatos e canais: `BoardTest.php:227-240`. Quem gere recebe só os contatos ativos do cliente do projeto (`:234`) e os canais (`:235`); o dev recebe `[[], []]` (`:239`) e o resto das opções igual (`:240`).
- CR-1-02, tipo do projeto: `TaskTypeTest.php:56-117` (desativar e reativar com auditoria, excluir só o nunca usado, os 3 de slug protegidos, tipo de outro projeto 404).
- CR-1-06, dev desativado: `TaskServiceTest.php:263-274`, com `Notification::assertNothingSent()`.

### Discrimination Sensor (rodada 2)

**Método.** O sensor rodou na árvore real, um mutante por vez, depois do gate. O script e os resultados ficaram em `/tmp/b3-sensor-r2/` (`run.py`, `results.json`, `run.log`). Para cada mutante, o script:
1. confere `git status --porcelain -- <arquivo>` vazio;
2. aplica a troca exata, que falha se o trecho não for único;
3. roda `php artisan test --compact --stop-on-failure <arquivos de teste>`;
4. reverte com `git checkout -- <arquivo>` e confere o arquivo limpo de novo.

Não usei `git reset` nem `stash`. `git status --porcelain` saiu vazio no fim.

**Base.** `php artisan test --compact tests/Feature/Projetos tests/Arch`: 264 passed (2120 assertions). Os três `authorize()` tirados do `TaskTypeController` rodaram também só contra `tests/Arch/RotasAutorizadasTest.php` (A5).

#### Os 7 sobreviventes da rodada 1

| # | Mutante | Resultado | Quem matou |
|---|---|---|---|
| p4 | `view(Task)` → `true` | morto | `test_who_does_not_see_the_project_gets_403_everywhere…` (`TaskPolicyTest.php:168`, a ficha deixa de dar 403 com `viewed`) |
| p16 | `view(Task)` → `false` | morto | `test_who_sees_the_project_opens_the_card_link…` (`:183-188`) |
| p7 | `comment` → `true` | morto | `test_comment_attach_archive_and_delete_decide…` (`:213`) |
| p8 | `attach` → `true` | morto | idem (`:213`) |
| p9 | `archive` → `true` | morto | idem (`:216`) |
| p10 | `delete` → `true` | morto | idem (`:216`) |
| s3 | `createDefaults` fora da transação (num `tap()` depois do commit) | morto | `B3TransacaoTest` (`:71`, o projeto ficou) |

#### Sondas de precisão (fora da contagem, mexem no Núcleo)

| # | Sonda | Resultado | Quem matou |
|---|---|---|---|
| pd1 | `AuditService::denied` grava `auditable_type = 'outro'` | morta | `TaskPolicyTest.php:167` (`assertOneDenied`) |
| pd2 | `AuditService::denied` grava `auditable_id` nulo | morta | idem |

#### Mutantes das mudanças novas

| # | Mutante | Resultado | Quem matou |
|---|---|---|---|
| g1 | `down()` da 000010 sem `withoutForeignKeyConstraints` | morto | `MigracaoCardDoQuadroTest::test_the_three_migrations_go_down…` (`:70`, `QueryException` 1832) |
| g2 | `down()` da 000010 com os drops (participantes, as duas cores) antes do `change()` | morto | `MigracaoCardDoQuadroTest::test_down_refuses_a_card_without_type…` (`:118`, `task_participants` saiu antes da recusa) |
| t1 | `TaskTypeService::deactivate` sem `refuseProtected` | morto | `TaskTypeTest::test_the_types_the_code_finds_by_slug…` |
| t2 | `TaskTypeService::delete` sem `refuseProtected` | morto | idem (`:97`) |
| t3 | `deleteIfUnused` trocado por `delete()` direto | morto | `TaskTypeTest::test_delete_only_the_type_no_card_uses` (o usado vira erro de FK, não o toast `ALREADY_USED`) |
| r6 | `TaskMoveService` sem `active()` no aviso | morto | `TaskServiceTest::test_a_deactivated_developer_is_not_warned…` (`:273`) |
| c1 | `options`: contatos a todos | morto | `BoardTest::test_options_bring_only_active_items_of_the_project` (`:239`) |
| c2 | `options`: canais a todos | morto | idem (`:239`) |
| c3 | `options` recebe `create_card` no lugar de `create_support` | morto | idem (`:239`) |
| ac1 | `TaskTypeController::deactivate` sem `authorize('manage')` | morto | `TaskPolicyTest::test_developer_does_not_create_support_card…` (`:260`); também o A5 |
| ac2 | `TaskTypeController::reactivate` sem `authorize('manage')` | morto **só pelo A5** (`RotasAutorizadasTest.php:104`, "… não autoriza"). O teste de comportamento passa: 21 OK em `TaskTypeTest` + `TaskPolicyTest` | A5. Lacuna de comportamento: nota do Fix 6 |
| ac3 | `TaskTypeController::destroy` sem `authorize('manage')` | morto | `TaskPolicyTest::…does_not_create_support_card…` (`:261`); também o A5 |
| u1 | `create` sem `refuseDuplicate` (fica só o unique do banco) | **equivalente** (256 OK em `tests/Feature/Projetos`) | O unique `task_types_project_id_label_unique` cai no `duplicateRefusal`, que monta a mesma mensagem, e a transação volta. Fora da contagem |
| u2 | `duplicate()` sem `where('project_id', …)`: recusa rótulo que só existe em outro projeto | **sobrevivente** (256 OK) | nenhum: Fix 6 |
| u3 | `create` sem recusa amigável (`refuseDuplicate` fora e `duplicateRefusal` → `null`): rótulo repetido vira erro 500 | **sobrevivente** (256 OK) | nenhum: Fix 6 |
| u4 | `update` sem recusa amigável (idem, na edição) | **sobrevivente** (256 OK) | nenhum: Fix 6 |

#### Amostra dos mortos da rodada 1

| # | Mutante | Resultado | Quem matou |
|---|---|---|---|
| p1 | `create` → `true` | segue morto | `TaskPolicyTest.php:169` |
| b2 | `$crossesBacklog = false` | segue morto | `TaskPolicyTest.php:242` |
| m3 | 000030 sem o `UPDATE` das tarefas | segue morto | `MigracaoTiposDoProjetoTest` (`QueryException`, FK no `delete` dos gerais) |
| m7 | 000030 sem `project_id` not null | segue morto | `MigracaoTiposDoProjetoTest.php:88` |
| m13 | 000030 sem a guarda `hasColumn` | segue morto | `MigracaoTiposDoProjetoTest::test_running_up_again…` (`QueryException`, coluna repetida) |

**Total da rodada 2**: 28 mutantes, 1 equivalente (u1), 27 contados.
- **24 mortos.** O ac2 está entre eles, morto só pelo A5, como o a2 da rodada 1.
- **3 sobreviventes**: u2, u3 e u4.

Os 22 mutantes que a spec exige (delegações, Backlog e `authorize()`) estão todos mortos: 17 na rodada 1 e os 5 que faltavam (p4, p7 a p10) nesta. A `TaskPolicy` e os FormRequests não mudaram no `83879c4` (`git diff 24d8489 83879c4 -- app/Domains/Projetos/Policies app/Domains/Projetos/Requests` vazio). Os 2 da migração (g1, g2) e os 7 sobreviventes da rodada 1 também morreram. Os sobreviventes estão no trilho leve (etiquetas), mas na lista desta rodada.

**Sensor depth**: P0 (permissão, exclusão de verdade, migração que reescreve dado), manual.
**Result**: 24/27 mortos. FAIL.

### Code Quality (rodada 2)

| Princípio | Status |
|---|---|
| Minimum code / Surgical changes | ✅ Os fixes mexem só no que o Fix Plan pedia. A Policy não mudou |
| Matches patterns | ✅ Tipo com as mesmas quatro portas da área: `setActiveLocked`, `deleteIfUnused` e `attempt`. ⚠️ Nota que segue da rodada 1: `abilities.archive` do quadro ainda vem de `$manages` (`TaskPresenter.php:116`), não de `can('archive', …)`. Não bloqueia: o resultado é o mesmo e a `TaskPolicy::archive` agora tem teste |
| Spec-anchored outcome check | ✅ Os 7 requisitos |
| Per-layer coverage | ✅ Rota da ficha, `down()` das três migrações, transação do `createDefaults`. ⚠️ A recusa de rótulo repetido do tipo não tem teste (Fix 6) |
| Every test maps to a spec requirement or review finding | ✅ |

### Fix Plans (rodada 2)

O Fix 1 ao Fix 5 da rodada 1 estão **fechados**, cada um com o teste que o Fix Plan pedia (tabela de critérios acima).

#### Fix 6 (mata u2, u3 e u4; só teste): rótulo repetido do tipo

- **Root cause**: nenhum teste cria nem edita tipo do projeto com rótulo que já existe. O `TaskTypeService::create/update` recusa com "Já existe o tipo {X} neste projeto." (`refuseDuplicate` + `duplicateRefusal`), mas a mensagem, o filtro por projeto e a recusa sem 500 não estão afirmados.
- **Fix task**: em `TaskTypeTest`, pela rota e como quem gere:
  - `POST /projetos/{id}/tipos` com `name = 'melhoria'` → `assertSessionHasErrors(['name' => 'Já existe o tipo Melhoria neste projeto.'])` e a contagem de tipos igual. Mata u3. Caixa e acento diferentes provam a colação;
  - `PUT /projetos/{id}/tipos/{Correção de bug}` com `name = 'Suporte'` → o mesmo erro, e o rótulo segue "Correção de bug". Mata u4;
  - criar `Pesquisa` em outro projeto e depois `POST … name = 'Pesquisa'` neste → `assertSessionHasNoErrors()` e um tipo novo neste projeto. Mata u2.
- **Nota (ac2)**: o `reactivate` sem `authorize` só morre no A5. Uma linha em `TaskPolicyTest::test_developer_does_not_create_support_card_nor_types_nor_areas` fecha no comportamento: `POST /tipos/{id}/reactivate` pelo dev → `assertOneDenied(…, 'updated', 'project', $projeto, 'reativar tipo')->assertForbidden()`.
- **Priority**: Minor (trilho leve, sem efeito em permissão nem em dado).

#### Para o humano decidir (não é Fix Plan)

- **Exclusão de verdade nova.** O `DELETE /projetos/{id}/tipos/{taskType}` (CR-1-02) é um `delete` de registro de negócio. Pelo AGENTS.md, isso pede aprovação humana e trilho completo. A regra está provada: só o nunca usado sai (`deleteIfUnused`), os de slug são protegidos, só quem gere, auditado, e t2, t3 e ac3 morreram. Mas a spec do PR 1 não lista essa exclusão nas premissas aprovadas em 19/10. Falta o "ok" do Lucas Scholz, registrado na spec ou no PR.

### Requirement Traceability Update (rodada 2)

| Requirement | Rodada 1 | Rodada 2 |
|---|---|---|
| TSK-01 | ❌ Needs Fix | ✅ Verified (Fix 2 e Fix 3 fechados) |
| TSK-02 | ✅ Verified | ✅ Verified |
| TSK-03 | ✅ Verified | ✅ Verified |
| TSK-04 | ⚠️ Needs Fix | ✅ Verified (Fix 4 fechado; spec precisa) |
| MIG-01 | ❌ Needs Fix | ✅ Verified (Fix 1 fechado) |
| MIG-02 | ⚠️ Needs Fix | ✅ Verified (Fix 5 fechado) |
| MIG-03 | ✅ Verified | ✅ Verified |

### Summary (rodada 2)

**Overall**: ❌ Not Ready: falta o Fix 6, só de teste.

**Spec-anchored check**: os 15 ACs batem com o valor da spec, e os 7 requisitos estão verificados.
**Sensor**: 24/27 mortos. 3 sobreviventes (u2, u3, u4), todos na recusa de rótulo repetido do tipo. 1 equivalente (u1). As 2 sondas morreram.
**Gate**: 633 passed (5106 assertions); Pint, typecheck e lint verdes. `migrate:rollback --step=3` + `migrate` sem erro, com dado de exemplo.

**Next steps**:
1. O implementador fecha o Fix 6 (três asserções em `TaskTypeTest` e, de preferência, a linha do ac2).
2. O Lucas Scholz dá o "ok" na exclusão de verdade do tipo.
3. Rodada 3 do Verifier só sobre o Fix 6: u2, u3, u4 e ac2 no comportamento.

---

## Rodada 1 (19/10)

### Validation: FAIL (rodada 1)

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

### Spec-Anchored Acceptance Criteria

#### TSK-01: qualquer alocado usa o quadro (P1 "Qualquer alocado", AC 1, 3 e 6)

| Critério | Valor da spec | Evidência (`arquivo:linha` + asserção) | Resultado |
|---|---|---|---|
| AC 1: admin, quem gere e dev alocado abrem `/projetos/{id}/quadro` → 200 com colunas e cards | 200 | Dev: `TaskPolicyTest.php:124-132` `assertOk()`, `component('projects/board')`, `has('cards', 2)`, `abilities` exatas. Admin, gestor responsável e gestor alocado: `:136-143` (provider `quemGere`, `:63-66`) `assertOk()` e `abilities` todas `true` | PASS |
| AC 3: dev alocado cria card rápido, move entre colunas fora do Backlog, reordena (inclusive no Backlog), troca tipo, área, prioridade, participante → grava | gravado | `TaskPolicyTest.php:165-180`: criar `:167` `assertRedirect()` + `:170` `sole()`; mover To do → Desenvolvimento `:168` `assertRedirect()` + `:176` etapa `DEVELOPMENT`; reordenar no Backlog `:172` + `:177` `assertSame([1, 2], posições)`; prioridade `:178` `'high'`; participante `:179` `[$dev->id]`. Tipo e área pelo PATCH: `TaskServiceTest.php:320-338` (`assertSame([$area->id, tipo development, 'urgent'], ...)`, via Service; pela rota só a prioridade) | PASS |
| AC 6: admin e quem gere fazem tudo → grava | gravado | `TaskPolicyTest.php:212-227` (provider `quemGere`): pôr na sprint `:223` `[$sprint->id, SPRINT]`, levar ao Backlog `:224` `[null, QUEUE]`, card de suporte `:225`, tipo `:226` `'pink'`, área `:227` `'red'` | PASS |
| Abilities da premissa: `view(Task)`, `comment`, `attach` (alocado); `archive`, `delete` (quem gere) | cada uma decide pela `ProjectPolicy` | **Nenhum teste.** `view(Task)` só é chamada em `TaskController::show` (`TaskController.php:39`), e nenhum teste faz `GET /projetos/{id}/cards/{chave}` (`grep` em `tests/` e `e2e/`). `comment`, `attach`, `archive` e `delete` não têm chamador: o `archive` do quadro sai de `ProjectPolicy::manage` direto (`TaskPresenter.php:107,114`), não de `TaskPolicy::archive`. Os mutantes p4, p16, p7, p8, p9 e p10 sobrevivem | ❌ GAP (Fix 2, Fix 3) |

#### TSK-02: o dev não mexe na sprint nem cria suporte, tipo ou área (AC 4 e 5)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 4: dev alocado move do Backlog para outra coluna, ou de outra coluna para o Backlog → 403, etapa, sprint e posição mantidas | 403; `board_stage_id`, `sprint_id`, `position` iguais | `TaskPolicyTest.php:182-192`: Backlog → To do `:186`, Backlog → Desenvolvimento `:187`, To do → Backlog `:188`, os três `assertForbidden()`; `:190` `assertSame($antes, $this->retrato())`, com o retrato de `:102-111` (`board_stage_id`, `sprint_id`, `position`, movimentos) | PASS |
| AC 5: dev cria card de suporte, cria ou edita tipo ou área → 403, nada muda | 403; nada novo | `TaskPolicyTest.php:194-209`: suporte `:200`, tipo novo `:201`, editar tipo `:202`, área nova `:203`, editar área `:204`, todos `assertForbidden()`; `:206` retrato igual (conta tipos e áreas) | PASS |

#### TSK-03: quem não vê o projeto recebe 403 e nada muda (AC 2)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 2: dev não alocado, gestor que não gere, Comercial abrem o quadro ou mandam escrita de card → 403, nada muda | 403; nada muda | `TaskPolicyTest.php:146-163` (provider `quemNaoVe`, `:57-60`): quadro `:152`, criar `:153`, mover `:154`, etiquetas `:155`, participante `:156`, todos `assertForbidden()`; `:158` retrato igual | PASS |

#### TSK-04: negação auditada com a ação certa (P1 "Negação auditada", AC 1 e 2)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: negação → `denied` com `auditable_type = task` (ou `project`) e a ação `created`/`viewed`/`updated`/`moved`/`archived`/`deleted` | ação por ability; `auditable_type` | Ação: `TaskPolicyTest.php:113-120` `assertNegado` (`where('action', ...)->exists()`), chamado em `:159-162` (`viewed`, `created`, `moved`, `updated`), `:191` (`moved`), `:207-208` (`created`, `updated`). Unitário: `AuditActionEnumTest.php:53-58`. **`auditable_type` não é afirmado em lugar nenhum:** a sonda pd1, que grava `'outro'` no lugar do tipo, passa no `TaskPolicyTest` inteiro. **A spec também é imprecisa:** "ou `project`, nas abilities que recebem o projeto". O código grava `task` com id nulo em `create` e `createSupport`, porque o primeiro argumento é `Task::class` (`StoreTaskRequest.php:16`, `AuditService::deniedTarget`). Grava `project` com o id em tipo e área (`ManagesProject`). Falta decidir qual dos dois a spec quer e afirmar. Notas: `assertNegado` usa `exists()`, sem conferir uma linha nova por requisição (L-015 do B5); `archived` e `deleted` não têm caminho que negue | ⚠️ Spec-precision gap (Fix 4) |
| AC 2: na negação, nenhuma linha `success` de `task` nem de `task_stage_movement` | 0 linhas `success`; nenhum movimento | `TaskPolicyTest.php:119` `assertFalse(...where('outcome','success')->exists())` por usuário; o retrato (`:106`) conta `task_stage_movements` | PASS |

#### MIG-01: tipos do projeto sem perder dado (AC 1 a 5)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: cada projeto com cópia de cada tipo geral (slug, rótulo, desativação) + "Desenvolvimento"; cada tarefa na cópia do próprio projeto | os rótulos, a desativação, a tarefa no próprio projeto | `MigracaoTiposDoProjetoTest.php:45-80`: `:66` os 6 rótulos por projeto, em ordem; `:72-73` `assertEquals((object) ['project_id' => $a->id, 'label' => 'Suporte', 'slug' => 'support'], ...)`, e o mesmo para a "Pesquisa" em B; `:74` tarefa sem tipo continua nula; `:77-78` cor, `deactivated_by` e `deactivated_at` copiados; `:79` um `development` | PASS |
| AC 2: nenhum tipo sem projeto; `project_id` not null | 0; `nullable = false` | `:68` `assertSame(0, ...whereNull('project_id')->count())`; `:87-88` `assertFalse($coluna['nullable'])` | PASS |
| AC 3: sem projeto, os gerais saem sem erro | 0 tipos | `:106-114` `assertSame(0, DB::table('task_types')->count())` | PASS |
| AC 4: rodar de novo não duplica | rótulos iguais | `:97-104` `up()` de novo, `assertSame([...5 rótulos], ...)` | PASS |
| AC 5: `down()` volta à lista geral **e remove as colunas e a tabela novas** | um tipo por rótulo, tarefas nele; `parent_task_id`, `cover_attachment_id`, `task_participants`, as cores fora | A parte da 000030: `:116-127` `assertSame(5, count)` e as duas tarefas no mesmo geral. **A parte da 000010 falha:** `migrate:rollback --step=3` sai com `SQLSTATE[HY000]: General error: 1832 Cannot change column 'task_type_id': used in a foreign key constraint 'tasks_task_type_id_foreign'`. Reproduzido num banco sem tarefa (`migrate:fresh` e três `rollback --step=1`: a 000030 e a 000020 voltam, a 000010 falha). Depois da falha, `migrate:status` mostra a 000010 `Ran` e as cores e `task_participants` já fora; o `migrate` seguinte quebra em `Undefined property: stdClass::$color`. Nenhum teste roda o `down()` da 000010 nem o da 000020 | ❌ FAIL (Fix 1) |

#### MIG-02: projeto novo nasce com os 5 tipos (AC 6)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 6: projeto novo grava os 5 tipos padrão, **na mesma transação** | os 5 (slug, rótulo), na ordem; mesma transação | Os 5: `ProjectTest.php:71-75` `assertSame([['improvement','Melhoria'], [null,'Correção de bug'], ['support','Suporte'], [null,'Mudança (GMUD)'], ['development','Desenvolvimento']], ...)`. **Mesma transação: sem prova.** `ProjectTest.php:146` confere 5 tipos depois do prefixo repetido, mas a falha acontece no `Project::create`, antes do `createDefaults`. O mutante s3, que tira o `createDefaults` da transação e o roda num `tap()` depois do commit, sobrevive | ⚠️ GAP de precisão (Fix 5) |

#### MIG-03: card sem tipo e sem prioridade (AC 7)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 7: o banco aceita `task_type_id` e `task_priority_id` nulos | nulos gravados | `TaskServiceTest.php:74-81` `assertSame([17, 'Tela de login', null, null, null, null], [number, title, task_type_id, task_priority_id, project_area_id, sprint_id])` | PASS |

#### Edge cases

| Edge case | Evidência | Resultado |
|---|---|---|
| Gestor perde o papel → negado na próxima requisição | `TaskPolicyTest.php:240-246` (`role_id` → dev; levar ao Backlog `assertForbidden()`) | PASS |
| Pessoa tirada do projeto → negado na próxima requisição | Sem teste no `TaskPolicyTest`. Coberto por equivalência: toda ability passa pela `ProjectPolicy::view`, que lê `project_members` a cada chamada. O "dev não alocado" do `quemNaoVe` (`:57-60`) é o mesmo estado, e o "saiu do projeto" pela `ProjectPolicy` está provado em `TimeEntryPolicyTest.php:146` | PASS (por equivalência; nota) |
| Chave de outro projeto ou inexistente → 404 antes da Policy | `TaskPolicyTest.php:230-238` `assertNotFound()` ×2 e `:237` `assertSame(0, ...denied...count())` | PASS |
| Rótulo igual em dois projetos → aceito | `MigracaoTiposDoProjetoTest.php:89` `assertSame(2, ...where('label','Melhoria')->count())` | PASS |

---

### Discrimination Sensor

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

#### Mutantes pedidos pela spec

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

#### Mutantes extras (direção oposta, bordas do Backlog, argumentos, enum)

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

### Gate Check (árvore real, HEAD `1828dff`, sem edição)

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

### Code Quality (escopo do trilho completo)

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

### Fix Plans

O Fix 1 é de código: a migração. Os Fix 2 a 5 são só de teste (o 4 também pede uma linha na spec). Fica tudo com quem implementou: o Verifier não corrige.

#### Fix 1 (MIG-01 AC 5 e critério de sucesso; Major): o `down()` da 000010 não roda

- **Root cause**: `2026_10_16_000010_add_card_do_quadro_to_tasks.php:69-71` faz `->nullable(false)->change()` em `task_type_id` e `task_priority_id` com a FK ligada, e o MySQL 8.4 recusa (1832). Como o DDL não é transacional, as três instruções anteriores do `down()` já rodaram (as duas cores e o `dropIfExists('task_participants')`). A linha da migração fica em `migrations`, e o `migrate` seguinte quebra na 000030 (`Undefined property: stdClass::$color`).
- **Fix task**: no `down()` da 000010, fazer o `change()` das duas colunas com as FKs desligadas: `Schema::withoutForeignKeyConstraints(fn () => Schema::table('tasks', ...change()...))`, ou soltar e refazer as duas FKs. Depois, o `dropForeign`/`dropIndex`/`dropColumn` do `parent_task_id` como está. A ordem do `down()` também deve deixar o banco consistente se um passo falhar: o `change()` primeiro, o `drop` das cores e da tabela por último. **Conferido pelo Verifier só na cópia descartável:** com o `withoutForeignKeyConstraints` em volta do `change()`, `migrate:fresh` → `migrate:rollback --step=3` → `migrate` roda sem erro.
- **Teste que falta**: um `MigracaoCardDoQuadroTest` (`DatabaseTruncation` + `UsaBancoDeVerdade`, no molde do `MigracaoTiposDoProjetoTest`). Ele roda o `down()` da 000030, da 000020 e da 000010, nessa ordem, e afirma:
  - `parent_task_id`, `cover_attachment_id`, `task_participants`, `task_types.color` e `project_areas.color` fora;
  - `task_type_id` e `task_priority_id` not null.

  Depois roda o `up()` das três e afirma tudo de volta. O `tearDown` repõe o schema.
- **Priority**: Major (só dev, mas é AC da spec e o critério "`migrate:rollback` + `migrate` sem erro").

#### Fix 2 (TSK-01, mata p4, p16 e o a2 no comportamento): `view(Task)` e a rota do card

- **Fix task**: em `TaskPolicyTest`, `GET /projetos/{id}/cards/{prefixo}-{n}`:
  - dev alocado e os três do `quemGere` → `assertRedirect('/projetos/{id}/quadro')` + `assertSessionHas('warning', 'A ficha de {chave} ainda não está pronta: chega no próximo PR do quadro.')`;
  - os três do `quemNaoVe` → `assertForbidden()` + uma linha `denied` `viewed` com `auditable_type = 'task'` e `auditable_id` do card.

  O 404 da chave de outro projeto já existe para o PATCH (`:230-238`); repetir para o GET.

#### Fix 3 (premissa "Abilities da TaskPolicy", mata p7, p8, p9 e p10)

- **Fix task**: as quatro abilities não têm chamador. Duas saídas, como no Fix 2 do B5:
  - **Teste direto da Policy.** Para `comment` e `attach`: `Gate::forUser($user)->allows(..., $task)` verdadeiro para o dev alocado e os três do `quemGere`, falso para os três do `quemNaoVe`. Para `archive` e `delete`: verdadeiro para os três do `quemGere`, falso para o dev alocado e os do `quemNaoVe`.
  - **Tirar as quatro até o PR 2 e o PR 3 terem as rotas.** Mudar a Policy pede o "ok" do Lucas Scholz.
- **Nota**: o `abilities.archive` do quadro (`TaskPresenter.php:114`) usa `$manages`, não `can('archive', ...)`. Se as abilities ficarem, o presenter pode perguntar a elas, para haver um jeito só.

#### Fix 4 (TSK-04 AC 1, lacuna de precisão)

- **Spec**: decidir o `auditable_type` da negação de `create` e `createSupport`. Hoje grava `task` com `auditable_id` nulo. Ou a spec troca "(ou `project`, nas abilities que recebem o projeto)" por "`task` com id nulo em `create` e `createSupport`; `project` em tipo e área", ou o código passa o projeto.
- **Teste**: em `TaskPolicyTest::assertNegado` (`:113-120`), afirmar `auditable_type` e `auditable_id` da linha. E contar exatamente uma linha `denied` nova por requisição, pegando o `max('id')` antes, como o `assertOneDenied` do `SprintPolicyTest` (L-015).

#### Fix 5 (MIG-02 AC 6, mata s3): os 5 tipos na mesma transação do projeto

- **Fix task**: um teste sem `RefreshDatabase` (`DatabaseTruncation` + `UsaBancoDeVerdade`, molde `B5TransacaoTest`). Ele injeta no `ProjectService` um `TaskTypeService` que cria o primeiro tipo e lança, chama `create()` e afirma pelo banco:
  - `Project::count() === 0`;
  - `task_types` vazio;
  - nenhuma linha `created` de `project`.

  Uma falha na auditoria não serve, porque ela também impede o `createDefaults` no mutante. A falha tem de vir de dentro do `createDefaults`.

#### Notas (não bloqueiam)

- m9: o unique `(project_id, slug)` só morre porque o `down()` não acha o índice. Nenhum teste insere slug repetido no mesmo projeto. Uma linha a mais em `test_project_id_is_not_null_and_labels_are_unique_per_project_only` fecha.
- e3 e e4 (`archive` e `manageSprint` no enum) só morrem no unitário, porque nenhum caminho da feature nega essas abilities. Basta enquanto as abilities não tiverem rota.
- `TaskPolicyTest.php:172` (reordenar no Backlog) usa `assertSessionHasNoErrors()`, que passa num 403. Quem prova é a posição em `:177`, e o b3 morreu por ela. Um `assertRedirect()` deixaria o teste mais direto.
- A janela entre a Policy (`move`, que lê a etapa antes da transação) e a trava do `TaskMoveService` já foi registrada sem achado no ciclo 1 da `wcj-code-review` e não foi mutada.

---

### Requirement Traceability Update

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

### Summary

**Overall**: ❌ Not Ready

**Spec-anchored check**: 12 dos 15 ACs batem com o valor da spec. 1 AC falha (MIG-01 AC 5). 2 têm lacuna de precisão (TSK-04 AC 1, MIG-02 AC 6). Há ainda a lacuna das abilities sem teste (TSK-01).
**Sensor**: 55/62 mortos, 7 sobreviventes (p4, p16, p7, p8, p9, p10, s3).
**Gate**: 623 passed, Pint, typecheck e lint verdes. `migrate:rollback --step=3` falha.

**What works**: quem vê o projeto usa o quadro e quem não vê recebe 403 sem mudar nada. O dev não cruza o Backlog nem cria suporte, tipo ou área. Todo FormRequest autoriza antes de validar, e a negação vira `denied` com a ação certa. A migração dos tipos copia, repõe, apaga, trava o not null e o unique por projeto, roda duas vezes sem duplicar e volta pelo `down()`. O projeto novo nasce com os 5 tipos. O card rápido aceita tipo e prioridade nulos.

**Next steps**: o Cauã fecha os Fix 1 a 5, com a decisão da spec no Fix 4 e, se escolher tirar as abilities no Fix 3, o "ok" do Lucas Scholz. Depois, rodada 2 do Verifier sobre o diff dos fixes, com os 7 sobreviventes, a sonda pd1 e uma amostra dos mortos.
