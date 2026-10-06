# B1 Núcleo Validation (rodada 4, focada)

## Validation: PASS

**Result**: PASS. A rodada 4 é uma reverificação focada, autorizada pelo dono do projeto depois da reprovação da rodada 3 (limite de 3 rodadas atingido; o humano escolheu corrigir e reverificar). O único sobrevivente da rodada 3, **S10** (`$remainder < 2` → `$remainder <= 2`, `app/Rules/AlphanumericCnpj.php:45`), agora morre por caso fixo: `tests/Unit/AlphanumericCnpjTest.php:21` (`00000000000191`, válido com resto 2) e `:41` (`00000000000108`, inválido). O S10b (`< 1`) também morre por caso fixo (`:22`, `00000000000604`, resto 1), não mais só pela factory. Gate verde na árvore real: `composer lint` ok, `php artisan test` **284 passed (2378 assertions)**. Clientes AC 1 e AC 2 passam de parciais a ✅. Continuam pendentes de decisão do Lucas Scholz os desvios declarados (seção "Declared Deviations"), que não são gap de teste. O PASS não encerra a feature (`.ai/core/especificacao.md` §5 item 9): seguem a revisão em camadas, o print do Playwright no PR e a revisão de segurança dirigida.

**Date**: 2026-10-06
**Spec**: `.specs/features/b1-nucleo/spec.md` (aprovada por Lucas Scholz, 2026-10-06)
**Diff range (rodada 4)**: `a610d47..2a32842`, um commit só (`2a32842`, T31). `git diff a610d47..HEAD --stat`: `.specs/features/b1-nucleo/tasks.md` (+38/−1) e `tests/Unit/AlphanumericCnpjTest.php` (+6). Nenhum arquivo de produção mudou desde a rodada 3, então as citações das rodadas anteriores fora desse teste continuam valendo.
**Verifier (rodada 4)**: subagente independente (autor ≠ verifier), escopo focado no S10 e em Clientes AC 1 e 2, por autorização do dono.

**Diff range (rodada 3)**: `origin/main...HEAD` = `9c4a67a..a610d47` (branch `feat/b1-nucleo`, 41 commits de `d4dfc4e` a `a610d47`, um deles o merge `f105928` de `origin/main`; 104 arquivos, +10693/−571). Desde a rodada 2 (`f105928`) entrou só o `a610d47` (T30), que muda `tests/Feature/Nucleo/ClientPolicyTest.php` (+23) e `.specs/features/b1-nucleo/tasks.md`. `git log HEAD..origin/main` vazio: a branch está em dia com a `main`.
**Verifier**: subagente independente (autor ≠ verifier), rodada 3 de no máximo 3. Partiu do relatório da rodada 2. As citações fora de `ClientPolicyTest.php` foram conferidas por amostragem: as linhas de C10, U12, C9, C14 e da WCJ no `ClientServiceTest` continuam iguais, como esperado, porque `git diff f105928 HEAD` só toca aquele teste e o `tasks.md`. A parte que a T30 afetou (C10, Clientes AC 14, o sensor) foi refeita por inteiro.

---

## Histórico das rodadas

| Rodada | HEAD | Veredito | Motivo | O que fechou |
|---|---|---|---|---|
| 1 | `02492d6` | FAIL | 3 mutantes sobreviventes (M10: `deactivate` sem transação; M33: revogação da IA depois do commit; M34: `ip_address` nulo), 11 critérios de texto ou estado de tela sem asserção, P3 AC 1 sem evidência | Fase 7: T26 (atomicidade), T27 (IP na auditoria), T28 (textos no e2e), T29 (estados das listas). Só testes |
| 2 | `f105928` | FAIL | 1 mutante sobrevivente: R06, `Client::isWcj()` trocado pela comparação do nome passava a suíte | T30 (`a610d47`): WCJ renomeada continua protegida; homônimo com outro CNPJ pode ser desativado e excluído |
| 3 | `a610d47` | FAIL | 1 mutante sobrevivente: S10, borda do dígito verificador do CNPJ (fora do P0) | Escala ao humano (limite de 3 rodadas). O dono escolheu corrigir: T31 (`2a32842`), só teste |
| 4 (focada, autorizada pelo dono) | `2a32842` | **PASS** | S10 e S10b mortos por caso fixo; mais 2 mutações na mesma regra mortas | - |

---

## Task Completion

| Task | Status | Notes |
| ---- | ------ | ----- |
| T1–T29 | ✅ Done | - |
| T30 | ✅ Done | Os 4 "Done when" estão marcados. O "R06 derrubado" foi conferido de novo pelo Verifier: os 2 testes novos pegam o mutante |
| T31 | ✅ Done | Os 3 "Done when" estão marcados e foram conferidos na rodada 4: os quatro CNPJs fixos estão em `AlphanumericCnpjTest.php:21-23,41`; S10 e S10b morrem por caso fixo; gate verde |

`tasks.md` (rodada 4): 31 tarefas `### T`, 115 caixas marcadas, 0 abertas.

---

## Gate Check

### Rodada 4 (focada)

| Passo | Onde | Resultado |
|---|---|---|
| `composer lint` | árvore real | `{"tool":"pint","result":"passed"}`, exit 0 |
| `php artisan test` | árvore real | **284 passed (2378 assertions)**, 0 failed, 0 skipped (26,0 s), exit 0 |

A rodada 3 tinha 280 testes; a T31 acrescentou 4 casos de data provider (3 válidos, 1 inválido) e 4 asserções. Nenhum teste removido nem enfraquecido (`git diff a610d47..HEAD -- tests` só acrescenta linhas). Front e e2e não foram refeitos: nenhum arquivo fora de `tests/Unit/AlphanumericCnpjTest.php` e do `tasks.md` mudou desde a rodada 3, quando passaram (typecheck, lint, guard, build, Playwright 48/48). A porta 8000 e o `public/hot` não foram tocados.

### Rodada 3

Gate **Build** de `tasks.md` §Gate Check Commands, rodado em partes:

| Passo | Onde | Resultado |
|---|---|---|
| `composer lint` | árvore real | `{"tool":"pint","result":"passed"}`, exit 0 |
| `php artisan test` | árvore real | **280 passed (2374 assertions)**, 0 failed, 0 skipped (28,5 s) |
| `npm run typecheck` | árvore real | exit 0 |
| `npm run lint` | árvore real | exit 0 |
| `npm run test:guard` | árvore real | exit 0. 1053 testes: 1024 pass, 0 fail, 29 skipped. Os 29 pulados dizem "gitleaks não está instalado; o CI roda estes casos" e ficam fora do diff da feature |
| `npm run build` | worktree descartável `/tmp/tlc-b1-nucleo-e2e3` | exit 0 (`✓ built in 3.41s`) |
| `npx playwright test` | árvore real, `E2E_BASE_URL=http://127.0.0.1:8001` | **48 passed** (56,1 s), exit 0 |

Como o e2e rodou:

1. `npm run e2e:prepare` recriou o banco `wcj_hub_e2e`.
2. Uma worktree descartável do HEAD recebeu `vendor` e `node_modules` por `ln -s`, a cópia do `.env.e2e` (sem ler o conteúdo) e o `npm run build` feito ali mesmo.
3. `APP_ENV=e2e php artisan serve --host=127.0.0.1 --port=8001` subiu a partir dessa worktree.

A porta 8000 (o `composer dev` do usuário, pid 88857) e o `public/hot` ficaram intactos. No fim, só o `php artisan serve` da 8001 e o filho dele foram encerrados, e a worktree foi removida.

- **Test count before feature** (`origin/main`): 76 passed (medido na rodada 1; a `main` só mudou o `composer.lock` desde então).
- **Test count after feature**: 280 passed. Rodada 2 tinha 278; a T30 acrescentou 2 testes. **Delta**: +204. Nenhum teste removido nem enfraquecido: a T30 só acrescenta (`git show a610d47 -- tests`).
- **Skipped (PHP)**: nenhum.

---

## Spec-Anchored Acceptance Criteria

Legenda:

- ✅ coberto com o valor da spec;
- ⚠️ parcial, ou spec-precision gap;
- 🔶 desvio declarado (`SPEC_DEVIATION`);
- ❌ GAP.

Linhas conferidas no HEAD `a610d47` (rodada 3); C1 e C2 reconferidas no `2a32842` (rodada 4). `ClientServiceTest.php:75-83` e `:89-94` continuam iguais. "e2e" quer dizer que o teste rodou nesta verificação.

### P1: Convidar e gerir usuários

| # | Spec-defined outcome | `file:line` + assertion | Result |
|---|---|---|---|
| U1 | usuário com `must_change_password = true`; e-mail ao endereço com URL do Hub, login e senha provisória | `tests/Feature/Nucleo/UserServiceTest.php:115` `assertTrue($maria->must_change_password)`; `:122` `Hash::check($plain, $maria->password)`; `:125-130` URL `route('login')`, e-mail, `$plain`, `'Login: maria@example.com'` | ✅ |
| U2 | senha nunca na tela nem em `audit_logs.changes` | `UserServiceTest.php:140` `assertStringNotContainsString($plain, json_encode(AuditLog::pluck('changes')))`, `:141` `assertArrayNotHasKey('password', …)`; `UserControllerTest.php:96` senha fora da sessão | ✅ |
| U3 | senha nova, a anterior inválida, mesmo e-mail | `UserServiceTest.php:159-163` `isReset === true`, `Hash::check($plain)`, `assertFalse(Hash::check('antiga-senha', …))`, auditoria `password_reset` | ✅ |
| U4 | "Carga diária é obrigatória para Desenvolvedor", sem criar nem alterar | `UserServiceTest.php:175,185` `assertSame(['daily_hours' => ['Carga diária é obrigatória para Desenvolvedor']], $e->errors())`; `:178,187-190` nada criado nem alterado; `UserControllerTest.php:117` | ✅ |
| U5 | carga ≤ 0 ou > 24: erro no campo | `UserServiceTest.php:203-211` `'0','-1','24.01'` → `assertSessionHasErrors('daily_hours')`, 24 aceito; `MensagensEmPortuguesTest.php:49,51` | ✅ (texto não definido na spec: precision gap leve) |
| U6 | papel gravado e `revoked_at` nas ativas, na mesma transação | `UserServiceTest.php:224-229`; `:365-375` falha na revogação → `assertUntouched` | ✅ (M33 morto na rodada 2) |
| U7 | marca gravada e `revoked_at` nas ativas | `UserServiceTest.php:243-250`; `:378-388` falha desfaz tudo | ✅ (S04 morto nesta rodada) |
| U8 | `deactivated_at`, `deactivated_by`, sessões encerradas, IA revogada, na mesma transação | `UserServiceTest.php:270-276`; `:334-342`, `:345-362` falha desfaz tudo | ✅ (M10 morto na rodada 2; S08 morto nesta rodada) |
| U9 | login recusado com a mensagem genérica; sessão existente rejeitada | `UserServiceTest.php:278-281` `INVALID_CREDENTIALS`, `assertGuest()`; `tests/Feature/BackofficeAccessTest.php:64-65` | ✅ |
| U10 | limpa `deactivated_at`/`deactivated_by` sem recriar IA | `UserServiceTest.php:413-416` | ✅ |
| U11 | apaga e grava `deleted` | `UserServiceTest.php:426-433` `assertModelMissing`, `changes` exatos | ✅ |
| U12 | **403** com "Usuário já usado: desative em vez de excluir" e a linha fica | `UserServiceTest.php:449,451,453` mensagem exata, `assertModelExists`, 0 `deleted`; `UserControllerTest.php:170,172` | 🔶 Desvio declarado (`app/Platform/Controllers/Admin/UserController.php:74`): 302 + toast; "nada muda" testado |
| U13 | colunas; desativados escondidos; visíveis em cinza pelo filtro | `UserControllerTest.php:59-81`; e2e `e2e/textos-nucleo.spec.ts:107,109,113-114` | ✅ |
| U14 | último admin ativo não é rebaixado, desativado nem excluído | `UserServiceTest.php:460-478` três recusas com texto exato, nada muda, 0 auditoria; `:481-484`; `:487-496` | ✅ (S03 morto nesta rodada) |
| U15 | gestor e dev: 403, quantidade e dados sem mudança nas 6 ações | `UserControllerTest.php:213` `assertForbidden()`; `:214` `denied`; `:217` snapshot igual; `:218` 0 success; `:219` nenhum e-mail | ✅ |
| U16 | `audit_logs` com `user_id`, `action`, `outcome = success`, `auditable`, `changes` sem senha | helper `UserServiceTest.php:92-105`, usado em `:132,163,229,250,276,416,427` | ✅ |

### P1: Cadastrar clientes

| # | Spec-defined outcome | `file:line` + assertion | Result |
|---|---|---|---|
| C1 | CNPJ válido de 14 caracteres: cliente criado com documento sem máscara, `deactivated_at` nulo, `created` | `tests/Feature/Nucleo/ClientServiceTest.php:75-83` `'document' => '12ABC34501DE35'`, `changes` exatos; `ClientControllerTest.php:131-133`; `tests/Unit/AlphanumericCnpjTest.php:15-27` quatro válidos; e2e `e2e/nucleo.spec.ts:21-23` | ✅ (rodada 4). `AlphanumericCnpjTest.php:21` `'00000000000191'` (restos 2 e 10), `:22` `'00000000000604'` (resto 1), `:23` `'00000000001406'` (resto 0), todos por `:30` `assertNull($this->erro($cnpj))`. S10 e S10b morrem por esses casos. Na rodada 3 era ⚠️ parcial por S10 |
| C2 | BR vazio ou inválido: erro no campo, sem criar | `ClientServiceTest.php:89-94` `'Informe o CNPJ.'`, `'Informe um CNPJ válido.'`, 0 clientes, 0 auditoria; `ClientControllerTest.php:147,151`; `AlphanumericCnpjTest.php:30-49` oito inválidos | ✅ (rodada 4). `AlphanumericCnpjTest.php:41` `'00000000000108'` (o que o "resto 2 vira 0" geraria) por `:54` `assertSame('Informe um CNPJ válido.', $this->erro($cnpj))`; S10 morre aqui também. Texto não definido na spec: precision gap leve. Na rodada 3 era ⚠️ parcial por S10 |
| C3 | "Já existe o cliente X com este CNPJ", ignorando caixa, sem criar nem alterar | `ClientServiceTest.php:103-114`; `:121` mesmo documento em outro país não é repetido; `ClientControllerTest.php:138-139`; e2e `nucleo.spec.ts:28` | ✅ (S05 morto nesta rodada) |
| C4 | fora do BR aceita vazio e rotula "Identificador fiscal" | `ClientServiceTest.php:136-141`; e2e `textos-nucleo.spec.ts:21-29` | ✅ |
| C5 | gestor cria pelo Service, mesmas validações, auditoria dele | `ClientServiceTest.php:188-196`; `ClientControllerTest.php:225-226` | ✅ |
| C6 | gestor edita cliente de projeto que gere | `ClientServiceTest.php:206-214`; `ClientControllerTest.php:228-230`; `ClientPolicyTest.php:51-52,72` matriz | ✅ |
| C7 | gestor: 403 e nada muda | `ClientControllerTest.php:252` `assertForbidden()`, `:253` `denied`, `:256` snapshot igual, `:257` 0 success | ✅ (S02 morto nesta rodada) |
| C8 | dev: 403 em toda rota | `ClientControllerTest.php:270-287` | ✅ |
| C9 | desativa com projetos abertos após "O cliente X tem N projetos abertos; eles continuam. Desativar?" e sai das listas | `ClientServiceTest.php:223-230`; `ClientControllerTest.php:112`; e2e `textos-nucleo.spec.ts:44-46` texto exato | ✅ |
| C10 | **403** com "A WCJ não pode ser desativada" para o cliente de documento `38614817000122` | `ClientControllerTest.php:187-192` `assertForbidden()->assertSee('A WCJ não pode ser desativada')`, segue ativa, `denied` gravado; `ClientServiceTest.php:254-261` Service recusa e 0 auditoria; `ClientPolicyTest.php:90-101` mensagem exata; **`:120-129`** WCJ renomeada: `assertSame('A WCJ não pode ser desativada', …inspect('deactivate', $wcj)->message())` e `'A WCJ não pode ser excluída'`; **`:131-140`** homônimo "WCJ Tecnologia" com outro CNPJ: `inspect('deactivate')->allowed()` e `inspect('delete')->allowed()`; `:142-148` mesmo documento em `PT` não é a WCJ | ✅ (R06 morto nesta rodada: 2 testes) |
| C11 | apaga e grava `deleted` | `ClientServiceTest.php:279-288` | ✅ |
| C12 | **403** com "Cliente já usado: desative em vez de excluir" e a linha fica | `ClientServiceTest.php:304,306,308`; `ClientControllerTest.php:202,204` | 🔶 Desvio declarado (`ClientController.php:96`); "nada muda" testado |
| C13 | colunas, busca, filtros País e Situação, "Nenhum cliente ainda" com *Novo cliente* | `ClientControllerTest.php:52-78,89-98`; e2e `textos-nucleo.spec.ts:71-73` | ✅ |
| C14 | WCJ semeada: "WCJ Tecnologia", `BR`, `38614817000122` | `tests/Feature/SementesEExemploTest.php:41-42`; `ClientPolicyTest.php:106-117` a semeada não se exclui nem sem histórico | ✅ |

### P1: Contatos do cliente

| # | Spec-defined outcome | `file:line` + assertion | Result |
|---|---|---|---|
| K1 | contato no cliente, e-mail e telefone opcionais, `created` | `tests/Feature/Nucleo/ContactServiceTest.php:70-80`, `:86-89` | ✅ |
| K2 | nome vazio ou cliente inativo: erro, sem criar | `ContactServiceTest.php:103-113`; `:125-129` `['client' => ['Cliente desativado: reative o cliente para cadastrar contato.']]` | ✅ (S09 morto; texto não definido: precision gap leve) |
| K3 | salva e devolve "Já existe Fulano (Cliente X) com este e-mail" | `ContactServiceTest.php:142-144`; `ContactControllerTest.php:144,157` | ✅ |
| K4 | gestor do cliente cria e edita | `ContactServiceTest.php:181-185`; `ContactControllerTest.php:271-276` | ✅ |
| K5 | gestor fora do cliente ou nas ações do admin: 403 e nada muda | `ContactControllerTest.php:302-307` | ✅ (S01 morto nesta rodada) |
| K6 | dev: 403 | `ContactControllerTest.php:320-337` | ✅ |
| K7 | `deactivated_at`/`deactivated_by`, fora das listas, mantido no card | `ContactServiceTest.php:199-204` | ✅ |
| K8 | apaga o nunca citado e grava `deleted` | `ContactServiceTest.php:229-234` | ✅ |
| K9 | **403** com "Contato já usado: desative em vez de excluir" | `ContactServiceTest.php:247,250-251`; `ContactControllerTest.php:206,208` | 🔶 Desvio declarado (`ContactController.php:77`); "nada muda" testado |
| K10 | numa transação: nome trocado, e-mail e telefone apagados, `changes` limpos, `anonymized` sem dado pessoal | `ContactAnonymizeTest.php:67-73`, `:85-99`, `:102`; `:149-169` falha no meio desfaz tudo | ✅ |
| K11 | confirmação diferente de `ANONIMIZAR`: recusa, nada muda | `ContactControllerTest.php:230-238` | ✅ (texto não definido: precision gap leve) |
| K12 | situação "Anonimizado", sem editar, reativar nem anonimizar | `ContactControllerTest.php:87-91`, `:256` 403 + `'Contato anonimizado: não pode ser alterado'`; e2e `textos-nucleo.spec.ts:89-93` | ✅ |
| K13 | lista geral, buscas, filtros; gestor só nos clientes dele | `ContactControllerTest.php:65-91`, `:102-106`, `:119-129` | ✅ (S07 morto nesta rodada) |

### P1: Auditoria

| # | Spec-defined outcome | `file:line` + assertion | Result |
|---|---|---|---|
| A1 | toda escrita com `user_id`, `ai_authorization_id`, `action`, `outcome`, `auditable_type` (alias), `auditable_id`, `changes`, `ip_address` | helpers `UserServiceTest.php:92-105`, `ClientServiceTest.php:46-57`, `ContactServiceTest.php:42-57`; `IpNaAuditoriaTest.php:28-30` | ✅ (S11 morto nesta rodada: `changes` só com o que mudou) |
| A2 | negação: `denied`, quem tentou, ação, `auditable_id` quando conhecido | `NegacaoAuditadaTest.php:22-27,43-48`; `AuditServiceTest.php:36-44`; `IpNaAuditoriaTest.php:38-41` | ✅ |
| A3 | colunas, mais recente primeiro, páginas de 50, negadas em destaque | `AuditLogQueryTest.php:192-196`, `:218-232`, `:141-143`; `AuditLogControllerTest.php:61-75`; e2e `textos-nucleo.spec.ts:134-136` | ✅ |
| A4 | detalhe com campo, antes e depois | `AuditLogQueryTest.php:258-273`; e2e `nucleo.spec.ts:42-43` | ✅ |
| A5 | filtros e período padrão de 7 dias | `AuditLogQueryTest.php:79-81,109-111,121,136-138,154-157,177-178` | ✅ |
| A6 | *Histórico* filtrado por tipo e id, sem limite de período | `AuditLogControllerTest.php:90-99`; `AuditLogQueryTest.php:98-99`; e2e `nucleo.spec.ts:40-41` | ✅ |
| A7 | gestor e dev: 403 | `AuditLogControllerTest.php:118` (data provider), `:120-125` `denied` gravado | ✅ (S06 morto nesta rodada) |
| A8 | nenhuma rota de editar ou apagar | `AuditLogControllerTest.php:134-140` | ✅ |
| A9 | anonimização altera só os campos pessoais das linhas do contato | `AuditServiceTest.php:77-85`; `ContactAnonymizeTest.php:102` | ✅ |
| A10 | "Nenhum registro com esses filtros" | `AuditLogQueryTest.php:303-304`; e2e `textos-nucleo.spec.ts:138-140`; `estados-das-listas.spec.ts:16-19` | ✅ |

### P2: Listas de escolha

| # | Spec-defined outcome | `file:line` + assertion | Result |
|---|---|---|---|
| E1 | gestor: só `id`, `name`, `role` dos ativos | `UserServiceTest.php:546-548`; `UserPolicyTest.php:48` | ✅ |
| E2 | gestor: só `id` e `name` dos clientes ativos | `ClientServiceTest.php:318`; `ClientPolicyTest.php:25,68-70` (`viewChoiceList` na matriz) | ✅ |
| E3 | dev: **403** | `UserPolicyTest.php:48`, `ClientPolicyTest.php:25,68-70` (ability negada) | ⚠️ Spec-precision gap / desvio declarado: o design D6 não cria rota, então não há 403 HTTP; a Policy está testada. Pendente do Lucas |

### P3: Estados de tela e textos

| # | Spec-defined outcome | `file:line` + assertion | Result |
|---|---|---|---|
| T1 | em cada lista: vazio em uma frase com o botão de criar, erro e carregando | e2e `e2e/estados-das-listas.spec.ts:10-19`, `:22-39`, `:46-58`; textos em `e2e/pages/list.page.ts:80-117` | ✅ (Contatos oferece *Ir para Clientes*; Auditoria, *Limpar filtros*) |
| T2 | recusas e avisos em português | `MensagensEmPortuguesTest.php:31-75`; textos exatos em U4, C3, C10, K3, K9, K12 | ✅ |

**Status (rodada 4)**: ✅ **58/58** critérios com evidência `arquivo:linha`:

- **54** com o valor exato da spec (C1 e C2 deixaram de ser parciais);
- **3** desvios declarados, com "nada muda" testado (U12, C12, K9), pendentes do Lucas;
- **1** spec-precision gap (E3), pendente do Lucas.

Na rodada 3 eram 52 exatos, 3 desvios, 2 parciais (C1 e C2, por S10) e 1 gap.

Há ainda precision gaps leves, onde a spec não define o texto: U5, C2, K2, K11 e o edge case do último admin. **C10 passou de parcial a ✅** nesta rodada.

---

## Edge Cases

- [x] Convite simultâneo do mesmo e-mail: `UserServiceTest.php:500-516`.
- [x] E-mail do convite falha: `UserServiceTest.php:519-535`; `UserControllerTest.php:105-107`.
- [x] CNPJ com máscara: `ClientServiceTest.php:71-83`; `AlphanumericCnpjTest.php:17,59-60`. A borda do dígito verificador passou a ser coberta na rodada 4 (`:21-23,41`).
- [x] País sai do BR: `ClientServiceTest.php:164-181`.
- [~] Último admin desativa a si mesmo: `UserServiceTest.php:462` "…Promova outra pessoa antes de desativar.". Precision gap: a spec pede "a mensagem do template", e o template não tem texto próprio para desativar. Pendente do Lucas.
- [x] Anonimização que falha no meio: `ContactAnonymizeTest.php:149-170`.
- [x] Período maior que 1 ano: `AuditLogQueryTest.php:110`.
- [x] Negação com `auditable_id` desconhecido: `NegacaoAuditadaTest.php:27`; `ContactControllerTest.php:292,303`.

---

## Discrimination Sensor

Worktree descartável `/tmp/tlc-b1-nucleo-s3` (`git worktree add --detach … HEAD`):

- `vendor` **copiado** com `cp -a`, sem `ln -s`; `node_modules` por `ln -s`; `.env.testing` versionado.
- Antes de qualquer mutante, `ReflectionClass::getFileName()` mostrou `Client`, `UserService`, `ContactPolicy` e `Tests\TestCase` carregando de `/tmp/tlc-b1-nucleo-s3/...`. A suíte sem mutação passou ali: **280 passed (2374 assertions)**.
- Cada mutante foi aplicado por troca exata de texto (uma ocorrência só), passou no `php -l`, rodou o `php artisan test` inteiro e foi revertido com `git checkout --`. O `git status --porcelain` da worktree ficou vazio depois de cada reversão.

### Rodada 4 (focada, autorizada pelo dono)

Worktree descartável `/tmp/tlc-b1-nucleo-s4` (`git worktree add --detach … HEAD`, no `2a32842`):

- `vendor` **copiado** com `cp -a` (diretório real, `test -L` falso), sem `ln -s`; `.env.testing` versionado.
- Antes de qualquer mutante, `ReflectionClass::getFileName()` mostrou `App\Rules\AlphanumericCnpj` em `/tmp/tlc-b1-nucleo-s4/app/Rules/AlphanumericCnpj.php` e `Tests\Unit\AlphanumericCnpjTest` em `/tmp/tlc-b1-nucleo-s4/tests/Unit/AlphanumericCnpjTest.php`.
- Sem mutação: `tests/Unit/AlphanumericCnpjTest.php` **17 passed (18 assertions)**; a suíte inteira ali, **284 passed (2378 assertions)**.
- Cada mutante foi aplicado por troca exata de texto (uma ocorrência só, conferida por script), passou no `php -l`, rodou o arquivo do teste e foi revertido com `git checkout --`; o `git status --porcelain` da worktree ficou vazio depois de cada reversão. S10 e S10b rodaram também a suíte inteira.

Restos dos casos fixos novos, calculados com a mesma fórmula (pesos e `ord - 48`):

| CNPJ | Resto 1º | Resto 2º | Papel no teste |
|---|---|---|---|
| `00000000000191` | 2 | 10 | válido, `:21` |
| `00000000000604` | 1 | 7 | válido, `:22` |
| `00000000001406` | 0 | 5 | válido, `:23` |
| `00000000000108` | - | - | inválido, `:41` (dígitos que o `<= 2` calcularia para a base `000000000001`) |

| # | File:line | Mutação | Killed? (casos fixos que falharam) |
|---|---|---|---|
| S10 | `app/Rules/AlphanumericCnpj.php:45` | `$remainder < 2` → `$remainder <= 2` | ✅ 2: `aceita` "primeiro dígito com resto 2" (`AlphanumericCnpjTest.php:21`) e `recusa com a mensagem do campo` "resto 2 tratado como 0" (`:41`). Suíte inteira: 2 failed, 282 passed |
| S10b | `AlphanumericCnpj.php:45` | `$remainder < 2` → `$remainder < 1` | ✅ 1 caso fixo: `aceita` "primeiro dígito com resto 1" (`:22`). Suíte inteira: 2 failed (o caso fixo e, como antes, `factory gera cnpj valido`) |
| S12 | `AlphanumericCnpj.php:41` | valor do caractere: letra vale `ord - 55` (A = 10), dígito segue `ord - 48` | ✅ 4: `aceita` "exemplo oficial da Receita", "com máscara", "em minúsculas"; `recusa` "primeiro dígito errado" |
| S13 | `AlphanumericCnpj.php:17` | peso do 2º dígito: `[6, 5, 4, …]` → `[5, 5, 4, …]` | ✅ 5: `aceita` "exemplo oficial da Receita", "com máscara", "em minúsculas", "numérico, o da WCJ"; `recusa` "segundo dígito errado" |

Discriminação: cada mutante cai em casos diferentes. O S10 só é pego pelos casos de resto 2 (`:21`, `:41`); o S10b só pelo de resto 1 (`:22`); o caso de resto 0 (`:23`) segura o ramo `0` sem depender da borda. As letras (S12) são pegas só pelos CNPJs alfanuméricos; o peso (S13), pelos que têm o 1º caractere diferente de zero.

**Resultado da rodada 4**: 4/4 mortas, todas por caso fixo. ✅

**Isolamento (rodada 4)**: `git status --porcelain` da árvore real gravado antes do gate e comparado depois de `git worktree remove --force /tmp/tlc-b1-nucleo-s4`: `diff` vazio. Só os três não rastreados de propósito (`.specs/LESSONS.md`, `.specs/features/b1-nucleo/validation.md`, `.specs/lessons.json`). `git worktree list` mostra só a árvore real, a pasta `/tmp/tlc-b1-nucleo-s4` não existe mais, o HEAD segue em `2a32842` e o `public/hot` continua lá.

### Rodada 3

| # | File:line | Mutação | Killed? (testes que pegaram) |
|---|---|---|---|
| R06 | `app/Platform/Models/Client.php:37` | `isWcj()` → `return $this->name === 'WCJ Tecnologia';` (repetido da rodada 2) | ✅ 2 (`a wcj renomeada continua protegida`, `outro cliente com o nome da wcj nao e a wcj`) |
| S01 | `app/Platform/Policies/ContactPolicy.php:72` | `managesClient`: o gestor gere qualquer cliente (sem `projects->manages`) | ✅ 5 |
| S02 | `app/Platform/Policies/ClientPolicy.php:41` | `update`: qualquer gestor edita qualquer cliente | ✅ 4 |
| S03 | `app/Platform/Services/UserService.php:213` | último admin: a contagem de "outros admins" inclui a própria pessoa (sem `whereKeyNot`) | ✅ 2 |
| S04 | `UserService.php:101` | trocar a marca "IA pode escrever" não revoga as conexões (`isDirty(['role_id'])`) | ✅ 2 |
| S05 | `app/Platform/Services/ClientService.php:189` | CNPJ repetido sem olhar o país | ✅ 1 (`mesmo documento em outro pais nao e repetido…`) |
| S06 | `app/Platform/Policies/AuditLogPolicy.php:15` | o gestor vê a auditoria | ✅ 4 |
| S07 | `app/Platform/Services/ContactService.php:209` | a lista geral de contatos não filtra os clientes do gestor | ✅ 1 (`gestor ve so os contatos dos clientes dele`) |
| S08 | `UserService.php:126` | desativar não encerra as sessões (sem `terminateOthers`) | ✅ 2 |
| S09 | `ContactService.php:62` | contato criado em cliente desativado | ✅ 2 |
| S10 | `app/Rules/AlphanumericCnpj.php:45` | dígito verificador: `$remainder < 2` → `$remainder <= 2` | ❌ **Sobreviveu** (280 passed) |
| S10b | `AlphanumericCnpj.php:45` | dígito verificador: `$remainder < 2` → `$remainder < 1` | ✅ 1, mas por amostra aleatória (`factory gera cnpj valido`, 20 CNPJs sorteados; morreu nas 3 execuções). Não há caso fixo |
| S11 | `app/Platform/Services/AuditService.php:124` | `changes` grava também os campos que não mudaram | ✅ 8 |

Classificação: R06 e S01–S09 ficam no caminho crítico (permissão, escopo do gestor, exclusão e WCJ, atomicidade e sessão na desativação). S10, S10b e S11 ficam no resto (validação de entrada, formato da auditoria).

**Por que o S10 sobrevive**: os CNPJs fixos dos testes têm resto 3 a 10 nos dois dígitos:

| CNPJ | Resto do 1º dígito | Resto do 2º dígito |
|---|---|---|
| `12ABC34501DE35` | 8 | 6 |
| `38614817000122` | 9 | 9 |

O ramo "resto < 2 → `0`" nunca é exercido com resto 2. A factory gera o CNPJ com o mesmo `withCheckDigits()` que a validação usa, então continua coerente com o mutante.

O efeito foi conferido na worktree, com o mutante aplicado:

- `00000000000191` (resto 2 no 1º dígito), um CNPJ verdadeiro: original "válido", mutante "Informe um CNPJ válido.";
- `38614817000122` e `11222333000181`: válidos nos dois;
- `00000000000108` (o que o mutante calcula para a mesma base): o mutante aceita, o original recusa.

Mais ou menos 1 CNPJ em cada 6 tem resto 2 em algum dos dois dígitos. Um mutante assim impediria cadastrar esses clientes (Clientes AC 1) e aceitaria o dígito errado no lugar (AC 2).

### Todas as rodadas

| Rodada | Mutações | Mortas | Sobreviventes |
|---|---|---|---|
| 1 | numeradas até M34; a tabela completa da rodada 1 não ficou no relatório, só as sobreviventes | não registrado | M10, M33, M34 |
| 2 | 15 (M10, M33, M34 repetidas + R01–R12) | 14 | R06 |
| 3 | 13 (R06 repetida + S01–S11, S10b) | 12 | S10 |
| 4 (focada) | 4 (S10, S10b repetidas + S12, S13) | 4 | nenhum |

Das rodadas 1 e 2, as mutações registradas estavam em: `UserService.php:122` (M10), `UserService.php:91,107` (M33), `AuditService.php:39` (M34), `ContactPolicy.php:62,77` (R01, R02), `ClientService.php:127` (R03), `ContactService.php:150` (R04), `Contact.php:58,67` (R05), `Client.php:37` (R06), `Controller.php:44` (R07), `AuditService.php:62` (R08), `UserPolicy.php:52` (R09), `AuditLogQuery.php:66` (R10), `AlphanumericCnpj.php:22` (R11), `UserService.php:202` (R12). As mutações S desta rodada caem em linhas e comportamentos diferentes de todas essas.

**Sensor depth**: P0, sem ferramenta (Infection não está instalado). Rodada 3: 13 mutações à mão (R06 repetida, 9 novas no caminho crítico e 3 no resto). Rodada 4: 4 mutações focadas na regra do CNPJ.
**Result**: rodada 4, 4/4 mortas. Somando as rodadas, nenhum sobrevivente em aberto. ✅ PASS.
Resultado da rodada 3: 12/13 mortas, reprovada por S10. Ele fica fora do caminho crítico P0, mas é um sobrevivente com efeito real em Clientes AC 1 e 2.

**Isolamento**: o `git status --porcelain` da árvore real foi gravado antes do gate e conferido depois do e2e e depois do sensor, com `diff` vazio nas duas vezes. Só aparecem os três arquivos não rastreados de propósito (`.specs/LESSONS.md`, `.specs/features/b1-nucleo/validation.md`, `.specs/lessons.json`). As worktrees `/tmp/tlc-b1-nucleo-e2e3` e `/tmp/tlc-b1-nucleo-s3` foram removidas; `git worktree list` mostra só a árvore real. O HEAD continua em `a610d47`, e a porta 8000 e o `public/hot` continuam intactos.

---

## Code Quality

| Principle | Status |
| --- | --- |
| Minimum code / surgical changes | ✅ A T30 é só teste (`ClientPolicyTest.php`, +23) e o registro dela no `tasks.md` |
| No scope creep | ✅ |
| Matches patterns | ✅ Usa `WcjClientSeeder::CNPJ`, `Gate::forUser()->inspect()` e a factory, como os testes vizinhos |
| Spec-anchored outcome check | ✅ Os valores batem com a spec; a borda do dígito verificador do CNPJ passou a ser discriminada na rodada 4 (`AlphanumericCnpjTest.php:21-23,41`) |
| T31 (rodada 4) | ✅ Só teste: 4 linhas no data provider e 2 comentários dizendo o porquê; nenhum código de produção. Mapeia para NUC-05 (Clientes AC 1 e 2) |
| Per-layer coverage | ✅ Services 1:1; rotas com caminho feliz, 403 com "nada muda" e validação; telas com textos e estados no e2e |
| Every test maps to a requirement | ✅ Os testes da T30 citam N7 e Clientes AC 6 no comentário (`ClientPolicyTest.php:119`) |
| Guidelines | `.ai/core/especificacao.md` §5–6, `.ai/core/testes.md` §5, §6, §10 |

Observação para o Lucas (não é gap, a spec não pede): a WCJ é reconhecida pelo país e pelo documento (Clientes AC 10, premissa "Cliente WCJ não desativável"), e o `update` do cliente permite editar os dois (`ClientPolicyTest.php:100`, `ClientService.php:77-86`). Se um administrador trocar o CNPJ ou o país da WCJ, ela perde a proteção do N7. Se isso não for aceitável, o caminho é uma regra a mais na spec.

---

## Declared Deviations (pendentes de decisão do Lucas Scholz)

| Ref | Spec | Implementado | "Nada muda" testado |
|---|---|---|---|
| `app/Platform/Controllers/Admin/UserController.php:74` | Usuários AC 12: 403 | 302 + toast `error` com a mesma mensagem | `UserControllerTest.php:172`; `UserServiceTest.php:451,453` |
| `app/Platform/Controllers/Admin/ClientController.php:96` | Clientes AC 12: 403 | idem | `ClientControllerTest.php:204`; `ClientServiceTest.php:306,308` |
| `app/Platform/Controllers/Admin/ContactController.php:77` | Contatos AC 9: 403 | idem | `ContactControllerTest.php:208`; `ContactServiceTest.php:250-251` |
| P2 AC 3 (sem marcador no código) | 403 para o dev na lista de escolha | o design D6 não cria rota; a Policy nega (`UserPolicyTest.php:48`, `ClientPolicyTest.php:25`) | não se aplica (não há escrita) |
| Edge case do último admin | "a mensagem do template" | "Este é o último administrador ativo. Promova outra pessoa antes de desativar." (`UserServiceTest.php:462`) | `UserServiceTest.php:475-478` |
| Textos que a spec não define | U5, C2, K2, K11 | textos em português, afirmados por valor | - |

---

## Fix Plans

### Fix 1: borda do dígito verificador do CNPJ (S10), Minor. ✅ Feito na T31 (`2a32842`), conferido na rodada 4

- **Root cause**: os CNPJs fixos de `tests/Unit/AlphanumericCnpjTest.php` só têm resto 3 a 10. A factory usa o mesmo `withCheckDigits()` que valida, então nenhum teste prova o ramo "resto < 2 → 0" contra um valor de fora.
- **Fix task**: em `AlphanumericCnpjTest::validos()`, acrescentar um CNPJ verdadeiro com resto 2 em um dos dígitos (ex.: `00000000000191`) e um com resto 0 ou 1 (dígito `0`). Em `invalidos()`, acrescentar o número com os dígitos que o mutante calcularia (`00000000000108`, que o `<= 2` gera a partir de `000000000001`). Só teste, sem código de produção.
- **Done when**: S10 (`AlphanumericCnpj.php:45`, `$remainder < 2` → `$remainder <= 2`) e S10b (`< 1`), reaplicados numa cópia descartável com `vendor` copiado, fazem um caso fixo falhar.

### Fix 2: decisões da spec (Lucas Scholz), pendente

- Os três `destroy` com toast em vez de 403.
- O P2 AC 3 (403 sem rota, por causa do D6).
- O texto do último admin ao se desativar.
- Os textos que a spec não define.
- Se a WCJ pode ter o CNPJ ou o país editado (observação acima).

---

## Requirement Traceability Update

| Requirement | New Status |
| --- | --- |
| NUC-01, NUC-02, NUC-03 (exceto AC 12, desvio), NUC-04, NUC-06, NUC-07 (exceto AC 12, desvio), NUC-08, NUC-09 (exceto AC 9, desvio), NUC-10, NUC-11, NUC-12, NUC-14 | ✅ Verified |
| NUC-05 | ✅ Verified (rodada 4: AC 1 e 2 fechados pela T31) |
| NUC-13 (AC 3) | ⚠️ Decisão (Fix 2) |

---

## Summary

**Overall**: ✅ Ready para as etapas seguintes ao Verifier (rodada 4, focada, autorizada pelo dono). O PASS não encerra a feature: faltam a revisão em camadas, o print do Playwright no PR, as quatro perguntas por entidade e a revisão de segurança dirigida (`.ai/core/especificacao.md` §5 item 9), além das decisões do Lucas abaixo.

**Spec-anchored check**: 58/58 com evidência. 54 com o valor da spec, 3 desvios declarados (U12, C12, K9), 1 spec-precision gap (E3) e 5 leves (U5, C2, K2, K11, último admin), todos pendentes de decisão do Lucas Scholz, não de teste.
**Sensor**: rodada 4, 4/4 mortas por caso fixo (S10, S10b, S12, S13). Rodada 3: 12/13, com o S10 agora fechado. Nenhum sobrevivente em aberto.
**Gate**: rodada 4, `composer lint` ok e `php artisan test` 284 passed, 0 failed (antes da feature: 76). Front e e2e da rodada 3 (typecheck, lint, guard, build, Playwright 48/48) seguem válidos: nada fora do teste unitário mudou.

**What works**: tudo o que a rodada 3 listou (identidade da WCJ, Policies e escopo do gestor, último admin, revogação da IA, sessões, CNPJ único por país, contato em cliente desativado, formato de `changes`) e, agora, a borda do dígito verificador do CNPJ alfanumérico.

**Pendente do Lucas Scholz (não bloqueia o PASS do Verifier)**:

- os três `destroy` (usuário, cliente, contato) com toast em vez de 403;
- o P2 AC 3 (403 para o dev sem rota, por causa do D6);
- a mensagem do último admin ao se desativar;
- os textos que a spec não define (U5, C2, K2, K11);
- se a WCJ pode ter o CNPJ ou o país editado e perder a proteção do N7 (observação em "Code Quality").

**Next steps**: decisões acima com o Lucas; depois as etapas do §5 item 9 e o PR.

---

## Saída dos validadores (rodada 4)

Rodados depois de escrever este relatório:

```
$ python3 .claude/skills/tlc-spec-driven/scripts/validate_state.py b1-nucleo
validate_state: 0 error(s) across [b1-nucleo]          (exit 0)

$ node .ai/skills/conferir-specs.mjs
[b1-nucleo] validate_spec.py: ok
[b1-nucleo] validate_tasks.py: ok
[b1-nucleo] validate_state.py: ok
conferir-specs: 1 feature(s), 0 problema(s)            (exit 0)
```

## Lições (`.specs/lessons.json`), rodada 4

- `L-007` (surviving_mutant, escopo `rules`) recebeu a evidência da rodada 4 pelo `lessons.py add`, com o mesmo texto da rodada 3 e a fonte "S10/S10b mortos por caso fixo (`tests/Unit/AlphanumericCnpjTest.php:21-23,41`), T31 `2a32842`". Saída: `UPDATED L-007 (recurrence=1, status=candidate)`. A recorrência não sobe porque conta features distintas. A lição fica como guia: a T31 é justamente a aplicação dela.
- Nenhuma lição nova: a rodada 4 não teve sobrevivente nem gap novo.
- `prune`: `Pruned 0 stale candidate(s)`. `status`: `lessons: 7 total | confirmed=0 candidate=7 quarantined=0`.

## Histórico de validadores e lições

### Rodada 3

Os dois reprovaram por causa do veredito da rodada 3, como esperado:

```
$ python3 .claude/skills/tlc-spec-driven/scripts/validate_state.py b1-nucleo
  ERROR b1-nucleo: validation.md verdict is FAIL - route the ranked gaps to fix tasks, then re-verify (feature is not done)
validate_state: 1 error(s) across [b1-nucleo]          (exit 1)

$ node .ai/skills/conferir-specs.mjs
[b1-nucleo] validate_spec.py: ok
[b1-nucleo] validate_tasks.py: ok
[b1-nucleo] validate_state.py reprovou
ERROR b1-nucleo: validation.md verdict is FAIL - route the ranked gaps to fix tasks, then re-verify (feature is not done)
validate_state: 1 error(s) across [b1-nucleo]
conferir-specs: 1 feature(s), 1 problema(s)            (exit 1)
```

### Lições da rodada 3

- **Nova**: `L-007` (surviving_mutant, S10, escopo `rules`): "Pin every branch boundary of a check-digit or threshold rule with fixed cases, since a factory built on the same function cannot expose a shifted boundary". Resultado: `ADDED L-007 (status=candidate, recurrence=1)`.
- **Sinal que continua**: `L-004` (P2 AC 3) e `L-005` (os três `destroy`) receberam a evidência da rodada 3 (`UPDATED … recurrence=1, status=candidate`). A recorrência não sobe, porque conta features distintas, e é a mesma feature.
- **Resolvida nesta rodada**: `L-006` (R06, identidade por chave). O `lessons.py` não tem ação de "resolver" (`penalize` é só para lição *confirmed* que falhou quando aplicada). Ela fica `candidate`, como guia, e sai pelo `prune` se não voltar em 45 dias.
- `prune`: `Pruned 0 stale candidate(s)`. `status`: `lessons: 7 total | confirmed=0 candidate=7 quarantined=0`.
