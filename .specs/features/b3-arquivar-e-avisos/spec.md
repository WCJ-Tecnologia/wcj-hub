# B3 PR 3: arquivar, desarquivar, excluir o card nunca usado e o sino de avisos — Specification

Feature `b3-arquivar-e-avisos` · **trilho completo só para o que o AGENTS.md manda parar**: a **exclusão de verdade** do card nunca usado (o que conta como uso, o que sai junto e o que acontece com os avisos que apontavam para ele) e o **escopo por dono** das rotas do sino (rota que recebe o id do aviso). Arquivar, desarquivar, a lista de Arquivados do quadro e o sino ficam no trilho leve; entram aqui como P2 só para o Verifier ter o contrato inteiro. **Sem Policy nova nem alterada**: `TaskPolicy::archive` e `TaskPolicy::delete` (gere o projeto) estão na `main` desde o PR 1 (spec `b3-quadro-e-card`, aprovada em 19/10). **Sem migração**: `tasks.archived_at`, `archive_reason` (B0), `archived_by` (PR 2) e `notifications` (B0) já existem. Fonte: [`docs/funcional/f3-quadro-e-card.md`](../../../docs/funcional/f3-quadro-e-card.md) §02 "Diálogo de arquivar", §03 (lista de arquivados), §04 "Na ficha" (Arquivar, Desarquivar, Excluir card), §07, §09; [`docs/necessidade.md`](../../../docs/necessidade.md) P22, P26, P57; [`docs/banco.md`](../../../docs/banco.md) `tasks` ("tirar do quadro, só gestor e admin"), `task_stage_movements`, `task_participants`, T4; contrato do kit em `resources/js/types/index.d.ts` (`HubNotification`, `NotificationsPayload`) e `resources/js/types/quadro.ts`. Escopo da skill: **Medium**.

**Approved by**: aguardando Lucas Scholz

Escrita com IA por Cauã Soares.

## Problem Statement

O kit da ficha (PR #12) já tem os botões *Arquivar*, *Desarquivar* e *Excluir*, e o do sino já lê `notifications` das props compartilhadas; o PR 2 manda `abilities.archive` e `abilities.delete` como `false` e o servidor não compartilha os avisos. Dois pontos não são decisão do bloco:

1. **Exclusão de verdade do card.** O `banco.md` diz o que é "nunca usado"; falta fechar o que sai junto e o que fica com os avisos (`notifications`) que apontam para a ficha excluída, que não têm FK para o card.
2. **Rota com id de outro dono.** Marcar aviso como lido recebe o UUID do aviso: aviso de outra pessoa não pode ser lido nem marcado (inegociável 8).

## Goals

- [ ] Só o card nunca usado se exclui de verdade, conferido com a linha travada; o que tem histórico só se arquiva.
- [ ] Nenhum aviso fica com link para uma ficha que não existe mais.
- [ ] Cada pessoa vê e marca só os próprios avisos.

## Out of Scope

| Feature | Reason |
|---|---|
| A IA arquivar ou excluir card | Não existe na fatia 1 (IA8; F3 §04 "Pela IA") |
| Definir o card pai arrastando com Alt | F3 §11.4: proposta de interação, não regra; o kit não tem |
| Tela "todos os avisos" | O F3 §07 não pede; o sino mostra os mais recentes |
| Linha do tempo do card | B7 (`history_url` segue nulo) |
| Recusar card pai ou tarefa de origem arquivado na ficha | Fluxo aberto (premissa de Projetos): a busca já esconde os arquivados, e o bug de produção pode apontar para a tarefa original já arquivada (P25). Pendência deixada ao PR 3 pela review do PR 2 (`PROJECT-PATTERNS.md`), decidida aqui: segue aceito |

---

## Assumptions & Open Questions

| Assumption / decision | Chosen default | Rationale | Confirmed? |
|---|---|---|---|
| Quem arquiva, desarquiva e exclui | `TaskPolicy::archive` (arquivar **e** desarquivar) e `TaskPolicy::delete`, as da `main`: quem gere o projeto. Sem ability nova: a negação do desarquivar grava a ação `archived` | F3 §04 e §09: "Arquivar, desarquivar, excluir card: gestor e Administrador"; a Policy não muda (spec `b3-quadro-e-card`, linha 27) | n |
| O que é "nunca usado" | O `banco.md` (`tasks`, "tirar do quadro"): **um movimento só** (o da criação), nenhuma linha em `time_entries`, `task_comments`, `task_attachments`, `task_pull_requests`, nenhum card com `origin_task_id` ou `parent_task_id` apontando para ele, `cross_tested_at` nulo. **Mais: fora de sprint** (`sprint_id` nulo) | O card comum que entra na sprint já se moveu; o de suporte entra na sprint sem trocar de coluna (P52), e P26 diz "não saiu do Backlog": card de suporte posto na sprint conta como uso | n |
| Ter participante, card pai, critério ou capa | Não conta como uso; critérios, participações e o movimento de criação saem junto (cascade) | P26, `banco.md` `task_participants`. Capa exige anexo, que já conta como uso | n |
| Card arquivado nunca usado | Também se exclui (arquivar não é uso) | P26 lista o que é uso; arquivar não está lá | n |
| A chave do card excluído | Não volta: o contador do projeto (T4) não recua | F3 §04 "A chave SGM-17 não volta a ser usada"; P16; `banco.md` T4 | y |
| Os avisos do card excluído | **Ficam, sem link**: os `TaskAssigned` e `TaskReturned` daquele card (`data.key`) passam a `url` nula na mesma transação; o texto continua | O contrato do kit já prevê: `HubNotification.url` "null quando o card foi excluído" (`types/index.d.ts`). Apagar o aviso tiraria da pessoa um fato que aconteceu; deixar o link daria 404 | **pergunta ao Lucas** |
| Card excluído com o projeto encerrado | Pode: excluir não é criar card (P7 fala só de card novo) | P7 | n |
| A auditoria | `archived` (o antes e o depois de `archived_at`, `archive_reason`, `archived_by`), `unarchived` (ação nova "Desarquivado", com o antes e o depois de etapa, sprint e arquivamento) e `deleted` (o retrato do card e o número de critérios que saíram), na mesma transação | G3; o enum ganha `unarchived` para a linha não virar "Outra ação" | n |
| Arquivar | Grava `archived_at`, `archive_reason` (até 500, obrigatório) e `archived_by`. **Não mexe** em sprint, etapa nem filhos | P26 ("sai do quadro e das contas"; as horas seguem nos indicadores); P57 ("arquivar o pai não mexe nos filhos"); `banco.md` linha do `parent_task_id` | n |
| As contas sem o arquivado | Quadro, capacidade, sprint, progresso do pai e Minhas horas já ignoram (`whereNull('archived_at')`); falta o **atraso** (`LateQuery`), que passa a ignorar também | P26 "sai das contas de prazo, atraso e capacidade" | n |
| Desarquivar | Limpa os três campos, limpa `sprint_id` e leva à etapa de papel `queue` (Backlog), no fim da ordem manual, com o movimento gravado **sem** retrabalho. Vale também para o card de suporte | `banco.md` `tasks` e `task_stage_movements` ("não contam: … desarquivar"); F3 §04 | n |
| A lista de Arquivados | Com o filtro *Arquivados*, o quadro mostra a lista (Chave, Título, Tipo, Arquivado em, Motivo), do mais recente para o mais antigo, com os outros filtros valendo; inclui o card arquivado numa etapa que depois foi removida | F3 §03 e §05; hoje a `BoardQuery` esconde o card de etapa removida também na lista | n |
| O sino | `notifications` nas props compartilhadas de toda tela logada: `unread_count` e os **10 mais recentes, não lidos primeiro**; `created_at` em `AAAA-MM-DD HH:MM` no fuso de São Paulo | Contrato do kit (`NotificationsPayload`) | n |
| Marcar como lido | `POST /avisos/{id}/lida` e `POST /avisos/lidas`, as rotas que o kit propôs. O aviso é procurado **só entre os da pessoa logada**: de outra pessoa, 404 (não 403, para não confirmar que o id existe). Ação sobre a própria conta (lista do A5) | Inegociável 8; `RotasAutorizadasTest` | n |

**Open questions:** uma, para o Lucas Scholz: os avisos do card excluído ficam sem link (o padrão escolhido, pelo contrato do kit) ou saem junto?

---

## User Stories

### P1: Só o card nunca usado se exclui de verdade ⭐ MVP

**Acceptance Criteria**:

1. WHEN quem gere o projeto exclui um card nunca usado THEN o sistema SHALL apagar o card, os critérios, as participações e o movimento de criação, e gravar a linha `deleted` da auditoria na mesma transação.
2. IF o card tem um segundo movimento, horas, comentário, anexo, link de PR, teste cruzado marcado, sprint, card filho ou bug de produção apontando para ele THEN o sistema SHALL recusar com "SGM-17 já foi usado: arquive em vez de excluir." e nada muda.
3. WHEN o card nunca usado tem participante, card pai ou critério THEN o sistema SHALL excluir mesmo assim.
4. WHEN o card é excluído THEN o próximo card do projeto SHALL nascer com o número seguinte ao último já dado, sem reaproveitar o do excluído (T4).
5. IF o dev ou quem não vê o projeto tenta excluir THEN o sistema SHALL responder 403, gravar a negação `deleted` e nada muda.
6. WHEN a ficha abre THEN `abilities.delete` SHALL ser verdadeiro só para quem gere e só no card nunca usado.
7. WHEN a exclusão dá certo THEN o sistema SHALL voltar ao quadro do projeto com "SGM-17 excluído."

### P1: Os avisos do card excluído ficam sem link ⭐ MVP

**Acceptance Criteria**:

1. WHEN um card é excluído THEN os avisos `TaskAssigned` e `TaskReturned` dele SHALL ficar com `url` nula e o mesmo texto, na mesma transação da exclusão.
2. WHEN um card é excluído THEN os avisos dos outros cards SHALL continuar com o link.

### P1: Cada pessoa só vê e marca os próprios avisos ⭐ MVP

**Acceptance Criteria**:

1. WHEN uma pessoa logada abre qualquer tela THEN as props SHALL trazer só os avisos dela, com a contagem dos não lidos.
2. WHEN a pessoa marca um aviso dela como lido THEN o sistema SHALL gravar `read_at` só nele.
3. IF a pessoa manda o id de um aviso de outra pessoa THEN o sistema SHALL responder 404 e o aviso SHALL continuar não lido.
4. WHEN a pessoa marca todos como lidos THEN o sistema SHALL marcar só os dela.

### P2: Arquivar e desarquivar

**Acceptance Criteria**:

1. WHEN quem gere arquiva com motivo THEN o sistema SHALL gravar `archived_at`, `archive_reason` e `archived_by`, sem mudar etapa, sprint nem filhos, e o card SHALL sair do quadro e do atraso.
2. IF o motivo está vazio ou passa de 500 caracteres THEN o sistema SHALL recusar no campo e nada muda.
3. IF o card já está arquivado THEN arquivar de novo SHALL ser recusado com "Card arquivado não muda: desarquive antes."
4. WHEN quem gere desarquiva THEN o sistema SHALL limpar o arquivamento e a sprint e levar o card ao fim do Backlog, gravando o movimento sem retrabalho.
5. IF o dev tenta arquivar ou desarquivar THEN o sistema SHALL responder 403 e nada muda.
6. WHEN o filtro *Arquivados* está ligado THEN o quadro SHALL listar os arquivados do projeto, do mais recente para o mais antigo, com chave, título, tipo, data e motivo.

---

## Requirement Traceability

| Requirement ID | Story | Phase | Status | Teste |
|---|---|---|---|---|
| EXC-01 | Só o card nunca usado se exclui | Execute | ✅ Verified (Verifier rodada 2) | `TaskArchiveTest.php:195-275`; `TaskArchivePolicyTest.php:71-120`; `TaskUsageQueryTest.php:33`, `:48`; `B3ArquivarTransacaoTest.php:104-140`, `:150` (a trava da linha e a exclusão num commit só) |
| EXC-02 | Os avisos do card excluído ficam sem link | Execute | ✅ Verified (Verifier rodada 2) | `TaskArchiveTest.php:279-297` (`TaskAssigned` e `TaskReturned`, o texto igual); `B3ArquivarTransacaoTest.php:126-154` (na mesma transação) |
| AVI-01 | Só os próprios avisos | Execute | ✅ Verified | `NotificationsTest.php:51-127` |
| ARQ-01 | Arquivar e desarquivar | Execute | ✅ Verified | `TaskArchiveTest.php:78-191`, `:301-319`; `TaskArchivePolicyTest.php:98-104`; `BoardTest.php:141-142` |

**Coverage:** 4 total, 4 mapped to tests, 0 unmapped; 4 verified (Verifier rodada 2, 20/10, `validation.md`: PASS). Sensor: rodada 1, 50/55 contados mortos e 5 sobreviventes; rodada 2, os 5 + 3 de confirmação, 8/8 mortos; consolidado 55/55 contados, 2 equivalentes.

## Success Criteria

- [x] `TaskArchiveTest`, `TaskArchivePolicyTest`, `TaskUsageQueryTest`, `B3ArquivarTransacaoTest` e `NotificationsTest` verdes no MySQL 8.
- [x] Um teste que falha quando uma FK nova aponta para `tasks` sem ser classificada como uso ou como "sai junto" (B4 e B6 vão criar tabelas ligadas ao card).
- [x] Verifier em agente separado com mutação na regra de "nunca usado" (cada condição), na trava da linha, no escopo por dono do aviso e no `authorize()` de cada rota.
- [x] e2e de fumaça: arquivar pela ficha, achar no filtro *Arquivados*, desarquivar; excluir o card novo.
