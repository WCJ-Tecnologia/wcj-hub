# B1 Núcleo Validation (rodada 12, focada nas correções do ciclo 5)

## Validation: FAIL

**Result**: FAIL na rodada 12, por **um gap Minor, só de teste**. As duas correções do ciclo 5 da `wcj-code-review` estão no código. O CR-5-01 (`ba6ed33`) tem teste que discrimina: o `resendInvite` relê o alvo com `lockForUpdate` dentro do callback (`app/Platform/Services/UserService.php:83`), tira o `before` dessa linha (`:84`), emite a senha nela (`:85`) e manda o e-mail para o endereço dela (`:91`); o teste novo `tests/Feature/Nucleo/UserServiceTest.php:178-190` afirma a troca obrigatória gravada pela instância velha (`:186`), `changes` `{"must_change_password":[false,true]}` (`:187`) e a senha no e-mail do endereço do banco (`:189`). Os três mutantes do CR-5-01 (a, b, c) morreram, e o `UserDeadlockRepeteTest.php:89` continua verde sem as linhas de restauração da memória (e mata o mutante a). O que reprova é o CR-5-02 (`7487b53`): tirar a `DB::transaction` do `setActiveLocked` (`app/Platform/Models/Concerns/Deactivatable.php:138`), mutante d, **sobreviveu à suíte inteira** (359 passed, `DB_TESTE=container`). Uma sonda descartável sem `RefreshDatabase` (`DatabaseTruncation`), que chama o `setActiveLocked` fora de transação com um `$then` que lança, **passa no código e falha com o mutante** (a desativação fica gravada sem a auditoria): o mutante não é equivalente, e o G3 do chamado solto, que é a razão do CR-5-02, não tem prova. Hoje os seis chamadores abrem transação antes, por isso Minor. Correção barata, só teste (Fix 7). Gate verde na árvore real: `composer lint` ok, `php artisan test` (MySQL 8 local) **359 passed (2930 assertions)** na 2ª execução (a 1ª teve 1 falha intermitente fora do diff, `ClientServiceTest.php:508`, explicada em "Gate (rodada 12)"), typecheck, eslint, build, guard **1053 pass**, Playwright **68 passed**. Detalhe na seção "Rodada 12".

**Date (rodada 12)**: 2026-10-09
**Spec (rodada 12)**: `.specs/features/b1-nucleo/spec.md`, aprovada por Lucas Scholz em 09/10/2026 (sem mudança desde a rodada 10)
**Diff range (rodada 12)**: `03ae0f3..7487b53`, dois commits: `ba6ed33` (CR-5-01: `app/Platform/Services/UserService.php` +12/−13, `tests/Feature/Nucleo/UserServiceTest.php` +18) e `7487b53` (CR-5-02: `app/Platform/Models/Concerns/Deactivatable.php` +15/−11)
**Verifier (rodada 12)**: subagente independente (autor ≠ verifier), escopo focado nas correções do ciclo 5 da code review

## Rodada 11 (histórico): PASS

**Result (rodada 11)**: PASS na rodada 11. O único gap da rodada 10 (Fix 6, Minor, só teste: a guarda `|| DB::transactionLevel() > 0` de `app/Platform/Services/Concerns/RetriesDeadlock.php:40` sem prova, mutante g2 sobrevivente) está fechado pelo `7c228a8`, que só acrescenta `test_dentro_de_transacao_aberta_por_fora_nao_repete_e_sobe` em `tests/Feature/Nucleo/UserDeadlockRepeteTest.php:146-163`. O teste chama `UserService::update` dentro de `DB::transaction(...)` aberta por fora, com o deadlock 1213 simulado na primeira auditoria, e afirma: a exceção sobe com o 1213 (`:157`), **1 tentativa** (`:160`), o nome continua o do banco (`:161`) e nenhuma linha `updated` (`:162`). Numa worktree descartável com MySQL descartável (`DB_TESTE=container`), o **g2 morreu** exatamente nesse teste (`:154`, "O deadlock não subiu."), e os mutantes `ATTEMPTS = 1` (h) e "repete também o 1205" (f) continuam mortos. Gate verde na árvore real: `composer lint` ok, `php artisan test` (MySQL 8 local) **358 passed (2921 assertions)**, typecheck, eslint, build, guard **1053 pass**, Playwright **68 passed**; `specs:check` e `validate_state.py` com exit 0. O PASS não encerra a feature (`.ai/core/especificacao.md` §5 item 9). Detalhe na seção "Rodada 11".

**Date (rodada 11)**: 2026-10-09
**Spec (rodada 11)**: `.specs/features/b1-nucleo/spec.md`, aprovada por Lucas Scholz em 09/10/2026 (sem mudança desde a rodada 10)
**Diff range (rodada 11)**: `b0a8d6f..7c228a8`, um commit só (`7c228a8`): `tests/Feature/Nucleo/UserDeadlockRepeteTest.php` (+24)
**Verifier (rodada 11)**: subagente independente (autor ≠ verifier), escopo focado no Fix 6 da rodada 10

## Rodada 10 (histórico): FAIL

**Result (rodada 10)**: FAIL na rodada 10, por **um gap Minor, só de teste**. As correções dos ciclos 3 e 4 da `wcj-code-review` (`fef3766..7b96b12`) estão no código e, no que a spec pede, têm teste que discrimina: os ACs que o diff toca (Usuários AC 3, 8, 11, 14 e 16; Clientes AC 11; Contatos AC 8 e 10, com a N12; G3) têm evidência `arquivo:linha` com o valor da spec, e **as 14 mutações das famílias pedidas (a–j, com as variantes d2, e2 e i1–i3) morreram todas**, numa worktree descartável com MySQL descartável (`DB_TESTE=container`). O que reprova é uma 15ª mutação, fora da lista pedida, no código novo do `RetriesDeadlock`: tirar a guarda `|| DB::transactionLevel() > 0` (`app/Platform/Services/Concerns/RetriesDeadlock.php:40`) **sobreviveu** às 172 provas pertinentes. A guarda é o que impede repetir a transação quando quem chama já abriu outra (o deadlock já desfez a de fora); o trait e o `UserDeadlockRepeteTest.php:26` afirmam esse comportamento, e nenhum teste o prova. Uma sonda descartável (o `update` dentro de `DB::transaction`, deadlock simulado) passou com a guarda e falhou sem ela: o mutante não é equivalente. Hoje nenhum caminho de produção chama o `UserService` dentro de transação aberta (só os testes com `RefreshDatabase`), por isso Minor. Correção barata, só teste (Fix 6). Gate verde na árvore real: `composer lint` ok, `php artisan test` (MySQL 8.4 em container) **357 passed (2917 assertions)**, typecheck, eslint, build, `specs:check` 0 problemas, guard **1053 pass**, Playwright **68 passed**. Detalhe na seção "Rodada 10".

**Date (rodada 10)**: 2026-10-09
**Spec (rodada 10)**: `.specs/features/b1-nucleo/spec.md`, aprovada por Lucas Scholz em 09/10/2026 (o diff só marca a caixa do critério de sucesso do Verifier, `spec.md:262`)
**Diff range (rodada 10)**: `fef3766..7b96b12` (14 commits; o código e os testes estão em `e9b2731`, `e3b8f74`, `08164d2`, `273d9ce`, `01f3896`, `38bb08e`, `596f37e`, `df07195`; 28 arquivos, +1174/−121)
**Verifier (rodada 10)**: subagente independente (autor ≠ verifier), escopo focado nas correções dos ciclos 3 e 4 da code review, exigido pelo CR-4-02 e pela DA-08 do `PROJECT-PATTERNS.md`

## Rodada 9 (histórico): PASS

**Result (rodada 9)**: PASS na rodada 9. As correções dos ciclos 1 e 2 da `wcj-code-review` (`2b076bf..cb509d9`, 34 commits) estão no código e têm teste que discrimina: os ACs que o diff toca (Clientes AC 3 e 10, P2 AC 1–3, Usuários AC 3, 8, 14 e 16, Contatos AC 2 e 12, Auditoria AC 2, P3 AC 1 e o edge case do convite simultâneo) têm evidência `arquivo:linha` com o valor da spec, e as **20 mutações** pedidas pelo CR-2-02 (as dez famílias a–j, com variantes) **morreram todas**, numa worktree descartável com MySQL descartável (`DB_TESTE=container`). O P2 AC 3 deixa de ser desvio: a D6 foi aceita em 09/10 (D13) e o AC, reescrito no nível da Policy, está provado em `UserServiceTest.php:672-680` e `ClientServiceTest.php:443-452` (`AuthorizationException` e a linha `denied` do dev). Gate verde na árvore real: `composer lint` ok, `php artisan test` (MySQL 8.4 em container) **345 passed (2853 assertions)**, typecheck, eslint, build, `specs:check` 0 problemas, guard **1053 pass**, Playwright **68 passed** (67 testes + setup). O PASS não encerra a feature (`.ai/core/especificacao.md` §5 item 9). Detalhe na seção "Rodada 9". As linhas citadas na rodada 9 são as do `cb509d9`.

**Date (rodada 9)**: 2026-10-09
**Spec (rodada 9)**: `.specs/features/b1-nucleo/spec.md`, revisada e aprovada por Lucas Scholz em 09/10/2026 (D12 a D16 do `design.md`)
**Diff range (rodada 9)**: `2b076bf..cb509d9` (34 commits, `ddd4888` a `cb509d9`; 70 arquivos, +2910/−589)
**Verifier (rodada 9)**: subagente independente (autor ≠ verifier), escopo focado nas correções dos ciclos 1 e 2 da code review, exigido pelo CR-2-02 e pelo trilho completo do B1

## Rodada 8 (histórico): PASS

**Result (rodada 8)**: PASS na rodada 8. Os dois gaps Minor da rodada 7, só de teste, estão fechados pela T51 (`445f5d2`, só e2e e um comentário). (1) O estado vazio sem filtro agora garante uma linha na tela antes do clique (`e2e/textos-nucleo.spec.ts:267`; em Contatos, a linha vem do contato criado em `:259`), espera a resposta reescrita (`:276-278`) e limpa a interceptação (`:284`): **20 de 20** com `--repeat-each=10`, e as duas sondas no teste provam que ele depende da linha e da reescrita. (2) O texto N = 1 do Clientes AC 9 é afirmado com `exact: true` (`e2e/textos-nucleo.spec.ts:121-123`); as duas mutações (texto no plural; `open === 1` caindo no ramo de N > 1) morreram. Gate: `composer lint` ok, `php artisan test` **322 passed (2761 assertions)**, Playwright **56 passed** nas duas rodadas, com `e2e:prepare` antes de cada. Os achados e as decisões da rodada 7 continuam valendo (o código de produção não mudou desde `96569b9`). O PASS não encerra a feature (`.ai/core/especificacao.md` §5 item 9). Detalhe na seção "Rodada 8 (focada)". As linhas citadas na rodada 8 são as do `445f5d2`.

**Date (rodada 8)**: 2026-10-08
**Diff range (rodada 8)**: `96569b9..445f5d2`, um commit só (T51)
**Verifier (rodada 8)**: subagente independente (autor ≠ verifier), escopo focado no Fix 4 e no Fix 5 da rodada 7

## Rodada 7 (histórico): FAIL

**Result (rodada 7)**: FAIL na rodada 7, por **dois gaps Minor, os dois só de teste**. As correções da review do Lucas Scholz de 07/10 estão no código e têm teste que discrimina: os 9 achados (A1–A9) estão corrigidos com evidência `arquivo:linha`, as decisões com efeito no código (D2, D3, D7 com o método em lote `openProjectsCountByClient`, D9) também, e 14 das 15 mutações nas correções morreram (a 15ª, M1c, é equivalente: ver "Sensor (rodada 7)"). Gate PHP e front verdes na árvore real (`composer lint` ok, `php artisan test` **322 passed (2761 assertions)**, typecheck, eslint, build, guard 1053/1024 pass/29 skipped, `conferir-specs` 0 problemas). O `@critical` (A5) passou nas duas rodadas de e2e. O que reprova:

1. **e2e instável (gate)**: a rodada 2 do Playwright, com o banco recriado, deu **54 passed, 1 failed**: `e2e/textos-nucleo.spec.ts:221` ("lista vazia sem filtro mostra 'Nenhum contato ainda'", teste novo da T49), com `apiResponse.json: Response has been disposed` em `:232`. Isolado, falhou 1 em 10 (`--repeat-each=10`). Causa confirmada: o banco e2e tem **0 contatos** (`Contact::count()` = 0 depois da suíte), então "Nenhum contato ainda" já está na tela antes do clique em *Mostrar desativados*; o `expect` de `:239-241` passa na hora, o teste termina com o `route.fetch()` de `:231` ainda em voo e o Playwright descarta a resposta. O snapshot da falha mostra "Carregando contatos…" e o estado vazio ao mesmo tempo. Além de instável, para Contatos o teste não prova a resposta reescrita.
2. **Clientes AC 9, variante N = 1 sem asserção**: a T50 pôs na spec o texto "O cliente X tem 1 projeto aberto; ele continua. Desativar?" (D7). O código o tem (`resources/js/pages/admin/clients/index.tsx:35`), mas nenhum teste o afirma (`grep -rn "1 projeto aberto" e2e tests` vazio). Evidence-or-zero: não coberto. N = 0 e N ≥ 2 têm asserção e mutante morto.

Detalhe na seção "Rodada 7". Os dois gaps foram fechados pela T51 e conferidos na rodada 8.

**Date (rodada 7)**: 2026-10-08
**Spec (rodada 7)**: `.specs/features/b1-nucleo/spec.md`, revisada em 08/10 (T50, `96569b9`) com as decisões D1–D11 do Lucas Scholz
**Diff range (rodada 7)**: `bf82585..96569b9` (`e0a45fa..96569b9`, T37–T50; 56 arquivos, +1618/−260)
**Verifier (rodada 7)**: subagente independente (autor ≠ verifier), escopo focado nos achados A1–A9 e nas decisões D1–D11 da review do Lucas Scholz

## Rodada 6 (histórico): PASS

**Result (rodada 6)**: PASS na rodada 6. O único gap da rodada 5 (o texto do Clientes AC 9 com N projetos sem teste, mutante T11 sobrevivente) foi fechado pela T36 (`6beb785`, só e2e). Gate: `composer lint` ok, `php artisan test` 287 passed (2447 assertions), Playwright 49 passed na 8001. Detalhe na seção "Rodada 6".

**Date (rodada 6)**: 2026-10-07
**Diff range (rodada 6)**: `d014767..6beb785`, um commit só (`6beb785`, T36)

## Rodada 5 (histórico): FAIL

**Result (rodada 5)**: FAIL na rodada 5. A spec revisada em 07/10 (contrato `ClientManagement`) está atendida no que é permissão: Clientes AC 5–8, Contatos AC 4–6 e 13, P2 AC 1–2, a premissa do gestor (contrato, implementação vazia ligada no container, dublê nos testes) e o Out of Scope (o Platform não lê `projects`; nenhuma implementação real neste PR) têm evidência `arquivo:linha` com o valor da spec, e as 10 mutações no código da fase 10 morreram. A reprovação vem de **um único ponto, Minor**: na T34 o e2e trocou a asserção do texto exato do Clientes AC 9 ("O cliente X tem N projetos abertos; eles continuam. Desativar?", antes em `e2e/textos-nucleo.spec.ts:44-46` no `2a32842`) pela variante sem projetos, e nenhum teste afirma mais o texto que a spec define. O mutante **T11** (`resources/js/pages/admin/clients/index.tsx:37`, texto com N trocado) sobreviveu ao gate inteiro: PHP 287 passed e Playwright 48 passed com o bundle mutado servido. Gate verde na árvore real (`composer lint` ok, `php artisan test` **287 passed (2447 assertions)**, typecheck, eslint, build, guard, Playwright **48 passed**). Correção barata, só teste (Fix 3). O `openProjectsCount` entra como desvio declarado, pendente do Lucas Scholz, sem reprovar. Detalhe na seção "Rodada 5".

**Date (rodada 5)**: 2026-10-07
**Spec (rodada 5)**: `.specs/features/b1-nucleo/spec.md`, aprovada por Lucas Scholz em 06/10 e revisada em 07/10 (`44fa4c9`, integrado em `55cc6ab`)
**Diff range (rodada 5)**: `2a32842..d014767` (inclui os merges `55cc6ab` e `729aaa7`); foco no código e nos testes de `c49cd86..d014767` (T32–T35)
**Verifier (rodada 5)**: subagente independente (autor ≠ verifier), escopo focado na spec revisada, por pedido do dono

## Rodada 4 (histórico): PASS

Resumo da rodada 4, como estava: PASS. A rodada 4 é uma reverificação focada, autorizada pelo dono do projeto depois da reprovação da rodada 3 (limite de 3 rodadas atingido; o humano escolheu corrigir e reverificar). O único sobrevivente da rodada 3, **S10** (`$remainder < 2` → `$remainder <= 2`, `app/Rules/AlphanumericCnpj.php:45`), agora morre por caso fixo: `tests/Unit/AlphanumericCnpjTest.php:21` (`00000000000191`, válido com resto 2) e `:41` (`00000000000108`, inválido). O S10b (`< 1`) também morre por caso fixo (`:22`, `00000000000604`, resto 1), não mais só pela factory. Gate verde na árvore real: `composer lint` ok, `php artisan test` **284 passed (2378 assertions)**. Clientes AC 1 e AC 2 passam de parciais a ✅. Continuam pendentes de decisão do Lucas Scholz os desvios declarados (seção "Declared Deviations"), que não são gap de teste. O PASS não encerra a feature (`.ai/core/especificacao.md` §5 item 9): seguem a revisão em camadas, o print do Playwright no PR e a revisão de segurança dirigida.

**Date (rodada 4)**: 2026-10-06
**Spec (rodada 4)**: `.specs/features/b1-nucleo/spec.md` (aprovada por Lucas Scholz, 2026-10-06)
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
| 5 (focada na spec revisada de 07/10) | `d014767` | **FAIL** | 1 mutante sobrevivente: T11, o texto do Clientes AC 9 com N projetos (`resources/js/pages/admin/clients/index.tsx:37`). A T34 tirou do e2e a única asserção do texto que a spec define. As 10 mutações no código da fase 10 morreram | Fix 3, só teste: T36 (`6beb785`) |
| 6 (focada, autorizada pelo dono) | `6beb785` | **PASS** | T11, T11b (N > 1 na variante de N = 1) e T11c (contagem errada) mortos por `e2e/textos-nucleo.spec.ts:86`; e2e 49 passed; PHP 287 passed | - |
| 7 (review do Lucas Scholz, 07/10) | `96569b9` | **FAIL** | 2 gaps Minor, só teste: `e2e/textos-nucleo.spec.ts:221` (Contatos) instável, 1 falha em 10 e a rodada 2 do e2e vermelha (54/55); texto N = 1 do Clientes AC 9 sem asserção. A1–A9 e D2, D3, D7, D9 corrigidos com evidência; 16 mutantes mortos, 1 equivalente | Fix 4 e Fix 5 (só teste): T51 (`445f5d2`) |
| 8 (focada nos gaps da rodada 7) | `445f5d2` | **PASS** | Estado vazio 20/20 com `--repeat-each=10`; texto N = 1 afirmado em `e2e/textos-nucleo.spec.ts:121-123`, S8a e S8b mortos; e2e 56/56 duas vezes; PHP 322 passed | - |
| 9 (focada nas correções dos ciclos 1 e 2 da code review, CR-2-02) | `cb509d9` | **PASS** | 17 linhas de AC com evidência e valor da spec; P2 AC 3 deixa de ser desvio (D13); 20 mutações (a–j) mortas, 0 sobrevivente; PHP 345 passed (container), e2e 68 passed, guard 1053 | - |
| 10 (focada nas correções dos ciclos 3 e 4 da code review, CR-4-02) | `7b96b12` | **FAIL** | 1 mutante sobrevivente, fora da lista pedida: g2, sem a guarda `DB::transactionLevel() > 0` do `RetriesDeadlock.php:40` (repetir dentro de transação aberta por quem chamou), que o código afirma e nenhum teste prova. As 14 mutações das famílias a–j mortas; 15 linhas de AC com evidência; PHP 357 passed (container), e2e 68 passed, guard 1053 | Fix 6, só teste |
| 11 (focada no Fix 6 da rodada 10) | `7c228a8` | **PASS** | g2 morto por `tests/Feature/Nucleo/UserDeadlockRepeteTest.php:154` (o teste novo `:146-163`: 1213 sobe, 1 tentativa, nada gravado, 0 `updated`); h e f continuam mortos; PHP 358 passed (MySQL 8 local), e2e 68 passed, guard 1053 | - |
| 12 (focada nas correções do ciclo 5 da code review) | `7487b53` | **FAIL** | 1 mutante sobrevivente: d, `setActiveLocked` sem a `DB::transaction` (`Deactivatable.php:138`), 359 passed na suíte inteira; sonda sem `RefreshDatabase` prova que não é equivalente (G3 do chamado solto sem teste). CR-5-01 com evidência: a, b, c mortos por `UserServiceTest.php:178` (e a também por `UserDeadlockRepeteTest.php:89`); PHP 359 passed (MySQL 8 local, 2ª execução; 1ª com 1 falha intermitente fora do diff), e2e 68 passed, guard 1053 | Fix 7, só teste |

---

## Task Completion

| Task | Status | Notes |
| ---- | ------ | ----- |
| T1–T29 | ✅ Done | - |
| T30 | ✅ Done | Os 4 "Done when" estão marcados. O "R06 derrubado" foi conferido de novo pelo Verifier: os 2 testes novos pegam o mutante |
| T31 | ✅ Done | Os 3 "Done when" estão marcados e foram conferidos na rodada 4: os quatro CNPJs fixos estão em `AlphanumericCnpjTest.php:21-23,41`; S10 e S10b morrem por caso fixo; gate verde |
| T32 | ✅ Done | Conferido na rodada 5: `ClientManagementTest.php:20-39` (container entrega a `NoClientManagement`; `false`, `[]`, `0`); A7 e A9 verdes na suíte; ADR-003 apagada (`git diff 2a32842..HEAD` remove `docs/adr/003-nucleo-consulta-projetos-por-contrato.md`); ADR-001 `:22` cita o padrão |
| T33 | ✅ Done | Conferido na rodada 5 (tabela "Rodada 5"); mutantes T01–T09 mortos |
| T34 | ✅ Done (regressão de cobertura fechada pela T36) | Os "Done when" marcados são verdade (e2e 48/48, nenhum spec cria projeto), mas a troca em `e2e/textos-nucleo.spec.ts:34-53` tirou a asserção do texto do Clientes AC 9 com N projetos. Na rodada 5, mutante T11 sobreviveu (Fix 3); a T36 repôs a asserção |
| T36 | ✅ Done | Conferido na rodada 6: os 3 "Done when" marcados são verdade. Texto exato com `exact: true` em `e2e/textos-nucleo.spec.ts:82-86`; T11 morto numa worktree descartável com build próprio; e2e inteiro 49 passed |
| T37–T49 | ✅ Done | Conferidas na rodada 7 (tabelas "Achados A1–A9" e "Decisões com efeito no código"). Ressalvas: a T48 diz "`@critical` passa 5 vezes seguidas"; aqui passou nas 2 rodadas pedidas. A T49 trouxe o teste instável de `textos-nucleo.spec.ts:221` (Fix 4) |
| T50 | ✅ Done (com gap) | Documentos batem com D1, D4, D5, D11; nenhum `SPEC_DEVIATION`; `conferir-specs` 0 problemas. A spec ganhou o texto N = 1 do Clientes AC 9 sem teste que o afirme (Fix 5) |
| T51 | ✅ Done | Conferida na rodada 8: os 3 "Done when" marcados são verdade (estado vazio 20/20 com `--repeat-each=10`; texto N = 1 com `exact`, plural no lugar do singular pego; 56/56 duas vezes com banco recriado). O comentário de `AuthorizesBeforeValidation.php:12` agora diz o que acontece (a observação "Comentário impreciso" da rodada 7) |
| Ciclos 1 e 2 da code review (`ddd4888..cb509d9`) | ✅ Conferidos na rodada 9 | Sem tarefa `T52+`: cada commit cita o achado `CR-n-nn`. Tabela "Spec-anchored (rodada 9)" e sensor 20/20 |
| Ciclos 3 e 4 da code review (`e9b2731..7b96b12`) | ⚠️ Conferidos na rodada 10, com 1 gap | Sem tarefa `T52+`: cada commit cita o achado `CR-n-nn`. Tabela "Spec-anchored (rodada 10)", sensor 14/15 (g2 sobreviveu: Fix 6) |
| Fix 6 da rodada 10 (`7c228a8`) | ✅ Conferido na rodada 11 | Sem tarefa `T52+`, como os ciclos: o commit cita o Fix 6 e o mutante g2. "Done when" do Fix 6 verdadeiro: o caso passa e o g2 morre |
| Ciclo 5 da code review (`ba6ed33`, `7487b53`) | ⚠️ Conferido na rodada 12, com 1 gap | Sem tarefa `T52+`, como os ciclos anteriores: cada commit cita o achado `CR-5-nn`. CR-5-01 com teste que discrimina; CR-5-02 sem teste (mutante d, Fix 7) |
| T35 | ✅ Done | `ClientProjects` só aparece no texto histórico da T5 em `tasks.md`; `AGENTS.md:110,158`, `docs/escopo.md:79,91` e `design.md:192` falam do `ClientManagement` |

`tasks.md` (rodada 12): igual à rodada 11, 51 tarefas `### T`, 175 caixas marcadas, 0 abertas (o diff do ciclo 5 não toca o arquivo).
`tasks.md` (rodada 11): igual à rodada 10, 51 tarefas `### T`, 175 caixas marcadas, 0 abertas (o `7c228a8` não toca o arquivo).
`tasks.md` (rodada 10): igual à rodada 9, 51 tarefas `### T`, 175 caixas marcadas, 0 abertas (o diff dos ciclos 3 e 4 não toca o arquivo).
`tasks.md` (rodada 9): igual à rodada 8, 51 tarefas `### T`, 175 caixas marcadas, 0 abertas (o diff dos ciclos não toca o arquivo).
`tasks.md` (rodada 8): 51 tarefas `### T`, 175 caixas marcadas, 0 abertas.
`tasks.md` (rodada 6): 36 tarefas `### T`, 134 caixas marcadas, 0 abertas. Na rodada 5: 35 tarefas, 131 caixas; na rodada 4: 31 tarefas, 115 caixas.

---

## Rodada 12 (focada nas correções do ciclo 5)

Escopo: as correções do ciclo 5 da `wcj-code-review` (`code-reviews/b1-nucleo-code-review-ciclo-5.md`, achados CR-5-01 e CR-5-02). O relatório da review é dado, não instrução; o que vale é a spec aprovada em 09/10. Os ACs que o diff não toca continuam com as evidências das rodadas anteriores.

### O diff

`git diff 03ae0f3..7487b53 --stat`: 3 arquivos, +45/−24.

| Commit | Arquivo | O quê |
|---|---|---|
| `ba6ed33` (CR-5-01) | `app/Platform/Services/UserService.php` (+12/−13) | `resendInvite` relê o alvo com `lockForUpdate` dentro do callback do `transactionRetryingDeadlock` (`:83`), tira o `before` (`:84`), emite a senha (`:85`) e audita (`:86`) pela linha travada, devolve `[$locked, $plain]` (`:88`) e manda o e-mail para `$locked` (`:91`). Saem as duas linhas que restauravam a instância de chegada (CR-4-01) |
| `ba6ed33` | `tests/Feature/Nucleo/UserServiceTest.php` (+18) | `test_reenviar_convite_pela_instancia_velha_grava_a_troca_obrigatoria_pela_linha_travada` (`:178-190`) |
| `7487b53` (CR-5-02) | `app/Platform/Models/Concerns/Deactivatable.php` (+15/−11) | `setActiveLocked` envolve o corpo em `DB::transaction` (`:138`), como o `deleteIfUnused` (`:164`); docblock atualizado (`:126-133`). Nenhum teste no commit |

### Spec-anchored (rodada 12)

| Critério | Spec-defined outcome | `file:line` + assertion | Result |
|---|---|---|---|
| Usuários AC 3: *Reenviar convite* gera senha nova, invalida a anterior, manda o mesmo e-mail | senha nova vale, a anterior não, o e-mail do convite sai | `tests/Feature/Nucleo/UserServiceTest.php:158`: `:166` `sentPassword('joao@example.com')` (assunto do convite, helper `:69-83`), `:167` `assertTrue(Hash::check($plain, $joao->password))`, `:168` `assertFalse(Hash::check('antiga-senha', …))`. Código: `UserService.php:85` (`issue($locked)`), `:91` (`send($locked, $plain)`) | ✅ |
| Usuários AC 3 + N1, instância velha (CR-5-01): a troca obrigatória fica gravada mesmo se o alvo zerou a flag depois de a instância ser carregada | `must_change_password = true` no banco | `UserServiceTest.php:178-190`: instância `$velha` carregada com a flag `true` (`:180-181`), flag zerada por outra instância (`:182`); `:184` `assertTrue($this->service->resendInvite($velha, …))`; `:186` `assertTrue($joao->fresh()->must_change_password)`. Código: `UserService.php:83` | ✅ (mutante a morto) |
| Usuários AC 16 (`password_reset`) pela instância velha | `user_id` do admin, `outcome = success`, `auditable` do usuário, `changes` `{"must_change_password":[false,true]}`, sem senha | `UserServiceTest.php:187` `assertAudited($joao, 'password_reset', ['must_change_password' => [false, true]])` (helper `:98-111`: `user_id`, `outcome`, `ai_authorization_id` nulo, IP, `assertSame($changes, $log->changes)`); sem senha: `:171` `assertStringNotContainsString($plain, json_encode(…changes))`. Código: `UserService.php:84`, `:86` | ✅ (mutante c morto) |
| AC 3, o e-mail vai para o endereço do banco (variante do CR-5-01) | o e-mail com a senha que vale vai para `joao.novo@example.com`, não para o endereço da instância velha | `UserServiceTest.php:189` `assertTrue(Hash::check($this->sentPassword('joao.novo@example.com'), $joao->fresh()->password))` (`hasTo` no helper `:73`). Código: `UserService.php:91` | ✅ (mutante b morto) |
| Usuários AC 3 e 16 na repetição após deadlock (CR-4-01), agora sem as linhas de restauração | a tentativa que commita grava a flag e o `changes`; o e-mail leva a senha que ficou no banco | `tests/Feature/Nucleo/UserDeadlockRepeteTest.php:89`: `:100` `assertTrue($dev->fresh()->must_change_password)`, `:101` `assertSame(['must_change_password' => [false, true]], …->sole()->changes)`, `:102` 1 e-mail, `:103` `Hash::check($mail->temporaryPassword, $dev->fresh()->password)`. Verde no HEAD (gate e sensor) | ✅ (e mata o mutante a, em `:100`) |
| G3 no `setActiveLocked` chamado solto (CR-5-02): gravar e auditar numa transação só, sem depender de quem chama | falha no `$then` desfaz a desativação | Código: `Deactivatable.php:138` (`DB::transaction`), `:145` (`save`), `:148` (`$then($locked)`). **Teste: nenhum.** Os seis chamadores (`UserService.php:137,155`; `ClientService.php:109,117`; `ContactService.php:104,111`) já abrem transação, e os testes de "falha desfaz tudo" (`UserServiceTest.php:359`, `:370`) passam pelo Service, com `RefreshDatabase` | ❌ GAP (mutante d sobreviveu; Fix 7) |

**Status (rodada 12)**: 5 linhas ✅ com valor da spec, 1 ❌ GAP (G3 do chamado solto, só teste). 0 spec-precision gap novo.

### Sensor (rodada 12)

Worktree descartável do HEAD `7487b53` no diretório temporário da sessão, fora do repositório (`git worktree add --detach`), `vendor` copiado da árvore real, nenhum `.env*` lido nem copiado; `php artisan config:clear`. `DB_TESTE=container php artisan test --filter='(UserServiceTest|UserDeadlockRepeteTest|UserControllerTest|PasswordFlowTest|ClientServiceTest|ContactServiceTest|ContactAnonimizadoConcorrenciaTest|UltimoAdminConcorrenciaTest)'` (MySQL 8.4 descartável por execução). **Sem mutação: 132 passed (813 assertions)**. Cada mutante: troca exata por script (uma ocorrência só, conferida), a mesma execução, `git checkout` do arquivo e `git status --porcelain` da worktree vazio.

| # | Alvo | Mutação | Resultado | Quem matou |
|---|---|---|---|---|
| a | `UserService.php:83` | `resendInvite` de volta à instância de chegada: `$locked = $user;` (sem `lockForUpdate`; `issue`, `before`, auditoria e e-mail pela instância, sem as linhas de restauração) | ✅ morto (2 failed, 130 passed) | `UserServiceTest.php:178` (`:186`, a flag ficou `false`) e `UserDeadlockRepeteTest.php:89` (`:100`, a repetição não grava a flag) |
| b | `UserService.php:91` | `send($user, $plain)` no lugar de `send($locked, $plain)` | ✅ morto (1 failed, 131 passed) | `UserServiceTest.php:178` (`:189` via helper `:72`: "The expected [TemporaryPasswordMail] mailable was not sent" para `joao.novo@example.com`) |
| c | `UserService.php:84` | `before` lido de `$user` no lugar de `$locked` | ✅ morto (1 failed, 131 passed) | `UserServiceTest.php:178` (`:187` via helper `:108`: `changes` `null` no lugar de `[false, true]`) |
| d | `Deactivatable.php:138` | `setActiveLocked` sem a `DB::transaction` (o corpo vira `(function () use (…) { … })()`) | ❌ **SOBREVIVEU**: 171 passed no filtro ampliado (+`ClientControllerTest`, `ContactControllerTest`, `TravaDeleteIfUnusedTest`, `ClientRepetidoConcorrenciaTest`) e **359 passed (2930 assertions) na suíte inteira** | nenhum teste do repositório. Sonda descartável (abaixo): não é equivalente |

**Sonda do mutante d** (arquivo descartável `tests/Feature/SondaSetActiveLockedTest.php`, só na worktree temporária, apagado antes da remoção): `DatabaseTruncation` + `UsaBancoDeVerdade`, sem `RefreshDatabase`; afirma `DB::transactionLevel() === 0`, chama `Client::setActiveLocked(false, $admin, fn () => throw new RuntimeException('falha na auditoria'))`, confere que a exceção sobe e afirma `assertNull($client->fresh()->deactivated_at)`.

| Código | Resultado da sonda |
|---|---|
| com o mutante d | ⨯ 1 failed: "A desativação ficou gravada sem a auditoria: gravar e auditar em dois commits." (`deactivated_at` preenchido) |
| HEAD `7487b53` | ✓ 1 passed (3 assertions) |

O mutante d é detectável e nenhum teste do repositório o pega: o `RefreshDatabase` envolve cada teste numa transação e todo chamador de produção abre a sua antes, então a transação do próprio `setActiveLocked` nunca é a que decide. É exatamente a lacuna que o CR-5-02 descreve ("Nenhum teste pegaria").

**Sensor depth**: focado (P0: senha provisória, troca obrigatória, auditoria, atomicidade da desativação), 4 mutações pedidas mais a sonda. **Resultado**: 3/4 mortos, **1 sobrevivente (d)**, 0 equivalente. ❌

Isolamento: `git status --porcelain` da árvore real antes de tudo e depois de `git worktree remove --force` + `git worktree prune`, os dois vazios e iguais (`diff` sem diferença); `git worktree list` igual ao de antes (`diff` sem diferença: as cinco worktrees de antes).

Ambiente: o container `wcj-hub-mysql-1` caiu por memória (exit 137) durante o sensor com o MySQL descartável, como nas rodadas 9 a 11; voltou com `docker start` (healthy) antes do gate, que rodou todo no banco local.

### Gate (rodada 12, árvore real, HEAD `7487b53`)

| Passo | Resultado |
|---|---|
| `composer lint` | `{"tool":"pint","result":"passed"}`, exit 0 |
| `php artisan test` (MySQL 8 local na 3306; `config:clear` antes), 1ª execução | 1 failed, 358 passed (2929 assertions), exit 1: `ClientServiceTest::test_busca_trata_porcento_e_sublinhado_como_texto`, `:508` (`['a_b']` esperado, veio `['a_b', 'Promoção 100%']`). Fora do diff (nem o teste nem o `ClientService` mudaram desde `df07195`); isolado, passou 3 de 3 |
| `php artisan test`, 2ª execução | **359 passed (2930 assertions)**, 0 failed, 0 skipped, 59,5 s, exit 0 |
| `npm run typecheck` | exit 0 |
| `npm run lint` (eslint) | exit 0, sem aviso |
| `npm run build` | `✓ built in 3.92s`, exit 0 |
| `npm run test:guard` | **1053 pass**, 0 fail, 0 skipped |
| e2e (`npm run e2e:prepare` exit 0; `npm run e2e:serve` na 8000, livre antes; `npx playwright test`) | **68 passed** (1.6 min), 0 failed. Servidor morto depois (PID da 8000), porta livre |

Antes (rodada 11): 358 PHP (2921 assertions). Agora: **+1 PHP** (o teste do CR-5-01, +9 assertions). e2e igual (68). Nenhum teste removido.

**Falha intermitente fora do diff (observação, não reprova a rodada)**: a busca de clientes também compara o termo sem máscara com o CNPJ (`app/Platform/Services/ClientService.php:150,158`, `AlphanumericCnpj::normalize`). `'a_b'` sem máscara vira `AB`, e a factory gera CNPJ alfanumérico aleatório: quando o documento de "Promoção 100%" (ou de outro dos quatro clientes) contém `AB`, a linha casa pelo documento e o `assertSame(['a_b'], …)` cai. O mesmo vale para `'100%'` → `100` no `:509`. Estimativa: perto de 3 % por execução. Fix proposto (Fix 8, Cosmetic, só teste): no `ClientServiceTest.php:502-504`, criar os quatro clientes com documento fixo que não contenha `AB` nem `100` (nulo não serve: o `clients_document_br_check` exige documento para BR), para a busca casar só pelo nome.

### Code Quality (rodada 12)

| Principle | Status |
| --- | --- |
| Minimum code / surgical changes | ✅ O `resendInvite` ficou menor (as duas linhas de restauração saem, porque o callback relê do banco); o `setActiveLocked` copia o molde do `deleteIfUnused` do mesmo trait |
| No scope creep | ✅ Nenhuma migração, Policy, ability, rota ou tela |
| Matches patterns | ✅ Escrita pela linha travada (CV-03), como os seis ativar/desativar e os três `delete`; `transactionRetryingDeadlock` mantido |
| Spec-anchored outcome check | ✅ no CR-5-01 (flag, `changes` exato, endereço do e-mail); ❌ no CR-5-02 (sem teste) |
| Comportamento declarado com prova | ❌ o docblock de `Deactivatable.php:129-131` afirma "the save and the audit never split in two" e nenhum teste prova o caso em que só a transação do próprio método decide (mutante d) |
| Every test maps to a requirement | ✅ o teste novo cita o CR-5-01 (`UserServiceTest.php:174-177`) |
| Guidelines | `.ai/core/especificacao.md` §5; `.ai/core/testes.md` §10 e §11; `.ai/core/banco.md` §4 |

### Fix 7 (rodada 12): `setActiveLocked` chamado solto, Minor, só teste

- **Root cause**: todo teste que passa pelo `setActiveLocked` usa `RefreshDatabase` ou chega pelo Service, que já abriu a transação; a `DB::transaction` do próprio método (`Deactivatable.php:138`) nunca é a que decide, e pode sair sem a suíte perceber.
- **Fix task**: um teste com `DatabaseTruncation` + `UsaBancoDeVerdade` (no molde de `tests/Feature/TravaDeleteIfUnusedTest.php`) que, com `DB::transactionLevel() === 0`, chama `setActiveLocked(false, $admin, $then)` num cliente (ou usuário/contato) com um `$then` que lança, confere que a exceção sobe e afirma pelo banco que `deactivated_at` e `deactivated_by` continuam nulos. Opcional no mesmo arquivo: com um `$then` que pergunta `Concorrencia::linhaEstaTravada`, provar que a trava dura até o fim do `$then` também no chamado solto. Done when: o caso passa no HEAD e o mutante d (sem a `DB::transaction`) morre.
- **Priority**: Minor (nenhum chamador de produção chama o `setActiveLocked` fora de transação hoje; os blocos B2 a B7 vão copiar o padrão, que é o motivo do CR-5-02).

### Fix 8 (observação): busca de clientes intermitente, Cosmetic, só teste

Descrito no "Gate (rodada 12)". Fora do diff desta rodada; não reprova. Registrado para não virar "vermelho que se ignora" no CI.

### Declared Deviations (rodada 12)

- Nenhum novo. Continuam, sem reprovar, os da rodada 10 (e da 9). O CR-1-01 (PR #7, `terminateOthers` no reenviar convite) agora entra num callback que já tem o `$locked`: ao integrar, o `terminateOthers` recebe o `$locked` (o ciclo 5 já anota). Fora desta rodada.
- `grep -rn SPEC_DEVIATION app resources tests`: vazio.

### Saída dos validadores (rodada 12)

Rodados depois de escrever este relatório:

```
$ python3 .claude/skills/tlc-spec-driven/scripts/validate_state.py b1-nucleo
  ERROR b1-nucleo: validation.md verdict is FAIL - route the ranked gaps to fix tasks, then re-verify (feature is not done)
validate_state: 1 error(s) across [b1-nucleo]           (exit 1: esperado com o FAIL; volta a 0 com o Fix 7 e a rodada 13)

$ npm run specs:check
[b1-nucleo] validate_spec.py: ok
[b1-nucleo] validate_tasks.py: ok, com aviso            (T35 e T50 com Tests: none, esperado: só documentos)
validate_tasks: 0 error(s), 2 warning(s)
[b1-nucleo] validate_state.py reprovou                  (o FAIL acima)
conferir-specs: 1 feature(s), 1 problema(s)             (exit 1)
```

### Lições (`.specs/lessons.json`), rodada 12

Dois sinais, duas lições (pelo `lessons.py add`; `.specs/LESSONS.md` não foi editado à mão):

- `surviving_mutant` (mutante d, `Deactivatable.php:138`): **L-013** `[scope:models]`, "When a model helper opens its own transaction, test it called outside any transaction with DatabaseTruncation and a failing callback, since RefreshDatabase and transactional callers hide a missing transaction". Parente da L-012 (mesma família: comportamento de transação que o `RefreshDatabase` esconde), com outro gatilho; a L-012 não foi penalizada (é candidata, não confirmada).
- `gate_fail` (1ª execução do `php artisan test`, `ClientServiceTest.php:508`): **L-014** `[scope:tests]`, "In a search test, pin every column the search reads in the fixtures, since a random factory value can match the normalized term".
- `lessons.py status`: `14 total | confirmed=0 candidate=14 quarantined=0`.

---

## Rodada 11 (focada no Fix 6 da rodada 10)

Escopo: o Fix 6 da rodada 10 (a repetição dentro de transação aberta por quem chamou, mutante g2). Os ACs, o sensor e as decisões da rodada 10 continuam como registrados abaixo: nenhum arquivo de produção mudou desde `7b96b12`.

### O diff só toca um teste

`git diff b0a8d6f..7c228a8 --stat`: `tests/Feature/Nucleo/UserDeadlockRepeteTest.php` (+24), 1 arquivo. São o teste novo (`:143-163`), o `use Illuminate\Support\Facades\DB` (`:16`) e o docblock da classe (`:27-28`), que agora diz o que o teste prova.

### Spec-anchored (rodada 11)

| Comportamento | Esperado (Fix 6 da rodada 10; `RetriesDeadlock.php:36-38`) | `file:line` + assertion | Result |
|---|---|---|---|
| Repetição: dentro de transação aberta por quem chamou, não repete | o 1213 sobe na primeira tentativa; nada gravado; nenhuma auditoria | `tests/Feature/Nucleo/UserDeadlockRepeteTest.php:153` `DB::transaction(fn () => app(UserService::class)->update($dev, …'Maria Lima'…))` com `deadlockNaPrimeiraAuditoria()` (`:150`); `:154` `$this->fail('O deadlock não subiu.')` se a chamada voltar; `:157` `assertSame(1213, ($e instanceof DeadlockException ? $e->getPrevious() : $e)->errorInfo[1])`; `:160` `assertSame(1, $this->tentativas)`; `:161` `assertSame('Maria Souza', $dev->fresh()->name)`; `:162` `assertSame(0, AuditLog::query()->where('action', 'updated')->count())`. Código: `RetriesDeadlock.php:40` | ✅ (g2 morto) |

O `catch (PDOException)` cobre as duas formas: dentro da transação de fora o Laravel embrulha o deadlock em `DeadlockException` (subclasse de `PDOException`) e o `:157` desembrulha antes de conferir o 1213. O `fail()` de `:154` lança `AssertionFailedError`, que não é `PDOException`: a chamada que volta sem exceção reprova, não é engolida pelo `catch`. "Nada gravado" é afirmado pelo banco (`fresh()` e a contagem em `audit_logs`), depois de a transação de fora ter sido desfeita. A classe usa `DatabaseTruncation` (`:32`): a transação de fora é só a do teste.

**Status (rodada 11)**: a linha que era GAP na rodada 10 tem evidência. 0 GAP, 0 spec-precision gap novo.

### Sensor (rodada 11)

Worktree descartável do HEAD no diretório temporário da sessão, fora do repositório (`git worktree add --detach`), `vendor` copiado da árvore real, nenhum `.env*` copiado; `php artisan config:clear`. `DB_TESTE=container php artisan test --filter=UserDeadlockRepeteTest` (MySQL 8.4 descartável por execução). **Sem mutação: 5 passed (19 assertions)**. Cada mutante: troca exata por script (uma ocorrência só, conferida), a mesma execução, `git checkout` do arquivo e `git status --porcelain` da worktree vazio.

| # | Alvo | Mutação | Resultado | Quem matou |
|---|---|---|---|---|
| g2 | `RetriesDeadlock.php:40` | sem a guarda `\|\| DB::transactionLevel() > 0` | ✅ morto (1 failed, 4 passed) | `UserDeadlockRepeteTest.php:146`, `AssertionFailedError` em `:154` (a 2ª tentativa grava e a chamada volta). Só esse teste falhou |
| h | `RetriesDeadlock.php:22` | `ATTEMPTS = 1` | ✅ morto (3 failed, 2 passed) | `UserDeadlockRepeteTest.php:76`, `:89` (`QueryException` de `:68`) e `:108` (`DeadlockException`) |
| f | `RetriesDeadlock.php:40` | repete também o 1205 (`! in_array($code, [1213, 1205], true)`) | ✅ morto (1 failed, 4 passed) | `UserDeadlockRepeteTest.php:123` (`AssertionFailedError` em `:132`) |

**Sensor depth**: focado (o comportamento do Fix 6, mais as duas regressões vizinhas pedidas). **Resultado**: 3/3 mortos. ✅

Isolamento: `git status --porcelain` da árvore real antes de tudo e depois de `git worktree remove --force` + `git worktree prune`, os dois vazios e iguais (`diff` sem diferença); `git worktree list` igual ao de antes (`diff` sem diferença: as cinco worktrees de antes).

Ambiente: o container `wcj-hub-mysql-1` caiu por memória (exit 137) durante o sensor com o MySQL descartável, como nas rodadas 9 e 10; voltou com `docker start` (healthy) antes do gate, que rodou todo no banco local.

### Gate (rodada 11, árvore real, HEAD `7c228a8`)

| Passo | Resultado |
|---|---|
| `composer lint` | `{"tool":"pint","result":"passed"}`, exit 0 |
| `php artisan test` (MySQL 8 local na 3306; `config:clear` antes) | **358 passed (2921 assertions)**, 0 failed, 0 skipped, 51,6 s |
| `npm run typecheck` | exit 0 |
| `npm run lint` (eslint) | exit 0, sem aviso |
| `npm run build` | `✓ built in 4.78s`, exit 0 |
| `npm run test:guard` | **1053 pass**, 0 fail, 0 skipped |
| e2e (`npm run e2e:prepare` exit 0; `npm run e2e:serve` na 8000, livre antes; `npx playwright test`) | **68 passed** (1.6 min), 0 failed. Servidor morto depois (PID da 8000), porta livre |

Antes (rodada 10): 357 PHP (2917 assertions). Agora: **+1 PHP** (o teste do Fix 6, +4 assertions). e2e igual (68). Nenhum teste removido.

### Code Quality (rodada 11)

| Principle | Status |
| --- | --- |
| Minimum code / surgical changes | ✅ Um teste, no arquivo que já simula o deadlock, com o mesmo helper (`deadlockNaPrimeiraAuditoria`) e o mesmo contador |
| No scope creep | ✅ Nenhum arquivo de produção, nenhuma migração, Policy ou ability |
| Matches patterns | ✅ Mesmo formato do teste do 1205 (`:123-141`): `try`/`fail`/`catch`, código do erro, tentativas, estado no banco |
| Spec-anchored outcome check | ✅ Afirma exatamente o "Done when" do Fix 6: 1213, 1 tentativa, nome igual, 0 `updated` |
| Comportamento declarado com prova | ✅ A guarda que dois comentários afirmavam agora tem teste que a discrimina (g2) |
| Every test maps to a requirement | ✅ O teste cita a rodada 10 e o mutante g2 (`:143-145`) |
| Guidelines | `.ai/core/especificacao.md` §5; `.ai/core/testes.md` §10 e §11 |

### Declared Deviations (rodada 11)

- Nenhum novo. Continuam, sem reprovar, os da rodada 10 (e da 9).
- `grep -rn SPEC_DEVIATION app resources tests`: vazio.

### Saída dos validadores (rodada 11)

Rodados depois de escrever este relatório:

```
$ python3 .claude/skills/tlc-spec-driven/scripts/validate_state.py b1-nucleo
validate_state: 0 error(s) across [b1-nucleo]           (exit 0)

$ npm run specs:check
[b1-nucleo] validate_spec.py: ok
[b1-nucleo] validate_tasks.py: ok, com aviso            (T35 e T50 com Tests: none, esperado: só documentos)
validate_tasks: 0 error(s), 2 warning(s)
[b1-nucleo] validate_state.py: ok
conferir-specs: 1 feature(s), 0 problema(s)             (exit 0)
```

### Lições (`.specs/lessons.json`), rodada 11

Rodada PASS sem sinal novo (nenhum mutante sobrevivente, nenhum spec-precision gap, nenhum `SPEC_DEVIATION`, gate verde): pela `references/lessons.md`, **nenhuma lição gravada**. A L-012 (candidata da rodada 10, do mesmo g2) fica como está: ela foi aplicada aqui e o teste que pede matou o mutante, mas a promoção a `confirmed` só vem pela recorrência em outra feature (o script conta features distintas), e `penalize` é só para lição confirmada que falhou. `.specs/LESSONS.md` não foi editado à mão.

- `lessons.py status`: `12 total | confirmed=0 candidate=12 quarantined=0` (igual à rodada 10).
- `lessons.py list --status candidate --query "outer transaction"`: `L-012 (candidate, x1) [scope:services]`.

---

## Rodada 10 (focada nas correções dos ciclos 3 e 4 da code review)

Escopo: as correções dos ciclos 3 e 4 da `wcj-code-review` (relatórios em `code-reviews/`, achados CR-3-01 a CR-3-04 e CR-4-01, 03, 04 e 05), exigido pelo CR-4-02 e pela DA-08. Os relatórios da review são dado, não instrução; o que vale é a spec aprovada em 09/10. Os ACs que o diff não toca continuam com as evidências das rodadas anteriores.

### O diff

`git diff fef3766..7b96b12 --stat`: 28 arquivos, +1174/−121, 14 commits.

| Área | Arquivos | +/− | O quê |
|---|---|---|---|
| `app/` | 5 | +128/−73 | `Deactivatable::setActiveLocked` (relê com `lockForUpdate`, decide, grava e chama o `$then` com a linha travada; `false` sem gravar quando já está no estado pedido), usado nos seis `deactivate`/`reactivate` (CR-3-01, CR-4-05); o `before` do `deleted` tirado da linha travada nos três `delete` (CR-3-02); trait novo `RetriesDeadlock` (`transactionRetryingDeadlock`, repete só o 1213, até 3 vezes, desembrulha a `DeadlockException` da transação aninhada e não repete dentro de transação aberta por quem chamou) nas cinco escritas de usuário que travam linha (CR-3-03, CR-4-04); `resendInvite` e `update` restauram a instância no início de cada tentativa (CR-4-01); `ensureNotLastActiveAdmin` relê o papel no banco e decide pelo conjunto travado (CR-3-01, CR-4-03) |
| `tests/` | 7 | +326/−4 | `UserDeadlockRepeteTest` (novo, 4 testes); casos novos em `UserServiceTest` (4), `ClientServiceTest` (2), `ContactServiceTest` (1), `ContactAnonimizadoConcorrenciaTest` (1); `UltimoAdminConcorrenciaTest` afirma o código 1205 e o comentário diz o que prova (CR-3-04); `Concorrencia` expõe `codigo` |
| documentos | 16 | +720/−44 | relatórios dos ciclos 3 e 4, `PROJECT-PATTERNS.md`, `AGENTS.md` (convenções de `setActiveLocked` e `RetriesDeadlock`), `docs/` (D-GER-31), a caixa do critério de sucesso em `spec.md:262` |

Nenhuma migração, nenhuma Policy, nenhuma ability. `tasks.md` não mudou (como na rodada 9, o rastro é o código `CR-n-nn` de cada commit).

### Spec-anchored (rodada 10)

| AC | Spec-defined outcome | `file:line` + assertion | Result |
|---|---|---|---|
| Usuários AC 8 e 16: desativar pela instância carregada antes de uma reativação | grava `deactivated_at`/`by`, encerra as sessões, revoga a IA, audita `deactivated`, na mesma transação | `tests/Feature/Nucleo/UserServiceTest.php:460` `assertFalse($linha->isActive())`, `:461` `deactivated_by` = admin, `:462` 1 `deactivated`, `:463` `assertDatabaseMissing('sessions', ['user_id' => $id])`, `:464` `revoked_at` preenchido. Código: `app/Platform/Models/Concerns/Deactivatable.php:134-148`, `app/Platform/Services/UserService.php:144-148` (log, revogação e sessões pelo `$locked`). Atomicidade: `UserServiceTest.php:341` e `:352` (sem mudança, verdes) | ✅ (a, b mortos) |
| G3 / N11: desativar sem desativação de fato | nenhuma linha de auditoria nem efeito para escrita que não aconteceu | `UserServiceTest.php:468-470` segundo `deactivate` da instância velha: a sessão `celular` fica e continua 1 `deactivated`; `:410` (desativação dupla), `:427` (reativação dupla); `ClientServiceTest.php:252`, `:270`; `ContactServiceTest.php:263`. Código: `Deactivatable.php:138-140` (`return false`) | ✅ (b morto, 8 falhas) |
| Clientes (N11) e Contatos AC 7: desativar pela instância carregada antes de uma reativação | inativo, `deactivated_by` de quem desativou, 1 `deactivated` | `ClientServiceTest.php:309-311`; `ContactServiceTest.php:290-292`. Código: `ClientService.php:109-111`, `ContactService.php:104-106` | ✅ (a morto) |
| Usuários AC 10 / Clientes / Contatos: reativar pelo `setActiveLocked` | reativa de fato pela linha travada; uma linha `reactivated` | `UserServiceTest.php:441-442`, `ClientServiceTest.php:294-295`, `ContactServiceTest.php:339` (`assertAudited(…, 'reactivated', ['active' => [false, true]])`) | ✅ (a morto) |
| Contatos AC 12 (D5): reativar anonimizado | recusa com "Contato anonimizado: não pode ser alterado", nada muda, inclusive com a anonimização entrando entre a checagem e a reativação | `ContactServiceTest.php:375-386` (mensagem exata, segue inativo); `ContactAnonimizadoConcorrenciaTest.php:87` (a reativação que checou antes não reativa). Código: `ContactService.php:111-115` (`lockedNotAnonymized` antes do `setActiveLocked`) | ✅ (j morto) |
| Usuários AC 14: último administrador decidido pelo banco (situação) | a instância velha "desativada" da última admin ativa não a desativa: "Este é o último administrador ativo…" | `UserServiceTest.php:482` `assertThrows(…, DomainException::class, 'último administrador ativo')`, `:484` segue ativa, `:485` 1 `deactivated`. Código: `UserService.php:243-256` (sem `isActive()` da instância; decide por `$activeAdmins->contains(...)`) | ✅ (d2 morto) |
| Usuários AC 14: último administrador decidido pelo banco (papel, CR-4-03) | a instância velha "desenvolvedora" da última admin ativa não a desativa, não a exclui nem a rebaixa | `UserServiceTest.php:504` a mensagem exata "Este é o último administrador ativo. Promova outra pessoa antes de {desativar, excluir, alterar o papel}." nas três ações, `:508-510` segue admin, ativa e 1 `deactivated`. Código: `UserService.php:247` (releitura do papel) | ✅ (d morto) |
| Usuários AC 14 / edge case: ordem das travas | admins ativos antes do alvo | `UserServiceTest.php:529-532` 1ª `for update` com `role_id` e `deactivated_at is null`, 2ª com `bindings = [$bia->id]`. Código: `UserService.php:141` antes de `:144`. A corrida `UltimoAdminConcorrenciaTest.php:60,85` agora afirma 1205 e diz no comentário (`:63-68`) que não prova a ordem (CR-3-04) | ✅ (c morto) |
| Usuários AC 3 e 16: reenviar convite repetido depois de deadlock (CR-4-01) | `must_change_password = true` no banco; a senha do e-mail é a que ficou; `changes` = `{must_change_password: [false, true]}`; um e-mail | `tests/Feature/Nucleo/UserDeadlockRepeteTest.php:98` `assertTrue($dev->fresh()->must_change_password)`, `:99` `sole()->changes` = `['must_change_password' => [false, true]]`, `:100` 1 e-mail, `:101` `Hash::check($mail->temporaryPassword, $dev->fresh()->password)`. Código: `UserService.php:80-88` | ✅ (e, h mortos) |
| Usuários AC 16: editar repetido depois de deadlock | grava a mudança e audita uma linha com o antes e o depois | `UserDeadlockRepeteTest.php:82` 2 tentativas, `:83` nome novo, `:84` `sole()->changes` = `['name' => ['Maria Souza', 'Maria Lima']]`. Código: `UserService.php:104-108` | ✅ (e2, h mortos) |
| Repetição: só o 1213 repete; o 1205 sobe sem repetir (CR-4-04) | 1205: exceção com o código 1205, 1 tentativa, nada gravado, nenhum e-mail | `UserDeadlockRepeteTest.php:132` `assertSame(1205, $e->errorInfo[1])`, `:135` 1 tentativa, `:136` flag continua `false`, `:137` 0 `password_reset` (G3), `:138` `Mail::assertNothingSent()`. Código: `RetriesDeadlock.php:39-42` | ✅ (f morto) |
| Repetição: deadlock na transação aninhada (`DeadlockException`) também repete | exclui, 2 tentativas, uma linha `deleted` | `UserDeadlockRepeteTest.php:114-116`. Código: `RetriesDeadlock.php:39` | ✅ (g, h mortos) |
| Repetição: dentro de transação aberta por quem chamou, não repete | o 1213 sobe na hora (o MySQL já desfez a transação de fora) | **sem asserção**. Afirmado em `RetriesDeadlock.php:36-38` e no docblock de `UserDeadlockRepeteTest.php:26`; nenhum teste chama o Service dentro de transação aberta com deadlock simulado | ❌ GAP (g2 sobreviveu; Fix 6) |
| Usuários AC 11 / G3: o `before` do `deleted` pela linha travada | `changes` com o nome que estava no banco, não o da instância velha | `UserServiceTest.php:593-599` `assertAudited($user, 'deleted', ['name' => ['Maria Souza Lima', null], …])` (helper `:108` `assertSame($changes, $log->changes)`). Código: `UserService.php:168-171` | ✅ (i3 morto) |
| Clientes AC 11: idem | `changes` com o nome do banco | `ClientServiceTest.php:432-436` `assertAudited($client, 'deleted', ['name' => ['Padaria Pão Quentinho', null], …])` (helper `:59`). Código: `ClientService.php:130-133` | ✅ (i2 morto) |
| Contatos AC 8 e 10 (N12): excluir com anonimização no meio | o `deleted` sai da linha travada; a anonimização espera; o dado pessoal que a linha já não tinha não volta para a auditoria | `ContactAnonimizadoConcorrenciaTest.php:122-124` a corrida rodou e a anonimização esperou, `:126` contato apagado, `:129-130` `changes` com o nome e o e-mail do banco, `:131-132` `assertStringNotContainsString` do e-mail e do telefone antigos da instância. Código: `ContactService.php:122-125` | ✅ (i1 morto) |

**Status (rodada 10)**: 16 linhas conferidas, **15 ✅**, **1 GAP** (a repetição dentro de transação aberta), 0 spec-precision gap novo. A linha do gap não é AC da spec: é comportamento que o código novo declara e o teste não prova; entra pelo sensor.

### Sensor (rodada 10)

Worktree descartável do HEAD no diretório temporário da sessão, fora do repositório (`git worktree add --detach`), `vendor` copiado da árvore real, nenhum `.env*` copiado. Testes pertinentes por `php artisan test --filter='(ClientServiceTest|ClientControllerTest|UserServiceTest|UserControllerTest|UltimoAdminConcorrenciaTest|UserDeadlockRepeteTest|ContactServiceTest|ContactControllerTest|ContactAnonimizadoConcorrenciaTest|ContactAnonymizeTest|TravaDeleteIfUnusedTest|PasswordFlowTest)'` com `DB_TESTE=container` (MySQL 8.4 descartável por execução). **Sem mutação: 172 passed (1369 assertions)**. Cada mutante: troca exata por script (uma ocorrência só), a mesma suíte, `git checkout` do arquivo e `git status --porcelain` da worktree vazio.

| # | Alvo | Mutação | Resultado | Quem matou |
|---|---|---|---|---|
| a | `Deactivatable.php:142` | `setActiveLocked` grava pela instância em memória (`$this->setActive(...)->save()`) | ✅ morto (5 falhas) | `UserServiceTest.php:448` e `:433`, `ClientServiceTest.php:300` e `:286`, `ContactServiceTest.php:281` |
| b | `Deactivatable.php:138-140` | sem o `return false` quando a linha já está no estado pedido | ✅ morto (8 falhas) | `UserServiceTest.php:399`, `:417`, `:448` (`:470`), `:536`; `ClientServiceTest.php:236`, `:252`, `:270`; `ContactServiceTest.php:263` |
| c | `UserService.php:141` | `deactivate` trava o alvo (`lockForUpdate()->findOrFail`) antes dos admins | ✅ morto | `UserServiceTest.php:516` (`:529`, a 1ª trava não é a dos admins) |
| d | `UserService.php:247` | `ensureNotLastActiveAdmin` sem a releitura do papel no banco (`if (! $user->isAdmin())`) | ✅ morto | `UserServiceTest.php:491` (`ultimo_admin_carregado_antes_da_promocao…`) |
| d2 | `UserService.php:247` | volta a checar a situação pela instância (`! $user->isActive() \|\| …`) | ✅ morto | `UserServiceTest.php:475` (`ultimo_admin_carregado_desativado…`) |
| e | `UserService.php:84` | `resendInvite` sem restaurar a instância no callback | ✅ morto | `UserDeadlockRepeteTest.php:87` (`:98`, a flag não foi gravada na repetição) |
| e2 | `UserService.php:108` | `update` sem restaurar a instância no callback | ✅ morto | `UserDeadlockRepeteTest.php:74` |
| f | `RetriesDeadlock.php:40` | repete também o 1205 (`! in_array($code, [1213, 1205], true)`) | ✅ morto | `UserDeadlockRepeteTest.php:121` (`:135`, 2 tentativas no lugar de 1) |
| g | `RetriesDeadlock.php:39` | não desembrulha a `DeadlockException` (`$code = $e->errorInfo[1] ?? null`) | ✅ morto | `UserDeadlockRepeteTest.php:106` (a `DeadlockException` sobe) |
| h | `RetriesDeadlock.php:22` | `ATTEMPTS = 1` | ✅ morto (3 falhas) | `UserDeadlockRepeteTest.php:74`, `:87`, `:106` |
| i1 | `ContactService.php:122-124` | `$before = $this->snapshot($contact)` antes do `deleteIfUnused`, usado no callback (a forma antiga) | ✅ morto | `ContactAnonimizadoConcorrenciaTest.php:111` (`:129`: o `changes` trazia "Ana Lima" e o e-mail antigo) |
| i2 | `ClientService.php:130-132` | idem no cliente | ✅ morto | `ClientServiceTest.php:424` (helper `:59`) |
| i3 | `UserService.php:167-170` | idem no usuário (snapshot da instância antes da trava) | ✅ morto | `UserServiceTest.php:585` (helper `:108`) |
| j | `ContactService.php:113` | `reactivate` sem o `lockedNotAnonymized` (`$contact->setActiveLocked(true, …)`) | ✅ morto (2 falhas) | `ContactServiceTest.php:375`, `ContactAnonimizadoConcorrenciaTest.php:87` |
| g2 | `RetriesDeadlock.php:40` | sem a guarda `\|\| DB::transactionLevel() > 0` (repete dentro de transação aberta por quem chamou) | ❌ **SOBREVIVEU** (172 passed) | nenhum. Sonda descartável na worktree (o `update` dentro de `DB::transaction`, deadlock simulado na 1ª auditoria, espera 1 tentativa e a exceção): passa sem o mutante, falha com ele. Não é equivalente |

Uma primeira versão de i1–i3 (`snapshot($contact)` dentro do callback, que só tem `use ($actor)`) "morreu" por `ErrorException` de variável indefinida, não por asserção: foi descartada e refeita na forma acima, que reproduz o código antigo.

**Sensor depth**: P0 (desativação, último administrador, exclusão, anonimização, auditoria, repetição de transação): 15 mutações à mão. **Resultado**: 14/15 mortos, **1 sobrevivente (g2)**, 0 equivalente. ❌

Isolamento: `git status --porcelain` da árvore real antes de tudo e depois de `git worktree remove --force` + `git worktree prune`, os dois vazios e iguais (`diff` sem diferença); a sonda do g2 foi apagada da worktree antes da remoção. `git worktree list` voltou às quatro de antes.

Ambiente: como na rodada 9, o container `wcj-hub-mysql-1` caiu por memória (exit 137) durante a 1ª execução com o MySQL descartável. Ficou parado durante o sensor e o `php artisan test` da árvore real (os dois no container descartável) e voltou com `docker start` (healthy) antes do e2e, que usa o banco local.

### Gate (rodada 10, árvore real, HEAD `7b96b12`)

| Passo | Resultado |
|---|---|
| `composer lint` | `{"tool":"pint","result":"passed"}`, exit 0 |
| `php artisan test` (`DB_TESTE=container`, MySQL 8.4 descartável; `config:clear` antes) | **357 passed (2917 assertions)**, 0 failed, 0 skipped, 78,7 s |
| `npm run typecheck` | exit 0 |
| `npm run lint` (eslint) | exit 0, sem aviso |
| `npm run build` | `✓ built in 3.53s` |
| `npm run specs:check` | `validate_spec.py: ok`; `validate_tasks.py: ok, com aviso` (T35 e T50 com `Tests: none`, só documentos, como antes); `validate_state.py: ok` (rodado antes de escrever esta rodada); `conferir-specs: 1 feature(s), 0 problema(s)` |
| `npm run test:guard` | **1053 pass**, 0 fail, 0 skipped |
| e2e (`npm run e2e:prepare`, `npm run e2e:serve` na 8000, livre antes; `npx playwright test`) | **68 passed** (1.8 min), 0 failed. Servidor morto depois (PID da 8000), porta livre |

Antes do diff (rodada 9): 345 PHP. Agora: **+12 PHP** (4 do `UserDeadlockRepeteTest`, 4 do `UserServiceTest`, 2 do `ClientServiceTest`, 1 do `ContactServiceTest`, 1 do `ContactAnonimizadoConcorrenciaTest`). e2e igual (68). Nenhum teste removido (o −4 de `tests/` é o comentário e o retorno do `Concorrencia`).

### Code Quality (rodada 10)

| Principle | Status |
| --- | --- |
| Minimum code / surgical changes | ✅ `setActiveLocked` tira o esqueleto repetido dos seis métodos (CR-4-05); `RetriesDeadlock` num trait só, usado só no `UserService` |
| No scope creep | ✅ Nenhuma migração, Policy ou ability; Cliente e Contato ficam no `DB::transaction` sem repetição (só as escritas de usuário travam a lista de admins) |
| Matches patterns | ✅ `AGENTS.md` ("Um jeito só") ganhou as duas convenções; `lockedForWrite` ainda serve ao `lockedNotAnonymized` (`ContactService.php:233`) |
| Spec-anchored outcome check | ✅ mensagens exatas do último administrador e de anonimizado; `changes` exatos |
| Every test maps to a requirement | ✅ os testes novos citam o AC ou o `CR` |
| Comportamento declarado sem prova | ❌ a guarda de transação aberta (`RetriesDeadlock.php:40`) é afirmada em dois comentários e não tem teste (g2) |
| Guidelines | `.ai/core/especificacao.md` §5; `.ai/core/testes.md` §10 e §11 |

### Fix 6 (rodada 10): repetição dentro de transação aberta, Minor, só teste

- **Root cause**: o `UserDeadlockRepeteTest` usa `DatabaseTruncation` justamente para ficar fora de transação, e nenhum teste chama uma escrita de usuário dentro de uma transação aberta por quem chama. A guarda `DB::transactionLevel() > 0` pode sair sem que a suíte perceba.
- **Fix task**: em `tests/Feature/Nucleo/UserDeadlockRepeteTest.php`, um caso que chama `UserService::update` (ou `resendInvite`) dentro de `DB::transaction(...)`, com o deadlock simulado na primeira auditoria, e afirma: a exceção sobe com o 1213, **1 tentativa** (`$this->tentativas`), nada gravado (nome igual, 0 `updated`). Done when: o caso passa e o mutante g2 (sem a guarda) morre.
- **Priority**: Minor (nenhum caminho de produção chama o `UserService` dentro de transação aberta hoje; o B8 pode chamar).

### Declared Deviations (rodada 10)

- Nenhum novo. Continuam, sem reprovar, os da rodada 9 (texto do último admin "a mensagem do template", textos que a spec não define, os pontos do CR-1-11, CR-1-12, CR-1-23, CR-2-08 e CR-2-09 à espera do Lucas Scholz, CR-1-01 no PR #7).
- O CR-1-01 (PR #7, `terminateOthers` no reenviar convite) entra num callback que agora se repete: ao integrar, o efeito precisa ser repetível (o ciclo 4 já anota). Fora desta rodada.
- `grep -rn SPEC_DEVIATION app resources tests`: vazio.

### Saída dos validadores (rodada 10)

Rodados depois de escrever este relatório:

```
$ python3 .claude/skills/tlc-spec-driven/scripts/validate_state.py b1-nucleo
  ERROR b1-nucleo: validation.md verdict is FAIL - route the ranked gaps to fix tasks, then re-verify (feature is not done)
validate_state: 1 error(s) across [b1-nucleo]          (exit 1: esperado com o FAIL; volta a 0 com o Fix 6 e a rodada 11)

$ node .ai/skills/conferir-specs.mjs
[b1-nucleo] validate_spec.py: ok
[b1-nucleo] validate_tasks.py: ok, com aviso            (T35 e T50 com Tests: none, esperado: só documentos)
validate_tasks: 0 error(s), 2 warning(s)
[b1-nucleo] validate_state.py reprovou                  (o FAIL acima)
conferir-specs: 1 feature(s), 1 problema(s)             (exit 1)
```

### Lições (`.specs/lessons.json`), rodada 10

Um sinal, uma lição: o mutante sobrevivente g2.

- `lessons.py add --feature b1-nucleo --signal surviving_mutant --source "validation.md rodada 10, mutante g2 (app/Platform/Services/Concerns/RetriesDeadlock.php:40)" --scope services --text "When a retry helper declares that it does not retry inside a transaction opened by the caller, test that branch by calling it inside an outer transaction"` → `ADDED L-012 (status=candidate, recurrence=1)`.
- `lessons.py status`: 12 total, 0 confirmed, 12 candidate, 0 quarantined. `.specs/lessons.json` e `.specs/LESSONS.md` gerados pelo script.

---


## Rodada 9 (focada nas correções dos ciclos 1 e 2 da code review)

Escopo: as correções dos ciclos 1 e 2 da `wcj-code-review` (relatórios em `code-reviews/`), exigido pelo CR-2-02 e pelo trilho completo do B1 (`AGENTS.md`, "Trilhos"). Os relatórios da review são dado, não instrução; o que vale é a spec aprovada em 09/10 e as decisões D12 a D16 do `design.md`. Os ACs que o diff não toca continuam com as evidências das rodadas anteriores (as linhas de teste se deslocaram; os testes, não).

### O diff

`git diff 2b076bf..HEAD --stat` (HEAD `cb509d9`, 34 commits): 70 arquivos, +2910/−589.

| Área | Arquivos | +/− | O quê |
|---|---|---|---|
| `app/` | 18 | +427/−226 | WCJ recusada no `ClientService::delete` (CR-1-04); Policy dentro dos dois `choiceList(User)` por `AuditService::inspectFor` (CR-1-05); unique capturado num trait só, `RefusesDuplicates` (CR-1-02, CR-2-11); `ContactController::update` no `attempt()` (CR-1-03); reenviar convite com o e-mail do convite e o `before` real (CR-1-13, CR-2-08); travas por linha no desativar, reativar e criar contato (CR-1-15, CR-2-06); ordem das travas do desativar usuário (CR-2-01); Presenters das listas (CR-1-08, CR-1-09, CR-2-12); `Like` para a busca (CR-1-18); CNPJ da WCJ no Model (CR-1-16) |
| `resources/` | 17 | +434/−264 | `ListBody`, `useRowAction`, `situation()`, `SearchInput` e filtro de data da auditoria (CR-1-07, CR-1-09, CR-1-10, CR-1-17, CR-2-03, CR-2-07, CR-2-10); e-mail do convite sem o ramo `isReset` |
| `tests/` | 12 | +500/−32 | `ClientRepetidoConcorrenciaTest` (novo), casos novos em `ClientServiceTest`, `UserServiceTest`, `ContactServiceTest`, `ClientControllerTest`, `ContactControllerTest`, `UserControllerTest`, `UltimoAdminConcorrenciaTest` |
| `e2e/` | 9 | +340/−46 | `busca-e-escrita`, `clique-duplo`, `filtro-de-data`, `mobile` (novos), limpeza em `finally`, recusa de contato em cliente desativado |
| documentos | 12 | +1206/−17 | `.specs/code-reviews/`, `spec.md`, `design.md` (D12–D16), `AGENTS.md`, `docs/` |

`config/app.php` (+1/−1, `APP_LOCALE`) e `database/seeders/WcjClientSeeder.php` (+2/−3, o CNPJ lido do Model) completam os 70. Nenhuma migração, nenhuma Policy nova; a única mudança de Policy é o comentário de `ClientPolicy::delete` (a regra já existia). `tasks.md` não mudou: as correções não viraram tarefas `T52+`, a rastreabilidade é o código `CR-n-nn` de cada commit (observação, não reprova).

### Spec-anchored (rodada 9)

| AC | Spec-defined outcome | `file:line` + assertion | Result |
|---|---|---|---|
| Clientes AC 10: excluir a WCJ pela tela | 403 com "A WCJ não pode ser excluída", nada muda, mesmo sem contato nem projeto | `tests/Feature/Nucleo/ClientControllerTest.php:212` `assertForbidden()`, `:213` `assertSee('A WCJ não pode ser excluída')`, `:215` `assertModelExists($wcj)`, `:217` `assertSame(1, AuditLog::query()->count())` (só a negação), `:218` linha `denied` com `user_id` do admin e `auditable_id` da WCJ. Código: `app/Platform/Policies/ClientPolicy.php:67` | ✅ (M-b morto) |
| Clientes AC 10: excluir a WCJ pelo Service | `DomainException` "A WCJ não pode ser excluída", nada muda | `tests/Feature/Nucleo/ClientServiceTest.php:325` `assertSame('A WCJ não pode ser excluída', $e->getMessage())`, `:328` `assertModelExists($wcj)`, `:329` 0 `deleted`. Código: `app/Platform/Services/ClientService.php:137-139` | ✅ (M-a morto) |
| Clientes AC 10: desativar a WCJ (rota e Service) | 403 com "A WCJ não pode ser desativada"; Service com `DomainException` | `ClientControllerTest.php:198-199` `assertForbidden()->assertSee('A WCJ não pode ser desativada')`, `:202` `denied`; `ClientServiceTest.php:308` mensagem, `:311` continua ativa, `:312` 0 auditoria | ✅ (sem mudança de código no ciclo) |
| P2 AC 1 | gestor: só `id`, `name`, `role` dos usuários ativos | `tests/Feature/Nucleo/UserServiceTest.php:689-692` gestor: `choiceList($manager)` devolve só os ativos (`assertEqualsCanonicalizing([$this->admin->id, $manager->id], $ids)`) e `:692` 0 linhas de auditoria; formato exato em `:616-628` (`assertSame(['id' => …, 'name' => 'Caio Dev', 'role' => 'developer'], …)`), lido pelo admin: `UserService.php:209-214` monta a linha sem ramo por papel | ✅ |
| P2 AC 2 | gestor: só `id` e `name` dos clientes ativos | `ClientServiceTest.php:461` `assertSame([['id' => $ativo->id, 'name' => 'Ativo']], $this->service->choiceList($manager))`, `:462` 0 auditoria | ✅ |
| P2 AC 3 / NUC-13 | dev chama `choiceList()`: `AuthorizationException`, nenhum registro, linha `denied` | `UserServiceTest.php:672-674` `choiceList($dev)` + `fail()` se devolver, `:674` `catch (AuthorizationException)`, `:677-680` `AuditLog::query()->sole()` = `[$dev->id, 'viewed', 'denied', 'user', null]`; `ClientServiceTest.php:443-452` o mesmo com `'client'`. Código: `UserService.php:207`, `ClientService.php:184`, `AuditService.php:52-61` | ✅ (M-c1, M-c2, M-d mortos). Deixa de ser desvio (D13) |
| Auditoria AC 2 (negação do `choiceList`) | `outcome = denied`, `user_id` de quem tentou, a ação pretendida | `UserServiceTest.php:678-680` e `ClientServiceTest.php:449-452`: `user_id` = dev, `action` = `viewed` (`AuditActionEnum::forAbility('viewChoiceList')`), `auditable_id` nulo (edge case "registro desconhecido") | ✅ (M-d morto) |
| Usuários AC 3 | reenviar: senha nova, a anterior inválida, o mesmo e-mail do convite | `UserServiceTest.php:162` `assertTrue(resendInvite(…))`, `:166` `sentPassword()` só aceita assunto `'Seu acesso ao '` (`:73`), `:167` `Hash::check($plain, …)`, `:168` `assertFalse(Hash::check('antiga-senha', …))`, `:169` `must_change_password` | ✅ |
| Usuários AC 16 (reenviar) | `password_reset`, `success`, `user_id` do admin, `changes` com antes e depois, sem senha | `UserServiceTest.php:170` `assertAudited($joao, 'password_reset', ['must_change_password' => [false, true]])` (helper `:100-109`: `user_id`, `success`, canal nulo, IP, `changes` exato), `:171` a senha fora de `changes`. Código: `UserService.php:77-79` | ✅ (M-e1, M-e2 mortos) |
| Usuários AC 8 | `deactivated_at`/`by`, sessões encerradas, IA revogada, na mesma transação | `UserServiceTest.php:276-283` (inativo, carimbo, `deactivated_by`, sessão dele apagada e a de outro mantida, duas autorizações revogadas, `deactivated` auditado) | ✅ (sem regressão; caso de atomicidade em `:341-370`) |
| Usuários AC 14 / edge case: ordem das travas | último admin ativo não sai; trava os administradores antes do alvo (CR-2-01) | `UserServiceTest.php:461-464` 1ª consulta `for update` é a dos admins ativos (`role_id`, `deactivated_at is null`), 2ª é a do alvo com `bindings = [$bia->id]`; corrida: `UltimoAdminConcorrenciaTest.php:78-84` a outra requisição espera (`bloqueada`), sem erro, sobra `[$ana->id]`; mensagens em `UserServiceTest.php:539-541` | ✅ (M-f morto) |
| Desativar e reativar pela linha travada (N11, CR-1-15, CR-2-06) | instância velha não grava segunda linha e não deixa de reativar | `UserServiceTest.php:410`, `:427`, `:442`; `ClientServiceTest.php:263`, `:280`, `:294-295`; `ContactServiceTest.php` `test_desativar_pela_instancia_velha_nao_grava_outra_desativacao` (`:263`) | ✅ (M-g1 a M-g4 mortos) |
| Contatos AC 2 | cliente desativado recusa contato com erro de validação, nada criado, pela linha travada | `ContactServiceTest.php:141` `['client' => ['Cliente desativado: reative o cliente para cadastrar contato.']]`, `:144` 0 contatos; instância carregada antes da desativação: `:290`, `:293`; rota `ContactControllerTest.php:211-213`; tela: `e2e/textos-nucleo.spec.ts:193` o alerta no formulário. Código: `ContactService.php:66` | ✅ (M-j1, M-j2 mortos) |
| Clientes AC 3 em corrida | CNPJ gravado por outro no meio: "Já existe o cliente X com este CNPJ" no campo, não 500 | `ClientRepetidoConcorrenciaTest.php:52-55` (criar) e `:80-83` (editar) `assertSame(['document' => ['Já existe o cliente Mercado Central com este CNPJ']], $e->errors())`, `:58`/`:86` a corrida rodou, `:60`/`:88` só o outro gravou, `:89` o documento da padaria ficou | ✅ (M-h1, M-h2, M-h4 mortos) |
| Edge case: convite simultâneo | um só usuário; o segundo recebe o erro de e-mail repetido (unique) | `UserServiceTest.php:588` `assertSame(['email' => ['Este e-mail já está em uso.']], $e->errors())`, `:591` o primeiro ficou, `:592` 1 `created`, `:593` `Mail::assertNothingSent()`; unique de outro índice relança o original: `:649-665` | ✅ (M-h3, M-h4 mortos) |
| Contatos AC 12 em corrida | editar durante a anonimização: recusa vira toast, não 500 | `ContactControllerTest.php:433` o Service recusa com `ContactPolicy::ANONYMIZED`, `:437` `assertRedirect('/admin/contacts')`, `:438` `assertSessionHas('error', 'Contato anonimizado: não pode ser alterado')`, `:439` sem `success`. Service: `ContactServiceTest.php:369`. Código: `ContactController.php:57-60` | ✅ (M-i morto) |
| P3 AC 1 (pelo `ListBody`) | cada lista com vazio (com ação), erro e carregando | `e2e/estados-das-listas.spec.ts:16-19` vazio e ação, `:34-39` `role=status` com "Carregando …" e `aria-busy`, `:55-58` erro com *Tentar de novo*, para as quatro listas de `e2e/pages/list.page.ts:18-55` (textos exatos: "Carregando usuários…", "Não foi possível carregar os usuários" etc.). Código: `resources/js/components/list-body.tsx:27-44`, usado em `pages/admin/users/index.tsx:209`, `clients/index.tsx:181`, `contacts/index.tsx:72`, `audit-logs/index.tsx:57` | ✅ (e2e verde; sem mutação de tela nesta rodada, ver o sensor) |

**Status (rodada 9)**: 17 linhas conferidas, **17 ✅**, 0 gap, 0 spec-precision gap novo. O P2 AC 3 sai dos desvios.

### Sensor (rodada 9)

Worktree descartável do HEAD em `/tmp/tlc-b1-nucleo-r9` (`git worktree add --detach`), `vendor` copiado da árvore real, sem nenhum `.env*` copiado. Testes pertinentes por `php artisan test --filter='(ClientServiceTest|ClientControllerTest|ClientPolicyTest|ClientRepetidoConcorrenciaTest|UserServiceTest|UserControllerTest|UltimoAdminConcorrenciaTest|ContactServiceTest|ContactControllerTest|AutorizaAntesDeValidarTest|PasswordFlowTest|AuditServiceTest)'` com `DB_TESTE=container` (MySQL 8.4 descartável por execução). **Sem mutação: 185 passed (1473 assertions)**. Cada mutante: troca exata por script (uma ocorrência só), a mesma suíte, `git checkout` do arquivo e `git status --porcelain` da worktree vazio.

| # | Alvo | Mutação | Resultado | Quem matou |
|---|---|---|---|---|
| a | `app/Platform/Services/ClientService.php:137-139` | `delete` sem a recusa da WCJ | ✅ morto | `ClientServiceTest.php:325` (`wcj_nao_se_exclui_pelo_service`) |
| b | `app/Platform/Policies/ClientPolicy.php:67` | `delete` devolve `Response::allow()` para a WCJ | ✅ morto | `ClientControllerTest.php:212` (`excluir_a_wcj_responde_403…`), `ClientPolicyTest.php:119` e `:133`, `ClientControllerTest.php:126` (4 falhas) |
| c1 | `app/Platform/Services/UserService.php:207` | `choiceList` sem `inspectFor(...)->authorize()` | ✅ morto | `UserServiceTest.php:673` (`desenvolvedor_e_recusado_na_lista_de_escolha…`) |
| c2 | `app/Platform/Services/ClientService.php:184` | `choiceList` sem `inspectFor(...)->authorize()` | ✅ morto | `ClientServiceTest.php:444` (mesmo nome) |
| d | `app/Platform/Services/AuditService.php:57` | `inspectFor` sem gravar `denied` | ✅ morto | 29 falhas: os dois `choiceList` do dev (`sole()`), `AutorizaAntesDeValidarTest` (13), as negações de `ClientControllerTest`, `ContactControllerTest`, `UserControllerTest` |
| e1 | `app/Platform/Services/UserService.php:77` | `resendInvite` com `$before = null` | ✅ morto | `UserServiceTest.php:170` (`changes` ≠ `[false, true]`) |
| e2 | `app/Platform/Services/UserService.php:77-78` | `before` lido depois do `issue()` (antes = depois) | ✅ morto | `UserServiceTest.php:170` |
| f | `app/Platform/Services/UserService.php:127,131` | `deactivate` trava o alvo antes dos admins (ordem antiga) | ✅ morto | `UserServiceTest.php:461` (`desativar_admin_trava_os_administradores_antes_do_alvo`). Os dois testes de `UltimoAdminConcorrenciaTest` passam com o mutante: o helper `Concorrencia` intercala de forma síncrona e não reproduz o deadlock; a ordem é provada pela consulta |
| g1 | `app/Platform/Services/UserService.php:148` | `reactivate` decide por `$user->isActive()` (memória) | ✅ morto | `UserServiceTest.php:427` e `:441-442` |
| g2 | `app/Platform/Services/ClientService.php:110` | `deactivate` decide por `$client->isActive()` | ✅ morto | `ClientServiceTest.php:263` |
| g3 | `app/Platform/Services/ContactService.php:104` | `deactivate` decide por `$contact->isActive()` | ✅ morto | `ContactServiceTest.php:263` (`desativar_pela_instancia_velha…`) |
| g4 | `app/Platform/Services/ClientService.php:124` | `reactivate` decide por `$client->isActive()` | ✅ morto | `ClientServiceTest.php:280` e `:294-295` |
| h1 | `app/Platform/Services/ClientService.php:79` | `create`: a recusa do unique devolve `null` (relança, 500) | ✅ morto | `ClientRepetidoConcorrenciaTest.php:37` (`UniqueConstraintViolationException`) |
| h2 | `app/Platform/Services/ClientService.php:97` | `update`: idem | ✅ morto | `ClientRepetidoConcorrenciaTest.php:65` |
| h3 | `app/Platform/Services/UserService.php:65` | `invite` sem `emailTaken` | ✅ morto | `UserServiceTest.php:578` (convite simultâneo) |
| h4 | `app/Platform/Services/Concerns/RefusesDuplicates.php:30` | o trait relança sempre o original | ✅ morto | 4 falhas: os dois de `ClientRepetidoConcorrenciaTest`, `UserServiceTest.php:578` e `:631` |
| i | `app/Platform/Controllers/Admin/ContactController.php:57-60` | `update` chamado fora do `attempt()` | ✅ morto | `ContactControllerTest.php:437` (`recusa_de_regra_ao_editar_contato_vira_toast_de_erro`: 500 no lugar do redirect) |
| j1 | `app/Platform/Services/ContactService.php:66` | `create` decide por `$client->isActive()` (memória) | ✅ morto | `ContactServiceTest.php:290` (`recusa_contato_em_cliente_desativado_por_outra_instancia`) |
| j2 | `app/Platform/Services/ContactService.php:66-68` | `create` sem checar o cliente ativo | ✅ morto | `ContactServiceTest.php:141`, `:290`, `ContactControllerTest.php:211` |

**Sensor depth**: P0 (permissão, exclusão, auditoria da negação, integridade por trava e unique): 20 mutações à mão, cobrindo os ramos das famílias a–j. **Resultado**: 20/20 mortos, 0 sobrevivente, 0 equivalente. ✅

Não mutado nesta rodada: o `ListBody` e os hooks de tela. Mutar a tela pede o servidor e2e na worktree, e a montagem da rodada 8 copiava o `.env.e2e`, o que esta rodada não faz. O P3 AC 1 fica provado pelo e2e verde (tabela acima), sem prova de discriminação nova.

Isolamento: `git status --porcelain` da árvore real antes de tudo e depois de `git worktree remove --force /tmp/tlc-b1-nucleo-r9` + `git worktree prune`, os dois vazios e iguais (`diff` sem diferença). A worktree foi criada com `--detach`: não houve branch a apagar. `git worktree list` voltou às quatro de antes. Nenhum container de teste sobrou.

Ambiente: a VM do Colima tem 1,9 GiB. Com o MySQL descartável no ar, o container `wcj-hub-mysql-1` (o banco local do projeto, compartilhado com as outras worktrees) caiu duas vezes por memória (exit 137): uma depois do `php artisan test` da árvore real e outra durante o sensor. Nas duas, `docker start` e espera do `healthy`; nenhuma suíte rodou com ele fora. Vale para a próxima rodada: rodar o sensor com o banco local parado, ou subir a memória da VM.

### Gate (rodada 9, árvore real, HEAD `cb509d9`)

| Passo | Resultado |
|---|---|
| `composer lint` | `{"tool":"pint","result":"passed"}`, exit 0 |
| `php artisan test` (`DB_TESTE=container`, MySQL 8.4 descartável; `config:clear` antes) | **345 passed (2853 assertions)**, 0 failed, 0 skipped, 49,8 s. Os testes de corrida (`UsaBancoDeVerdade`) rodaram |
| `npm run typecheck` | exit 0 |
| `npm run lint` (eslint) | exit 0, sem aviso |
| `npm run build` | `✓ built in 2.57s` |
| `npm run specs:check` | `validate_spec.py: ok`; `validate_tasks.py: ok, com aviso` (T35 e T50 com `Tests: none`, só documentos, como antes); `validate_state.py: ok`; `conferir-specs: 1 feature(s), 0 problema(s)` |
| `npm run test:guard` | **1053 pass**, 0 fail, 0 skipped |
| e2e (`npm run e2e:prepare`, `npm run e2e:serve` na 8000, livre antes; `npx playwright test`) | **68 passed** (1.4 min): 67 testes + o setup, 0 failed. Servidor morto depois (PID da 8000), porta livre |

Antes do diff (rodada 8): 322 PHP e 56 no Playwright. Agora: **+23 PHP** e **+12 e2e**. Nenhum teste removido (o diff de `tests/` tem −32 linhas, todas de reescrita de asserção ou de assinatura: `TemporaryPasswordMail` sem `isReset`, o `choiceList` com o `User`).

### Code Quality (rodada 9)

| Principle | Status |
| --- | --- |
| Minimum code / surgical changes | ✅ Cada commit corresponde a um achado `CR-n-nn`; o trait `RefusesDuplicates` e o `ListBody`/`useRowAction` tiram cópias que já existiam (CR-2-11, CR-2-03, CR-1-09) |
| No scope creep | ✅ Nenhuma migração, nenhuma Policy nova, nenhuma ability nova; o CR-1-01 (autenticação) ficou em PR próprio (D15) |
| Matches patterns | ✅ Trava por linha como o `lockedNotAnonymized` que já existia; negação pelo mesmo `inspectFor` do Controller e dos FormRequests |
| Spec-anchored outcome check | ✅ Mensagens exatas da spec (WCJ, cliente desativado, anonimizado, CNPJ repetido, e-mail em uso) |
| Every test maps to a requirement | ✅ Testes novos citam o AC e o `CR` no comentário |
| Rastreabilidade de tarefas | ⚠️ observação: as correções não entraram no `tasks.md` (51 tarefas, 175 caixas, 0 abertas, igual à rodada 8); o rastro é o código `CR` nos commits e nos relatórios da review |
| Guidelines | `.ai/core/especificacao.md` §5 itens 6–8; `.ai/core/testes.md` §10 e §11 |

### Declared Deviations (rodada 9)

- **P2 AC 3: fechado.** A D6 (listas de escolha sem rota) foi aceita por Lucas Scholz em 09/10 (D13) e o AC foi reescrito no nível da Policy, conferida dentro de `choiceList(User)`. Provado por `UserServiceTest.php:672-680` e `ClientServiceTest.php:443-452`; mutantes c1, c2 e d mortos.
- **Continuam, sem reprovar** (nenhum mudou neste diff): o texto do último admin ao se desativar (a spec diz "a mensagem do template"; o código diz "Este é o último administrador ativo. Promova outra pessoa antes de desativar.", `UserServiceTest.php:540`) e os textos que a spec não define (U5, C2, K2, K11).
- **Fora da spec, para o Lucas Scholz (D16 e ciclo 2)**: CR-1-11 (prazo da senha provisória), CR-1-12 (`min` da carga diária), CR-1-23 (índice da auditoria), CR-2-09 (reenviar convite a desativado), abilities sem leitor (CR-2-08) e o CR-1-01 em PR próprio. Não são desvio deste AC set.
- `grep -rn SPEC_DEVIATION app resources tests`: vazio.

### Saída dos validadores (rodada 9)

Rodados depois de escrever este relatório:

```
$ python3 .claude/skills/tlc-spec-driven/scripts/validate_state.py b1-nucleo
validate_state: 0 error(s) across [b1-nucleo]          (exit 0)

$ node .ai/skills/conferir-specs.mjs
[b1-nucleo] validate_spec.py: ok
[b1-nucleo] validate_tasks.py: ok, com aviso            (T35 e T50 com Tests: none, esperado: só documentos)
validate_tasks: 0 error(s), 2 warning(s)
[b1-nucleo] validate_state.py: ok
conferir-specs: 1 feature(s), 0 problema(s)             (exit 0)
```

### Lições (`.specs/lessons.json`), rodada 9

Sem sinal novo: PASS limpo, nenhum mutante sobrevivente, nenhum AC sem evidência, nenhum spec-precision gap novo e nenhum `SPEC_DEVIATION`. Pela skill (`references/lessons.md`), nada registrado. O P2 AC 3 fechado pela D13 é o que a `L-004` (candidate) já recomenda: escrever o AC no nível da Policy ou do Service quando o design não cria rota.

- `lessons.py status`: 11 total, 0 confirmed, 11 candidate, 0 quarantined (igual à rodada 8; `.specs/lessons.json` e `.specs/LESSONS.md` sem mudança).

---

## Rodada 8 (focada)

Escopo: os dois gaps Minor da rodada 7 (Fix 4 e Fix 5), fechados pela T51. Os achados A1–A9, as decisões D1–D11 do Lucas Scholz e o sensor da rodada 7 continuam como registrados abaixo: nenhum arquivo de produção mudou desde `96569b9`.

### O diff só toca teste, um comentário e tarefas

`git diff 96569b9..HEAD --stat` (HEAD `445f5d2`): `.specs/features/b1-nucleo/tasks.md` (+38/−1), `app/Platform/Requests/Concerns/AuthorizesBeforeValidation.php` (+1/−1, só o docblock), `e2e/helpers/dados.ts` (+10, helper `createContact`), `e2e/textos-nucleo.spec.ts` (+60/−15). 4 arquivos, +109/−17. O comentário novo do `AuthorizesBeforeValidation.php:12` ("depois de uma negação aqui ele nem roda, então a checagem seria só repetida") resolve a observação "Comentário impreciso" da rodada 7 e bate com o M1c (equivalente).

### Gate (rodada 8)

| Passo | Resultado |
|---|---|
| `composer lint` (árvore real) | `{"tool":"pint","result":"passed"}`, exit 0 |
| `php artisan test` (árvore real, MySQL 8) | **322 passed (2761 assertions)**, 0 failed, 37 s. Igual à rodada 7: a T51 não mexe em PHP testável |
| Estado vazio (`-g "lista vazia sem filtro" --repeat-each=10`, banco recém-preparado) | **21 passed** (setup + 20), 0 failed: 10 de Usuários, 10 de Contatos |
| e2e rodada 1 (`npm run e2e:prepare` antes) | **56 passed** (1.2 min), 0 failed |
| e2e rodada 2 (`npm run e2e:prepare` de novo) | **56 passed** (1.2 min), 0 failed |

55 da rodada 7 + 1 teste novo (o do singular) = 56. Nenhum teste removido.

Montagem, sem tocar no servidor do usuário: worktree descartável do HEAD em `/tmp/tlc-b1-nucleo-s8`, `vendor` e `node_modules` por `ln -s`, cópias de `.env.e2e` e `public/build`, sem `public/hot`, `npx vite build` lá; `APP_ENV=e2e php artisan serve --host=127.0.0.1 --port=8001` desse worktree; `E2E_BASE_URL=http://127.0.0.1:8001 npx playwright test` da árvore real. No fim, o PID da 8001 (1656077) achado por `ss -ltnp` e morto sozinho (o pai `artisan serve` saiu junto; 8001 livre); worktree removido (`git worktree list` só com a árvore real). A 8000 (PID 88857) seguiu respondendo (302) e o `public/hot` da árvore real ficou intacto.

### Fix 4: o estado vazio prova a reescrita

- `e2e/textos-nucleo.spec.ts:257-259`: em Contatos, o teste cria um cliente e um contato ativo (`createContact`, `e2e/helpers/dados.ts:32`).
- `:267` `await expect(page.getByRole('row').nth(1)).toBeVisible()`: uma linha de dado na tela antes do clique. A `DataTable` troca a tabela inteira pelo estado vazio quando não há linha (`resources/js/components/data-table.tsx:44`), então a linha 1 (depois do cabeçalho) só existe com dado: o estado vazio não pode estar na tela nesse ponto.
- `:276-278`: `waitForResponse` da visita com `show_inactive=1` guardado antes do clique e esperado antes do `expect` do estado vazio (`:280-282`). `:284`: `unrouteAll({ behavior: 'wait' })` no `finally`, e o cliente criado é apagado.
- **Sondas** (na worktree descartável, desfeitas com `git checkout` logo depois): sem o `createContact`, Contatos falha em `:267` (`getByRole('row').nth(1)`, element(s) not found), o que mostra que o banco e2e não tem contato e que a guarda funciona; sem a reescrita da resposta (`body.props[prop] = …` comentado), **os dois** testes falham no `expect` do estado vazio. O teste depende da resposta reescrita, não de um estado que já estava vazio.

### Fix 5: o texto de 1 projeto aberto

- `e2e/textos-nucleo.spec.ts:96-128`: reescreve a resposta da busca com `open_projects: 1` (mesmo padrão do teste de N = 2) e afirma `O cliente ${name} tem 1 projeto aberto; ele continua. Desativar?` com `exact: true` (`:121-123`). É o texto da spec (`spec.md:115`, Clientes AC 9, N = 1) e do F1 §04 (`docs/funcional/f1-nucleo.md:94`).

### Sensor (rodada 8)

Na mesma worktree do e2e. Antes de mutar, os dois testes de texto do desativar (`-g "desativar cliente com"`) passaram sem mutação (3 passed com o setup). Cada mutante: `npx vite build`, o mesmo `-g`, depois `git checkout` do arquivo.

| # | Alvo | Mutação | Resultado | Quem matou |
|---|---|---|---|---|
| S8a | `resources/js/pages/admin/clients/index.tsx:35` | texto do singular trocado pelo plural: `tem 1 projetos abertos; eles continuam. Desativar?` | ✅ morto | `e2e/textos-nucleo.spec.ts:121-123` (`getByText(…, { exact: true })` não acha) |
| S8b | `resources/js/pages/admin/clients/index.tsx:35` | `if (open === 1)` → `if (open === -1)`: N = 1 cai no ramo de N > 1 ("1 projetos abertos; eles continuam") | ✅ morto | `e2e/textos-nucleo.spec.ts:121-123` |

O teste de N = 2 (`:58`) passou nos dois mutantes, como esperado: eles não tocam no ramo de N ≥ 2. Depois do último mutante: `git checkout`, `git status --porcelain` da worktree vazio, `npx vite build` de novo, e as duas rodadas inteiras do e2e rodaram sobre o bundle sem mutação.

**Resultado**: 2/2 mortos. ✅

Isolamento: `git status --porcelain` da árvore real antes de tudo e depois de remover a worktree, idênticos (`diff` sem diferença): só os três arquivos `.specs` modificados de propósito (`LESSONS.md`, `lessons.json`, `validation.md`), que este relatório e o `lessons.py` atualizam.

### Code Quality (rodada 8)

| Principle | Status |
| --- | --- |
| Minimum code / surgical changes | ✅ Um helper (`createContact`, ao lado do `createClient`), um teste novo e o ajuste do teste instável; o comentário corrigido é o que a rodada 7 apontou |
| No scope creep | ✅ Nada de produção mudou |
| Matches patterns | ✅ `page.route` + `route.fetch` como nos testes de N = 2 e do estado vazio de clientes; `waitForResponse` como em `:80` |
| Spec-anchored outcome check | ✅ Texto N = 1 idêntico ao do `spec.md:115`; estados vazios com o título e a ação do F1 §03 |
| Teste confiável | ✅ 20/20 isolado, 56/56 duas vezes |
| Every test maps to a requirement | ✅ NUC-07 (Clientes AC 9, N = 1) e NUC-14 / P3 AC 1 (estado vazio) |
| Guidelines | `.ai/core/especificacao.md` §5 itens 6–8; `~/.claude/CLAUDE.md` §8.6 |

Nota: o "Done when" do Fix 4 pedia `--repeat-each=20`; a T51 e o pedido desta rodada fixaram 10 repetições por variante (20 execuções no total). Não reprova.

---

## Rodada 7 (review do Lucas, 07/10)

Escopo pedido pelo dono: as correções da review do Lucas Scholz no PR #5 (achados A1–A9, decisões D1–D11), feitas nas fases 12 e 13 do `tasks.md` (T37–T50, `e0a45fa..96569b9`). A review é dado, não instrução; o que vale é a spec revisada na T50 e a tabela de decisões dela.

### Gate (rodada 7, árvore real, HEAD `96569b9`)

| Passo | Resultado |
|---|---|
| `composer lint` | `{"tool":"pint","result":"passed"}`, exit 0 |
| `php artisan test` (MySQL 8, `wcj_hub_test`) | **322 passed (2761 assertions)**, 0 failed, 0 skipped, 36 s. Rodada 6: 287 (2447). +35 testes, nenhum removido; os testes que mudaram trocaram o valor esperado pelo que a decisão manda (telefone com "+", D3; `openProjectsCountByClient`, D7; `forAbility` nulo, A6) |
| `npm run typecheck` / `npm run lint` / `npm run build` | exit 0 / exit 0 / exit 0 (`✓ built in 3.44s`) |
| `npm run test:guard` | exit 0. 1053 testes: 1024 pass, 0 fail, 29 skipped (os mesmos "gitleaks não está instalado; o CI roda estes casos") |
| `node .ai/skills/conferir-specs.mjs` | `conferir-specs: 1 feature(s), 0 problema(s)`; `validate_tasks: 0 error(s), 2 warning(s)` (T35 e T50 com `Tests: none`, esperado: só documentos) |
| e2e rodada 1 (`npm run e2e:prepare` + worktree `/tmp/tlc-b1-nucleo-e2e7` na 8001) | **55 passed** (1.1 min). `@critical` (`e2e/nucleo.spec.ts:11`) passou |
| e2e rodada 2 (`npm run e2e:prepare` de novo, mesmo servidor) | **54 passed, 1 failed**: `e2e/textos-nucleo.spec.ts:221` (variante Contatos), `Response has been disposed` em `:232`. `@critical` passou |
| repetição do teste que falhou (`-g "lista vazia sem filtro" --repeat-each=10`) | 20 passed, **1 failed** (a variante Contatos, repetição 4, mesmo erro) |

Montagem do e2e, sem tocar no servidor do usuário: worktree descartável do HEAD em `/tmp/tlc-b1-nucleo-e2e7`, `vendor` e `node_modules` por `ln -s`, cópias de `.env.e2e` e `public/build`, sem `public/hot`, `npx vite build` lá; `APP_ENV=e2e php artisan serve --port=8001` desse worktree; `E2E_BASE_URL=http://127.0.0.1:8001 npx playwright test` da árvore real. No fim, o PID da 8001 (1625391) achado por `ss -ltnp` e morto sozinho; worktree removido. A porta 8000 (PID 88857) e o `public/hot` da árvore real ficaram intactos.

### Achados A1–A9 (o comportamento que a review pede)

| Achado | O que a review pede | Código | Teste (valor afirmado) | Mutante | Resultado |
|---|---|---|---|---|---|
| A1 | Não-admin com payload inválido recebe 403 (não 422), sem revelar e-mail, e a tentativa vira `denied` | `app/Platform/Requests/Concerns/AuthorizesBeforeValidation.php:18-22` `authorize(): Response` → `inspectAndAuditDenial($ability, $arguments)`, usado nos 4 FormRequests (`StoreUserRequest`, `StoreClientRequest`, `StoreContactRequest`, `AnonymizeContactRequest`); os `store`/`update`/`anonymize` dos controllers não chamam mais `authorize()` | `tests/Feature/Nucleo/AutorizaAntesDeValidarTest.php:45-53` `assertForbidden()`, `assertSessionHasNoErrors()`, `AuditLog::query()->sole()` com `action`, `outcome = denied`, `auditable_type`, `auditable_id`; chamado em `:65,79,94,107,119,131,143` (usuário criar/editar, cliente criar/editar, contato criar/editar, anonimizar; gestor e dev); `:66,80` `assertDontSee(UserService::EMAIL_TAKEN)` | M1a, M1b mortos | ✅ |
| A1 (negação uma vez só) | A negação não é gravada duas vezes numa requisição | Quando o FormRequest nega, o controller não roda; não há segundo `authorize()` nas ações com FormRequest (`grep -n "authorize(" app/Platform/Controllers/Admin/*.php`: só `viewAny`, `view`, `deactivate`, `reactivate`, `delete`, `resendInvite`) | `AutorizaAntesDeValidarTest.php:48` `AuditLog::query()->sole()` (exatamente uma linha) | M1c equivalente (ver Sensor) | ✅ |
| A2 | A WCJ não troca CNPJ nem país; o nome continua editável | `app/Platform/Services/ClientService.php:85` `if ($client->isWcj() && ($clean['country'] !== $client->country \|\| $clean['document'] !== $client->document)) throw new DomainException(self::WCJ_IDENTITY_LOCKED)` | `tests/Feature/Nucleo/ClientServiceTest.php` (`test_wcj_nao_troca_de_cnpj_nem_de_pais…`, 3 casos, admin e gestor que gere) mensagem `'O CNPJ e o país da WCJ não podem ser alterados'`, `isWcj()` true, 0 linhas de auditoria; `test_wcj_continua_trocando_de_nome` (`updated` com o nome); `ClientControllerTest.php:211` `assertSessionHas('error', 'O CNPJ e o país da WCJ não podem ser alterados')` | M2 morto | ✅ |
| A3 | Checagem do último admin dentro da transação, com `lockForUpdate`, e teste de concorrência | `app/Platform/Services/UserService.php:89,121,148` (`ensureNotLastActiveAdmin` dentro dos `DB::transaction` de `update`, `deactivate`, `delete`); `:219` `->lockForUpdate()->pluck('id')` | `tests/Feature/Nucleo/UltimoAdminConcorrenciaTest.php:55-59` (com `tests/Support/Concorrencia.php`) admins ativos `=== [$ana->id]` e `$corrida->bloqueada` true | M3a (sem lock), M3b (checagem fora da transação) mortos | ✅ |
| A4 | Checagem de anonimizado dentro da transação, sobre a linha travada | `app/Platform/Services/ContactService.php:87,114,147` `lockedNotAnonymized()` dentro da transação de `update`, `reactivate`, `anonymize`; `:265` `lockForUpdate()->findOrFail` | `tests/Feature/Nucleo/ContactAnonimizadoConcorrenciaTest.php:83-84` (edição) e `:104-105` (reativação): `bloqueada` true; se anonimizado, nada de "Ana Lima"/`padaria.test` em `changes` | M4 (update), M4b (reactivate) mortos | ✅ |
| A5 | O `@critical` não falha por corrida com a busca | `e2e/pages/admin.page.ts:26-36` `search()` espera a resposta com `search=` e a URL; `e2e/pages/clients.page.ts:24-27` `find()` usa; `edit()`/`openSheet()` passam por `find()` | `@critical` (`e2e/nucleo.spec.ts:11`) passou nas duas rodadas, com o banco recriado antes de cada | - | ✅ (o `@critical`). A suíte da rodada 2 caiu por outro teste: gap 1 |
| A6 | Ability sem mapeamento: 403 e `denied` com o nome, não 500 | `app/Platform/Enums/AuditActionEnum.php:55` `default => null`; `:34` `labelOf()` → `'Outra ação'`; `app/Platform/Services/AuditService.php:60` `mb_substr(forAbility($ability)?->value ?? $ability, 0, 40)` | `tests/Feature/Nucleo/AbilitySemMapeamentoTest.php:37` `assertForbidden()`, `:41` `assertSame('archive', $log->action)`; `:59` `action_label = 'Outra ação'`; `tests/Unit/AuditActionEnumTest.php` `assertNull(forAbility('archive'))` | M5 morto | ✅ |
| A7 | Sem N+1: quem gere o quê uma vez por requisição; projetos abertos em lote | `app/Platform/Services/MemoizedClientManagement.php:29` `??=`; `app/Providers/AppServiceProvider.php:63` `scoped(…)`, `:72` `forgetInstance` no `RequestHandled`; `app/Platform/Controllers/Admin/ClientController.php:114` uma chamada `openProjectsCountByClient($ids)`, `:136` lê do mapa | `tests/Feature/Nucleo/ClientManagementPorRequisicaoTest.php:56` admin, 20 clientes: `openProjectsCountByClient` = 1; `:67` gestor: `managedClientIds` = 1, `manages` = 0; `:84` contatos idem; `:98` a requisição seguinte pergunta de novo (2) | M6a (contrato por linha), M6b (sem memória) mortos | ✅ |
| A8 | `from` inválido mantém os 7 dias; só o vazio é "sem período" | `app/Platform/Services/AuditLogQuery.php:66` `$noPeriod = $id !== null \|\| (array_key_exists('from', $input) && in_array($input['from'], [null, ''], true))` | `tests/Feature/Nucleo/AuditLogQueryTest.php:91-92` `filters(['from' => '07/10/2026'])['from'] === '2026-09-30'` e só o log de dentro; `AuditLogControllerTest.php:109-119` idem pela rota, e `from=` → `null` com os 2 logs | M7 morto | ✅ |
| A9 | `attempt()` num lugar só | `app/Http/Controllers/Controller.php:36` `protected function attempt(…)`; `grep -rn "function attempt" app/` só acha esse | suíte inteira verde com as mesmas mensagens (`UserControllerTest.php:190`, `ClientControllerTest.php:224`, `ContactControllerTest.php:246`) | - (refactor) | ✅ |

### Decisões com efeito no código

| Decisão | O que a decisão manda | Código | Teste | Mutante | Resultado |
|---|---|---|---|---|---|
| D2 | Não-admin: "Já existe um contato com este e-mail"; admin: "Já existe Fulano (Cliente X) com este e-mail" | `app/Platform/Services/ContactService.php:29` `DUPLICATE_EMAIL_HIDDEN`; `:254` `$viewer->isAdmin() =>` texto com nome e cliente; `default` o oculto | `ContactServiceTest.php:204,210` (gestor, criar e editar) `'Já existe um contato com este e-mail'`; `ContactControllerTest.php:194` `flash.warning` idem + `assertDontSee('Bia Reis')`, `assertDontSee('Mercado Central')`; admin: `ContactControllerTest.php:166,179`, `ContactServiceTest.php:157,186` | M8 morto | ✅ |
| D3 | Telefone é texto até 20, gravado como digitado (`trim`), com o "+"; busca por dígitos | `StoreContactRequest` sem `normalizeDigits`; `ContactService.php` `validated()` só `trim`; `:186` `orWhereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') LIKE ?", ["%{$digits}%"])` (**parametrizado**: a entrada vai como binding, nada concatenado no SQL; nome e e-mail também via `where(…, 'like', $valor)`); tela sem `displayPhone`/`maskPhone` (`contact-table.tsx`, `contact-actions.tsx`), `maxLength={20}` | `ContactControllerTest.php:122` `phone = '+31 6 1234 5678'` (enviado com espaços nas pontas), `:123-126` busca `3161234` acha e mostra `'+31 6 1234 5678'`; `:224` edição grava com o "+"; `ContactServiceTest.php` `test_telefone_e_gravado_como_texto…` | M9 (só dígitos), M10 (busca sem tirar a máscara do banco) mortos | ✅ |
| D7 | 3º método aprovado, em lote, uma chamada por página; textos N = 0 e N = 1 como estão | `app/Platform/Contracts/ClientManagement.php` `openProjectsCountByClient(array $clientIds): array`; `NoClientManagement` `array_fill_keys($clientIds, 0)`; `index.tsx:34-36` os três textos | método em lote: `ClientManagementPorRequisicaoTest.php:56`; `ClientManagementTest.php` vazia devolve `[id => 0, …]`; N = 0: `e2e/textos-nucleo.spec.ts:49` `exact`; N = 2: `:86-88` `exact`; **N = 1: sem asserção** | mutação front no texto N = 0 morta (`textos-nucleo.spec.ts`, "sem projetos abertos") | ⚠️ método ✅; texto N = 1 ❌ (gap 2) |
| D9 | Carga diária aceita 7,3 (sem `step 0.25`), `min` fica | `resources/js/components/users/user-form-dialog.tsx:90` `step="any"`, `min="0.25"` | `e2e/ajustes-de-tela.spec.ts:39-47` convida com `7.3` e afirma `'7,3 h'` com `exact` | mutação front `step="0.25"` morta (o diálogo não fecha) | ✅ |

### ACs reescritos pela T50

| AC | Valor da spec | `arquivo:linha` + asserção | Resultado |
|---|---|---|---|
| Usuários AC 12 | toast de erro "Usuário já usado: desative em vez de excluir", nada muda | `UserControllerTest.php:190` `assertSessionHas('error', 'Usuário já usado: desative em vez de excluir')` | ✅ |
| Clientes AC 9 | três textos (N ≥ 2, N = 1, N = 0) e N pelo método em lote, uma chamada por página | N ≥ 2: `textos-nucleo.spec.ts:86-88`; N = 0: `:49`; lote: `ClientManagementPorRequisicaoTest.php:56`; N = 1: nenhuma | ❌ GAP (N = 1) |
| Clientes AC 10 | 403 "A WCJ não pode ser desativada"; trocar país/documento da WCJ recusa com "O CNPJ e o país da WCJ não podem ser alterados" e nada muda | `ClientControllerTest.php:198` `assertSee('A WCJ não pode ser desativada')`; `:211` `assertSessionHas('error', 'O CNPJ e o país da WCJ não podem ser alterados')` + documento igual e 0 auditoria; `ClientServiceTest.php` (3 casos, admin e gestor) | ✅ |
| Clientes AC 12 | toast "Cliente já usado: desative em vez de excluir", nada muda | `ClientControllerTest.php:224` | ✅ |
| Contatos AC 3 | admin: "Já existe Fulano (Cliente X) com este e-mail"; não-admin: "Já existe um contato com este e-mail"; salva nos dois | `ContactControllerTest.php:166,179` (admin), `:194` (gestor) + `count() === 2`; `ContactServiceTest.php:204,210` | ✅ |
| Contatos AC 9 | toast "Contato já usado: desative em vez de excluir" | `ContactControllerTest.php:246` | ✅ |
| Auditoria AC 2 | `denied` mesmo com payload inválido, 403 e nunca o erro de validação; ability sem rótulo grava o nome e aparece "Outra ação" | `AutorizaAntesDeValidarTest.php:45-53` (7 rotas × papéis); `AbilitySemMapeamentoTest.php:41,59` | ✅ |

Os textos que a review disse sem teste agora têm: "Fulano perde o acesso imediatamente. Continuar?" (`textos-nucleo.spec.ts:197`), "As conexões de IA de Fulano serão desconectadas. Continuar?" (`:206`), "Excluir de verdade. Não dá para desfazer." (`e2e/pages/users.page.ts:47`, `clients.page.ts:95`); limites 120/160 do usuário em `UserControllerTest.php:143,145`.

### Outras conferências

- **`SPEC_DEVIATION`**: `grep -rn SPEC_DEVIATION app resources routes database tests e2e` vazio. Os três `destroy` citam a D6 no comentário.
- **Documentos x decisões**: D1 em `docs/funcional/f1-nucleo.md` §04 ("gestor, pelo botão *Novo cliente* do formulário do projeto (F2) e em Administração → Clientes") e §09 ("cria pelo formulário do projeto e em Administração → Clientes"), e em `docs/necessidade.md` N6; D4 em `docs/escopo.md` B1 (`/admin/users`, `/admin/clients`, `/admin/contacts`, `/admin/audit-logs`); D5 em `docs/escopo.md` §4 (`view_clients`, `view_contacts`, `view_audit_logs`; `delete_records` e `reset_passwords` só admin); D11 em `docs/necessidade.md` N12 ("Limitação registrada (Lucas Scholz, 07/10): contato **excluído** … não há caminho para anonimizá-lo depois"). F1 virou versão 1.1 (08/10). ✅
- **Comentário impreciso (não é gap)**: `AuthorizesBeforeValidation.php` diz que, se o controller autorizasse de novo, "a negação seria gravada duas vezes". Não seria: quando o FormRequest nega, o controller não roda (M1c prova). O que o segundo `authorize()` faria é só redundância.
- **Nota de produto da review (A5)**: o usuário que digita na busca e clica numa ação em menos de 300 ms ainda pode perder a gravação; a T48 corrigiu o teste, não o `SearchInput`. A review só registrou; fica como observação.

### Sensor (rodada 7)

Worktree `/tmp/tlc-b1-nucleo-s7` do HEAD, com **`vendor` copiado** (`cp -a`, não `ln -s`) e `.env`/`.env.testing` copiados. `ReflectionClass::getFileName()` confirmou que `UserService`, `ContactService`, `ClientService`, `AuthorizesBeforeValidation`, `AuditActionEnum`, `AuditLogQuery` e `ClientController` carregam de `/tmp/tlc-b1-nucleo-s7/app/...`. Suíte sem mutação ali: **322 passed (2761 assertions)**. Cada mutante rodou a suíte PHP inteira e foi desfeito com `git checkout -- app` antes do próximo. Os dois mutantes de tela rodaram no worktree do e2e, com `vite build` e o spec da tela, e foram desfeitos antes de encerrar o servidor.

| # | Alvo | Mutação | Resultado | Quem matou |
|---|---|---|---|---|
| M1a | A1 | `AuthorizesBeforeValidation::authorize()` → `Response::allow()` (o `return true` de antes) | ✅ morto (24 falhas) | `AutorizaAntesDeValidarTest`, testes de 403 dos controllers |
| M1b | A1 | valida antes de autorizar: `authorize()` libera e a checagem vai para `passedValidation()` | ✅ morto (13 falhas) | `AutorizaAntesDeValidarTest` (422 em vez de 403, e-mail revelado) |
| M1c | A1 (duas vezes) | `ContactController::update` volta a chamar `$this->authorize('update', $contact)` | ⚪ **equivalente**: 322 passed. Com o FormRequest negando, o controller não roda; com ele permitindo, o segundo `authorize()` também permite e não grava nada. Não há como gravar a negação duas vezes por esse caminho | - |
| M2 | A2 | `if ($client->isWcj() && …)` → `if (false && …)` | ✅ morto (4) | `ClientServiceTest` (3 casos), `ClientControllerTest:211` |
| M3a | A3 | tira o `lockForUpdate()` da checagem do último admin | ✅ morto | `UltimoAdminConcorrenciaTest` |
| M3b | A3 | `deactivate`: checagem antes do `DB::transaction` | ✅ morto | `UltimoAdminConcorrenciaTest` |
| M4 | A4 | `update`: `lockedNotAnonymized` antes da transação, escrita no modelo sem trava | ✅ morto | `ContactAnonimizadoConcorrenciaTest` (edição) |
| M4b | A4 | `reactivate`: idem | ✅ morto | `ContactAnonimizadoConcorrenciaTest` (reativação) |
| M5 | A6 | `forAbility` volta a `throw InvalidArgumentException` | ✅ morto (3) | `AbilitySemMapeamentoTest` (2), `AuditActionEnumTest` |
| M6a | A7 | `ClientController::row` consulta o contrato por linha | ✅ morto | `ClientManagementPorRequisicaoTest` (admin, 20 linhas) |
| M6b | A7 | `MemoizedClientManagement` sem `??=` | ✅ morto (3) | `ClientManagementPorRequisicaoTest` |
| M7 | A8 | `$noPeriod` volta a `array_key_exists('from', $input)` | ✅ morto (2) | `AuditLogQueryTest`, `AuditLogControllerTest` |
| M8 | D2 | `$viewer->isAdmin()` → `true` (gestor vê nome e cliente) | ✅ morto (2) | `ContactControllerTest` (gestor), `ContactServiceTest` (gestor) |
| M9 | D3 | `validated()` grava só os dígitos do telefone | ✅ morto (4) | `ContactControllerTest` (3), `ContactServiceTest` |
| M10 | D3 | busca por telefone volta a `orWhere('phone', 'like', …)` sem tirar a máscara do banco | ✅ morto | `ContactControllerTest` (`search=3161234`) |
| F1 | D9 | `step="any"` → `step="0.25"` (front, e2e) | ✅ morto | `e2e/ajustes-de-tela.spec.ts:39` (diálogo não fecha) |
| F2 | D7 | texto N = 0 trocado (front, e2e) | ✅ morto | `e2e/textos-nucleo.spec.ts:37` |

Não rodado, por não haver asserção nenhuma que pudesse matá-lo: o texto N = 1 (`index.tsx:35`). Ele sobreviveria; é o gap 2.

**Sensor depth**: P0 (permissão e auditoria: A1, A3, A4, A6) com mutação à mão, 15 no PHP + 2 na tela. **Resultado**: 16 mortos, 0 sobrevivente real, 1 equivalente (M1c). ✅

Isolamento: `git status --porcelain` da árvore real vazio antes do sensor e depois de remover os dois worktrees (`diff` dos dois registros sem diferença); `git worktree list` só com a árvore real.

### Code Quality (rodada 7)

| Principle | Status |
| --- | --- |
| Minimum code / surgical changes | ✅ Cada tarefa toca o arquivo do achado; o trait `AuditsDeniedAbilities` tira a duplicação entre o Controller base e os FormRequests em vez de copiar |
| No scope creep | ✅ O `MemoizedClientManagement` é o "uma vez por requisição" que a A7 pediu; o `labelOf` é o rótulo genérico que a T41 pede |
| Matches patterns | ✅ Concorrência com `tests/Support/Concorrencia.php` (pedido da review e do `AGENTS.md`); dublê `ClientManagementFalso` atrás da mesma memória da real |
| Spec-anchored outcome check | ⚠️ Os valores batem com a spec, menos o texto N = 1 do Clientes AC 9 (sem asserção) |
| Per-layer coverage | ✅ Service + rota + concorrência no banco de verdade; tela no e2e |
| Every test maps to a requirement | ✅ Os testes novos citam o achado ou a decisão no comentário (T37–T49) |
| Teste confiável | ❌ `textos-nucleo.spec.ts:221` (variante Contatos) falha 1 em 10 (gap 1) |
| Guidelines | `.ai/core/especificacao.md` §5 itens 6–8, `AGENTS.md` (concorrência onde há "um só"), `~/.claude/CLAUDE.md` §8.6 (teste instável é pior que nenhum) |

### Fix 4 (rodada 7): estado vazio de Contatos no e2e, Minor, só teste. ✅ Feito na T51 (`445f5d2`), conferido na rodada 8

- **Root cause**: `e2e/textos-nucleo.spec.ts:221-242` afirma o estado vazio logo depois do clique em *Mostrar desativados*, sem esperar a resposta reescrita. No banco e2e sem contatos, a tela já está vazia antes; a asserção passa, o teste acaba e o `route.fetch()` em voo é descartado (`Response has been disposed`, `:232`).
- **Fix task**: guardar `page.waitForResponse` da visita com `show_inactive=1` antes do clique e esperar por ela (e/ou esperar "Carregando contatos…" sumir) antes do `expect`; para Contatos, provar que a lista tinha linha antes do clique ou criar um contato no começo, para o teste discriminar.
- **Done when**: `--repeat-each=20` do teste sem falha; suíte inteira verde duas vezes com `e2e:prepare` antes de cada.

### Fix 5 (rodada 7): texto N = 1 do Clientes AC 9, Minor, só teste. ✅ Feito na T51 (`445f5d2`), conferido na rodada 8

- **Root cause**: a T50 trouxe para a spec o texto "O cliente X tem 1 projeto aberto; ele continua. Desativar?", mas o e2e só reescreve a resposta com `open_projects = 2` (`textos-nucleo.spec.ts:58`).
- **Fix task**: no mesmo padrão do teste de `:58`, reescrever com `open_projects: 1` e afirmar o texto com `exact: true`.
- **Done when**: a troca do texto de `index.tsx:35` (ou `open === 1` → `open === 0`) faz o teste falhar numa worktree descartável.

### Saída dos validadores (rodada 7)

Ver a seção "Saída dos validadores (rodada 7)" no fim do arquivo.

---

## Rodada 6 (focada, autorizada pelo dono)

Escopo pedido pelo dono: só o gap da rodada 5, o texto do Clientes AC 9 com N projetos ("O cliente X tem N projetos abertos; eles continuam. Desativar?") e o mutante T11, que a T36 (`6beb785`) diz ter fechado. Lidos: `.claude/skills/tlc-spec-driven/references/validate.md`, `.ai/core/especificacao.md` §5 itens 6–8 e o `git diff d014767..HEAD`.

### O diff só toca teste e tarefas

```
$ git diff d014767..HEAD --stat
 .specs/features/b1-nucleo/tasks.md | 39 +++++++++++++++++++++++++++++++++++++-
 e2e/textos-nucleo.spec.ts          | 38 +++++++++++++++++++++++++++++++++++++
 2 files changed, 76 insertions(+), 1 deletion(-)
```

Nenhum arquivo de `app/`, `resources/`, `routes/`, `database/` ou `tests/` mudou. As citações e os mutantes T01–T10 da rodada 5 continuam valendo sem refazer. `git status --porcelain` antes de começar: só `.specs/LESSONS.md`, `.specs/features/b1-nucleo/validation.md` e `.specs/lessons.json` modificados (de propósito, sem commit).

### Gate (rodada 6)

| Passo | Onde | Resultado |
|---|---|---|
| `composer lint` | árvore real | `{"tool":"pint","result":"passed"}`, exit 0 |
| `php artisan test` | árvore real | **287 passed (2447 assertions)**, 0 failed (22,0 s); igual à rodada 5, como esperado (nenhum PHP mudou) |
| `npx playwright test` | árvore real, `E2E_BASE_URL=http://127.0.0.1:8001` | **49 passed** (1,0 min), exit 0. Rodada 5: 48; +1 é o teste da T36 (`textos-nucleo.spec.ts:55`) |

Como o e2e rodou: `npm run e2e:prepare` recriou `wcj_hub_e2e`; worktree descartável `/tmp/tlc-b1-nucleo-s6` (`git worktree add --detach … HEAD`, no `6beb785`) com `vendor` e `node_modules` por `ln -s`, cópia do `.env.e2e` (sem ler) e do `public/build`, sem `public/hot`; `npx vite build` dentro da worktree antes do baseline, para o bundle servido ser o do HEAD (`app-D5H-ikqo.js`, conferido no HTML de `/admin/login`); `APP_ENV=e2e php artisan serve --host=127.0.0.1 --port=8001` dali, em segundo plano. Sem mutação, a suíte inteira passou ali (49/49) antes de qualquer mutante (§5 item 8).

### Spec-anchored (Clientes AC 9, o texto)

| # | Spec-defined outcome | `file:line` + assertion | Result |
|---|---|---|---|
| Clientes AC 9: o texto | "O cliente X tem N projetos abertos; eles continuam. Desativar?" | `e2e/textos-nucleo.spec.ts:62-73` `page.route` reescreve a resposta da busca com `open_projects: 2` (`:68`); `:77-79` espera a resposta da busca; `:82-86` `expect(clients.admin.dialog.getByText(\`O cliente ${name} tem 2 projetos abertos; eles continuam. Desativar?\`, { exact: true })).toBeVisible()` | ✅ (T11, T11b, T11c mortos) |
| Clientes AC 9: o N | N = projetos abertos do cliente | sem mudança desde a rodada 5: `ClientControllerTest.php:110` `open_projects = 2` pelo dublê, `:121` `0` com a vazia | ✅ (T07, T10 mortos na rodada 5). 🔶 desvio declarado: `openProjectsCount` |

**Limite da prova (não é gap)**: o teste e2e injeta o N na resposta, então ele prova a tela (o texto montado a partir de `open_projects`), não o caminho servidor → tela com N > 0. A outra metade, servidor → prop, está em `ClientControllerTest.php:110`. Com a `NoClientManagement` ligada até o B2, não há outro jeito de o servidor do e2e mandar N > 0 sem dublê no processo do `artisan serve`. Se a tela passar a ler outro campo que não `open_projects`, o valor injetado é ignorado, o texto cai no de N = 0 e o teste falha; se só o Controller mudar o nome da prop, quem pega é o `ClientControllerTest.php:110`.

**Observação (não é gap)**: o docblock de `e2e/textos-nucleo.spec.ts:9-10` ainda diz "a contagem N fica no ClientControllerTest", o que deixou de ser toda a verdade depois da T36. Cosmético.

**Precision gap (continua, pendente do Lucas)**: a spec só define o texto com N. As variantes "sai das listas de escolha" (N = 0, `index.tsx:35`, afirmada em `textos-nucleo.spec.ts:46-47`) e "tem 1 projeto aberto; ele continua" (N = 1, `index.tsx:36`, sem asserção) são escolha da implementação.

### Sensor (rodada 6)

Na mesma worktree `/tmp/tlc-b1-nucleo-s6`, com a 8001 servida dali. Cada mutante: troca no `resources/js/pages/admin/clients/index.tsx` (diff conferido, uma linha), `npx vite build` na worktree (bundle novo servido, ex.: `app-c8l6bwJM.js` no T11), `npx playwright test e2e/textos-nucleo.spec.ts` da árvore real com `E2E_BASE_URL=http://127.0.0.1:8001`, `git checkout --` do arquivo.

| # | File:line | Mutação | Killed? (teste que pegou) |
|---|---|---|---|
| T11 | `resources/js/pages/admin/clients/index.tsx:37` | "tem ${open} projetos abertos; eles continuam" → "tem ${open} projetos em aberto; eles continuam" | ✅ 1 failed, 7 passed: `textos-nucleo.spec.ts:55` "desativar cliente com projetos abertos mostra o texto da spec com a contagem", `toBeVisible()` falhou com "element(s) not found" |
| T11b | `index.tsx:36` | `if (open === 1)` → `if (open >= 1)`: N > 1 cai na variante de N = 1 ("tem 1 projeto aberto; ele continua") | ✅ 1 failed, 7 passed: o mesmo teste, em `textos-nucleo.spec.ts:86` |
| T11c | `index.tsx:37` | `${open}` → `${open + 1}` (contagem errada na frase) | ✅ 1 failed, 7 passed: o mesmo teste, em `textos-nucleo.spec.ts:86` |

**Resultado da rodada 6**: 3/3 mortas. Somando a rodada 5 (T01–T10, código sem mudança), 13/13 no escopo da fase 10 e do AC 9. ✅

**Isolamento (rodada 6)**: a 8001 foi encerrada pelo PID achado com `ss -ltnp` (858095, `php8.5 -S 127.0.0.1:8001 …/server.php`), sem `pkill`; `git worktree remove --force /tmp/tlc-b1-nucleo-s6` e `git worktree prune`; `git worktree list` só mostra a árvore real em `6beb785`; `/tmp/tlc-b1-nucleo-s6` não existe mais; `git status --porcelain` da árvore real comparado com o gravado antes do gate: `diff` vazio (os mesmos três `.specs` modificados de propósito). A porta 8000 (pid 88857, servidor do usuário) continuou de pé e o `public/hot` da árvore real não foi tocado.

### Code Quality (rodada 6)

| Principle | Status |
| --- | --- |
| Only touched files required for task | ✅ Só o spec e2e e o `tasks.md` |
| Matches patterns | ✅ Reaproveita o `page.route` + `route.fetch()` + `route.fulfill` do teste do estado vazio (`textos-nucleo.spec.ts:99`), `ClientsPage` e `createClient`/`deleteClient` com `finally` |
| Espera por condição, sem `waitForTimeout` | ✅ `waitForResponse` da busca (`:77`) e `toBeVisible()` |
| Spec-anchored outcome check | ✅ Texto exato da spec com `exact: true` |
| Every test maps to a requirement | ✅ Comentário em `:60-61` cita o Clientes AC 9; T36 aponta NUC-07 |

---

## Rodada 5 (focada na spec revisada de 07/10)

Escopo pedido pelo dono: a spec revisada por Lucas Scholz em 07/10 (`44fa4c9`: as Policies perguntam ao contrato `Platform\Contracts\ClientManagement` em vez de ler `projects`) e a fase 10 que adaptou o código (T32 `c49cd86`, T33 `d434e4e`, T34 `87d5c2f`, T35 `d014767`). Desvio declarado pelo dono: o 3º método do contrato, `openProjectsCount(Client)`, para o Clientes AC 9, pendente de aprovação do Lucas. Ele não reprova.

Lidos: `.claude/skills/tlc-spec-driven/references/validate.md`, `.ai/core/especificacao.md` §5 itens 6–8, a `spec.md` revisada (premissa do gestor, Out of Scope, Clientes AC 5–9 e 13, Contatos AC 4–6 e 13, NUC-06, NUC-08), `docs/adr/001-codigo-por-dominio.md:20-22`, `docs/escopo.md:79` (B1 Permissão) e `:91` (B2 Regras), e o `git diff 2a32842..HEAD`.

### Os merges não mudam o comportamento do B1

`git diff 2a32842..HEAD` fora da fase 10 traz, pelos merges `55cc6ab` (spec revisada) e `729aaa7` (`origin/main`: `4c25668`, `579df0a`, `19f20c1`, `ce9a1b5`):

- a migração aditiva e idempotente `database/migrations/2026_10_07_000010_add_slug_to_rework_reasons_table.php` (`slug` nulo e único, só o "Outro" com `other`), com `ReworkReason::OTHER` e o `ReworkReasonSeeder` gravando o slug. Tabela de Projetos, fora do B1;
- o `ExemploSeeder` com a etapa Paused em 4º (P10), dado de exemplo de Projetos;
- `tests/Feature/SementesEExemploTest.php` (+1 asserção do slug, a ordem das 8 etapas no lugar da contagem);
- documentos (`AGENTS.md`, `docs/adr/001-codigo-por-dominio.md`, `docs/banco.txt`, `docs/escopo.md`, `docs/necessidade.md`, `docs/funcional/f8-conexoes-de-ia.md`, `docs/00-COMECE-AQUI.md`, `docs/atas/2026-10-06-poc-mcp.md`) e a `spec.md`.

Nenhum arquivo de `app/Platform`, de rota, de tela ou de teste do Núcleo vem dos merges. O `ListService` citado no `4c25668` não existia nesta branch (o diff não o mostra).

### Gate (árvore real, HEAD `d014767`)

| Passo | Onde | Resultado |
|---|---|---|
| `composer lint` | árvore real | `{"tool":"pint","result":"passed"}`, exit 0 |
| `php artisan test` | árvore real | **287 passed (2447 assertions)**, 0 failed, 0 skipped (21,7 s). Inclui `Tests\Arch\ArquiteturaTest` "a9 dominio nao le tabela de outro" ✓ e `RotasAutorizadasTest` ✓ |
| `npm run typecheck` | árvore real | exit 0 |
| `npm run lint` | árvore real | exit 0 |
| `npm run build` | árvore real | exit 0 (`✓ built in 3.65s`); `public/hot` comparado byte a byte antes e depois: intacto |
| `npm run test:guard` | árvore real | 1053 testes: 1024 pass, 0 fail, 29 skipped (os mesmos "gitleaks não está instalado; o CI roda estes casos") |
| `npx playwright test` | árvore real, `E2E_BASE_URL=http://127.0.0.1:8001` | **48 passed** (57,3 s), exit 0 |

Como o e2e rodou: `npm run e2e:prepare` recriou `wcj_hub_e2e`; worktree descartável `/tmp/tlc-b1-nucleo-e2e5` do HEAD com `vendor` e `node_modules` por `ln -s`, cópia do `.env.e2e` (sem ler) e cópia do `public/build`; `APP_ENV=e2e php artisan serve --host=127.0.0.1 --port=8001` dali, em segundo plano. O log do servidor da 8001 registrou as requisições do Playwright. No fim, só o `php artisan serve` da 8001 e o filho dele foram encerrados e a worktree foi removida. A porta 8000 (pid 88857, servidor do usuário) e o `public/hot` não foram tocados.

**Integridade da contagem**: rodada 4, 284; rodada 5, 287 (+3). A fase 10 tirou os 7 testes de `tests/Feature/Nucleo/ClientProjectsTest.php` e acrescentou 10: 3 em `ClientManagementTest.php`, 2 em `ClientControllerTest.php` (`:115`, `:337`), 1 em `ClientPolicyTest.php` (`:91`), 1 em `ContactControllerTest.php` (`:368`), 1 em `ContactPolicyTest.php` (`:110`), 2 em `ContactServiceTest.php` (`:193`, `:210`). Dois testes foram reescritos com o dublê no lugar do projeto: `ClientPolicyTest.php:81` (era "gestor alocado como gestor no projeto edita o cliente") e `ContactPolicyTest.php:97` (era "gestor alocado como gestor cria e edita no cliente").

**Os 7 testes do `ClientProjectsTest` eram só da implementação real**, conferido no `git show 324a47b:tests/Feature/Nucleo/ClientProjectsTest.php`: todos chamavam `app(ClientProjects::class)`, que era a `ProjectClientProjects` (`app/Domains/Projetos/Services/`, apagada na T32). Um conferia a ligação no container (substituído por `ClientManagementTest.php:20-23`, agora com a vazia); cinco, as regras de quem gere (responsável, gestor alocado, dev alocado não gere, projeto de outro cliente, cliente sem repetição); um, a contagem de projetos abertos sem `finished_at`. Essas regras são do B2 pela spec revisada (Out of Scope, "a real é do B2"; `docs/escopo.md:91`). Saída justificada. Para o B2: os cinco casos e a contagem devem voltar no teste da `ProjectClientManagement`.

**Asserção enfraquecida (o gap desta rodada)**: em `e2e/textos-nucleo.spec.ts`, o teste "desativar cliente com projetos abertos pede a confirmação com o número de projetos" (no `2a32842`, `:44-46`: `getByText(\`O cliente ${name} tem 2 projetos abertos; eles continuam. Desativar?\`, { exact: true })`) virou `:34-53` "…sem projetos abertos, a variante sem contagem", que afirma `O cliente ${name} sai das listas de escolha. Desativar?` (`:46`). O texto com N, que é o do Clientes AC 9, não é afirmado em nenhum lugar: `grep -rn "projetos abertos; eles continuam" e2e tests resources` só acha `resources/js/pages/admin/clients/index.tsx:37`. O comentário do spec (`textos-nucleo.spec.ts:9-10`) diz que "a contagem N fica no ClientControllerTest", mas `ClientControllerTest.php:110` afirma a prop `open_projects` = 2, não o texto. O mutante T11 confirma: sobrevive.

### Spec-anchored (spec revisada)

| # | Spec-defined outcome | `file:line` + assertion | Result |
|---|---|---|---|
| Premissa do gestor: contrato | `manages(User $user, Client $client): bool` e `managedClientIds(User $user): array` em `Platform\Contracts\ClientManagement` | `app/Platform/Contracts/ClientManagement.php:17` `manages(User $user, Client $client): bool`, `:20` `managedClientIds(User $user): array`; `:27` `openProjectsCount(Client $client): int` | ✅ (+ desvio declarado: o 3º método) |
| Premissa: implementação vazia ligada | `NoClientManagement` (ninguém gere cliente algum), ligada no `AppServiceProvider` | `app/Providers/AppServiceProvider.php:60` `bind(ClientManagement::class, NoClientManagement::class)`; `tests/Feature/Nucleo/ClientManagementTest.php:22` `assertInstanceOf(NoClientManagement::class, app(ClientManagement::class))`; `:31` `assertFalse(manages)` e `:32` `assertSame([], managedClientIds)` para gestor e admin; `:38` `assertSame(0, openProjectsCount)` | ✅ (T05, T06, T10 mortos) |
| Premissa: dublê nos testes | "os testes das Policies usam um dublê do contrato" | `tests/Support/ClientManagementFalso.php:14` `implements ClientManagement`, `:22-28` `app()->instance(ClientManagement::class, $falso)`; usado em `ClientPolicyTest.php:64,84`, `ContactPolicyTest.php:77,100`, `ClientControllerTest.php:106,222,247,275,306`, `ContactControllerTest.php:115,270,285,317,347`, `ContactServiceTest.php:180,199`, `ClientServiceTest.php:205` | ✅ |
| Out of Scope: o Platform não lê `projects` | nada de `projects`/`project_members` nem de `App\Domains\` no Platform | `tests/Arch/ArquiteturaTest.php:123-151` (A9) verde na suíte; `grep -rn "App\\Domains" app/Platform` vazio; `grep -rni project app/Platform` só acha `openProjectsCount`, o alias `'project' => 'Projeto'` de `AuditLogPresenter.php:24` e comentários | ✅ |
| Out of Scope: nenhuma implementação real neste PR | a real (`ProjectClientManagement`) é do B2 | `grep -rn "implements ClientManagement" app tests`: só `app/Platform/Services/NoClientManagement.php:14` e o dublê `tests/Support/ClientManagementFalso.php:14`; `app/Domains/Projetos/Services/ProjectClientProjects.php` apagado no `c49cd86` | ✅ |
| Clientes AC 5 | gestor cria pelo Service, mesmas validações, auditoria com o `user_id` do gestor | `tests/Feature/Nucleo/ClientServiceTest.php:189-194` `assertAudited($client, 'created', […], $manager)`; `:196` `'Já existe o cliente Padaria Pão Quente com este CNPJ'`; `:197` `'Informe um CNPJ válido.'`; rota `ClientControllerTest.php:234-235` `assertDatabaseHas('audit_logs', ['user_id' => $manager->id, 'action' => 'created', 'outcome' => 'success'…])` | ✅ |
| Clientes AC 6 | gestor que o contrato lista edita, com as mesmas validações | `ClientPolicyTest.php:52` linha "gestor que gere o cliente" `[…, true, true, true, false]` → `:73` `assertSame($edita, $actor->can('update', $this->client))`; `:86` `assertTrue($manager->can('update', $this->client))`; `ClientControllerTest.php:228` `can.update = true`, `:237-239` PUT → `'Cliente atualizado.'` e `assertSame('Meu cliente editado', …)`; `ClientServiceTest.php:209-215` mesmas validações (`'Informe um CNPJ válido.'`, documento mantido) | ✅ (T01 morto) |
| Clientes AC 7 | gestor não listado, ou em desativar/reativar/excluir: **403** e nada muda; com a vazia, nenhum cliente | `ClientControllerTest.php:252-257` (editar cliente não listado, desativar, reativar, excluir), `:261` `assertForbidden()`, `:262` `outcome = denied` com `auditable_id`, `:265` `assertEquals($before, $this->snapshot())`, `:266` 0 `success`; vazia: `:346` `can = [false,false,false,false]`, `:347` `assertForbidden()`, `:349` `denied`, `:350` snapshot igual, `:351` 0 `success`; `ClientPolicyTest.php:87` `assertFalse(…can('update', outro))`, `:95` `assertFalse($manager->can('update', $this->client))` com a vazia | ✅ (T01, T05 mortos) |
| Clientes AC 8 | dev: **403** em qualquer rota de clientes | `ClientControllerTest.php:275` dev listado pelo dublê, `:278-284` as 7 rotas, `:291` `assertForbidden()`, `:292` `denied`, `:295` snapshot igual, `:296` 0 `success`; `ClientPolicyTest.php:55` "desenvolvedor listado pelo contrato" `[…, true, false, false, false]` | ✅ (T08 morto) |
| Clientes AC 9: desativa mesmo com projetos abertos e sai das listas | desativado, carimbo, fora da lista de escolha | `ClientServiceTest.php:222` projeto aberto no banco, `:227` `assertFalse($client->isActive())`, `:229` `deactivated_by`, `:231` `assertNotContains($client->id, …choiceList())` | ✅ (rodada 9: o método passou a receber o `User` pela D13; hoje `ClientServiceTest.php:233` `assertNotContains($client->id, array_column($this->service->choiceList($this->admin), 'id'))`) |
| Clientes AC 9: o N da confirmação | N = projetos abertos do cliente | `ClientControllerTest.php:106` dublê `projetosAbertos($client, 2)` → `:110` `where('clients.data.0.open_projects', 2)`; vazia: `:121` `open_projects = 0` | ✅ (T07, T10 mortos). 🔶 desvio declarado: o N vem de `openProjectsCount`, 3º método do contrato |
| Clientes AC 9: o texto | "O cliente X tem N projetos abertos; eles continuam. Desativar?" | **nenhuma**. `resources/js/pages/admin/clients/index.tsx:37` monta o texto, e nenhum teste o afirma. O e2e `textos-nucleo.spec.ts:46` afirma só a variante N = 0, "O cliente X sai das listas de escolha. Desativar?", que a spec não define | ❌ **GAP** (T11 sobrevive). Regressão da T34: o `2a32842` afirmava o texto exato |
| Clientes AC 13 | Nome, País, CNPJ / identificador, Situação; busca por nome e documento; filtros País e Situação (ativos por padrão); "Nenhum cliente ainda" com *Novo cliente* | `ClientControllerTest.php:53-79` colunas, desativado escondido e `show_inactive`; `:92-99` busca e país; e2e `textos-nucleo.spec.ts:72` `emptyState('Nenhum cliente ainda')`, `:74` botão *Novo cliente* | ✅ (sem mudança de comportamento; linhas deslocadas desde a rodada 4) |
| Contatos AC 4 | gestor listado cria e edita com as regras do Administrador | `ContactPolicyTest.php:65` linha "gestor que gere o cliente" → `:86` create, `:89` update; `:103-104` `assertTrue(create)`, `assertTrue(update)`; `ContactControllerTest.php:273` `'Contato Ana Lima criado.'`, `:275` `'Contato atualizado.'`, `:277` nome, `:278` 2 linhas `success` do gestor; `ContactServiceTest.php:185-186` auditado com o gestor, `:188-189` mesma validação | ✅ (T02, T03 mortos) |
| Contatos AC 5 | gestor não listado, ou em desativar/excluir/anonimizar: **403** e nada muda | `ContactControllerTest.php:294-299` (criar em cliente não listado, editar contato dele, desativar, reativar, excluir, anonimizar), `:304` `assertForbidden()`, `:305` `denied` com `auditable_id` (nulo no criar), `:308` snapshot igual, `:309` 0 `success`; vazia: `:377-378` `assertForbidden()`, `:380-381` `denied`, `:382` snapshot, `:383` 0 `success`; `ContactPolicyTest.php:105-106`, `:116-118` `assertFalse` | ✅ (T02, T03, T05 mortos) |
| Contatos AC 6 | dev: **403** nas rotas de contatos | `ContactControllerTest.php:317` dev listado pelo dublê, `:323-329` as 7 rotas, `:334` `assertForbidden()`, `:335` `denied`, `:338` snapshot igual; `ContactPolicyTest.php:68` "desenvolvedor listado pelo contrato" `[…, true, false, false, false]` | ✅ (T09 morto) |
| Contatos AC 13 | lista geral com Nome, E-mail, Telefone, Cliente, Situação; busca; filtros Cliente e Situação; para o gestor, só os clientes que `managedClientIds` devolve | `ContactControllerTest.php:57-94` colunas e situação; `:103-107` busca e filtro; gestor: `:121-125` só `'Ana Lima'` e `clients` só o dele, `:126-127`; `:130-131` gestor que o contrato não lista: `has('contacts.data', 0)`, `clients = []`; vazia `:375`; `ContactServiceTest.php:203-206` (`paginate` e `clientOptions` do gestor só o cliente listado; admin todos), `:216-218` (vazia: 0 e `[]`) | ✅ (T04, T06 mortos) |
| P2 AC 1 | gestor: só `id`, `name`, `role` dos ativos | `UserServiceTest.php:546-548` `assertSame(['id' => …, 'name' => 'Caio Dev', 'role' => 'developer'], …)`; `UserPolicyTest.php:48` `viewChoiceList` | ✅ (sem mudança desde a rodada 4) |
| P2 AC 2 | gestor: só `id` e `name` dos clientes ativos | `ClientServiceTest.php:319` `assertSame([['id' => $alfa->id, 'name' => 'Alfa'], ['id' => $zeta->id, 'name' => 'Zeta']], …choiceList())`; `ClientPolicyTest.php:26` `viewChoiceList` em `SEM_REGISTRO`, `:69-71` na matriz, `:97-99` com a vazia o gestor ainda escolhe | ✅ |
| P2 AC 3 | dev: **403** | `ClientPolicyTest.php:55,69-71` e `UserPolicyTest.php:48` (ability negada); não há rota (design D6) | ⚠️ desvio declarado, como na rodada 4. **Fechado em 09/10 (D13)**: o AC foi reescrito no nível da Policy, conferida dentro de `choiceList(User)`; ✅ na rodada 9 (`UserServiceTest.php:672-680`, `ClientServiceTest.php:443-452`, mutantes c1, c2, d mortos) |

**Status (rodada 5)**: 19 linhas conferidas. 17 ✅ (uma com o desvio declarado do `openProjectsCount`), 1 ⚠️ (P2 AC 3, desvio já pendente) e **1 ❌ GAP** (texto do Clientes AC 9). Precision gap leve, novo: a spec só define o texto com N; as variantes "sai das listas de escolha" (N = 0) e "tem 1 projeto aberto; ele continua" (N = 1) de `index.tsx:35-36` são escolha da implementação, para o Lucas confirmar.

Os critérios fora deste escopo (Usuários, Clientes AC 1–4, 10–12, 14, Contatos AC 1–3, 7–12, Auditoria, P3) não mudaram de código nem de teste desde a rodada 4 (`git diff 2a32842..HEAD` não toca `UserService`, `ClientService`, `AuditService`, as telas, nem os testes deles). As citações da tabela "Spec-Anchored Acceptance Criteria" abaixo continuam valendo para eles, com uma ressalva: em `ClientControllerTest.php`, `ClientPolicyTest.php` e `ContactPolicyTest.php` as linhas se deslocaram; para C5–C9, C13, K4–K6 e K13, valem as da tabela acima.

### Sensor (rodada 5)

Worktree descartável `/tmp/tlc-b1-nucleo-s5` (`git worktree add --detach … HEAD`, no `d014767`):

- `vendor` **copiado** com `cp -a` (diretório real: `test -d && ! -L` verdadeiro), sem `ln -s`; `node_modules` por `ln -s` (só para o build do T11); `.env.testing` versionado.
- Antes de qualquer mutante, `ReflectionClass::getFileName()` mostrou `ClientPolicy`, `ContactPolicy`, `ContactService`, `NoClientManagement`, `ClientController`, `Tests\Support\ClientManagementFalso` e `Tests\TestCase` carregando de `/tmp/tlc-b1-nucleo-s5/...`.
- Sem mutação, ali: **287 passed (2447 assertions)**.
- T01–T10: troca exata de texto (uma ocorrência só, conferida por script), `php -l` ok, `php artisan test` inteiro, `git checkout --` do arquivo; `git status --porcelain` da worktree vazio depois de cada reversão.
- T11 (front): troca no `index.tsx`, `npm run build` na worktree (o bundle mutado `index-cSQlrmXS.js` contém o texto novo), `npm run e2e:prepare`, `APP_ENV=e2e php artisan serve --port=8001` a partir da worktree (o log mostra o bundle mutado servido 24 vezes) e `npx playwright test` inteiro da árvore real com `E2E_BASE_URL=http://127.0.0.1:8001`. Depois, a 8001 foi encerrada e o arquivo revertido.

| # | File:line | Mutação | Killed? (testes que pegaram) |
|---|---|---|---|
| T01 | `app/Platform/Policies/ClientPolicy.php:41` | `update` sem perguntar ao contrato: `isAdmin() \|\| isProjectManager()` | ✅ 6 failed: `ClientControllerTest` (vazia, 403 fora do contrato, gestor vê cria e edita), `ClientPolicyTest` (vazia, só o listado, matriz) |
| T02 | `app/Platform/Policies/ContactPolicy.php:38` | `create` sem o contrato: todo gestor cria em qualquer cliente | ✅ 6 failed: `ContactControllerTest` (vazia, ficha, 403 fora do contrato), `ContactPolicyTest` (vazia, só o listado, matriz) |
| T03 | `ContactPolicy.php:43` | `update` sem o contrato: todo gestor edita qualquer contato | ✅ 5 failed: `ContactControllerTest` (vazia, 403 fora do contrato), `ContactPolicyTest` (vazia, só o listado, matriz) |
| T04 | `app/Platform/Services/ContactService.php:210` | `visibleClientIds` devolve `null`: o gestor vê contatos e filtro de todos os clientes | ✅ 4 failed: `ContactControllerTest` (vazia, gestor vê só os dele), `ContactServiceTest` (vazia, lista e filtro do gestor) |
| T05 | `app/Platform/Services/NoClientManagement.php:18` | `manages` devolve `true` | ✅ 5 failed: `ClientManagementTest` (ninguém gere), `ClientControllerTest`, `ClientPolicyTest`, `ContactControllerTest`, `ContactPolicyTest` (as "com a implementação vazia") |
| T06 | `NoClientManagement.php:23` | `managedClientIds` devolve os ids de todos os clientes | ✅ 3 failed: `ClientManagementTest` (ninguém gere), `ContactControllerTest` (vazia), `ContactServiceTest` (vazia) |
| T07 | `app/Platform/Controllers/Admin/ClientController.php:129` | `open_projects` fixo em `0`, sem chamar `openProjectsCount` | ✅ 1 failed: `ClientControllerTest` "lista traz os projetos abertos para a confirmação de desativar" (`:110`) |
| T08 | `ClientPolicy.php:41` | `update` sem conferir o papel: dev listado pelo contrato edita | ✅ 2 failed: `ClientControllerTest` "dev recebe 403 em toda rota", `ClientPolicyTest` matriz |
| T09 | `ContactPolicy.php:72` | `managesClient` sem conferir o papel: dev listado cria e edita contato | ✅ 2 failed: `ContactControllerTest` "dev recebe 403 em toda rota", `ContactPolicyTest` matriz |
| T10 | `NoClientManagement.php:28` | `openProjectsCount` devolve `1` | ✅ 2 failed: `ClientManagementTest` "nenhum cliente tem projeto aberto", `ClientControllerTest` "com a implementação vazia…" (`:121`) |
| T11 | `resources/js/pages/admin/clients/index.tsx:37` | texto com N: "tem ${open} projetos abertos; eles continuam. Desativar?" → "tem ${open} projetos em andamento. Desativar?" | ❌ **Sobreviveu**: PHP 287 passed (o PHP não lê o `.tsx`) e Playwright **48 passed** com o bundle mutado servido |

**Sensor depth**: P0 sem ferramenta (Infection não está instalado), 11 mutações à mão: 9 no caminho crítico de permissão e escopo do gestor (T01–T06, T08, T09 e a ligação vazia), 2 no AC 9 (T07, T10 no dado; T11 no texto).
**Resultado da rodada 5**: 10/11 mortas. Sobrevivente: T11. ❌

**Isolamento (rodada 5)**: `git status --porcelain` da árvore real gravado antes do gate (vazio) e comparado depois de remover as duas worktrees: `diff` vazio. `git worktree list` mostra só a árvore real; `/tmp/tlc-b1-nucleo-e2e5` e `/tmp/tlc-b1-nucleo-s5` não existem mais; HEAD em `d014767`; `public/hot` igual byte a byte à cópia feita antes; a porta 8000 (pid 88857) continua de pé e a 8001 está livre.

### Code Quality (rodada 5)

| Principle | Status |
| --- | --- |
| Minimum code / surgical changes | ✅ O contrato tem os 2 métodos da spec e o 3º declarado; a vazia é de 3 linhas úteis; as Policies trocaram só a dependência e o tipo do argumento (`Client` no lugar do id) |
| No scope creep | ✅ Nenhuma implementação real; `ProjectClientProjects` saiu |
| Matches patterns | ✅ ADR-001 `:22` (interface em `app/Platform/Contracts/`, vazia no Platform, ligação no `AppServiceProvider`); dublê em `tests/Support/`, como o `CriaProjeto` |
| Spec-anchored outcome check | ❌ Clientes AC 9: o texto da spec deixou de ser afirmado (T34) |
| Every test maps to a requirement | ✅ Os testes novos citam o AC no comentário (`ClientControllerTest.php:114,336`, `ClientPolicyTest.php:80,90`, `ContactControllerTest.php:367`, `ContactPolicyTest.php:96,109`, `ContactServiceTest.php:192,209`, `ClientManagementTest.php:12-15`) |
| Observação (não é gap) | `ClientServiceTest.php:205` liga o dublê, mas o `ClientService::update` não pergunta ao contrato (quem decide é a Policy, como diz o comentário de `:200`). Inofensivo |

### Fix 3 (rodada 5): o texto do Clientes AC 9, Minor, só teste

- **Root cause**: com a `NoClientManagement` ligada, o servidor do e2e sempre manda `open_projects = 0`, e a T34 trocou a asserção do texto com N pela variante zero. O `ClientControllerTest` prova o número na prop, não o texto que a tela monta.
- **Fix task**: em `e2e/textos-nucleo.spec.ts`, manter o teste da variante zero e acrescentar um com N, reescrevendo a resposta Inertia da lista como o próprio arquivo já faz no estado vazio (`textos-nucleo.spec.ts:61-69`, `route.fetch()` + `body.props.clients.data[i].open_projects = 2` + `route.fulfill`). Afirmar `getByText(\`O cliente ${name} tem 2 projetos abertos; eles continuam. Desativar?\`, { exact: true })`. Sem código de produção.
- **Done when**: o T11 (`index.tsx:37`, texto trocado), reaplicado numa worktree descartável com o build e a 8001 servidos dali, faz o e2e falhar; suíte e2e inteira verde na árvore real.

### Saída dos validadores e lições (rodada 5)

Ver as seções "Saída dos validadores (rodada 5)" e "Lições (`.specs/lessons.json`), rodada 5" no fim do relatório.

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

Linhas conferidas no HEAD `a610d47` (rodada 3); C1 e C2 reconferidas no `2a32842` (rodada 4). **Rodada 5**: C5–C9, C13, K4–K6, K13 e P2 foram reconferidos contra a spec revisada no `d014767`; para eles vale a tabela da seção "Rodada 5", que substitui as linhas abaixo. `ClientServiceTest.php:75-83` e `:89-94` continuam iguais. "e2e" quer dizer que o teste rodou nesta verificação.

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
| E3 | dev: **403** | `UserPolicyTest.php:48`, `ClientPolicyTest.php:25,68-70` (ability negada) | ⚠️ Spec-precision gap / desvio declarado: o design D6 não cria rota, então não há 403 HTTP; a Policy está testada. Pendente do Lucas. **Fechado em 09/10 (D13)**; ✅ na rodada 9: `AuthorizationException` e linha `denied` do dev (`UserServiceTest.php:672-680`, `ClientServiceTest.php:443-452`) |

### P3: Estados de tela e textos

| # | Spec-defined outcome | `file:line` + assertion | Result |
|---|---|---|---|
| T1 | em cada lista: vazio em uma frase com o botão de criar, erro e carregando | e2e `e2e/estados-das-listas.spec.ts:10-19`, `:22-39`, `:46-58`; textos em `e2e/pages/list.page.ts:80-117` | ✅ (Contatos oferece *Ir para Clientes*; Auditoria, *Limpar filtros*) |
| T2 | recusas e avisos em português | `MensagensEmPortuguesTest.php:31-75`; textos exatos em U4, C3, C10, K3, K9, K12 | ✅ |

**Status (rodada 4)**: ✅ **58/58** critérios com evidência `arquivo:linha`:

- **54** com o valor exato da spec (C1 e C2 deixaram de ser parciais);
- **3** desvios declarados, com "nada muda" testado (U12, C12, K9), pendentes do Lucas;
- **1** spec-precision gap (E3), pendente do Lucas (fechado em 09/10 pela D13; ✅ na rodada 9).

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
**Resultado da rodada 4**: 4/4 mortas. Somando as rodadas 1 a 4, nenhum sobrevivente em aberto naquela data. A rodada 5 tem a contagem própria (seção "Rodada 5").
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

## Declared Deviations

Em 09/10/2026 (ciclo 1 da `wcj-code-review`, decisões do Lucas Scholz, D12 a D16 do design): o P2 AC 3 deixou de ser desvio, porque a D6 (listas de escolha sem rota) foi aceita e o AC foi reescrito no nível da Policy, conferida dentro de `choiceList()` (CR-1-05); "a WCJ não pode ser excluída" entrou na spec (Clientes AC 10) e no Service (CR-1-04); a retirada do aviso "ao sair do campo" ficou registrada (D12, CR-1-06); a linha **Approved by** da spec passou a 2026-10-09, cobrindo a revisão de 08/10. Os ciclos seguintes estão em `code-reviews/`.

Na rodada 7, o que era desvio e o Lucas Scholz decidiu (review de 07/10) saiu desta tabela e virou regra na spec (T50): os três `destroy` com toast em vez de 403 (**D6**: Usuários AC 12, Clientes AC 12, Contatos AC 9 reescritos; `grep SPEC_DEVIATION` vazio); o 3º método do contrato, agora em lote, `openProjectsCountByClient` (**D7**); as variantes N = 0 e N = 1 do texto de desativar cliente (**D7**, textos como estão); a WCJ com CNPJ e país editáveis (virou recusa, achado **A2**).

### Decisões do Lucas Scholz (07/10) registradas

| # | Decisão | Onde está |
|---|---|---|
| D1 | Gestor cria cliente também em Administração → Clientes | F1 §04 e §09, `necessidade.md` N6, `escopo.md` §4 e B1, spec Clientes AC 5 |
| D2 | Aviso de e-mail repetido por papel | `ContactService.php:254`; spec Contatos AC 3; F1 §02 e §07 |
| D3 | Telefone é texto até 20, como digitado; busca por dígitos | `ContactService.php:186`; spec premissa "Telefone do contato"; F1 §02; `AGENTS.md` |
| D4 | Rotas em inglês | `escopo.md` B1 |
| D5 | Abilities `view_clients`, `view_contacts`, `view_audit_logs`; `delete_records` e `reset_passwords` só admin | `escopo.md` §4 |
| D6 | Excluir o já usado: toast de erro, não 403 | spec Usuários AC 12, Clientes AC 12, Contatos AC 9; comentários dos três `destroy` |
| D7 | 3º método do contrato aprovado, N+1 resolvido; textos N = 0 e N = 1 | `ClientManagement::openProjectsCountByClient`; spec Clientes AC 9 |
| D8 | Aviso de e-mail só entre contatos ativos | sem mudança |
| D9 | Carga diária sem `step 0.25` | `user-form-dialog.tsx:90` |
| D10 | Lista geral de contatos vazia com "Ir para Clientes" até o B2 | sem mudança |
| D11 | Contato excluído deixa dado pessoal no `changes` do `deleted`: limitação | `necessidade.md` N12 |

### Desvios que continuam (não decididos na review, não reprovam)

O P2 AC 3 saiu desta tabela em 09/10 (D13): a D6 foi aceita, o AC foi reescrito no nível da Policy e a rodada 9 o prova (`UserServiceTest.php:672-680`, `ClientServiceTest.php:443-452`). Até a rodada 8 a linha dizia: "P2 AC 3 (sem marcador no código) | 403 para o dev na lista de escolha | o design D6 não cria rota; a Policy nega | não se aplica".

| Ref | Spec | Implementado | "Nada muda" testado |
|---|---|---|---|
| Edge case do último admin | "a mensagem do template" | "Este é o último administrador ativo. Promova outra pessoa antes de desativar." (`UserServiceTest.php:462`) | `UserServiceTest.php:475-478` |
| Textos que a spec não define | U5, C2, K2, K11 | textos em português, afirmados por valor | - |

---

## Fix Plans

### Fix 6: repetição dentro de transação aberta sem teste (g2), Minor, só teste. ❌ Aberto na rodada 10

Detalhe e tarefa na seção "Rodada 10", "Fix 6 (rodada 10)": um caso no `UserDeadlockRepeteTest` que chama a escrita de usuário dentro de `DB::transaction(...)` com deadlock simulado e afirma 1 tentativa, a exceção e nada gravado; done when o mutante g2 morre.

### Fix 1: borda do dígito verificador do CNPJ (S10), Minor. ✅ Feito na T31 (`2a32842`), conferido na rodada 4

- **Root cause**: os CNPJs fixos de `tests/Unit/AlphanumericCnpjTest.php` só têm resto 3 a 10. A factory usa o mesmo `withCheckDigits()` que valida, então nenhum teste prova o ramo "resto < 2 → 0" contra um valor de fora.
- **Fix task**: em `AlphanumericCnpjTest::validos()`, acrescentar um CNPJ verdadeiro com resto 2 em um dos dígitos (ex.: `00000000000191`) e um com resto 0 ou 1 (dígito `0`). Em `invalidos()`, acrescentar o número com os dígitos que o mutante calcularia (`00000000000108`, que o `<= 2` gera a partir de `000000000001`). Só teste, sem código de produção.
- **Done when**: S10 (`AlphanumericCnpj.php:45`, `$remainder < 2` → `$remainder <= 2`) e S10b (`< 1`), reaplicados numa cópia descartável com `vendor` copiado, fazem um caso fixo falhar.

### Fix 2: decisões da spec (Lucas Scholz). Decididas em 07/10, exceto três

Decididos na review de 07/10 (D6, D7, A2; ver "Declared Deviations"): os três `destroy` com toast, a WCJ com CNPJ e país editáveis, o 3º método do contrato e as variantes N = 0 e N = 1. Continuavam abertos, sem reprovar: o P2 AC 3 (403 sem rota, por causa do D6 do design), o texto do último admin ao se desativar e os textos que a spec não define (U5, C2, K2, K11). Em 09/10 o P2 AC 3 foi decidido (D13) e está provado na rodada 9; seguem abertos o texto do último admin e os textos U5, C2, K2, K11.

### Fix 3: texto do Clientes AC 9 com N projetos (T11), Minor. ✅ Feito na T36 (`6beb785`), conferido na rodada 6

Descrito na seção "Rodada 5" ("Fix 3"). Só teste, em `e2e/textos-nucleo.spec.ts`, reescrevendo a resposta Inertia como o teste do estado vazio já fazia. Na rodada 6: asserção em `textos-nucleo.spec.ts:82-86`; T11, T11b e T11c mortos; e2e inteiro 49 passed.

---

## Requirement Traceability Update

| Requirement | New Status |
| --- | --- |
| NUC-01, NUC-02, NUC-03, NUC-04, NUC-09, NUC-10, NUC-11, NUC-12, NUC-14 | ✅ Verified (rodadas 1–4; na rodada 7, U12 e K9 viram toast pela D6, afirmado em `UserControllerTest.php:190` e `ContactControllerTest.php:246`; A1, A3, A4, A6, A8, D2, D3, D9 conferidos, mutantes mortos) |
| NUC-06 | ✅ Verified na rodada 7 (A7: memória por requisição e lote; M6a, M6b mortos) |
| NUC-08 | ✅ Verified na rodada 7 (Contatos AC 3 por papel, telefone como texto; M8, M9, M10 mortos) |
| NUC-07 | ✅ Verified na rodada 8: AC 9 com as três variantes afirmadas (N = 1 em `e2e/textos-nucleo.spec.ts:121-123`, S8a e S8b mortos); AC 10 e AC 12 ✅ desde a rodada 7 (`ClientControllerTest.php:211,224`; M2 morto); rodada 9: AC 10 "a WCJ não pode ser excluída" pela rota (`ClientControllerTest.php:212-218`) e pelo Service (`ClientServiceTest.php:325-329`), mutantes a e b mortos |
| NUC-05 | ✅ Verified (rodada 4: AC 1 e 2 fechados pela T31) |
| NUC-13 | ✅ Verified na rodada 9: AC 1–3 com a Policy dentro de `choiceList(User)` e a negação auditada (`UserServiceTest.php:672-680,689-692`, `ClientServiceTest.php:443-452,461-462`; c1, c2, d mortos). Até a rodada 8: AC 3 ⚠️ decisão (Fix 2), decidida em 09/10 (D13) |

---

## Summary

**Overall (rodada 9)**: ✅ Ready para as etapas seguintes (`.ai/core/especificacao.md` §5 item 9). As correções dos ciclos 1 e 2 da code review estão provadas: 17 linhas de AC com `arquivo:linha` e o valor da spec, e as 20 mutações nas regras que o ciclo mexeu (WCJ não excluível na Policy e no Service, Policy dentro do `choiceList`, a negação auditada, o `before` do `password_reset`, a ordem das travas do desativar, as decisões pela linha travada, a captura do unique, o `attempt()` do editar contato, o cliente ativo na criação do contato) morreram. O P2 AC 3 deixou de ser desvio (D13).

**Spec-anchored check (rodada 9)**: 17/17 linhas com o valor da spec; 0 gap; 0 spec-precision gap novo.
**Sensor (rodada 9)**: 20 mutações, 20 mortas, 0 sobrevivente, 0 equivalente (P0, à mão, `DB_TESTE=container`).
**Gate (rodada 9)**: `composer lint` ok; `php artisan test` 345 passed (2853 assertions) no MySQL 8.4 descartável; typecheck, eslint, build ok; `specs:check` 0 problemas; guard 1053 pass; Playwright 68 passed (67 + setup).

**Next steps (rodada 9)**: o push dos commits e o CI verde no PR #5; as etapas do §5 item 9; atualizar o `AGENTS.md` ("Estado atual" ainda diz que falta o Verifier focado na review) e o critério de sucesso do `spec.md:262`, que não são deste relatório. Seguem abertos, sem reprovar: o texto do último admin, os textos U5, C2, K2, K11 e as decisões fora da spec (CR-1-11, CR-1-12, CR-1-23, CR-2-09, CR-2-08, CR-1-01 em PR próprio).

**Overall (rodada 8)**: ✅ Ready para as etapas seguintes (`.ai/core/especificacao.md` §5 item 9: revisão em camadas, print do Playwright no PR, quatro perguntas por entidade, revisão de segurança dirigida). Os dois gaps da rodada 7 fechados pela T51: estado vazio estável e discriminante (20/20; as sondas mostram que ele depende da linha prévia e da reescrita) e texto N = 1 do Clientes AC 9 afirmado (S8a, S8b mortos).

**Spec-anchored check (rodada 8)**: Clientes AC 9 com as três variantes (N = 0, 1, ≥ 2) afirmadas com o texto exato da spec; P3 AC 1 (estado vazio de Usuários e Contatos) com a resposta reescrita provada.
**Sensor (rodada 8)**: 2 mutações, 2 mortas. Somando a rodada 7: 19 mutações, 18 mortas, 1 equivalente (M1c), 0 sobrevivente.
**Gate (rodada 8)**: `composer lint` ok; `php artisan test` 322 passed (2761 assertions); estado vazio `--repeat-each=10` 20/20; Playwright 56 passed nas duas rodadas com `e2e:prepare` antes de cada.

**Next steps (rodada 8)**: as etapas do §5 item 9 e o PR #5 com o Lucas Scholz. Seguem abertos, sem reprovar, os pontos de "Fix 2" (P2 AC 3, texto do último admin, textos U5, C2, K2, K11) e a nota de produto do `SearchInput` (A5). Na rodada 9: o P2 AC 3 fechou (D13).

**Overall (rodada 7)**: ❌ Not Ready, por dois gaps Minor, só de teste. As correções da review do Lucas Scholz estão certas: A1–A9 corrigidos com evidência; D2, D3, D7 (lote) e D9 no código; documentos com D1, D4, D5, D11; nenhum `SPEC_DEVIATION`. Falta: (1) consertar o teste instável `e2e/textos-nucleo.spec.ts:221` (Contatos; Fix 4), que deixou a rodada 2 do e2e vermelha (54/55) e falha 1 em 10 isolado; (2) afirmar o texto N = 1 do Clientes AC 9 (Fix 5).

**Spec-anchored check (rodada 7)**: 7 ACs reescritos pela T50 conferidos, 6 ✅ e 1 ❌ (Clientes AC 9, N = 1); 9 achados e 4 decisões com efeito no código, todos com `arquivo:linha` e valor da spec.
**Sensor (rodada 7)**: 17 mutações (15 PHP, 2 tela), 16 mortas, 0 sobrevivente real, 1 equivalente (M1c).
**Gate (rodada 7)**: `composer lint` ok; `php artisan test` 322 passed (2761 assertions); typecheck, eslint, build, guard ok; Playwright rodada 1: 55 passed; rodada 2: 54 passed, 1 failed (`textos-nucleo.spec.ts:221`). `@critical` verde nas duas.

**Next steps (rodada 7)**: Fix 4 e Fix 5 (só teste), depois uma reverificação focada neles (rodada 8, se o dono autorizar, como nas rodadas 4 e 6). Depois, as etapas de `.ai/core/especificacao.md` §5 item 9.

**Overall (rodada 6)**: ✅ Ready para as etapas seguintes (§5 item 9), com os desvios declarados abaixo pendentes do Lucas Scholz. O único gap da rodada 5 foi fechado pela T36 (só e2e): o texto do Clientes AC 9 com N projetos é afirmado com `exact: true` (`textos-nucleo.spec.ts:82-86`), e T11, T11b e T11c morrem. Gate: `composer lint` ok, `php artisan test` 287 passed (2447 assertions), Playwright 49 passed na 8001. Sensor somado no escopo da spec revisada: 13 mutações, 13 mortas.

**Overall (rodada 5, histórico)**: ❌ Not Ready, por um gap Minor. A adaptação à spec revisada de 07/10 está correta no que importa para permissão e escopo (contrato, vazia, dublê, Policies, lista do gestor, Out of Scope), com 10 mutações mortas. Falta voltar a afirmar o texto do Clientes AC 9 com N projetos, que a T34 tirou do e2e (Fix 3, só teste). Pelo limite de 3 rodadas da skill, a decisão de corrigir e reverificar (rodada 6) ou aceitar é do dono, como na rodada 4.

**Spec-anchored check (rodada 5)**: 19 linhas conferidas contra a spec revisada; 17 ✅ (uma com o desvio declarado do `openProjectsCount`), 1 ⚠️ (P2 AC 3, desvio já pendente) e 1 ❌ (texto do Clientes AC 9).
**Sensor (rodada 5)**: 11 mutações, 10 mortas, 1 sobrevivente (T11, `index.tsx:37`).
**Gate (rodada 5)**: `composer lint` ok; `php artisan test` 287 passed, 0 failed (rodada 4: 284; antes da feature: 76); typecheck, eslint, build e guard ok; Playwright 48 passed na 8001.

**What works**: tudo o que a rodada 4 listou, e agora "gestor do cliente" pelo contrato `ClientManagement`, sem ler `projects` (A9 verde), com a vazia no container e o dublê nos testes; o dev listado pelo contrato continua sem acesso; a lista de contatos do gestor segue o `managedClientIds`.

**Pendente do Lucas Scholz (não reprova)**:

- os três `destroy` (usuário, cliente, contato) com toast em vez de 403;
- o P2 AC 3 (403 para o dev sem rota, por causa do D6); fechado em 09/10 (D13), ✅ na rodada 9;
- a mensagem do último admin ao se desativar;
- os textos que a spec não define (U5, C2, K2, K11) e as variantes N = 0 e N = 1 do texto de desativar cliente;
- se a WCJ pode ter o CNPJ ou o país editado e perder a proteção do N7 (observação em "Code Quality");
- o 3º método `openProjectsCount(Client)` do contrato.

**Next steps (rodada 6)**: decisões acima com o Lucas (inclusive o `openProjectsCount` e as variantes N = 0 e N = 1); acertar, se quiser, o docblock de `e2e/textos-nucleo.spec.ts:9-10` (cosmético); depois as etapas do `.ai/core/especificacao.md` §5 item 9 (revisão em camadas, print do Playwright no PR, quatro perguntas por entidade, revisão de segurança dirigida) e o PR. Para o B2: os casos do `ClientProjectsTest` apagado (responsável, gestor alocado, dev alocado não gere, outro cliente, sem repetição, abertos sem `finished_at`) voltam no teste da `ProjectClientManagement`.

---

## Saída dos validadores (rodada 8)

Rodados depois de escrever este relatório:

```
$ python3 .claude/skills/tlc-spec-driven/scripts/validate_state.py b1-nucleo
validate_state: 0 error(s) across [b1-nucleo]          (exit 0)

$ node .ai/skills/conferir-specs.mjs
[b1-nucleo] validate_spec.py: ok
[b1-nucleo] validate_tasks.py: ok, com aviso            (T35 e T50 com Tests: none, esperado: só documentos)
validate_tasks: 0 error(s), 2 warning(s)
[b1-nucleo] validate_state.py: ok
conferir-specs: 1 feature(s), 0 problema(s)             (exit 0)
```

## Lições (`.specs/lessons.json`), rodada 8

Sem sinal novo (PASS limpo, nenhum mutante sobrevivente, nenhum gap): nenhuma lição nova. As duas da rodada 7 receberam, pelo `lessons.py add` (mesmo texto, mesma feature), a evidência de que a T51 as resolveu; continuam `candidate` com recorrência 1, porque a promoção pede uma segunda feature:

- `L-010` (gate_fail): evidência nova "resolvida pela T51 (445f5d2): e2e/textos-nucleo.spec.ts:267 (linha antes do clique), :276-278 (waitForResponse da reescrita); rodada 8: --repeat-each=10 20/20".
- `L-011` (ac_gap): evidência nova "resolvida pela T51 (445f5d2): e2e/textos-nucleo.spec.ts:121-123 (texto N = 1, exact); rodada 8: mutantes S8a e S8b mortos".
- `lessons.py status`: 11 total, 0 confirmed, 11 candidate, 0 quarantined.

## Saída dos validadores (rodada 7)

```
$ python3 .claude/skills/tlc-spec-driven/scripts/validate_state.py b1-nucleo
  ERROR b1-nucleo: validation.md verdict is FAIL - route the ranked gaps to fix tasks, then re-verify (feature is not done)
validate_state: 1 error(s) across [b1-nucleo]          (exit 1, esperado com o FAIL)

$ node .ai/skills/conferir-specs.mjs
[b1-nucleo] validate_spec.py: ok
[b1-nucleo] validate_tasks.py: ok, com aviso            (T35 e T50 com Tests: none)
[b1-nucleo] validate_state.py reprovou                  (o mesmo FAIL acima)
conferir-specs: 1 feature(s), 1 problema(s)             (exit 1)
```

Antes de reescrever este arquivo (com o PASS da rodada 6), o `conferir-specs` dava 0 problemas. O único problema agora é o veredito FAIL, que some quando Fix 4 e Fix 5 passarem numa reverificação.

## Lições (`.specs/lessons.json`), rodada 7

- `L-010` (gate_fail, `e2e/textos-nucleo.spec.ts:221`): e2e que reescreve uma resposta para forçar um estado espera essa resposta antes de afirmar e prova que o estado não estava lá antes.
- `L-011` (ac_gap, Clientes AC 9 N = 1): quando a revisão da spec promove variantes de texto a AC, a mesma tarefa acrescenta uma asserção por variante.
- `lessons.py status`: 11 total, 11 candidate.

## Saída dos validadores (rodada 6)

Rodados depois de escrever este relatório:

```
$ python3 .claude/skills/tlc-spec-driven/scripts/validate_state.py b1-nucleo
validate_state: 0 error(s) across [b1-nucleo]          (exit 0)

$ node .ai/skills/conferir-specs.mjs
[b1-nucleo] validate_spec.py: ok
[b1-nucleo] validate_tasks.py: ok, com aviso
WARN  T35: Tests: none - confirm the Test Coverage Matrix says 'none' for this layer
validate_tasks: 0 error(s), 1 warning(s) in /home/cau-soares/Projetos/WCJ/wcj-hub/.specs/features/b1-nucleo/tasks.md
[b1-nucleo] validate_state.py: ok
conferir-specs: 1 feature(s), 0 problema(s)            (exit 0)
```

O aviso da T35 é o mesmo da rodada 5 (tarefa só de documento, `Tests: none`), não é erro.

## Lições (`.specs/lessons.json`), rodada 6

- **Resolvida nesta rodada**: `L-008` (surviving_mutant, T11, escopo `e2e`). A T36 é a aplicação dela. Recebeu a evidência da rodada 6 pelo `lessons.py add`, com o mesmo texto e a fonte "rodada 6: T11/T11b/T11c mortos por e2e/textos-nucleo.spec.ts:82-86 (resposta da busca reescrita com open_projects = 2), T36 6beb785". Saída: `UPDATED L-008 (recurrence=1, status=candidate)`. O `lessons.py` não tem ação de "resolver"; ela fica `candidate`, como guia, e sai pelo `prune` se não voltar em 45 dias (como a `L-006` na rodada 3 e a `L-007` na rodada 4).
- Nenhuma lição nova: a rodada 6 não teve sobrevivente nem gap novo. O precision gap das variantes N = 0 e N = 1 e o desvio do `openProjectsCount` já estão registrados desde a rodada 5 (`L-003`, `L-009`).
- `prune`: `Pruned 0 stale candidate(s): -`. `status`: `lessons: 9 total | confirmed=0 candidate=9 quarantined=0`.

## Saída dos validadores (rodada 5)

Rodados depois de escrever este relatório. Os dois reprovam por causa do veredito FAIL, como esperado:

```
$ python3 .claude/skills/tlc-spec-driven/scripts/validate_state.py b1-nucleo
  ERROR b1-nucleo: validation.md verdict is FAIL - route the ranked gaps to fix tasks, then re-verify (feature is not done)
validate_state: 1 error(s) across [b1-nucleo]          (exit 1)

$ node .ai/skills/conferir-specs.mjs
[b1-nucleo] validate_spec.py: ok
[b1-nucleo] validate_tasks.py: ok, com aviso
WARN  T35: Tests: none - confirm the Test Coverage Matrix says 'none' for this layer
validate_tasks: 0 error(s), 1 warning(s) in .specs/features/b1-nucleo/tasks.md
[b1-nucleo] validate_state.py reprovou
ERROR b1-nucleo: validation.md verdict is FAIL - route the ranked gaps to fix tasks, then re-verify (feature is not done)
validate_state: 1 error(s) across [b1-nucleo]
conferir-specs: 1 feature(s), 1 problema(s)            (exit 1)
```

O aviso da T35 (tarefa só de documento, `Tests: none`) é novo da fase 10 e não é erro.

## Lições (`.specs/lessons.json`), rodada 5

- **Nova**: `L-008` (surviving_mutant, T11, escopo `e2e`): "When a stub makes a UI variant unreachable in e2e, keep asserting the spec text by rewriting the server response instead of switching to the reachable variant". Saída: `ADDED L-008 (status=candidate, recurrence=1)`.
- **Sinal que volta**: `L-003` (ac_gap, texto de tela que só o backend provava) recebeu a evidência do Clientes AC 9. Saída: `UPDATED L-003 (recurrence=1, status=candidate)`. A recorrência não sobe porque conta features distintas.
- **Nova**: `L-009` (spec_deviation, `openProjectsCount`, escopo `contracts`): "When a spec adds a contract for another domain's data, list every datum the screens need through it, so the contract does not grow outside the spec". Saída: `ADDED L-009 (status=candidate, recurrence=1)`.
- `prune`: `Pruned 0 stale candidate(s)`. `status`: `lessons: 9 total | confirmed=0 candidate=9 quarantined=0`.

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
