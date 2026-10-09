# Code Review — `feat/b2-projeto-e-configuracao` · Ciclo 2

**Data:** 2026-10-09
**História:** PR-6 ([WCJ-Tecnologia/wcj-hub#6](https://github.com/WCJ-Tecnologia/wcj-hub/pull/6), "B2 passo 1: lista, cadastro, encerrar e excluir projeto", autor Victor Costa / Vitinho)
**Branch:** `feat/b2-projeto-e-configuracao` (`783a99b`, 2 commits) · **Base:** `main` (`2fb5680`, branch do ambiente)
**Spec:** não mapeada (trilho leve: spec na descrição do PR)
**Revisor:** Claude Code (sessão de revisão, contexto zerado; worktree `wcj-hub-pr6`)
**Ciclos anteriores:** [Ciclo 1](pr-6-code-review-ciclo-1.md) (rodado do lado do Vitinho, 09/10, sobre `fa9c126`)
**Escopo deste ciclo:** o código é o mesmo do ciclo 1 (`fa9c126`; o 2º commit só acrescenta o relatório). O que mudou foi a **base**: desde o ciclo 1 entraram na `main` o B1 (#5), o CR-1-01 do B1 (#7) e o front atômico (#8). Este ciclo reverifica os 5 achados do ciclo 1 contra a `main` atual, mede o que o rebase vai exigir e revisa o que o ciclo 1 não cobriu (hooks de lista, trilho leve, contrato `ClientManagement`, N+1 na Policy).

**Como este ciclo verificou:** CI do PR verde em `783a99b` (PHP, JS, e2e, secrets), mas sobre a `main` antiga; `git merge-tree --write-tree main HEAD` para os conflitos; leitura integral dos 27 arquivos; `rg` de segredo no diff (nenhum); F2 (`docs/funcional/f2-projeto-e-configuracao.md`), escopo B2 e `banco.txt` (`projects`, `project_members`, `board_stages`) como régua.

---

## Status dos achados anteriores

| ID | Achado | Status | Evidência no código atual |
|---|---|---|---|
| CR-1-01 | Sem auditoria da negação e sem o padrão de autorização do Núcleo (branch da `main` antiga) | ❌ **Não resolvido** (agora acionável: o B1 está na `main` `a1a052e`) | `StoreProjectRequest.php:24-32` ainda tem o `authorize(): bool` próprio; `ProjectController.php:50,59` ainda chama `authorize()` de novo; `AuditActionEnum::forAbility()` da `main` não mapeia `manage`/`viewAny` de projeto; `ProjectPermissionTest` segue sem conferir `outcome = denied` |
| CR-1-02 | Linha da lista montada no controller | ❌ Não resolvido | `ProjectController.php:98-116` (`row()`) |
| CR-1-03 | `guardPrefix` captura `UniqueConstraintViolationException` solto | ❌ Não resolvido | `ProjectService.php:129-138` |
| CR-1-04 | `'%'.$termo.'%'` à mão | ❌ Não resolvido | `ProjectQuery.php:25,36-38` |
| CR-1-05 | Modal × telas `/projetos/novo` e `/projetos/{id}/editar` do escopo | ✅ **Decidido pelo Lucas Scholz em 09/10/2026: fica o modal** (o que o PR fez). Resta só alinhar o escopo B2 ("Telas") no rebase | `routes/web.php:76-83`; DA-11 / FP-08 do `PROJECT-PATTERNS.md` |

Resolvidos: 1 (CR-1-05, por decisão) · Abertos: 4 · Regressões: 0 (nada mudou no código).

---

## Achados do Ciclo 2

## [ALTO] CR-2-01 — O rebase sobre a `main` atual é maior que o ciclo 1 previu: 9 conflitos, o front inteiro no modelo antigo (ADR-003) e dois arquivos que substituiriam os da `main`

**Arquivo:** `resources/js/pages/projects/index.tsx`, `resources/js/components/projects/*`, `resources/js/components/app-sidebar.tsx`, `resources/js/components/status-pill.tsx`, `tests/Support/CriaProjeto.php`, `.specs/code-reviews/PROJECT-PATTERNS.md`, `e2e/smoke.spec.ts`
**Trecho atual:**
```text
$ git merge-tree --write-tree main HEAD
CONFLICT (add/add):        .specs/code-reviews/PROJECT-PATTERNS.md
CONFLICT (content):        app/Http/Middleware/HandleInertiaRequests.php
CONFLICT (content):        app/Platform/Enums/AuditActionEnum.php
CONFLICT (content):        app/Providers/AppServiceProvider.php
CONFLICT (content):        e2e/smoke.spec.ts
CONFLICT (modify/delete):  resources/js/components/app-sidebar.tsx   (apagado na main pelo #8)
CONFLICT (modify/delete):  resources/js/components/status-pill.tsx   (apagado na main pelo #8)
CONFLICT (content):        resources/js/types/index.d.ts
CONFLICT (add/add):        tests/Support/CriaProjeto.php              (o B1 criou um trait com o mesmo nome)
```
```tsx
// pages/projects/index.tsx:5-15 — caminhos que não existem mais na main (#8, ADR-003)
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable, type Column } from '@/components/data-table';
import { StatusPill } from '@/components/status-pill';
import { Select } from '@/components/ui/select';          // Select nativo: a main usa o do Radix
import AppLayout from '@/layouts/app-layout';
// project-form-dialog.tsx:138,151 — <Input type="date">: a main manda WDatePickerMolecule
```

**Ajuste necessário:**
Rebase (ou merge da `main`) resolvendo os 9 conflitos e **portando o front para a estrutura atômica**, como o #8 fez com o B1: tela em `components/pages/w-projects-page/`, filtros e modal em `organisms/w-project-filters-organism/` e `w-project-form-dialog-organism/`, Select do Radix (sentinela `all`), `WDatePickerMolecule` no Início e na Entrega, `WStatusPillAtom` com o dicionário `project` (Aberto / Encerrado), item "Projetos" no `NAV` de `w-app-sidebar-organism`, `.md` por componente e stories da tela, e o wrapper de uma linha em `resources/js/pages/projects/index.tsx`. `tests/Support/CriaProjeto.php`: unir os dois traits (o do B1 tem `project()` e `supportCard()`, usados pelos testes do Núcleo; o do B2 tem `projeto()`, `alocar()`, `etapa()`, `tarefa()`), sem apagar nenhum método. `PROJECT-PATTERNS.md`: ficar com o da `main` (238 linhas, B1 ciclos 1 a 6 + PR-8) e acrescentar o que a branch anotou; a versão da branch (30 linhas, formato próprio) substituiria o acumulado do projeto. `e2e/smoke.spec.ts`: só acrescentar `/projetos` à lista da `main` (os títulos mudaram: "Auditoria", "Meu perfil") e, se couber, a linha no `mobile.spec.ts`.

**Motivo:**
O ciclo 1 (CR-1-01) pediu o rebase pelo lado do backend (helpers do Núcleo). Desde então o #8 mudou o front inteiro: `components/ui/` virou `components/shadcn/`, `layouts/` sumiu, `DataTable`/`StatusPill`/`RowActions`/`SearchInput` viraram `W*` em pastas próprias, e o Select nativo e o `<input type="date">` deixaram de existir no projeto (ADR-003, aprovado pelo Lucas em 09/10). Os 4 arquivos de front deste PR importam 11 caminhos que não existem mais; `app-sidebar.tsx` e `status-pill.tsx` foram apagados na `main`. Sem o porte, a branch não compila depois do rebase.

**Sugestão:**
Usar o #8 como molde: `w-users-page` (tela com filtros, modal, confirmações, `useListVisit` + `WListBodyMolecule`, `useRowAction`), `w-client-form-dialog-organism` (Select do Radix dentro do `WFormFieldMolecule`, com `id`/`aria-*` no gatilho) e `w-audit-log-filters-organism` (date picker com estado local). O `dateBr()` de `lib/utils.ts` pode ficar: a `main` ainda não tem equivalente (o `WDatePickerMolecule` formata só o próprio botão).

**Impacto:**
Mergear sem o porte quebra o build da `main`; resolver os add/add pelo lado da branch apaga o `PROJECT-PATTERNS.md` acumulado e os helpers de teste do Núcleo.

---

## [MÉDIO] CR-2-02 — A tela não usa os hooks de lista do projeto: `useState(busy)` copiado (CV-09) e sem estados de carregando e erro (CV-07)

**Arquivo:** `resources/js/pages/projects/index.tsx:50,105-114,225-250`
**Trecho atual:**
```tsx
const [busy, setBusy] = useState(false);
// …
const runPending = () => {
    const options = { preserveScroll: true, onStart: () => setBusy(true), onFinish: () => { setBusy(false); setPending(null); } };
    if (pending.kind === 'delete') router.delete(url, options);
    else router.post(`${url}/${…}`, {}, options);
};
// RowActions sem busy/disabled; DataTable sem ListBody/useListVisit em volta
```

**Ajuste necessário:**
`useRowAction()` no lugar do `useState(busy)` (`rowAction.post`/`rowAction.del`, `isBusy(row)`/`isLocked(row)` no `WRowActionsMolecule`, `rowAction.busy` no `loading` do `WConfirmDialogMolecule`), e `useListVisit()` + `WListBodyMolecule` em volta da tabela, como em `w-users-page`, `w-clients-page` e `w-contacts-page`.

**Motivo:**
O B1 provou (CR-1-17, CR-2-03 e CR-2-07 do B1; `e2e/clique-duplo.spec.ts`) que no Inertia 2 uma visita não-GET cancela a outra: dois cliques em "Encerrar" ou "Excluir" em linhas diferentes antes da primeira resposta chegam os dois ao servidor e o navegador descarta um. O `useState(busy)` só trava os botões da confirmação; as ações de outras linhas seguem clicáveis. O `AGENTS.md` ("Um jeito só" e a tabela de componentes) registra `useRowAction` como "a única trava de clique duplo das ações de linha" e `useListVisit`/`WListBodyMolecule` como os estados de lista (`.ai/stacks/ui.md` §2: "estado vazio, de erro e de carregando existem em toda tela").

**Sugestão:**
Copiar o esqueleto de `w-clients-page.tsx` (confirmações em tabela `CONFIRM`, `closeConfirm`, `rowActions` com `busy`/`disabled`). Entra junto do porte do CR-2-01.

**Impacto:**
Clique duplo em ações de linha diferentes com uma só efetivada em silêncio; lista sem aviso de rede caída e sem "carregando".

---

## [MÉDIO] CR-2-03 — Por linha da lista, a Policy consulta o banco para decidir `can.manage` (N+1), contra o padrão de lote do projeto

**Arquivo:** `app/Domains/Projetos/Policies/ProjectPolicy.php:51-58`, `app/Domains/Projetos/Models/Project.php:106-109`, `app/Domains/Projetos/Controllers/ProjectController.php:112-115`
**Trecho atual:**
```php
// ProjectPolicy::manages — chamado por linha no row() do controller
return $project->manager_id === $user->id || $project->hasMember($user);
// Project::hasMember — uma consulta por chamada
return $this->members()->where('user_id', $user->id)->exists();
```

**Ajuste necessário:**
Carregar o vínculo em lote na consulta da lista (`ProjectQuery::paginate`: `->withExists(['members as is_member' => fn ($q) => $q->where('user_id', $user->id)])`) e fazer `hasMember()` usar o atributo quando ele vier carregado, caindo na consulta só sem ele (as Policies por ID continuam valendo).

**Motivo:**
Para o Gestor de projetos, cada projeto da página em que ele não é o `manager_id` custa uma consulta (`can('manage')` → `manages()` → `hasMember()`); 20 por página hoje, e o mesmo caminho vai ser chamado pelo quadro (B3) e pelos indicadores (B7). O projeto já resolveu esse padrão no B1: `MemoizedClientManagement` e `openProjectsCountByClient` existem porque "lista pede em lote, nunca por linha" (CV-04 do `PROJECT-PATTERNS.md`; escopo B1). Para o Administrador e o dev não há custo (curto-circuito em `isAdmin()`/`isProjectManager()`).

**Sugestão:**
```php
// Project.php
public function hasMember(User $user): bool
{
    if (array_key_exists('is_member', $this->attributes)) return (bool) $this->is_member;
    return $this->members()->where('user_id', $user->id)->exists();
}
```
e o `withExists` no `ProjectQuery::paginate`. Teste: contagem de consultas na lista com 20 projetos do gestor alocado (`DB::enableQueryLog()`), como o `ClientManagementPorRequisicaoTest` do B1.

**Impacto:**
Lista do gestor com 1 + N consultas; o B3 herda o padrão.

---

## [MÉDIO] CR-2-04 — O corpo do PR não cumpre o trilho leve: sem "o que não entra", sem premissas decididas, sem print do uso no navegador nem teste cruzado

**Arquivo:** descrição do PR #6; `app/Domains/Projetos/Policies/ProjectPolicy.php:9-12`
**Trecho atual:**
```text
PR #6 (corpo inteiro):
- ProjectPolicy (view/manage/create/delete) e abilities view_projects e manage_projects
- ProjectService: criar…, editar…, encerrar/reabrir (P7), excluir só o nunca usado (G2)
- BoardStageService com as 8 etapas; ExemploSeeder passa a usá-lo
- Tela /projetos com busca, filtros e modal de criar/editar
- Testes por regra e de permissão (403 e nada muda)
```
```php
// ProjectPolicy.php:11 — a "premissa do PR" não está no PR
 * mesmo sendo o responsável: o F2 §09 nega configuração ao dev e o Comercial não entra em
 * Projetos (premissa do PR). Project::scopeVisibleTo espelha view.
```

**Ajuste necessário:**
Completar a descrição com o que o `AGENTS.md` ("Trilhos", trilho leve) pede: **o que não entra** neste passo (aba Pessoas, Etapas, Áreas, Padrões, listas gerais, `ProjectClientManagement`, botão "Novo cliente" inline do F2 §02/N6, "Clicar abre o quadro"), as **premissas decididas** (quem perdeu o papel de gestor deixa de gerir mesmo sendo o responsável; Comercial não vê projeto algum; `due_on` vazio = produto contínuo), os **prints** do uso no navegador (criar, editar com prefixo travado, encerrar, excluir recusado) e quem fez o **teste cruzado**.

**Motivo:**
O trilho leve dispensa a spec e o Verifier justamente porque a descrição do PR faz o papel da spec e o print faz o papel da evidência ("Teste por regra + uso no navegador com print; teste cruzado pelo outro dev"). Sem "o que não entra", o passo 2 fica sem contrato; a premissa de permissão que só existe no comentário da Policy é decisão de regra que o Lucas Scholz precisa ver (ele é o dono de toda trava, escopo §7).

**Sugestão:**
Seções no corpo do PR: Objetivo · Regras (P1, P2, P3, P5, P7, P10, G2, G3, C14) · O que não entra · Premissas decididas · Evidência (CI + prints) · Teste cruzado (Cauã).

**Impacto:**
Premissa de permissão passando sem a aprovação explícita de quem decide; passo 2 sem escopo escrito.

---

## [MÉDIO] CR-2-05 — Desde o merge do B1, o gestor não gere cliente nem vê contato: `ProjectClientManagement` (do escopo B2) ainda não existe e o PR não diz em que passo entra

**Arquivo:** `app/Providers/AppServiceProvider.php` (ligação `ClientManagement` → `NoClientManagement`, na `main`); escopo B2 (`docs/escopo.md:91`)
**Trecho atual:**
```text
docs/escopo.md (B2): "O B2 também entrega Projetos\Services\ProjectClientManagement, a implementação
do contrato Platform\Contracts\ClientManagement que o B1 deixa pronto (quais clientes um gestor gere:
projects.manager_id ou alocado com papel gestor em project_members), e troca a ligação no AppServiceProvider."
```

**Ajuste necessário:**
Declarar no PR (seção "o que não entra") que a implementação fica para o passo 2 do B2, ou entregá-la aqui: a regra "gere" já está pronta na `ProjectPolicy::manages` e no `scopeVisibleTo`; `ProjectClientManagement` é a mesma consulta por cliente (`manages`, `managedClientIds`, `openProjectsCountByClient` em lote) mais a troca do `give()` no `MemoizedClientManagement` do `AppServiceProvider`, com o `ClientManagementFalso` dos testes do Núcleo continuando a valer.

**Motivo:**
Com o B1 na `main`, a `ClientPolicy` e a `ContactPolicy` perguntam ao contrato quem gere cada cliente; a implementação ligada responde "ninguém". Na prática, hoje o Gestor de projetos não edita o cliente dos projetos dele nem vê os contatos (F1 §09), e isso só se resolve com a entrega que o escopo atribui ao B2. Não é defeito do passo 1, mas é a primeira coisa que o passo 2 precisa fazer, e o PR precisa dizer isso.

**Sugestão:**
Se entrar neste PR: classe em `app/Domains/Projetos/Services/ProjectClientManagement.php` implementando o contrato com `Project::query()->where('manager_id', …)->orWhereHas('members', …)` (gestor com papel), e `openProjectsCountByClient` com `whereNull('finished_at')->groupBy('client_id')`; teste de gestor nos `ClientPolicyTest`/`ContactPolicyTest` do B1 passando a usar a real.

**Impacto:**
Enquanto não entrar, a regra N6/F1 §09 (gestor cria e edita cliente e contato dos projetos dele) está desligada em produção.

---

## [BAIXO] CR-2-06 — `AGENTS.md` sem o que o PR criou: `StatusPill kind="project"`, `dateBr()` e o estado do B2

**Arquivo:** `AGENTS.md` (tabela "Componentes e hooks do projeto"; "Estado atual"); `resources/js/lib/utils.ts:39-47`; `resources/js/components/status-pill.tsx:32-37`
**Trecho atual:**
```ts
// lib/utils.ts — util novo, fora da tabela do AGENTS.md
export function dateBr(value: string | null | undefined): string { … }
```

**Ajuste necessário:**
Linha na tabela para `dateBr()` (`lib/utils.ts`: data de negócio `AAAA-MM-DD` → `DD/MM/AAAA` sem passar por `Date`) e para o dicionário `project` do `WStatusPillAtom`; linha do B2 em "Estado atual".

**Motivo:**
`AGENTS.md` e `.ai/stacks/ui.md` §5: "quem criar componente registra nesta tabela no mesmo commit". O `dateBr` resolve um erro real (data cai um dia atrás ao passar por `Date`) que as próximas telas vão repetir se não acharem o helper.

**Sugestão:**
Entra no mesmo commit do porte (CR-2-01).

**Impacto:**
Helper duplicado no B3/B5.

---

## [BAIXO] CR-2-07 — Projeto encerrado não fica "em cinza" na lista (F2 §03)

**Arquivo:** `resources/js/pages/projects/index.tsx:178-186,189-192`
**Trecho atual:**
```tsx
{ key: 'name', header: 'Nome', cell: (row) => <span className="font-medium">{row.name}</span> },
// só a pill muda: "Encerrado em DD/MM/AAAA"
```

**Ajuste necessário:**
`muted(row)` (`!row.finished_at ? '' : 'text-muted-foreground'`) no nome, cliente e gestor, como as três tabelas do B1 fazem com o desativado (`w-user-table-organism.tsx`, `muted()`).

**Motivo:**
F2 §03: "Encerrados ficam escondidos; o filtro *Mostrar encerrados* os traz, **em cinza**". O padrão já existe no B1 para "desativado".

**Impacto:**
Só visual; o filtro e a pill já distinguem.

---

## Registrado sem achado (não é FP: o Lucas não foi consultado)

- Rotas em português (`/projetos`, `/encerrar`, `/reabrir`) com nomes `projects.*` em inglês: é o que o escopo B2 define, diferente do `/admin/*` em inglês do B1 (D4). Não apontar.
- `ProjectPolicy::manages` lê o papel **global** do alocado (`isProjectManager()`): `project_members` não tem coluna de papel, como o `banco.txt` diz. Correto.
- "Nunca usado" de projeto = sem tarefa, sprint e pacote (G2, F2 §04), não o `isReferenced()` genérico do `Deactivatable`: `board_stages`, `project_areas` e `project_members` sempre referenciam e saem em cascade. Correto.
- `guardPrefix` envolve a transação inteira (CR-1-03): hoje o único unique de `projects` é o do prefixo; o achado é de convenção, não de bug ativo (anotado pelo ciclo 1).
- Encerrar o já encerrado responde "encerrado" sem gravar nada (`setFinished` curto-circuita): coerente com o Trello (fluxo aberto). Não apontar.
- Delete grava a auditoria com `auditable_id` do projeto já apagado: mesmo caminho do B1 (`deleteIfUnused`). Correto.
- O `ExemploSeeder` da `main` ainda tem a constante `ETAPAS` (o B1 não a tirou); o PR a substitui pelo `BoardStageService`: é o que o comentário do seeder pedia. Sem conflito no `merge-tree`.
- Segurança (`.ai/core/seguranca.md` §11): (1) grupo `['auth', 'backoffice', 'password.current']` igual ao `/admin`, `authorize()` em todo método, A5 passa; (2) `update`/`finish`/`reopen`/`destroy` autorizam sobre o projeto da rota, `index` filtra por `visibleTo`; (3) `StoreProjectRequest` valida tudo, `type` não vai ao banco, filtros do `index` saneados; (4) nenhuma regra só na tela (a confirmação de troca de gestor é confirmação, não regra); (5) sem segredo; (6) login fora do PR. Inegociável 11: criar, listar, editar, encerrar/reabrir, excluir, menu. Ok.

---

## Resumo executivo

| Criticidade | Achados | Na revisão em camadas |
|---|---|---|
| Crítico | 0 | — |
| Alto | 1 (CR-2-01) | **bloqueante** (o merge quebra o build da `main` sem o porte) |
| Médio | 4 (CR-2-02, CR-2-03, CR-2-04, CR-2-05) | deveria corrigir |
| Baixo | 2 (CR-2-06, CR-2-07) | detalhe |

**Achados anteriores:** 4 abertos (CR-1-01 a CR-1-04), 1 resolvido por decisão (CR-1-05: modal, Lucas Scholz 09/10), 0 regressões. O CR-1-01 continua **bloqueante** e agora é acionável (o B1 está na `main`).

**Principais riscos:** (1) rebase sobre a `main` com B1 + #8: 9 conflitos e o front inteiro a portar para a estrutura atômica; (2) negação de permissão sem auditoria (CR-1-01) até o rebase; (3) o gestor sem gerir cliente/contato enquanto `ProjectClientManagement` não existir (CR-2-05).

**Avaliação geral:** o backend do passo 1 é bom (o ciclo 1 já disse: transações com `lockForUpdate`, `scopeVisibleTo` espelhando a Policy, G2 e G3 das escritas corretos, testes por regra e por papel) e os achados são de integração e de padrão de projeto, não de regra de negócio. O custo real está no front: o PR nasceu antes do ADR-003 e precisa ser reescrito na estrutura atômica, o mesmo trabalho que o #8 fez com o B1. Recomendação de ordem para o Vitinho: rebase + porte do front (CR-2-01) → CR-1-01 a CR-1-04 (helpers do Núcleo) → CR-2-02/CR-2-03 → corpo do PR (CR-2-04/05) → detalhes.

**Decidido pelo Lucas Scholz (09/10/2026):** CR-1-05 fica como o PR fez, modal na lista (gabarito do `.ai/stacks/ui.md` §4, igual ao B1). No rebase, trocar no `docs/escopo.md` (B2, "Telas") `/projetos/novo` e `/projetos/{id}/editar` por "modal na lista"; a configuração (`/projetos/{id}/configuracao`, abas) continua página própria.

**Recomendação final: não apta para merge** (bloqueantes CR-1-01 e CR-2-01). Resolvidos os dois e os deveria-corrigir, **apta com ajustes**.

*Achado de permissão/auditoria (CR-1-01) só é marcado como corrigido por um humano, após commit, merge e verificação (inegociável 5).*
