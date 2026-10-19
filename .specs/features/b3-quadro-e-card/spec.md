# B3 PR 1: `TaskPolicy` e migração aditiva do card do quadro — Specification

Feature `b3-quadro-e-card` · **trilho completo só para a Policy nova e para a migração** (AGENTS.md, "Trilhos" e "O que exige aprovação humana"; a migração dos tipos reescreve dado). O resto do PR 1 (quadro, card rápido, mover, pausa, retrabalho, etiquetas, participantes, card de suporte, aviso `TaskReturned`) é trilho leve, com a spec na descrição do PR. Fonte: [`docs/funcional/f3-quadro-e-card.md`](../../../docs/funcional/f3-quadro-e-card.md) §04, §09 e §11, [`docs/escopo.md`](../../../docs/escopo.md) B3 ("Permissão" e "Dados"), [`docs/necessidade.md`](../../../docs/necessidade.md) (P5, P7, P13, P18, P26, P45, P52, P56 a P59, C14, G3, G4), [`docs/banco.md`](../../../docs/banco.md) (`tasks`, `task_types`, `project_areas`, `task_participants`; seção 7, diferenças de 13/10 e 14/10, aprovadas por Lucas Scholz em 15/10). Escopo da skill: **Medium** (uma Policy, três migrações, uma delas com dado).

**Approved by**: Lucas Scholz, 2026-10-19 (as 11 premissas da tabela *Assumptions*; Verifier a rodar antes do merge)

Escrita com IA por Cauã Soares.

## Problem Statement

O B3 abre as rotas do quadro e do card (ver o quadro, criar rápido, mover, reordenar, trocar etiqueta, participante, card de suporte). Sem uma Policy da tarefa, essas rotas não teriam `authorize()`, a negação não viraria `denied` na auditoria (G3) e o teste A5 (`RotasAutorizadasTest`) reprovaria. A `ProjectPolicy` já diz quem *vê* e quem *gere* o projeto, e a `SprintPolicy` diz quem põe card na sprint: a `TaskPolicy` se apoia nas duas, sem um segundo jeito de dizer "alocado" ou "gestor".

O kit do quadro (PR #11 e #12) e as decisões de 13/10 e 14/10 pedem colunas e uma tabela que o banco ainda não tem (card pai, capa, participantes, cor das etiquetas, tipo do projeto, card sem tipo e sem prioridade). A mudança de `task_types` de lista geral para lista do projeto **reescreve dado**: os tipos gerais que já existem viram cópias por projeto e as tarefas passam a apontar para a cópia do próprio projeto.

## Goals

- [ ] Quem vê o projeto vê o quadro, cria card no Backlog, move a partir do To do e do Support, reordena, edita, comenta e anexa; só quem gere põe card na sprint, leva para o Backlog, cria card de suporte, arquiva e exclui.
- [ ] Toda negação vira linha `outcome = denied` em `audit_logs`, com a ação certa.
- [ ] O banco passa a ter tudo o que o kit do quadro lê, sem perder nenhuma tarefa nem o tipo de nenhuma tarefa existente.

## Out of Scope

| Feature | Reason |
|---|---|
| Arquivar, desarquivar e excluir card (as rotas) | PR 3 do B3; as abilities `archive` e `delete` entram agora para o `can` da tela e para não mudar a Policy duas vezes |
| Comentar e anexar (as rotas) | PR 2 (ficha); as abilities `comment` e `attach` entram agora pelo mesmo motivo |
| Lançar horas | Já é da `TimeEntryPolicy::create(Task)` (B5): a `TaskPolicy` não ganha `logTime`, para não haver dois jeitos (ver premissas) |
| Ferramentas MCP (`escrever_tarefa`, `mover_tarefa`) | B8; a IA age como o usuário, pelas mesmas Policies (G4) |
| Tela de configuração dos tipos | F3 §11 item 9: tipo se cria e edita pela etiqueta, no quadro |

---

## Assumptions & Open Questions

| Assumption / decision | Chosen default | Rationale | Confirmed? |
|---|---|---|---|
| Quem é "qualquer alocado" | `ProjectPolicy::view` do projeto da tarefa: Administrador, quem gere e quem está alocado. Comercial nunca | F3 §09 e o texto do topo do F3; C14 | |
| Quem "gere" | `ProjectPolicy::manage`: Administrador, ou Gestor de projetos responsável ou alocado | Um jeito só (AGENTS.md) | |
| Abilities da `TaskPolicy` | `create(Project)`, `view(Task)`, `update(Task)`, `move(Task, ?BoardStage)`, `comment(Task)`, `attach(Task)` = vê o projeto; `createSupport(Project)`, `archive(Task)`, `delete(Task)` = gere o projeto; `manageSprint(Project)` = `SprintPolicy::manageTasks` | Escopo B3 "Permissão"; F3 §09 | |
| Pôr na sprint e levar ao Backlog | `move` de um card **entre** o Backlog e outra coluna (de lá ou para lá) pede `manageSprint`. Reordenar **dentro** do Backlog não muda a sprint e fica com qualquer alocado | P45 e F3 §04 ("Reordenar na coluna: qualquer alocado") | |
| `logTime` | Não entra: lançar horas já é `TimeEntryPolicy::create(Task)`, aprovada no B5 | Um jeito só; o escopo listou `logTime` antes do B5 existir | |
| Tipo e área pela etiqueta | Criar e editar nome e cor de tipo e de área do projeto = `ProjectPolicy::manage`, a mesma das áreas na configuração (B2). Sem ability nova no back; o quadro manda `manage_types` e `manage_areas` calculadas dela | F3 §11 item 9; B2 passo 2 já usa `manage` para áreas | |
| Trocar o tipo, a área ou a prioridade de um card | `update(Task)`: qualquer alocado | F3 §09 "Editar a ficha" | |
| Projeto encerrado e card arquivado | Não são permissão: o `TaskService` recusa com mensagem (P7: "Projeto encerrado não recebe card novo"; P26: card arquivado não se move). A tela recebe `create_card = false` no projeto encerrado | Regra de estado no Service, como no B5; a Policy decide só quem | |
| Mapeamento da negação | `create`, `update`, `delete`, `view*` já mapeiam; entram `move` → `moved` (caso novo, "Movido"), `createSupport` → `created`, `manageSprint` → `updated`, `archive` → `archived` (caso novo, "Arquivado"), `comment` e `attach` → `updated` | Sem mapeamento a linha grava o nome cru e a tela mostra "Outra ação" | |
| Rota do card | `/projetos/{project}/cards/{chave}`, com a chave inteira (`SGM-17`), procurada só entre os cards do projeto da rota; chave de outro projeto é 404 antes da Policy | Escopo B3 "Telas"; `Copiar link` do kit | |
| Migração dos tipos | Para cada projeto, copia cada tipo geral (slug, label, desativação), acrescenta `development` "Desenvolvimento", repõe `tasks.task_type_id` na cópia do próprio projeto e apaga os gerais; `project_id` vira not null; os uniques passam a `(project_id, slug)` e `(project_id, label)` | banco.md seção 7 (14/10) | |
| Projeto novo | O `ProjectService` grava os 5 tipos padrão na criação, na mesma transação; o `TaskTypeSeeder` geral sai | banco.md seção 7 | |
| Cor | `varchar(10)` nulo, só os 10 nomes da paleta (`gray`, `blue`, `sky`, `green`, `lime`, `yellow`, `orange`, `red`, `pink`, `purple`), validados no FormRequest; nulo = cinza | banco.md seção 7; `TagColor` do kit | |

**Open questions:** nenhuma; as premissas acima pedem o "ok" do Lucas Scholz.

---

## User Stories

### P1: Qualquer alocado usa o quadro; o dev não mexe na sprint ⭐ MVP

**User Story**: As a desenvolvedor alocado, I want criar card, mover a partir do To do e reordenar, so that o quadro anda sem depender do gestor, sem que eu mude o plano da sprint.

**Acceptance Criteria**:

1. WHEN o Administrador, quem gere ou um Desenvolvedor alocado abre `/projetos/{id}/quadro` THEN o sistema SHALL responder 200 com as colunas e os cards.
2. IF um usuário que não vê o projeto (Desenvolvedor não alocado, Gestor de projetos que não gere, Comercial) abre o quadro ou envia qualquer escrita de card do projeto THEN o sistema SHALL responder 403 e nada muda.
3. WHEN um Desenvolvedor alocado cria card rápido, move um card entre colunas que não são o Backlog, reordena um card (inclusive dentro do Backlog) ou troca tipo, área, prioridade ou participante THEN o sistema SHALL gravar.
4. IF um Desenvolvedor alocado envia mover um card do Backlog para outra coluna, ou de outra coluna para o Backlog THEN o sistema SHALL responder 403 e manter etapa, sprint e posição do card.
5. IF um Desenvolvedor alocado envia criar card de suporte, ou criar ou editar tipo ou área do projeto THEN o sistema SHALL responder 403 e nada muda.
6. WHEN o Administrador ou quem gere o projeto faz qualquer uma das ações acima THEN o sistema SHALL gravar.

**Independent Test**: `tests/Feature/Projetos/TaskPolicyTest.php`.

---

### P1: Negação auditada com a ação certa ⭐ MVP

**Acceptance Criteria**:

1. WHEN a `TaskPolicy` nega uma ação THEN o sistema SHALL gravar em `audit_logs` exatamente uma linha `outcome = denied` por requisição, com a ação `created`, `viewed`, `updated`, `moved`, `archived` ou `deleted`, conforme a ability, e o alvo assim: `auditable_type = task` com o `auditable_id` do card nas abilities que recebem o card (`view`, `update`, `move`); `task` com `auditable_id` nulo em `create` e `createSupport` (a negação vem antes de o card existir); `project` com o id do projeto em tipo e área, que passam pela `ProjectPolicy::manage` (decidido na rodada 1 do Verifier, Fix 4, 19/10).
2. IF a negação acontece THEN o sistema SHALL não gravar nenhuma linha `success` de `task` nem de `task_stage_movement`.

**Independent Test**: `tests/Feature/Projetos/TaskPolicyTest.php`.

---

### P1: Migração sem perder dado ⭐ MVP

**User Story**: As a quem roda o `npm run migrate` no banco que já tem tarefas, I want que todo card continue com o tipo que tinha, so that a troca de lista geral para lista do projeto não apague nada.

**Acceptance Criteria**:

1. WHEN a migração roda num banco com projetos, tipos gerais e tarefas THEN o sistema SHALL deixar cada projeto com uma cópia de cada tipo geral (mesmo slug, rótulo e desativação) mais "Desenvolvimento" (`development`), e cada tarefa apontando para a cópia do próprio projeto com o mesmo rótulo.
2. WHEN a migração termina THEN o sistema SHALL não ter tipo sem projeto, e `task_types.project_id` SHALL ser not null.
3. WHEN a migração roda num banco sem projeto THEN o sistema SHALL apagar os tipos gerais sem erro.
4. WHEN a migração roda de novo sobre o banco já migrado THEN o sistema SHALL não duplicar nada (idempotente, banco.md §5).
5. WHEN o `down()` roda THEN o sistema SHALL voltar a uma lista geral (um tipo por rótulo, as tarefas apontando para ele) e remover as colunas e a tabela novas. O `down()` é o plano de volta em dev; em produção, a volta é o backup (B10).
6. WHEN um projeto novo é criado THEN o sistema SHALL gravar os 5 tipos padrão dele, na mesma transação.
7. WHEN o card rápido é criado THEN o banco SHALL aceitar `task_type_id` e `task_priority_id` nulos.

**Independent Test**: `tests/Feature/Projetos/MigracaoTiposDoProjetoTest.php`, `tests/Feature/Projetos/ProjectServiceTest.php`.

---

## Edge Cases

- WHEN o gestor perde o papel de Gestor de projetos THEN o sistema SHALL negar as ações de quem gere na próxima requisição (a `ProjectPolicy` lê o papel atual).
- WHEN a pessoa é tirada do projeto THEN o sistema SHALL negar tudo do quadro a ela na próxima requisição.
- WHEN a chave da rota é de outro projeto, ou não existe THEN o sistema SHALL responder 404 antes da Policy.
- WHEN o tipo geral tem rótulo igual em dois projetos depois da cópia THEN o sistema SHALL aceitar (o unique passa a ser por projeto).

---

## Requirement Traceability

| Requirement ID | Story | Phase | Status | Teste |
|---|---|---|---|---|
| TSK-01 | P1: Qualquer alocado usa o quadro (AC 1–3, 6) | Execute | ✅ Verified | `TaskPolicyTest.php:136-158`, `:178-193` (`view(Task)` pela rota), `:199-218` (`comment`, `attach`, `archive`, `delete` pelo Gate), `:220-235`, `:268-285`; `TaskServiceTest.php:347-349` |
| TSK-02 | P1: Dev não mexe na sprint nem cria suporte, tipo ou área (AC 4–5) | Execute | ✅ Verified | `TaskPolicyTest.php:237-266` |
| TSK-03 | P1: Não alocado e Comercial, 403 e nada muda (AC 2) | Execute | ✅ Verified | `TaskPolicyTest.php:160-175` |
| TSK-04 | P1: Negação auditada com a ação certa | Execute | ✅ Verified | `TaskPolicyTest.php:120-134` (`assertOneDenied`: uma linha, ação, `auditable_type`, `auditable_id`, `user_id`), usado em `:167-172`, `:242`, `:257-263`; `tests/Unit/AuditActionEnumTest.php:55,58` |
| MIG-01 | P1: Tipos do projeto sem perder dado (AC 1–5) | Execute | ✅ Verified | `MigracaoTiposDoProjetoTest.php:45-127`; `MigracaoCardDoQuadroTest.php:66-121` (`down()` da 000010 e da 000020) |
| MIG-02 | P1: Projeto novo nasce com os 5 tipos (AC 6) | Execute | ✅ Verified | `ProjectTest.php:71-75`; `B3TransacaoTest.php:44-75` (mesma transação) |
| MIG-03 | P1: Card sem tipo e sem prioridade (AC 7) | Execute | ✅ Verified | `TaskServiceTest.php:74-81` |

**Coverage:** 7 total, 7 mapped to tests, 0 unmapped; 7 verified (Verifier rodada 2, 19/10, `validation.md`). Veredito da rodada 2: FAIL só pelo sensor, com 3 sobreviventes na recusa de rótulo repetido do tipo, fora destes requisitos (Fix 6, só teste)

---

## Success Criteria

- [ ] `TaskPolicyTest`, `MigracaoTiposDoProjetoTest` e `ProjectServiceTest` verdes no MySQL 8.
- [ ] `php artisan migrate:fresh --seed` e `migrate:rollback` + `migrate` sem erro, e o `ExemploSeeder` com os cards de exemplo tipados.
- [ ] Verifier da skill em agente separado com PASS e mutação sem sobrevivente nas regras de permissão (cada delegação à `ProjectPolicy`/`SprintPolicy` trocada por `true`, o "entre o Backlog e outra coluna" trocado por "qualquer movimento" e por "nenhum", o `authorize()` tirado de cada ação).
