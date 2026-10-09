# Code Review — `feat/b1-nucleo` · Ciclo 3

**Data:** 2026-10-09
**História:** b1-nucleo (PR [WCJ-Tecnologia/wcj-hub#5](https://github.com/WCJ-Tecnologia/wcj-hub/pull/5), autor Cauã Soares)
**Branch:** `feat/b1-nucleo` (HEAD local `c0b3304`) · **Base:** `main` (branch do ambiente, `AGENTS.md` → Ambientes)
**Spec:** `.specs/features/b1-nucleo/`
**Revisor:** Claude Code (skill `wcj-code-review`, sessão de revisão com contexto zerado). Três camadas em subagentes Opus sem contexto (segurança, requisitos, qualidade/regressão) e a escada num subagente Sonnet. Esta sessão consolidou e reverificou no código.
**Ciclos anteriores:** [Ciclo 1](b1-nucleo-code-review-ciclo-1.md) · [Ciclo 2](b1-nucleo-code-review-ciclo-2.md)
**Escopo deste ciclo:**
- Os 13 commits de correção do ciclo 2 (`d6cbc4c..c0b3304`: 44 arquivos, +870 / −341), com os arquivos tocados lidos inteiros.
- A branch inteira contra `main` (155 arquivos, +16 336 / −694), pelas seis perguntas de `seguranca.md` §11.
- A Rodada 9 do Verifier (`validation.md`), conferida por amostragem: 6 linhas de evidência abertas no teste. Todas batem; uma está deslocada uma linha (`ContactControllerTest.php:432`, não `:433`).
- Working tree limpo.

> **Atenção:** o HEAD local `c0b3304` está 37 commits à frente de `origin/feat/b1-nucleo` (`2b076bf`). O PR #5 e o CI ainda mostram o estado anterior ao ciclo 1. O push é do Lucas e não entra como achado de código.

**Escada de verificação (worktree `wcj-hub-pr5`, 09/10, MySQL 8 da 3306, HEAD `c0b3304`):**
- `composer lint` ✅
- `php artisan test` ✅ 345 testes, 2 853 asserções, 0 falhas, 0 skipped
- `npm run typecheck` ✅ · `npm run lint` ✅ · `npm run build` ✅
- `npm run specs:check` ✅ 0 problemas; avisos T35 e T50 "Tests: none", como antes
- `npm run test:guard` ✅ 1 053
- Playwright ✅ 68/68 (67 testes + setup; 0 skipped, 0 flaky)
- `git status` vazio ao final; servidor da 8000 morto

---

## Status dos achados anteriores

Reverificados **no código atual** (HEAD `c0b3304`), não pelo relatório anterior nem pelas mensagens de commit.

### Ciclo 1

| ID | Criticidade | Título | Status | Evidência |
|---|---|---|---|---|
| CR-1-01 | Alto | Reenviar convite não derruba as sessões abertas do alvo | ↪️ Tratado em PR próprio, fora desta branch (DA-05) | `UserService.php:74-85` segue sem `terminateOthers`, de propósito. PR [#7](https://github.com/WCJ-Tecnologia/wcj-hub/pull/7) OPEN, sem merge (`gh pr view 7`, 09/10). Achado de segurança: quem marca como corrigido é o Lucas |
| CR-1-02 | Médio | CNPJ ou e-mail repetido em gravação simultânea responde 500 | ✅ Resolvido | Trait `RefusesDuplicates.php:25-31`; `ClientService.php:74-79,93-97`; `UserService.php:52-65,95-118`; `ClientRepetidoConcorrenciaTest.php` (create e update) |
| CR-1-03 | Médio | `ContactController::update` deixa `DomainException` virar 500 | ✅ Resolvido | `ContactController.php:57`, dentro do `attempt()` |
| CR-1-04 | Médio | "A WCJ não pode ser excluída" só na Policy | ✅ Resolvido | `ClientService.php:137-139`; `ClientPolicy.php:67` |
| CR-1-05 | Médio | NUC-13 sem porta de entrada; AC 3 sem prova | ✅ Resolvido | `UserService.php:207` e `ClientService.php:184` usam `inspectFor()->authorize()`; Rodada 9 (mutantes c1, c2 e d mortos) |
| CR-1-06 | Médio | Spec reescrita sem nova aprovação | ✅ Resolvido | `spec.md:5`: "Approved by: Lucas Scholz, 2026-10-09" |
| CR-1-07 | Médio | Filtro de data da auditoria dispara uma visita por tecla | ✅ Resolvido | `audit-log-filters.tsx:66-118` (estado local, 300 ms, blur e Enter); `e2e/filtro-de-data.spec.ts` |
| CR-1-08 | Médio | Botões da linha de Usuários por ability global | ✅ Resolvido | `UserPresenter.php:28-34` (`can` pela `UserPolicy`) |
| CR-1-09 | Médio | Esqueleto de lista em 4 páginas, 3 padrões de linha | ✅ Resolvido | `ListBody` em `users/index.tsx:209`, `clients/index.tsx:181`, `contacts/index.tsx:72`, `audit-logs/index.tsx:57`; linha nos quatro Presenters (CR-2-12) |
| CR-1-10 | Médio | Busca com debounce cancela escrita em voo | ✅ Resolvido | `search-input.tsx:39,51,76-98`; `e2e/busca-e-escrita.spec.ts` |
| CR-1-11 | Baixo | Senha provisória não expira, a spec diz que expira | ❌ Não resolvido, aguarda decisão do Lucas (DA-07) | `spec.md:226` ainda diz "expira pela regra do template" |
| CR-1-12 | Baixo | Tela mais rígida que o servidor na carga diária | ❌ Não resolvido, aguarda decisão do Lucas (DA-07) | `user-form-dialog.tsx:87` ainda tem `min="0.25"` |
| CR-1-13 | Baixo | Reenviar convite manda "nova senha" e audita o antes vazio | ✅ Resolvido | `UserService.php:77-79`; assunto único em `TemporaryPasswordMail.php:24` |
| CR-1-14 | Baixo | Erro de cliente desativado numa chave que o formulário não mostra | ✅ Resolvido | `contact-form-dialog.tsx:39,53-55` |
| CR-1-15 | Baixo | Checar-e-agir fora da transação em `deactivate` e no contato | ⚠️ Parcial | O deadlock do ciclo 2 foi corrigido (CR-2-01). Os três `deactivate` decidem pela linha travada, mas **gravam e auditam pela instância em memória**: ver CR-3-01 |
| CR-1-16 | Baixo | `Client` depende de classe de seeder | ✅ Resolvido | `Client.php:19,40`; `WcjClientSeeder.php:18` |
| CR-1-17 | Baixo | Reativar não sinaliza nem trava clique duplo | ✅ Resolvido | `hooks/use-row-action.ts:24-66` nas três telas; `e2e/clique-duplo.spec.ts` |
| CR-1-18 | Baixo | `LIKE` sem escapar `%` e `_` | ✅ Resolvido | `Like::contains` em `UserService.php:191-192`, `ClientService.php:168-170`, `ContactService.php:186-188`, `AuditLogQuery.php:167,176` |
| CR-1-19 | Baixo | Mensagens dependem de `APP_LOCALE` fora do runbook | ✅ Resolvido | `config/app.php:116`; `docs/DEPLOY.md:20` |
| CR-1-20 | Baixo | Sem prova em 375 px | ✅ Resolvido | `e2e/mobile.spec.ts` (verde na escada) |
| CR-1-21 | Baixo | e2e crítico limpa só no fim do corpo | ✅ Resolvido | `finally` em `e2e/nucleo.spec.ts:52` e `e2e/smoke.spec.ts:106` |
| CR-1-22 | Baixo | Componentes e hook fora da tabela de componentes | ✅ Resolvido | `AGENTS.md:119-130`, com `useRowAction` e `RowActions disabled` |
| CR-1-23 | Baixo | Auditoria sem índice para `created_at` | ❌ Não resolvido, aguarda decisão do Lucas (DA-07) | `2026_10_06_000170_create_audit_logs_table.php:42-44` sem índice novo |
| CR-1-24 | Baixo | `MAIL_MAILER=log` grava a senha provisória em claro | ✅ Resolvido (pela documentação) | `docs/DEPLOY.md:22` |

### Ciclo 2

| ID | Criticidade | Título | Status | Evidência |
|---|---|---|---|---|
| CR-2-01 | Médio | `deactivate` trava o alvo antes dos admins: deadlock vira 500 | ✅ Resolvido | `UserService.php:127` (admins) antes de `:131` (alvo); `UserServiceTest.php:448-466` prova a ordem pela consulta (mutante f morto na Rodada 9). O teste de corrida novo não prova a ordem: CR-3-04. Resta uma combinação de deadlock anterior ao ciclo, por outro caminho: CR-3-03 |
| CR-2-02 | Médio | Verifier e mutação não cobrem as correções do ciclo 1 | ✅ Resolvido | `validation.md:103-300` (Rodada 9: PASS, 17/17 linhas, 20/20 mutantes mortos). Amostragem desta revisão: `ClientControllerTest:212-218`, `UserServiceTest:461-464`, `:672-680`, `:162-170`, `ClientRepetidoConcorrenciaTest:52-55,80-89` batem; `ContactControllerTest:433` está em `:432` (cosmético) |
| CR-2-03 | Médio | Trava de clique duplo copiada em três telas | ✅ Resolvido | `use-row-action.ts` usado em `users/index.tsx:38`, `clients/index.tsx:47`, `contact-actions.tsx:28`; `git grep busyRow\|setBusy` fora do hook: zero |
| CR-2-04 | Baixo | Excluir a WCJ não tem teste pela rota | ✅ Resolvido | `ClientControllerTest.php:206-219` (403, mensagem, WCJ intacta, uma linha `denied`); `:134` `can.delete === false` |
| CR-2-05 | Baixo | Documentos não acompanharam a D14 nem a F1 1.2 | ✅ Resolvido | `necessidade.md:196` ("nem se exclui"); `escopo.md:77` (`TemporaryPasswordMail`), `:79`; `spec.md:3` (F1 v1.2), `:57`; `design.md:108` |
| CR-2-06 | Baixo | `reactivate` de Usuário e Cliente decide pela instância em memória | ✅ Resolvido | `UserService.php:145-154` e `ClientService.php:121-130` decidem e gravam por `$locked`; testes `test_reativar_pela_instancia_*` (`UserServiceTest.php:417,433`, `ClientServiceTest.php:270,286`) |
| CR-2-07 | Baixo | Guarda de clique duplo vale para a página, o sinal só para a linha | ✅ Resolvido | `use-row-action.ts:62-64` (`isBusy`/`isLocked`) → `row-actions.tsx:54` `disabled={busy \|\| disabled}`; `e2e/clique-duplo.spec.ts:44,47` |
| CR-2-08 | Baixo | Código morto depois do CR-1-13 e do CR-1-08 | ✅ Resolvido (`isReset`) · ❌ aguarda decisão do Lucas (abilities, DA-07) | Nenhum `isReset` em `app`, `resources` ou `tests`. `delete_records` e `reset_passwords` seguem sem leitor (`HandleInertiaRequests.php:66,68`; `types/index.d.ts:12,14`) |
| CR-2-09 | Baixo | "Reenviar convite" aparece para usuário desativado | ❌ Não resolvido, aguarda decisão do Lucas (DA-07) | `UserPresenter.php:33` sem condição de ativo |
| CR-2-10 | Baixo | Select trocado logo depois pode desfazer o período recém-aplicado | ✅ Resolvido | `audit-log-filters.tsx:91-95` (`params` com o período local), `:164-169` (o select cancela o timer); `e2e/filtro-de-data.spec.ts` |
| CR-2-11 | Baixo | Captura do unique em dois formatos; `update` de cliente sem teste de corrida | ✅ Resolvido | `RefusesDuplicates.php:25-31` (`throw $refusal($e) ?? $e`); `UserService.php:218-223` só aceita o índice `users_email_unique`; `ClientRepetidoConcorrenciaTest.php:63-90` |
| CR-2-12 | Baixo | Linha da lista em dois lugares; `situation()` duplicado | ✅ Resolvido | `UserPresenter`, `ClientPresenter`, `ContactPresenter` e `AuditLogPresenter` em `app/Platform/Services`; controllers só chamam `->row()` (`UserController.php:38`); `lib/situation.ts:9-10`; convenção no `AGENTS.md:227` |

**Evolução:**

| Rodada | Achados novos |
|---|---|
| Revisão 07/10 | 9 |
| Ciclo 1 | 24 |
| Ciclo 2 | 12 |
| Ciclo 3 | 5 |

**Status neste ciclo, dos 36 achados anteriores:**
- 30 ✅ resolvidos: 19 do ciclo 1 e 11 do ciclo 2. O CR-2-08 conta como resolvido na parte do `isReset`.
- 1 ⚠️ parcial: CR-1-15.
- 4 ❌ aguardando decisão do Lucas: CR-1-11, CR-1-12, CR-1-23 e CR-2-09, mais a parte de abilities do CR-2-08.
- 1 em PR próprio: CR-1-01.
- **0 regressões.**

---

## Achados do Ciclo 3

## [MÉDIO] CR-3-01 — `deactivate` decide pela linha travada, mas grava e audita pela instância em memória: pode auditar uma desativação que não aconteceu

**Arquivo:** `app/Platform/Services/UserService.php:127-138`; `app/Platform/Services/ClientService.php:108-115`; `app/Platform/Services/ContactService.php:101-109`
**Trecho atual:**
```php
$this->ensureNotLastActiveAdmin($user, 'desativar');            // lê isAdmin()/isActive() da memória

if (! User::query()->lockForUpdate()->findOrFail($user->getKey())->isActive()) {
    return;
}

$user->setActive(false, $actor)->save();                          // instância em memória
$this->audit->log(AuditActionEnum::DEACTIVATED, $user, ['active' => true], ['active' => false], $actor->id);
$this->revokeAiAuthorizations($user, $actor);
$this->sessions->terminateOthers($user);
```

**Ajuste necessário:**
- Nos três `deactivate`, gravar e auditar pela linha travada (`$locked`), como os três `reactivate` já fazem desde o CR-2-06.
- No `UserService`, a trava dos administradores tem de decidir pelo papel e pela situação da linha relida, mantendo a ordem admins → alvo do CR-2-01. Por exemplo: travar os admins ativos sempre que o alvo for admin pelo banco, e só depois a linha do alvo.
- Teste espelho do `test_reativar_pela_instancia_carregada_antes_da_desativacao_reativa` (`UserServiceTest.php:433`): "desativar pela instância carregada antes da reativação desativa", nos três cadastros.

**Motivo:**
- `Deactivatable::setActive()` (`Deactivatable.php:115-119`) não faz nada quando a instância já está no estado pedido.
- Instância carregada desativada (route binding ou chamada direta ao Service), outra requisição reativa no meio, o `deactivate` roda:
  - a linha travada está ativa, então ele segue;
  - `setActive(false)` não muda nada e o `save()` não grava;
  - mesmo assim, a linha `deactivated` vai para `audit_logs`;
  - no Usuário, também revoga as conexões de IA e derruba as sessões de quem continua ativo.
- No Usuário, o `ensureNotLastActiveAdmin($user)` também lê a memória: com a memória "inativa" ele pula a trava dos administradores.
- Foi introduzido pela correção do CR-1-15, que trocou a decisão pela memória por "decide pela linha travada" sem trocar a escrita. É o mesmo padrão que o CR-2-06 corrigiu e testou no `reactivate`. Anti-padrão AP-01: a ação espelho ficou para trás.

**Sugestão:**
Ler o `$locked` antes de tudo (depois dos admins, no Usuário) e usá-lo no `setActive`, no `log`, na revogação e no `terminateOthers`. São três trocas de variável mais o teste espelho.

**Impacto:**
- A auditoria (G3) registra "Desativado" num cadastro que continua ativo, e no Usuário a pessoa perde sessões e conexões de IA sem perder o acesso.
- Pela tela, a janela é de milissegundos (entre o binding e a trava).
- Quem chamar o Service com instância guardada amplia a janela: o B2, e a IA pelo MCP no B8.

---

## [BAIXO] CR-3-02 — Excluir contato tira o snapshot antes da trava: uma anonimização no meio devolve o dado pessoal à auditoria

**Arquivo:** `app/Platform/Services/ContactService.php:128-134`
**Trecho atual:**
```php
$before = $this->snapshot($contact);

$deleted = $contact->deleteIfUnused(function (Contact $deleted) use ($before, $actor) {
    $this->audit->log(AuditActionEnum::DELETED, $deleted, $before, null, $actor->id);
});
```

**Ajuste necessário:**
- Tirar o snapshot da linha travada, dentro do callback: `$this->snapshot($deleted)`. O `$locked` que o `deleteIfUnused` passa (`Deactivatable.php:134-146`) ainda tem os atributos depois do `delete()`.
- Fazer o mesmo em `UserService::delete` (`:160`) e `ClientService::delete` (`:141`), que gravam um `before` possivelmente velho.
- Teste de intercalação com `tests/Support/Concorrencia.php`.

**Motivo:**
- `ContactPolicy::delete` (`:60-63`) não barra contato anonimizado.
- `audit_logs` não tem FK para o contato, então o contato nunca citado em card continua "não usado".
- Dois admins ao mesmo tempo: A anonimiza (`anonymize`, `:146-163`, troca nome, e-mail e telefone e limpa a auditoria) e B exclui. O `deleted` de B grava em `changes` o nome, o e-mail e o telefone de antes, depois da limpeza.
- Desfaz a N12 justamente para o titular que pediu a remoção.
- Não é o FP-04, que trata de contato excluído **sem** anonimização.
- Mesma raiz do AP-01: estado lido da memória antes da transação.

**Sugestão:**
Mover o `snapshot` para dentro do callback nos três `delete`. É uma linha em cada um, com o mesmo resultado no caso comum.

**Impacto:**
Dado pessoal de um titular anonimizado volta para `audit_logs`, onde a regra diz que não pode estar. Raro: precisa de dois admins na mesma linha ao mesmo tempo.

---

## [BAIXO] CR-3-03 — Deadlock residual: escrita em admin-alvo trava alvo → ator, a trava dos admins trava ator → alvo

**Arquivo:** `app/Platform/Services/UserService.php:246` × `:74-85` (`resendInvite`) e `:95-118` (`update` sem rebaixar)
**Trecho atual:**
```php
$activeAdmins = User::query()->active()->where('role_id', RoleEnum::ADMIN->value)->lockForUpdate()->pluck('id');
```

**Ajuste necessário:**
`DB::transaction(..., attempts: 3)` nas escritas do `UserService`: o Laravel repete a transação no deadlock (1213). A alternativa é travar os admins ativos antes do alvo em toda escrita de admin-alvo.

**Motivo:**
- `resendInvite` e `update` de um admin-alvo trazem a linha do alvo em X (o `save`) e depois a do ator em S: a FK `audit_logs.user_id` (`2026_10_06_000170…:27`) confere a linha pai.
- Em paralelo, outro admin desativa, exclui ou rebaixa um terceiro admin e trava os admins ativos em ordem de índice. Se o ator tiver id menor que o alvo, essa transação pega o ator e espera o alvo, enquanto a primeira espera o ator.
- O MySQL derruba uma (1213), que vira 500: o `attempt()` só pega `DomainException`.
- É anterior ao ciclo 2, não é regressão. É o AP-01 do CR-2-01 por outro caminho.
- Análise de travas lida no código, não reproduzida em execução. A extensão a não-admins depende do plano de execução, que não conferi com `EXPLAIN`.

**Sugestão:**
`attempts: 3` é a correção de menor risco: não muda a ordem nem a regra, e a integridade já está garantida.

**Impacto:**
500 raro (três admins agindo ao mesmo tempo) numa rota de formulário. Nenhum dado errado.

---

## [BAIXO] CR-3-04 — O teste de corrida novo do CR-2-01 promete "não deadlock" e não discrimina a ordem das travas

**Arquivo:** `tests/Feature/Nucleo/UltimoAdminConcorrenciaTest.php:62-85`; `tests/Support/Concorrencia.php:26`
**Trecho atual:**
```php
// … A outra espera a principal (trava, não deadlock), e sobra exatamente um administrador ativo.
…
$this->assertTrue($corrida->bloqueada, 'A outra requisição não esperou a principal.');
```

**Ajuste necessário:**
- Tirar a promessa "não deadlock" do comentário e dizer que a ordem é provada por `UserServiceTest::test_desativar_admin_trava_os_administradores_antes_do_alvo` (`:448`).
- Ou expor o código no `Concorrencia` e afirmar 1205 (espera), não 1213.

**Motivo:**
- O `intercalarDepoisDe` roda a outra requisição com a principal parada no PHP, então o deadlock real não se forma.
- `bloqueada` aceita 1205 e 1213 sem distinguir (`CODIGOS_DE_TRAVA`).
- Com a ordem antiga o teste também passa. A própria Rodada 9 registra que o mutante f sobrevive a ele.
- Quem ler o comentário acha que a corrida está coberta.

**Impacto:**
Falsa sensação de cobertura em concorrência, a área com mais achados do B1 (AP-01).

---

## [BAIXO] CR-3-05 — A D-GER-31 ficou pela metade: o plano e o COMECE-AQUI ainda dizem que o funcional é aprovado pelo dono do módulo

**Arquivo:** `docs/plano-de-desenvolvimento.md:12,113`; `docs/00-COMECE-AQUI.md:33`; `docs/funcional/README.md:3,47`
**Trecho atual:**
```markdown
| **Lucas Scholz** | Dono do produto. Aprova funcional (F1, F8), spec de todo bloco e todo PR; …
- [ ] Os "pontos a validar" do funcional que o Rafael respondeu estão refletidos …
4. **`docs/funcional/`**: como cada bloco funciona na tela, aprovado pelo dono do módulo (D-GER-28).
```

**Ajuste necessário:**
- `plano:12`: "Aprova os funcionais (F1 a F8, D-GER-31)".
- `plano:113`: "que o Lucas Scholz respondeu (com o feedback do Rafael)".
- `COMECE-AQUI:33` e `README:3,47`: "aprovado por Lucas Scholz (D-GER-28, D-GER-31)".

**Motivo:**
- A D-GER-31 (`necessidade.md:57`) diz que Lucas Scholz aprova os funcionais de todos os blocos e que o Rafael não assina aprovação.
- O commit `f1bebc7` corrigiu F2 a F7, o escopo e parte do plano, mas deixou estas linhas.
- O `00-COMECE-AQUI.md` é a porta de leitura do B2.

**Impacto:**
Quem começar o B2 pelo COMECE-AQUI procura a aprovação do funcional com a pessoa errada.

---

## Registrado, sem achado

- **Push e CI.**
  - O PR #5 no GitHub está em `2b076bf`, e os 37 commits dos ciclos 1 e 2 e da Rodada 9 nunca passaram pelo CI. O gate verde é local.
  - O `AGENTS.md:78` diz "falta o merge do Lucas"; antes faltam o push e o CI verde.
  - É ação do Lucas, não código do Cauã.
- **O commit `f1bebc7` (D-GER-31) está no PR do B1.**
  - Muda quem aprova F2 a F7, o README dos funcionais, o plano e o escopo §7: governança de outros blocos.
  - É decisão e commit do próprio Lucas e não contradiz o código, então não é achado.
  - Vale declarar na descrição do PR ou separar junto com o CR-3-05.
- **`validation.md:664` (U3, rodada 3)** cita `isReset === true`, que não existe mais. É evidência histórica de uma rodada antiga, e o relatório se escreve por rodadas: a Rodada 9 é a vigente. Não se reescreve rodada antiga.
- **`tests/Feature/PasswordFlowTest.php:242`** perdeu o `&& $mail->isReset === false` sem asserção substituta (a `:270` ganhou a do assunto). Como o ramo deixou de existir, o afrouxamento é só formal.
- **`useRowAction`: a trava liga antes do `onStart`.**
  - `guarded` faz `running.current = true` antes do `onStart` (`use-row-action.ts:44-48`).
  - Se um dia um ouvinte `router.on('before')` cancelar a visita, nem `onStart` nem `onFinish` rodam e a trava fica presa até recarregar a página.
  - Hoje nenhum ouvinte cancela: o de `search-input.tsx:78` não chama `preventDefault`. Vale como nota para quem mexer no router.
- **Filtro de data.** Buscar ou trocar um select com o "De" pela metade manda `from=''` (sem período). É o mesmo valor que o timer do CR-1-07 já mandaria em 300 ms: não piora.
- **Contato anonimizado pode ser excluído pela rota** quando nunca foi citado. A D5 proíbe só editar, reativar e anonimizar de novo. Fica para o Lucas, só se ele quiser essa trava; o risco de dado está no CR-3-02.
- **Segurança, §11.** As seis perguntas não trouxeram outro achado:
  - grupo `admin.` com `auth`, `backoffice` e `password.current` (`routes/web.php:58-85`);
  - todas as ações autorizam;
  - os Presenters repetem byte a byte os `row()` antigos (comparado com `git show 440c4c1`), sem campo novo para o front;
  - `throttle:login` em login, forgot, reset e first-access (`web.php:26,31,37,53`; `AppServiceProvider.php:83-98`);
  - nenhum segredo no diff (só valores sintéticos de teste);
  - senha e token fora de `changes` (`AuditService::NEVER_AUDITED`).
- **Fora da tarefa:** nada de código.
  - O `AGENTS.md` ganhou três regras em "Um jeito só" (Presenter, `RefusesDuplicates`, `Like`) e linhas na tabela de componentes, o que os CR-2-03, CR-2-11 e CR-2-12 pediam.
  - `composer.json`, `package.json` e `tests/Arch/divida.php` intactos.
  - Services abaixo do teto da A6: `ContactService` 279, `UserService` 272, `ClientService` 258.

## Está bem feito

- Os 12 achados do ciclo 2 foram tratados, um por commit, com o ID no título. Os que pediam código têm teste ou e2e.
- A Rodada 9 cobre com mutação o que o CR-2-02 pediu, com 20 mutantes mortos, e admite com honestidade o que não prova (o mutante f no teste de corrida, as telas sem mutação).
- `RefusesDuplicates` relança o que não reconhece, e o `UserService` passou a conferir o nome do índice: um unique novo em `users` não vira "e-mail em uso".
- O `useRowAction` resolve o CR-2-03 e o CR-2-07 juntos, com a guarda por ref (dois cliques no mesmo quadro) e o e2e que prova um POST só.
- Os Presenters tiraram a última montagem de linha dos controllers sem mudar a lógica, e a convenção entrou no `AGENTS.md` no mesmo commit.

---

## Resumo executivo

**Achados deste ciclo:** 5 no total, todos ligados a concorrência ou a documento.
- 0 Crítico
- 0 Alto
- 1 Médio: CR-3-01
- 4 Baixo: CR-3-02 a CR-3-05

**Pelo vocabulário da revisão em camadas (`testes.md` §10), para a descrição do PR:**

| Classe | Achados | Observação |
|---|---|---|
| **Bloqueante** | 0 | Nenhum Crítico nem Alto |
| **Deveria corrigir** (corrige ou justifica no PR) | 1 | CR-3-01: auditoria de desativação que não aconteceu. Nasceu da correção do CR-1-15, que fica ⚠️ parcial até ele sair |
| **Detalhe** (a critério de quem fez) | 4 | CR-3-02 (snapshot do contato fora da trava), CR-3-03 (deadlock residual, anterior ao ciclo), CR-3-04 (teste de corrida que promete mais do que prova), CR-3-05 (D-GER-31 incompleta) |

**Achados anteriores (36):**
- 30 resolvidos e verificados no código.
- 1 parcial: CR-1-15, por causa do CR-3-01.
- 4 aguardando decisão do Lucas: CR-1-11, CR-1-12, CR-1-23 e CR-2-09, mais as abilities do CR-2-08.
- 1 em PR próprio: CR-1-01, PR #7 aberto.
- **0 regressões.**

**Para o Lucas decidir (não é código do Cauã):**
- CR-1-11, CR-1-12, CR-1-23, CR-2-09 e as abilities do CR-2-08.
- O merge do PR #7 (CR-1-01).
- O push dos 37 commits com CI verde no PR #5.
- Se o `f1bebc7` fica no PR do B1.

**Principais riscos:**
1. O AP-01 apareceu de novo, desta vez dentro da correção: decidir pela linha travada e escrever pela memória. Três dos cinco achados são check-then-act ou ordem de travas.
2. As correções ainda não passaram pelo CI do PR.

**Avaliação geral:**
- O ciclo 2 foi corrigido por inteiro e bem provado: escada verde (345 PHP, 68 e2e), Verifier com 20/20 mutantes mortos, nenhuma regressão de ID, nenhum teste afrouxado de fato.
- O que sobra é estreito: um Médio que pede três trocas de variável e um teste espelho, mais quatro detalhes.

**Recomendação final: apta com ajustes.**
- Antes do merge: corrigir ou justificar o CR-3-01, depois push com CI verde no PR #5.
- Os Baixos podem ir num PR de limpeza antes do B2. O CR-3-02 é o primeiro da fila, porque toca dado pessoal.
