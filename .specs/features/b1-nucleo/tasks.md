# B1 Núcleo Tasks

## Execution Protocol (MANDATORY -- do not skip)

Implement these tasks with the `tlc-spec-driven` skill: **activate it by name and follow its Execute flow and Critical Rules.** Do not search for skill files by filesystem path. The skill is the source of truth for the full flow (per-task cycle, sub-agent delegation, adequacy review, Verifier, discrimination sensor).

**If the skill cannot be activated, STOP and tell the user - do not proceed without it.**

---

**Design**: `.specs/features/b1-nucleo/design.md`
**Status**: Approved (Cauã, 06/10/2026)

---

## Test Coverage Matrix

> Generated from codebase, project guidelines, and spec - confirm before Execute. Guidelines found: `AGENTS.md` (Testes técnicos, Trilhos), `.ai/core/testes.md`, `.ai/core/especificacao.md` §6 (critérios de piso), `.ai/stacks/ui.md` §8, `docs/plano-de-desenvolvimento.md` §3 passo 6, `phpunit.xml`, `playwright.config.ts`.

| Code Layer | Required Test Type | Coverage Expectation | Location Pattern | Run Command |
| ---------- | ------------------ | -------------------- | ---------------- | ----------- |
| Service (regra de negócio) | integration (PHPUnit em MySQL 8) | Todos os ramos; 1:1 com os AC da spec; todo edge case listado; auditoria conferida em toda escrita | `tests/Feature/Nucleo/*ServiceTest.php` | `php artisan test --filter=Nucleo` |
| Policy | integration | Cada ability × cada papel (admin, gestor que gere, gestor que não gere, dev); negação grava `denied` | `tests/Feature/Nucleo/*PolicyTest.php` | `php artisan test --filter=Nucleo` |
| Controller / rota | integration (HTTP) | Toda rota do bloco: caminho feliz + 403 com "nada muda" (piso §6) + erro de validação | `tests/Feature/Nucleo/*ControllerTest.php` | `php artisan test --filter=Nucleo` |
| Regra de validação, função pura | unit | Todos os ramos, valores-limite e o exemplo oficial | `tests/Unit/*Test.php` | `php artisan test --testsuite=Unit` |
| Página React | e2e (Playwright) | Smoke com axe nos dois temas para cada tela nova; o fluxo crítico do bloco ponta a ponta | `e2e/*.spec.ts` | `npx playwright test` |
| Arquitetura | arch (já existe) | A1–A7, A9 e A5 verdes; dívida só diminui | `tests/Arch/*` | `php artisan test --filter=Arch` |
| ADR, docs, `.specs` | none | - (conferidor de specs só) | `docs/adr/*`, `.specs/*` | `node .ai/skills/conferir-specs.mjs` |

## Gate Check Commands

> Generated from codebase - confirm before Execute.

| Gate Level | When to Use | Command |
| ---------- | ----------- | ------- |
| Quick | Tarefa só de backend com testes PHPUnit | `composer lint && php artisan test` |
| Full | Tarefa que toca rota ou tela | `composer lint && php artisan test && npm run typecheck && npm run lint` |
| Build | Fim de fase e tarefas de tela | `composer lint && php artisan test && npm run typecheck && npm run lint && npm run build && npm run test:guard && npx playwright test` (servidor e2e numa porta livre, `E2E_BASE_URL`) |

---

## Execution Plan

Phases are ordered and run sequentially - each phase completes before the next begins, and tasks within a phase execute in order.

### Phase 1: Fundação (auditoria, CNPJ, contrato com Projetos)

```
T1 → T2 → T3
T4
T5
```

### Phase 2: Usuários

```
T6 → T7 → T8 → T9
```

### Phase 3: Clientes

```
T10 → T11 → T12 → T13 → T14
```

### Phase 4: Contatos

```
T15 → T16 → T17 → T18 → T19
```

### Phase 5: Auditoria (tela)

```
T20 → T21 → T22
```

### Phase 6: Integração

```
T23 → T24
```

---

## Task Breakdown

### Phase 1: Fundação

### T1: Ações de auditoria novas e o mapa ability → ação

**What**: `AuditActionEnum` ganha `DEACTIVATED`, `REACTIVATED`, `ANONYMIZED`, `VIEWED`, os rótulos em português e `forAbility(string)`.
**Where**: `app/Platform/Enums/AuditActionEnum.php`
**Depends on**: None
**Reuses**: o enum existente
**Requirement**: NUC-11

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Casos novos com rótulo (Desativado, Reativado, Anonimizado, Consultado)
- [x] `forAbility` cobre create, update, delete, deactivate, reactivate, anonymize, resendInvite e leitura (viewAny, view)
- [x] Teste unitário de cada mapeamento em `tests/Unit/AuditActionEnumTest.php`
- [x] Gate check passes: `composer lint && php artisan test`

**Tests**: unit
**Gate**: quick

**Commit**: `feat(auditoria): ações de desativar, reativar, anonimizar e negação de leitura`

---

### T2: AuditService grava negação e limpa dado pessoal

**What**: `AuditService::denied()` (outcome `denied`, ação pretendida, alvo por morph map, id nulo quando o alvo é a classe) e `scrubPersonalData()` (remove chaves de `changes` das linhas de um registro).
**Where**: `app/Platform/Services/AuditService.php`
**Depends on**: T1
**Reuses**: `log()` existente, `NEVER_AUDITED`
**Requirement**: NUC-11

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] `denied` grava `user_id`, `action`, `outcome = denied`, `auditable_type` e `auditable_id` (nulo para classe)
- [x] `scrubPersonalData` só toca as linhas do registro informado e só as chaves pedidas; devolve quantas linhas mudaram
- [x] Testes em `tests/Feature/Nucleo/AuditServiceTest.php` (inclui: outra linha de outro contato fica intacta; senha nunca entra em `changes`)
- [x] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `feat(auditoria): gravação da negação e limpeza de dado pessoal no AuditService`

---

### T3: authorize() do Controller base audita a negação

**What**: `App\Http\Controllers\Controller::authorize()` usa `Gate::inspect`; se nega, chama `AuditService::denied()` e lança o 403.
**Where**: `app/Http/Controllers/Controller.php`
**Depends on**: T2
**Reuses**: `AuthorizesRequests`
**Requirement**: NUC-11

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Dev em `GET /admin/users` recebe 403 e aparece uma linha `denied` com `auditable_type = user`
- [x] Rota permitida não grava linha `denied`
- [x] `RotasAutorizadasTest` (A5) continua verde
- [x] Testes em `tests/Feature/Nucleo/NegacaoAuditadaTest.php`
- [x] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `feat(auditoria): negação por Policy gravada na auditoria`

---

### T4: Regra do CNPJ alfanumérico

**What**: `App\Rules\AlphanumericCnpj` valida 12 alfanuméricos + 2 dígitos com o cálculo da Receita (ASCII − 48) e expõe `normalize()`.
**Where**: `app/Rules/AlphanumericCnpj.php`
**Depends on**: None
**Reuses**: estrutura de `App\Rules\Document`
**Requirement**: NUC-05

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Aceita `12ABC34501DE35`, `12.ABC.345/01DE-35`, `12abc34501de35` e `38614817000122`
- [x] Recusa dígito errado, tamanho errado, letra nos dígitos, todos iguais
- [x] `normalize` tira máscara e põe maiúscula
- [x] Testes em `tests/Unit/AlphanumericCnpjTest.php`
- [x] Gate check passes: `composer lint && php artisan test`

**Tests**: unit
**Gate**: quick

**Commit**: `feat(clientes): regra do CNPJ alfanumérico`

---

### T5: Contrato ClientProjects e a implementação de Projetos

**What**: interface `App\Platform\Contracts\ClientProjects` e `App\Domains\Projetos\Services\ProjectClientProjects` (clientes geridos, `manages`, projetos abertos), ligada no `AppServiceProvider`, com ADR-003 e AD-003.
**Where**: `app/Domains/Projetos/Services/ProjectClientProjects.php`
**Depends on**: None
**Reuses**: Models `Project`, `ProjectMember`
**Requirement**: NUC-06

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Gestor por `manager_id` e gestor alocado em `project_members` contam; dev alocado não conta; projeto de outro cliente não conta
- [ ] `openProjectsCount` conta só `finished_at` nulo
- [ ] `docs/adr/003-nucleo-consulta-projetos-por-contrato.md` e `AD-003` no `.specs/STATE.md`
- [ ] A7 e A9 verdes
- [ ] Testes em `tests/Feature/Nucleo/ClientProjectsTest.php`
- [ ] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `feat(projetos): contrato para o Núcleo saber os clientes de cada gestor`

---

### Phase 2: Usuários

### T6: UserPolicy completa

**What**: `deactivate`, `reactivate`, `resendInvite`, `viewChoiceList` e o `delete` sem a si mesmo.
**Where**: `app/Platform/Policies/UserPolicy.php`
**Depends on**: T3
**Reuses**: a Policy existente
**Requirement**: NUC-04, NUC-13

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Matriz ability × papel testada (admin, gestor, dev, comercial)
- [ ] Testes em `tests/Feature/Nucleo/UserPolicyTest.php`
- [ ] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `feat(usuarios): UserPolicy com desativar, reativar, reenviar convite e lista de escolha`

---

### T7: UserService

**What**: `invite`, `resendInvite`, `update`, `deactivate`, `reactivate`, `delete`, `choiceList`, com auditoria na transação, revogação das conexões de IA, carga obrigatória para Desenvolvedor, último admin ativo e e-mail enviado depois do commit.
**Where**: `app/Platform/Services/UserService.php`
**Depends on**: T6
**Reuses**: `TemporaryPasswordService` (separado em gravar e enviar), `SessionService`, `Deactivatable`, `AuditService`
**Requirement**: NUC-01, NUC-02, NUC-03, NUC-13

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Usuários AC 1–12, 14 e 16 com teste cada
- [ ] Edge cases: convite simultâneo (unique), e-mail que falha mantém o usuário, último admin
- [ ] Linha "Login: e-mail" no e-mail de convite (texto e HTML)
- [ ] Testes em `tests/Feature/Nucleo/UserServiceTest.php`
- [ ] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `feat(usuarios): UserService com convite, desativação e revogação das conexões de IA`

---

### T8: UserController fino, rotas e requests

**What**: o controller passa a chamar o `UserService`; rotas `deactivate` e `reactivate`; lista com Último acesso, Situação e *Mostrar desativados*; `StoreUserRequest` com `ai_write_allowed` e carga obrigatória para dev.
**Where**: `app/Platform/Controllers/Admin/UserController.php`
**Depends on**: T7
**Reuses**: o controller e o request existentes
**Requirement**: NUC-04

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Toda rota de usuário: feliz, 403 com "nada muda" para gestor e dev, erro de validação
- [ ] `UserControllerTest` existente ajustado sem perder caso; a caixa "ativo" sai
- [ ] Dívida A6 do `UserController` diminui ou sai do `divida.php`
- [ ] Testes em `tests/Feature/Nucleo/UserControllerTest.php`
- [ ] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `refactor(usuarios): controller fino sobre o UserService, com desativar e reativar`

---

### T9: Tela de usuários

**What**: colunas do F1 §03, filtro *Mostrar desativados*, ações *Reenviar convite*, Editar, Desativar, Reativar, Excluir, *Histórico*; formulário com "IA pode escrever" e o aviso de desconexão da IA.
**Where**: `resources/js/pages/admin/users/index.tsx`
**Depends on**: T8
**Reuses**: `UserFormDialog`, `DataTable`, `RowActions`, `ConfirmDialog`
**Requirement**: NUC-04, NUC-14

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Estados vazio, erro e carregando; textos do F1 §04 e §07
- [ ] Smoke e2e da tela e do modal (axe, dois temas) continua verde
- [ ] Gate check passes: Build

**Tests**: e2e
**Gate**: build

**Commit**: `feat(usuarios): tela com convite, desativação e histórico`

---

### Phase 3: Clientes

### T10: ClientPolicy

**What**: Policy de clientes do F1 §09, com o gestor decidido pelo `ClientProjects` e a WCJ nunca desativável.
**Where**: `app/Platform/Policies/ClientPolicy.php`
**Depends on**: T5
**Reuses**: `ClientProjects`
**Requirement**: NUC-06, NUC-07

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Matriz ability × papel, com gestor que gere e que não gere
- [ ] Registrada com `Gate::policy` no `AppServiceProvider`
- [ ] Testes em `tests/Feature/Nucleo/ClientPolicyTest.php`
- [ ] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `feat(clientes): ClientPolicy`

---

### T11: ClientService

**What**: `create`, `update`, `deactivate`, `reactivate`, `delete`, `choiceList`, com CNPJ normalizado, recusa de repetido citando o cliente existente e auditoria na transação.
**Where**: `app/Platform/Services/ClientService.php`
**Depends on**: T10
**Reuses**: `AlphanumericCnpj`, `Deactivatable`, `AuditService`
**Requirement**: NUC-05, NUC-06, NUC-07, NUC-13

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Clientes AC 1–12 e 14 com teste cada; edge cases de máscara e de troca de país
- [ ] `ClientFactory` gera CNPJ válido
- [ ] Testes em `tests/Feature/Nucleo/ClientServiceTest.php`
- [ ] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `feat(clientes): ClientService com CNPJ único por país e WCJ protegida`

---

### T12: ClientController, rotas e requests

**What**: rotas de clientes (lista, ficha, criar, editar, desativar, reativar, excluir), `StoreClientRequest`.
**Where**: `app/Platform/Controllers/Admin/ClientController.php`
**Depends on**: T11
**Reuses**: gabarito do `UserController`
**Requirement**: NUC-05, NUC-07

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Toda rota: feliz, 403 com "nada muda" (dev em tudo; gestor em desativar, excluir e editar cliente que não gere), erro de validação
- [ ] Lista com busca por nome e documento, filtros País e Situação
- [ ] Testes em `tests/Feature/Nucleo/ClientControllerTest.php`
- [ ] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `feat(clientes): rotas e controller de clientes`

---

### T13: Tela de clientes

**What**: lista e formulário de cliente (rótulo CNPJ ↔ Identificador fiscal), desativar com a contagem de projetos abertos, excluir.
**Where**: `resources/js/pages/admin/clients/index.tsx`
**Depends on**: T12
**Reuses**: gabarito de CRUD; `components/clients/client-form-dialog.tsx` novo
**Requirement**: NUC-05, NUC-07, NUC-14

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Estados vazio ("Nenhum cliente ainda" + *Novo cliente*), erro e carregando
- [ ] Smoke e2e com axe nos dois temas e no modal
- [ ] Gate check passes: Build

**Tests**: e2e
**Gate**: build

**Commit**: `feat(clientes): tela de clientes`

---

### T14: Ficha do cliente

**What**: página da ficha com os dados e a aba Contatos (lista dos contatos do cliente, por enquanto só leitura), com ADR-004.
**Where**: `resources/js/pages/admin/clients/show.tsx`
**Depends on**: T13
**Reuses**: `PageHeader`, `DataTable`
**Requirement**: NUC-08

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] `docs/adr/004-ficha-do-cliente.md`
- [ ] Smoke e2e com axe
- [ ] Gate check passes: Build

**Tests**: e2e
**Gate**: build

**Commit**: `feat(clientes): ficha do cliente com a aba de contatos`

---

### Phase 4: Contatos

### T15: ContactPolicy e ContactFactory

**What**: Policy de contatos do F1 §09 (gestor nos clientes que gere; anonimizado não se edita, não se reativa, não se anonimiza de novo) e a factory.
**Where**: `app/Platform/Policies/ContactPolicy.php`
**Depends on**: T14
**Reuses**: `ClientProjects`
**Requirement**: NUC-08, NUC-09, NUC-10

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Matriz ability × papel × situação do contato
- [ ] `Contact::isAnonymized()` e `newFactory()`
- [ ] Testes em `tests/Feature/Nucleo/ContactPolicyTest.php`
- [ ] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `feat(contatos): ContactPolicy`

---

### T16: ContactService (cadastro e ciclo de vida)

**What**: `create`, `update` (com o aviso de e-mail repetido), `deactivate`, `reactivate`, `delete`, com cliente ativo obrigatório.
**Where**: `app/Platform/Services/ContactService.php`
**Depends on**: T15
**Reuses**: `Deactivatable`, `AuditService`
**Requirement**: NUC-08, NUC-09

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Contatos AC 1–9 com teste cada
- [ ] Testes em `tests/Feature/Nucleo/ContactServiceTest.php`
- [ ] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `feat(contatos): ContactService com aviso de e-mail repetido`

---

### T17: Anonimizar contato

**What**: `ContactService::anonymize` numa transação: nome trocado, e-mail e telefone nulos, `scrubPersonalData` na auditoria, desativação e a linha `anonymized` sem dado pessoal.
**Where**: `app/Platform/Services/ContactService.php`
**Depends on**: T16
**Reuses**: `AuditService::scrubPersonalData`
**Requirement**: NUC-10

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Contatos AC 10–12; falha no meio desfaz tudo
- [ ] Nenhuma linha de `audit_logs` do contato contém nome, e-mail ou telefone antigos
- [ ] Testes em `tests/Feature/Nucleo/ContactAnonymizeTest.php`
- [ ] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `feat(contatos): anonimização a pedido do titular`

---

### T18: ContactController, rotas e requests

**What**: lista geral, criar no cliente, editar, desativar, reativar, excluir, anonimizar (`AnonymizeContactRequest` exige ANONIMIZAR).
**Where**: `app/Platform/Controllers/Admin/ContactController.php`
**Depends on**: T17
**Reuses**: `NormalizesMaskedInput` (telefone)
**Requirement**: NUC-08, NUC-09, NUC-10

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Toda rota: feliz, 403 com "nada muda", erro de validação; aviso de e-mail repetido chega como flash
- [ ] Lista do gestor só traz os clientes dele
- [ ] Testes em `tests/Feature/Nucleo/ContactControllerTest.php`
- [ ] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `feat(contatos): rotas e controller de contatos`

---

### T19: Telas de contatos

**What**: lista geral `contacts/index.tsx`, ações na aba da ficha do cliente, formulário e diálogo de anonimizar.
**Where**: `resources/js/pages/admin/contacts/index.tsx`
**Depends on**: T18
**Reuses**: gabarito de CRUD; `components/contacts/*` novos
**Requirement**: NUC-08, NUC-10, NUC-14

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Estados vazio, erro e carregando; situação "Anonimizado" sem botões de editar
- [ ] Smoke e2e com axe nos dois temas e no modal
- [ ] Gate check passes: Build

**Tests**: e2e
**Gate**: build

**Commit**: `feat(contatos): telas de contatos e anonimização`

---

### Phase 5: Auditoria (tela)

### T20: AuditLogPolicy e AuditLogQuery

**What**: Policy no lugar do gate `view-audit-logs`; consulta com os filtros do F1 §05, "Registro" legível e canal pelo assistente.
**Where**: `app/Platform/Services/AuditLogQuery.php`
**Depends on**: T19
**Reuses**: `LocalTime`
**Requirement**: NUC-12

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Cada filtro testado; período padrão 7 dias; *Histórico* (tipo + id) sem limite de período
- [ ] Testes em `tests/Feature/Nucleo/AuditLogQueryTest.php`
- [ ] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `feat(auditoria): Policy e consulta da auditoria com filtros`

---

### T21: AuditLogController

**What**: controller fino sobre o `AuditLogQuery`, páginas de 50, sem rota de editar ou apagar.
**Where**: `app/Platform/Controllers/Admin/AuditLogController.php`
**Depends on**: T20
**Reuses**: o controller existente
**Requirement**: NUC-12

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Admin vê; gestor e dev 403 e a negação gravada
- [ ] Dívida A6 do `AuditLogController::index` sai do `divida.php`
- [ ] Testes em `tests/Feature/Nucleo/AuditLogControllerTest.php`
- [ ] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `refactor(auditoria): controller fino sobre a consulta`

---

### T22: Tela de auditoria

**What**: colunas e filtros do F1, detalhe antes/depois com rótulo de campo, negado em destaque, estado vazio "Nenhum registro com esses filtros".
**Where**: `resources/js/pages/admin/audit-logs/index.tsx`
**Depends on**: T21
**Reuses**: a página existente
**Requirement**: NUC-12, NUC-14

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Smoke e2e com axe e o detalhe aberto
- [ ] Gate check passes: Build

**Tests**: e2e
**Gate**: build

**Commit**: `feat(auditoria): tela com filtros, canal e detalhe`

---

### Phase 6: Integração

### T23: Menu e abilities

**What**: grupo "Administração" no menu (Usuários, Clientes, Contatos, Auditoria) e as abilities novas em `User`, `HandleInertiaRequests`, `types/index.d.ts`.
**Where**: `app/Http/Middleware/HandleInertiaRequests.php`
**Depends on**: T22
**Reuses**: `NAV` declarativo
**Requirement**: NUC-14

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Admin vê os quatro itens; gestor vê Clientes e Contatos; dev não vê nenhum
- [ ] Teste das abilities em `tests/Feature/Nucleo/AbilitiesTest.php` e smoke e2e verdes
- [ ] Gate check passes: Build

**Tests**: e2e
**Gate**: build

**Commit**: `feat(nucleo): menu de administração e abilities por papel`

---

### T24: Fluxo crítico do Núcleo em e2e

**What**: spec `@critical` que cria um cliente com CNPJ alfanumérico, é recusado no repetido, cria um contato, edita o cliente e abre o *Histórico*.
**Where**: `e2e/nucleo.spec.ts`
**Depends on**: T23
**Reuses**: `AdminPage`, fixtures
**Requirement**: NUC-05, NUC-08, NUC-12

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Spec passa na suíte inteira
- [ ] Gate check passes: Build

**Tests**: e2e
**Gate**: build

**Commit**: `test(e2e): fluxo de cliente, contato e histórico`

---

## Phase Execution Map

```
Phase 1 → Phase 2 → Phase 3 → Phase 4 → Phase 5 → Phase 6

Phase 1:  T1 → T2 → T3
Phase 1:  T4
Phase 1:  T5
Phase 2:  T6 → T7 → T8 → T9
Phase 3:  T10 → T11 → T12 → T13 → T14
Phase 4:  T15 → T16 → T17 → T18 → T19
Phase 5:  T20 → T21 → T22
Phase 6:  T23 → T24
```

## Task Granularity Check

| Task | Scope | Status |
| --- | --- | --- |
| T1, T4 | 1 enum / 1 regra | ✅ Granular |
| T2, T3 | 1 Service / 1 método base | ✅ Granular |
| T5 | 1 interface + 1 implementação (contrato inseparável) + ADR | ⚠️ Coeso |
| T6, T10, T15, T20 | 1 Policy (T15 com a factory do Model; T20 com a Policy da auditoria) | ⚠️ Coeso |
| T7, T11, T16, T17 | 1 Service | ✅ Granular |
| T8, T12, T18, T21 | 1 controller com as rotas e o FormRequest dele | ⚠️ Coeso |
| T9, T13, T14, T19, T22 | 1 página (com o formulário dela) | ⚠️ Coeso |
| T23 | abilities nos 4 pontos que o AGENTS.md manda mudar juntos | ⚠️ Coeso |
| T24 | 1 spec e2e | ✅ Granular |

## Diagram-Definition Cross-Check

| Task | Depends On (task body) | Diagram Shows | Status |
| --- | --- | --- | --- |
| T1 | None | início | ✅ Match |
| T2 | T1 | T1 → T2 | ✅ Match |
| T3 | T2 | T2 → T3 | ✅ Match |
| T4 | None | sozinha | ✅ Match |
| T5 | None | sozinha | ✅ Match |
| T6 | T3 | fase anterior | ✅ Match |
| T7 | T6 | T6 → T7 | ✅ Match |
| T8 | T7 | T7 → T8 | ✅ Match |
| T9 | T8 | T8 → T9 | ✅ Match |
| T10 | T5 | fase anterior | ✅ Match |
| T11 | T10 | T10 → T11 | ✅ Match |
| T12 | T11 | T11 → T12 | ✅ Match |
| T13 | T12 | T12 → T13 | ✅ Match |
| T14 | T13 | T13 → T14 | ✅ Match |
| T15 | T14 | fase anterior | ✅ Match |
| T16 | T15 | T15 → T16 | ✅ Match |
| T17 | T16 | T16 → T17 | ✅ Match |
| T18 | T17 | T17 → T18 | ✅ Match |
| T19 | T18 | T18 → T19 | ✅ Match |
| T20 | T19 | fase anterior | ✅ Match |
| T21 | T20 | T20 → T21 | ✅ Match |
| T22 | T21 | T21 → T22 | ✅ Match |
| T23 | T22 | fase anterior | ✅ Match |
| T24 | T23 | T23 → T24 | ✅ Match |

## Test Co-location Validation

| Task | Code Layer Created/Modified | Matrix Requires | Task Says | Status |
| --- | --- | --- | --- | --- |
| T1 | Enum (função pura) | unit | unit | ✅ OK |
| T2 | Service | integration | integration | ✅ OK |
| T3 | Controller base | integration | integration | ✅ OK |
| T4 | Regra de validação | unit | unit | ✅ OK |
| T5 | Service (Projetos) | integration | integration | ✅ OK |
| T6, T10, T15 | Policy | integration | integration | ✅ OK |
| T7, T11, T16, T17 | Service | integration | integration | ✅ OK |
| T8, T12, T18, T21 | Controller / rota | integration | integration | ✅ OK |
| T9, T13, T14, T19, T22 | Página React | e2e | e2e | ✅ OK |
| T20 | Policy + consulta | integration | integration | ✅ OK |
| T23 | Middleware + página (menu) | integration + e2e | e2e (com teste de abilities) | ✅ OK |
| T24 | e2e | e2e | e2e | ✅ OK |
