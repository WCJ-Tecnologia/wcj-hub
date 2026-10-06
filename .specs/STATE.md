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

## Handoff

- **Feature**: b1-nucleo (B1 Núcleo: usuários, clientes, contatos e auditoria), trilho completo.
- **Phase / Task**: Specify concluída em 06/10/2026 (`.specs/features/b1-nucleo/spec.md`, `validate_spec.py` limpo). **Aprovada por Lucas Scholz em 06/10/2026.** Próxima fase: Tasks (Cauã).
- **Completed**: B0 (PR #1, mergeado em 06/10).
- **In-progress** (file:line): none
- **Next step**: Cauã roda Design (opcional) e Tasks → Execute em `feat/b1-nucleo`, PR em rascunho → Verifier → pronto. Em paralelo, B2 (Vitinho, trilho leve, sem pasta em `.specs/`).
- **Blockers**: none
- **Uncommitted files**: none
- **Branch**: feat/b1-nucleo
