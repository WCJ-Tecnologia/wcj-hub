# Code Review — `feat/b2-projeto-e-configuracao` · Ciclo 1

**Data:** 2026-10-09
**História:** PR-6 (B2 Projeto e configuração, passo 1: lista, cadastro, encerrar, excluir projeto)
**Branch:** `feat/b2-projeto-e-configuracao` · **Base:** `main` (branch do ambiente)
**Spec:** não mapeada (B2 é trilho leve — spec na descrição do PR)
**Revisor:** Claude Code (sessão de revisão, contexto zerado)
**Ciclos anteriores:** nenhum
**Escopo deste ciclo:** primeira revisão. +1926/−31 em 25 arquivos, 1 commit (`fa9c126`). CI do PR todo verde (PHPUnit MySQL 8.4, E2E, JS, secrets).

---

## Achados do Ciclo 1

## [ALTO] CR-1-01 — PR baseado em `main`, não no B1: a negação de autorização não é auditada (G3) e o padrão de autorização do Núcleo não é seguido

**Arquivo:** `app/Domains/Projetos/Requests/StoreProjectRequest.php:25` · `app/Domains/Projetos/Controllers/ProjectController.php:48` · base `app/Http/Controllers/Controller.php`

**Trecho atual:**
```php
// StoreProjectRequest — authorize() próprio, sem o trait do Núcleo
public function authorize(): bool
{
    $project = $this->route('project');
    return $project instanceof Project
        ? (bool) $this->user()?->can('manage', $project)
        : (bool) $this->user()?->can('create', Project::class);
}

// ProjectController::store — ainda autoriza de novo
public function store(StoreProjectRequest $request): RedirectResponse
{
    $this->authorize('create', Project::class);
    ...
}
```

**Motivo:**
A infraestrutura de auditoria de negação é do **B1 (PR #5), que ainda não está na `main`**. Confirmei por `git cat-file`: na `main` não existem `App\Http\Concerns\AuditsDeniedAbilities`, o `authorize()` auditado do base `Controller`, `App\Platform\Requests\Concerns\AuthorizesBeforeValidation`, `App\Support\Like`, `App\Platform\Services\Concerns\RefusesDuplicates` nem os Presenters — tudo isso nasce no B1. Como a B2 foi ramificada de `main`, o `ProjectController` estende o `Controller` **do template** (só `AuthorizesRequests`), então:

- toda recusa de `viewAny`/`create`/`manage`/`delete` lança 403 **sem gravar `outcome = denied`** — viola o G3 e o inegociável do `AGENTS.md` ("toda negação vira linha `denied`");
- o `StoreProjectRequest` reimplementa `authorize()` em vez de usar `AuthorizesBeforeValidation` (que o B1 padronizou em `StoreUserRequest`, `StoreClientRequest`, `StoreContactRequest`, `AnonymizeContactRequest`), e por isso a recusa do `store`/`update` **nunca passa pelo caminho auditado**: o FormRequest lança o 403 antes de o controller rodar;
- o `$this->authorize('create'/'manage')` no `store`/`update` fica redundante com o FormRequest;
- `AuditActionEnum` ganhou `FINISHED`/`REOPENED`, mas não há `forAbility()` (também do B1) mapeando `viewAny`/`create`/`manage`/`delete` de projeto — na negação, a tela mostraria "Outra ação".

Os testes de permissão (`ProjectPermissionTest`) conferem 403 e "nada muda", mas **não conferem a linha `denied`** — coerente com o fato de que, hoje, ela não existe.

**Ajuste necessário:**
Mergear o **B1 (PR #5) primeiro** e **rebasar a B2 sobre a `main` com o B1**. Depois:
1. `StoreProjectRequest` passa a `use App\Platform\Requests\Concerns\AuthorizesBeforeValidation` (como os FormRequests do Núcleo), removendo o `authorize(): bool` próprio;
2. remover o `$this->authorize('create')`/`('manage')` redundante de `ProjectController::store`/`update` (o FormRequest auditado já cobre); manter o `authorize()` de `index`/`finish`/`reopen`/`destroy`, que então passam a auditar pelo base `Controller`;
3. adicionar o mapeamento das abilities de projeto em `AuditActionEnum::forAbility()`;
4. acrescentar a `ProjectPermissionTest` a asserção de que a negação gravou `outcome = denied` (como fazem os testes do B1).

**Sugestão:**
Não mergear o PR #6 antes do PR #5. A ordem da onda 1 no `docs/plano-de-desenvolvimento.md` é B1 → B2, e o escopo da B2 já diz que ela "entrega `ProjectClientManagement`, a implementação do contrato que o B1 deixa pronto".

**Impacto:**
Se o PR #6 for para `main` antes do B1, o sistema passa a ter escrita de projeto cuja recusa de acesso não é registrada (lacuna de G3, inegociável de segurança) e o B1 terá de resolver conflito nos arquivos compartilhados (`Controller`, `AppServiceProvider`, `HandleInertiaRequests`, `routes/web.php`, `AuditActionEnum`). É **bloqueante** por tocar permissão e auditoria.

---

## [MÉDIO] CR-1-02 — A linha da lista é montada no controller, não num Presenter

**Arquivo:** `app/Domains/Projetos/Controllers/ProjectController.php:98`

**Trecho atual:**
```php
private function row(Project $project, User $user): array
{
    return [
        'id' => $project->id,
        ...
        'can' => [
            'manage' => $user->can('manage', $project),
            'delete' => $user->can('delete', $project),
        ],
    ];
}
```

**Motivo:**
O `AGENTS.md` ("Um jeito só") manda: *"Linha de lista para a tela: um Presenter em `app/Platform/Services` (…), com o `can` decidido pela Policy; o controller só chama `->row()`. Nunca montar a linha no controller."* O B1 segue isso em `UserPresenter`, `ClientPresenter`, `ContactPresenter`, `AuditLogPresenter`. A B2 monta a linha dentro do controller.

**Ajuste necessário:**
Extrair um `ProjectPresenter` (em `app/Domains/Projetos/Services`, pela regra A9 — é dado do próprio módulo) com `->row()`, e deixar o `index` só chamando-o.

**Sugestão:**
Espelhar `UserPresenter`/`ClientPresenter` do B1; disponível após o rebase.

**Impacto:**
Dois jeitos de montar linha de lista no mesmo projeto; a regra do `can` espalha entre controller e Presenters. Manutenção e divergência de padrão.

---

## [MÉDIO] CR-1-03 — `guardPrefix` captura `UniqueConstraintViolationException` solto, contra a convenção `RefusesDuplicates`

**Arquivo:** `app/Domains/Projetos/Services/ProjectService.php:129`

**Trecho atual:**
```php
private function guardPrefix(string $prefix, Closure $write): mixed
{
    try {
        return $write();
    } catch (UniqueConstraintViolationException) {
        $owner = Project::query()->where('key_prefix', $prefix)->value('name');
        throw ValidationException::withMessages(['key_prefix' => "O prefixo {$prefix} já é do projeto {$owner}."]);
    }
}
```

**Motivo:**
O `AGENTS.md` ("Um jeito só") proíbe o catch solto: *"Unique do banco em gravação simultânea: o trait `RefusesDuplicates` (`writeOrRefuseDuplicate`), que vira `ValidationException` só quando o índice violado é o esperado e relança o resto. Nunca `catch (UniqueConstraintViolationException)` solto."* Aqui o `guardPrefix` envolve a **transação inteira**, inclusive `createDefaultBoard()`: qualquer violação de unique vinda da criação do quadro (hoje o `single_role_key` de `board_stages`, ou um unique novo amanhã) seria traduzida, incorretamente, como "o prefixo já é do projeto X". Hoje o único unique de `projects` é o do prefixo, então o efeito visível não aparece — mas o mecanismo está errado e é exatamente o que a convenção evita.

**Ajuste necessário:**
Usar `writeOrRefuseDuplicate` do trait `RefusesDuplicates`, traduzindo só o índice `projects_key_prefix_unique` e relançando o resto.

**Sugestão:**
Disponível após o rebase sobre o B1 (o trait é do B1).

**Impacto:**
Mensagem de erro enganosa e silenciamento de uma violação de integridade diferente, se a transação crescer.

---

## [BAIXO] CR-1-04 — Busca monta `'%'.$termo.'%'` à mão em vez de `App\Support\Like::contains()`

**Arquivo:** `app/Domains/Projetos/Services/ProjectQuery.php:25`

**Trecho atual:**
```php
$like = '%'.$filters['search'].'%';
...
->where('key_prefix', 'like', $like)
->orWhere('name', 'like', $like)
```

**Motivo:**
O `AGENTS.md` ("Um jeito só") define `App\Support\Like::contains()` como o jeito único de busca por texto ("nunca `"%{$termo}%"` à mão"). Além da convenção, o termo não escapa os coringas `%` e `_`: buscar `50%` casa mais do que o esperado (não é falha de segurança — o valor vai como bind —, só de correção da busca).

**Ajuste necessário:**
Trocar por `Like::contains($filters['search'])` nos três `where` (inclusive no `orWhereHas('client')`).

**Sugestão:**
Disponível após o rebase (o helper é do B1).

**Impacto:**
Resultado de busca inconsistente com o resto do sistema; divergência de padrão.

---

## [BAIXO] CR-1-05 — Criar/editar por modal, sem as telas `/projetos/novo` e `/projetos/{id}/editar` do F2 (confirmar)

**Arquivo:** `routes/web.php:74` · `resources/js/pages/projects/index.tsx`

**Motivo:**
O escopo da B2 lista `/projetos/novo` e `/projetos/{id}/editar` como telas; o PR resolve criar/editar por modal na própria lista. Para sistema interno WCJ (fluxo aberto) o modal é uma escolha razoável e não é uma trava indevida — mas diverge do que o funcional nomeia.

**Ajuste necessário:**
Confirmar com o Lucas se o modal substitui as telas dedicadas no F2 (provavelmente sim); se sim, alinhar o funcional. Nenhuma mudança de código obrigatória.

**Impacto:**
Só alinhamento doc × implementação.

---

## Resumo executivo

**Achados do Ciclo 1 por criticidade:** 1 Alto · 2 Médios · 2 Baixos · 0 Críticos.

**Pela revisão em camadas (o que vai na descrição do PR):**

| | Achados |
|---|---|
| **Bloqueante** | CR-1-01 (toca permissão e auditoria) |
| **Deveria corrigir** | CR-1-02, CR-1-03 |
| **Detalhe** | CR-1-04, CR-1-05 |

**O que está bem (não force apontamento):**
- Transações corretas com `lockForUpdate` + `findOrFail` em `update`/`finish`/`reopen`/`delete`; a linha travada é a mesma do contador da chave (T4/P3), e o teste prova que o quadro do projeto recusado some no rollback.
- Escopo por ID pela `ProjectPolicy` no `authorize()` de cada método; `scopeVisibleTo` espelha `view` (a lista e o acesso por ID não divergem).
- G2 (excluir só o nunca usado) com os dois ramos testados; G3 das **escritas** auditado (created/updated/finished/reopened/deleted, com `outcome` e `changes`).
- `dateBr` evita o deslize de fuso (não passa a `date` por `Date`); o quadro padrão mora num só lugar (`BoardStageService`), e seeder e factory passaram a usá-lo (P10).
- Porta de entrada completa (inegociável 11): link no sidebar (`view_projects`), lista, criar, editar, encerrar/reabrir e excluir.
- Cobertura de teste forte para trilho leve: regra a regra (P1/P2/P3/P5/P7/G2), 403 + "nada muda" por papel, e visibilidade por papel.

**Principais riscos:** a lacuna de auditoria de negação (CR-1-01) enquanto a B2 estiver sobre a `main` sem o B1.

**Avaliação geral:** código de alta qualidade e bem testado; quase todos os desvios de padrão são **consequência de a B2 ter sido ramificada de `main` em vez do B1** — os helpers do Núcleo (auditoria de negação, `AuthorizesBeforeValidation`, Presenter, `RefusesDuplicates`, `Like`) ainda não existem na base.

**Recomendação final:** **não apta para merge** enquanto houver o bloqueante. Caminho: mergear o B1 (PR #5) → rebasar a B2 → aplicar CR-1-01 a CR-1-04 → apta. Não mergear o PR #6 antes do PR #5.

*Achado de segurança/auditoria (CR-1-01) só é marcado como corrigido por um humano, após commit, merge e verificação (inegociável 5).*
