# LESSONS - auto-maintained by scripts/lessons.py

> Machine-owned. Do NOT hand-edit. Changes are overwritten on the next `lessons.py` write.
> Canonical state lives in `.specs/lessons.json`. Edit lessons only via the script.
> promote_threshold=2 distinct features · window_days=45 · quarantine_threshold=2

## Confirmed (load these at Specify/Design)

Corroborated across multiple features. Safe to apply as guidance.

### L-016 - Toda ability da Policy, inclusive a sem chamador (view por registro) e a exclusão, precisa de um teste positivo por quem pode, não só de negação.
- signal: `ac_gap` · recurrence: 2 feature(s) · scope: `tests/policy` · harmful: 0
- features: b5-sprint-e-horas, b3-quadro-e-card
- evidence: SPR-01 AC1 / DEL-01 AC1 (tests/policy) (+1 more)
- last seen: 2026-10-19T18:13:49Z

## Candidates (under observation - do NOT load as guidance yet)

Seen once or not yet corroborated. Tracked, not trusted.

### L-001 - When an AC requires several writes in the same transaction, force a failure in the last write and assert the earlier writes rolled back
- signal: `surviving_mutant` · recurrence: 1 feature(s) · scope: `services` · harmful: 0
- features: b1-nucleo
- evidence: validation.md M10, M33 (app/Platform/Services/UserService.php:122, :107) (services)
- last seen: 2026-10-06T21:16:54Z

### L-002 - Assert every column the spec lists for an audit row by value on the success path, not only on the denied path
- signal: `surviving_mutant` · recurrence: 1 feature(s) · scope: `auditoria` · harmful: 0
- features: b1-nucleo
- evidence: validation.md M34 (app/Platform/Services/AuditService.php:38) (auditoria)
- last seen: 2026-10-06T21:16:54Z

### L-003 - When an AC quotes a UI text or state, cover it with an e2e or component assertion, since backend tests only prove the data behind it
- signal: `ac_gap` · recurrence: 1 feature(s) · scope: `telas` · harmful: 0
- features: b1-nucleo
- evidence: validation.md C9, C13, A3, A10, C4, K12, U13, P3 AC1 (telas) (+1 more)
- last seen: 2026-10-07T16:06:02Z

### L-004 - Do not write an HTTP status AC for a capability the design exposes without a route; phrase it at the Policy or Service level
- signal: `spec_precision_gap` · recurrence: 1 feature(s) · scope: `spec` · harmful: 0
- features: b1-nucleo
- evidence: validation.md P2 AC3 (design D6, sem rota) (spec) (+2 more)
- last seen: 2026-10-06T22:09:34Z

### L-005 - Settle in the spec whether a business-rule refusal answers 403 or a flash error before implementing, so the three CRUDs do not deviate together
- signal: `spec_deviation` · recurrence: 1 feature(s) · scope: `spec` · harmful: 0
- features: b1-nucleo
- evidence: UserController.php:74, ClientController.php:225, ContactController.php:357 (spec) (+2 more)
- last seen: 2026-10-06T22:09:35Z

### L-006 - When a rule singles out a record by a key, test a record that keeps the key with other attributes changed and one that copies the other attributes without the key
- signal: `surviving_mutant` · recurrence: 1 feature(s) · scope: `policies` · harmful: 0
- features: b1-nucleo
- evidence: validation.md rodada 2 R06 (app/Platform/Models/Client.php:37) (policies)
- last seen: 2026-10-06T21:52:54Z

### L-007 - Pin every branch boundary of a check-digit or threshold rule with fixed cases, since a factory built on the same function cannot expose a shifted boundary
- signal: `surviving_mutant` · recurrence: 1 feature(s) · scope: `rules` · harmful: 0
- features: b1-nucleo
- evidence: validation.md rodada 3 S10 (app/Rules/AlphanumericCnpj.php:45) (rules) (+1 more)
- last seen: 2026-10-06T22:23:44Z

### L-008 - When a stub makes a UI variant unreachable in e2e, keep asserting the spec text by rewriting the server response instead of switching to the reachable variant
- signal: `surviving_mutant` · recurrence: 1 feature(s) · scope: `e2e` · harmful: 0
- features: b1-nucleo
- evidence: T11 resources/js/pages/admin/clients/index.tsx:37 (rodada 5; e2e/textos-nucleo.spec.ts:34-53 trocou a asserção do texto com N pela variante zero) (e2e) (+1 more)
- last seen: 2026-10-07T16:36:43Z

### L-009 - When a spec adds a contract for another domain's data, list every datum the screens need through it, so the contract does not grow outside the spec
- signal: `spec_deviation` · recurrence: 1 feature(s) · scope: `contracts` · harmful: 0
- features: b1-nucleo
- evidence: app/Platform/Contracts/ClientManagement.php:22-27 openProjectsCount (rodada 5, declarado pelo dono) (contracts)
- last seen: 2026-10-07T16:06:02Z

### L-010 - When an e2e intercepts a response to force a screen state, await that response before asserting and prove the state was absent first, or the test passes on the old state and dies mid-route
- signal: `gate_fail` · recurrence: 1 feature(s) · scope: `e2e` · harmful: 0
- features: b1-nucleo
- evidence: e2e/textos-nucleo.spec.ts:221 (e2e) (+1 more)
- last seen: 2026-10-08T15:23:26Z

### L-011 - When a spec revision promotes UI text variants into an AC, add an assertion for each variant in the same task that edits the spec
- signal: `ac_gap` · recurrence: 1 feature(s) · scope: `e2e` · harmful: 0
- features: b1-nucleo
- evidence: Clientes AC 9 (N = 1), resources/js/pages/admin/clients/index.tsx:35 (e2e) (+1 more)
- last seen: 2026-10-08T15:23:26Z

### L-012 - When a retry helper declares that it does not retry inside a transaction opened by the caller, test that branch by calling it inside an outer transaction
- signal: `surviving_mutant` · recurrence: 1 feature(s) · scope: `services` · harmful: 0
- features: b1-nucleo
- evidence: validation.md rodada 10, mutante g2 (app/Platform/Services/Concerns/RetriesDeadlock.php:40) (services)
- last seen: 2026-10-09T14:12:49Z

### L-013 - When a model helper opens its own transaction, test it called outside any transaction with DatabaseTruncation and a failing callback, since RefreshDatabase and transactional callers hide a missing transaction
- signal: `surviving_mutant` · recurrence: 1 feature(s) · scope: `models` · harmful: 0
- features: b1-nucleo
- evidence: mutante d, app/Platform/Models/Concerns/Deactivatable.php:138 (rodada 12) (models)
- last seen: 2026-10-09T15:01:42Z

### L-014 - In a search test, pin every column the search reads in the fixtures, since a random factory value can match the normalized term
- signal: `gate_fail` · recurrence: 1 feature(s) · scope: `tests` · harmful: 0
- features: b1-nucleo
- evidence: tests/Feature/Nucleo/ClientServiceTest.php:508 (rodada 12, 1a execucao) (tests)
- last seen: 2026-10-09T15:01:42Z

### L-015 - Prove a negação auditada contando exatamente uma linha denied nova por requisição, com user_id, nunca lendo latest('id').
- signal: `surviving_mutant` · recurrence: 1 feature(s) · scope: `tests/negacao` · harmful: 0
- features: b5-sprint-e-horas
- evidence: tests/Feature/Projetos/SprintPolicyTest.php:191 (tests/negacao)
- last seen: 2026-10-15T15:29:13Z

### L-017 - Escrita mais auditoria 'na mesma transação' só se prova com DatabaseTruncation e um AuditService que lança; RefreshDatabase esconde a falta da transação.
- signal: `surviving_mutant` · recurrence: 1 feature(s) · scope: `tests/transacao` · harmful: 0
- features: b5-sprint-e-horas
- evidence: SprintService::delete sem DB::transaction (tests/transacao)
- last seen: 2026-10-15T15:29:13Z

### L-018 - Toda ability da Policy, inclusive a sem chamador (view por registro) e a exclusão, precisa de um teste positivo por quem pode, não só de negação.
- signal: `surviving_mutant` · recurrence: 1 feature(s) · scope: `tests/policy` · harmful: 0
- features: b3-quadro-e-card
- evidence: validation.md p4 p16 p7 p8 p9 p10 (TaskPolicy view/comment/attach/archive/delete) (tests/policy)
- last seen: 2026-10-19T18:13:37Z

### L-019 - Toda migração cujo down() a spec promete precisa de um teste que rode o down() e o up() de novo no MySQL, porque o MySQL recusa voltar a not null uma coluna com FK que o up() deixou nula.
- signal: `ac_gap` · recurrence: 1 feature(s) · scope: `migrations` · harmful: 0
- features: b3-quadro-e-card
- evidence: validation.md MIG-01 AC 5; 2026_10_16_000010 down() erro 1832 (migrations)
- last seen: 2026-10-19T18:13:37Z

### L-020 - Para provar que um passo roda na transação de quem chama, faça o próprio passo falhar e afirme que as escritas anteriores voltaram; uma falha depois dele não distingue.
- signal: `surviving_mutant` · recurrence: 1 feature(s) · scope: `tests/transacao` · harmful: 0
- features: b3-quadro-e-card
- evidence: validation.md s3 (ProjectService::create, createDefaults fora da transação) (tests/transacao)
- last seen: 2026-10-19T18:13:37Z

### L-021 - Quando a spec diz o auditable_type da linha denied, o helper de negação afirma tipo e id, e a spec fecha qual tipo grava a ability que recebe a classe e o projeto.
- signal: `spec_precision_gap` · recurrence: 1 feature(s) · scope: `tests/negacao` · harmful: 0
- features: b3-quadro-e-card
- evidence: validation.md TSK-04 AC 1, sonda pd1 (tests/negacao)
- last seen: 2026-10-19T18:13:37Z

### L-022 - Recusa de duplicado por escopo precisa de teste pela rota com o repetido no mesmo escopo (mensagem e nada gravado) e o mesmo valor em outro escopo (aceito), porque o unique do banco esconde a falta da checagem.
- signal: `surviving_mutant` · recurrence: 1 feature(s) · scope: `tests/duplicado` · harmful: 0
- features: b3-quadro-e-card
- evidence: validation.md rodada 2 u2, u3, u4 (TaskTypeService::create/update) (tests/duplicado)
- last seen: 2026-10-19T19:28:45Z

### L-023 - Porta nova que entra em par (desativar e reativar) ganha negação no teste de comportamento para as duas; a que ficar de fora só morre no teste de arquitetura.
- signal: `surviving_mutant` · recurrence: 1 feature(s) · scope: `tests/negacao` · harmful: 0
- features: b3-quadro-e-card
- evidence: validation.md rodada 2 ac2 (TaskTypeController::reactivate) (tests/negacao)
- last seen: 2026-10-19T19:28:45Z

## Quarantined (failed when applied - ignore)

A confirmed lesson that recurred alongside failure. Kept for the maintainer to review.

_none_
