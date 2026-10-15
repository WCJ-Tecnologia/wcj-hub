# B5: Policies da sprint e dos lançamentos de horas — Specification

Feature `b5-sprint-e-horas` · **trilho completo só para as duas Policies novas** (AGENTS.md, "Trilhos" e "O que exige aprovação humana"); o resto do B5 (SprintService, DueDateService, CapacityQuery, TimeEntryService, telas `/projetos/{id}/sprints` e `/minhas-horas`) é trilho leve, com a spec na descrição do PR. Fonte: [`docs/funcional/f5-sprint-e-horas.md`](../../../docs/funcional/f5-sprint-e-horas.md) §04 e §09, [`docs/escopo.md`](../../../docs/escopo.md) B5 ("Permissão"), [`docs/necessidade.md`](../../../docs/necessidade.md) (P5, P27, P30, P45, P49, P50, C14, G3, G4), [`docs/banco.md`](../../../docs/banco.md) (`sprints`, `time_entries`). Escopo da skill: **Small** (duas Policies, nenhuma ability global nova).

**Approved by**: Lucas Scholz, 2026-10-15

**Aprovada por Lucas Scholz em 15/10/2026, via sessão principal** ("Aprovo a SprintPolicy, a TimeEntryPolicy e as duas exclusões do B5 como propostas.").

Escrita com IA por Lucas Scholz (D-GER-34).

## Problem Statement

O B5 abre rotas novas de sprint (criar, editar, encerrar, reabrir, excluir, pôr e tirar card, horas previstas na tela da sprint, painel de capacidade) e de lançamento de horas (lançar, corrigir, excluir, *Minhas horas*). Sem uma Policy por entidade, essas rotas não teriam `authorize()`, a negação não viraria `denied` na auditoria (G3) e o teste A5 (`RotasAutorizadasTest`) reprovaria. A `ProjectPolicy` decide quem *gere* e quem *vê* o projeto; as duas Policies novas apoiam-se nela, sem um segundo jeito de dizer "gestor do projeto".

## Goals

- [ ] Só o Administrador e o gestor do projeto criam, editam, encerram, reabrem e excluem sprint, põem e tiram card dela e veem a capacidade; quem só vê o projeto vê as sprints e as tarefas delas.
- [ ] Quem está no projeto lança as próprias horas; corrige e exclui as próprias enquanto está no projeto; o gestor do projeto e o Administrador corrigem e excluem as de qualquer um.
- [ ] Toda negação vira linha `outcome = denied` em `audit_logs`, com a ação certa (não o nome cru da ability).

## Out of Scope

| Feature | Reason |
|---|---|
| `TaskPolicy` (mover card, definir, propor e aprovar horas na ficha) | É do B3 (Cauã); a ficha liga as rotas do B5 com a Policy dela |
| Arrastar do Backlog para o To do no quadro | B3 liga o `SprintService` no movimento; a regra de quem pode é a mesma `manageTasks` |
| Ferramenta MCP `lancar_horas` e `minhas_tarefas` | B8; a IA age como o usuário, pelas mesmas Policies (G4) |
| Ability global nova no front | Nenhuma: *Minhas horas* aparece no menu com `view_projects` (a mesma regra, ver as premissas) e as telas recebem o `can` por linha do Presenter |

---

## Assumptions & Open Questions

| Assumption / decision | Chosen default | Rationale | Confirmed? |
|---|---|---|---|
| Quem "gere" o projeto | O mesmo da `ProjectPolicy::manage`: Administrador, ou Gestor de projetos que é o responsável (`manager_id`) ou está alocado. A `SprintPolicy` e a `TimeEntryPolicy` perguntam à `ProjectPolicy` (Gate), não repetem a conta | F5 §09 "nos projetos dele"; um jeito só (AGENTS.md) | y |
| Quem "vê" as sprints | `ProjectPolicy::view`: Administrador, quem gere e quem está alocado. Comercial nunca | F5 §09; C14 | y |
| Abilities da `SprintPolicy` | `viewAny(Project)`, `view(Sprint)` = vê o projeto; `create(Project)`, `update(Sprint)`, `close(Sprint)`, `reopen(Sprint)`, `delete(Sprint)`, `manageTasks(Project)` (pôr e tirar card, horas previstas na linha da sprint e decidir a reestimativa proposta) e `viewCapacity(Project)` = gere o projeto | F5 §04 e §09; escopo B5; P27, P45, P49, P50 | y |
| Dev vê a capacidade | Não: `viewCapacity` é só de quem gere; o painel nem vai para o dev | F5 §09 e §10.17 | y |
| Regras de estado da sprint | Uma aberta por projeto, reabrir só sem outra aberta, excluir só a aberta sem tarefa: são do `SprintService` (recusa com mensagem), não da Policy | Regra no servidor, no ponto que efetiva (inegociável 9); a Policy decide só quem | y |
| Projeto encerrado | Não trava sprint nem horas: quem gere segue criando e encerrando sprint e quem está no projeto segue lançando | O kanban não trava (AGENTS.md); P7 só barra tarefa nova | y |
| Abilities da `TimeEntryPolicy` | `viewAny` (*Minhas horas*) = todo papel menos o Comercial; `create(Task)` = vê o projeto da tarefa (alocado, quem gere, Administrador); `update(TimeEntry)` e `delete(TimeEntry)` = Administrador, quem gere o projeto da tarefa, ou o dono do lançamento enquanto ainda vê o projeto | F5 §04 e §09; P30; C14 | y |
| Dono que saiu do projeto | Não corrige mais o próprio lançamento naquele projeto; o gestor ou o Administrador corrige | P5: tirar a pessoa tira a visão do projeto; quem não vê não escreve | y |
| Gestor lança pelo dev | Não existe: o lançamento grava sempre quem está logado (`user_id` do Service, sem campo na tela) | F5 §10.14; P30 "quem trabalhou lança" | y |
| Tarefa arquivada | Lançar nela é recusado pela validação ("tarefa arquivada"), não pela Policy; corrigir e excluir o que já existe seguem | F5 §02 (a busca não oferece arquivada); P26 (as horas lançadas continuam valendo) | y |
| Menu *Minhas horas* | Aparece com `view_projects` (`ProjectPolicy::viewAny` = todo papel menos o Comercial), a mesma regra de `TimeEntryPolicy::viewAny`; sem ability nova em `HandleInertiaRequests` | Abilities só escondem botão; a rota é decidida pela `TimeEntryPolicy` | y |
| "Nunca teve tarefa" na exclusão da sprint | Lido como "nenhuma tarefa aponta para ela agora" (`tasks.sprint_id`). A sprint que teve cards e os perdeu todos (tirados de volta ao Backlog) pode ser excluída; o rastro das tarefas fica na auditoria delas | O Projetos não lê `audit_logs` (teste A9); o caso real do F5 é a sprint criada por engano | y |
| Mapeamento da negação | `create` → `created`, `update` → `updated`, `delete` → `deleted`, `view*` → `viewed` já mapeiam; entram `close` → `finished`, `reopen` → `reopened`, `manageTasks` → `updated` em `AuditActionEnum::forAbility` | Sem mapeamento a linha grava o nome cru e a tela mostra "Outra ação" | y |

**Open questions:** none - all resolved or logged above.

---

## User Stories

### P1: Só quem gere o projeto mexe na sprint ⭐ MVP

**User Story**: As a gestor de projetos, I want ser o único, com o Administrador, a criar, editar, encerrar, reabrir e excluir sprint e a pôr e tirar card dela, so that o dev não muda o plano da sprint.

**Why P1**: É a P45 e a P50, e a regra de permissão do bloco.

**Acceptance Criteria**:

1. WHEN o Administrador ou o gestor do projeto (responsável ou alocado com papel Gestor de projetos) cria, edita, encerra, reabre ou exclui sprint do projeto THEN o sistema SHALL executar a ação e responder com o redirecionamento de sucesso.
2. IF um Desenvolvedor alocado no projeto envia criar, editar, encerrar, reabrir ou excluir sprint THEN o sistema SHALL responder 403.
3. IF um Gestor de projetos que não é o responsável nem está alocado envia qualquer escrita de sprint do projeto THEN o sistema SHALL responder 403.
4. IF um Desenvolvedor alocado envia pôr card na sprint, tirar card da sprint ou gravar horas previstas pela tela da sprint THEN o sistema SHALL responder 403.
5. IF uma escrita de sprint é negada THEN o sistema SHALL manter a sprint e as tarefas sem mudança (nome, datas, `closed_at`, `sprint_id` e etapa das tarefas, `estimated_hours`) e nenhuma linha `success` de `sprint` nem de `task` na auditoria.
6. WHEN a Policy nega uma ação de sprint THEN o sistema SHALL gravar em `audit_logs` uma linha `outcome = denied` com `auditable_type = sprint` e a ação `created`, `updated`, `finished`, `reopened`, `deleted` ou `viewed`, conforme a ability.

**Independent Test**: `tests/Feature/Projetos/SprintPolicyTest.php`.

---

### P1: Quem vê o projeto vê as sprints; só quem gere vê a capacidade ⭐ MVP

**User Story**: As a desenvolvedor alocado, I want ver as sprints e as tarefas de cada uma, so that eu sei o que está planejado.

**Acceptance Criteria**:

1. WHEN o Administrador, quem gere o projeto ou um Desenvolvedor alocado abre `/projetos/{id}/sprints` THEN o sistema SHALL responder 200 com as sprints do projeto.
2. IF um usuário que não vê o projeto (Desenvolvedor não alocado, Gestor de projetos que não gere, Comercial) abre `/projetos/{id}/sprints` THEN o sistema SHALL responder 403.
3. WHEN quem gere o projeto abre a tela THEN o sistema SHALL mandar o painel de capacidade.
4. WHEN um Desenvolvedor alocado abre a tela THEN o sistema SHALL mandar `capacity = null` e nenhum `can` de escrita verdadeiro.

**Independent Test**: `tests/Feature/Projetos/SprintPolicyTest.php`.

---

### P1: Cada um lança e corrige as próprias horas; quem gere corrige as de todos ⭐ MVP

**User Story**: As a pessoa alocada, I want lançar e corrigir as minhas horas, so that o realizado da tarefa fica certo, sem que outro dev mexa nelas.

**Acceptance Criteria**:

1. WHEN uma pessoa que vê o projeto da tarefa (alocada, quem gere, Administrador) lança horas THEN o sistema SHALL gravar o lançamento com `user_id` = quem está logado.
2. IF uma pessoa que não vê o projeto da tarefa envia lançar horas nela THEN o sistema SHALL responder 403 e não gravar nada.
3. WHEN o dono do lançamento, ainda no projeto, corrige ou exclui o lançamento THEN o sistema SHALL gravar a mudança.
4. WHEN o gestor do projeto ou o Administrador corrige ou exclui o lançamento de outra pessoa THEN o sistema SHALL gravar a mudança.
5. IF um Desenvolvedor envia corrigir ou excluir o lançamento de outra pessoa THEN o sistema SHALL responder 403 e manter o lançamento sem mudança.
6. IF o dono do lançamento saiu do projeto e envia corrigir ou excluir o lançamento THEN o sistema SHALL responder 403 e manter o lançamento sem mudança.
7. IF o Comercial abre `/minhas-horas` THEN o sistema SHALL responder 403.
8. WHEN a Policy nega uma ação de lançamento THEN o sistema SHALL gravar em `audit_logs` uma linha `outcome = denied` com `auditable_type = time_entry` e a ação `created`, `updated`, `deleted` ou `viewed`.

**Independent Test**: `tests/Feature/Projetos/TimeEntryPolicyTest.php`.

---

### P1: Exclusão de verdade só do que não tem história ⭐ MVP

**User Story**: As a gestor de projetos, I want excluir a sprint criada por engano e o lançamento feito na tarefa errada, so that o histórico não guarda lixo, sem perder o que já foi usado.

**Why P1**: Exclusão de verdade é trilho completo no Hub (AGENTS.md); F5 §04 e §10.13 aprovam as duas.

**Acceptance Criteria**:

1. WHEN quem gere o projeto exclui a sprint aberta que não tem tarefa THEN o sistema SHALL apagar a sprint e gravar a linha `deleted` de `sprint` na auditoria, com o antes, na mesma transação.
2. IF a sprint tem alguma tarefa (`tasks.sprint_id` aponta para ela) THEN o sistema SHALL recusar com "A Sprint N tem tarefas: não dá para excluir." e manter a sprint.
3. IF a sprint está encerrada THEN o sistema SHALL recusar com "Só se exclui a sprint aberta." e manter a sprint.
4. WHEN quem pode corrigir o lançamento o exclui THEN o sistema SHALL apagar o lançamento e gravar a linha `deleted` de `time_entry` na auditoria com tarefa, pessoa, data e horas no antes, na mesma transação.

**Independent Test**: `tests/Feature/Projetos/SprintServiceTest.php` e `tests/Feature/Projetos/TimeEntryServiceTest.php` e `tests/Feature/Projetos/B5TransacaoTest.php`.

---

## Edge Cases

- WHEN o gestor perde o papel de Gestor de projetos THEN o sistema SHALL negar as escritas de sprint na próxima requisição (a `ProjectPolicy` lê o papel atual).
- WHEN a sprint da rota é de outro projeto THEN o sistema SHALL responder 404 antes da Policy (`scopeBindings`).
- WHEN o lançamento da rota não existe THEN o sistema SHALL responder 404 antes da Policy (route binding).

---

## Requirement Traceability

| Requirement ID | Story | Phase | Status |
|---|---|---|---|
| SPR-01 | P1: Escritas de sprint só de quem gere (AC 1–5) | Execute | Verified |
| SPR-02 | P1: Negação de sprint auditada com a ação certa (AC 6) | Execute | Verified |
| SPR-03 | P1: Ver sprints e capacidade (AC 1–4) | Execute | Verified |
| HRS-01 | P1: Lançar e corrigir horas (AC 1–7) | Execute | Verified |
| HRS-02 | P1: Negação de lançamento auditada (AC 8) | Execute | Verified |
| DEL-01 | P1: Excluir sprint só aberta e sem tarefa (AC 1–3) | Execute | Verified |
| DEL-02 | P1: Excluir lançamento auditado (AC 4) | Execute | Verified |

**Coverage:** 7 total, 7 mapped to tests, 0 unmapped

---

## Success Criteria

- [ ] `SprintPolicyTest` e `TimeEntryPolicyTest` verdes no MySQL 8.
- [ ] Verifier da skill em agente separado com PASS e mutação sem sobrevivente nas regras de permissão (cada delegação à `ProjectPolicy` trocada por `true`, o dono trocado por "qualquer um", o `authorize()` tirado de cada ação) e na "uma sprint aberta por projeto" (a trava da linha do projeto tirada, a checagem da aberta tirada).
