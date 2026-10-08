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
T23 → T24 → T25
```

### Phase 7: Correções do Verifier (rodada 1)

```
T26 → T27 → T28 → T29
```

### Phase 8: Correções do Verifier (rodada 2)

```
T30
```

### Phase 9: Correções do Verifier (rodada 3)

```
T31
```

### Phase 10: Spec revisada em 07/10 (contrato ClientManagement)

```
T32 → T33 → T34 → T35
```

### Phase 11: Correções do Verifier (rodada 5)

```
T36
```

### Phase 12: Review do Lucas (07/10): achados de código

```
T37 → T38 → T39 → T40 → T41 → T42 → T43 → T44
```

### Phase 13: Review do Lucas (07/10): decisões, telas e e2e

```
T45 → T46 → T47 → T48 → T49 → T50
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

> Substituída na fase 10 (T32–T33, spec revisada em 07/10): o contrato agora é `ClientManagement`, com a implementação vazia no B1 e a real no B2. O texto abaixo é o registro do que a T5 fez.

**What**: interface `App\Platform\Contracts\ClientProjects` e `App\Domains\Projetos\Services\ProjectClientProjects` (clientes geridos, `manages`, projetos abertos), ligada no `AppServiceProvider`, com ADR-003 e AD-003.
**Where**: `app/Domains/Projetos/Services/ProjectClientProjects.php`
**Depends on**: None
**Reuses**: Models `Project`, `ProjectMember`
**Requirement**: NUC-06

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Gestor por `manager_id` e gestor alocado em `project_members` contam; dev alocado não conta; projeto de outro cliente não conta
- [x] `openProjectsCount` conta só `finished_at` nulo
- [x] `docs/adr/003-nucleo-consulta-projetos-por-contrato.md` e `AD-003` no `.specs/STATE.md`
- [x] A7 e A9 verdes
- [x] Testes em `tests/Feature/Nucleo/ClientProjectsTest.php`
- [x] Gate check passes: `composer lint && php artisan test`

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

- [x] Matriz ability × papel testada (admin, gestor, dev, comercial)
- [x] Testes em `tests/Feature/Nucleo/UserPolicyTest.php`
- [x] Gate check passes: `composer lint && php artisan test`

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

- [x] Usuários AC 1–12, 14 e 16 com teste cada
- [x] Edge cases: convite simultâneo (unique), e-mail que falha mantém o usuário, último admin
- [x] Linha "Login: e-mail" no e-mail de convite (texto e HTML)
- [x] Testes em `tests/Feature/Nucleo/UserServiceTest.php`
- [x] Gate check passes: `composer lint && php artisan test`

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

- [x] Toda rota de usuário: feliz, 403 com "nada muda" para gestor e dev, erro de validação
- [x] `UserControllerTest` existente ajustado sem perder caso; a caixa "ativo" sai
- [x] Dívida A6 do `UserController` diminui ou sai do `divida.php`
- [x] Testes em `tests/Feature/Nucleo/UserControllerTest.php`
- [x] Gate check passes: `composer lint && php artisan test`

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

- [x] Estados vazio, erro e carregando; textos do F1 §04 e §07
- [x] Smoke e2e da tela e do modal (axe, dois temas) continua verde
- [x] Gate check passes: Build

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

- [x] Matriz ability × papel, com gestor que gere e que não gere
- [x] Registrada com `Gate::policy` no `AppServiceProvider`
- [x] Testes em `tests/Feature/Nucleo/ClientPolicyTest.php`
- [x] Gate check passes: `composer lint && php artisan test`

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

- [x] Clientes AC 1–12 e 14 com teste cada; edge cases de máscara e de troca de país
- [x] `ClientFactory` gera CNPJ válido
- [x] Testes em `tests/Feature/Nucleo/ClientServiceTest.php`
- [x] Gate check passes: `composer lint && php artisan test`

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

- [x] Toda rota: feliz, 403 com "nada muda" (dev em tudo; gestor em desativar, excluir e editar cliente que não gere), erro de validação
- [x] Lista com busca por nome e documento, filtros País e Situação
- [x] Testes em `tests/Feature/Nucleo/ClientControllerTest.php`
- [x] Gate check passes: `composer lint && php artisan test`

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

- [x] Estados vazio ("Nenhum cliente ainda" + *Novo cliente*), erro e carregando
- [x] Smoke e2e com axe nos dois temas e no modal
- [x] Gate check passes: Build

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

- [x] `docs/adr/004-ficha-do-cliente.md`
- [x] Smoke e2e com axe
- [x] Gate check passes: Build

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

- [x] Matriz ability × papel × situação do contato
- [x] `Contact::isAnonymized()` e `newFactory()`
- [x] Testes em `tests/Feature/Nucleo/ContactPolicyTest.php`
- [x] Gate check passes: `composer lint && php artisan test`

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

- [x] Contatos AC 1–9 com teste cada
- [x] Testes em `tests/Feature/Nucleo/ContactServiceTest.php`
- [x] Gate check passes: `composer lint && php artisan test`

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

- [x] Contatos AC 10–12; falha no meio desfaz tudo
- [x] Nenhuma linha de `audit_logs` do contato contém nome, e-mail ou telefone antigos
- [x] Testes em `tests/Feature/Nucleo/ContactAnonymizeTest.php`
- [x] Gate check passes: `composer lint && php artisan test`

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

- [x] Toda rota: feliz, 403 com "nada muda", erro de validação; aviso de e-mail repetido chega como flash
- [x] Lista do gestor só traz os clientes dele
- [x] Testes em `tests/Feature/Nucleo/ContactControllerTest.php`
- [x] Gate check passes: `composer lint && php artisan test`

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

- [x] Estados vazio, erro e carregando; situação "Anonimizado" sem botões de editar
- [x] Smoke e2e com axe nos dois temas e no modal
- [x] Gate check passes: Build

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

- [x] Cada filtro testado; período padrão 7 dias; *Histórico* (tipo + id) sem limite de período
- [x] Testes em `tests/Feature/Nucleo/AuditLogQueryTest.php`
- [x] Gate check passes: `composer lint && php artisan test`

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

- [x] Admin vê; gestor e dev 403 e a negação gravada
- [x] Dívida A6 do `AuditLogController::index` sai do `divida.php`
- [x] Testes em `tests/Feature/Nucleo/AuditLogControllerTest.php`
- [x] Gate check passes: `composer lint && php artisan test`

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

- [x] Smoke e2e com axe e o detalhe aberto
- [x] Gate check passes: Build

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

- [x] Admin vê os quatro itens; gestor vê Clientes e Contatos; dev não vê nenhum
- [x] Teste das abilities em `tests/Feature/Nucleo/AbilitiesTest.php` e smoke e2e verdes
- [x] Gate check passes: Build

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

- [x] Spec passa na suíte inteira
- [x] Gate check passes: Build

**Tests**: e2e
**Gate**: build

**Commit**: `test(e2e): fluxo de cliente, contato e histórico`

---

### T25: Mensagens de validação em português

**What**: arquivo de tradução `lang/pt_BR/validation.php` (mensagens e nomes de atributo do Núcleo), para que todo erro de campo saia em português com `APP_LOCALE=pt_BR`, que o `.env.example` já define.
**Where**: `lang/pt_BR/validation.php`
**Depends on**: T24
**Reuses**: as mensagens à mão que já existem nos FormRequests (continuam valendo)
**Requirement**: NUC-14

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Erro de e-mail inválido, campo obrigatório, tamanho máximo e número fora do limite saem em português nas telas do Núcleo
- [x] Teste em `tests/Feature/Nucleo/MensagensEmPortuguesTest.php` asserindo o texto exato de pelo menos quatro regras
- [x] Gate check passes: Build

**Tests**: integration
**Gate**: build

**Commit**: `feat(nucleo): mensagens de validação em português`

---

### Phase 7: Correções do Verifier (rodada 1)

Gaps do `validation.md` de 06/10/2026 (FAIL): mutantes M10, M33 e M34 sobreviventes, textos de tela sem asserção e P3 AC 1 sem evidência. Ficam fora, por decisão já registrada para o Lucas Scholz: os três `destroy` com toast em vez de 403, o P2 AC 3 (rota que o D6 não cria), a mensagem do último admin ao se desativar e os textos que a spec não define.

### T26: Atomicidade do UserService

**What**: testes que forçam uma falha depois da primeira escrita de `deactivate` e de `update` (troca de papel e de "IA pode escrever") e provam que o usuário, as sessões e as `ai_authorizations` ficam como estavam; correção do Service se ele não for atômico.
**Where**: `tests/Feature/Nucleo/UserServiceTest.php`
**Depends on**: T25
**Reuses**: `UserService`; dublê do `AuditService` e do `SessionService` no container
**Requirement**: NUC-02, NUC-03

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Desativar com falha na auditoria da revogação: usuário ativo, sem `deactivated_at`/`deactivated_by`, sessões e conexões de IA intactas, nenhuma linha de auditoria nova (Usuários AC 8)
- [x] Desativar com falha depois de encerrar as sessões: as sessões voltam junto com o resto (Usuários AC 8)
- [x] Trocar papel e trocar "IA pode escrever" com falha na revogação: papel, marca, conexões e auditoria como estavam (Usuários AC 6, 7)
- [x] M10 e M33 reaplicados numa cópia descartável em `/tmp` (com `vendor` copiado) fazem os testes novos falharem; a cópia é removida
- [x] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `test(usuarios): atomicidade de desativar e de trocar papel ou marca da IA`

---

### T27: IP na auditoria

**What**: os testes afirmam o `ip_address` das linhas de sucesso e da negação com o IP da requisição de teste.
**Where**: `tests/Feature/Nucleo/IpNaAuditoriaTest.php`
**Depends on**: T26
**Reuses**: os helpers `assertAudited` dos testes de Service
**Requirement**: NUC-11

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Os helpers `assertAudited` de `UserServiceTest`, `ClientServiceTest` e `ContactServiceTest` afirmam o `ip_address` da linha de sucesso
- [x] Uma requisição com IP próprio grava esse IP na linha de sucesso e na de negação (Auditoria AC 1, 2)
- [x] M34 reaplicado numa cópia descartável faz os testes falharem
- [x] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `test(auditoria): ip da requisição nas linhas de sucesso e de negação`

---

### T28: Textos e estados das telas que a spec define

**What**: e2e que afirma os textos montados no front: rótulo "CNPJ" no Brasil e "Identificador fiscal" fora; a confirmação "O cliente X tem N projetos abertos; eles continuam. Desativar?"; "Nenhum cliente ainda" com *Novo cliente*; a situação "Anonimizado" sem editar, reativar nem anonimizar; o usuário desativado em cinza com *Mostrar desativados*; "Nenhum registro com esses filtros"; a linha negada em destaque, com ícone e "Negado".
**Where**: `e2e/textos-nucleo.spec.ts`
**Depends on**: T27
**Reuses**: `ClientsPage`, `AuditLogsPage`, `AdminPage`, fixtures; Page Objects novos de Usuários e Contatos; helper de dado do e2e
**Requirement**: NUC-04, NUC-05, NUC-07, NUC-10, NUC-12

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Clientes AC 4, 9, 13; Contatos AC 12; Usuários AC 13; Auditoria AC 3 e 10 com asserção do valor exato
- [x] O spec limpa o que cria
- [x] Gate check passes: Build

**Tests**: e2e
**Gate**: build

**Commit**: `test(e2e): textos e estados das telas do Núcleo que a spec define`

---

### T29: Estados vazio, erro e carregando das listas

**What**: spec e2e parametrizado para Usuários, Clientes, Contatos e Auditoria: vazio (filtro que não acha nada), carregando (visita Inertia atrasada) e erro (visita respondida com 500); a tela que não tiver o estado ganha no padrão do `hooks/use-list-visit.ts`.
**Where**: `e2e/estados-das-listas.spec.ts`
**Depends on**: T28
**Reuses**: `useListVisit`, fixtures, Page Objects
**Requirement**: NUC-14

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] As quatro listas: texto do vazio com a ação dele, indicador de carregando e mensagem de erro com *Tentar de novo* afirmados
- [x] Sem `waitForTimeout`
- [x] Gate check passes: Build

**Tests**: e2e
**Gate**: build

**Commit**: `test(e2e): estados vazio, carregando e erro das quatro listas`

---

### Phase 8: Correções do Verifier (rodada 2)

### T30: A WCJ é identificada pelo país e pelo documento, nunca pelo nome

**What**: testes que separam nome e documento da WCJ: a WCJ renomeada continua sem poder ser desativada nem excluída, e outro cliente BR chamado "WCJ Tecnologia" com outro CNPJ pode ser desativado e excluído.
**Where**: `tests/Feature/Nucleo/ClientPolicyTest.php`
**Depends on**: T29
**Reuses**: `WcjClientSeeder`, `ClientFactory`
**Requirement**: NUC-07

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] WCJ renomeada: `deactivate` e `delete` negados com as mensagens da Policy
- [x] Homônimo com outro CNPJ: `deactivate` e `delete` permitidos
- [x] Mutante R06 do Verifier (isWcj por nome) derrubado pelos testes, conferido numa cópia descartável
- [x] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `test(clientes): a WCJ é reconhecida pelo documento, não pelo nome`

---

### Phase 9: Correções do Verifier (rodada 3)

### T31: Bordas do dígito verificador do CNPJ com caso fixo

**What**: casos fixos no teste do CNPJ para resto 2 (dígito 9), resto 1 e resto 0 (dígito 0), e o inválido que um "resto ≤ 2 vira 0" aceitaria.
**Where**: `tests/Unit/AlphanumericCnpjTest.php`
**Depends on**: T30
**Reuses**: os data providers existentes
**Requirement**: NUC-05

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Válidos `00000000000191`, `00000000000604`, `00000000001406`; inválido `00000000000108`
- [x] Mutantes S10 (`< 2` → `<= 2`) e S10b (`< 2` → `< 1`) derrubados por caso fixo, numa cópia descartável
- [x] Gate check passes: `composer lint && php artisan test`

**Tests**: unit
**Gate**: quick

**Commit**: `test(clientes): bordas do dígito verificador do CNPJ`

---

### Phase 10: Spec revisada em 07/10 (contrato ClientManagement)

### T32: Contrato ClientManagement e a implementação vazia

**What**: interface `Platform\Contracts\ClientManagement` com `manages(User $user, Client $client): bool`, `managedClientIds(User $user): array` e `openProjectsCount(Client $client): int` (este último para o Clientes AC 9, a confirmar pelo Lucas), e `Platform\Services\NoClientManagement` (ninguém gere cliente algum; zero projetos abertos), ligada no `AppServiceProvider`. Saem `ClientProjects`, `Domains\Projetos\Services\ProjectClientProjects` (a real é do B2), o `ClientProjectsTest`, a ADR-003 e o AD-003 (o padrão agora está na ADR-001).
**Where**: `app/Platform/Services/NoClientManagement.php`
**Depends on**: T31
**Reuses**: o contrato `ClientProjects` que sai
**Requirement**: NUC-06

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] `app(ClientManagement::class)` é a `NoClientManagement`; ela devolve `false`, `[]` e `0`
- [x] Nada de `App\Domains\` no Platform; A7 e A9 verdes
- [x] ADR-003 e AD-003 removidos, com a ADR-001 citada no lugar
- [x] Testes em `tests/Feature/Nucleo/ClientManagementTest.php`
- [x] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `refactor(nucleo): contrato ClientManagement com a implementação vazia do B1`

---

### T33: Policies e Services perguntam ao ClientManagement

**What**: `ClientPolicy`, `ContactPolicy`, `ClientService` (projetos abertos) e `ContactService` (clientes visíveis ao gestor) passam a usar o `ClientManagement`. Os testes de gestor usam um dublê do contrato; com a vazia, o gestor não edita cliente nem contato de ninguém.
**Where**: `app/Platform/Policies/ClientPolicy.php`
**Depends on**: T32
**Reuses**: as Policies e os Services existentes
**Requirement**: NUC-06, NUC-08

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Clientes AC 6 e 7 e Contatos AC 4, 5 e 13 com o dublê (gere / não gere)
- [x] Com a `NoClientManagement` ligada, gestor recebe 403 ao editar qualquer cliente e criar ou editar contato, e a lista de contatos dele vem vazia
- [x] Clientes AC 9: a contagem vem de `openProjectsCount`, provada com o dublê
- [x] Dublê em `tests/Support/`
- [x] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `refactor(nucleo): policies e services de clientes e contatos pelo ClientManagement`

---

### T34: e2e com a implementação vazia

**What**: os specs e2e que dependiam de um gestor gerir cliente ou de projetos abertos contados passam a refletir a `NoClientManagement`: a confirmação de desativar sem a contagem (variante zero) e o gestor sem edição.
**Where**: `e2e/textos-nucleo.spec.ts`
**Depends on**: T33
**Reuses**: Page Objects e `e2e/helpers/dados.ts`
**Requirement**: NUC-07, NUC-14

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Suíte e2e inteira verde (porta 8001, worktree)
- [x] Nenhum spec e2e cria projeto para provar gestão de cliente
- [x] Gate check passes: Build

**Tests**: e2e
**Gate**: build

**Commit**: `test(e2e): telas de clientes e contatos com a implementação vazia do ClientManagement`

---

### T35: Documentos do B1 acompanham a revisão

**What**: `design.md` (D1, componentes e integração), `AGENTS.md` (a linha do contrato), `.specs/STATE.md` (handoff) e o rastreamento da spec (NUC-06 e NUC-08 em Done).
**Where**: `.specs/features/b1-nucleo/design.md`
**Depends on**: T34
**Reuses**: os textos atuais
**Requirement**: NUC-06, NUC-08

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Nenhuma menção a `ClientProjects` fora do histórico do git
- [x] `conferir-specs.mjs` sem problema além do `validation.md` da rodada anterior
- [x] Gate check passes: `node .ai/skills/conferir-specs.mjs`

**Tests**: none
**Gate**: build

**Commit**: `docs(spec): design, AGENTS e estado do B1 com o contrato ClientManagement`

---

### Phase 11: Correções do Verifier (rodada 5)

### T36: O texto do Clientes AC 9 com N projetos abertos volta a ter teste

**What**: spec e2e que reescreve a resposta da busca de clientes com `open_projects = 2` e afirma o texto exato da spec na confirmação de desativar; com a implementação vazia, o servidor sempre manda 0 até o B2.
**Where**: `e2e/textos-nucleo.spec.ts`
**Depends on**: T35
**Reuses**: o `page.route` do teste do estado vazio, `ClientsPage`, `e2e/helpers/dados.ts`
**Requirement**: NUC-07

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] "O cliente X tem 2 projetos abertos; eles continuam. Desativar?" afirmado com `exact: true`
- [x] Mutante T11 (texto com N trocado em `pages/admin/clients/index.tsx`) derrubado pelo teste novo, numa cópia descartável com build próprio
- [x] Gate check passes: Build (e2e inteiro: 49 passed)

**Tests**: e2e
**Gate**: build

**Commit**: `test(e2e): texto da confirmação de desativar com N projetos abertos`

---

### Phase 12: Review do Lucas (07/10): achados de código

### T37: A1: autorizar antes de validar

**What**: os FormRequests do Núcleo (`StoreUserRequest`, `StoreClientRequest`, `StoreContactRequest`, `AnonymizeContactRequest`) autorizam no `authorize()` pelo mesmo caminho auditado do Controller base (Gate::inspect + `AuditService::denied`), para que não-admin com payload inválido receba 403, não 422, e a tentativa vire `denied`. Review do Lucas Scholz em 07/10 (PR #5).
**Where**: `app/Platform/Requests/Admin/StoreUserRequest.php`
**Depends on**: T36
**Reuses**: o código atual do B1
**Requirement**: NUC-04, NUC-11

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Dev e gestor mandando POST/PUT de usuário com payload inválido e e-mail existente recebem 403, sem a mensagem "já está em uso", e grava linha `denied`
- [x] O mesmo para clientes, contatos e anonimizar
- [x] Teste visto falhando antes da correção
- [x] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `fix(nucleo): autorizar antes de validar nos formulários do Núcleo`

---

### T38: A2: CNPJ e país da WCJ não mudam

**What**: `ClientService::update` recusa a troca de `country` ou `document` quando o cliente é a WCJ (N7), com mensagem na linguagem do usuário; o nome continua editável. Review do Lucas Scholz em 07/10 (PR #5).
**Where**: `app/Platform/Services/ClientService.php`
**Depends on**: T37
**Reuses**: o código atual do B1
**Requirement**: NUC-07

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Admin e gestor que gere a WCJ (dublê) recebem a recusa ao trocar CNPJ ou país; nada muda
- [x] Trocar só o nome da WCJ continua valendo
- [x] Teste visto falhando antes
- [x] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `fix(clientes): a WCJ não troca de CNPJ nem de país`

---

### T39: A3: trava do último admin dentro da transação

**What**: `UserService` faz a checagem do último administrador ativo dentro da transação, com `lockForUpdate()` nas linhas de admin ativo, em desativar, rebaixar e excluir. Review do Lucas Scholz em 07/10 (PR #5).
**Where**: `app/Platform/Services/UserService.php`
**Depends on**: T38
**Reuses**: o código atual do B1
**Requirement**: NUC-02, NUC-03

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Teste de concorrência com `tests/Support/Concorrencia.php`: dois admins se desativando ao mesmo tempo deixam pelo menos um ativo
- [x] Teste visto falhando antes (ou a corrida reproduzida) e passando depois
- [x] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `fix(usuarios): trava do último administrador dentro da transação`

---

### T40: A4: checagem de anonimizado sobre a linha travada

**What**: `ContactService::update` e `reactivate` checam o anonimizado dentro da transação, sobre a linha travada, como o `anonymize()`. Review do Lucas Scholz em 07/10 (PR #5).
**Where**: `app/Platform/Services/ContactService.php`
**Depends on**: T39
**Reuses**: o código atual do B1
**Requirement**: NUC-10

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Edição que esperou a anonimização terminar não regrava dado pessoal no contato nem em `audit_logs.changes` (teste de concorrência ou de ordem)
- [x] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `fix(contatos): checar anonimizado dentro da transação`

---

### T41: A6: ability sem mapeamento não dá 500

**What**: `AuditActionEnum::forAbility()` deixa de lançar exceção: ability desconhecida grava o nome cru como ação da linha `denied`; o rótulo cai num texto genérico. Review do Lucas Scholz em 07/10 (PR #5).
**Where**: `app/Platform/Enums/AuditActionEnum.php`
**Depends on**: T40
**Reuses**: o código atual do B1
**Requirement**: NUC-11

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Ability nova sem mapeamento negada responde 403 e grava `denied` com o nome dela
- [x] Tela da auditoria mostra a linha sem quebrar
- [x] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `fix(auditoria): ability sem mapeamento grava o nome em vez de dar 500`

---

### T42: A7: sem N+1 na lista de clientes e contatos

**What**: `managedClientIds` calculado uma vez por requisição (o `manages` das Policies usa o mesmo resultado) e a contagem de projetos abertos da lista em lote, por um método em lote no contrato `ClientManagement` (a vazia responde zeros). Review do Lucas Scholz em 07/10 (PR #5).
**Where**: `app/Platform/Contracts/ClientManagement.php`
**Depends on**: T41
**Reuses**: o código atual do B1
**Requirement**: NUC-06, NUC-07

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [x] Teste de contagem de consultas: a lista de clientes com 20 linhas não multiplica consultas ao contrato por linha (dublê conta as chamadas)
- [x] Clientes AC 9 continua provado (texto com N)
- [x] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `perf(clientes): contrato consultado uma vez por lista, não por linha`

---

### T43: A8: data inválida não derruba o período padrão

**What**: `AuditLogQuery::filters()` trata como "sem período" só o `from` vazio; `from` inválido volta para o padrão de 7 dias. Review do Lucas Scholz em 07/10 (PR #5).
**Where**: `app/Platform/Services/AuditLogQuery.php`
**Depends on**: T42
**Reuses**: o código atual do B1
**Requirement**: NUC-12

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] `from=07/10/2026` lista só os últimos 7 dias; `from=` vazio lista tudo
- [ ] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `fix(auditoria): data inválida no filtro mantém o período padrão`

---

### T44: A9: attempt() no Controller base

**What**: o `attempt()` repetido em `UserController`, `ClientController` e `ContactController` sobe para `App\Http\Controllers\Controller`, ao lado do `authorize()`. Review do Lucas Scholz em 07/10 (PR #5).
**Where**: `app/Http/Controllers/Controller.php`
**Depends on**: T43
**Reuses**: o código atual do B1
**Requirement**: NUC-04

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Os três controllers usam o do base; nenhuma mensagem muda
- [ ] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `refactor(nucleo): attempt() num lugar só, no Controller base`

---

### Phase 13: Review do Lucas (07/10): decisões, telas e e2e

### T45: D2: aviso de e-mail repetido por papel

**What**: `ContactService::duplicateWarning` recebe quem está vendo: Administrador segue com "Já existe Fulano (Cliente X) com este e-mail"; quem não é admin recebe "Já existe um contato com este e-mail". Decisão D2 do Lucas (07/10).
**Where**: `app/Platform/Services/ContactService.php`
**Depends on**: T44
**Reuses**: o código atual do B1
**Requirement**: NUC-08

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Teste para admin e para gestor (dublê) com o texto exato de cada um
- [ ] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: quick

**Commit**: `fix(contatos): aviso de e-mail repetido sem dado de outro cliente para não-admin`

---

### T46: D3: telefone é texto

**What**: sai o `normalizeDigits(['phone'])` do `StoreContactRequest`; grava o que foi digitado (`trim`), até 20; a tela não força máscara de Brasil e mantém o "+"; a busca por telefone continua achando por dígitos. Decisão D3 do Lucas (07/10).
**Where**: `app/Platform/Requests/Admin/StoreContactRequest.php`
**Depends on**: T45
**Reuses**: o código atual do B1
**Requirement**: NUC-08

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] `+31 6 1234 5678` é gravado e mostrado como digitado
- [ ] Busca por `3161234` acha o contato
- [ ] Gate check passes: `composer lint && php artisan test`

**Tests**: integration
**Gate**: full

**Commit**: `fix(contatos): telefone gravado como texto, com o +`

---

### T47: D9: carga diária aceita 7,3

**What**: sai o `step 0.25` do campo de carga diária no formulário de usuário (`step` qualquer), mantendo `min`. Decisão D9 do Lucas (07/10).
**Where**: `resources/js/components/users/user-form-dialog.tsx`
**Depends on**: T46
**Reuses**: o código atual do B1
**Requirement**: NUC-02

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] e2e: carga 7,3 é aceita e aparece na lista
- [ ] Gate check passes: Build

**Tests**: e2e
**Gate**: build

**Commit**: `fix(usuarios): carga diária sem passo de 0,25`

---

### T48: A5: e2e espera a busca antes de agir

**What**: `find()`, `edit()`, `openSheet()` e `deleteRow()` dos Page Objects esperam a URL com `search=` (e a resposta da busca) antes de clicar, para o debounce do `SearchInput` não cancelar o PUT. Review do Lucas Scholz em 07/10 (PR #5).
**Where**: `e2e/pages/clients.page.ts`
**Depends on**: T47
**Reuses**: o código atual do B1
**Requirement**: NUC-05, NUC-08, NUC-12

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] `@critical` passa 5 vezes seguidas na suíte inteira com banco recriado
- [ ] Gate check passes: Build

**Tests**: e2e
**Gate**: build

**Commit**: `test(e2e): page objects esperam a busca antes de agir`

---

### T49: Cobertura que a review apontou

**What**: testes para os textos das confirmações (desativar usuário, desconectar IA, excluir), os limites de 120 e 160 caracteres do usuário e o estado vazio sem filtro de Usuários e Contatos. Review do Lucas Scholz em 07/10 (PR #5).
**Where**: `e2e/textos-nucleo.spec.ts`
**Depends on**: T48
**Reuses**: o código atual do B1
**Requirement**: NUC-04, NUC-14

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Cada texto do F1 §04 citado na review afirmado com `exact`
- [ ] Limites 120/160 com teste de feature (121 e 161 recusados)
- [ ] Gate check passes: Build

**Tests**: e2e
**Gate**: build

**Commit**: `test(nucleo): textos das confirmações, limites e estados vazios`

---

### T50: Documentos com as decisões do Lucas

**What**: `spec.md` (D6: Usuários AC 12, Clientes AC 12, Contatos AC 9 com o toast; tirar os `SPEC_DEVIATION` do código; D7 com os textos N = 0 e N = 1; D2 e D3), F1 §04 e §09 (D1), `escopo.md` B1 com os caminhos em inglês e §4 com as abilities (D4, D5), `necessidade.md` N12 com a limitação do contato excluído (D11), `design.md`, `AGENTS.md`, `STATE.md`.
**Where**: `docs/funcional/f1-nucleo.md`
**Depends on**: T49
**Reuses**: o código atual do B1
**Requirement**: NUC-04, NUC-06, NUC-07, NUC-09

**Tools**:

- MCP: NONE
- Skill: NONE

**Done when**:

- [ ] Nenhum `SPEC_DEVIATION` no código
- [ ] `conferir-specs.mjs` sem problema
- [ ] Gate check passes: `node .ai/skills/conferir-specs.mjs`

**Tests**: none
**Gate**: build

**Commit**: `docs(spec): decisões do Lucas na spec, no F1, no escopo e na necessidade`

---

## Phase Execution Map

```
Phase 1 → Phase 2 → Phase 3 → Phase 4 → Phase 5 → Phase 6 → Phase 7 → Phase 8 → Phase 9 → Phase 10 → Phase 11 → Phase 12 → Phase 13

Phase 1:  T1 → T2 → T3
Phase 1:  T4
Phase 1:  T5
Phase 2:  T6 → T7 → T8 → T9
Phase 3:  T10 → T11 → T12 → T13 → T14
Phase 4:  T15 → T16 → T17 → T18 → T19
Phase 5:  T20 → T21 → T22
Phase 6:  T23 → T24 → T25
Phase 7:  T26 → T27 → T28 → T29
Phase 8:  T30
Phase 9:  T31
Phase 10: T32 → T33 → T34 → T35
Phase 11: T36
Phase 12: T37 → T38 → T39 → T40 → T41 → T42 → T43 → T44
Phase 13: T45 → T46 → T47 → T48 → T49 → T50
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
| T25 | 1 arquivo de tradução | ✅ Granular |
| T26, T27 | testes de 1 Service / do IP da auditoria (helpers dos testes de Service junto) | ⚠️ Coeso |
| T28, T29 | 1 spec e2e (com os Page Objects dele) | ✅ Granular |

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
| T25 | T24 | T24 → T25 | ✅ Match |
| T26 | T25 | fase anterior | ✅ Match |
| T27 | T26 | T26 → T27 | ✅ Match |
| T28 | T27 | T27 → T28 | ✅ Match |
| T29 | T28 | T28 → T29 | ✅ Match |
| T30 | T29 | fase anterior | ✅ Match |
| T31 | T30 | fase anterior | ✅ Match |
| T32 | T31 | fase anterior | ✅ Match |
| T33 | T32 | T32 → T33 | ✅ Match |
| T34 | T33 | T33 → T34 | ✅ Match |
| T35 | T34 | T34 → T35 | ✅ Match |
| T36 | T35 | fase anterior | ✅ Match |
| T37 | T36 | fase anterior | ✅ Match |
| T38 | T37 | T37 → T38 | ✅ Match |
| T39 | T38 | T38 → T39 | ✅ Match |
| T40 | T39 | T39 → T40 | ✅ Match |
| T41 | T40 | T40 → T41 | ✅ Match |
| T42 | T41 | T41 → T42 | ✅ Match |
| T43 | T42 | T42 → T43 | ✅ Match |
| T44 | T43 | T43 → T44 | ✅ Match |
| T45 | T44 | fase anterior | ✅ Match |
| T46 | T45 | T45 → T46 | ✅ Match |
| T47 | T46 | T46 → T47 | ✅ Match |
| T48 | T47 | T47 → T48 | ✅ Match |
| T49 | T48 | T48 → T49 | ✅ Match |
| T50 | T49 | T49 → T50 | ✅ Match |

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
| T25 | Tradução (validação) | integration | integration | ✅ OK |
| T26, T27 | Service (testes) | integration | integration | ✅ OK |
| T28, T29 | Página React | e2e | e2e | ✅ OK |
| T30 | Policy | integration | integration | ✅ OK |
| T31 | Regra de validação | unit | unit | ✅ OK |
| T32, T33 | Service, Policy | integration | integration | ✅ OK |
| T34 | e2e | e2e | e2e | ✅ OK |
| T35 | Docs, `.specs` | none | none | ✅ OK |
| T36 | e2e | e2e | e2e | ✅ OK |
| T37–T46 | Service, Policy, Request, Controller, Enum | integration | integration | ✅ OK |
| T47–T49 | Página React, e2e | e2e | e2e | ✅ OK |
| T50 | Docs, `.specs` | none | none | ✅ OK |
