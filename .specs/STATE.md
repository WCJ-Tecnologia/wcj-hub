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
- **Phase / Task**: Specify aprovada por Lucas Scholz em 06/10/2026. Design e Tasks concluídos por Cauã em 06/10/2026 (`design.md`, `tasks.md` com 24 tarefas em 6 fases, `validate_tasks.py` e `conferir-specs.mjs` limpos). Próxima fase: Execute a partir de T1.
- **Completed**: B0 (PR #1, mergeado em 06/10).
- **In-progress** (file:line): none
- **Next step**: Cauã roda Design (opcional) e Tasks → Execute em `feat/b1-nucleo`, PR em rascunho → Verifier → pronto. Em paralelo, B2 (Vitinho, trilho leve, sem pasta em `.specs/`).
- **Blockers**: none
- **Uncommitted files**: none
- **Branch**: feat/b1-nucleo
