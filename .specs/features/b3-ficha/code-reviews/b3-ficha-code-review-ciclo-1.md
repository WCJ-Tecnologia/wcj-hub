# Code Review — `feat/b3-ficha` · Ciclo 1

**Data:** 2026-10-20
**História:** b3-ficha (PR WCJ-Tecnologia/wcj-hub#18, "B3 PR 2: a ficha do card")
**Branch:** `feat/b3-ficha` (head do Cauã `b6473ca` + merge da `main` `ca82d62`) · **Base:** `main` (branch do ambiente, `9476165`, já com o #16 e o #17)
**Spec:** `.specs/features/b3-ficha/spec.md` (trilho completo para a Policy nova e alterada, as quatro exclusões de verdade e a migração das colunas de "quem"; **"Approved by: aguardando Lucas Scholz"**, com duas perguntas abertas)
**Revisor:** Claude Code (sessão de revisão, contexto zerado; não é quem implementou)
**Ciclos anteriores:** nenhum
**Escopo deste ciclo:** só o que é da ficha, contra a `main` nova: 44 arquivos (+2.045 / −148). O merge da `main` (`ca82d62`) resolveu três conflitos de texto (`AGENTS.md`, `TaskPresenter::options` com `create_support`, comentário das rotas de tipo) e um conflito semântico: o teste do PR 1 que esperava o redirect provisório da ficha passou a esperar a tela `projects/task`.

## Evidência colhida neste ciclo

Rodado no Mac, nesta branch já com a `main`, em 20/10 (MySQL 8 do `docker compose`):

| Comando | Resultado |
|---|---|
| `composer lint` (Pint) | passou |
| `php artisan test --compact` | **658 passed** (depois do ajuste do teste do redirect; antes, 1 failed / 657 passed) |
| `npm run typecheck` · `npm run lint` · `npm run build` · `npm run build-storybook` | ok |
| `npm run specs:check` | 0 erros; **5 avisos na `b3-ficha`**: as quatro histórias não têm o título `**Acceptance Criteria**:` e o validador não lê nenhum SHALL (ver CR-1-05) |
| `npx playwright test` | **não rodado** neste ciclo (o PR declara 78 verdes em 16/10) |
| CI do PR #18 | nunca rodou (rascunho aberto com o billing travado); o billing está liberado desde 19/10 |

## O que foi olhado, com as seis perguntas de `seguranca.md` §11

1. **Rota de dados com `auth` e Policy?** As 14 rotas da ficha estão no grupo `auth + backoffice + password.current` e dentro do `scopeBindings()`. Leitura: `TaskController::show` e `downloadAttachment` com `authorize('view')`. Escritas: `UpdateTaskRequest` (`update`, ou `manageSprint` quando vem `estimated_hours`), `ProposeHoursRequest` (`proposeHours`), `CardTextRequest` (`comment` no comentário novo, `update` do comentário pela `TaskCommentPolicy` na edição, `update` do card no resto), `StoreAttachmentsRequest` (`attach`); `approveHours`/`rejectHours` com `manageSprint`; `destroyCriterion`/`destroyPullRequest` com `update`; `destroyComment` com `delete` da `TaskCommentPolicy`; `destroyAttachment` com `attach`. Nenhuma rota sem autorização; o A5 passa. Sim.
2. **Rota com ID valida o vínculo?** `{card}` pelo binder do PR 1; o filho (critério, PR, comentário, anexo) é conferido contra o card da rota (`belongsToCard`, 404). Card pai e tarefa de origem: mesmo projeto, não o próprio, um nível (`TaskEditService::assertRelations`). Capa: anexo de imagem do próprio card. Dev e QA: alocado e ativo (`TaskAssignmentService`). Subitem: critério do próprio card, sem neto. Sim.
3. **Entrada validada no servidor?** FormRequests em toda escrita; `url:http,https` no link do PR (`javascript:` recusado, com teste); anexos por quantidade, tamanho e **bytes** (`mimes` lê o conteúdo; HTML com nome `.png` recusado, com teste), sem SVG nem HTML; horas `gt:0`. Sim.
4. **Regra só na tela?** Não: "só A testar" do critério, "um nível" do pai, "só o autor" do comentário, "só quem gere define horas", "quem gere não propõe", tudo no servidor com teste.
5. **Segredo?** Nenhum: `config/hub.php` lê nomes de variável; os limites têm padrão no código.
6. **Login e senha?** A branch não toca.

**Inegociável 11 (porta de entrada), por entidade nova da ficha:** critério (criar, listar, editar, excluir), link de PR (criar, listar, excluir), comentário (criar, listar, editar, excluir, só o autor), anexo (enviar, listar, baixar, excluir, usar como capa e limpar), horas (definir, propor, aprovar, recusar), card pai e tarefa de origem (definir e limpar). Sim.

**Arquitetura:** `LateQuery` extraída da `BoardQuery` e usada pelo quadro e pela ficha (um jeito só); prioridade pela etiqueta passou a entrar pelo `TaskAssignmentService` (regra do #16); o `TaskEditService` distribui cada campo ao seu Service numa transação só. Três Services acima de 100 linhas, nenhum perto do limite A6.

---

## Achados do Ciclo 1

Nenhum Crítico nem Alto.

## [MÉDIO] CR-1-01 — Abrir a ficha monta o quadro inteiro só para reaproveitar três props

**Arquivo:** `app/Domains/Projetos/Services/TaskDetailPresenter.php:40-54`
**Trecho atual:**
```php
$boardProps = $this->board->board($project, $viewer, new Collection, $this->noFilters());

return [
    'project' => $boardProps['project'],
    // ...
    'options' => [
        ...collect($boardProps['options'])->only(['areas', 'types', 'members', 'rework_origins', 'rework_reasons'])->all(),
        'stages' => $boardProps['stages'],
```

**Ajuste necessário:**
Expor no `TaskPresenter` os pedaços que a ficha usa (`project()`, `stages()`, `options()`) e chamá-los direto, sem passar pelo `board()`.

**Motivo:**
`board()` também calcula `abilities` do quadro, as `options` de suporte (contatos do cliente e canais, para quem gere) e a prop `planning` do B5b (`SprintPlanningQuery::forProject`: pessoas alocadas, sprint aberta e capacidade), e a ficha joga tudo isso fora. A cada abertura de ficha por quem gere, são consultas a mais que não aparecem na tela (`performance.md`: consulta que a tela não usa). Com 500 cards e 14 pessoas não derruba nada, mas é o tipo de acoplamento que vira custo quando o B7 pesar o `planning`.

**Sugestão:**
`TaskPresenter::project()`, `stages()` e `options()` públicos (hoje privados) e, na ficha, `['project' => $this->board->project($project), 'stages' => $this->board->stages($project), 'options' => [...$this->board->options($project, false)...]]`. O benchmark da ficha (contagem de consultas ao abrir) entra no `TaskDetailTest`, como o do quadro.

**Impacto:**
Tempo de abertura da ficha e acoplamento da ficha ao formato do quadro.

## [MÉDIO] CR-1-02 — Os testes de permissão da ficha não provam "uma linha `denied` por requisição com o alvo" (TSK-04 do PR 1, L-015)

**Arquivo:** `tests/Feature/Projetos/TaskDetailPolicyTest.php:84-113`, `:115-133`
**Trecho atual:**
```php
$this->assertSame(0, AuditLog::query()->where('user_id', $user->id)->where('outcome', 'success')->count());
$this->assertGreaterThanOrEqual(13, AuditLog::query()->where('user_id', $user->id)->where('outcome', 'denied')->count());
```

**Ajuste necessário:**
Um `assertOneDenied(user, request, action, type, id, caso)` como o do `TaskPolicyTest` (PR 1, Fix 4) em cada requisição negada, afirmando a ação e o alvo (`task` com o id do card; `task_comment` com o id do comentário; `project` em `manageSprint`); e completar a lista do não-viewer com `POST …/horas/recusar`, `PATCH` e `DELETE …/comentarios/{id}` (hoje só o `store`).

**Motivo:**
`>= 13` e `exists()` passam com uma linha errada, duas linhas por requisição (Request e controller autorizando de novo) ou a ação trocada. O PR 1 fechou exatamente essa lacuna na rodada 1 do Verifier (Fix 4, mutante pd1) e a spec do B3 agora fixa o alvo da negação; a ficha herda a regra. O Verifier do trilho completo vai reprovar aqui.

**Sugestão:**
Copiar o helper do `TaskPolicyTest` (ou levá-lo ao `tests/Support/`), aplicar nas três funções de negação.

**Impacto:**
Sem isso, uma rota da ficha que passe a autorizar duas vezes (ou nenhuma, com o 403 vindo de outro lugar) não é pega.

## [BAIXO] CR-1-03 — Endereço de anexo, capa e card montado à mão em três lugares

**Arquivo:** `app/Domains/Projetos/Services/TaskDetailPresenter.php:166`, `:212`; `app/Domains/Projetos/Services/TaskPresenter.php:72`
**Trecho atual:**
```php
return url("/projetos/{$task->project_id}/cards/{$task->key()}/anexos/{$file->id}");
```

**Ajuste necessário:** `route('projects.cards.attachments.show', [$task->project_id, $task->key(), $file->id])` nos dois lugares, e `Task::url()` (ou a rota `projects.cards.show`) nos cards da busca.

**Motivo:** o PR 1 trocou `Task::url()` pela rota nomeada (CR-1-05) justamente porque o endereço vai para e-mail e sino; a capa vai para o quadro e a miniatura para a ficha, mesma coisa. Três cópias do caminho literal são três lugares que quebram em silêncio se a rota mudar.

**Sugestão:** um método `TaskAttachment::url()` com a rota nomeada, usado pelos dois presenters.

**Impacto:** link morto em capa e miniatura ao renomear a rota.

## [BAIXO] CR-1-04 — Ordem 403/404 do filho da rota muda de rota para rota

**Arquivo:** `app/Domains/Projetos/Requests/CardTextRequest.php:20-24`; `app/Domains/Projetos/Controllers/TaskDetailController.php:79-85`, `:112-118`
**Trecho atual:**
```php
// CardTextRequest e destroyComment: 404 do filho ANTES da Policy
abort_if($model !== null && (int) $model->task_id !== $this->card()->id, 404);
// destroyCriterion, destroyPullRequest, destroyAttachment: Policy ANTES do 404
$this->authorize('update', $card);
$this->belongsToCard($acceptanceCriterion, $card);
```

**Ajuste necessário:** uma ordem só. A premissa 4 do PR e a spec dizem "de outro card é 404 **antes** da Policy": levar o `belongsToCard` para antes do `authorize()` nas três exclusões.

**Motivo:** hoje quem não vê o projeto recebe 404 num filho alheio pela edição e 403 pelo mesmo filho pela exclusão. Não vaza nada (o id de outro card não diz nada a quem não vê o projeto), mas é dois jeitos para a mesma pergunta.

**Impacto:** só coerência; o teste de 404 do `TaskDetailPolicyTest` cobre o caminho da edição.

## [BAIXO] CR-1-05 — A spec da ficha não está no formato que o Specs gate do CI lê

**Arquivo:** `.specs/features/b3-ficha/spec.md:7`, `:46-57`, `:61-88`
**Trecho atual:**
```
**Approved by**: _aguardando Lucas Scholz_
### P1: Horas: o dev propõe, o gestor decide ⭐ MVP
1. WHEN o dev alocado propõe horas THEN ...
```

**Ajuste necessário:** o título `**Acceptance Criteria**:` antes de cada lista numerada das quatro histórias (hoje o validador não lê nenhum SHALL: "a spec passaria sem conferir SHALL"); `y` na coluna *Confirmed?* de toda premissa quando o Lucas aprovar; `**Approved by**: Lucas Scholz, AAAA-MM-DD` sem nada depois da data (aprendizado do PR 1: o `validate_state.py` é literal); e, no `validation.md` que o Verifier vai escrever, uma linha só de veredito vigente (`## Validation: PASS`), com o histórico sem a palavra `Result:`.

**Motivo:** o job JS do CI roda `npm run specs:check`; hoje são avisos porque a feature ainda não "parece concluída", mas no momento em que o `validation.md` existir o `validate_state.py` passa a cobrar o formato e o PR fica vermelho, como aconteceu no #17 em 19/10.

**Impacto:** CI vermelho na reta final, por formato.

## [BAIXO] CR-1-06 — `AGENTS.md` ("Um jeito só") e `docs/banco.md` não acompanham a ficha (AP-04)

**Arquivo:** `AGENTS.md`, bloco "Um jeito só", item "Card (B3)"; `docs/banco.md` (`tasks`, `task_pull_requests`, `task_attachments`)
**Trecho atual:**
```
- Card (B3): criar pelo `TaskService`, mudar de etapa e de posição só pelo `TaskMoveService` (…), etiquetas e participante pelo `TaskTagService`.
```

**Ajuste necessário:** dizer que o `PATCH` da ficha e das etiquetas entra pelo `TaskEditService` (que reparte: tipo e área no `TaskTagService`, dev, QA e prioridade no `TaskAssignmentService`, horas no `TaskHoursService`), e que critério, link de PR, comentário e anexo têm Service próprio. Se a migração das colunas de "quem" for aprovada, as quatro colunas entram no `docs/banco.md` (seção 7, diferença de 16/10) no mesmo PR.

**Motivo:** é o anti-padrão AP-04 do projeto: a regra "um jeito só" muda no código e o documento que o próximo bloco lê fica com a versão de antes. O B4 (teste cruzado e QA) vai mexer nos critérios.

**Impacto:** o próximo dev grava `developer_id` ou `task_priority_id` à mão, porque o `AGENTS.md` não disse por onde entra.

## [BAIXO] CR-1-07 — `down()` da migração 000040 com a chave `'tasks '` (espaço) para driblar a chave repetida

**Arquivo:** `database/migrations/2026_10_16_000040_add_quem_to_task_records.php:42-43`
**Trecho atual:**
```php
foreach ([...self::COLUMNS, 'tasks ' => 'archived_by'] as $table => $column) {
    $table = trim($table);
```

**Ajuste necessário:** uma lista de pares `[['task_pull_requests', 'user_id'], ['task_attachments', 'user_id'], ['tasks', 'proposed_by'], ['tasks', 'archived_by']]` usada pelo `up()` e pelo `down()`.

**Motivo:** o truque funciona, mas quem lê precisa descobrir por que há um espaço na chave; e o `up()` trata `archived_by` fora do laço, então as duas metades da migração já não usam a mesma lista.

**Impacto:** leitura.

---

## Registrado sem achado no ciclo 1 (não é FP: o Lucas não foi consultado)

- **Card pai ou tarefa de origem arquivado:** `TaskEditService::assertRelations` não confere `archived_at` (a busca `pickable` esconde os arquivados, mas a API aceita). Fluxo aberto (`feedback_wcj_sistemas_fluxo_aberto`): não apontar; o PR 3 (arquivar) decide se vale uma recusa.
- **Comentar card arquivado** é permitido de propósito (F3 §11 item 12; `TaskCommentService` não passa pelo `lockTask`). Coerente com a ficha "continua abrindo".
- **`TaskTagService` delegando a prioridade ao `TaskAssignmentService`** gera duas linhas de auditoria num `PATCH` com tipo e prioridade juntos. É o preço do "um jeito só" do #16; não apontar.
- **Anexo de 20 MB** pede `upload_max_filesize` e `post_max_size` no PHP do servidor: vai para o `docs/DEPLOY.md` no B10, não é deste PR.
- **`TaskDetailPresenter::detail` com ~20 relações carregadas e 4 consultas soltas** (movimento de criação, motivo da pausa, retrabalho, horas lançadas): uma ficha por vez, sem laço; o benchmark fica para o CR-1-01.
- **Quem não vê o projeto recebe 404 (não 403) num filho alheio pela edição**: é a premissa 4 declarada; o CR-1-04 pede só a coerência entre rotas.
- **As duas perguntas da spec** (4 colunas de "quem"; número do critério depois de excluir o último) são decisão do Lucas Scholz (inegociável 5); o código segue o recomendado na spec (migração aditiva; "maior + 1" do `banco.md`). Não são achados.

## Resumo executivo

| Criticidade | Achados | Na revisão em camadas |
|---|---|---|
| Crítico | 0 | bloqueante: 0 |
| Alto | 0 | bloqueante: 0 |
| Médio | 2 (CR-1-01, CR-1-02) | **deveria corrigir** |
| Baixo | 5 (CR-1-03 a CR-1-07) | detalhe |

Achados anteriores: não há (ciclo 1). Regressões: 0.

**Principais riscos:** nenhum de produção. O CR-1-02 é o que pesa para o rito: sem a asserção exata da negação, o Verifier do trilho completo (permissões novas, quatro exclusões de verdade) reprova na primeira rodada, como no PR 1.

**Avaliação geral:** a branch segue o padrão da casa: autorização nos FormRequests antes da validação, escopo por ID em todo filho da rota, regra no Service com a linha travada (projeto → card → filho), exclusões com o antes na auditoria e o arquivo do anexo saindo depois do commit, upload conferido pelos bytes e servido com `nosniff`, aviso `TaskAssigned` depois do commit sem avisar quem se define, e a `LateQuery` como um jeito só para quadro e ficha. 658 testes, Pint, typecheck, lint, build, storybook e o gate de specs (sem erro) passaram no Mac neste ciclo, já com a `main` nova.

**Recomendação final: apta com ajustes**, condicionada ao rito do trilho completo: (1) as duas decisões e o "aprovo" do Lucas Scholz na spec; (2) o Verifier em agente separado (permissões, exclusões, migração) com `validation.md`; (3) o CI do #18 verde (nunca rodou). CR-1-01 e CR-1-02 corrigem ou se justificam no PR antes do merge.
