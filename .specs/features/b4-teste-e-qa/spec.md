# B4 PR 1: teste cruzado e roteiro de QA na ficha — Specification

Feature `b4-teste-e-qa` · **trilho completo só para o que o AGENTS.md manda parar**: a **Policy alterada** (três abilities novas na `TaskPolicy`: marcar e desfazer o teste cruzado, marcar o resultado do item do roteiro), a **migração aditiva** de duas colunas para a "última marcação" do item e as duas **regras de exclusão de verdade** que o B4 toca (critério com evidência; card com item já marcado). O resto do PR 1 (a seção *Teste* da ficha, a evidência no item, o atalho *Devolver para desenvolvimento* com a origem já escolhida e o `qa_summary` do quadro para o aviso do Done) é trilho leve e entra aqui como P2, só para o Verifier ter o contrato inteiro. A lista *Para testar* é o PR 2 (trilho leve, só leitura, sem Policy nova). Fonte: [`docs/funcional/f4-teste-e-qualidade.md`](../../../docs/funcional/f4-teste-e-qualidade.md) inteiro; [`docs/necessidade.md`](../../../docs/necessidade.md) P32 a P39, P22, P24; [`docs/escopo.md`](../../../docs/escopo.md) "### B4"; [`docs/banco.md`](../../../docs/banco.md) `tasks` (`cross_tested_by/at`), `task_acceptance_criteria`, `test_results`, `task_attachments`, `validation_types`; contrato do kit em `resources/js/types/quadro.ts` (`BoardCard.qa_summary`, `AcceptanceCriterion`, `TaskAttachment.criterion_id`, `TaskDetail.cross_tested`, `TaskAbilities`). Escopo da skill: **Medium**.

**Approved by**: _aguardando Lucas Scholz_

Escrita com IA por Cauã Soares.

## Problem Statement

O banco do B0 já guarda tudo do B4, menos uma coisa: `tasks.cross_tested_by/at`, o `test_result_id` de cada critério, a evidência como anexo ligado ao critério (`task_attachments.task_acceptance_criterion_id`) e as origens `cross_test` e `qa`. A volta de retrabalho já limpa o teste cruzado e o roteiro (`TaskMoveService`), e o aviso do Done (P38) já está no kit, lendo `BoardCard.qa_summary`, que o `TaskPresenter` manda `null`. Falta quem pode marcar, a gravação e a tela. Três pontos não são decisão do bloco:

1. **Permissão nova.** O F4 §09 diz quem marca e quem desfaz o teste cruzado e quem marca o roteiro; a `TaskPolicy` não tem essas abilities, e o kit não tem os `can` delas.
2. **Dado que o banco não guarda na linha.** O F4 §02 mostra a "Última marcação" de cada item (Fulano, DD/MM/AAAA às HH:MM). O `banco.md` diz que ela vem da auditoria; para a ficha do B3 (20/10) a decisão foi a coluna, não ler a auditoria.
3. **Exclusão de verdade.** Excluir critério (T1) não olha a evidência, e a FK `task_attachments.task_acceptance_criterion_id` é `restrict`: hoje o critério A testar com evidência dá erro 500 ao excluir. E o "nunca usado" do card (`TaskUsageQuery`) não conta o item já marcado, que é histórico como o teste cruzado (que já conta).

## Goals

- [ ] Outro dev alocado marca o teste cruzado; o dev da tarefa nunca marca; quem marcou, o gestor e o Administrador desfazem.
- [ ] Qualquer alocado marca o resultado de cada item do roteiro e anexa a evidência no item, com quem e quando na tela.
- [ ] Nenhuma exclusão de verdade apaga evidência nem histórico de teste.
- [ ] O quadro avisa ao levar para o Done com item reprovado ou a testar, e deixa seguir.

## Out of Scope

| Feature | Reason |
|---|---|
| A lista *Para testar* (F4 §03, §05) | PR 2 do B4, trilho leve, só leitura |
| Marcar teste cruzado, roteiro e evidência pela IA | F4 §04 "Pela IA": não estão nas 6 ferramentas da fatia 1 |
| O aviso do Done para a IA (`mover_tarefa`) | B8: o servidor devolve o resumo quando a ferramenta existir; a tela já avisa pelo `qa_summary` |
| Aviso a alguém quando o teste cruzado é marcado | F4 §07 e §10.9: a P22 limita os avisos de Projetos a dois |
| Parecer de code review, botão de aprovar | P32, P33 (R8): o sistema guarda só o PR |
| Roteiro em PDF | P55: depois da v1 |
| Escolher outra etapa no atalho *Devolver* | O diálogo de retrabalho do kit recebe o destino pronto; quem quiser outra etapa usa o *Mover para…* da ficha ou arrasta no quadro, com o mesmo diálogo (F4 §04, P39) |

---

## Assumptions & Open Questions

| Assumption / decision | Chosen default | Rationale | Confirmed? |
|---|---|---|---|
| Marcar o teste cruzado | Ability nova `TaskPolicy::crossTest(Task)`: vê o projeto **e não é o dev da tarefa**. Tarefa sem dev: qualquer alocado. Em qualquer etapa (o normal é `In test`) | F4 §04 e §09; P34; escopo B4 ("alocado, não o dev") | |
| **O dev da tarefa que também gere o projeto ou é Administrador** (pergunta) | **Não marca.** A regra "não é o dev" vale para todo papel | P34 diz "menos o dev da tarefa", sem exceção; o teste cruzado existe para ser outra pessoa. O F4 §09 dá "sim" ao gestor e ao Administrador sem falar desse caso | |
| Desfazer a marca | Ability nova `TaskPolicy::undoCrossTest(Task)`: quem gere o projeto (`ProjectPolicy::manage`, inclui o Administrador), **ou** quem marcou enquanto ainda vê o projeto (como o comentário e o lançamento de horas) | F4 §04, §09, §10.2 | |
| Marcar com marca já feita | Recusa com "Teste cruzado já marcado por Fulano: desfaça antes." e nada muda; com a linha do card travada, o segundo de dois cliques simultâneos cai aqui | F4 §02: os dois campos "se preenchem juntos e se limpam juntos" | |
| Desfazer sem marca | Recusa com "Este card não tem teste cruzado marcado." e nada muda | Mesmo motivo | |
| A Policy olhou o card antes da trava | Com a linha travada, o `TaskService` confere de novo: quem virou o dev da tarefa no meio não marca ("Você é o dev desta tarefa: o teste cruzado é de outra pessoa."), e quem não gere só desfaz a marca que ainda é dele ("A marca de teste cruzado mudou: recarregue a ficha."). As duas mensagens só aparecem numa corrida | Verifier rodada 1 (Fix 1 e a observação do desfazer); a Policy sozinha deixaria limpar a marca nova de outra pessoa | |
| Marcar o resultado do item | Ability nova `TaskPolicy::runQa(Task)`: vê o projeto (qualquer alocado; o QA da tarefa é o caso normal, sem trava). Pai e subitem se marcam à parte. Marcar reprovado não move o card | F4 §04, §09, §10.1, §10.4; escopo B4; premissa de Projetos | |
| Anexar e excluir evidência | Pela rota de anexos que já existe (`POST …/anexos`, `DELETE …/anexos/{aid}`), com `criterion_id` opcional no envio, e a ability que já existe, `attach`. O critério tem de ser do card da rota (senão, erro no campo `criterion_id`). A evidência entra na lista de anexos com a marca "Evidência do QA" | F4 §02 e §09 ("como em F3"); P24, P36; `TaskAttachment.criterion_id` do contrato | |
| **"Última marcação" do item** (pergunta) | **Migração aditiva**, 2 colunas nulas em `task_acceptance_criteria`: `marked_by` (fk users, restrict) e `marked_at` (datetime, UTC), gravadas a cada marcação. Nulas nas linhas de antes; a tela mostra "—". A auditoria continua guardando cada marcação com o antes e o depois (G3) | O mesmo caminho da ficha em 20/10 (as 4 colunas de "quem", spec `b3-ficha`): o contrato mostra o dado sem montar histórico na tela. A alternativa, ler `audit_logs` (o que o `banco.md` previa), dispensa a migração mas faz a ficha procurar o `test_result_id` dentro do JSON de `changes` de cada critério | |
| Quando o item volta sozinho para A testar | Editar o texto de item já testado (P37) e o retrabalho (P37, P34) **limpam** `marked_by` e `marked_at` junto com o resultado: ninguém marcou "A testar". Marcar A testar à mão é uma marcação e grava quem e quando | F4 §02 "Última marcação"; o histórico fica na auditoria | |
| Marcar o mesmo resultado de novo | Nada muda e não grava auditoria (como editar o critério com o mesmo texto) | `CriterionService::update` | |
| Card arquivado | Marcar, desfazer, marcar item e anexar evidência são recusados com "Card arquivado não muda: desarquive antes." (o `WritesTasks`) | P26; F3 §11 item 12 (só o comentário passa) | |
| Projeto encerrado | Aceita as quatro escritas | P7 recusa só card novo | |
| **Excluir critério com evidência** (exclusão de verdade) | "Testado" passa a ser **resultado diferente de A testar ou com evidência**, nele ou num subitem: recusa com a mensagem que já existe ("CA_3 já foi testado: edite em vez de excluir.") e nada muda. Hoje o caso dá 500 pela FK `restrict` | T1 ("só enquanto não testado"); F4 §10.5 (depois do retrabalho a evidência fica e o item volta a A testar) | |
| **Card com item já marcado não é "nunca usado"** (exclusão de verdade) | O `TaskUsageQuery` passa a contar como uso algum critério com resultado diferente de A testar ou com `marked_at` preenchido, como já conta o teste cruzado. Sem FK nova para `tasks` | P26 ("com histórico, arquiva"); o `isUnused` já recusa `cross_tested_at` preenchido | |
| `qa_summary` do card no quadro | `{ rejected, pending }` contando pai e subitem como um item cada; `null` no card sem critério (sem roteiro, sem aviso, F4 §10.6). Uma consulta para o quadro inteiro, não por card | Contrato do kit; F4 §06; `docs/escopo.md` §10 (500 cards contando consultas) | |
| Abilities na ficha | `TaskAbilities` ganha `cross_test`, `undo_cross_test` e `run_qa`, decididas pela `TaskPolicy` no `TaskDetailPresenter`. `AuditActionEnum::forAbility` mapeia as três para `updated` | AGENTS.md "Ability nova: `User`, `HandleInertiaRequests`, `types` e Policy no mesmo commit"; a negação não pode virar "Outra ação" | |
| A auditoria | `updated` no card (`cross_tested_by` e `cross_tested_at`, antes e depois) ao marcar e ao desfazer; `updated` no critério (`test_result_id`, `marked_by`, `marked_at`) ao marcar o item; o `created` do anexo ganha o `task_acceptance_criterion_id`. Na mesma transação | G3 | |
| Rotas novas | `POST /projetos/{id}/cards/{chave}/teste-cruzado` e `…/teste-cruzado/desfazer` (como `arquivar`/`desarquivar`); `POST …/criterios/{cid}/resultado` com `{ result: 'pending' \| 'approved' \| 'failed' }` (o slug `passed` do banco vira `approved` na tela, como o `TaskDetailPresenter` já faz). Critério de outro card: 404, antes da Policy | Padrão do `TaskDetailController` (CR-1-04 do PR 2) | |

**Open questions:** as quatro linhas em negrito, marcadas "(pergunta)" ou "(exclusão de verdade)": o dev da tarefa que gere não marca; a migração das 2 colunas; o critério com evidência não se exclui; o card com item marcado não é "nunca usado".

---

## User Stories

### P1: Só outra pessoa marca o teste cruzado ⭐ MVP

**Acceptance Criteria**:

1. WHEN uma pessoa alocada que não é o dev da tarefa clica em *Marquei: testado por mim* THEN o sistema SHALL gravar `cross_tested_by` com ela e `cross_tested_at` com a hora do clique.
2. WHEN a marca é gravada THEN o sistema SHALL registrar `updated` na auditoria do card, com o antes e o depois dos dois campos, na mesma transação.
3. WHEN a tarefa não tem dev THEN qualquer pessoa alocada SHALL poder marcar.
4. IF o dev da tarefa tenta marcar THEN o sistema SHALL responder 403.
5. IF o dev da tarefa tenta marcar THEN o sistema SHALL manter `cross_tested_by` e `cross_tested_at` nulos.
6. IF o dev da tarefa gere o projeto e tenta marcar THEN o sistema SHALL responder 403.
7. IF quem não vê o projeto tenta marcar THEN o sistema SHALL responder 403 e gravar a negação `updated` na auditoria.
8. IF o card já tem a marca THEN o sistema SHALL recusar com "Teste cruzado já marcado por Fulano: desfaça antes."
9. IF o card já tem a marca THEN o sistema SHALL manter quem e quando da primeira marca.
10. WHEN a ficha abre para o dev da tarefa THEN `abilities.cross_test` SHALL ser falso.

### P1: Desfazer a marca do teste cruzado ⭐ MVP

**Acceptance Criteria**:

1. WHEN quem marcou desfaz THEN o sistema SHALL limpar `cross_tested_by` e `cross_tested_at` e registrar `updated` na auditoria.
2. WHEN o gestor do projeto desfaz a marca de outra pessoa THEN o sistema SHALL limpar os dois campos.
3. WHEN o Administrador desfaz a marca de outra pessoa THEN o sistema SHALL limpar os dois campos.
4. IF um dev alocado que não marcou tenta desfazer THEN o sistema SHALL responder 403.
5. IF um dev alocado que não marcou tenta desfazer THEN o sistema SHALL manter a marca.
6. IF quem marcou saiu do projeto e tenta desfazer THEN o sistema SHALL responder 403.
7. IF o card não tem marca THEN o sistema SHALL recusar com "Este card não tem teste cruzado marcado."

### P1: Marcar o resultado de cada item do roteiro ⭐ MVP

**Acceptance Criteria**:

1. WHEN uma pessoa alocada marca um item como Aprovado THEN o sistema SHALL gravar o resultado `passed`, `marked_by` com ela e `marked_at` com a hora.
2. WHEN uma pessoa alocada marca um item como Reprovado THEN o sistema SHALL gravar `failed` sem mover o card.
3. WHEN uma pessoa alocada marca um item como A testar THEN o sistema SHALL gravar `pending` com quem e quando.
4. WHEN o resultado muda THEN o sistema SHALL registrar `updated` na auditoria do critério, com o antes e o depois, na mesma transação.
5. WHEN o resultado marcado é o mesmo que já estava THEN o sistema SHALL manter a linha e não gravar auditoria.
6. WHEN o subitem é marcado THEN o sistema SHALL manter o resultado do pai.
7. IF quem não vê o projeto tenta marcar THEN o sistema SHALL responder 403.
8. IF quem não vê o projeto tenta marcar THEN o sistema SHALL manter o resultado.
9. IF o critério da rota é de outro card THEN o sistema SHALL responder 404.
10. IF o resultado enviado não é `pending`, `approved` nem `failed` THEN o sistema SHALL recusar no campo `result`.
11. IF o card está arquivado THEN o sistema SHALL recusar com "Card arquivado não muda: desarquive antes."

### P1: A volta para A testar limpa a última marcação ⭐ MVP

**Acceptance Criteria**:

1. WHEN o texto de um item já testado é editado THEN o sistema SHALL pôr o item em A testar e limpar `marked_by` e `marked_at`.
2. WHEN o card volta para desenvolvimento com origem e motivo THEN o sistema SHALL pôr todos os itens em A testar e limpar `marked_by` e `marked_at` de todos.
3. WHEN o card volta para desenvolvimento marcado como engano THEN o sistema SHALL manter os resultados, as marcações e o teste cruzado.

### P1: Exclusão de verdade não apaga teste ⭐ MVP

**Acceptance Criteria**:

1. IF o critério A testar tem evidência THEN o sistema SHALL recusar a exclusão com "CA_3 já foi testado: edite em vez de excluir."
2. IF um subitem A testar do critério tem evidência THEN o sistema SHALL recusar a exclusão do critério.
3. IF a exclusão do critério é recusada THEN o sistema SHALL manter o critério, os subitens e a evidência.
4. IF o card nunca movido tem um item com resultado diferente de A testar THEN o `TaskUsageQuery` SHALL dizer que o card foi usado.
5. IF o card nunca movido tem um item com `marked_at` preenchido THEN o `TaskUsageQuery` SHALL dizer que o card foi usado.
6. IF quem gere tenta excluir o card com item marcado THEN o sistema SHALL recusar com "SGM-17 já foi usado: arquive em vez de excluir."

### P2: Evidência no item

**Acceptance Criteria**:

1. WHEN uma pessoa alocada envia arquivo com `criterion_id` de um critério do card THEN o sistema SHALL gravar o anexo ligado ao critério.
2. WHEN a ficha abre THEN a evidência SHALL aparecer na lista de anexos com `criterion_id` preenchido.
3. IF o `criterion_id` é de critério de outro card THEN o sistema SHALL recusar no campo `criterion_id`.
4. IF o `criterion_id` é de critério de outro card THEN o sistema SHALL manter a quantidade de anexos.
5. WHEN a evidência é excluída THEN o sistema SHALL apagar o arquivo e o registro, com a mesma regra do anexo do F3.

### P2: A seção *Teste* da ficha

**Acceptance Criteria**:

1. WHEN a ficha abre THEN a seção *Teste* SHALL mostrar a situação do teste cruzado ("Testado por Fulano em DD/MM/AAAA às HH:MM" ou "Ainda não marcado") e o resumo "Roteiro: 7 itens · 4 aprovados · 1 reprovado · 2 a testar".
2. WHEN a ficha abre THEN cada item SHALL mostrar o número, o texto, o resultado, a quantidade de evidências e a última marcação ("Fulano, DD/MM/AAAA às HH:MM" ou "—").
3. WHEN o item está reprovado THEN a linha SHALL aparecer em destaque.
4. WHEN o card não tem critério THEN a seção SHALL mostrar "Esta tarefa não tem critérios de aceite." com o botão *Novo critério*, que abre a criação de critério da ficha.
5. WHEN a pessoa clica em *Devolver para desenvolvimento* no bloco do teste cruzado THEN o diálogo de retrabalho SHALL abrir com destino `To do` e origem "Teste cruzado".
6. WHEN a pessoa clica em *Devolver para desenvolvimento* no roteiro THEN o diálogo SHALL abrir com destino `To do` e origem "QA".
7. WHEN a pessoa desfaz a marca THEN a tela SHALL pedir "Desfazer a marca de teste cruzado de Fulano?" antes de enviar.
8. WHILE uma escrita da seção está em andamento a tela SHALL desabilitar os botões da seção.

### P2: O aviso do Done no quadro

**Acceptance Criteria**:

1. WHEN o quadro abre THEN cada card com critério SHALL trazer `qa_summary` com a quantidade de itens reprovados e a testar, pai e subitem contados um a um.
2. WHEN o card não tem critério THEN `qa_summary` SHALL ser nulo.
3. WHEN o quadro tem 500 cards THEN o `qa_summary` SHALL sair de uma consulta só.

---

## Requirement Traceability

| Requirement ID | Story | Phase | Status | Teste |
|---|---|---|---|---|
| CRUZ-01 | Só outra pessoa marca o teste cruzado | Execute | Implemented (Verifier rodada 2 pendente) | `TaskTestTest.php` (`test_another_allocated_person_marks…`, `…without_a_developer…`, `…developer_of_the_task_never_marks…`, `…service_refuses_whoever_became_the_developer…`, `…ability_hides_the_button…`, `…marking_again_is_refused…`, `…archived_card…`, `…finished_project…`); `TaskTestPolicyTest.php` (não alocado, o dev da tarefa); `TesteCruzadoConcorrenciaTest.php`; `B4TransacaoTest.php` (a marca e a auditoria num commit só) |
| CRUZ-02 | Desfazer a marca do teste cruzado | Execute | Implemented (Verifier rodada 2 pendente) | `TaskTestTest.php` (`test_who_marked_undoes…`, `…manager_and_the_admin…`, `…another_developer_does_not_undo…`, `…left_the_project…`, `…undoing_without_a_mark…`, `…mark_that_changed_hands…`, `…archived_card_keeps_its_mark`); `TaskTestPolicyTest.php`; `B4TransacaoTest.php` |
| ROTEIRO-01 | Marcar o resultado de cada item do roteiro | Execute | Implemented (Verifier rodada 2 pendente) | `TaskTestTest.php` (`test_marking_an_item_records…`, `…failed_item_does_not_move…`, `…pending_marked_by_hand…`, `…same_result_again…`, `…subitem_is_marked_apart…`, `…unknown_result…`, `…missing_result…`, `…item_of_another_card_is_404`, `…archived_card_is_not_marked`); `TaskTestPolicyTest.php`; `B4TransacaoTest.php` |
| ROTEIRO-02 | A volta para A testar limpa a última marcação | Execute | Implemented (Verifier rodada 2 pendente) | `TaskTestTest.php` (`test_editing_a_tested_item_clears…`, `…rework_sends_every_item_back…`, `…return_marked_as_mistake…`); `MigracaoMarcacaoTest.php` (a migração desce e sobe) |
| EXC-01 | Exclusão de verdade não apaga teste | Execute | Implemented (Verifier rodada 2 pendente) | `TaskTestTest.php` (`test_a_pending_item_with_evidence…`, `…pending_subitem_has_evidence…`, `…card_with_a_marked_item_is_used`, com aprovado, reprovado e A testar marcado à mão): sem a guarda, o primeiro dava o erro 1451 do MySQL (visto falhar em 23/10) |
| EVID-01 | Evidência no item | Execute | Implemented (Verifier rodada 2 pendente) | `TaskTestTest.php` (`test_evidence_is_an_attachment…`, `…another_card_is_refused…`, `…not_attached_to_an_archived_card`, `…removing_evidence…`); `TaskTestPolicyTest.php`; `B4TransacaoTest.php` (sem a auditoria, nem linha nem arquivo) |
| TELA-01 | A seção *Teste* da ficha | Execute | Implemented (Verifier rodada 2 pendente) | `e2e/teste-e-qa.spec.ts` (2 testes: o fluxo inteiro; e os AC 4, 5, 7 e 8, com a requisição presa para ver os botões travados); `TaskDetailTest.php` (contrato: abilities e `marked`; consultas da ficha com itens marcados); stories `Organisms/Task Test Section` |
| DONE-01 | O aviso do Done no quadro | Execute | Implemented (Verifier rodada 2 pendente) | `BoardTest.php` (`test_each_card_brings_the_qa_summary…`, `test_the_number_of_queries_does_not_grow…` com um critério por card) |

**Coverage:** 8 total, 8 mapped to tests, 0 unmapped; 0 verified (Verifier rodada 1 FAIL só por teste faltando, `validation.md`; os 7 Fix entraram, rodada 2 pendente).

## Success Criteria

- [ ] Testes de feature por regra no MySQL 8: Policy (403 e nada muda) para cada ability nova, nos três papéis e no não alocado.
- [ ] Concorrência: dois cliques simultâneos em *Marquei* gravam uma marca só.
- [ ] Migração com `down()` e `up()` de novo, idempotente.
- [ ] O benchmark do quadro com 500 cards continua no mesmo número de consultas mais uma, no máximo.
- [ ] Verifier em agente separado com mutação nas três abilities, na recusa do dev da tarefa, no "testado" da exclusão do critério e no "usado" do `TaskUsageQuery`.
- [ ] e2e de fumaça: marcar o teste cruzado, marcar um item reprovado com evidência, devolver pelo atalho e ver o roteiro em A testar.
