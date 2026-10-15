# B5 (SprintPolicy, TimeEntryPolicy e as duas exclusões) Validation (rodada 2)

## Validation: PASS

**Result**: PASS na rodada 2. O `8b30f7f` fecha os cinco gaps da rodada 1, e só mexe em teste e na spec. O código de produção é o mesmo do `7667d75`: `git diff 7667d75..8b30f7f -- app routes resources` sai vazio. Os sete sobreviventes da rodada 1 agora morrem numa cópia descartável:
- x6, x17, x18 e x19, que eram gaps de teste;
- s2 e s11, a ability sem chamador;
- x20, o mesmo do x19 no `TimeEntryService::delete`.

Uma amostra de 14 mutantes que já tinham morrido na rodada 1 voltou a morrer. Então a mudança no `assertDeniedForAll` não enfraqueceu nada. Todos os sete critérios (SPR-01 a SPR-03, HRS-01, HRS-02, DEL-01, DEL-02) passam.

Gate verde na árvore real (HEAD `8b30f7f`):
- `composer lint`: passou.
- `php artisan test`: **541 passed (4312 assertions)**. Eram 535: +4 no `SprintPolicyTest` (3 do provider + 1) e +2 no `B5TransacaoTest`.
- `npm run typecheck`: exit 0.
- `npm run lint`: exit 0.

O PASS não encerra a feature (`.ai/core/especificacao.md` §5 item 9).

**Date (rodada 2)**: 2026-10-15
**Spec (rodada 2)**: `.specs/features/b5-sprint-e-horas/spec.md`, aprovada por Lucas Scholz em 15/10/2026. A única mudança é o "Independent Test" do DEL-02, que agora aponta `TimeEntryServiceTest.php` e `B5TransacaoTest.php`.
**Diff range (rodada 2)**: `7667d75..8b30f7f`, um commit:
- `tests/Feature/Projetos/SprintPolicyTest.php` (+51/−6);
- `tests/Feature/Projetos/B5TransacaoTest.php` (novo, +93);
- `tests/Feature/Projetos/TimeEntryServiceTest.php` (+3);
- `spec.md` (1 linha);
- o `validation.md` da rodada 1.

**Verifier (rodada 2)**: subagente independente (autor ≠ verifier), escopo focado nos Fix 1 a 5 da rodada 1

### Fix da rodada 1 → evidência (rodada 2)

| Fix | Critério | Evidência (`arquivo:linha` + asserção) | Resultado |
|---|---|---|---|
| 1 | SPR-01 AC 1 e DEL-01 AC 1: quem gere exclui e cria pela rota | `SprintPolicyTest.php:220-237`, provider `quemGere` (admin, gestor responsável, gestor alocado). Excluir: `:228-229` `assertRedirect(.../sprints)` + `assertSessionHas('success', 'Sprint 2 excluída.')`; `:230` `assertNull(Sprint::find(...))`; `:231-232` a linha `deleted` `sole()` com `[user_id, 'success', ['Sprint 2', null]]`. Criar: `:234-236` `assertSessionHasNoErrors()` + `'Sprint 3 criada.'`; `:237` uma aberta com esse nome | PASS (x6 e `s-create-false` mortos, nos três papéis) |
| 2 | Critério de sucesso: `SprintPolicy::view(Sprint)` | `SprintPolicyTest.php:241-254`. `:247` `Gate::forUser($user)->allows('view', $encerrada)` verdadeiro para admin, gestor e dev alocado. `:250` falso para dev não alocado, gestor que não gere e Comercial. `:253` o dev não tem `update` | PASS (s2 e s11 mortos) |
| 3 | SPR-02 AC 6: uma `denied` desta requisição e desta pessoa | `SprintPolicyTest.php:210-218` `assertOneDenied`, chamado em `:191` (escritas) e `:202` (tarefas). Pega o `max('id')` antes da requisição, `assertCount(1, $rows)` com as linhas novas, e `[auditable_type, action, user_id]` | PASS (x17 em "tirar", x17b em "aprovar" e x17c em "reabrir", os três como `abort_unless(...can..., 403)` sem `denied`, mortos) |
| 4 | DEL-02 AC 4: pessoa e horas no antes | `TimeEntryServiceTest.php:40` `assertSame([$dev->id, null], changes['user_id'])`, `:41` `assertSame(['3.00', null], changes['hours'])`. Tarefa e data já eram afirmadas em `:37-38` | PASS (x18 morto) |
| 5 | DEL-01 AC 1 e DEL-02 AC 4: na mesma transação | `B5TransacaoTest.php` usa `DatabaseTruncation` + `UsaBancoDeVerdade`, sem `RefreshDatabase`; `:72` e `:84` conferem `transactionLevel() === 0`. Um `AuditService` que lança em `log()` (`:47-56`) é injetado nos Services. Sprint: `:77` falha com `'a auditoria falhou'`, `:79` a sprint continua lá. Lançamento: `:89` e `:91`, idem. Rodou no MySQL 8, não foi pulado (2 testes, 6 asserções). O `failure()` (`:59-68`) também pega o `fail()` do PHPUnit, mas a comparação com a mensagem exata em `:77`/`:89` faz o teste reprovar mesmo assim | PASS (x19 e x20 mortos) |

### Sensor de mutação (rodada 2)

O mesmo isolamento da rodada 1:
- cópia `git worktree add /tmp/b5-verifier HEAD --detach` em `8b30f7f`, removida no fim;
- `vendor/` com um symlink por pacote, mais cópias reais de `composer`, `autoload.php` e `bin`;
- autoload conferido em `/private/tmp/b5-verifier/app/...`;
- testes por `php vendor/bin/phpunit`, uma suíte por vez;
- restauração com `git checkout -- <arquivo>` na cópia.

Base na cópia: `SprintPolicyTest` + `TimeEntryServiceTest` + `SprintServiceTest` com 43 OK, e `B5TransacaoTest` com 2 OK. A árvore real ficou com `git status --porcelain` vazio antes e depois; só este `validation.md` foi editado.

| # | Mutante | Rodada 1 | Rodada 2 | Quem matou |
|---|---|---|---|---|
| x6 | `SprintPolicy::delete` → `false` | vivo | **morto** | `test_who_manages_creates_and_deletes_an_empty_open_sprint` |
| s2 | `SprintPolicy::view` → `true` | vivo | **morto** | `test_view_of_a_sprint_follows_who_sees_the_project` |
| s11 | `SprintPolicy::view` → `manage` | vivo | **morto** | idem |
| x17 | "tirar" (`SprintTaskController::destroy`) com `abort_unless(can(...), 403)`, sem `denied` | vivo | **morto** | `…cannot_write…` (dev e gestor), pelo `assertOneDenied` |
| x17b | idem em "aprovar" (novo) | não rodado | **morto** | idem |
| x17c | idem em "reabrir" do `SprintController` (novo) | não rodado | **morto** | idem |
| x18 | `TimeEntryService::delete` com o antes sem `user_id` e `hours` | vivo | **morto** | `TimeEntryServiceTest::test_create_update_and_delete_are_audited_with_the_before` |
| x19 | `SprintService::delete` sem `DB::transaction` | vivo | **morto** | `B5TransacaoTest::test_sprint_delete_rolls_back_when_the_audit_fails` |
| x20 | `TimeEntryService::delete` sem `DB::transaction` (novo) | não rodado | **morto** | `B5TransacaoTest::test_time_entry_delete_rolls_back_when_the_audit_fails` |
| amostra | `s-create-false`, `s-create-true`, `s-viewCapacity-view`, `t-dono-sem-view`, `t-gestor-manage-vira-view`, `c-sprint-destroy`, `c-sprinttask-reject`, `f-SprintTask-viewAny`, `f-StoreTimeEntry-viewAny`, `v-lockProject-sem-lock`, `v-delete-sem-tasks`, `v-delete-sem-closed_at`, `v-time-delete-sem-audit`, `e-sem-manageTasks` | os 14 mortos | **os 14 mortos** | os mesmos testes da rodada 1. O `s-create-false` agora também morre no teste novo do Fix 1 |

**Total das duas rodadas**: 63 mutantes distintos (os 60 da rodada 1, mais x17b, x17c e x20). Nenhum sobrevivente.
- 62 mortos.
- 1 equivalente (x12: o `! isSales()` do dono, que já sai negado pelo `ProjectPolicy::view`).

Os 39 mutantes pedidos na rodada 1 estão todos mortos.

### Gate (rodada 2, árvore real, HEAD `8b30f7f`, sem edição)

| Comando | Resultado |
|---|---|
| `composer lint` (`pint --test`) | passed |
| `php artisan test` (MySQL 8, `wcj-hub-mysql-1`) | **541 passed (4312 assertions)**, 133 s, 1ª execução |
| `npm run typecheck` | exit 0 |
| `npm run lint` | exit 0 |

Não rodei o Playwright, o Storybook nem o build: o commit não toca front.

### Fix necessários (rodada 2)

Nenhum.

As duas notas da rodada 1 seguem valendo, e nenhuma bloqueia:
- os 404 de outro projeto não afirmam a ausência de `denied`;
- o A5 aceita `abort_unless(can(...))`.

A segunda agora tem rede no `SprintPolicyTest`, mas só nas rotas que ele exercita.

---

## Rodada 1 (histórico): FAIL

**Result (rodada 1)**: FAIL na rodada 1, por **cinco gaps Minor, todos só de teste**. Nenhum pede mudança no código de produção. O núcleo de permissão está provado:
- As delegações da `SprintPolicy` que algum chamador usa, trocadas por `true` e por `false`, morreram, menos `delete → false`.
- Os cinco mutantes pedidos da `TimeEntryPolicy` morreram.
- Os sete `authorize()` tirados dos controllers morreram no teste de comportamento e no A5.
- Os cinco `abilityAndArguments` trocados por `viewAny` morreram.
- A trava e as checagens da "uma sprint aberta" morreram, inclusive no teste de concorrência.
- As exclusões morreram nas recusas e na auditoria.
- Os três mapeamentos novos do `AuditActionEnum` morreram no teste de feature e no unitário.

O que reprova:
1. O gestor excluindo a sprint pela rota (SPR-01 AC 1, DEL-01 AC 1) não tem teste: `SprintPolicy::delete → false` sobrevive.
2. `SprintPolicy::view(Sprint)` não tem chamador nem teste, e a spec pede que cada delegação trocada por `true` morra.
3. A linha `denied` das ações de tarefa da sprint é lida por `latest('id')`, sem conferir que a requisição gravou uma: um 403 sem `denied` em "tirar" sobrevive.
4. O "antes" da exclusão do lançamento (DEL-02 AC 4) não afirma pessoa nem horas.
5. "Na mesma transação" (DEL-01 AC 1, DEL-02 AC 4) não tem prova.

Gate verde na árvore real (HEAD `7667d75`):
- `composer lint`: passou.
- `php artisan test`: **535 passed (4243 assertions)**.
- `npm run typecheck`: ok.
- `npm run lint`: ok.

**Date**: 2026-10-15
**Spec**: `.specs/features/b5-sprint-e-horas/spec.md` (SPR-01 a SPR-03, HRS-01, HRS-02, DEL-01, DEL-02), aprovada por Lucas Scholz em 15/10/2026
**Diff range**: `f5d15f4..7667d75` (9 commits, 82 arquivos, +6459/−90). O escopo do trilho completo é o seguinte:
- `app/Domains/Projetos/Policies/{Sprint,TimeEntry}Policy.php`;
- registro no `AppServiceProvider`;
- `AuditActionEnum::forAbility`;
- `SprintController`, `SprintTaskController`, `TimeEntryController`;
- os FormRequests `StoreSprint`, `CloseSprint`, `SprintTask`, `EstimateTask`, `StoreTimeEntry`;
- `SprintService`, `SprintTaskService`, `TimeEntryService` (estado da sprint e exclusões);
- `SprintScreen`/Presenters (o `can` e a capacidade);
- `routes/web.php`.

O resto do B5 (telas, DueDate, CapacityQuery) é trilho leve e ficou fora.
**Verifier**: subagente independente (autor ≠ verifier), sem acesso ao raciocínio de quem implementou

### Critérios de aceite

#### SPR-01: escritas de sprint só de quem gere (AC 1–5)

| Critério | Valor da spec | Evidência (`arquivo:linha` + asserção) | Resultado |
|---|---|---|---|
| AC 1: admin, gestor responsável e gestor alocado criam, editam, encerram, reabrem, excluem → sucesso | redirecionamento de sucesso nas cinco | `SprintPolicyTest.php:114-131` (provider `quemGere`, `:54-57`): editar `:119-120` `assertSessionHasNoErrors()->assertSessionHas('success')`, encerrar `:124-125`, reabrir `:126`, com o estado em `:128-130`. Criar: só o gestor responsável, `SprintControllerTest.php:100-101` `assertRedirect(...?sprint=id)`. **Excluir pela rota: nenhum teste** (`grep` em `tests/` e `e2e/`): o mutante `s-delete-false` sobrevive | ❌ GAP (Fix 1) |
| AC 2: dev alocado envia as cinco escritas → 403 | 403 | `SprintPolicyTest.php:167-170` → `:189-190` `assertForbidden()` para criar, editar, encerrar, reabrir, excluir (`:78-89`) | PASS |
| AC 3: gestor que não gere → 403 em qualquer escrita | 403 | `SprintPolicyTest.php:172-175` → `:189-190` e `:202-203`; o gestor rebaixado também, em `:177-183` | PASS |
| AC 4: dev alocado pôr, tirar card, gravar horas previstas → 403 | 403 | `SprintPolicyTest.php:195-203`: pôr, tirar, horas, aprovar, recusar, `assertForbidden()` | PASS |
| AC 5: negada → sprint e tarefas sem mudança, nenhum `success` de `sprint` nem `task` | nome, datas, `closed_at`, `sprint_id`, etapa, `estimated_hours` iguais; 0 linhas `success` | `SprintPolicyTest.php:92-98` (retrato com `name`, `starts_on`, `ends_on`, `closed_at`, `sprint_id`, `board_stage_id`, `estimated_hours`, `proposed_hours`), `:208` `assertSame($antes, $this->retrato())`, `:209` `assertSame(0, ...outcome='success'...whereIn('auditable_type', ['sprint','task'])->count())` | PASS |

#### SPR-02: negação de sprint auditada com a ação certa (AC 6)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 6: negação → `denied`, `auditable_type = sprint`, ação `created`/`updated`/`finished`/`reopened`/`deleted`/`viewed` | ação por ability | Escritas: `SprintPolicyTest.php:191-192` `assertSame(['sprint', $action], [$log?->auditable_type, $log?->action])`, com `$action` em `:83-87`. Tarefas: `:204-205` `['sprint','updated']`. Lista: `:163-164` `sole()` + `['sprint','viewed',$user->id]`. Unitário: `tests/Unit/AuditActionEnumTest.php:48-50`. **Precisão:** em `:191` e `:204` a linha é `latest('id')->first()`, sem conferir que *esta* requisição gravou uma `denied`. As quatro ações de tarefa seguidas esperam a mesma ação `updated`, então um 403 sem `denied` em "tirar", "horas", "aprovar" ou "recusar" lê a linha da requisição anterior e passa. O mutante `p-sprinttask-destroy-403-sem-denied` sobrevive, e o A5 também não o vê | ⚠️ GAP de precisão (Fix 3) |

#### SPR-03: ver sprints e capacidade (AC 1–4)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: admin, quem gere e dev alocado abrem `/projetos/{id}/sprints` → 200 com as sprints | 200 | `SprintPolicyTest.php:103-107` `assertOk()`, `component('projects/sprints')`, `where('selected.id', aberta)` (três papéis); dev em `:135-136` `assertOk()`, `:143` `has('selected.tasks', 1)` | PASS |
| AC 2: dev não alocado, gestor que não gere, comercial → 403 | 403 | `SprintPolicyTest.php:152-161` `assertForbidden()` (provider `quemNaoVe`, `:147-150`) | PASS |
| AC 3: quem gere recebe o painel de capacidade | capacidade presente | `SprintPolicyTest.php:108` `has('selected.capacity.developers')` | PASS |
| AC 4: dev alocado → `capacity = null`, nenhum `can` de escrita verdadeiro | `null`; todos `false` | `SprintPolicyTest.php:138` `where('selected.capacity', null)`, `:139` `selected.can` = os quatro `false`, `:140` `can.manage_tasks` `false`, `:141-142` `closing` e `proposal` `null` | PASS |

#### HRS-01: lançar e corrigir horas (AC 1–7)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: quem vê o projeto lança → `user_id` = logado | `user_id` do logado | `TimeEntryPolicyTest.php:64-77` (dev alocado, gestor, admin) `assertSame([$user->id, '1.50', '2026-10-07'], ...)`; `:79-86` `user_id` do pedido ignorado | PASS |
| AC 2: quem não vê → 403 e nada gravado | 403; nada novo | `TimeEntryPolicyTest.php:94-105` `assertForbidden()`, `assertSame(1, TimeEntry::count())` | PASS |
| AC 3: dono no projeto corrige e exclui | gravado | `TimeEntryPolicyTest.php:110-117` `assertSame('3.00', ...)`, `assertNull(find(...))` | PASS |
| AC 4: gestor e admin corrigem e excluem o de outro | gravado | `TimeEntryPolicyTest.php:125-139` (gestor responsável, alocado, admin) `assertSame([outroDev, '4.00'], ...)`, `assertNull(...)` | PASS |
| AC 5: dev corrige ou exclui o de outro → 403, sem mudança | 403; atributos iguais | `TimeEntryPolicyTest.php:141-144` → `:162-165` `assertForbidden()` ×2, `assertSame($antes, ->getAttributes())` | PASS |
| AC 6: dono que saiu do projeto → 403, sem mudança | 403 | `TimeEntryPolicyTest.php:146-151` (removido pelo `ProjectMemberService`) → `:162-165` | PASS |
| AC 7: comercial em `/minhas-horas` → 403 | 403 | `TimeEntryPolicyTest.php:176-177` `assertForbidden()` (e 200 para os outros, em `:172-174`) | PASS |

#### HRS-02: negação de lançamento auditada (AC 8)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 8: `denied`, `auditable_type = time_entry`, `created`/`updated`/`deleted`/`viewed` | as quatro | `created`: `TimeEntryPolicyTest.php:106-107` (`sole()`). `updated`, `deleted`: `:166` `assertSame(['updated','deleted'], ...pluck('action'))` (conta exata). `viewed`: `:178-179` (`sole()` + `user_id`) | PASS |

#### DEL-01: excluir sprint só aberta e sem tarefa (AC 1–3)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: quem gere exclui a aberta sem tarefa → apaga, linha `deleted` com o antes, **na mesma transação** | apagada; `deleted` com o antes; mesma transação | Service: `SprintServiceTest.php:328-333` `assertNull(Sprint::find(...))`, `deleted` `sole()` com `changes['name'] === ['Sprint 2', null]`. **Pela rota, como gestor: nenhum teste** (Fix 1). **Mesma transação: nenhum teste.** O mutante `p-sprint-delete-sem-transacao` sobrevive (Fix 5) | ❌ GAP (Fix 1, Fix 5) |
| AC 2: com tarefa → "A Sprint N tem tarefas: não dá para excluir.", sprint mantida | o texto literal | `SprintServiceTest.php:325` `assertSame('A Sprint 2 tem tarefas: não dá para excluir.', ...)`, o literal; a sprint segue existindo e é excluída depois em `:329` | PASS |
| AC 3: encerrada → "Só se exclui a sprint aberta.", sprint mantida | o texto literal | `SprintServiceTest.php:316` literal, `:334` `assertTrue(...whereKey($fechada->id)->exists())` | PASS |

#### DEL-02: excluir lançamento auditado (AC 4)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 4: quem pode corrigir exclui → apaga, `deleted` de `time_entry` com **tarefa, pessoa, data e horas** no antes, na mesma transação | os quatro campos no antes; mesma transação | `TimeEntryServiceTest.php:33` `assertNull(find)`, `:35` ações `['created','updated','deleted']`, `:37` `changes['worked_on'] === ['2026-10-07', null]`, `:38` `changes['task_id'] === [$task->id, null]`. **Pessoa (`user_id`) e horas no antes não são afirmadas:** o mutante `p-time-delete-antes-sem-pessoa-horas` sobrevive (Fix 4). **Mesma transação:** sem teste (Fix 5). A spec cita `tests/Feature/Projetos/TimeEntryTest.php`, que não existe: o teste é o `TimeEntryServiceTest.php` | ⚠️ GAP de precisão (Fix 4, Fix 5) |

#### Edge cases

| Edge case | Evidência | Resultado |
|---|---|---|
| Gestor perde o papel → negado na próxima requisição | `SprintPolicyTest.php:177-183` (`role_id` → dev, alocado; as dez escritas 403 com `denied` e nada muda) | PASS |
| Sprint de outro projeto → 404 antes da Policy | `SprintPolicyTest.php:212-219` `assertNotFound()` em `PUT` da sprint e `DELETE` da tarefa alheia (`scopeBindings`, `routes/web.php`); `TimeEntryControllerTest.php:126-131` para a tarefa da rota de horas. Não afirma ausência de `denied` (nota) | PASS |
| Lançamento inexistente → 404 antes da Policy | `TimeEntryPolicyTest.php:182-186` `assertNotFound()` + `assertSame(0, AuditLog::count())` | PASS |

### Sensor de mutação

Cópia descartável com `git worktree add /tmp/b5-verifier HEAD --detach`, removida no fim com `git worktree remove --force`. O `vendor/` foi montado assim: um symlink por pacote, mais cópias reais de `vendor/composer`, `vendor/autoload.php` e `vendor/bin`. Assim o autoload resolve para a cópia, conferido por `ReflectionClass::getFileName()` = `/private/tmp/b5-verifier/app/...`. O `node_modules` entrou por symlink. O arquivo de ambiente de teste não foi copiado: ele é versionado e veio no checkout.

Os testes rodaram por `php vendor/bin/phpunit <arquivos>`, uma suíte por vez, no MySQL `wcj-hub-mysql-1` (não caiu). Cada mutante foi aplicado por script com troca exata, que falha se o trecho não for único, e restaurado com `git checkout -- <arquivo>` na cópia. Base na cópia: 104 testes OK nas sete suítes + `AuditActionEnumTest` + `tests/Arch`, e 3 OK no `SprintAbertaConcorrenciaTest`.

A árvore real ficou com `git status --porcelain` vazio antes e depois; só este `validation.md` foi criado.

Conjuntos de teste por mutante:
- **Policy de sprint**: `SprintPolicyTest` + `SprintControllerTest`.
- **Policy de horas**: `TimeEntryPolicyTest` + `TimeEntryControllerTest`.
- **controllers e FormRequests**: só o teste de comportamento (`SprintPolicyTest` ou `TimeEntryPolicyTest`), sem o A5, para provar que o comportamento mata. O A5 foi rodado à parte em dois deles.
- **Service**: `SprintServiceTest` + `SprintControllerTest`, e o `SprintAbertaConcorrenciaTest` para as travas.

#### Mutantes pedidos

| # | Mutante | Resultado | Quem matou |
|---|---|---|---|
| s1 | `SprintPolicy::viewAny` → `true` | morto | `SprintPolicyTest::test_who_does_not_see_the_project_gets_403…` (dev, gestor) |
| s2 | `SprintPolicy::view` → `true` | **sobrevivente** (ability sem chamador) | nenhum: Fix 2 |
| s3 | `create` → `true` | morto | `test_allocated_developer_cannot_write…`, `test_project_manager_who_does_not_manage…` |
| s4 | `update` → `true` | morto | `test_allocated_developer_sees_sprints_without_capacity…` (`selected.can`), `…cannot_write…` |
| s5 | `close` → `true` | morto | idem s4 |
| s6 | `reopen` → `true` | morto | `…cannot_write…` |
| s7 | `delete` → `true` | morto | `…cannot_write…` |
| s8 | `manageTasks` → `true` | morto | `…sees_sprints_without_capacity…` (`can.manage_tasks`), `…cannot_write…` |
| s9 | `viewCapacity` → `true` | morto | `…sees_sprints_without_capacity…` (`selected.capacity` nulo) |
| s10 | `viewAny` → `manage` | morto | `…sees_sprints_without_capacity…` (dev recebe 403) |
| s11 | `view` → `manage` | **sobrevivente** (ability sem chamador) | nenhum: Fix 2 |
| s12 | `viewCapacity` → `view` | morto | `…sees_sprints_without_capacity…` |
| t1 | `TimeEntryPolicy::viewAny` → `true` | morto | `TimeEntryPolicyTest::test_my_hours_is_for_everyone_but_sales` |
| t2 | `create` sem a checagem de `view` | morto | `test_who_does_not_see_the_project_cannot_log_hours` (dev, gestor) |
| t3 | `corrects`: dono sem `can('view', $project)` | morto | `test_owner_who_left_the_project_cannot_correct_nor_delete_anymore` |
| t4 | `corrects`: qualquer usuário que vê | morto | `test_developer_cannot_correct_nor_delete_someone_elses_entry` |
| t5 | `corrects`: `manage` → `view` no gestor | morto | `test_developer_cannot_correct_nor_delete_someone_elses_entry` |
| c1 | sem `authorize('reopen')` (`SprintController.php:70`) | morto | `SprintPolicyTest …cannot_write…` (dev e gestor); também o A5 (`RotasAutorizadasTest`) |
| c2 | sem `authorize('delete')` (`SprintController.php:77`) | morto | `…cannot_write…` |
| c3 | sem `authorize('manageTasks')` no `SprintTaskController::destroy` (`:49`) | morto | `…cannot_write…` |
| c4 | idem no `approve` (`:61`) | morto | `…cannot_write…` |
| c5 | idem no `reject` (`:68`) | morto | `…cannot_write…` |
| c6 | sem `authorize('viewAny')` no `TimeEntryController::index` (`:35`) | morto | `test_my_hours_is_for_everyone_but_sales` |
| c7 | sem `authorize('delete')` no `TimeEntryController::destroy` (`:63`) | morto | `test_developer_cannot_correct…`, `test_owner_who_left…`; também o A5 |
| f1 | `StoreSprintRequest::abilityAndArguments` → `viewAny` | morto | `…cannot_write…` |
| f2 | `CloseSprintRequest` → `viewAny` | morto | `…cannot_write…` |
| f3 | `SprintTaskRequest` → `viewAny` | morto | `…cannot_write…` |
| f4 | `EstimateTaskRequest` → `viewAny` | morto | `…cannot_write…` |
| f5 | `StoreTimeEntryRequest` → `viewAny` | morto | `test_who_does_not_see_the_project_cannot_log_hours` |
| v1 | `lockProject` sem `lockForUpdate()` (`SprintService.php:239`) | morto | `SprintAbertaConcorrenciaTest::test_two_creations…`, `test_two_drags…` |
| v2 | sem `assertNoOtherOpen` (`SprintService.php:77`) | morto | `SprintServiceTest::test_creates_one_open_sprint_and_refuses_a_second`, `SprintControllerTest::test_create_refuses…`; e `test_two_creations…` na concorrência |
| v3 | `reopen` sem a checagem de `$open` (`SprintService.php:159`) | morto | `SprintServiceTest::test_reopen_only_without_another_open_sprint…`, `SprintControllerTest::test_reopen_with_another_open_sprint…`. Rodado também só contra a concorrência: lá passa, porque aquele teste prova a espera da trava, não a regra |
| v4 | `addTask` cria a nova mesmo com aberta (`if ($sprint === null \|\| $newSprint !== null)`, `SprintTaskService.php:41`) | morto | `test_proposal_sent_when_a_sprint_is_already_open_joins_it` |
| v4b | `addTask` sempre cria (`if (true)`) | morto | `test_adding_a_backlog_card…`, `test_support_card…` |
| v5 | `delete` sem a checagem de `closed_at` (`SprintService.php:179`) | morto | `test_delete_only_the_open_sprint_without_cards` |
| v6 | `delete` sem `tasks()->exists()` (`SprintService.php:182`) | morto | `test_delete_only_the_open_sprint_without_cards` |
| v7 | `TimeEntryService::delete` sem `audit->log` (`:74`) | morto | `TimeEntryServiceTest::test_create_update_and_delete_are_audited_with_the_before` |
| e1 | `forAbility` sem `close` | morto | `SprintPolicyTest …cannot_write…` e `AuditActionEnumTest` (data set `close`) |
| e2 | `forAbility` sem `reopen` | morto | idem (data set `reopen`) |
| e3 | `forAbility` sem `manageTasks` | morto | idem (data set `manageTasks`) |

#### Mutantes extras (direção oposta, precisão e trava da sprint)

| # | Mutante | Resultado | Quem matou |
|---|---|---|---|
| x1 | `SprintPolicy::viewAny` → `false` | morto | `test_who_manages_the_project_sees_capacity…` |
| x2 | `create` → `false` | morto | `SprintControllerTest::test_empty_project_proposes…`, `test_create_refuses…` |
| x3 | `update` → `false` | morto | `test_who_manages_the_project_writes_on_the_sprint` |
| x4 | `close` → `false` | morto | `test_close_over_http…`, `test_who_manages…sees_capacity…` |
| x5 | `reopen` → `false` | morto | `test_who_manages_the_project_writes_on_the_sprint` |
| x6 | **`delete` → `false`** (o gestor não exclui mais) | **sobrevivente** | nenhum: Fix 1 |
| x7 | `manageTasks` → `false` | morto | `test_who_manages_the_project_sees_capacity…` |
| x8 | `viewCapacity` → `false` | morto | `test_who_manages_the_project_sees_capacity…` |
| x9 | `TimeEntryPolicy::create` → `true` | morto | `test_who_does_not_see_the_project_cannot_log_hours` |
| x10 | `update` → `true` | morto | `test_developer_cannot_correct…`, `test_owner_who_left…` |
| x11 | `delete` → `true` | morto | idem |
| x12 | `corrects` sem `! isSales()` do dono | sobrevivente, **equivalente** | `ProjectPolicy::view` já devolve `false` para o Comercial (`ProjectPolicy.php:27-29`), então o `can('view')` da mesma linha nega igual |
| x13 | `StoreSprintRequest` sempre `create` (editar audita `created`) | morto | `…cannot_write…` (espera `updated` em editar) |
| x14 | sem `authorize('viewAny')` no `SprintController::index` | morto | `test_who_does_not_see_the_project_gets_403…` |
| x15 | `SprintService::delete` sem `audit->log` | morto | `test_delete_only_the_open_sprint_without_cards` |
| x16 | `lockSprint` sem travar o projeto (`SprintService.php:245`) | morto | `SprintAbertaConcorrenciaTest::test_reopen_and_create_at_the_same_time…` |
| x17 | **`SprintTaskController::destroy` com `abort_unless(can(...), 403)`** (403 sem `denied`) | **sobrevivente**, também no A5 | nenhum: Fix 3 |
| x18 | **`TimeEntryService::delete` com o antes sem `user_id` e `hours`** | **sobrevivente** | nenhum: Fix 4 |
| x19 | **`SprintService::delete` sem `DB::transaction`** | **sobrevivente** | nenhum: Fix 5 |

**Total**: 60 mutantes distintos (39 pedidos + 21 extras).
- 53 mortos.
- 1 equivalente (x12).
- 2 sobreviventes numa ability sem chamador (s2, s11). Não mudam comportamento hoje, mas a spec pede que morram.
- 4 sobreviventes de teste (x6, x17, x18, x19).

Dos 39 pedidos, 37 morreram e 2 sobreviveram (s2 e s11, os dois no `view(Sprint)`).

### Gate (árvore real, HEAD `7667d75`, sem edição)

| Comando | Resultado |
|---|---|
| `composer lint` (`pint --test`) | passed |
| `php artisan test` (MySQL 8, `wcj-hub-mysql-1`) | **535 passed (4243 assertions)**, 68,9 s, 1ª execução |
| `npm run typecheck` | exit 0 |
| `npm run lint` | exit 0 |
| Testes antes (`f5d15f4`, `phpunit --list-tests`) → depois | 427 → 535 (+108). Nenhum teste apagado (`git diff --diff-filter=D -- tests` vazio) |

Não rodei o Playwright (`e2e/sprints-e-horas.spec.ts`), o Storybook nem o build.

### Fix necessários

Todos Minor e só de teste.

1. **Fix 1 (SPR-01 AC 1, DEL-01 AC 1, mata x6):** em `SprintPolicyTest.php`, um teste com o provider `quemGere` que crie uma sprint aberta sem tarefa num projeto sem outra aberta. Ou encerre a `aberta` do `setUp` antes; a `aberta` tem tarefa, então use uma vazia. O teste envia `DELETE /projetos/{id}/sprints/{sprint}` e afirma `assertRedirect(.../sprints)`, `assertSessionHas('success', 'Sprint N excluída.')`, a sprint fora do banco e a linha `deleted` de `sprint`. Junto, cobrir *criar* para admin e gestor alocado: hoje só o gestor responsável cria pela rota.
2. **Fix 2 (critério de sucesso da spec, mata s2 e s11):** `SprintPolicy::view(Sprint)` não é chamada em lugar nenhum. Opções:
   - Um teste direto da Policy, com `Gate::forUser($dev)->allows('view', $sprint)` verdadeiro para o dev alocado e falso para o dev não alocado, o gestor que não gere e o Comercial.
   - Tirar a ability até ter chamador (B3/B8). Mudança de Policy pede aprovação do Lucas Scholz.
3. **Fix 3 (SPR-02 AC 6, mata x17):** em `SprintPolicyTest::assertDeniedForAll` (`:189-206`), contar as linhas `denied` antes e depois de cada requisição e afirmar +1. Ou filtrar pela linha nova, `where('id', '>', $ultimoId)->sole()`. Afirmar também `user_id = $user->id`.
4. **Fix 4 (DEL-02 AC 4, mata x18):** em `TimeEntryServiceTest.php`, depois da `:38`, afirmar `$logs[2]->changes['user_id'] === [$dev->id, null]` e `$logs[2]->changes['hours'] === ['3.00', null]`. Junto, corrigir na spec o nome do teste independente (`TimeEntryTest.php` → `TimeEntryServiceTest.php`).
5. **Fix 5 (DEL-01 AC 1 e DEL-02 AC 4, "na mesma transação", mata x19):** um teste sem `RefreshDatabase` (`DatabaseTruncation` + `UsaBancoDeVerdade`), no molde do `ReworkReasonTransacaoTest` do B2. Ele injeta um `AuditService` que lança em `log()` no `SprintService` e no `TimeEntryService` e afirma pelo banco que a sprint e o lançamento continuam lá depois da exceção.

### Notas (não bloqueiam)

- `SprintPolicyTest.php:212-219` e `TimeEntryControllerTest.php:126-131` afirmam o 404, mas não que nenhuma linha `denied` foi gravada. O lançamento inexistente afirma, em `TimeEntryPolicyTest.php:185`.
- O A5 aceita `abort_unless($request->user()->can(...), 403)` como autorização. Ele não exige o `authorize()` que grava a `denied`, então essa forma passa sem auditoria. Hoje nenhum controller do B5 usa essa forma.
