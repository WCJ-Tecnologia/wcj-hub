# B3 PR 2: a ficha do card — permissões, exclusões de verdade e colunas que a ficha mostra — Specification

Feature `b3-ficha` · **trilho completo só para o que o AGENTS.md manda parar**: a Policy alterada e a nova (horas propostas pelo dev; comentário só do autor), as **exclusões de verdade** (comentário, anexo, critério, link de PR) e a **migração aditiva** das quatro colunas de "quem" que o kit da ficha mostra e o banco não guarda. O resto do PR 2 (ficha `GET /projetos/{id}/cards/{chave}`, edição dos campos, card pai, tarefa de origem, capa, critérios, PRs, comentários, anexos, horas, aviso `TaskAssigned`) é trilho leve, com a spec na descrição do PR. Fonte: [`docs/funcional/f3-quadro-e-card.md`](../../../docs/funcional/f3-quadro-e-card.md) §02, §04 "Na ficha", §09, §10; [`docs/funcional/f5-sprint-e-horas.md`](../../../docs/funcional/f5-sprint-e-horas.md) §02 "Horas e data prevista"; [`docs/banco.md`](../../../docs/banco.md) (`tasks`, `task_comments`, `task_attachments`, `task_pull_requests`, `task_acceptance_criteria`); contrato do kit em `resources/js/types/quadro.ts` (bloco "Ficha do card"). Escopo da skill: **Medium**.

Depende do PR 1 (#17: `TaskPolicy`, migração da seção 7) e reaproveita o `TaskAssignmentService` do #16 (B5b).

**Approved by**: _aguardando Lucas Scholz_

Escrita com IA por Cauã Soares.

## Problem Statement

A ficha do kit (PR #12) chama 20 rotas novas. Três grupos delas não são decisão do bloco:

1. **Permissão nova.** O dev **propõe** horas e o gestor **decide** (P27); o comentário se edita e se exclui **só pelo autor** (F3 §10.10). A `TaskPolicy` do PR 1 não tem nenhuma das duas.
2. **Exclusão de verdade** de quatro registros de negócio: comentário, anexo (com o arquivo), critério de aceite e link de PR. O AGENTS.md exige aprovação humana para qualquer `delete` de registro de negócio.
3. **Dado que o banco não tem.** O contrato do kit mostra quem colou o link do PR (`TaskPullRequest.added_by`), quem enviou o anexo (`TaskAttachment.uploaded_by`), quem propôs a reestimativa (`TaskDetail.proposed_by`) e quem arquivou (`TaskDetail.archived.by`). Nenhuma dessas colunas existe no `banco.md`, e o módulo não lê `audit_logs` (teste A9).

## Goals

- [ ] O dev alocado propõe horas; só quem gere define direto e aprova ou recusa a proposta.
- [ ] Só o autor edita e exclui o próprio comentário, enquanto ainda vê o projeto.
- [ ] As quatro exclusões apagam só o que a regra deixa, com a auditoria na mesma transação, e o anexo apaga o arquivo junto.
- [ ] A ficha mostra "quem" sem ler a auditoria.

## Out of Scope

| Feature | Reason |
|---|---|
| Arquivar, desarquivar e excluir o card | PR 3 do B3. Na ficha do PR 2, `abilities.archive` e `abilities.delete` vão `false` (os botões somem) até as rotas existirem |
| Sino de avisos | PR 3. O `TaskAssigned` grava no `notifications` já no PR 2; o sino só lê |
| Evidência do QA por critério (`criterion_id` do anexo) | B4 |
| Linha do tempo (`history_url`) | B7; vai `null` e o botão fica desabilitado, como o kit prevê |

---

## Assumptions & Open Questions

| Assumption / decision | Chosen default | Rationale | Confirmed? |
|---|---|---|---|
| Definir horas direto e decidir a proposta | `SprintPolicy::manageTasks`, a mesma da tela de sprints e do *Montar sprint*; o `TaskHoursService` do B5 grava | Um jeito só; F3 §04 e F5 §09 | |
| Propor horas | Ability nova `TaskPolicy::proposeHours(Task)`: vê o projeto **e não gere** (quem gere define direto, não propõe). O `TaskHoursService` ganha `propose()`, que grava `proposed_hours` e `proposed_by` | F3 §04 "Desenvolvedor alocado"; P27 | |
| Comentário: editar e excluir | **`TaskCommentPolicy` nova**: `update` e `delete` = autor **e** ainda vê o projeto (quem saiu do projeto não mexe mais, como no lançamento de horas do B5). O Administrador e o gestor **não** editam nem excluem o comentário dos outros | F3 §10.10 ("só o próprio") | |
| Excluir comentário | Exclusão de verdade, pelo autor; o antes (texto) fica na auditoria | F3 §04 | |
| Excluir anexo | Exclusão de verdade, qualquer alocado (`TaskPolicy::attach`); o Service apaga o arquivo e a linha juntos, e a FK limpa a capa (P59). Se o arquivo já não existe no disco, a linha sai do mesmo jeito | F3 §04, §10.11; banco.md `task_attachments` | |
| Excluir critério | Exclusão de verdade, qualquer alocado (`TaskPolicy::update`), só enquanto ele e cada subitem estão *A testar* (T1); os subitens saem junto (cascade); excluir não renumera os outros (T9; ver a pergunta do número abaixo). Testado: recusa com mensagem | F3 §04 e §10.9 | |
| Excluir link de PR | Exclusão de verdade, qualquer alocado (`TaskPolicy::update`) | F3 §04 | |
| **Quem colou, enviou, propôs, arquivou** (pergunta) | **Recomendo migração aditiva, 4 colunas nulas:** `task_pull_requests.user_id`, `task_attachments.user_id` (fk users, restrict), `tasks.proposed_by` e `tasks.archived_by` (fk users, restrict). Nulas porque as linhas de antes não têm o dado; a tela mostra "—". **Alternativa:** mudar o contrato do kit para `PersonRef \| null` e não guardar. | O kit já desenha o "quem"; ler `audit_logs` do módulo fura o A9 | **pergunta ao Lucas** |
| **Número do critério depois de excluir o último** (pergunta) | **Hoje: "maior número + 1"** (banco.md, `task_acceptance_criteria`: "Número novo = maior number da tarefa no mesmo pai + 1"). Excluir um do meio não renumera nem reaproveita; **excluir o último faz o próximo nascer com o número dele**. O diálogo do F3 §04 promete "O número não será reaproveitado". Para cumprir o F3 sempre, seria preciso um contador por tarefa (coluna nova). | F3 × banco.md divergem: decisão do Lucas (AGENTS.md, inegociável 5) | **pergunta ao Lucas** |
| Limites do anexo | 20 MB por arquivo, até 10 por envio; imagem (jpg, png, gif, webp), PDF, Office (doc, docx, xls, xlsx, ppt, pptx, odt, ods), csv, txt e zip, conferidos pelos bytes. Sem SVG nem HTML (podem carregar script). Em `config/hub.php`, ajustáveis pelo `.env` (`HUB_ATTACHMENT_MAX_KB`, `HUB_ATTACHMENT_MAX_FILES`). Imagem abre na página; o resto baixa, com `nosniff` | F3 §10.11 ("limite do template", que não define); seguranca.md §7 | |
| Tamanho do anexo e "editado em" | Sem coluna: o tamanho vem do disco (`Storage::size`) ao montar a ficha; "editado" = `updated_at` depois de `created_at` | Dado que já existe | |
| Abilities da ficha | `update`, `comment`, `attach`, `move` = `TaskPolicy`; `set_hours` e `decide_hours` = `SprintPolicy::manageTasks`; `propose_hours` = `TaskPolicy::proposeHours`; `manage_sprint`, `manage_types`, `manage_areas` como no quadro; `archive` e `delete` = `false` até o PR 3; o `can` de cada comentário vem da `TaskCommentPolicy` | Abilities só escondem botão | |
| Mapeamento da negação | `proposeHours` → `updated`; `update`/`delete` do comentário já mapeiam | Sem mapeamento a tela mostra "Outra ação" | |

**Open questions:** as duas em negrito acima (as 4 colunas de "quem"; o número do critério depois de excluir o último).

---

## User Stories

### P1: Horas: o dev propõe, o gestor decide ⭐ MVP

1. WHEN o dev alocado propõe horas THEN o sistema SHALL gravar `proposed_hours` (e `proposed_by`, se a migração for aprovada), sem mexer em `estimated_hours` nem em `due_on`.
2. IF quem gere tenta propor THEN o sistema SHALL responder 403 (ele define direto).
3. IF o dev tenta definir as horas direto, aprovar ou recusar a proposta THEN o sistema SHALL responder 403 e nada muda.
4. WHEN quem gere aprova THEN a proposta SHALL virar `estimated_hours` e refazer a data prevista (o `TaskHoursService` do B5); recusar só limpa a proposta.

### P1: Comentário só do autor ⭐ MVP

1. WHEN o autor, ainda no projeto, edita ou exclui o próprio comentário THEN o sistema SHALL gravar, com o antes na auditoria.
2. IF outra pessoa (inclusive o Administrador e o gestor) tenta editar ou excluir THEN o sistema SHALL responder 403 e manter o comentário.
3. IF o autor saiu do projeto THEN o sistema SHALL responder 403.

### P1: Exclusões de verdade só do que a regra deixa ⭐ MVP

1. WHEN um alocado exclui um anexo THEN o sistema SHALL apagar o arquivo e a linha, e o card que o tinha como capa SHALL ficar sem capa.
2. WHEN um alocado exclui um critério *A testar* THEN o sistema SHALL apagar ele e os subitens, sem renumerar os outros (T9).
3. IF o critério ou um subitem já foi testado THEN o sistema SHALL recusar com "CA_3 já foi testado: edite em vez de excluir." e manter tudo.
4. WHEN um alocado exclui um link de PR THEN o sistema SHALL apagar a linha.
5. Em toda exclusão, a linha `deleted` da auditoria SHALL ir na mesma transação, com o antes.
6. IF quem não vê o projeto tenta qualquer exclusão THEN o sistema SHALL responder 403 e nada muda.

### P2: Quem colou, enviou, propôs e arquivou (se a migração for aprovada)

1. WHEN alguém cola um link, envia um anexo ou propõe horas THEN o sistema SHALL gravar quem.
2. WHEN a ficha abre THEN ela SHALL mostrar o nome, ou "—" nas linhas de antes da migração.

---

## Requirement Traceability

| Requirement ID | Story | Phase | Status |
|---|---|---|---|
| FCH-01 | Horas: propor, definir, decidir | Design | Pending |
| FCH-02 | Comentário só do autor | Design | Pending |
| FCH-03 | Exclusões de verdade (anexo, critério, PR, comentário) | Design | Pending |
| FCH-04 | Negação auditada com a ação certa | Design | Pending |
| MIG-04 | As 4 colunas de "quem" (se aprovada) | Design | Pending |

## Success Criteria

- [ ] `TaskDetailPolicyTest`, `TaskCommentPolicyTest` e os testes de exclusão verdes no MySQL 8.
- [ ] Verifier em agente separado com mutação nas regras de permissão (proposeHours, autor do comentário, `authorize()` de cada rota) e de exclusão (o "só A testar" do critério, o arquivo do anexo).
