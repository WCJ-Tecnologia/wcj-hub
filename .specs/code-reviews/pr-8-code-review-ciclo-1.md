# Code Review — `chore/marca-wcj` · Ciclo 1

**Data:** 2026-10-09
**História:** PR-8 ([WCJ-Tecnologia/wcj-hub#8](https://github.com/WCJ-Tecnologia/wcj-hub/pull/8), "Front: componentes shadcn, Atomic Design, Storybook e marca WCJ", autor Lucas Varanda)
**Branch:** `chore/marca-wcj` (`c4f0317`, 2 commits) · **Base:** `main` (`2056834`, branch do ambiente)
**Spec:** não mapeada (chore sobre o B0; decisões nos ADR-003 e ADR-004)
**Revisor:** Claude Code (sessão de revisão, contexto zerado; worktree `wcj-hub-pr8`)
**Ciclos anteriores:** nenhum
**Escopo deste ciclo:** a branch inteira contra a `main`: +9.873 / −2.134 em 178 arquivos. Troca `components/ui/` por `components/shadcn/`, move os componentes do Hub para `atoms/molecules/organisms/templates/pages`, substitui sidebar, toast, tabela, paginação, Select e campo de data pelos do shadcn, adiciona Storybook 10 (103 stories em 38 arquivos, confirmado por `rg`) e aplica a marca WCJ (dois azuis, cantos de 5 px, logos oficiais, Space Mono, e-mails, manifest). Nenhuma rota, Policy, FormRequest, migração ou regra de negócio foi tocada; no backend só `config/app.php`, `HandleInertiaRequests` (sai `app.brand.logo`) e os Blade de e-mail.

**Decisões já tomadas antes deste ciclo (não são achados):** o Lucas Scholz aprovou em 09/10, no próprio PR, o ADR-003 e o ADR-004, inclusive a troca do Select e do `<input type="date">` nativos pelos do shadcn (diverge do `.ai/stacks/ui.md` L66 de propósito), e deu ao Varanda autonomia nesse tipo de escolha de front.

---

## Como este ciclo verificou

| Verificação | Resultado |
|---|---|
| CI do PR (`gh pr checks 8`, run `37944806057`) | **JS (typecheck + lint + build + storybook): FAIL em 9 s** no `npm ci`; **E2E: skipping**; PHP (Pint + PHPUnit em MySQL 8.4): pass; Secrets scan: pass |
| `npm ci` local (node 24.14.1, npm 11.11.0) | mesmo erro do CI: `Missing: @emnapi/core@1.11.3 from lock file`, `@emnapi/runtime@1.11.3` |
| `npm install` local + checagens | lock muda 23+/19−; `npm run typecheck` OK; `npm run lint` OK; `npm run build` OK (3,86 s); `npm run build-storybook` OK (17,6 s); `npm run test:guard` 1053 pass / 0 fail. O lock foi restaurado depois (`git status` limpo) |
| e2e (Playwright + axe) | **não rodado** nesta revisão (precisa do Laravel e do MySQL); o CI pulou. O "13/13" do corpo do PR está sem evidência |
| `rg "components/ui/|@/layouts/" resources/js e2e .storybook` (ADR-003, "Como verificar") | vazio |
| `rg "text-primary([^-]|$)"` e hex em `resources/js` (ADR-004) | vazio; `text-primary-text` é o único azul de texto |
| SVGs de `public/brand/` × `docs/brand/logo/` e `docs/brand/nos/` (`cmp`) | os 7 arquivos são **idênticos** ao mestre |
| `@font-face` × `public/fonts/` | os 8 arquivos citados existem |
| Segredo no diff (`rg` + gitleaks do CI) | nenhum |
| Conflito com os PRs abertos (`git merge-tree --write-tree`) | com `feat/b1-nucleo` (#5): **9 conflitos**; com `feat/b2-projeto-e-configuracao` (#6): 1 conflito. Detalhe no CR-1-02 |

---

## Achados do Ciclo 1

## [ALTO] CR-1-01 — CI do PR vermelho: `npm ci` recusa o `package-lock.json`; typecheck, lint, build, Storybook e e2e não rodaram

**Arquivo:** `package-lock.json` (job `JS` do `.github/workflows/ci.yml:49-64`)
**Trecho atual:**
```text
npm error `npm ci` can only install packages when your package.json and package-lock.json
or npm-shrinkwrap.json are in sync. Please update your lock file with `npm install` before continuing.
npm error Missing: @emnapi/core@1.11.3 from lock file
npm error Missing: @emnapi/runtime@1.11.3 from lock file
```
(run `37944806057`, job `JS`, 2026-10-09 14:31; reproduzido local com o mesmo erro)

**Ajuste necessário:**
Regenerar o lock (`npm install`, com o npm da equipe) e commitar. Conferir o CI verde, inclusive o e2e com axe, antes de pedir o merge. No corpo do PR, trocar "typecheck, lint e build verdes" e "E2E 13/13" pelo link do run verde.

**Motivo:**
O corpo do PR afirma tudo verde e o CI diz o contrário (inegociável 1: evidência antes de afirmação). Como o job JS caiu no `npm ci`, nada depois dele rodou, e o job E2E, que é o gate de acessibilidade do projeto (axe em claro, escuro e com modal), foi pulado. Localmente, com o lock regenerado, typecheck, lint, build, Storybook e guard passam: o defeito é só o lock, mas hoje ninguém pode provar isso pelo PR.

**Sugestão:**
`npm install && git add package-lock.json && git commit -m "chore(deps): sincroniza o package-lock com o package.json"`. Se o time usa versões diferentes de npm (o lock veio de uma máquina, o CI usa Node 22), fixar `engines`/`.nvmrc` evita repetir.

**Impacto:**
PR não mergeável enquanto o CI estiver vermelho; o e2e com axe, que os dois ADRs citam como prova, continua sem rodar.

---

## [ALTO] CR-1-02 — A fundação nasce do B0 e colide com os PRs #5 (B1) e #6 (B2): 9 conflitos de merge, 51 arquivos dos outros PRs com import que deixa de existir e os componentes do B1 ausentes aqui

**Arquivo:** `resources/js/components/` (toda a reestruturação), `AGENTS.md:112-131`, `resources/js/components/pages/w-users-page/w-users-page.tsx:67`
**Trecho atual:**
```text
$ git merge-tree --write-tree chore/marca-wcj feat/b1-nucleo
CONFLICT (content): AGENTS.md
CONFLICT (modify/delete): resources/js/components/app-sidebar.tsx
CONFLICT (content): resources/js/components/molecules/w-row-actions-molecule/w-row-actions-molecule.tsx
CONFLICT (content): resources/js/components/organisms/w-data-table-organism/w-data-table-organism.tsx
CONFLICT (file location): resources/js/components/users/user-table.tsx
CONFLICT (content): resources/js/components/organisms/w-user-form-dialog-organism/w-user-form-dialog-organism.tsx
CONFLICT (content): resources/js/components/shadcn/masked-input.tsx
CONFLICT (modify/delete): resources/js/layouts/app-layout.tsx
CONFLICT (content): resources/js/pages/admin/audit-logs/index.tsx
CONFLICT (content): resources/js/pages/admin/users/index.tsx
CONFLICT (content): resources/views/mail/temporary-password.blade.php

$ rg -l "@/components/ui/|@/layouts/" resources/js   # em feat/b1-nucleo: 30 arquivos; em feat/b2-…: 21
```
```tsx
// w-users-page.tsx:67 — padrão do B0 que o B1 substituiu pelo useRowAction (CV-09)
const [busy, setBusy] = useState(false);
```

**Ajuste necessário:**
Decisão do Lucas Scholz sobre a ordem de integração, antes de qualquer merge, registrada no corpo deste PR. Este PR precisa declarar que depende dela e o que vai portar.

**Motivo:**
O PR #8 partiu da `main` (B0) e renomeia ou apaga exatamente os arquivos que o B1 (#5, pronto, Verifier PASS, aguardando o merge do Lucas) e o B2 (#6) editam: `components/ui/*` vira `components/shadcn/*`, `layouts/` some, `data-table`, `row-actions`, `search-input`, `status-pill`, `user-form-dialog`, `app-sidebar` mudam de pasta e de nome. Depois do merge do #8, 30 arquivos do B1 e 21 do B2 importam caminhos inexistentes e nenhum dos dois compila. Além disso, o #8 não contém nada do que o B1 acrescentou ao front e que passou por cinco ciclos de review e pelo Verifier: `ListBody` + `useListVisit` (estados de lista, CV-07), `ShowInactiveToggle`, `useRowAction` + `RowActions busy/disabled` (trava de clique duplo, CV-09), `StatusPill kind="contact"/"audit"`, `DataTable rowClassName`, `SearchInput` que espera a escrita em voo (CR-1-10 do B1), os componentes de clientes, contatos e auditoria e a tabela "Componentes e hooks do projeto" do `AGENTS.md`. Quem resolver os conflitos sem esse mapa perde comportamento validado sem perceber (ex.: a tela de usuários daqui volta ao `useState(busy)` do B0). O corpo do PR não cita o #5 nem o #6.

**Sugestão:**
Recomendação: mergear o **#5 primeiro** (está pronto e só espera o merge), depois o #8 faz rebase sobre a `main` e porta o B1 para a estrutura atômica no próprio PR: `ListBody` e `ShowInactiveToggle` viram molécula/organismo `w-*`, os hooks ficam em `hooks/`, `WStatusPillAtom` ganha os dicionários `contact` e `audit`, `WDataTableOrganism` ganha `rowClassName`, `WRowActionsMolecule` ganha `busy`/`disabled`, `WSearchInputMolecule` recebe a versão do B1, as telas de clientes/contatos/auditoria entram em `pages/` e `organisms/`, e a tabela do `AGENTS.md` passa a usar os nomes `W*`. Em seguida o #6 faz o mesmo rebase (1 conflito, 21 imports). Quem é dono da estrutura é quem deve portar; a alternativa (#8 primeiro) joga 51 arquivos de retrabalho para o Cauã e o Vitinho em paralelo e sem mapa. Em qualquer ordem: rodar os e2e do B1 (`e2e/clique-duplo.spec.ts` entre eles) depois da integração e registrar no PR a lista acima com o que foi portado.

**Impacto:**
Mergear o #8 como está deixa #5 e #6 sem compilar; resolver os conflitos tela a tela, sem o mapa, descarta a trava de clique duplo, os estados de lista e a busca em voo que o B1 provou.

---

## [MÉDIO] CR-1-03 — ADR-004 duplicado: `004-marca-wcj.md` aqui × `004-ficha-do-cliente.md` no PR #5

**Arquivo:** `docs/adr/004-marca-wcj.md:1`; `AGENTS.md:115`, `design/README.md:9,12`, `scripts/build-theme.mjs:73,157`, `resources/css/fonts.css:7`, `resources/js/components/atoms/w-brand-logo-atom/w-brand-logo-atom.md:60`, `resources/views/components/mail-layout.blade.php:10`, `resources/js/components/atoms/w-brand-logo-atom/w-brand-logo-atom.tsx` (comentários "ADR-004")
**Trecho atual:**
```text
$ git ls-tree --name-only feat/b1-nucleo docs/adr/
docs/adr/000-modelo.md  001-codigo-por-dominio.md  002-mysql-nos-testes.md  004-ficha-do-cliente.md
$ git ls-tree --name-only chore/marca-wcj docs/adr/
docs/adr/000-modelo.md  001-…  002-…  003-componentes-atomicos-e-storybook.md  004-marca-wcj.md
```

**Ajuste necessário:**
Renumerar o ADR da marca (005, salvo acerto com o Cauã) e trocar todas as citações "ADR-004" dos arquivos deste PR.

**Motivo:**
O B1 pulou o 003 (reservado para este PR, ao que tudo indica) e usou o 004 para a ficha do cliente. Depois dos dois merges, "ADR-004" aparece no `AGENTS.md` com dois sentidos, e o `.specs/STATE.md` não consegue registrar `AD-004` apontando para dois arquivos (ADR-000, modelo).

**Sugestão:**
`git mv docs/adr/004-marca-wcj.md docs/adr/005-marca-wcj.md` e `rg -n "ADR-004" AGENTS.md design resources scripts docs/adr/005-marca-wcj.md` para a troca. Combinar no PR #5 que o 004 fica com a ficha do cliente.

**Impacto:**
Referência ambígua em documento que a IA e o time usam como fonte das decisões.

---

## [MÉDIO] CR-1-04 — `scripts/shadcn.mjs` executa `npx shadcn@latest` sem versão e edita `package.json` sozinho

**Arquivo:** `scripts/shadcn.mjs:29,47`
**Trecho atual:**
```js
run('npx', ['shadcn@latest', ...process.argv.slice(2)]);
// …
if (pkg.dependencies?.cn || pkg.devDependencies?.cn) run('npm', ['uninstall', 'cn']);
```

**Ajuste necessário:**
Fixar a versão do CLI (a que gerou os primitivos deste PR) e deixar o `npm uninstall` como aviso, não como ação automática.

**Motivo:**
`@latest` baixa e executa, a cada uso, o código mais novo do registro, e esse CLI gera arquivos que entram no repositório. O projeto fixa tudo o mais pelo lock. O ADR-003 e o `components/README.md` já citam que o registro novo mudou o import do `cn`: é o tipo de mudança que chega sem aviso quando a versão flutua. O `npm uninstall` automático altera `package.json` e lock por fora do que o dev pediu.

**Sugestão:**
`shadcn` em `devDependencies` (versão exata) e `run('npx', ['shadcn', ...])`, ou `shadcn@3.x.y` no script; no final, `console.log('O CLI instalou o pacote "cn"; remova com npm uninstall cn')` em vez de rodar.

**Impacto:**
Primitivo gerado com uma versão diferente da documentada entra no repo sem ninguém notar; reprodutibilidade do `npm run shadcn` entre máquinas.

---

## [MÉDIO] CR-1-05 — `WDatePickerMolecule` perde `aria-invalid` e `aria-describedby` dentro do `WFormFieldMolecule`, que é o uso que o manual recomenda

**Arquivo:** `resources/js/components/molecules/w-date-picker-molecule/w-date-picker-molecule.tsx:10-24,61-66`; `w-date-picker-molecule.md` ("Formulário com `useForm`"); `resources/js/components/molecules/w-form-field-molecule/w-form-field-molecule.tsx:28-35`
**Trecho atual:**
```tsx
// w-form-field-molecule.tsx: injeta no filho único
cloneElement(children as ReactElement<ControlProps>, {
    id: children.props.id ?? htmlFor,
    'aria-invalid': children.props['aria-invalid'] ?? Boolean(error),
    'aria-describedby': children.props['aria-describedby'] ?? (error || hint ? messageId : undefined),
})
// w-date-picker-molecule.tsx: só `id` existe nas props; os dois aria-* caem no chão
export interface WDatePickerMoleculeProps { value; onChange; placeholder?; label?; id?; disabled?; clearable?; className? }
<Button id={id} type="button" variant="outline" aria-label={label ? … : undefined} …>
```

**Ajuste necessário:**
Aceitar `'aria-invalid'?: boolean` e `'aria-describedby'?: string` nas props e repassar ao `Button` gatilho (e `aria-invalid` ao estilo de erro do botão).

**Motivo:**
O `cloneElement` passa os atributos como props do componente, não do DOM. O `id` chega ao botão porque está declarado; `aria-invalid` e `aria-describedby` não estão, então o TypeScript não acusa (o cast para `ControlProps` esconde) e o `<p role="alert" id="due_on-error">` nunca fica ligado ao campo. É a regra do `.ai/stacks/ui.md` §6 ("erro ligado ao campo por `aria-describedby` e `aria-invalid`") quebrada no exemplo que o próprio `.md` do componente manda copiar. Hoje nenhuma tela usa o date picker dentro de formulário (a auditoria usa `label`), então o axe não pega; o B2 (data de entrega do projeto, `projects.due_on`) é o primeiro que vai usar.

**Sugestão:**
```tsx
'aria-invalid'?: boolean; 'aria-describedby'?: string;
…
<Button id={id} aria-invalid={ariaInvalid} aria-describedby={ariaDescribedby} className={cn(…, ariaInvalid && 'border-destructive ring-destructive/30')} …>
```
e uma story "Com erro" dentro do `WFormFieldMolecule`.

**Impacto:**
Erro de validação de data invisível para leitor de tela e sem destaque visual no campo, na primeira tela que usar o componente em formulário.

---

## [BAIXO] CR-1-06 — ADR-003 e ADR-004 seguem "proposta / pendente", e o `.specs/STATE.md` não tem os `AD-`

**Arquivo:** `docs/adr/003-componentes-atomicos-e-storybook.md:4-5`, `docs/adr/004-marca-wcj.md:4-5`, `.specs/STATE.md:5-21`
**Trecho atual:**
```markdown
**Estado:** proposta
**Decidido por:** pendente (Lucas Scholz)
```

**Ajuste necessário:**
`**Estado:** aprovada` e `**Decidido por:** Lucas Scholz, 2026-10-09 (PR #8)`; `AD-003` e o da marca (número conforme o CR-1-03) no `.specs/STATE.md`, seção Decisions, como o `docs/adr/000-modelo.md` manda.

**Motivo:**
A aprovação aconteceu em 09/10 no comentário do PR; o repositório ainda diz pendente, e a skill `tlc-spec-driven` lê as decisões ativas pelo `STATE.md` (`.ai/core/especificacao.md`, seção 5). Decisão registrada só em parte dos documentos é o AP-04 do projeto.

**Sugestão:**
Dois blocos `AD-00N` no molde dos existentes (Decision, Reason, Trade-off, Scope, Date, Status).

**Impacto:**
A próxima sessão que desenhar tela não encontra as decisões onde o processo manda procurar.

---

## [BAIXO] CR-1-07 — `APP_BRAND_LOGO` morto em `.env.testing:17` e `.env.e2e.example:14`

**Arquivo:** `.env.testing:17`, `.env.e2e.example:14`
**Trecho atual:**
```env
APP_BRAND_LOGO=/brand/logo.svg
```

**Ajuste necessário:**
Apagar as duas linhas.

**Motivo:**
O `config/app.php` deixou de ler a variável e o `/brand/logo.svg` foi apagado; o `.env.example` foi limpo, estes dois não. Variável de ambiente que ninguém lê confunde quem configura o e2e.

**Sugestão:**
Remover; `rg APP_BRAND_LOGO` fica só nos ADR e no `.md` do logo, que a citam como histórico.

**Impacto:**
Nenhum em execução; ruído de configuração.

---

## [BAIXO] CR-1-08 — `calendar.tsx` usa tamanho de texto arbitrário (`text-[0.8rem]`)

**Arquivo:** `resources/js/components/shadcn/calendar.tsx:75,78`
**Trecho atual:**
```tsx
weekday: cn('flex-1 rounded-md text-[0.8rem] font-normal text-muted-foreground select-none', …),
week_number: cn('text-[0.8rem] text-muted-foreground select-none', …),
```

**Ajuste necessário:**
Trocar por utilitário nomeado (`text-xs`).

**Motivo:**
`.ai/stacks/ui.md` §6: "Tamanho de texto só por utilitário nomeado (`text-2xs` é o menor). `text-[Npx]` é proibido". É o gabarito do shadcn, mas `calendar` já é um primitivo customizado deste PR (o `components/README.md` lista os que não se sobrescrevem), e é o único ponto de `resources/js` fora da regra.

**Sugestão:**
`text-xs` nas duas linhas.

**Impacto:**
Padronização; nenhum efeito funcional.

---

## Registrado sem achado (não é FP: o Lucas foi consultado só no que está marcado)

- **Select e date picker do shadcn no lugar dos nativos** (`.ai/stacks/ui.md` L66): **aprovado pelo Lucas Scholz em 09/10** no PR #8. Não reabrir. Vale levar ao template `ai-systems`, como o ADR-003 sugere.
- Sentinela `all` dos Selects × servidor: Usuários manda `role_id` ausente (`UserController.php:37`, `integer('role_id')`), Auditoria manda `''` com `nullable` + `! empty` (`AuditLogController.php:20-31`). Coerente com o que o nativo mandava.
- `WPaginationMolecule.kindOf` depende dos rótulos em inglês ou das chaves `pagination.*`: o componente do B0 tinha a mesma dependência, o `locale` é `en` e não há `lang/`. Não é regressão.
- `WAuthLayoutTemplate` põe o `h2` do painel da marca antes do `h1` da tela: `heading-order` é best-practice do axe, fora das tags WCAG do gate do projeto. Não apontado.
- Cookie `sidebar_state` gravado no cliente sem `SameSite`: preferência de UI, sem dado; o navegador aplica `Lax`.
- E-mails: hex inline é a exceção documentada do ADR-004 (cliente de e-mail não lê CSS); o `asset('brand/wcj-icone-64.png')` existe no PR; "PNG do logo completo" segue como pendência declarada.
- Os 7 SVG oficiais são idênticos ao mestre (`cmp`), como o ADR-004 exige; as 8 fontes citadas existem.
- `.storybook/main.ts` usa `import.meta.resolve` (Node ≥ 20.6): o CI usa Node 22. `staticDirs: ['../public']` serve também `public/build` se existir: inofensivo.
- `.storybook/inertia-mock.tsx` e `fixtures.ts`: `processing` sempre `false` e `e-mails @exemplo.wcj.test`: dado de exemplo no padrão do `ExemploSeeder`, sem dado real.
- `WStatCardMolecule` e `WEmptyStateMolecule` renderizam ícone do lucide sem `aria-hidden` explícito: o `lucide-react` já emite `aria-hidden="true"` por padrão.
- Storybook: 103 stories em 38 arquivos, confirmado; `build-storybook` passa localmente com o lock regenerado.

## Segurança (as seis perguntas de `.ai/core/seguranca.md` §11)

1. Rota de dados com `auth` e Policy: **nenhuma rota tocada.**
2. Rota que recebe ID valida o vínculo: **nenhuma tocada.**
3. Entrada validada no servidor / upload: nenhum FormRequest mudou; os valores novos do front (`all`, `''`, `AAAA-MM-DD`) caem nas validações existentes (acima).
4. Regra de negócio só na tela: nenhuma regra nova; o date picker declara no `.md` que intervalo e mínimo são do servidor.
5. Segredo em código, front, git ou documento: **nenhum** (`rg` no diff; gitleaks do CI em todo o histórico: pass).
6. Limite de tentativas em login e recuperação: não tocado (`e2e/auth.spec.ts:37-43` segue cobrindo o 429).

Inegociável 8 (duas camadas, escopo por ID) e 9 (regra no servidor): sem mudança. Inegociável 11 (porta de entrada): nenhuma entidade criada ou alterada.

---

## Resumo executivo

| Criticidade | Achados | Na revisão em camadas |
|---|---|---|
| Crítico | 0 | — |
| Alto | 2 (CR-1-01, CR-1-02) | **bloqueantes**: o CI está vermelho e o merge quebra os PRs #5 e #6 |
| Médio | 3 (CR-1-03, CR-1-04, CR-1-05) | deveria corrigir |
| Baixo | 3 (CR-1-06, CR-1-07, CR-1-08) | detalhe |

**Achados anteriores:** não há (ciclo 1). **Regressões:** 0.

**Principais riscos:**
1. PR afirma verde com CI vermelho; o gate de acessibilidade (e2e com axe) não rodou. Causa provada: só o `package-lock.json`.
2. Ordem de integração com #5 e #6 não decidida nem declarada; 9 conflitos e 51 imports quebrados; os componentes e hooks do B1 (clique duplo, estados de lista, busca em voo) não existem nesta branch.
3. Numeração de ADR em colisão com o #5.

**Avaliação geral:** o trabalho é consistente e bem documentado: cada componente tem `.md` e story, os primitivos customizados estão listados, os ADRs explicam o porquê, os SVG são os oficiais sem retoque, não há hex nem `text-primary` fora da regra, os wrappers do Inertia têm uma linha, o mock do Inertia no Storybook é limpo, e a troca para shadcn resolve de fato o que o ADR-003 aponta (gaveta sem trava de foco, toast à mão). Os dois bloqueantes não são de código: um é o lock, o outro é de sequência de merge, e a decisão é do Lucas Scholz.

**Recomendação final: não apta para merge** enquanto o CI estiver vermelho (CR-1-01) e a ordem com o #5/#6 não estiver decidida e refletida no PR (CR-1-02). Resolvidos esses dois, **apta com ajustes** (CR-1-03 a CR-1-08).
