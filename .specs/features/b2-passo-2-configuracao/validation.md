# B2 passo 2 (ReworkReasonPolicy e `manage_lists`) Validation (rodada 2, focada nos 5 Fix da rodada 1)

## Validation: PASS

**Result**: PASS na rodada 2. Os cinco gaps da rodada 1 (todos Minor, só de teste) estão fechados pelo `a79bd8b`, que só mexe em teste e e2e. Os quatro sobreviventes da rodada 1 agora morrem numa cópia descartável:
- **x10** (sem a auditoria `reactivated`) morre em `ReworkReasonTest.php:128`.
- **x13** (texto do `ALREADY_USED` trocado) morre em `:132`.
- **x15** (`createReworkReason` sem `DB::transaction`) morre em `ReworkReasonTransacaoTest.php:67`; o x15b, o mesmo no `updateReworkReason`, morre em `:81`.
- **x16** (item *Listas* sempre visível) morre no Playwright, em `e2e/configuracao-projeto.spec.ts:219` ("unexpected value 1"), contra um servidor da cópia mutada na porta 8001.

Os dois edge cases da spec têm teste e passam. O código de produção não mudou desde `9003cec`, então os 29 mutantes mortos e o equivalente (e) da rodada 1 continuam valendo.

Gate verde na árvore real (`a79bd8b`):
- `composer lint`: passou.
- `php artisan test`: **427 passed (3701 assertions)**.
- `npm run typecheck`: ok.
- `npm run lint`: ok.

O PASS não encerra a feature (`.ai/core/especificacao.md` §5 item 9).

**Date (rodada 2)**: 2026-10-15
**Spec (rodada 2)**: `.specs/features/b2-passo-2-configuracao/spec.md`, aprovada por Lucas Scholz em 15/10/2026, sem mudança desde a rodada 1
**Diff range (rodada 2)**: `9003cec..a79bd8b`, um commit, só teste (5 arquivos, +153/−7):
- `tests/Feature/Projetos/ReworkReasonTest.php` (+34/−2)
- `tests/Feature/Projetos/ReworkReasonTransacaoTest.php` (novo, +83)
- `e2e/smoke.spec.ts` (+3/−2)
- `e2e/configuracao-projeto.spec.ts` (+26)
- `e2e/helpers/dados.ts` (+8/−4)
**Verifier (rodada 2)**: subagente independente (autor ≠ verifier), escopo focado nos Fix 1 a 5 da rodada 1

### Fix da rodada 1 → evidência (rodada 2)

| Fix | Critério | Evidência (`arquivo:linha` + asserção) | Resultado |
|---|---|---|---|
| 1 | LST-02 AC 6, linha `success` de reativar | `ReworkReasonTest.php:128` `assertDatabaseHas('audit_logs', ['action' => 'reactivated', 'outcome' => 'success', 'auditable_type' => 'rework_reason', 'auditable_id' => $usado->id])` | PASS (x10 morto em `:128`) |
| 2 | LST-02 AC 6, "na mesma transação" | `ReworkReasonTransacaoTest.php:26` `DatabaseTruncation` + `UsaBancoDeVerdade` (sem `RefreshDatabase`), `:58` `transactionLevel() === 0`. Um `AuditService` que lança em `log()` é injetado no `ListService` (`:44-53`). Criar: `:67` `assertDatabaseMissing('rework_reasons', ['label' => 'Sem auditoria'])`. Editar: `:81` `assertDatabaseHas(... 'label' => 'Ajuste visual', 'requires_text' => false)`. Rodou no MySQL 8, não foi pulado: 25 testes OK, sem aviso de skip | PASS (x15 morto em `:67`, x15b em `:81`) |
| 3 | LST-03 AC 8, texto exato | `ReworkReasonTest.php:131-132` `assertSessionHas('error', 'Motivo já usado em alguma volta para desenvolvimento: desative em vez de excluir')`, o literal da spec | PASS (x13 morto em `:132`) |
| 4 | LST-04 AC 3, item escondido no menu | Some para quem não é admin: `e2e/configuracao-projeto.spec.ts:202-224`, um dev criado pelo próprio spec entra pelo login; `:218` Projetos visível, `:219` `menu.getByRole('link', { name: 'Listas' })` `toHaveCount(0)`, `:221-222` `GET /admin/listas` responde 403. Aparece para o admin: `e2e/smoke.spec.ts:34-36`, 'Listas' na lista de links visíveis, e `:12` `/admin/listas` no laço do axe | PASS (x16 morto em `configuracao-projeto.spec.ts:219`). O lado do admin no smoke foi conferido só por leitura: não rodei, porque precisa da sessão do admin de e2e (`e2e/.auth/admin.json` da árvore real, compartilhada com o servidor da 8000) |
| 5a | Edge case: admin rebaixado | `ReworkReasonTest.php:165` 200 antes; `:167` papel trocado para gestor; `:170-171` `assertForbidden()` em `GET /admin/listas` e no criar; `:173-174` linhas `denied` de `viewed` e `created`; `:175` nada criado | PASS |
| 5b | Edge case: 404 antes da Policy | `ReworkReasonTest.php:183-185` `assertNotFound()` em `PUT`, `DELETE` e `POST …/deactivate` do id 999999, como gestor; `:187` `assertSame(0, AuditLog::…where('user_id', $gestor->id)->count())`, sem linha `denied` | PASS |

Notas, nenhuma bloqueia:
- O 5a usa `actingAs` com o usuário relido do banco (`:168`). Ele prova que a Policy decide pelo papel atual. A releitura pela sessão em cada requisição é do `SessionGuard` do framework.
- No `ReworkReasonTransacaoTest.php:62` e `:76`, o `$this->fail(...)` fica dentro do `try` com `catch (RuntimeException)`. A exceção do PHPUnit é `RuntimeException`, então o `fail` cai no `catch`. O teste ainda reprova (o `assertSame` da mensagem falha), mas a mensagem de erro sai trocada.
- `e2e/helpers/dados.ts` passou a usar o e-mail `<slug>@e2e.test`, sem `Date.now()`. Os dois chamadores usam nomes com o `PREFIX` da execução (`configuracao-projeto.spec.ts:38` e `:207`), então não colidem entre execuções.

### Sensor de mutação (rodada 2)

Cópia descartável `git worktree add --detach /tmp/verifier-b2 a79bd8b`, removida no fim. O `vendor/` foi montado como na rodada 1: um symlink por pacote, mais cópias reais de `vendor/composer`, `vendor/autoload.php` e `vendor/bin`. Testes por `php vendor/bin/phpunit`. Conferido: `ListService` carregado de `/private/tmp/verifier-b2/app/...`. Base na cópia: **25 testes, 194 asserções, OK**. Comando PHP por mutante: `php vendor/bin/phpunit tests/Feature/Projetos/ReworkReasonTest.php tests/Feature/Projetos/ReworkReasonTransacaoTest.php tests/Feature/Nucleo/AbilitiesTest.php tests/Arch`.

Para o x16, montei um servidor próprio na cópia:
- `npm run build` na cópia.
- `APP_ENV=e2e php artisan serve --host=127.0.0.1 --port=8001 --no-reload`, com as variáveis do `.env.e2e.example` versionado (que se declara sem segredo), menos `APP_KEY` (gerada na hora para esse servidor), senha do admin, `APP_URL` e `E2E_BASE_URL`. Nenhum `.env*` foi criado, copiado ou lido além do exemplo. Banco `wcj_hub_e2e`, o compartilhado.
- `E2E_BASE_URL=http://127.0.0.1:8001 npx playwright test e2e/configuracao-projeto.spec.ts -g "quem não é" --no-deps`, rodado na cópia. O `--no-deps` pula o setup do admin, que o describe não usa (`storageState` vazio).

Base sem mutação: **1 passed**, com as requisições no log do servidor da 8001. Depois do mutante, o build foi refeito sem ele, o servidor desligado e a cópia removida. A porta 8000 (servidor do coordenador) não foi tocada, e a árvore real ficou só com este `validation.md` como não versionado.

| # | Mutante | Resultado | Quem matou |
|---|---|---|---|
| x10 | sem `audit->log(REACTIVATED, …)` (`ListService.php:98`) | morto | `ReworkReasonTest.php:128` |
| x13 | texto do `ALREADY_USED` → `'Motivo em uso.'` (`ListService.php:31`) | morto | `ReworkReasonTest.php:132` |
| x15 | `createReworkReason` sem `DB::transaction` (`ListService.php:54`) | morto | `ReworkReasonTransacaoTest.php:67` |
| x15b | `updateReworkReason` sem `DB::transaction` (`ListService.php:66`) | morto | `ReworkReasonTransacaoTest.php:81` |
| x16 | item *Listas* com `visible: () => true` (`w-app-sidebar-organism.tsx:71`), com rebuild | morto | `e2e/configuracao-projeto.spec.ts:219`, `toHaveCount(0)`: "unexpected value 1" |

Somando as duas rodadas: 35 mutantes, 34 mortos e 1 equivalente (e, sem `Gate::policy`: a Policy é achada pela convenção). Nenhum sobrevivente.

### Gate (rodada 2, árvore real, HEAD `a79bd8b`, sem edição)

| Comando | Resultado |
|---|---|
| `composer lint` (`pint --test`) | passed |
| `php artisan test` (MySQL 8, `wcj-hub-mysql-1`) | **427 passed (3701 assertions)**, 51,4 s, 1ª execução (eram 423: +2 do `ReworkReasonTransacaoTest`, +2 edge cases) |
| `npm run typecheck` | exit 0 |
| `npm run lint` | exit 0 |

Não rodei a suíte e2e inteira, o Storybook nem o build da árvore real. O build e o Playwright só rodaram na cópia, para o x16.

### Fix necessários (rodada 2)

Nenhum.

---

## Rodada 1 (histórico): FAIL

**Result (rodada 1)**: FAIL na rodada 1, por **cinco gaps Minor, todos só de teste**. O núcleo de permissão está provado: os seis `return $user->isAdmin();` da `ReworkReasonPolicy` trocados por `true` (um por vez) morreram, os quatro `authorize()` tirados do controller morreram (no `ReworkReasonTest` e no A5), a edição conferindo `create` em vez de `update` morreu (falta a linha `denied` de `updated`), `manage_lists` vazando para o gestor morreu no `AbilitiesTest`, e as três recusas do "Outro" e as duas trocas do Presenter morreram. O mutante "sem `Gate::policy(ReworkReason…)`" sobreviveu, mas é **equivalente**: o Gate do Laravel acha `App\Domains\Projetos\Policies\ReworkReasonPolicy` pela convenção `Models` → `Policies` (sonda descartável: sem a linha, `Gate::policies()` não tem a classe e `Gate::getPolicyFor(ReworkReason::class)` ainda devolve a `ReworkReasonPolicy`). O que reprova: (1) a linha `success` de `reactivated` (AC 6) não é afirmada, e o mutante que tira essa auditoria sobrevive; (2) "na mesma transação" (AC 6) não tem prova: tirar a `DB::transaction` do `createReworkReason` sobrevive; (3) a mensagem do AC 8 é afirmada pela constante, não pelo texto da spec: trocar o texto da constante sobrevive; (4) o item *Listas* escondido no menu (LST-04 AC 3) não tem teste nenhum (só a ability e o 403); (5) os dois edge cases da spec (rebaixado, 404 antes da Policy) não têm teste. Gate verde na árvore real: `composer lint` ok, `php artisan test` **423 passed (3685 assertions)**, `npm run typecheck` ok, `npm run lint` ok.

**Date (rodada 1)**: 2026-10-15
**Spec (rodada 1)**: `.specs/features/b2-passo-2-configuracao/spec.md` (LST-01 a LST-04), aprovada por Lucas Scholz em 15/10/2026
**Diff range (rodada 1)**: `23749bd..9003cec`, escopo restrito a: `app/Domains/Projetos/Policies/ReworkReasonPolicy.php`, `app/Providers/AppServiceProvider.php` (registro), `app/Http/Middleware/HandleInertiaRequests.php` (`manage_lists`), `resources/js/types/index.d.ts`, `resources/js/components/organisms/w-app-sidebar-organism/w-app-sidebar-organism.tsx`, `app/Domains/Projetos/Controllers/ReworkReasonController.php`, `app/Domains/Projetos/Requests/StoreReworkReasonRequest.php`, `app/Domains/Projetos/Services/ListService.php`, `app/Domains/Projetos/Services/ProjectSettingsPresenter.php` (`reworkReason`), `routes/web.php` (`admin/listas`, `admin/rework-reasons*`); testes `tests/Feature/Projetos/ReworkReasonTest.php` e `tests/Feature/Nucleo/AbilitiesTest.php`. Pessoas, etapas e áreas (trilho leve) ficaram fora.
**Verifier (rodada 1)**: subagente independente (autor ≠ verifier), sem acesso ao raciocínio de quem implementou

### Critérios de aceite (rodada 1)

#### LST-01: Só o Administrador mantém os motivos (AC 1–4)

| Critério | Valor da spec | Evidência (`arquivo:linha` + asserção) | Resultado |
|---|---|---|---|
| AC 1: admin abre `/admin/listas` → 200, o "Outro" por último | 200; "Outro" na última posição | código `ReworkReasonController.php:28-37`, `ListService.php:45`; teste `ReworkReasonTest.php:41` `assertOk()`, `:45` `pluck('label') === [..., 'Outro']`, `:46` `reworkReasons.4.fixed === true` | PASS |
| AC 2: gestor, dev e comercial abrem `/admin/listas` → 403 | 403 | código `ReworkReasonController.php:28`, `ReworkReasonPolicy.php:16`; teste `ReworkReasonTest.php:141-142` (os três papéis) `assertForbidden()`; `AbilitiesTest.php:69,73` `assertStatus(403)` por papel | PASS |
| AC 3: não admin cria, edita, desativa, reativa, exclui → 403 | 403 nas cinco | código `StoreReworkReasonRequest.php:17-22` (+ `AuthorizesBeforeValidation`), `ReworkReasonController.php:54,61,68`, Policy `:19-42`; teste `ReworkReasonTest.php:143-147` `assertForbidden()` nas cinco escritas, para os três papéis | PASS |
| AC 4: escrita negada não muda nada; nenhuma linha `success` de `rework_reason` | nome, pede texto, situação iguais; 0 linhas `success` | teste `ReworkReasonTest.php:139,154` `assertSame($antes, ...['id','label','requires_text','deactivated_at'])`; `:155` `assertSame(0, ...outcome='success'...auditable_type='rework_reason'->count())` | PASS |

#### LST-02: Negação e sucesso auditados (AC 5–6)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 5: negação das seis ações → `denied`, `auditable_type = rework_reason`, ação `viewed`/`created`/`updated`/`deactivated`/`reactivated`/`deleted` | as seis ações, `outcome = denied` | código `App\Http\Concerns\AuditsDeniedAbilities` via `authorize()` do Controller base e do FormRequest; teste `ReworkReasonTest.php:149-150` `assertDatabaseHas('audit_logs', [user_id, action => $acao, outcome => 'denied', auditable_type => 'rework_reason'])` para as seis, por papel | PASS |
| AC 6: admin cria, edita, desativa, reativa, exclui → linha `success` correspondente, **na mesma transação** | uma linha por ação, mesma transação | criar `ReworkReasonTest.php:62`; editar `:72`; desativar `:116`; excluir `:133` (`assertDatabaseHas(... action ...)`). **Reativar: `:125-126` só afirma `isActive()`, sem a linha `reactivated`** (mutante `x-audit-reactivated` sobrevive). **Mesma transação: nenhum teste** (mutante `x-create-sem-transacao` sobrevive) | ❌ GAP (Fix 1, Fix 2) |

#### LST-03: Regras do "Outro" e do já usado (AC 7–8)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 7: desativar, excluir o "Outro" ou desligar o pede texto → recusa com a mensagem do `ListService`, "Outro" sem mudança | mensagem do `ListService` (a spec não fixa o texto) | código `ListService.php:68-70,86-88,105-107`; teste `ReworkReasonTest.php:92` `assertSessionHas('error', ListService::OTHER_REQUIRES_TEXT)` + `:93` `requires_text` continua `true`; `:95` e `:96` `assertSessionHas('error', ListService::OTHER_LOCKED)`; `:97` ativo; `:98` `assertModelExists` | PASS |
| AC 8: excluir motivo já usado → "Motivo já usado em alguma volta para desenvolvimento: desative em vez de excluir", motivo mantido | o texto literal da spec | código `ListService.php:31,113-115` (o texto da constante é idêntico ao da spec); teste `ReworkReasonTest.php:128` `assertSessionHas('error', ListService::ALREADY_USED)` + `:129` `assertModelExists`. O teste compara com a constante, não com o texto da spec: trocar o texto sobrevive (`x-already-used-text`) | ⚠️ GAP de precisão (Fix 3) |

#### LST-04: Ability `manage_lists` e o item do menu (AC 1–3)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: admin → `auth.user.abilities.manage_lists = true` | `true` | código `HandleInertiaRequests.php:80`; teste `AbilitiesTest.php:28,55` `where('auth.user.abilities', $esperadas)` (igualdade do mapa inteiro) | PASS |
| AC 2: gestor, dev, comercial → `false` | `false` | `AbilitiesTest.php:33,38,43,55` | PASS |
| AC 3: `manage_lists` falso esconde o item *Listas*; a rota continua 403 | item escondido; 403 | 403: `AbilitiesTest.php:69,73`, `ReworkReasonTest.php:142`. Esconder: código `w-app-sidebar-organism.tsx:71` (`visible: (a) => a.manage_lists`) e `:139` (filtro), **sem teste**: nenhum e2e, story ou teste de unidade afirma que *Listas* aparece para o admin e some para os outros (`e2e/smoke.spec.ts:29-36` confere só os quatro itens do B1) | ❌ GAP (Fix 4) |

#### Edge cases

| Edge case | Evidência | Resultado |
|---|---|---|
| Admin rebaixado é negado na próxima requisição | código: a Policy lê `isAdmin()` do usuário da requisição (`ReworkReasonPolicy.php:14-42`), sem cache; **sem teste** em `ReworkReasonTest.php` (o padrão existe em `ProjectPermissionTest.php:142-147`) | ❌ GAP (Fix 5) |
| Motivo inexistente → 404 antes da Policy | código: binding implícito `{rework_reason}` → `ReworkReason $reworkReason` (`routes/web.php:95-98`); **sem teste** | ❌ GAP (Fix 5) |

### Sensor de mutação (rodada 1)

Cópia descartável `git worktree add /tmp/verifier-b2 9003cec` (removida no fim com `git worktree remove --force`). Observação de método: com `vendor` como symlink inteiro, o autoload do Composer resolve `__DIR__` para a worktree real e os testes rodam o código **não mutado** (a primeira rodada deu todos os mutantes como sobreviventes; descartada). Correção: na cópia, `vendor/` virou diretório com symlink para cada pacote, mais cópias reais de `vendor/composer/`, `vendor/autoload.php` e `vendor/bin/`, e os testes rodaram por `php vendor/bin/phpunit` (o `php artisan test` chama o `phpunit` pelo caminho real e carrega o autoload da worktree real). Conferido por `ReflectionClass::getFileName()` = `/private/tmp/verifier-b2/app/...`. Base na cópia: **21 testes, 178 asserções, OK**. Comando por mutante: `php vendor/bin/phpunit tests/Feature/Projetos/ReworkReasonTest.php tests/Feature/Nucleo/AbilitiesTest.php tests/Arch`; arquivo restaurado com `git checkout` depois de cada um. A árvore real ficou com `git status --porcelain` vazio antes e depois.

| # | Mutante | Resultado | Quem matou |
|---|---|---|---|
| a1 | Policy `viewAny` → `return true;` | morto | `ReworkReasonTest.php:142`; `AbilitiesTest.php:55` (3 papéis) e `:73` (3 papéis) |
| a2 | Policy `create` → `return true;` | morto | `ReworkReasonTest.php:143` |
| a3 | Policy `update` → `return true;` | morto | `ReworkReasonTest.php:144` |
| a4 | Policy `deactivate` → `return true;` | morto | `ReworkReasonTest.php:145` |
| a5 | Policy `reactivate` → `return true;` | morto | `ReworkReasonTest.php:146` |
| a6 | Policy `delete` → `return true;` | morto | `ReworkReasonTest.php:147` |
| b1 | sem `authorize('viewAny')` no `index` | morto | `ReworkReasonTest.php:142`; `AbilitiesTest.php:73` (3 papéis); `RotasAutorizadasTest.php:104` |
| b2 | sem `authorize('deactivate')` | morto | `ReworkReasonTest.php:145`; `RotasAutorizadasTest.php:104` |
| b3 | sem `authorize('reactivate')` | morto | `ReworkReasonTest.php:146`; `RotasAutorizadasTest.php:104` |
| b4 | sem `authorize('delete')` no `destroy` | morto | `ReworkReasonTest.php:147`; `RotasAutorizadasTest.php:104` |
| c | `abilityAndArguments` sempre `['create', ReworkReason::class]` | morto | `ReworkReasonTest.php:150` (falta a linha `denied` de `updated`) |
| d | `manage_lists` = `isProjectManager() \|\| isAdmin()` | morto | `AbilitiesTest.php:55` (gestor) |
| e | sem `Gate::policy(ReworkReason::class, ReworkReasonPolicy::class)` | sobrevivente, **equivalente** | o Gate acha a Policy pela convenção `Models` → `Policies` (sonda: `getPolicyFor(ReworkReason::class)` = `ReworkReasonPolicy` sem o registro). Não é gap de teste |
| f1 | `ListService::deactivate` sem a recusa do "Outro" | morto | `ReworkReasonTest.php:95` |
| f2 | `ListService::delete` sem a recusa do "Outro" | morto | `ReworkReasonTest.php:96` |
| f3 | `ListService::update` sem a recusa do pede texto do "Outro" (`if (false)`) | morto | `ReworkReasonTest.php:92` |
| g | Presenter `can.delete` sem `! $other` | morto | `ReworkReasonTest.php:49` |
| g2 | Presenter `can.deactivate` sem `! $other` | morto | `ReworkReasonTest.php:48` |
| x1–x6 | cada `return $user->isAdmin();` → `return false;` (o admin perde) | os 6 mortos | `:41` (viewAny), `:58` (create), `:70`/`:87` (update), `:114`/`:95` (deactivate), `:125` (reactivate), `:128`/`:50` (delete) |
| x7 | sem a auditoria `created` | morto | `ReworkReasonTest.php:62` |
| x8 | sem a auditoria `updated` | morto | `ReworkReasonTest.php:72` |
| x9 | sem a auditoria `deactivated` | morto | `ReworkReasonTest.php:116` |
| x10 | **sem a auditoria `reactivated`** (`ListService.php:98`) | **sobrevivente** | nenhum: Fix 1 |
| x11 | sem a auditoria `deleted` | morto | `ReworkReasonTest.php:133` |
| x12 | já usado não recusa (`if (! $deleted)` → `if (false)`) | morto | `ReworkReasonTest.php:128` |
| x13 | **texto do `ALREADY_USED` trocado** (`ListService.php:31`) | **sobrevivente** | nenhum: Fix 3 |
| x14 | sem o `orderByRaw` do "Outro" por último | morto | `ReworkReasonTest.php:45` |
| x15 | **`createReworkReason` sem `DB::transaction`** (`ListService.php:54`) | **sobrevivente** | nenhum: Fix 2 |
| x16 | sidebar `visible: () => true` no item *Listas* (`w-app-sidebar-organism.tsx:71`) | **sobrevivente por inspeção** (não rodei Playwright nem Storybook, por instrução; nenhum teste do repositório afirma o item) | nenhum: Fix 4 |

Total: 34 mutantes; 29 mortos, 1 equivalente (e), 4 sobreviventes (x10, x13, x15, x16). **Nenhum sobrevivente nas regras de permissão** (Policy, `authorize()`, FormRequest, ability): o critério de sucesso da spec sobre mutação de permissão está cumprido; os sobreviventes são de auditoria, mensagem, transação e menu.

### Gate (árvore real, HEAD `9003cec`, sem edição) (rodada 1)

| Comando | Resultado |
|---|---|
| `composer lint` (`pint --test`) | passed, exit 0 |
| `php artisan test` (MySQL 8, `wcj-hub-mysql-1`) | **423 passed (3685 assertions)**, 67,6 s, 1ª execução |
| `npm run typecheck` | exit 0 |
| `npm run lint` | exit 0 |

Não rodei build, Storybook nem Playwright (fora do pedido).

### Fix necessários (rodada 1)

Todos Minor e só de teste; nenhum muda código de produção.

1. **Fix 1 (AC 6, reativar):** em `tests/Feature/Projetos/ReworkReasonTest.php`, depois da `:126`, afirmar `assertDatabaseHas('audit_logs', ['action' => 'reactivated', 'outcome' => 'success', 'auditable_type' => 'rework_reason', 'auditable_id' => $usado->id])`. Mata o x10.
2. **Fix 2 (AC 6, mesma transação):** teste sem `RefreshDatabase` (`DatabaseTruncation` + banco de verdade, como o `tests/Feature/Nucleo/SetActiveLockedTransacaoTest.php` do B1) que faz o `AuditService::log` lançar na criação (e na edição) do motivo e afirma pelo banco que nenhum motivo novo ficou gravado (e que a edição não ficou). Mata o x15. Desativar, reativar e excluir já passam pelo `setActiveLocked`/`deleteIfUnused`, provados no B1.
3. **Fix 3 (AC 8, texto exato):** em `ReworkReasonTest.php:128`, comparar com o literal da spec, `'Motivo já usado em alguma volta para desenvolvimento: desative em vez de excluir'`, em vez de `ListService::ALREADY_USED`. Mata o x13.
4. **Fix 4 (LST-04 AC 3, menu):** prova de que *Listas* aparece para o Administrador e some quando `manage_lists` é falso. Opções: no `e2e/smoke.spec.ts:34`, incluir `'Listas'` na lista do admin e um teste com um usuário não admin afirmando `toHaveCount(0)` para o link *Listas*; ou um teste que renderize o `WAppSidebarOrganism` com `manage_lists` falso e verdadeiro. Mata o x16.
5. **Fix 5 (edge cases):** em `ReworkReasonTest.php`, (a) admin que vira gestor (`role_id` atualizado) recebe 403 em `GET /admin/listas` e numa escrita, com a linha `denied`; (b) `PUT`/`DELETE /admin/rework-reasons/999999` como não admin responde 404 (não 403) e não grava `denied`.
