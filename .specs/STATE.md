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

## Handoff

- **Feature**: b1-nucleo (B1 Núcleo: usuários, clientes, contatos e auditoria), trilho completo.
- **Phase / Task**: Execute concluído (T1–T50, fases 1–13). Fase 12 (T37–T44): achados de código A1–A4 e A6–A9 da review do Lucas Scholz no PR #5 (07/10). Fase 13 (T45–T50): decisões D1–D11 (D2 aviso de e-mail por papel, D3 telefone texto, D9 carga sem passo; D1, D4–D8, D10, D11 na spec e nos documentos) e o A5 (Page Objects esperam a busca; @critical 5 vezes seguidas na suíte inteira com banco recriado). Último Verifier: PASS da rodada 6 (07/10), anterior à review; o `validation.md` é dessa rodada.
- **Completed**: B0 (PR #1, mergeado em 06/10); B1 T1–T50.
- **In-progress** (file:line): none
- **Next step**: Verifier independente focado na review (achados A1–A9 e decisões D1–D11, com a tabela "Decisões da review de 07/10" do `design.md`), que gera o `validation.md` novo; depois push da `feat/b1-nucleo` e resposta à review no PR #5, quando o Cauã decidir. Para o B2: os seis casos do `ClientProjectsTest` removido voltam no teste da `ProjectClientManagement`.
- **Blockers**: none
- **Uncommitted files**: none
- **Branch**: feat/b1-nucleo
