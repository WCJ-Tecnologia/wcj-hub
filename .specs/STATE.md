# STATE: WCJ Hub

Decisões de projeto (`AD-NNN`) e o ponto de retomada da skill `tlc-spec-driven` (`.ai/core/especificacao.md`). Cada `AD` aponta para o ADR em `docs/adr/`.

## Decisions

### AD-001
- **Decision**: Código por domínio desde o primeiro commit: `app/Platform` (Núcleo) e `app/Domains/<Módulo>`, com FK só de Domains para Platform. Ver `docs/adr/001-codigo-por-dominio.md`.
- **Reason**: o Hub nasce com dois módulos e já tem CRM e Chamados marcados; mover depois conflita com todo PR aberto (D-GER-15).
- **Trade-off**: os testes de arquitetura e o registro de Policy, factory e morph map precisam conhecer as pastas novas.
- **Scope**: todo o backend; regras A7 e A9 do `tests/Arch/ArquiteturaTest.php`.
- **Date**: 2026-10-06
- **Status**: active

### AD-002
- **Decision**: Toda a suíte roda em MySQL 8 (local e CI), nunca SQLite. Ver `docs/adr/002-mysql-nos-testes.md`.
- **Reason**: colunas geradas, CHECK, `LAST_INSERT_ID(expr)`, colação sem acento e trava de linha só existem no MySQL (D-GER-22, T6).
- **Trade-off**: o MySQL do `docker-compose.yml` precisa estar de pé para rodar `php artisan test`; a suíte fica uns segundos mais lenta.
- **Scope**: `phpunit.xml`, `.env.testing`, `.env.e2e.example`, `.github/workflows/ci.yml`.
- **Date**: 2026-10-06
- **Status**: active

### AD-004
- **Decision**: A ficha do cliente é uma página própria (`pages/admin/clients/show.tsx`, `GET /admin/clients/{client}`) com as seções Dados e Contatos; criar e editar o cliente seguem no modal da lista. Ver `docs/adr/004-ficha-do-cliente.md`.
- **Reason**: o cliente tem mais de um bloco (dados e contatos) e o F1 cria o contato a partir do cliente; o gabarito de UI pede página com ADR nesse caso (D7 do design do B1).
- **Trade-off**: duas telas para o cliente; sem componente de abas, as "abas" do F1 são seções da página até haver um terceiro bloco.
- **Scope**: `ClientController::show`, a ficha e a fase de contatos do B1, que põe *Novo contato* e as ações do contato nela.
- **Date**: 2026-10-06
- **Status**: active (aceita por Cauã; a confirmar pelo Lucas Scholz no PR do B1)

### AD-003
- **Decision**: Front em Atomic Design: primitivos do shadcn em `components/shadcn/`, componentes do Hub em `components/{atoms,molecules,organisms,templates,pages}/w-*`, telas reexportadas de `resources/js/pages/`, Storybook 10 no CI; Select, Sidebar, Table, Pagination, Sonner e date picker do shadcn no lugar dos nativos. Ver `docs/adr/003-componentes-atomicos-e-storybook.md`.
- **Reason**: catálogo e manual por componente para quem monta tela com IA; peças à mão sem trava de foco; o Select e o date nativos ficavam fora do tema.
- **Trade-off**: diverge do `.ai/stacks/ui.md` do template (Select nativo, `components/ui/`, `layouts/`); perde o seletor do sistema no celular; um passo a mais no CI.
- **Scope**: `resources/js/components/**`, `.storybook/`, `components.json`, `scripts/shadcn.mjs`, `.github/workflows/ci.yml`.
- **Date**: 2026-10-09
- **Status**: active

### AD-005
- **Decision**: Identidade visual da WCJ no Hub: dois azuis (`primary` #1A6CF3 em botão e foco, `primaryText` #0F4FC4 em texto), cantos de 5 px, logos oficiais sem configuração (`WBrandLogoAtom`), Space Mono em eyebrow, rótulo e número, e-mails com hex da marca, manifest. Ver `docs/adr/005-marca-wcj.md`.
- **Reason**: o Hub é só da WCJ; o azul da marca reprova no AA como texto, então o texto usa o tom profundo.
- **Trade-off**: a escala de raio do `build-theme.mjs` diverge da do template; trocar logo é trocar SVG a partir do mestre da marca.
- **Scope**: `design/tokens.json`, `scripts/build-theme.mjs`, `resources/css/`, `public/brand/`, `resources/views/components/mail-layout.blade.php`.
- **Date**: 2026-10-09
- **Status**: active

## Handoff

- **Feature**: b1-nucleo (B1 Núcleo: usuários, clientes, contatos e auditoria), trilho completo.
- **Phase / Task**: Execute concluído (T1–T51, fases 1–14). Review do Lucas Scholz no PR #5 (07/10) aplicada nas fases 12–14: achados A1–A9 corrigidos e decisões D1–D11 na spec, no F1, no escopo e na necessidade. Verifier PASS na rodada 8 (focada) em 08/10/2026 (`validation.md`).
- **Completed**: B0 (PR #1, mergeado em 06/10); B1 T1–T50.
- **In-progress** (file:line): none
- **Next step**: Lucas Scholz roda a escada de novo e revisa o PR #5; teste cruzado do Vitinho; merge. Para o B2: `ProjectClientManagement` com `manages`, `managedClientIds` e `openProjectsCountByClient`, trocando o `give()` do `MemoizedClientManagement`, e os seis casos do `ClientProjectsTest` removido (commit `d434e4e`).
- **Blockers**: none
- **Uncommitted files**: none
- **Branch**: feat/b1-nucleo
