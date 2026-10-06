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

### AD-003
- **Decision**: O Núcleo pergunta a Projetos quais clientes cada gestor gere e quantos projetos abertos o cliente tem pela interface `App\Platform\Contracts\ClientProjects`, implementada em `App\Domains\Projetos\Services\ProjectClientProjects` e ligada no `AppServiceProvider`. Ver `docs/adr/003-nucleo-consulta-projetos-por-contrato.md`.
- **Reason**: as Policies de clientes e contatos do B1 precisam dessa resposta, e o A9 proíbe o Platform de ler `projects` e `project_members` (D1 do design do B1).
- **Trade-off**: interface com uma implementação só, exceção consciente à regra das duas implementações.
- **Scope**: `ClientPolicy`, `ContactPolicy` e a desativação do cliente no B1; toda pergunta nova do Núcleo sobre projetos.
- **Date**: 2026-10-06
- **Status**: active (aceita por Cauã; a confirmar pelo Lucas Scholz no PR do B1)

### AD-004
- **Decision**: A ficha do cliente é uma página própria (`pages/admin/clients/show.tsx`, `GET /admin/clients/{client}`) com as seções Dados e Contatos; criar e editar o cliente seguem no modal da lista. Ver `docs/adr/004-ficha-do-cliente.md`.
- **Reason**: o cliente tem mais de um bloco (dados e contatos) e o F1 cria o contato a partir do cliente; o gabarito de UI pede página com ADR nesse caso (D7 do design do B1).
- **Trade-off**: duas telas para o cliente; sem componente de abas, as "abas" do F1 são seções da página até haver um terceiro bloco.
- **Scope**: `ClientController::show`, a ficha e a fase de contatos do B1, que põe *Novo contato* e as ações do contato nela.
- **Date**: 2026-10-06
- **Status**: active (aceita por Cauã; a confirmar pelo Lucas Scholz no PR do B1)

## Handoff

- **Feature**: b1-nucleo (B1 Núcleo: usuários, clientes, contatos e auditoria), trilho completo.
- **Phase / Task**: Execute concluído, T1 a T25 (25 tarefas em 6 fases, um commit por tarefa em `feat/b1-nucleo`). Gate Build verde no fim do lote: PHPUnit em MySQL, typecheck, lint, build, `test:guard` e Playwright (smoke com axe nos dois temas e o `@critical` de `e2e/nucleo.spec.ts`).
- **Completed**: B0 (PR #1, mergeado em 06/10); B1 T1–T25.
- **In-progress** (file:line): none
- **Next step**: Verifier em agente separado (quem não implementou), sobre `spec.md` e o intervalo de commits do B1, com o sensor de mutação nas regras de permissão, exclusão e anonimização; depois PR em rascunho e revisão do Lucas Scholz. Pendências para o PR: SPEC_DEVIATION dos três `destroy` (toast em vez de 403, AC 12/12/9) e a busca da auditoria por chave de card ("SGM-17"), que entra com o B3 pelo domínio de Projetos (A9).
- **Blockers**: none
- **Uncommitted files**: none
- **Branch**: feat/b1-nucleo
