# B4 PR 1 (teste cruzado e roteiro de QA na ficha) Validation

## Validation: FAIL

**Result**: FAIL na rodada 2 (23/10), por um mutante só, e ele é de mensagem. Os 11 sobreviventes da rodada 1 morrem com os testes do commit `b71d5c0`, cada um no teste do seu Fix. Os 3 de confirmação seguem mortos, e o gate está verde. A guarda nova do `undoCrossTested` passou pela mutação completa (tirar, inverter, `manage`, antes da trava) e por mais três extras: 6 dos 7 mutantes morrem. O que sobrevive é o **x6**, a guarda posta antes da recusa sem marca.

Com o x6, quem marcou e desfaz numa corrida, depois de o gestor já ter desfeito, recebe "A marca de teste cruzado mudou: recarregue a ficha." no lugar de "Este card não tem teste cruzado marcado.". O x6 não é equivalente: a spec dá a segunda mensagem ao card sem marca (CRUZ-02 AC 7; premissa "Desfazer sem marca", `spec.md:46`), e a sonda x6 mata. Nada muda no banco nos dois casos. Falta só o teste da ordem das duas recusas (Fix 8, Minor).

O que a rodada 2 acrescenta ao que a rodada 1 já provou:
- a recusa do dev pelo `TaskService` com a linha relida, sem passar pela Policy (s1);
- a marca, o desfazer, o resultado do item e a evidência num commit só com a auditoria, no banco de verdade (s7, d5, c7, a7);
- o card arquivado recusa o desfazer e a evidência (d4, a3, a3b);
- o `result` ausente vira erro no campo (q4);
- o Reprovado conta como uso (qu6);
- o `run_qa` do dev da tarefa (t3b);
- a guarda nova do desfazer, que decide pela linha travada: quem marcou desfaz, quem gere desfaz a marca de outra pessoa, e a marca que trocou de mão no meio não se limpa (x1 a x5, x7);
- TELA-01 AC 4, 5, 7 e 8, pela leitura do 2º teste do e2e (não rodado);
- a ficha com uma consulta a menos (o `testResult` que não era lido saiu do eager load).

CRUZ-01, ROTEIRO-01, ROTEIRO-02, EXC-01, EVID-01, TELA-01 (pela leitura) e DONE-01 estão ✅ Verified. CRUZ-02 fica ⚠️ só pelo Fix 8.

Sensor: rodada 2, 21 aplicados (os 11 + 7 na guarda nova, 3 deles extras, + 3 de confirmação), 20 mortos, 1 sobrevivente não equivalente (x6). Consolidado das duas rodadas: 109 mutantes distintos, 5 equivalentes (os da rodada 1), **103/104** contados mortos.

Fica para o humano, e não é Fix: a spec segue **sem aprovação do Lucas Scholz**. Agora são 19 premissas sem `y` em *Confirmed?*, porque a premissa nova da L47 ("A Policy olhou o card antes da trava") entrou com a mudança de produção do `b71d5c0`.

---

## Rodada 2 (23/10)

**Date**: 2026-10-23
**Spec**: `.specs/features/b4-teste-e-qa/spec.md` (CRUZ-01, CRUZ-02, ROTEIRO-01, ROTEIRO-02, EXC-01, EVID-01, TELA-01, DONE-01), com a premissa nova da L47. **Approved by: aguardando Lucas Scholz.**
**Diff range**: `a414931..b71d5c0` (1 commit, `test(projetos): os 7 Fix do Verifier do B4 PR 1 (rodada 1) e a trava do desfazer`). Sete arquivos:
- `tests/Feature/Projetos/B4TransacaoTest.php`, novo, com 4 testes: Fix 2;
- `TaskTestTest.php`, com 5 testes novos e 2 asserções novas: Fix 1, 3, 4, 5 e 6, e o teste da guarda nova;
- `e2e/teste-e-qa.spec.ts`, com o 2º teste, e `e2e/helpers/dados.ts`, com o 2º card sem critério: Fix 7;
- `spec.md`: a premissa da L47 e a rastreabilidade;
- **produção**, 2 arquivos (11 linhas a mais, 2 a menos):
  - `TaskService.php`: a constante `CROSS_TEST_CHANGED` (`:40`), a guarda `if (! $actor->can('undoCrossTest', $locked))` com a linha travada (`:159-161`) e o docblock (`:145-149`);
  - `TaskDetailPresenter.php:66`: sem o `acceptanceCriteria.testResult`.

  O `git diff --stat a414931..b71d5c0 -- app resources routes database config bootstrap` lista só esses dois.

**Verifier**: agente separado de quem implementou e de quem aplicou os Fix, sem acesso ao raciocínio deles; julgado só pela spec, pelo diff e pelos testes.

### Gate Check (árvore real, HEAD `b71d5c0`, sem edição)

| Comando | Saída |
|---|---|
| `composer lint` (`pint --test`) | `{"tool":"pint","result":"passed"}`, exit 0 |
| `npm run typecheck` (`tsc --noEmit`) | exit 0 |
| `npm run lint` (`eslint .`) | exit 0 |
| `node .ai/skills/conferir-specs.mjs` (antes deste relatório) | `conferir-specs: 8 feature(s), 21 problema(s)`, exit 1, tudo no B4: <br>1) `validate_state.py`: "validation.md verdict is FAIL" (o da rodada 1); <br>2) `spec.md` sem `**Approved by**: <nome>, <AAAA-MM-DD>`; <br>3) 19 premissas sem `y` (L42 a L60; a L47 é a nova). <br>Mais o aviso "open questions do not read as resolved". As outras 7 features ok |
| `node .ai/skills/conferir-specs.mjs` (depois deste relatório) | ver "Depois do relatório", no fim desta rodada |
| `php artisan test --compact` (na worktree descartável `/tmp/tlc-b4-teste-e-qa-2`, HEAD `b71d5c0` sem mutação, depois do sensor; MySQL 8, `wcj_hub_test`) | **826 passed (6947 assertions)**, 147,98 s, exit 0. São os 817 da rodada 1 mais 9: os 4 do `B4TransacaoTest` e os 5 testes novos do `TaskTestTest` |
| Base do sensor (worktree, sem mutação) | os 10 arquivos pedidos (`TaskTestTest`, `TaskTestPolicyTest`, `TesteCruzadoConcorrenciaTest`, `B4TransacaoTest`, `MigracaoMarcacaoTest`, `TaskDetailTest`, `BoardTest`, `TaskUsageQueryTest`, `TaskArchiveTest`, `B3FichaTransacaoTest`): **109 passed (858 assertions)**, 32,55 s, nenhum pulado. O `--list-tests` confirma os 4 do `B4TransacaoTest` e os 5 do `B3FichaTransacaoTest` no conjunto. A sonda x6 contra o código real: 1 passed |

Não rodei o Playwright (o servidor de e2e é de outra sessão) nem o Storybook. TELA-01 foi julgada pela leitura.

### Cada Fix Plan da rodada 1 e o teste que o cumpre

Abreviaturas da rodada 1, mais **B4T** = `tests/Feature/Projetos/B4TransacaoTest.php`. As linhas são as de agora.

| Fix | O que a rodada 1 pediu | Evidência (`arquivo:linha` + asserção) | Prova de verdade? |
|---|---|---|---|
| **Fix 1**: o Service recusa o dev com a linha travada (s1) | o Service chamado direto; e a corrida, o card lido sem dev e o dev trocado no banco | TT:147-160 `test_the_service_refuses_whoever_became_the_developer_after_the_policy`: o card lido antes (`$antes`, :149); o Outro posto como dev no banco (:150); `markCrossTested($antes, $this->outroDev)` sem a rota (:153); `assertSame('Você é o dev desta tarefa: o teste cruzado é de outra pessoa.', …)` (:156); `assertNull(cross_tested_by)` (:159) | **Sim.** O s1 morre em TT:154 ("o dev da tarefa marcou"). Só a variante da corrida tem teste; a chamada direta pelo dev fica coberta pelo mesmo teste, porque o s1 tira a checagem inteira |
| **Fix 2**: "na mesma transação" (s7, d5, c7, a7) | `DatabaseTruncation` + `UsaBancoDeVerdade`, sem `RefreshDatabase`, `transactionLevel()` 0, a auditoria que lança, nada fica | B4T:37 (`CriaProjeto, DatabaseTruncation, UsaBancoDeVerdade`); `exigirBancoDeVerdade()` antes do `parent::setUp()` (:47-48); `limparOQueFicou()` no `tearDown` (:59); `failingAudit()` (:63-72); `assertSame(0, DB::transactionLevel())` em :93, :103, :113 e :125. A marca: :95 a falha é a da auditoria, :97-98 os dois campos nulos. O desfazer: :106, :108 "a marca continua". O resultado: :117, :120 `[PENDING, null, null]`. A evidência: :129, :133 0 anexos, :134 o disco vazio | **Sim.** s7 morre em B4T:97, d5 em :108, c7 em :120 e a7 em :133 |
| **Fix 3**: card arquivado no desfazer e na evidência (d4, a3, a3b) | as duas recusas com a mensagem; a marca fica; 0 anexos | TT:280-290 `test_an_archived_card_keeps_its_mark` (o gestor desfaz; :287 `assertSessionHas('error', 'Card arquivado não muda: desarquive antes.')`; :289 a marca fica). TT:479-490 `test_evidence_is_not_attached_to_an_archived_card` (:486 a mensagem; :488 0 anexos; :489 o disco vazio) | **Sim.** d4 morre em TT:287; a3 e a3b em TT:486 |
| **Fix 4**: o `result` ausente (q4) | `assertSessionHasErrors('result')` | TT:369-377 `test_a_missing_result_is_refused_on_the_field`: corpo vazio; :374 `assertSessionHasErrors(['result'])`; :376 o resultado igual | **Sim.** q4 morre em TT:374 |
| **Fix 5**: o Reprovado conta como uso (qu6) | `FAILED` sem `marked_at`, `assertFalse(isUnused)` | TT:544-545 ("item reprovado é uso"), entre o Aprovado (:541-542) e o A testar marcado à mão (:547-548) | **Sim.** qu6 morre em TT:545 |
| **Fix 6**: `run_qa` do dev da tarefa (t3b) | `assertTrue(ficha($dev)['abilities']['run_qa'])` | TT:168, no mesmo teste que afirma `cross_test` falso para o dev (:164) | **Sim.** t3b morre em TT:168 |
| **Fix 7**: TELA-01 AC 4, 5, 7 e 8 | e2e do *Devolver* do teste cruzado, da confirmação, do roteiro vazio; AC 8 por `play` ou pela leitura | E2E:92-134, o 2º teste (detalhe na tabela abaixo) | **Sim, pela leitura** (não rodado) |

O achado da rodada 1 sobre o desfazer também foi atendido, e com código: o `undoCrossTested` confere de novo com a linha travada (`TaskService.php:159-161`), com o teste `test_undo_does_not_clear_a_mark_that_changed_hands_in_the_meantime` (TT:263-278). Os outros dois achados também:
- o `acceptanceCriteria.testResult` saiu do eager load: o benchmark da ficha agora é 45 consultas, contra 46 na rodada 1 (ver o t11);
- a mensagem `OWN_CROSS_TEST` está na spec (premissa L47).

#### TELA-01 AC 4, 5, 7 e 8 (leitura do `e2e/teste-e-qa.spec.ts`, não rodado)

| Critério | Evidência | Resultado |
|---|---|---|
| AC 4: sem critério → "Esta tarefa não tem critérios de aceite." + *Novo critério*, que abre a criação | E2E:128-133. O card `-2` sem critério vem do helper (`e2e/helpers/dados.ts:156-161`); :131 o texto; :132 o clique em *Novo critério*; :133 `expect(page.getByLabel('Novo critério')).toBeFocused()`. O caminho no código: `onNewCriterion` abre a seção `criteria` e incrementa o `addRequest` (`w-task-detail-page.tsx:347-350`); o organism dos critérios abre o editor do critério novo (`w-task-criteria-section-organism.tsx:117-123`), com o rótulo "Novo critério" (`:305`) e `autoFocus` (`:193`) | PASS pela leitura |
| AC 5: *Devolver* do bloco do teste cruzado → To do, origem "Teste cruzado" | E2E:121-126. Clica no primeiro *Devolver* da seção, que é o do bloco do teste cruzado: `returnButton('cross_test')` (`w-task-test-section-organism.tsx:312`) vem antes do `returnButton('qa')` (`:339`) no DOM. :123 o diálogo `Devolver <prefixo>-1 para To do`; :124 `Origem` com o texto "Teste cruzado" | PASS pela leitura |
| AC 7: "Desfazer a marca de teste cruzado de Fulano?" antes de enviar | E2E:114-119: :116-117 o diálogo com o nome; :118 *Desfazer*; :119 "Ainda não marcado" só depois. O "antes de enviar" vale pela ordem; nenhuma asserção diz que a marca continua enquanto o diálogo está aberto | PASS pela leitura |
| AC 8: os botões da seção desabilitados durante a escrita | E2E:102-112: `page.route('**/teste-cruzado')` prende o POST da marca. Dentro do handler: `toBeDisabled()` no primeiro *Devolver* (:104) e no *Aprovado* de CA_1 (:105-107); só então `route.continue()` (:108). Se a asserção falha, o handler lança, o POST não segue e o :111 estoura o tempo: o teste falha de qualquer jeito. O glob não pega `…/teste-cruzado/desfazer`, e o `unroute` (:112) vem antes do desfazer | PASS pela leitura (dois dos botões com `disabled={busy}`, numa das escritas) |

O *Novo critério* do roteiro vazio (`w-task-test-section-organism.tsx:324`) não tem `disabled={busy}`. Não é Fix: ele não grava, só abre o campo na seção dos critérios.

As linhas da rodada 1 no `TaskTestTest.php` andaram com o diff:
- +1 depois de `:11` e +1 depois de `:14` (os `use`);
- +19 depois de `:140` e +2 depois de `:145`;
- +33 depois de `:235`, +10 depois de `:312` e +13 depois de `:412`;
- a `:463` virou `:542-545`.

Exemplos: TT:153 → `:176`; TT:233 → `:256`; TT:245 → `:301`; TT:434 → `:513`; TT:463 → `:542`. `TaskTestPolicyTest`, `TesteCruzadoConcorrenciaTest`, `MigracaoMarcacaoTest`, `TaskDetailTest`, `BoardTest` e E2E:1-85 não mudaram de linha. No `TaskService.php`, as linhas da rodada 1 andaram +2 depois de `:38`, +4 no docblock do `undoCrossTested` e +3 na guarda; a tabela abaixo já usa as de agora.

### Discrimination Sensor

**Método.** O mesmo da rodada 1, pelo script `/tmp/verifier-b4-pr1-r2/run2.py`. Ele é cópia do `run.py` da rodada 1, com os mesmos trechos de troca e dois ajustes:
- o trecho do t11 mudou, porque a linha do eager load mudou;
- entraram os mutantes x1 a x7 da guarda nova.

Os resultados ficam em `results.json` (o do conjunto R2 também em `results-r2-set.json`), a saída em `out-<id>.txt`, o log em `run-log.txt`.

Worktree `/tmp/tlc-b4-teste-e-qa-2` (`git worktree add … HEAD`), com o `vendor/` **copiado** (`cp -a`) e o `node_modules` em symlink. Antes do primeiro mutante, o script confere que a classe carregada é a da worktree: `ReflectionClass::getFileName()` deu `/tmp/tlc-b4-teste-e-qa-2/app/…`. Para cada mutante, o script:
1. confere o arquivo limpo;
2. aplica a troca exata (falha se o trecho não for único);
3. roda `php -l` e `php artisan test --compact --filter=<R2>`;
4. reverte com `git checkout -- <arquivo>` e confere limpo de novo.

`git status --porcelain` da worktree estava vazio antes e saiu vazio no fim (`porcelain final: ''`). A worktree foi removida (`git worktree remove --force`), e a árvore real não foi tocada fora deste arquivo.

Conjunto **R2** = os 10 arquivos pedidos (109 testes). O sobrevivente x6 rodou também contra a suíte inteira (`--filter .`, 826 testes) e passou.

| # | Mutante (o mesmo trecho da rodada 1, onde houver) | `arquivo:linha` (agora) | Saída | Quem matou |
|---|---|---|---|---|
| s1 | `markCrossTested` sem a recusa do dev | `TaskService.php:128-130` | morto | TT:154 ("o dev da tarefa marcou") |
| s7 | auditoria da marca em `DB::afterCommit` | `TaskService.php:139` | morto | B4T:97 (a marca ficou, a auditoria caiu depois do commit) |
| d4 | `undoCrossTested` com `lockTaskRow` (aceita o arquivado) | `TaskService.php:154` | morto | TT:287 (sem a mensagem do arquivado) |
| d5 | auditoria do desfazer em `DB::afterCommit` | `TaskService.php:165` | morto | B4T:108 ("a marca continua") |
| c7 | auditoria do resultado em `DB::afterCommit` | `CriterionService.php:101` | morto | B4T:120 |
| a3 | `upload` com `lockTaskRow` (aceita o arquivado) | `AttachmentService.php:51` | morto | TT:486 |
| a3b | `upload` com critério sem trava nenhuma | `AttachmentService.php:50-51` | morto | TT:486 |
| a7 | auditoria do anexo em `DB::afterCommit` | `AttachmentService.php:69-72` | morto | B4T:133 ("a linha não ficou") |
| q4 | `result` sem `required` | `CriterionResultRequest.php:32` | morto | TT:374 |
| qu6 | só Aprovado conta como uso | `TaskUsageQuery.php:54` | morto | TT:545 ("item reprovado é uso") |
| t3b | `run_qa` pela `crossTest` | `TaskDetailPresenter.php:169` | morto | TT:168 |
| x1 | `undoCrossTested` sem a guarda nova | `TaskService.php:159-161` | morto | TT:272 ("limpou a marca de outra pessoa") |
| x2 | a guarda invertida (recusa quem pode) | `TaskService.php:159` | morto | B4T:106 (a `DomainException` escapa do `failure()`); TT:210; TT:224; TT:272 |
| x3 | a guarda pede `manage` em vez de `undoCrossTest` | `TaskService.php:159` | morto | B4T:106; TT:210 (quem marcou e não gere não desfaz) |
| x4 | a guarda movida para antes da trava (com o `$task` da rota) | `TaskService.php:153` | morto | TT:272 |
| x5 | [extra] a guarda depois da trava, mas com o `$task` de antes | `TaskService.php:159` | morto | TT:272 |
| x6 | [extra] a guarda antes da recusa sem marca | `TaskService.php:156-161` | **sobreviveu** | nenhum: 109 passed no R2 e 826 passed na suíte inteira. A sonda x6 mata. Fix 8 |
| x7 | [extra] a guarda só com "foi quem marcou" (sem o ramo de quem gere) | `TaskService.php:159` | morto | TT:224 (o gestor e o Administrador desfazem a marca de outra pessoa) |
| d1 | [confirma] `undoCrossTested` sem a recusa sem marca | `TaskService.php:156` | morto | TT:256 |
| c12 | [confirma] `CriterionService::delete` sem a guarda da evidência | `CriterionService.php:120` | morto | TT:513; TT:527 |
| t11 | [confirma] a ficha sem o eager load de `acceptanceCriteria.markedBy` | `TaskDetailPresenter.php:66` | morto | TD:125 (75 consultas em vez de 45; na rodada 1, 76 em vez de 46) |

**Total da rodada 2**: 21 aplicados. Destes:
- os 11 sobreviventes da rodada 1, todos mortos;
- 7 na guarda nova: os 4 pedidos (x1 a x4) e 3 extras (x5 a x7); 6 mortos, 1 sobrevivente (x6);
- 3 de confirmação (d1, c12, t11), mortos.

São 20 mortos, 1 sobrevivente não equivalente e 0 equivalentes novos.
**Consolidado**: 109 mutantes distintos nas duas rodadas (os 102 da rodada 1 + x1 a x7); 5 equivalentes (q1, q2, q6, qu5, g3, com a guarda nomeada na rodada 1); **103/104** contados mortos. Por família, onde mudou: `TaskService` 15/15 (era 11/15), guarda nova 6/7, `CriterionService` 16/16, `AttachmentService` e `StoreAttachmentsRequest` 8/8, `TaskUsageQuery` 5/5, `CriterionResultRequest` 3/3, `TaskDetailPresenter` 13/13.
**Sensor depth**: P0, manual.

### Sonda (rascunho em `/tmp/verifier-b4-pr1-r2/`, fora do repositório)

`SondaR2Test.php` (`RefreshDatabase`) roda contra o código real com `php artisan test --compact /tmp/verifier-b4-pr1-r2/SondaR2Test.php` (1 passed, 3 assertions). Os passos:
1. Monta um card do dev, com o gestor e o Outro alocados.
2. Lê o card com a marca do Outro em memória (`forceFill`); no banco, sem marca, como se o gestor já tivesse desfeito.
3. Confere que a Policy, com o card de antes, deixa o Outro desfazer.
4. Chama `undoCrossTested($antes, $outro)`.
5. Afirma a `DomainException` com "Este card não tem teste cruzado marcado." e `cross_tested_by` nulo.

| Sonda | Código real | Mutante |
|---|---|---|
| x6 | passa | x6: falha em `:41`, com `'A marca de teste cruzado mudou: recarregue a ficha.'` em vez de `'Este card não tem teste cruzado marcado.'` |

O script também rodou essa sonda depois dos mutantes que a rodada 1 marcava com sonda (s1, s7, d4, d5, c7, a3, a3b, a7). Ela passou em todos, como esperado: só vale para o x6.

### Code Quality (só o diff da rodada 2)

| Princípio | Status |
|---|---|
| Surgical changes | ✅ Só testes, o helper do e2e e a spec, mais duas mudanças de produção pequenas: a guarda do desfazer (3 linhas, a constante e o docblock) e uma relação a menos no eager load da ficha. A guarda é o que a rodada 1 apontou ("`undoCrossTested` não confere de novo, com a linha travada, quem marcou"), e a spec ganhou a premissa L47 no mesmo commit |
| Matches patterns | ✅ A guarda pergunta à mesma Policy (`$actor->can('undoCrossTest', $locked)`) com a linha travada, como o `markCrossTested` repete o "não é o dev". A recusa é uma `DomainException`, que vira toast pelo `attempt()`. O `B4TransacaoTest` segue o molde do `B3FichaTransacaoTest` (L-017): `exigirBancoDeVerdade()` antes do `parent::setUp()`, `limparOQueFicou()` no `tearDown`, `transactionLevel()` 0 afirmado e `failingAudit()` |
| Every test maps to a spec requirement | ✅ O docblock do `B4TransacaoTest` cita a spec, os AC e o Fix 2; os testes novos do `TaskTestTest` citam o Fix ou a premissa |
| Spec-anchored outcome check | ✅ As mensagens são afirmadas por `assertSame`, assim como o `[PENDING, null, null]`, o disco vazio e o `run_qa` literal |

Notas que não bloqueiam:
- **As duas recusas novas do Service não gravam `denied`.** São `DomainException`, como a "Teste cruzado já marcado…", e não o `authorize()`. Isso segue o padrão da casa: a recusa de regra vira toast, e o `denied` é a negação da Policy. Também só aparecem numa corrida.
- **A corrida é simulada, não disputada.** O Fix 1 e o teste da guarda põem o card de antes em memória, diferente do banco, sem o `Concorrencia`. Provam a decisão pela linha relida; não provam a espera da trava, que o CC prova para a marca. Basta: o x4 e o x5 (a guarda pelo card de antes) morrem ali.
- Segue da rodada 1: a hora do `cross_tested_at` na auditoria e a do A testar à mão continuam só com `assertNotNull`.

### Fix Plans

Só teste. Nenhum código de produção precisa mudar.

**Fix 8: CRUZ-02 AC 7 na corrida, a ordem das duas recusas do desfazer** (mata x6; Minor)
- No `TaskTestTest.php`, junto do `test_undo_does_not_clear_a_mark_that_changed_hands_in_the_meantime` (TT:263-278):
  - o card lido com a marca do Outro (`$this->task->fresh()->forceFill(['cross_tested_by' => $this->outroDev->id, 'cross_tested_at' => now()])`) e, no banco, sem marca;
  - `app(TaskService::class)->undoCrossTested($antes, $this->outroDev)`;
  - a `DomainException` com `TaskService::NOT_CROSS_TESTED` ("Este card não tem teste cruzado marcado.") e `cross_tested_by` nulo.
- O molde é a sonda x6.
- Por que não é equivalente: com o x6, a mesma requisição recebe "A marca de teste cruzado mudou: recarregue a ficha.". Para o card sem marca, a spec pede "Este card não tem teste cruzado marcado." (CRUZ-02 AC 7, que não distingue quem desfaz, e a premissa "Desfazer sem marca", `spec.md:46`). A premissa nova (`spec.md:47`) cobre a marca que "ainda é dele", e o código decide a precedência: sem marca primeiro (`TaskService.php:156-161`). Se o Lucas Scholz preferir a outra mensagem nesse caso, muda a spec e o teste afirma a outra. O que não pode é a ordem ficar sem teste.

#### Para o humano decidir (não é Fix Plan)

- **A spec não está aprovada.** São as quatro open questions, mais 19 premissas sem `y`. A da L47 é nova e é decisão de produção tomada depois da rodada 1: com a linha travada, quem não gere só desfaz a marca que ainda é dele, e as duas mensagens só aparecem numa corrida.
- **A regra da worktree do sensor** (`.ai/core/especificacao.md` §5 item 8) continua com o `ln -s` para o `vendor/`. Nesta rodada, a cópia (`cp -a`) mais a conferência do `getFileName()` funcionaram de primeira. A sugestão de texto da rodada 1 segue de pé; não editei o arquivo.

### Requirement Traceability Update

| Requirement ID | Status | Teste |
|---|---|---|
| CRUZ-01 | ✅ Verified (rodada 2) | TT:99-200; TP:98-127; CC:38-61; B4T:91-99 |
| CRUZ-02 | ⚠️ Needs Fix 8: AC 1 a 6 PASS. AC 7 PASS pela rota (TT:253-257), sem teste na corrida (x6). A transação (B4T:101-109), o arquivado (TT:280-290) e a guarda nova (TT:263-278) estão provados | TT:204-290; TP:110, TP:129-136; B4T:101-109 |
| ROTEIRO-01 | ✅ Verified (rodada 2) | TT:294-399; TP:111, TP:138-143; B4T:111-121 |
| ROTEIRO-02 | ✅ Verified | TT:402-449; MM:47-70 |
| EXC-01 | ✅ Verified (rodada 2) | TT:506-553 |
| EVID-01 | ✅ Verified (rodada 2) | TT:452-503; TP:112-114; B4T:123-135 |
| TELA-01 | ✅ Verified pela leitura do e2e, não rodado (rodada 2) | E2E:31-134; TT:115, TT:162-169, TT:307-309; TD:101-104 |
| DONE-01 | ✅ Verified | BT:250-266, BT:269-284 |

O `spec.md` não foi editado: o Verifier só reporta.

### Summary

**Overall**: ❌ Not ready, por um Fix só (Fix 8, Minor, um teste). Os sete Fix da rodada 1 fecharam todas as lacunas que abriram.

**Spec-anchored check**: 7 de 8 requisitos verificados; CRUZ-02 fica só pela ordem das duas recusas na corrida.
**Sensor**: rodada 2, 20/21 mortos; consolidado, 103/104 contados mortos, 5 equivalentes, 1 sobrevivente não equivalente (x6), morto pela sonda.
**Gate**: 826 passed (6947 assertions) na worktree, no HEAD sem mutação; Pint, typecheck e lint verdes na árvore real. O `conferir-specs` fica vermelho pelo veredito FAIL e pela aprovação da spec (ver "Depois do relatório").

**Lições** (`lessons.py`): não gravei, porque a instrução era deixar só este arquivo modificado na árvore. Candidatas:
- duas recusas seguidas, com a linha travada, pedem um teste da ordem (qual mensagem vence quando as duas valem);
- a da rodada 1 sobre o `vendor/` copiado e o `getFileName()`, confirmada nesta rodada.

**Next steps**:
1. Quem implementou aplica o Fix 8 (só teste) num commit local.
2. Verifier rodada 3 em agente separado: o x6 e uma amostra dos mortos. Ao gravar um PASS no topo, a rodada 3 precisa renomear o rótulo `Result` das linhas de veredito das rodadas 1 e 2 (como no B3, "**Veredito da rodada N**"). O `_verdict` do `validate_state.py` (`:52-69`) junta o título `## Validation` com toda linha que tenha o rótulo `Result` seguido de dois-pontos, e PASS com FAIL nelas é lido como "unfilled" (esta mesma frase, escrita com o rótulo literal, fez o script ler "unfilled" na 1ª gravação deste relatório).
3. Lucas Scholz aprova a spec, responde às quatro open questions e marca as 19 premissas, inclusive a L47, antes do PR.

### Depois do relatório

`node .ai/skills/conferir-specs.mjs` depois de gravar esta rodada: `conferir-specs: 8 feature(s), 21 problema(s)`, exit 1, os mesmos 21 de antes:
1. `[b4-teste-e-qa] validate_state.py reprovou`: "validation.md verdict is FAIL", agora o desta rodada (sai com o PASS da rodada 3);
2. `spec.md` sem `**Approved by**: <nome>, <AAAA-MM-DD>`;
3. 19 premissas sem `y` em *Confirmed?* (`spec.md` L42 a L60);
4. o aviso "open questions do not read as resolved".

As outras 7 features seguem ok. Os itens 2 a 4 são a aprovação do Lucas Scholz: decisão humana, não Fix.

---

## Rodada 1 (23/10)

**Result**: FAIL na rodada 1 (23/10). Nenhum defeito de comportamento no código de produção: em toda checagem, inclusive nas duas sondas (seção "Sondas"), o código fez o que a spec pede. O que falta é teste. Onze mutantes não equivalentes sobrevivem à suíte inteira, e cada um morre numa sonda fora do repositório, o que prova que não são equivalentes.

O que está provado:
- as três abilities novas, ramo por ramo: cada condição de `crossTest`, `undoCrossTest` e `runQa`, o `authorize()` das duas rotas do teste cruzado e o mapeamento da negação para `updated` (23 mutantes, 23 mortos);
- a recusa da marca repetida com o nome de quem marcou, a recusa sem marca, a trava da linha (o teste de concorrência);
- o resultado do item com quem e quando, o "mesmo resultado não grava", o subitem à parte, a volta para A testar que limpa a marcação (edição e retrabalho) e o engano que mantém;
- as duas regras de exclusão de verdade (critério com evidência, no item e no subitem; card com item marcado);
- a evidência ligada ao critério do card e recusada no critério de outro card;
- o `qa_summary` (as duas contagens, nulo sem critério, uma consulta só com 500 cards);
- a migração (desce com a FK, sobe de novo, idempotente).

As lacunas, todas só de teste:
- **CRUZ-01, a recusa do dev com a linha travada** (s1): o `TaskService` recusa o dev da tarefa depois de travar a linha (`TaskService.php:126-128`), para o caso em que o dev muda entre o `authorize()` e a trava. Tirar a recusa passa na suíte, porque todo teste chega pelo HTTP e a Policy barra antes. É regra de permissão (P0).
- **"Na mesma transação"** (s7, d5, c7, a7; L-017 pela 4ª feature): a auditoria da marca, do desfazer, do resultado do item e do anexo levada para `DB::afterCommit` passa na suíte. CRUZ-01 AC 2 e ROTEIRO-01 AC 4 dizem "na mesma transação" com todas as letras.
- **Card arquivado** (d4, a3, a3b): a premissa recusa as quatro escritas no card arquivado; marcar e marcar o item têm teste, desfazer a marca e anexar evidência não.
- **ROTEIRO-01 AC 10** (q4): sem o `result` no corpo, o servidor responde 500 em vez do erro no campo; o teste só manda `passed`.
- **EXC-01 AC 4** (qu6): "resultado diferente de A testar" só é provado com Aprovado; contar só o Aprovado passa.
- **`abilities.run_qa` do dev da tarefa** (t3b): o único caso em que `run_qa` difere de `cross_test` não tem asserção.
- **TELA-01 AC 4, 5, 7 e 8**: sem asserção (stories sem `play` que afirme, e2e só do atalho do roteiro).

Sensor: 102 mutantes aplicados, 86 mortos, 16 sobreviventes, 5 deles equivalentes (q1, q2, q6, qu5, g3, cada um com a guarda nomeada). Contados: 86/97.

Fica para o humano, e não é Fix: a spec segue **sem aprovação do Lucas Scholz**, com as quatro open questions e as premissas sem `y` em *Confirmed?*. Ver "Gate Check".

---

**Date**: 2026-10-23
**Spec**: `.specs/features/b4-teste-e-qa/spec.md` (CRUZ-01, CRUZ-02, ROTEIRO-01, ROTEIRO-02, EXC-01, EVID-01, TELA-01, DONE-01). **Approved by: aguardando Lucas Scholz.**
**Diff range**: `9edb26c..9c3c2b4` (8 commits na branch `feat/b4-teste-e-qa`; `9edb26c` é a `main`, merge do PR #22).
**Escopo do trilho completo**:
- a Policy: `TaskPolicy::crossTest`, `undoCrossTest`, `runQa` (`TaskPolicy.php:97-122`), `AuditActionEnum::forAbility` (`AuditActionEnum.php:72`), o `authorize()` do `TaskTestController` e o do `CriterionResultRequest`;
- a migração aditiva `2026_10_23_000010_add_marcacao_to_task_acceptance_criteria.php`;
- as duas exclusões de verdade: `CriterionService::delete` (`CriterionService.php:111-129`) e `TaskUsageQuery::isUnused` (`TaskUsageQuery.php:43-68`).

Trilho leve, só para o contrato: a seção *Teste* da ficha, a evidência no item, o atalho *Devolver* e o `qa_summary`.

**Verifier**: agente separado de quem implementou, sem acesso ao raciocínio dele; julgado só pela spec, pelo diff e pelos testes.

### Gate Check (árvore real, HEAD `9c3c2b4`, sem edição)

| Comando | Saída |
|---|---|
| `composer lint` (`pint --test`) | `{"tool":"pint","result":"passed"}`, exit 0 |
| `npm run typecheck` (`tsc --noEmit`) | exit 0 |
| `npm run lint` (`eslint .`) | exit 0 |
| `node .ai/skills/conferir-specs.mjs` (antes deste relatório) | `conferir-specs: 8 feature(s), 0 problema(s)`, exit 0. Um aviso no B4, esperado: "open questions do not read as resolved" (a spec ainda tem as quatro perguntas) |
| `node .ai/skills/conferir-specs.mjs` (depois deste relatório) | ver "Depois do relatório", no fim |
| `php artisan test --compact` (na worktree descartável, no HEAD `9c3c2b4` sem mutação, depois do sensor; MySQL 8, `wcj_hub_test`) | **817 passed (6911 assertions)**, 147,10 s, exit 0 |
| Base do sensor (worktree, sem mutação) | os 11 arquivos pedidos (`TaskTestTest`, `TaskTestPolicyTest`, `TesteCruzadoConcorrenciaTest`, `MigracaoMarcacaoTest`, `TaskDetailTest`, `TaskDetailPolicyTest`, `BoardTest`, `TaskUsageQueryTest`, `TaskArchiveTest`, `B3FichaTransacaoTest`, `TaskServiceTest`): 134 passed (1122 assertions). Com `AuditActionEnumTest`, `TaskPolicyTest`, `TaskHistoryTest` e `TaskArchivePolicyTest` (o conjunto FULL): 190 passed (1613). As duas sondas contra o código real: 8 passed e 3 passed |

Não rodei o Playwright (o servidor de e2e é de outra sessão) nem o Storybook. TELA-01 foi julgada pela leitura do `e2e/teste-e-qa.spec.ts`, do organism e dos três prints em `prints/`.

### Spec-Anchored Acceptance Criteria

Abreviaturas: **TT** = `tests/Feature/Projetos/TaskTestTest.php`; **TP** = `TaskTestPolicyTest.php`; **CC** = `TesteCruzadoConcorrenciaTest.php`; **MM** = `MigracaoMarcacaoTest.php`; **TD** = `TaskDetailTest.php`; **BT** = `BoardTest.php`; **E2E** = `e2e/teste-e-qa.spec.ts`. O número entre parênteses é o mutante que morre ali (seção "Discrimination Sensor").

#### CRUZ-01: só outra pessoa marca o teste cruzado (P1, AC 1 a 10)

| Critério | Valor da spec | Evidência (`arquivo:linha` + asserção) | Resultado |
|---|---|---|---|
| AC 1: alocado que não é o dev marca → `cross_tested_by` e `cross_tested_at` | quem clicou; a hora do clique | TT:100 `assertSessionHas('success', 'Teste cruzado marcado.')`; TT:103 `assertSame($this->outroDev->id, …)`; TT:104 `assertSame('2026-10-23 17:30:00', …)` com o `setTestNow` (p6 em TT:100, s6 em TT:100) | PASS |
| AC 2: `updated` na auditoria do card, antes e depois dos dois campos, **na mesma transação** | uma linha `success/updated/task`; `[null, id]` | TT:107 os cinco campos da linha; TT:108 `[null, outroDev]`; TT:109-110 o `cross_tested_at` de antes nulo e o de depois só `assertNotNull` (s5 em TT:106, s8 em TT:108). **Mesma transação: sem prova.** A auditoria em `DB::afterCommit` passa (s7); a sonda t1 mata | ❌ GAP (Fix 2) |
| AC 3: tarefa sem dev → qualquer alocado marca | marcado | TT:118-122 | PASS |
| AC 4: o dev tenta → 403 | 403 | TT:127 `assertForbidden()`; TP:124 `assertOneDenied` (uma linha `denied/updated/task`) (p2, p3, p5 em TP:124 e TT:127) | PASS |
| AC 5: o dev tenta → os dois campos nulos | nulos | TT:128-129; TP:126 retrato igual | PASS |
| AC 6: o dev que gere tenta → 403 | 403 | TT:131-132 (o gestor é o dev) e TT:135-137 (o Administrador é o dev) | PASS |
| AC 7: quem não vê → 403 e `denied`/`updated` | 403; a linha negada | TP:109 `assertOneDenied(…, 'marcar')->assertForbidden()` nos três papéis do provider (dev não alocado, gestor que não gere, Comercial); TP:116 retrato (p1, p4, e1, e4, k1 em TP:109) | PASS |
| AC 8: já marcado → "Teste cruzado já marcado por Fulano: desfaça antes." | a mensagem com quem marcou | TT:153 `assertSessionHas('error', 'Teste cruzado já marcado por Carla Teste: desfaça antes.')` com o gestor clicando (s2 e s3 em TT:153); CC:58-60 | PASS |
| AC 9: já marcado → quem e quando da primeira | os da primeira | TT:156-157; CC:56 | PASS |
| AC 10: a ficha do dev → `abilities.cross_test` falso | false | TT:143 `assertFalse` (t1 em TT:143); TT:144-145 verdadeiro para outro dev e para o gestor; TD:101 | PASS |
| Premissa: o Service recusa o dev com a linha travada (`TaskPolicy.php:95`, `TaskService.php:117-118`) | `DomainException`, nada muda | **Nenhum teste.** Sem a recusa (`TaskService.php:126-128`), a suíte inteira passa (s1). A sonda d1 (o Service chamado com o dev) e a d1b (o dev trocado depois de o card ser lido) matam | ❌ GAP (Fix 1) |
| Success Criteria: dois cliques simultâneos gravam uma marca só | a outra espera; uma marca | CC:54 `rodou`, CC:55 `bloqueada`, CC:56 a primeira fica, CC:58-60 a seguinte é recusada (s4b e s4c em CC:54) | PASS (ver nota sobre a trava do projeto em "Code Quality") |
| Premissas: card arquivado recusa; projeto encerrado aceita | a mensagem; marcado | TT:160-168 (s4a em TT:165); TT:170-177 | PASS |

#### CRUZ-02: desfazer a marca (P1, AC 1 a 7)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: quem marcou desfaz → limpa os dois e `updated` | nulos; `[id, null]` | TT:184 a ability; TT:187 a mensagem; TT:190-191 nulos; TT:193 `['updated', [outroDev, null]]` (d2 em TT:187, d3 em TT:192). A premissa "A auditoria … Na mesma transação" vale aqui também: o `DB::afterCommit` passa (d5); a sonda t2 mata | ⚠️ PASS nos valores; transação no Fix 2 |
| AC 2: o gestor desfaz a marca de outra pessoa | nulos | TT:198-205, papel `gestor` (u7 em TT:201) | PASS |
| AC 3: o Administrador desfaz | nulos | TT:198-205, papel `admin` (u1 e u6 em TT:201) | PASS |
| AC 4: dev alocado que não marcou → 403 | 403 | TT:215; TP:133 `assertOneDenied` (u2, u3, u5 em TP:133; k2 em TT:215; t2 em TT:213) | PASS |
| AC 5: … → a marca fica | a marca | TT:217; TP:135 | PASS |
| AC 6: quem marcou e saiu do projeto → 403 | 403 | TT:225 (u4 em TT:225) | PASS |
| AC 7: sem marca → "Este card não tem teste cruzado marcado." | a mensagem | TT:233 (d1 em TT:233) | PASS |
| Negação de quem não vê | `denied/updated` | TP:110 (e2, k3 em TP:110) | PASS |
| Premissa: card arquivado não desfaz | "Card arquivado não muda: desarquive antes." | **Nenhum teste.** `undoCrossTested` com `lockTaskRow` (aceita o arquivado) passa (d4); a sonda d2 mata | ❌ GAP (Fix 3) |

#### ROTEIRO-01: o resultado de cada item (P1, AC 1 a 11)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: Aprovado → `passed`, `marked_by`, `marked_at` | `[2, quem, a hora]` | TT:245 `assertSame([PASSED, outroDev, '2026-10-23 17:30:00'], …)` (a constante `PASSED`, id 2) (c2, c3 em TT:245; c9 em TP:142) | PASS |
| AC 2: Reprovado → `failed` sem mover o card | `failed`; etapa igual; 0 movimentos | TT:263-265 | PASS |
| AC 3: A testar à mão → `pending` com quem e quando | `[1, quem]`; a hora | TT:275 `[PENDING, gestor]` (c4 em TT:275); TT:276 a hora só `assertNotNull` | PASS (a hora sem valor exato; o AC 1 a afirma) |
| AC 4: `updated` no critério, antes e depois, **na mesma transação** | uma linha; `[1, 2]`, `[null, id]` | TT:247-249 (c5 em TT:246, c16 em TT:249). **Mesma transação: sem prova.** `DB::afterCommit` passa (c7); a sonda t3 mata | ❌ GAP (Fix 2) |
| AC 5: o mesmo resultado → linha igual e nenhuma auditoria | iguais; contagem igual | TT:288-289 (c1 em TT:288) | PASS |
| AC 6: o subitem → o pai igual | `passed` no pai | TT:299-300 (c8 em TT:300) | PASS |
| AC 7: quem não vê → 403 | 403, `denied/updated` | TP:111 (r1, e3 em TP:111) | PASS |
| AC 8: quem não vê → o resultado fica | igual | TP:116 retrato (`test_result_id`, `marked_by`, `marked_at`) | PASS |
| AC 9: critério de outro card → 404 | 404 | TT:318 `assertNotFound()`; TT:320 o outro critério igual. O 404 sai do `scopeBindings()` do grupo (`routes/web.php:144`), antes do FormRequest; o `abort_if` de `CriterionResultRequest.php:22` é uma segunda guarda (q1, q2 equivalentes) | PASS |
| AC 10: resultado fora de `pending`/`approved`/`failed` → erro no campo `result` | erro no campo | TT:307-308 com `passed` e a mensagem exata (q3, q5 em TT:308); TT:310 nada muda. **Sem `result` no corpo, 500**: tirar o `required` passa (q4); a sonda q4 mata | ⚠️ GAP (Fix 4) |
| AC 11: card arquivado → "Card arquivado não muda: desarquive antes." | a mensagem; nada muda | TT:329, TT:331 (c6 em TT:329) | PASS |
| Premissa: qualquer alocado, inclusive o dev da tarefa | marcado | TP:140-142 (r2, r3 em TP:142) | PASS |

#### ROTEIRO-02: a volta para A testar limpa a última marcação (P1, AC 1 a 3)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: editar o texto de item testado → A testar, sem `marked_*` | `[1, null, null]`; `marked` nulo na ficha | TT:344, TT:345 (c10, c11 em TT:344) | PASS |
| AC 2: retrabalho com origem e motivo → todos A testar, sem `marked_*` | `[1, null, null]` nos dois | TT:362-366 (m1, m2 em TT:364) | PASS |
| AC 3: volta marcada como engano → mantém resultados, marcações e teste cruzado | iguais | TT:380-381 (m3 em TT:380) | PASS |
| Success Criteria: migração com `down()`, `up()` de novo e idempotente | colunas e FK saem e voltam; nulo nas linhas de antes | MM:56-69 (g1 em MM:56; g2, g4 em MM:64). g3 (FK `cascade` em vez de `restrict`) é equivalente (ver o sensor) | PASS |

#### EXC-01: exclusão de verdade não apaga teste (P1, AC 1 a 6)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: critério A testar com evidência → "CA_3 já foi testado: edite em vez de excluir." | a mensagem | TT:433-434 (c12, c15 em TT:434) | PASS |
| AC 2: subitem A testar com evidência → recusa | a mensagem | TT:447-448 (c13, c14 em TT:448) | PASS |
| AC 3: recusada → critério, subitens e evidência ficam | existem | TT:436-437, TT:450-451 | PASS |
| AC 4: card nunca movido com item de resultado ≠ A testar → usado | `isUnused` falso | TT:462-463 só com **Aprovado** (qu1, qu3 em TT:463; qu4 em TT:460). Contar só o Aprovado passa (qu6); a sonda qu6 (Reprovado) mata | ⚠️ GAP (Fix 5) |
| AC 5: item com `marked_at` → usado | falso | TT:465-466 (qu2 em TT:466) | PASS |
| AC 6: quem gere exclui o card com item marcado → "SGM-17 já foi usado: arquive em vez de excluir." | a mensagem; o card fica | TT:468-470 | PASS |

#### EVID-01: evidência no item (P2, AC 1 a 5)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: envio com `criterion_id` do card → anexo ligado ao critério | o id do critério; quem enviou; a auditoria `created` com o id | TT:395 (a4, a6 em TT:395); TT:396 (a5 em TT:396). A auditoria do anexo em `DB::afterCommit` passa (a7); a sonda t4 mata | ⚠️ PASS nos valores; transação no Fix 2 |
| AC 2: a ficha mostra a evidência com `criterion_id` | o id | TT:397 (t10 em TT:397) | PASS |
| AC 3: critério de outro card → erro no campo `criterion_id` | "Escolha um critério deste card." | TT:405-407 (a1, a2 em TT:407) | PASS |
| AC 4: … → a quantidade de anexos fica | 0; nenhum arquivo no disco | TT:409-410 | PASS |
| AC 5: excluir a evidência → arquivo e registro saem | 0; arquivo ausente | TT:419-422 | PASS |
| Negação de quem não vê | `denied/updated` | TP:112-114 | PASS |
| Premissa: card arquivado não recebe evidência | a mensagem | **Nenhum teste.** `lockTaskRow` no `upload` (a3) e o `upload` sem trava nenhuma (a3b) passam; a sonda a1 mata os dois | ❌ GAP (Fix 3) |

#### TELA-01: a seção *Teste* da ficha (P2, AC 1 a 8; e2e lido, não rodado)

| Critério | Evidência | Resultado |
|---|---|---|
| AC 1: situação do teste cruzado e o resumo do roteiro | E2E:40 "Ainda não marcado"; E2E:41 e :54 o resumo exato; E2E:45 `/^Testado por E2E Admin em \d{2}\/\d{2}\/\d{4} às \d{2}:\d{2}$/`; contrato TT:113 (`'2026-10-23 14:30'`, t6 e t7 em TT:113) | PASS pela leitura |
| AC 2: número, texto, resultado, quantidade de evidências, última marcação | E2E:49-52 (grupo "Resultado de CA_1", `aria-pressed`); E2E:56 a marcação; E2E:83 "1 evidência"; contrato TT:251-253 e TD:104 (t4, t5, t8 em TT:253; t9 em TT:252; t11 no benchmark TD:125) | PASS pela leitura |
| AC 3: item reprovado em destaque | E2E:55 `li[data-result="failed"]` (o destaque é a classe em `w-task-test-section-organism.tsx:166`) | PASS pela leitura |
| AC 4: sem critério → "Esta tarefa não tem critérios de aceite." + *Novo critério* que abre a criação | só a story `SemCriterio`, sem `play`; código em `w-task-test-section-organism.tsx:320-329` e `w-task-detail-page.tsx` (`onNewCriterion`) | ⚠️ sem asserção (Fix 7) |
| AC 5: *Devolver* do teste cruzado → To do, origem "Teste cruzado" | nenhum teste; o e2e clica só o último *Devolver* (E2E:69). Código: `returnButton('cross_test')` (`…organism.tsx:312`) e `returnToDevelopment` pelo slug | ⚠️ sem asserção (Fix 7) |
| AC 6: *Devolver* do roteiro → To do, origem "QA" | E2E:69-71 (o diálogo `Devolver <prefixo>-1 para To do`, `Origem` = "QA") | PASS pela leitura |
| AC 7: "Desfazer a marca de teste cruzado de Fulano?" antes de enviar | só a story `DesfazendoAMarca` (clica, não afirma); `undoCrossTestText` em `lib/ficha.ts` | ⚠️ sem asserção (Fix 7) |
| AC 8: botões da seção desabilitados durante a escrita | pela leitura: `disabled={busy}` em `…organism.tsx:148`, `:183`, `:226`, `:260`, `:302`, `:308`, `:352`; story `Gravando` | ⚠️ sem asserção (Fix 7) |
| Abilities da ficha | `cross_test`: TT:143-145, TD:101; `undo_cross_test`: TT:184, TT:213, TD:102 (t2, t2b); `run_qa`: TD:103 só no card **sem dev**, em que `run_qa` e `cross_test` coincidem. `run_qa` decidido pela `crossTest` (esconde o roteiro do dev da tarefa) passa (t3b); a sonda t3b mata | ⚠️ GAP (Fix 6) |

#### DONE-01: o aviso do Done no quadro (P2, AC 1 a 3)

| Critério | Valor da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: `qa_summary` com reprovados e a testar, pai e subitem um a um | `{rejected: 2, pending: 1}`; `{0, 0}` | BT:264 (subitem reprovado conta; b2, b3, b4, b5, b7, b8 em BT:264); BT:265 | PASS |
| AC 2: sem critério → nulo | null | BT:266 (b1 em BT:266) | PASS |
| AC 3: 500 cards → uma consulta só | o número não cresce | BT:269-284 com um critério por card (BT:302). Consulta por card no presenter: 531 em vez de 36 (b6 em BT:284). Pela leitura, as três contagens são subconsultas do mesmo select (`BoardQuery.php:58-60`): +0 consultas sobre a `main` | PASS |

### Discrimination Sensor

**Método.** Mutação à mão, sem Infection, numa worktree descartável (`git worktree add /tmp/tlc-b4-teste-e-qa-1 HEAD`), nunca na árvore real e nunca com `git stash`. O script `/tmp/verifier-b4-pr1/run.py` grava `results.json`, `run-log.txt`, a saída de cada mutante em `out-<id>.txt` e a da sonda em `sonda-<id>.txt`. Para cada mutante, o script:
1. confere o arquivo limpo na worktree;
2. aplica a troca exata (falha se o trecho não for único);
3. roda `php -l` e `php artisan test --compact --filter=<conjunto>`;
4. nos mutantes marcados, roda também a sonda;
5. reverte com `git checkout -- <arquivo>` e confere limpo de novo.

Antes do primeiro mutante, o script confere que a classe carregada é a da worktree (`ReflectionClass::getFileName()`). `git status --porcelain` da worktree estava vazio antes e saiu vazio no fim (`porcelain final: ''`). A árvore real não foi tocada (`git status --porcelain` vazio no fim).

**Achado do método (a 1ª tentativa não valeu).** Com o `vendor/` ligado por `ln -s`, como o `especificacao.md` §5 item 8 sugere, os dois primeiros mutantes (p1, p2) "sobreviveram". O motivo: o autoload do Composer usa `__DIR__`, que o PHP resolve para o caminho real do symlink, e as classes `App\` e `Tests\` vinham da árvore real, não da worktree. Parei, troquei o symlink por uma cópia (`cp -a vendor`), confirmei pelo `getFileName()` e rodei tudo de novo. A base sem mutação (134 e 190 passed) e as sondas foram rodadas de novo depois da troca. O `node_modules` ficou em symlink, porque o sensor não roda TypeScript.

Conjuntos (todos com **CORE** = `TaskTestTest|TaskTestPolicyTest|TesteCruzadoConcorrenciaTest`):
- **POL** = CORE + `TaskDetailTest`, `TaskDetailPolicyTest`, `TaskPolicyTest`, `AuditActionEnumTest`;
- **SVC** = CORE + `TaskServiceTest`, `TaskDetailTest`;
- **CRIT** e **ATT** = CORE + `TaskDetailTest`, `B3FichaTransacaoTest`, `TaskDetailPolicyTest`;
- **MOVE** = CORE + `TaskServiceTest`;
- **USE** = CORE + `TaskUsageQueryTest`, `TaskArchiveTest`, `TaskArchivePolicyTest`, `TaskDetailTest`;
- **PRES** = CORE + `TaskDetailTest`, `TaskDetailPolicyTest`;
- **BOARD** = CORE + `BoardTest`, `TaskArchiveTest`;
- **MIG** = CORE + `MigracaoMarcacaoTest`;
- **FULL** = os 11 arquivos pedidos + `AuditActionEnumTest`, `TaskPolicyTest`, `TaskHistoryTest`, `TaskArchivePolicyTest` (190 testes).

Os 11 sobreviventes não equivalentes rodaram de novo no FULL: os 11 passaram (190 passed cada).

Mutação completa (todos os ramos) nas regras de permissão e de exclusão de verdade; de 1 a 3 nas demais.

| # | Mutante | `arquivo:linha` | Conjunto | Saída | Quem matou |
|---|---|---|---|---|---|
| p1 | `crossTest` sem `view` | `TaskPolicy.php:99` | POL | morto | TP:109 ("marcar": nenhuma linha `denied`) |
| p2 | `crossTest` sem "não é o dev" | `TaskPolicy.php:99` | POL | morto | TP:124; TT:127 (302 em vez de 403) |
| p3 | `crossTest` com `===` | `TaskPolicy.php:99` | POL | morto | TD:101; TP:124 |
| p4 | `crossTest` com `\|\|` | `TaskPolicy.php:99` | POL | morto | TP:109 |
| p5 | `crossTest` compara com `qa_id` | `TaskPolicy.php:99` | POL | morto | TP:124; TT:127 |
| p6 | `crossTest` pede `manage` | `TaskPolicy.php:99` | POL | morto | TD:101; TT:100 (403) |
| u1 | `undoCrossTest` sem o ramo de quem gere | `TaskPolicy.php:108-110` | POL | morto | TT:201 (papel `gestor`) |
| u2 | ramo de quem gere com `view` | `TaskPolicy.php:108` | POL | morto | TD:102; TP:133 |
| u3 | sem "foi quem marcou" | `TaskPolicy.php:112` | POL | morto | TD:102; TP:133 |
| u4 | sem "ainda vê o projeto" | `TaskPolicy.php:112` | POL | morto | TT:225 (302 em vez de 403) |
| u5 | `!==` em "quem marcou" | `TaskPolicy.php:112` | POL | morto | TD:102; TP:133 |
| u6 | ramo de quem gere só o gestor do projeto (sem o Administrador) | `TaskPolicy.php:108` | POL | morto | TT:201 (papel `admin`) |
| u7 | ramo de quem gere só o Administrador | `TaskPolicy.php:108` | POL | morto | TT:201 (papel `gestor`) |
| r1 | `runQa` devolve `true` | `TaskPolicy.php:121` | POL | morto | TP:111 ("resultado") |
| r2 | `runQa` pede `manage` | `TaskPolicy.php:121` | POL | morto | TD:103; TP:142 |
| r3 | `runQa` exclui o dev da tarefa | `TaskPolicy.php:121` | POL | morto | TP:142 |
| e1 | `forAbility` sem `crossTest` | `AuditActionEnum.php:72` | POL | morto | TP:109 (ação `crossTest` em vez de `updated`) |
| e2 | sem `undoCrossTest` | `AuditActionEnum.php:72` | POL | morto | TP:110 |
| e3 | sem `runQa` | `AuditActionEnum.php:72` | POL | morto | TP:111 |
| e4 | as três como `MOVED` | `AuditActionEnum.php:72` | POL | morto | TP:109 |
| k1 | `markCrossTest` sem `authorize` | `TaskTestController.php:29` | POL | morto | TP:109 |
| k2 | `undoCrossTest` autoriza `crossTest` | `TaskTestController.php:36` | POL | morto | TT:215 |
| k3 | `undoCrossTest` sem `authorize` | `TaskTestController.php:36` | POL | morto | TP:110 |
| q1 | sem o `abort_if` do critério de outro card | `CriterionResultRequest.php:22` | CRIT | equivalente | o `{acceptanceCriterion}` está no grupo `Route::scopeBindings()` (`routes/web.php:144`), resolvido por `Task::acceptanceCriteria()`: o 404 sai no binding, antes do FormRequest. TT:318 passa com o mutante |
| q2 | [extra] o `abort_if` depois da Policy (no `rules()`) | `CriterionResultRequest.php:22` | CRIT | equivalente | a mesma guarda: o binding devolve 404 antes da Policy |
| q3 | `Rule::in` aceita `passed` | `CriterionResultRequest.php:32` | CRIT | morto | TT:308 (500, sem erro no campo) |
| q4 | [extra] `result` sem `required` | `CriterionResultRequest.php:32` | CRIT, FULL | **sobreviveu** | nenhum; sem `result`, o `result(): string` recebe nulo e a resposta é 500. A sonda q4 mata. Fix 4 |
| q5 | mensagem do `result.in` trocada | `CriterionResultRequest.php:45` | CRIT | morto | TT:308 |
| q6 | [extra] autoriza `update` em vez de `runQa` | `CriterionResultRequest.php:24` | CRIT | equivalente | as duas são `can('view', project)` (`TaskPolicy.php:46-49` e `:119-122`) e as duas negam como `updated` (`AuditActionEnum.php:60` e `:72`). Se a `runQa` mudar, deixa de ser |
| s1 | `markCrossTested` sem a recusa do dev | `TaskService.php:126-128` | SVC, FULL | **sobreviveu** | nenhum (todo teste chega pelo HTTP, e a Policy barra antes). A sonda d1 e a d1b matam. Fix 1 |
| s2 | sem a recusa da marca repetida | `TaskService.php:129` | SVC | morto | TT:153; CC:58-60 |
| s3 | a recusa cita quem clicou | `TaskService.php:130` | SVC | morto | TT:153 ("Ana Gestora" em vez de "Carla Teste") |
| s4a | `lockTaskRow` (aceita o arquivado) | `TaskService.php:124` | SVC | morto | TT:165 |
| s4b | relê o card sem `FOR UPDATE` | `TaskService.php:124` | SVC | morto | CC:54 ("A linha do card não foi travada") |
| s4c | sem trava nenhuma (projeto e card) | `TaskService.php:123-124` | SVC | morto | CC:54 |
| s5 | sem auditoria | `TaskService.php:137` | SVC | morto | TT:106 |
| s6 | sem `cross_tested_at` | `TaskService.php:136` | SVC | morto | TT:100 (500: `tasks_cross_test_check`, os dois campos juntos ou nenhum, erro 3819); TT:120 |
| s7 | auditoria em `DB::afterCommit` | `TaskService.php:137` | SVC, FULL | **sobreviveu** | nenhum (L-017). A sonda t1 mata. Fix 2 |
| s8 | [extra] o "antes" lido depois de gravar | `TaskService.php:135-137` | SVC | morto | TT:108 |
| d1 | `undoCrossTested` sem a recusa sem marca | `TaskService.php:150` | SVC | morto | TT:233 |
| d2 | não limpa `cross_tested_at` | `TaskService.php:155` | SVC | morto | TT:187; TT:201 |
| d3 | sem auditoria | `TaskService.php:156` | SVC | morto | TT:192 |
| d4 | `lockTaskRow` (aceita o arquivado) | `TaskService.php:148` | SVC, FULL | **sobreviveu** | nenhum. A sonda d2 mata. Fix 3 |
| d5 | auditoria em `DB::afterCommit` | `TaskService.php:156` | SVC, FULL | **sobreviveu** | nenhum (L-017). A sonda t2 mata. Fix 2 |
| c1 | `setResult` sem o "mesmo resultado não grava" | `CriterionService.php:95-97` | CRIT | morto | TT:288 |
| c2 | sem `marked_by` | `CriterionService.php:100` | CRIT | morto | TT:245; TT:275 |
| c3 | sem `marked_at` | `CriterionService.php:100` | CRIT | morto | TT:245; TT:276 |
| c4 | A testar à mão não grava quem e quando | `CriterionService.php:100` | CRIT | morto | TT:275 |
| c5 | sem auditoria | `CriterionService.php:101` | CRIT | morto | TT:246 |
| c6 | `lockTaskRow` (aceita o arquivado) | `CriterionService.php:91` | CRIT | morto | TT:329 |
| c7 | auditoria em `DB::afterCommit` | `CriterionService.php:101` | CRIT, FULL | **sobreviveu** | nenhum (L-017). A sonda t3 mata. Fix 2 |
| c8 | o subitem marca o pai também | `CriterionService.php:100` | CRIT | morto | TT:300 |
| c9 | `RESULTS` com `approved` e `failed` trocados | `CriterionService.php:28` | CRIT | morto | TD:84 (`criteria.0.result`); TP:142 |
| c10 | `update` não limpa a marcação | `CriterionService.php:71` | CRIT | morto | TT:344 |
| c11 | `update` limpa só `marked_by` | `CriterionService.php:71` | CRIT | morto | TT:344 |
| c12 | `delete` sem a guarda da evidência | `CriterionService.php:120` | CRIT | morto | TT:434 (o 1451 da FK vira 500); TT:448 |
| c13 | evidência só no item | `CriterionService.php:119-120` | CRIT | morto | TT:448 |
| c14 | sem o `loadExists` dos subitens | `CriterionService.php:117` | CRIT | morto | TT:448 |
| c15 | evidência só nos subitens | `CriterionService.php:120` | CRIT | morto | TT:434 |
| c16 | [extra] snapshot sem `marked_by` | `CriterionService.php:142` | CRIT | morto | TT:249 |
| m1 | retrabalho não limpa a marcação | `TaskMoveService.php:92` | MOVE | morto | TT:364 |
| m2 | retrabalho limpa só `marked_by` | `TaskMoveService.php:92` | MOVE | morto | TT:364 |
| m3 | todo movimento (inclusive o engano) limpa a marcação | `TaskMoveService.php:95` | MOVE | morto | TT:380 |
| a1 | critério procurado sem `task_id` (aceita o de outro card) | `AttachmentService.php:52` | ATT | morto | TT:407 |
| a2 | sem a recusa do critério | `AttachmentService.php:53` | ATT | morto | TT:407 |
| a3 | `lockTaskRow` (aceita o arquivado) | `AttachmentService.php:51` | ATT, FULL | **sobreviveu** | nenhum. A sonda a1 mata. Fix 3 |
| a3b | sem trava nenhuma com critério | `AttachmentService.php:50-51` | ATT, FULL | **sobreviveu** | nenhum. A sonda a1 mata (o arquivado entra). Fix 3 |
| a4 | não grava `task_acceptance_criterion_id` | `AttachmentService.php:67` | ATT | morto | TT:395 |
| a5 | auditoria do anexo sem o critério | `AttachmentService.php:71` | ATT | morto | TT:396 |
| a6 | `criterionId()` devolve nulo | `StoreAttachmentsRequest.php:41` | ATT | morto | TT:395; TT:407 |
| a7 | auditoria do anexo em `DB::afterCommit` | `AttachmentService.php:69-72` | ATT, FULL | **sobreviveu** | nenhum (L-017). A sonda t4 mata. Fix 2 |
| qu1 | `isUnused` sem "resultado ≠ A testar" | `TaskUsageQuery.php:54` | USE | morto | TT:463 |
| qu2 | sem "com `marked_at`" | `TaskUsageQuery.php:54` | USE | morto | TT:466 |
| qu3 | ignora `marked_criteria_exists` | `TaskUsageQuery.php:57` | USE | morto | TT:463 |
| qu4 | `=` em vez de `<>` | `TaskUsageQuery.php:54` | USE | morto | TT:460; `TaskArchiveTest.php:211` |
| qu5 | [extra] `marked_by` em vez de `marked_at` | `TaskUsageQuery.php:54` | USE | equivalente | as duas colunas nascem nulas (migração), se gravam juntas (`CriterionService.php:100`) e se limpam juntas (`CriterionService.php:71`, `TaskMoveService.php:92`); nenhum caminho grava uma sem a outra |
| qu6 | [extra] só Aprovado conta | `TaskUsageQuery.php:54` | USE, FULL | **sobreviveu** | nenhum. A sonda qu6 (Reprovado) mata. Fix 5 |
| t1 | `abilities.cross_test` pela `runQa` | `TaskDetailPresenter.php:167` | PRES | morto | TT:143 |
| t2 | `undo_cross_test` pela `crossTest` | `TaskDetailPresenter.php:168` | PRES | morto | TD:102; TT:213 |
| t2b | `undo_cross_test` só para quem gere | `TaskDetailPresenter.php:168` | PRES | morto | TT:184 |
| t3 | `run_qa` falso | `TaskDetailPresenter.php:169` | PRES | morto | TD:103 |
| t3b | `run_qa` pela `crossTest` | `TaskDetailPresenter.php:169` | PRES, FULL | **sobreviveu** | nenhum. A sonda t3b (a ficha do dev da tarefa) mata. Fix 6 |
| t4 | `marked` sempre nulo | `TaskDetailPresenter.php:213` | PRES | morto | TT:253 |
| t5 | `marked.at` sem a hora | `TaskDetailPresenter.php:214` | PRES | morto | TT:253 |
| t6 | `cross_tested.at` sem a hora (o de antes) | `TaskDetailPresenter.php:136` | PRES | morto | TT:113 |
| t7 | `cross_tested.at` em UTC | `TaskDetailPresenter.php:136` | PRES | morto | TT:113 |
| t8 | `marked.at` em UTC | `TaskDetailPresenter.php:214` | PRES | morto | TT:253 |
| t9 | `result` com o slug do banco (`passed`) | `TaskDetailPresenter.php:212` | PRES | morto | TD:84; TT:252 |
| t10 | anexo sem `criterion_id` | `TaskDetailPresenter.php:117` | PRES | morto | TT:397 |
| t11 | sem o eager load de `acceptanceCriteria.markedBy` | `TaskDetailPresenter.php:66` | PRES | morto | TD:125 (76 consultas em vez de 46) |
| b1 | `qa_summary` nunca nulo | `TaskPresenter.php:88` | BOARD | morto | BT:266; `BoardTest.php:93` (o contrato do card no `test_the_card_carries_every_zone_of_the_contract`) |
| b2 | sem `criteria_count` | `BoardQuery.php:58` | BOARD | morto | BT:264 |
| b3 | reprovados contam A testar | `BoardQuery.php:59` | BOARD | morto | BT:264 |
| b4 | a testar contam reprovados | `BoardQuery.php:60` | BOARD | morto | BT:264 |
| b5 | reprovados só no pai | `BoardQuery.php:59` | BOARD | morto | BT:264 |
| b6 | `qa_summary` por consulta por card | `TaskPresenter.php:89` | BOARD | morto | BT:284 (531 consultas em vez de 36) |
| b7 | a testar incluem reprovados | `BoardQuery.php:60` | BOARD | morto | BT:264 |
| b8 | `rejected` e `pending` trocados | `TaskPresenter.php:89` | BOARD | morto | BT:264 |
| g1 | `down` sem `dropForeign` | migração `:35` | MIG | morto | MM:56 (1828) |
| g2 | `up` sem a guarda de `marked_by` | migração `:19` | MIG | morto | MM:64 (1060) |
| g3 | [extra] FK `marked_by` em `cascade` | migração `:21` | MIG | equivalente | pessoa referenciada não se exclui: `UserService::delete` → `deleteIfUnused` → `User::hasHistory()` → `Deactivatable::isReferenced()`, que lê toda FK para `users` no catálogo (`Deactivatable.php:40-74`). O `ON DELETE` nunca dispara pelo sistema |
| g4 | `up` sem a guarda de `marked_at` | migração `:24` | MIG | morto | MM:64 (1060) |

**Total**: 102 aplicados (8 deles marcados [extra], fora dos alvos pedidos), 86 mortos, 16 sobreviventes, dos quais 5 equivalentes (q1, q2, q6, qu5, g3) e 11 não equivalentes (s1, s7, d4, d5, c7, a3, a3b, a7, q4, qu6, t3b). Contados: **86/97**.
Por família: Policy, enum e controller 23/23; `TaskService` 11/15; `CriterionService` 15/16; `TaskMoveService` 3/3; `AttachmentService` e `StoreAttachmentsRequest` 5/8; `TaskUsageQuery` 4/5 (mais 1 equivalente); `CriterionResultRequest` 2/3 (mais 3 equivalentes); `TaskDetailPresenter` 12/13; quadro 8/8; migração 3/3 (mais 1 equivalente).
**Sensor depth**: P0, manual.

### Sondas (rascunho em `/tmp/verifier-b4-pr1/`, fora do repositório)

Rodadas com `php artisan test --compact /tmp/verifier-b4-pr1/<arquivo>` na worktree, contra o código real (todas passam) e contra cada mutante sobrevivente (o `run.py` roda a sonda logo depois da suíte, com o mutante aplicado).

`SondaB4Test.php` (`DatabaseTruncation` + `UsaBancoDeVerdade`, sem `RefreshDatabase`; `transactionLevel()` 0 no início):

| Sonda | O que faz | Código real | Mutante |
|---|---|---|---|
| t1 | `TaskService` com um `AuditService` que lança no `log` → `markCrossTested` falha e `cross_tested_by` fica nulo | passa | s7: falha em `:102` (a marca ficou gravada, a auditoria caiu depois do commit) |
| t2 | idem no `undoCrossTested` → a marca fica | passa | d5: falha em `:112` |
| t3 | `CriterionService` com a auditoria que lança → `setResult` falha e o item fica `[1, null, null]` | passa | c7: falha em `:122` |
| t4 | `AttachmentService` com a auditoria que lança → `upload` com critério falha, 0 anexos e nenhum arquivo | passa | a7: falha em `:131` |
| d1 | `TaskService::markCrossTested($task, $dev)` direto → `DomainException` com `OWN_CROSS_TEST` e nada marcado | passa | s1: falha em `:139` (o dev marcou o próprio teste cruzado) |
| d1b | o card lido sem dev; outra escrita põe o Outro como dev antes da trava; o Outro marca → recusado | passa | s1: falha em `:155` |
| a1 | evidência no card arquivado → "Card arquivado não muda: desarquive antes.", 0 anexos | passa | a3 e a3b: falham em `:167` |
| d2 | desfazer no card arquivado → a mesma mensagem, a marca fica | passa | d4: falha em `:181` |

`SondaB4bTest.php` (`RefreshDatabase`):

| Sonda | O que faz | Código real | Mutante |
|---|---|---|---|
| q4 | `POST …/resultado` sem `result` → `assertSessionHasErrors('result')` | passa | q4: falha em `:30` (sem erro na sessão: 500) |
| qu6 | card nunca movido com um item **Reprovado** → `isUnused` falso | passa | qu6: falha em `:40` |
| t3b | a ficha do dev da tarefa → `run_qa` verdadeiro e `cross_test` falso | passa | t3b: falha em `:52` |

### Code Quality (escopo do trilho completo)

| Princípio | Status |
|---|---|
| Minimum code / Surgical changes | ✅ Três abilities de uma linha, dois métodos no `TaskService`, um no `CriterionService`, um Controller de 45 linhas, um FormRequest; a migração tem duas colunas nulas |
| Matches patterns | ✅ Projeto → card travados (`WritesTasks::lockTask`, que também recusa o arquivado), `DomainException` vira toast pelo `attempt()`, `ActsOnCard` autoriza antes de validar, auditoria pelo `AuditService::log`, `forAbility` com a ação certa, migração idempotente com `hasColumn` (molde da `2026_10_16_000040`) |
| Defense in depth | ✅ A ficha esconde o botão e a Policy recusa; o Service recusa de novo o dev com a linha travada; o critério de outro card cai no binding e no `abort_if` |
| Spec-anchored outcome check | ⚠️ Os valores são afirmados exatos (mensagens, ids, `'2026-10-23 17:30:00'`, `'14:30'` em São Paulo). Faltam a transação (Fix 2), a recusa do Service (Fix 1), o arquivado (Fix 3), o `result` ausente (Fix 4), o Reprovado (Fix 5), o `run_qa` do dev (Fix 6) e quatro AC da tela (Fix 7) |
| Every test maps to a spec requirement | ✅ Os docblocks citam CRUZ/ROTEIRO/EVID/EXC/DONE; o `semeiaCards` e o `semeiaItens` ganharam critério e marcação para o benchmark contar o caso novo |

Achados que não bloqueiam (detalhe):
- **O `abort_if` do `CriterionResultRequest` (`:22`) não é o que dá o 404.** As rotas do card estão dentro do `Route::scopeBindings()` (`routes/web.php:144`), e o `{acceptanceCriterion}` é procurado por `Task::acceptanceCriteria()`: o 404 sai no binding (q1 e q2 equivalentes). O mesmo vale para o `CardTextRequest` do B3. O comentário do Request ("o critério de outro card é 404, antes da Policy") continua certo; só a origem é outra.
- **`TaskDetailPresenter.php:66` ainda carrega `acceptanceCriteria.testResult`**, que deixou de ser lido (o `result` sai de `CriterionService::RESULTS`, `:212`). É uma consulta a mais por ficha, constante.
- **O teste de concorrência prova a consulta, não a necessidade da trava do card.** O gatilho do CC é o `FOR UPDATE` em `tasks`, e a outra requisição espera já no `SprintService::lockProject` (`SprintService.php:239`), a primeira trava dela. Tirar só o `FOR UPDATE` do card (s4b) morre porque o gatilho não casa (`rodou` falso, CC:54), mas a trava do projeto, sozinha, já serializaria as duas marcas. Nada a corrigir; vale saber ao ler o teste.
- **`undoCrossTested` não confere de novo, com a linha travada, quem marcou.** A Policy decide pelo card lido na rota. Se, entre o `authorize()` e a trava, o gestor desfizer e outra pessoa marcar, o desfazer em voo de quem marcou antes limpa a marca nova. É raro, e a spec não pede; o `markCrossTested` faz a conferência equivalente para o dev.
- **Mensagem fora da spec:** `TaskService::OWN_CROSS_TEST` ("Você é o dev desta tarefa: o teste cruzado é de outra pessoa.") só aparece na corrida acima; pelo HTTP, o dev recebe 403.
- **CRUZ-01 AC 2 e ROTEIRO-01 AC 3:** a hora do `cross_tested_at` na auditoria (TT:110) e a do A testar à mão (TT:276) são afirmadas só como `assertNotNull`. O `setTestNow` já está no `setUp`; a hora exata custa uma linha. Não abri Fix, porque os AC 1 afirmam a hora exata do mesmo código.

### Fix Plans

Todos só de teste. Nenhum código de produção precisa mudar.

**Fix 1: CRUZ-01, o Service recusa o dev com a linha travada** (mata s1; Major, regra de permissão)
- Um teste que chama `app(TaskService::class)->markCrossTested($task, $dev)` e afirma a `DomainException` com `TaskService::OWN_CROSS_TEST` e `cross_tested_by` nulo.
- E o caso da corrida: o card lido sem dev, o dev trocado no banco antes da chamada, a recusa igual (molde das sondas d1 e d1b).

**Fix 2: "na mesma transação"** (mata s7, d5, c7, a7; Major; L-017 pela 4ª feature)
- Num arquivo `B4TransacaoTest` com `DatabaseTruncation` + `UsaBancoDeVerdade`, sem `RefreshDatabase`, `transactionLevel()` 0 no início, no molde do `B3FichaTransacaoTest` (`failingAudit()` e `failure()`):
  - `TaskService` com a auditoria que lança: marcar não grava; desfazer não limpa (CRUZ-01 AC 2; premissa "A auditoria");
  - `CriterionService::setResult`: o item fica `[1, null, null]` (ROTEIRO-01 AC 4);
  - `AttachmentService::upload` com critério: 0 anexos e nenhum arquivo no disco (premissa "o `created` do anexo").
- As sondas t1 a t4 fazem exatamente isso.

**Fix 3: card arquivado, desfazer e evidência** (mata d4, a3, a3b; Minor, premissa "Card arquivado")
- `POST …/teste-cruzado/desfazer` no card arquivado com marca: "Card arquivado não muda: desarquive antes." e a marca fica.
- `POST …/anexos` com `criterion_id` no card arquivado: a mesma mensagem e 0 anexos.

**Fix 4: ROTEIRO-01 AC 10, o `result` ausente** (mata q4; Minor)
- Em `test_an_unknown_result_is_refused_on_the_field` (TT:303-311), um caso sem `result` (ou `''`) com `assertSessionHasErrors('result')`.

**Fix 5: EXC-01 AC 4, o Reprovado** (mata qu6; Minor)
- Em `test_a_card_with_a_marked_item_is_used` (TT:454-471), o item também com o id do Reprovado (a constante `FAILED`), sem `marked_at`, com `assertFalse($usage->isUnused($novo))`.

**Fix 6: `abilities.run_qa` do dev da tarefa** (mata t3b; Minor)
- Em `test_the_ability_hides_the_button_for_the_developer_only` (TT:141-146), `assertTrue($this->ficha($this->dev)['abilities']['run_qa'])`: o dev não marca o teste cruzado, mas roda o roteiro.

**Fix 7: TELA-01 AC 4, 5, 7 e 8** (Minor, trilho leve)
- No `e2e/teste-e-qa.spec.ts`:
  - o *Devolver* do bloco do teste cruzado abre o diálogo com a origem "Teste cruzado" (AC 5);
  - *Desfazer a marca* abre a confirmação "Desfazer a marca de teste cruzado de E2E Admin?" (AC 7);
  - num card sem critério, o texto "Esta tarefa não tem critérios de aceite." e o *Novo critério* abrindo a criação (AC 4).
- AC 8: um `play` na story `Gravando` que afirme os botões desabilitados, ou a nota no PR de que vale pela leitura (`disabled={busy}` nas sete linhas citadas na tabela).

#### Para o humano decidir (não é Fix Plan)

- **A spec não está aprovada.** `Approved by: aguardando Lucas Scholz`, com as quatro open questions e as premissas sem `y` em *Confirmed?*. O trilho completo pede a aprovação antes do código. O código implementa os padrões escolhidos:
  - o dev da tarefa que gere não marca;
  - as 2 colunas da migração;
  - o critério com evidência não se exclui;
  - o card com item marcado não é "nunca usado".
- **A regra da worktree do sensor (`.ai/core/especificacao.md` §5 item 8).** "Ligue esses itens da árvore real, com `ln -s`" não serve para o `vendor/` do PHP: o autoload do Composer resolve o `__DIR__` pelo caminho real, e o mutante da worktree nunca carrega. Todo mutante "sobrevive" sem o Verifier perceber, a não ser por um mutante que tinha de morrer. Sugestão de texto: "no PHP, copie o `vendor/` (`cp -a`) e confira com `ReflectionClass::getFileName()` que a classe vem da worktree". Não editei o arquivo.

### Requirement Traceability Update

| Requirement ID | Status | Teste |
|---|---|---|
| CRUZ-01 | ❌ Needs Fix: AC 1 e 3 a 10 PASS; AC 2 sem prova da transação (Fix 2); a recusa do Service sem teste (Fix 1) | TT:97-177; TP:98-127; CC:38-61 |
| CRUZ-02 | ⚠️ Needs Fix: AC 1 a 7 PASS; a transação da premissa (Fix 2) e o card arquivado (Fix 3) | TT:181-234; TP:110, TP:129-136 |
| ROTEIRO-01 | ❌ Needs Fix: AC 1 a 3 e 5 a 9 e 11 PASS; AC 4 sem prova da transação (Fix 2); AC 10 sem o `result` ausente (Fix 4) | TT:238-332; TP:111, TP:138-143 |
| ROTEIRO-02 | ✅ Verified | TT:336-382; MM:47-70 |
| EXC-01 | ⚠️ Needs Fix: AC 1 a 3, 5 e 6 PASS; AC 4 só com Aprovado (Fix 5) | TT:427-471 |
| EVID-01 | ⚠️ Needs Fix: AC 1 a 5 PASS; a transação do `created` (Fix 2) e o card arquivado (Fix 3) | TT:386-423; TP:112-114 |
| TELA-01 | ⚠️ Needs Fix: AC 1, 2, 3 e 6 pela leitura do e2e (não rodado); AC 4, 5, 7 e 8 sem asserção (Fix 7); `run_qa` do dev (Fix 6) | E2E:31-85; TT:113, TT:143-145, TT:251-253; TD:101-104 |
| DONE-01 | ✅ Verified | BT:250-266, BT:269-284 |

O `spec.md` não foi editado: o Verifier só reporta.

### Summary

**Overall**: ❌ Not ready. Sete Fix Plans, todos de teste; dois Major (Fix 1, a recusa do dev no Service; Fix 2, a transação).

**Spec-anchored check**: 2 de 8 requisitos verificados (ROTEIRO-02, DONE-01); os outros seis com lacuna de prova, nenhum com defeito de comportamento.
**Sensor**: 86/97 contados mortos; 5 equivalentes, cada um com a guarda nomeada; 11 sobreviventes, todos confirmados não equivalentes por sonda e todos sobrevivendo também ao conjunto FULL (190 testes).
**Gate**: 817 passed (6911 assertions) na worktree no HEAD sem mutação; Pint, typecheck e lint verdes na árvore real; `conferir-specs` verde antes deste relatório.

**Lições** (`lessons.py`): não gravei, porque a instrução era deixar só este arquivo novo na árvore. Candidatas, para quem conduzir o fechamento:
- L-017 pela 4ª feature: "na mesma transação" afirmado só com `RefreshDatabase`.
- Nova: o `vendor/` em symlink na worktree do sensor carrega a árvore real; copiar e conferir o `getFileName()`.
- Nova: a recusa que o Service repete "com a linha travada" depois da Policy precisa de um teste que chame o Service sem passar pela Policy; pelo HTTP, a Policy sempre barra antes.
- Nova: "diferente de A testar" nomeia dois valores; um caso de cada (a mesma da "dois tipos nomeados", do B3).

**Next steps**:
1. Quem implementou aplica os Fix 1 a 7 (só testes) num commit local.
2. Verifier rodada 2 em agente separado: os 11 sobreviventes de novo e uma amostra dos mortos.
3. Lucas Scholz aprova a spec, responde às quatro open questions e marca as premissas antes do PR.

### Depois do relatório

`node .ai/skills/conferir-specs.mjs` depois de gravar este arquivo: `conferir-specs: 8 feature(s), 20 problema(s)`, exit 1, como o B3 já mostrou. Com o `validation.md`, a feature passa da especificação, e o script cobra:
1. `[b4-teste-e-qa] validate_state.py reprovou`: "validation.md verdict is FAIL" (o desta rodada; sai com o PASS da rodada 2);
2. `spec.md` sem `**Approved by**: <nome>, <AAAA-MM-DD>`;
3. 18 premissas sem `y` em *Confirmed?* (`spec.md` L42 a L59);
4. o aviso "open questions do not read as resolved".

As outras 7 features seguem ok. Os itens 2 a 4 são a aprovação do Lucas Scholz: decisão humana, não Fix.
