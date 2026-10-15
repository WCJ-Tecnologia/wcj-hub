# B2 passo 2: Policy dos motivos de retrabalho e ability `manage_lists` — Specification

Feature `b2-passo-2-configuracao` · **trilho completo só para a Policy nova e a ability nova** (AGENTS.md, "Trilhos" e "O que exige aprovação humana"); o resto do B2 passo 2 (Pessoas, Etapas, Áreas, Padrões, "Novo cliente" inline) é trilho leve, com a spec na descrição do PR, e usa só a `ProjectPolicy::manage` que já existe. Fonte: [`docs/funcional/f2-projeto-e-configuracao.md`](../../../docs/funcional/f2-projeto-e-configuracao.md) §02 a §04 e §09, [`docs/escopo.md`](../../../docs/escopo.md) B2 ("Listas gerais: só admin"), [`docs/necessidade.md`](../../../docs/necessidade.md) (G2, G3, G7, P40), [`docs/banco.md`](../../../docs/banco.md) (`rework_reasons`). Escopo da skill: **Small** (uma Policy, uma ability, uma tela).

**Approved by**: Lucas Scholz, 2026-10-15

**Aprovada por Lucas Scholz em 15/10/2026, via sessão da docs/b3-partida** ("aprovados os 2": a `ReworkReasonPolicy` nova com todas as abilities restritas ao Administrador e a ability `manage_lists` no front para o item do menu). Escrita com IA por Lucas Scholz (D-GER-34).

## Problem Statement

Os motivos de retrabalho são uma lista geral que só o Administrador edita (G7, P40, R11). A `ProjectPolicy` não serve, porque o motivo não é de projeto. Sem uma Policy própria, a tela `/admin/listas` não teria `authorize()`, a negação não viraria `denied` na auditoria (G3) e o teste A5 (`RotasAutorizadasTest`) reprovaria as rotas. O menu precisa de uma ability para mostrar *Listas* só a quem pode.

## Goals

- [ ] Só o Administrador vê e edita os motivos de retrabalho; qualquer outro papel recebe 403 em toda rota, e nada muda.
- [ ] Toda negação vira linha `outcome = denied` em `audit_logs`, com a ação certa (não o nome cru da ability).
- [ ] O item *Listas* do menu aparece só para quem a Policy deixa.

## Out of Scope

| Feature | Reason |
|---|---|
| Lista geral de tipos de tarefa | Saiu do F2 em 14/10: tipo é do projeto (F3 §11 item 9); entra com a migração do B3 PR 1 |
| Usar o motivo numa volta para desenvolvimento | É do movimento do card (B3, P39); não passa por esta Policy |
| Escrita pela IA (MCP) | Nenhuma das 6 ferramentas edita a lista (F2 §04, IA14) |

---

## Assumptions & Open Questions

| Assumption / decision | Chosen default | Rationale | Confirmed? |
|---|---|---|---|
| Quem edita a lista | Só o Administrador, em todas as abilities (`viewAny`, `create`, `update`, `deactivate`, `reactivate`, `delete`) | G7, F2 §09, escopo B2 | y |
| Ability do front | `manage_lists` = `ReworkReasonPolicy::viewAny`, só para mostrar o item *Listas* do menu | Abilities só escondem botão; a Policy decide (AGENTS.md, Autorização) | y |
| Mapeamento da negação na auditoria | `AuditActionEnum::forAbility` já cobre as seis abilities (`viewAny` → `viewed`, `create` → `created`, `update` → `updated`, `deactivate`, `reactivate`, `delete`); nada a acrescentar | Sem mapeamento a linha gravaria o nome cru; aqui todos já mapeiam | y |
| `User` | Sem método novo: as abilities do front saem das Policies pelo `HandleInertiaRequests`, como as do B1 | O `User` não guarda abilities (só `isAdmin()` e afins) | y |
| O "Outro" | A Policy permite o `update` ao Administrador; o "não desativa, não exclui, sempre pede texto" é regra do `ListService` (recusa por `DomainException`) e a linha some os botões pelo Presenter | P40; regra no servidor, no ponto que efetiva (inegociável 9) | y |
| Já usado | Excluir só o nunca usado (`deleteIfUnused`), recusa com toast; desativar o resto | G2 | y |

**Open questions:** none - all resolved or logged above.

---

## User Stories

### P1: Só o Administrador mantém os motivos de retrabalho ⭐ MVP

**User Story**: As a Administrador, I want ser o único a ver e editar os motivos de retrabalho, so that a lista que todo projeto usa não muda por quem não tem o papel.

**Why P1**: É a regra G7 e a única Policy nova do bloco.

**Acceptance Criteria**:

1. WHEN o Administrador abre `/admin/listas` THEN o sistema SHALL responder 200 com os motivos, o "Outro" por último.
2. IF um usuário com papel Gestor de projetos, Desenvolvedor ou Comercial abre `/admin/listas` THEN o sistema SHALL responder 403.
3. IF um usuário que não é Administrador envia criar, editar, desativar, reativar ou excluir motivo THEN o sistema SHALL responder 403.
4. IF um usuário que não é Administrador envia qualquer escrita de motivo THEN o sistema SHALL manter todos os motivos sem mudança (nome, pede texto, situação) e nenhuma linha `success` de `rework_reason` na auditoria.
5. WHEN a Policy nega qualquer uma das seis ações THEN o sistema SHALL gravar em `audit_logs` uma linha `outcome = denied`, `auditable_type = rework_reason`, com a ação `viewed`, `created`, `updated`, `deactivated`, `reactivated` ou `deleted`.
6. WHEN o Administrador cria, edita, desativa, reativa ou exclui um motivo THEN o sistema SHALL gravar a linha `success` correspondente na auditoria, na mesma transação.
7. IF o Administrador tenta desativar ou excluir o "Outro", ou desligar o "pede texto" dele THEN o sistema SHALL recusar com a mensagem do `ListService` e manter o "Outro" sem mudança.
8. IF o Administrador tenta excluir um motivo já usado em movimento THEN o sistema SHALL recusar com "Motivo já usado em alguma volta para desenvolvimento: desative em vez de excluir" e manter o motivo.

**Independent Test**: `tests/Feature/Projetos/ReworkReasonTest.php`.

---

### P1: Item *Listas* no menu só para quem pode ⭐ MVP

**User Story**: As a usuário do Hub, I want ver no menu só o que posso abrir, so that eu não caio num 403.

**Acceptance Criteria**:

1. WHEN a página carrega para um Administrador THEN o sistema SHALL mandar `auth.user.abilities.manage_lists = true`.
2. WHEN a página carrega para Gestor de projetos, Desenvolvedor ou Comercial THEN o sistema SHALL mandar `auth.user.abilities.manage_lists = false`.
3. WHERE `manage_lists` é falso, the sistema SHALL esconder o item *Listas* do menu e a rota SHALL continuar respondendo 403.

**Independent Test**: `tests/Feature/Nucleo/AbilitiesTest.php`.

---

## Edge Cases

- WHEN o Administrador é rebaixado para outro papel THEN o sistema SHALL negar na próxima requisição (a Policy lê o papel atual).
- WHEN o motivo não existe THEN o sistema SHALL responder 404 antes da Policy (route binding).

---

## Requirement Traceability

| Requirement ID | Story | Phase | Status |
|---|---|---|---|
| LST-01 | P1: Só o Administrador mantém os motivos (AC 1–4) | Execute | Implementing |
| LST-02 | P1: Negação auditada com a ação certa (AC 5–6) | Execute | Implementing |
| LST-03 | P1: Regras do "Outro" e do já usado (AC 7–8) | Execute | Implementing |
| LST-04 | P1: Ability `manage_lists` e o item do menu (AC 1–3) | Execute | Implementing |

**Coverage:** 4 total, 4 mapped to tests, 0 unmapped

---

## Success Criteria

- [ ] `ReworkReasonTest` e `AbilitiesTest` verdes no MySQL 8.
- [ ] Verifier da skill em agente separado com PASS e mutação sem sobrevivente nas regras de permissão (cada `isAdmin()` trocado por `true`, o `authorize()` tirado de cada ação, a ability sem a Policy).
