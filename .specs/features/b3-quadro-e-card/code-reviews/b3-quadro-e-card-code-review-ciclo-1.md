# Code Review — `feat/b3-quadro-e-card` · Ciclo 1

**Data:** 2026-10-19
**História:** b3-quadro-e-card (PR WCJ-Tecnologia/wcj-hub#17, "B3 PR 1: quadro, card rápido, mover e migração do card do quadro")
**Branch:** `feat/b3-quadro-e-card` (head `6eeadc6`, 5 commits) · **Base:** `main` (branch do ambiente, `5e34663`)
**Spec:** `.specs/features/b3-quadro-e-card/spec.md` (trilho completo só para a `TaskPolicy` e a migração; **"Approved by: aguardando Lucas Scholz"**)
**Revisor:** Claude Code (sessão de revisão, contexto zerado; não é quem implementou)
**Ciclos anteriores:** nenhum
**Escopo deste ciclo:** a branch inteira contra a `main`: 63 arquivos (+3.577 / −107), sem mudança não commitada.

## Evidência colhida neste ciclo

Rodado no Mac, nesta branch, em 19/10 (MySQL 8 do `docker compose`, banco `wcj_hub_test`):

| Comando | Resultado |
|---|---|
| `composer lint` (Pint) | passou |
| `php artisan test --compact` | **605 passed** (4.842 asserções, 92 s) |
| `npm run typecheck` | ok |
| `npm run lint` | ok |
| `npx playwright test` | **não rodado** neste ciclo (o PR declara 77 verdes em 15/10) |
| CI do PR #17 | **não rodou** em 16/10 (billing do Actions da org: "The job was not started because recent account payments have failed"); em 19/10 o billing já aceita re-run (o #16 rodou verde às 14h) |

Ou seja: o "escada local verde" do corpo do PR confere para Pint, PHPUnit, typecheck e lint (AP-06 não se aplica); o e2e e o CI ficam como "sem evidência deste ciclo".

Também conferido: `git merge-tree origin/feat/b5b-sprint-em-lote HEAD` dá conflito em `AGENTS.md` e em `w-project-board-page.tsx`, exatamente como o PR avisa.

## O que foi olhado, com as seis perguntas de `seguranca.md` §11

Arquivo por arquivo do que a branch tocou:

1. **Rota de dados com `auth` e Policy?** As 9 rotas novas (`quadro`, `cards`, `cards/suporte`, `cards/{card}`, `mover`, `PATCH cards/{card}`, `participantes`, `tipos`, `tipos/{taskType}`) estão no grupo `auth + backoffice + password.current` e dentro do `scopeBindings()`. Cada uma autoriza: `BoardController::show` e `TaskController::show` pelo `authorize()` do Controller base (auditado); as escritas pelos FormRequests com `AuthorizesBeforeValidation` (`ActsOnCard`, `ManagesProject`), e o controller não autoriza de novo (CV-01). Sim.
2. **Rota com ID valida o vínculo?** `{card}` é resolvido pelo `Route::bind('card')` só entre os cards do projeto da rota (prefixo + número; de outro projeto é 404 antes da Policy, `TaskPolicyTest::test_a_card_key_of_another_project_is_404_before_the_policy`). `{taskType}` é escopado pela relação `taskTypes`. `stage_id` do mover: `Rule::exists` com `project_id` do card **e** `assertSameProject` no Service com a linha travada. `area_id`/`type_id`: `find` + `project_id` + ativo no `TaskTagService`. `user_id` do participante: ativo e `hasMember` do projeto travado. `requester_contact_id`: contato ativo do `client_id` do projeto travado. `sequence`: ids filtrados aos da coluna. Sim.
3. **Entrada validada no servidor?** Todos os payloads passam por FormRequest; os filtros do quadro por `$request->validate` (`marks.*` em `Rule::in`). Sem upload neste PR. Sim.
4. **Regra só na tela?** Não: pausa com motivo (`MovementReasons`), retrabalho com origem e motivo (+ texto do "Outro"), "Suporte pela etiqueta" recusado, projeto encerrado, card arquivado, "dev não cruza o Backlog" (Policy `move` com destino), tudo no servidor com teste.
5. **Segredo?** Nenhum. Hex inline nos Blade de e-mail é a exceção documentada do ADR-004.
6. **Limite de tentativas em login/senha?** A branch não toca login.

**Inegociáveis 8 e 9:** atendidos (acima). **Inegociável 11 (porta de entrada):** card (criar, listar, editar etiquetas, mover; arquivar e excluir vêm no PR 3, declarado), participante (pôr e tirar), área (as quatro do B2), **tipo: criar, listar e editar, sem desfazer** (ver CR-1-02).

**Regras de domínio do `AGENTS.md`:** R15 asserido no Service em toda gravação; T4 pelo `LAST_INSERT_ID` na transação (com `CardConcorrenciaTest`); ordem de travas projeto → card igual nos três Services (`WritesTasks`); movimento + auditoria na mesma transação; aviso `TaskReturned` só depois do commit; "um só" (sprint aberta) delegado ao `SprintTaskService` do B5; datas em UTC e dia de negócio por `DiasUteis`/`LocalTime`. O benchmark do quadro (`BoardTest::test_the_number_of_queries_does_not_grow_with_the_cards`, 5 → 500 cards) está escrito e passou.

**Migração:** as três são idempotentes (conferem coluna/tabela/índice antes), a dos tipos reescreve dado dentro de `DB::transaction` e os cinco testes de `MigracaoTiposDoProjetoTest` cobrem cópia, not null, unique por projeto, segunda rodada, sem projeto e `down()`.

---

## Achados do Ciclo 1

Nenhum Crítico nem Alto.

## [MÉDIO] CR-1-01 — O quadro entrega a lista de contatos do cliente a quem não pode vê-la

**Arquivo:** `app/Domains/Projetos/Services/TaskPresenter.php:136-137`
**Trecho atual:**
```php
'contacts' => Contact::query()->active()->where('client_id', $project->client_id)->orderBy('name')->get(['id', 'name'])
    ->map(fn (Contact $contact) => ['id' => $contact->id, 'name' => $contact->name])->all(),
```

**Ajuste necessário:**
Mandar `contacts` (e `channels`, que só servem ao mesmo diálogo) apenas quando `abilities.create_support` é verdadeiro; para os demais, lista vazia.

**Motivo:**
`options` vai nas props de `projects/board` para **todo mundo que vê o projeto**, inclusive o Desenvolvedor alocado. No Núcleo, a `ContactPolicy::viewAny` nega ao dev a lista de contatos (só Administrador e Gestor de projetos; o gestor só dos clientes que gere). O único uso dessa lista é o diálogo *Novo card de suporte*, que o dev não tem (`create_support = false`, `createSupport` negado na Policy). Resultado: a tela esconde o botão, mas o payload do Inertia leva nome e id de todos os contatos ativos do cliente a quem a Policy do Núcleo barra. É dado pessoal (N8) saindo por um caminho que a regra não previu; o card de suporte mostrar o nome de **quem pediu** (F3 §03) é outra coisa: um contato, no card que a pessoa vê.

**Sugestão:**
Em `TaskPresenter::board()`, calcular as abilities antes das options e passar o resultado:
```php
$abilities = $this->abilities($project, $viewer);
// ...
'options' => $this->options($project, $abilities['create_support']),
```
e em `options()`, `'contacts' => $canCreateSupport ? Contact::query()->active()->where(...)... : []`, o mesmo para `channels`. O kit já lida com lista vazia (o diálogo não abre sem a ability). Cobrir no `BoardTest::test_options_bring_only_active_items_of_the_project` com o dev recebendo `contacts = []`.

**Impacto:**
Baixo em risco imediato (sistema interno, só nomes), mas é a primeira vez no projeto que uma tela entrega dado que a Policy do próprio Núcleo nega; se ficar, o PR 2 (ficha) tende a copiar o padrão.

## [MÉDIO] CR-1-02 — Tipo do projeto sem porta de "desfazer" (inegociável 11)

**Arquivo:** `routes/web.php:176-177`; `app/Domains/Projetos/Controllers/TaskTypeController.php`
**Trecho atual:**
```php
// Tipos do projeto, criados e editados pela etiqueta do quadro (14/10).
Route::post('{project}/tipos', [TaskTypeController::class, 'store'])->name('types.store');
Route::put('{project}/tipos/{taskType}', [TaskTypeController::class, 'update'])->name('types.update');
```

**Ajuste necessário:**
Ou as rotas `deactivate`, `reactivate` e `destroy` do tipo, espelhando as da área (`ProjectAreaController`, `ProjectPolicy::manage`, `setActiveLocked` + `deleteIfUnused`, que o `TaskType` já tem pelo `Deactivatable`), ou a decisão registrada de que tipo não se desativa nem se exclui, com o texto do F3 §03 ajustado.

**Motivo:**
A branch tirou a lista geral de tipos (e com ela o `TaskTypeSeeder` e qualquer tela do Administrador) e pôs no projeto, com criar e editar pela etiqueta. O F3 §03 diz que "item desativado some da escolha e continua nos cards que já o usam (P18, G7)" e o `TaskPresenter::options` já filtra `->active()`, mas **não existe caminho para desativar um tipo**: nem rota, nem ação no `w-tag-popover-organism`. Um tipo criado por engano ("Pesquisa" com erro de grafia) fica para sempre na lista do projeto. A área, que é a irmã gêmea (mesmo popover, mesma Policy), tem as quatro portas desde o B2.

**Sugestão:**
Copiar o par `deactivate`/`reactivate` + `destroy` do `ProjectAreaController` e do `ProjectAreaService` para o tipo (o `deleteIfUnused` apaga só o nunca usado; o de slug, `improvement`/`support`/`development`, não se exclui nem desativa, porque o código o procura). Se o Lucas preferir deixar para o PR 2 ou 3, dizer isso no "O que NÃO entra" do PR e no `AGENTS.md`, para o teste cruzado não cobrar.

**Impacto:**
Lista de tipos do projeto só cresce; o que o F3 promete (desativado some da escolha) não é alcançável pela tela.

## [BAIXO] CR-1-03 — Docblock do `registerMorphMap` ficou órfão

**Arquivo:** `app/Providers/AppServiceProvider.php:112-120`
**Trecho atual:**
```php
     * gravar o nome da classe, que muda quando o código muda de pasta. Model novo entra aqui
     * no mesmo commit.
     */
    /**
     * {card} nas rotas do quadro (B3) é a chave do card, "SGM-17" (P16), procurada só entre os
```

**Ajuste necessário:** mover `registerCardBinding()` (e o docblock dele) para antes do docblock de `registerMorphMap`, ou depois do método.

**Motivo:** o comentário que explica o morph map ("Model novo entra aqui no mesmo commit") ficou colado no método errado; quem ler o arquivo vai achar que é do binder.

**Sugestão:** só reordenar os blocos.

**Impacto:** leitura.

## [BAIXO] CR-1-04 — `docs/escopo.md` B3 ainda descreve a `TaskPolicy` com `logTime` e as queries em `Queries/` (AP-04)

**Arquivo:** `docs/escopo.md:107` (e o parágrafo "Regras" do B3)
**Trecho atual:**
```
`comment`/`attach`/`logTime` (alocado)
```

**Ajuste necessário:** tirar `logTime` (a spec decidiu que lançar horas fica na `TimeEntryPolicy::create(Task)` do B5) e citar `Services/` para `BoardQuery`/`ChildProgressQuery` (premissa 11 do PR), ou uma nota "decidido no PR #17".

**Motivo:** é o anti-padrão AP-04 do projeto: a decisão está na spec e no `AGENTS.md`, mas o documento de escopo, que é o que o próximo bloco lê, segue com a versão antiga.

**Sugestão:** uma linha em cada ponto, no mesmo PR.

**Impacto:** o B8 (MCP) pode procurar um `logTime` que não existe.

## [BAIXO] CR-1-05 — `Task::url()` monta o caminho à mão em vez de usar a rota nomeada

**Arquivo:** `app/Domains/Projetos/Models/Task.php:195-198`
**Trecho atual:**
```php
public function url(): string
{
    return url("/projetos/{$this->project_id}/cards/{$this->key()}");
}
```

**Ajuste necessário:** `route('projects.cards.show', [$this->project_id, $this->key()])`.

**Motivo:** o endereço vai para o e-mail e para o sino (`TaskReturned`), que ficam gravados; se a rota mudar (o PR 2 vai mexer justamente em `cards/{card}`), o link quebra sem teste que acuse. O projeto já tem a rota nomeada e usa `to_route('projects.board', …)` no mesmo controller.

**Sugestão:** trocar pela rota nomeada; o teste de `TaskReturned` continua valendo.

**Impacto:** link morto em aviso já enviado.

## [BAIXO] CR-1-06 — Aviso de retrabalho vai ao dev mesmo desativado

**Arquivo:** `app/Domains/Projetos/Services/TaskMoveService.php:118`
**Trecho atual:**
```php
User::query()->find($developerId)?->notify(new TaskReturned($moved, $actor, $stageName, $fields));
```

**Ajuste necessário:** `User::query()->active()->find($developerId)`.

**Motivo:** um card pode seguir com `developer_id` de uma pessoa já desativada (G2: desativar não limpa histórico). O e-mail levaria título do card e motivo da devolução para uma caixa de quem saiu. O projeto trata "ativo" por um jeito só (`->active()`).

**Sugestão:** o filtro na consulta, e um caso no `test_rework_counts_clears_cross_test_resets_criteria_and_warns_the_developer` com o dev desativado.

**Impacto:** dado do projeto para endereço fora de uso.

---

## Registrado sem achado no ciclo 1 (não é FP: o Lucas não foi consultado)

- **Janela entre a Policy e a trava no mover.** `TaskPolicy::move` lê `$task->boardStage` antes da transação; o `TaskMoveService` relê com `lockForUpdate` mas não refaz a pergunta "cruza o Backlog?" com a linha travada. Se um gestor levar o card ao Backlog entre a Policy e o `move` de um dev, o dev acaba pondo o card na sprint. Janela de milissegundos, efeito reversível, sistema de fluxo aberto (`feedback_wcj_sistemas_fluxo_aberto`): não apontar; refazer a conta no Service seria um segundo jeito de dizer a regra (AP-02).
- **Índice duplicado em `parent_task_id`/`cover_attachment_id`** (`constrained()` + `index()`): hipótese descartada. Conferido no `wcj_hub_test` migrado: um índice por coluna (`tasks_parent_task_id_index`, `tasks_cover_attachment_id_index`); o MySQL reaproveita o índice explícito para a FK. É o mesmo padrão do `origin_task_id` do B0.
- **Dias em pausa** (`BoardQuery::pausedBusinessDays`, `between − 1`): uma pausa de segunda a terça desconta 1 dia, embora não haja "dia inteiro" entre as duas; é a aproximação declarada na premissa 5 do PR ("a conta fina é a do F7"). Não apontar.
- **`down()` da migração dos tipos**: ao voltar a `unique('slug')`, falha se dois projetos renomearam o tipo de mesmo slug com rótulos diferentes (a dedupe é por rótulo). Só em dev; o PR diz que em produção a volta é o backup.
- **`TaskController::show` provisório** (redireciona ao quadro com aviso): declarado como premissa 2, sai no PR 2.
- **Spec só com `spec.md`** (sem `design.md`, `tasks.md`, `validation.md`): o PR declara que o Verifier roda depois da aprovação. É condição do trilho completo, não achado de código (ver recomendação).
- **Conflito com o #16** em `AGENTS.md` e `w-project-board-page.tsx`: confirmado; o PR já avisa e se compromete a resolver.
- **Comentário de "Para o kit"** (primeiro clique no ⋯ engolido com o campo de card aberto): defeito do kit do quadro (#11), não desta branch; fica registrado para o PR 2.

## Resumo executivo

| Criticidade | Achados | Na revisão em camadas |
|---|---|---|
| Crítico | 0 | bloqueante: 0 |
| Alto | 0 | bloqueante: 0 |
| Médio | 2 (CR-1-01, CR-1-02) | **deveria corrigir** (corrige ou justifica no PR) |
| Baixo | 4 (CR-1-03 a CR-1-06) | detalhe |

Achados anteriores: não há (ciclo 1). Regressões: 0.

**Principais riscos:** nenhum de produção. O que pesa é de desenho: a lista de contatos nas props a quem a Policy do Núcleo nega (CR-1-01) e o tipo do projeto sem desfazer (CR-1-02), dois pontos que o PR 2 vai herdar se ficarem.

**Avaliação geral:** a branch segue o padrão da casa com folga: autorização nos FormRequests antes da validação, negação auditada com ação própria (`moved`, `archived`), escopo por ID em toda rota, regra de negócio no Service com a linha travada e na ordem de travas que o projeto já usava, contador atômico com teste de intercalação, benchmark do quadro, migração idempotente com teste de ida e volta. O código é legível, os três Services ficaram abaixo do limite A6 e as 12 premissas decididas estão escritas no PR, com baixo risco. Os 605 testes, Pint, typecheck e lint passaram no Mac neste ciclo.

**Recomendação final: apta com ajustes**, condicionada ao rito do trilho completo que o próprio PR declara: (1) o "aprovo" do Lucas Scholz na spec (11 premissas da tabela *Assumptions*), (2) o Verifier em agente separado com mutação nas regras de permissão e o `validation.md` anexado, e (3) o CI do #17 verde (o billing já aceita re-run). Sem bloqueante em aberto; CR-1-01 e CR-1-02 corrigem ou se justificam no PR antes do merge.
