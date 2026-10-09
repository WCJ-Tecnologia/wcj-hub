# PROJECT-PATTERNS — WCJ Hub (code review)

Conhecimento acumulado entre ciclos de revisão. O próximo agente lê isto **antes** de apontar.
Editável por humano.

## Convenções confirmadas do projeto ("um jeito só", do `AGENTS.md`)

- **Autorização + auditoria de negação:** base `Controller` usa `AuditsDeniedAbilities` e seu `authorize()` grava `outcome = denied` antes do 403. FormRequest que valida payload usa `App\Platform\Requests\Concerns\AuthorizesBeforeValidation` (que audita) e a ação **não** chama `authorize()` de novo. Ability nova entra em `AuditActionEnum::forAbility()`. **Tudo isso nasce no B1** — não existe na `main`.
- **Presenter, nunca linha no controller:** `UserPresenter`/`ClientPresenter`/… em `*\Services`, com `can` pela Policy; controller só chama `->row()`. (Para módulos de `Domains`, o Presenter mora no próprio módulo pela regra A9.)
- **Unique concorrente:** trait `RefusesDuplicates` (`writeOrRefuseDuplicate`), que traduz só o índice esperado. Nunca `catch (UniqueConstraintViolationException)` solto.
- **Busca por texto:** `App\Support\Like::contains()`; nunca `"%termo%"` à mão.
- **Ativar/desativar e deadlock:** `Deactivatable::setActiveLocked()` na transação; `RetriesDeadlock::transactionRetryingDeadlock()` (repete só no 1213).
- **Policy fora de `app/Policies`:** registrar com `Gate::policy()` no `AppServiceProvider` (B2 fez certo para `ProjectPolicy`).
- **Datas:** `datetime` UTC; `date` de negócio no fuso SP; na tela, converter `date` sem passar por `Date` (ver `dateBr`, para não cair no dia anterior).

## Decisões arquiteturais observadas

- Ordem da onda 1 é **B1 → B2** (`plano-de-desenvolvimento.md`). A B2 depende do B1 (contrato `ClientManagement`, helpers de autorização/auditoria, Presenters, `Like`, `RefusesDuplicates`). Branch de bloco deve sair do B1, não da `main`, ou o B1 merge primeiro e o bloco rebasa.
- Quadro padrão (8 etapas, P10) mora só em `BoardStageService::DEFAULT_BOARD`; `ProjectService`, `ExemploSeeder` e `ProjectFactory::withBoard()` o chamam. Não duplicar a lista.
- `project_members` **não tem coluna de papel**: "alocado com papel gestor" = papel **global** do usuário (`isProjectManager()`) + vínculo. A `ProjectPolicy::manages` e o `scopeVisibleTo` do B2 implementam isso certo.
- "Nunca usado" de projeto (G2, F2 §10.9) = sem **tarefa, sprint, pacote** (não é o `isReferenced()` genérico — `board_stages`/`areas`/`members` sempre referenciam e saem por cascade).

## Falsos positivos / já decididos (não reabrir sem evidência nova)

- `FormField` injeta `id`/`aria-invalid`/`aria-describedby` no filho via `cloneElement` quando recebe `htmlFor`. **Não** apontar "label sem `for`/input sem `id`" nos formulários que usam `FormField`.
- Catch "amplo" de unique em `ProjectService::guardPrefix` **não** causa erro visível hoje (o único unique de `projects` é o do prefixo); o achado (CR-1-03) é de convenção/robustez, não de bug ativo. Não elevar a criticidade sem um segundo unique na transação.

## Padrões recorrentes a vigiar

- Bloco ramificado de `main` reimplementando (ou deixando de ter) a infraestrutura do B1 — conferir sempre a base da branch antes de apontar "não seguiu o padrão do Núcleo": pode ser que o padrão ainda não exista na base.
