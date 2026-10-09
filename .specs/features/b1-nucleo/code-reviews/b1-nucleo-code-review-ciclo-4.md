# Code Review — `feat/b1-nucleo` · Ciclo 4

**Data:** 2026-10-09
**História:** b1-nucleo (PR [WCJ-Tecnologia/wcj-hub#5](https://github.com/WCJ-Tecnologia/wcj-hub/pull/5), autor Cauã Soares)
**Branch:** `feat/b1-nucleo` (HEAD local `6dec4f4`) · **Base:** `main` (branch do ambiente, `AGENTS.md` → Ambientes)
**Spec:** `.specs/features/b1-nucleo/`
**Revisor:** Claude Code (skill `wcj-code-review`, sessão de revisão com contexto zerado). Três camadas em subagentes Opus sem contexto (segurança, requisitos, qualidade/regressão) e a escada num subagente Sonnet. Esta sessão consolidou e reverificou no código; o achado principal (CR-4-01) foi reproduzido em execução.
**Ciclos anteriores:** [Ciclo 1](b1-nucleo-code-review-ciclo-1.md) · [Ciclo 2](b1-nucleo-code-review-ciclo-2.md) · [Ciclo 3](b1-nucleo-code-review-ciclo-3.md)
**Escopo deste ciclo:**
- Os 5 commits de correção do ciclo 3 (`f47411f..6dec4f4`: 13 arquivos, +308 / −46), com os Services tocados lidos inteiros e o `vendor/laravel` conferido (`ManagesTransactions`, `ConcurrencyErrorDetector`, `HasAttributes::originalIsEquivalent`, `Model::finishSave`).
- A branch inteira contra `main`, pelas seis perguntas de `seguranca.md` §11. Desde o ciclo 3 nenhuma rota, controller, FormRequest, Policy, middleware ou tela mudou.
- Working tree limpo.

> **Atenção:** o HEAD local `6dec4f4` está 43 commits à frente de `origin/feat/b1-nucleo` (`2b076bf`). O PR #5 e o CI ainda mostram o estado anterior ao ciclo 1. O push é de quem integra, depois deste ciclo, e não entra como achado.

**Escada de verificação (worktree `wcj-hub-pr5`, 09/10, MySQL 8 da 3306, HEAD `6dec4f4`):**
- `composer lint` ✅
- `php artisan test` ✅ 354 testes, 2 898 asserções, 0 falhas, 0 skipped (66 s)
- `npm run typecheck` ✅ · `npm run lint` ✅ · `npm run build` ✅
- `npm run specs:check` ✅ 0 problemas; avisos T35 e T50 "Tests: none", como antes
- `npm run test:guard` ✅ 1 053
- Playwright ✅ 68/68 (67 testes + setup; 0 skipped, 0 flaky)
- `git status` vazio ao final; servidor da 8000 morto

**Reprodução do CR-4-01:** o cenário do `UserDeadlockRepeteTest::test_reenviar_convite_repetido_…` rodado com uma asserção a mais, num arquivo de teste fora do repositório (scratchpad), contra o MySQL de teste: `must_change_password` no banco depois da repetição = `false`; auditoria = `{"must_change_password":[false,true]}`.

---

## Status dos achados anteriores

Reverificados **no código atual** (HEAD `6dec4f4`), não pelo relatório anterior nem pelas mensagens de commit.

### Ciclo 1

| ID | Criticidade | Título | Status | Evidência |
|---|---|---|---|---|
| CR-1-01 | Alto | Reenviar convite não derruba as sessões abertas do alvo | ↪️ Tratado em PR próprio, fora desta branch (DA-05) | `UserService.php:79-93` segue sem `terminateOthers`, de propósito. PR [#7](https://github.com/WCJ-Tecnologia/wcj-hub/pull/7) OPEN, sem merge (`gh pr view 7`, 09/10). Ao integrar: o `resendInvite` agora repete a transação, então o `terminateOthers` do #7 entra no callback e precisa ser repetível; corrigir o CR-4-01 antes ou junto. Achado de segurança: quem marca como corrigido é o Lucas |
| CR-1-02 | Médio | CNPJ ou e-mail repetido em gravação simultânea responde 500 | ✅ Resolvido | `RefusesDuplicates.php:25-31`; `ClientService.php:74-79,93-97`; `UserService.php:57-70,107-132`; `ClientRepetidoConcorrenciaTest.php` |
| CR-1-03 | Médio | `ContactController::update` deixa `DomainException` virar 500 | ✅ Resolvido | `Admin/ContactController.php:57`, dentro do `attempt()` |
| CR-1-04 | Médio | "A WCJ não pode ser excluída" só na Policy | ✅ Resolvido | `ClientService.php:138-139`; `ClientPolicy.php:67` |
| CR-1-05 | Médio | NUC-13 sem porta de entrada; AC 3 sem prova | ✅ Resolvido | `UserService.php:222` e `ClientService.php:184` usam `inspectFor()->authorize()` |
| CR-1-06 | Médio | Spec reescrita sem nova aprovação | ✅ Resolvido | `spec.md:5`: "Approved by: Lucas Scholz, 2026-10-09" |
| CR-1-07 | Médio | Filtro de data da auditoria dispara uma visita por tecla | ✅ Resolvido | `audit-logs/audit-log-filters.tsx:83,93-105`; `e2e/filtro-de-data.spec.ts` |
| CR-1-08 | Médio | Botões da linha de Usuários por ability global | ✅ Resolvido | `UserPresenter.php:28-34` |
| CR-1-09 | Médio | Esqueleto de lista em 4 páginas, 3 padrões de linha | ✅ Resolvido | `ListBody` nas quatro páginas de `pages/admin/*/index.tsx`; quatro Presenters |
| CR-1-10 | Médio | Busca com debounce cancela escrita em voo | ✅ Resolvido | `search-input.tsx:65-111`; `e2e/busca-e-escrita.spec.ts` |
| CR-1-11 | Baixo | Senha provisória não expira, a spec diz que expira | ❌ Não resolvido, aguarda decisão do Lucas (DA-07) | `spec.md:226` igual |
| CR-1-12 | Baixo | Tela mais rígida que o servidor na carga diária | ❌ Não resolvido, aguarda decisão do Lucas (DA-07) | `user-form-dialog.tsx:87` `min="0.25"` |
| CR-1-13 | Baixo | Reenviar convite manda "nova senha" e audita o antes vazio | ✅ Resolvido | `UserService.php:82`; `TemporaryPasswordMail.php:24` |
| CR-1-14 | Baixo | Erro de cliente desativado numa chave que o formulário não mostra | ✅ Resolvido | `contact-form-dialog.tsx:39` |
| CR-1-15 | Baixo | Checar-e-agir fora da transação em `deactivate` e no contato | ✅ Resolvido (era ⚠️ no ciclo 3) | Os três `deactivate` decidem, gravam e auditam pelo `$locked`: `UserService.php:146-154`, `ClientService.php:110-116`, `ContactService.php:105-111`; testes espelho (CR-3-01) |
| CR-1-16 | Baixo | `Client` depende de classe de seeder | ✅ Resolvido | `Client.php:19,40` |
| CR-1-17 | Baixo | Reativar não sinaliza nem trava clique duplo | ✅ Resolvido | `hooks/use-row-action.ts`, usado em `users/index.tsx`, `clients/index.tsx`, `contact-actions.tsx` |
| CR-1-18 | Baixo | `LIKE` sem escapar `%` e `_` | ✅ Resolvido | `Like::contains` em `UserService.php:206-207`, `ClientService.php:168-170`, `ContactService.php:187-189`, `AuditLogQuery.php:167,176` |
| CR-1-19 | Baixo | Mensagens dependem de `APP_LOCALE` fora do runbook | ✅ Resolvido | `config/app.php:116`; `docs/DEPLOY.md:20` |
| CR-1-20 | Baixo | Sem prova em 375 px | ✅ Resolvido | `e2e/mobile.spec.ts:11` (verde na escada) |
| CR-1-21 | Baixo | e2e crítico limpa só no fim do corpo | ✅ Resolvido | `finally` em `e2e/nucleo.spec.ts:52` e `e2e/smoke.spec.ts:106` |
| CR-1-22 | Baixo | Componentes e hook fora da tabela de componentes | ✅ Resolvido | `AGENTS.md:121,129` |
| CR-1-23 | Baixo | Auditoria sem índice para `created_at` | ❌ Não resolvido, aguarda decisão do Lucas (DA-07) | `…create_audit_logs_table.php:42-44` sem índice novo |
| CR-1-24 | Baixo | `MAIL_MAILER=log` grava a senha provisória em claro | ✅ Resolvido (pela documentação) | `docs/DEPLOY.md:22` |

### Ciclo 2

| ID | Criticidade | Título | Status | Evidência |
|---|---|---|---|---|
| CR-2-01 | Médio | `deactivate` trava o alvo antes dos admins: deadlock vira 500 | ✅ Resolvido | `UserService.php:141` (admins, via `:263`) antes de `:146` (alvo); `UserServiceTest.php:491` prova a ordem pela consulta |
| CR-2-02 | Médio | Verifier e mutação não cobrem as correções do ciclo 1 | ✅ Resolvido | `validation.md:103` (Rodada 9). As correções do ciclo 3 não têm rodada: CR-4-02 |
| CR-2-03 | Médio | Trava de clique duplo copiada em três telas | ✅ Resolvido | `git grep busyRow\|setBusy` fora do hook: zero |
| CR-2-04 | Baixo | Excluir a WCJ não tem teste pela rota | ✅ Resolvido | `ClientControllerTest.php:125-219` |
| CR-2-05 | Baixo | Documentos não acompanharam a D14 nem a F1 1.2 | ✅ Resolvido | `necessidade.md:196` |
| CR-2-06 | Baixo | `reactivate` de Usuário e Cliente decide pela instância em memória | ✅ Resolvido | `UserService.php:163-169`, `ClientService.php:121-130`; `UserServiceTest.php:417,433`, `ClientServiceTest.php:270,286` |
| CR-2-07 | Baixo | Guarda de clique duplo vale para a página, o sinal só para a linha | ✅ Resolvido | `use-row-action.ts:62-64`; `row-actions.tsx` `disabled` |
| CR-2-08 | Baixo | Código morto depois do CR-1-13 e do CR-1-08 | ✅ Resolvido (`isReset`) · ❌ aguarda decisão do Lucas (abilities, DA-07) | Nenhum `isReset` em `app`, `resources`, `tests`. `delete_records`/`reset_passwords` seguem sem leitor (`HandleInertiaRequests.php:66,68`; `types/index.d.ts:12,14`) |
| CR-2-09 | Baixo | "Reenviar convite" aparece para usuário desativado | ❌ Não resolvido, aguarda decisão do Lucas (DA-07) | `UserPresenter.php:33` sem condição de ativo |
| CR-2-10 | Baixo | Select trocado logo depois pode desfazer o período recém-aplicado | ✅ Resolvido | `audit-log-filters.tsx:93-95,166` |
| CR-2-11 | Baixo | Captura do unique em dois formatos; `update` de cliente sem teste de corrida | ✅ Resolvido | `RefusesDuplicates.php:25-31`; `UserService.php:233-238` |
| CR-2-12 | Baixo | Linha da lista em dois lugares; `situation()` duplicado | ✅ Resolvido | quatro `*Presenter.php`; `lib/situation.ts:9`; `AGENTS.md:227` |

### Ciclo 3

| ID | Criticidade | Título | Status | Evidência |
|---|---|---|---|---|
| CR-3-01 | Médio | `deactivate` decide pela linha travada, mas grava e audita pela memória | ✅ Resolvido | `UserService.php:146-154` (setActive, log, revogação e sessões pelo `$locked`), `ClientService.php:110-116`, `ContactService.php:105-111`. Trava dos admins pelo banco: `UserService.php:259,265`. Testes espelho: `UserServiceTest.php:448`, `ClientServiceTest.php:300`, `ContactServiceTest.php:281`; `UserServiceTest.php:475` (situação lida da memória). O ramo "papel lido do banco" ficou sem teste: CR-4-03 |
| CR-3-02 | Baixo | Excluir contato tira o snapshot antes da trava | ✅ Resolvido | `snapshot($deleted)` dentro do callback nos três `delete` (`ContactService.php:130-140`, `ClientService.php:143-145`, `UserService.php:179-183`); `Deactivatable.php:136-145` passa a linha travada. `ContactAnonimizadoConcorrenciaTest.php:111` discrimina; `ClientServiceTest.php:424`, `UserServiceTest.php:560` |
| CR-3-03 | Baixo | Deadlock residual em escrita de admin-alvo | ⚠️ Parcial | `attempts: 3` em `resendInvite`, `update`, `deactivate`, `reactivate` e `delete` (`UserService.php:38,90,132,155,170,184`). O 500 sai, mas a repetição do `resendInvite` não é repetível e perde a troca obrigatória de senha: CR-4-01. A repetição também pega o 1205: CR-4-04 |
| CR-3-04 | Baixo | Teste de corrida promete "não deadlock" e não discrimina a ordem | ✅ Resolvido | Comentário honesto em `UltimoAdminConcorrenciaTest.php:63-68`, `assertSame(1205, $corrida->codigo)` em `:60,85`; `Concorrencia.php` expõe `codigo`. A asserção 1205 é fraca (1213 não se forma ali), e o próprio comentário diz isso |
| CR-3-05 | Baixo | D-GER-31 pela metade no plano e no COMECE-AQUI | ✅ Resolvido | `plano-de-desenvolvimento.md:12,113`, `00-COMECE-AQUI.md:33`, `funcional/README.md:3,47`. Os "dono do módulo" restantes (`necessidade.md:5`, `docs/atas`, `docs/pautas`) falam de Chamados e CRM, corretos |

**Evolução:**

| Rodada | Achados novos |
|---|---|
| Revisão 07/10 | 9 |
| Ciclo 1 | 24 |
| Ciclo 2 | 12 |
| Ciclo 3 | 5 |
| Ciclo 4 | 5 |

**Status neste ciclo, dos 41 achados anteriores:**
- 34 ✅ resolvidos: 20 do ciclo 1 (o CR-1-15 fechou), 10 do ciclo 2 (CR-2-08 conta na parte do `isReset`) e 4 do ciclo 3.
- 1 ⚠️ parcial: CR-3-03, por causa do CR-4-01.
- 4 ❌ aguardando decisão do Lucas: CR-1-11, CR-1-12, CR-1-23 e CR-2-09, mais as abilities do CR-2-08.
- 1 em PR próprio: CR-1-01.
- **0 regressões de ID.** Uma regressão de comportamento nasceu da correção do CR-3-03 (antes, deadlock dava 500 sem dado errado; agora a repetição grava estado errado): CR-4-01.

---

## Achados do Ciclo 4

## [MÉDIO] CR-4-01 — A repetição do `resendInvite` perde o `must_change_password = true`: a senha provisória vira definitiva e a auditoria registra a troca obrigatória que não aconteceu

**Arquivo:** `app/Platform/Services/UserService.php:79-93`; `app/Platform/Services/TemporaryPasswordService.php:29-39`; teste `tests/Feature/Nucleo/UserDeadlockRepeteTest.php:74-86`
**Trecho atual:**
```php
// Fora do callback: na repetição, o issue() da tentativa desfeita já mudou a memória.
$before = ['must_change_password' => (bool) $user->must_change_password];

$plain = DB::transaction(function () use ($user, $before, $actor) {
    $plain = $this->passwords->issue($user);   // forceFill(['password' => …, 'must_change_password' => true])->save() em $user
    $this->audit->log(AuditActionEnum::PASSWORD_RESET, $user, $before, ['must_change_password' => true], $actor->id);

    return $plain;
}, self::ATTEMPTS);
```

**Ajuste necessário:**
- Tornar o callback repetível como o `update` já é (`UserService.php:102-108`): guardar `getAttributes()`/`getRawOriginal()` antes e restaurar no início do callback, ou emitir a senha numa instância relida dentro do callback.
- No teste, acrescentar `$this->assertTrue($dev->fresh()->must_change_password)`.

**Motivo:**
- Tentativa 1: o `save()` do `issue()` grava e o `Model::finishSave()` roda `syncOriginal()`. O original em memória passa a `must_change_password = true`.
- O deadlock do CR-3-03 acontece no insert da auditoria (FK `audit_logs.user_id`), depois do `save`. O Laravel faz rollback e repete (`ManagesTransactions.php:107-111`); no banco a flag volta a `false`.
- Tentativa 2: `forceFill(true)` não fica sujo (`originalIsEquivalent`: `true === true`). O `UPDATE` grava só `password` e `updated_at`.
- Resultado, quando o alvo já tinha trocado a senha (flag `false`, o caso comum do "Reenviar convite" para quem já entrou; `UserPolicy::resendInvite` só exige admin):
  - a senha provisória vai em claro por e-mail e passa a valer sem troca: `EnsurePasswordIsCurrent.php:19` só obriga com a flag `true` (N1, Usuários AC 3);
  - a auditoria grava `must_change_password: [false, true]`, estado que não existe (G3, AC 16).
- Reproduzido em execução: mesmo cenário do teste, com a asserção a mais, deu `false` no banco e `[false, true]` na auditoria.
- O teste novo simula exatamente esse caminho, mas confere só o `changes` (que vem do `$before` de fora) e o hash do e-mail, e passa com o defeito. O docblock da classe ("a repetição grava o mesmo que uma tentativa só") não vale para este caso.
- AP-01 de novo: a correção de concorrência abriu um achado de concorrência. A regra "callback repetível" do comentário `UserService.php:35-38` foi aplicada no `update` e esquecida no `resendInvite`.

**Sugestão:**
Repetir no `resendInvite` o padrão do `update` (duas linhas) e a asserção no teste. Conferir junto o merge do PR #7 (CR-1-01): o `terminateOthers` vai para dentro do mesmo callback repetido.

**Impacto:**
- Gatilho raro: deadlock (três admins agindo ao mesmo tempo, CR-3-03) ou espera de trava acima de 50 s no reenvio.
- Quando acontece, a propriedade de segurança se perde em silêncio: senha que trafegou por e-mail fica permanente e a auditoria afirma o contrário. Antes da correção, o mesmo caso dava um 500 sem dado errado.

---

## [MÉDIO] CR-4-02 — As correções do ciclo 3 não passaram pelo Verifier (DA-08)

**Arquivo:** `.specs/features/b1-nucleo/validation.md:3-5,103`
**Trecho atual:**
```markdown
**Result**: PASS na rodada 9. As correções dos ciclos 1 e 2 da `wcj-code-review` (`2b076bf..cb509d9`, 34 commits) …
```

**Ajuste necessário:**
Rodada 10 do Verifier (agente separado, com mutação) sobre `e9b2731..6dec4f4`, incluindo o CR-4-01 e o CR-4-03 depois de corrigidos.

**Motivo:**
- O intervalo mexe em desativação dos três cadastros (CR-3-01), no "antes" da exclusão (CR-3-02), na regra do último administrador (`UserService.php:259,265`) e em repetição de transação em toda escrita de usuário (CR-3-03).
- B1 é trilho completo, e exclusão e regra de permissão pedem Verifier com mutação (`AGENTS.md` "Trilhos"; DA-08). O ciclo 3 já registrou na DA-08 que estas correções pediam rodada nova.
- O `validation.md` não tem "Rodada 10" nem menção a `CR-3-0x`; o último commit nele é `fef3766`, anterior ao ciclo 3.
- O CR-4-01 e o CR-4-03 são o tipo de lacuna que uma rodada com sensor de discriminação pega.

**Sugestão:**
Rodada focada, como a 9: uma linha de evidência por correção e os mutantes "escreve pela memória" (CR-3-01), "snapshot da instância" (CR-3-02), "releitura do papel removida" (CR-4-03) e "sem restauração da memória no `resendInvite`" (CR-4-01).

**Impacto:**
Correções de concorrência e de exclusão entram no merge sem a prova independente que o trilho exige.

---

## [BAIXO] CR-4-03 — O ramo novo "admin pelo banco" do `ensureNotLastActiveAdmin` não tem teste

**Arquivo:** `app/Platform/Services/UserService.php:259`
**Trecho atual:**
```php
if (! $user->isAdmin() && ! User::query()->findOrFail($user->getKey())->isAdmin()) {
    return;
}
```

**Ajuste necessário:**
Teste com a instância carregada como desenvolvedor, promovida a admin por outra instância e que fica sendo a única admin ativa: `deactivate`, `delete` e o rebaixamento por ela têm de recusar com "último administrador ativo".

**Motivo:**
- Todos os testes do último administrador carregam o alvo como admin em memória: `UserServiceTest.php:475` (admin inativa), `:491`, `:598`; `UltimoAdminConcorrenciaTest.php`; `UserControllerTest.php:194`.
- O mutante `if (! $user->isAdmin()) return;` (tira a releitura) passa na suíte. A releitura era metade do ajuste pedido no CR-3-01.

**Sugestão:**
Espelho do `UserServiceTest.php:475` trocando "admin inativa" por "dev promovido".

**Impacto:**
Pela rota a janela é de milissegundos; cresce com quem chamar o Service guardando a instância (B2, MCP no B8). Sem teste, uma refatoração tira a releitura sem ninguém ver.

---

## [BAIXO] CR-4-04 — `attempts: 3` também repete o 1205: espera de até ~150 s segurando a trava dos admins, e a tela pode receber 504 enquanto a escrita ainda commita

**Arquivo:** `app/Platform/Services/UserService.php:35-38` e as cinco transações (`:90,132,155,170,184`)
**Trecho atual:**
```php
// … O Laravel também repete no "Lock wait timeout". Os callbacks precisam ser repetíveis.
private const ATTEMPTS = 3;
```

**Ajuste necessário:**
Decisão do Lucas (mexe em configuração): repetir só o deadlock (1213), ou baixar o `innodb_lock_wait_timeout` da sessão na conexão MySQL e documentar no `docs/DEPLOY.md`, ou registrar que a espera longa é aceita.

**Motivo:**
- `ConcurrencyErrorDetector.php:34` conta "Lock wait timeout exceeded" como erro de concorrência e `ManagesTransactions.php:109-111` repete.
- O projeto não configura `innodb_lock_wait_timeout` (nada em `config/database.php`, `docker-compose.yml`, `docs/DEPLOY.md`); só os testes usam 2 s (`Concorrencia.php:128`, `BancoDeVerdade.php:45-48`). Em produção vale o padrão do MySQL, 50 s.
- Em `deactivate`, `delete` e rebaixamento, cada tentativa trava em X os admins ativos (`:263`) antes de esperar o alvo; toda escrita auditada de outro admin espera junto (S da FK no ator).
- Pior caso ~150 s numa requisição web. O proxy corta antes (o padrão do nginx é 60 s): a tela mostra erro e a 2ª ou 3ª tentativa pode commitar depois. No "Reenviar convite", o admin clica de novo e sai um segundo e-mail com outra senha.
- Antes da correção: 50 s, 500, nada gravado.

**Sugestão:**
A de menor risco é um timeout de sessão curto (alguns segundos) na conexão, que vale para o sistema todo e mantém a repetição útil no deadlock.

**Impacto:**
Só com transação presa por mais de 50 s, que o B1 não tem (gatilho externo: sessão SQL manual, manutenção). Amplifica, não cria. Disponibilidade, sem dado errado.

---

## [BAIXO] CR-4-05 — Desativar e reativar repetem o mesmo esqueleto em seis métodos; a correção do CR-3-01 manteve as seis cópias

**Arquivo:** `UserService.php:146-154` e `:163-169`; `ClientService.php:110-116` e `:121-130`; `ContactService.php:105-111` e `:117-127`
**Trecho atual:**
```php
$locked = Client::query()->lockForUpdate()->findOrFail($client->getKey());
if (! $locked->isActive()) {
    return;
}

$locked->setActive(false, $actor)->save();
$this->audit->log(AuditActionEnum::DEACTIVATED, $locked, ['active' => true], ['active' => false], $actor->id);
```

**Ajuste necessário:**
Um método no `Deactivatable`, no molde do `deleteIfUnused(?Closure $then)` (`Deactivatable.php:133-150`), que trava, decide, grava e chama `$then($locked)`; cada Service passa só a auditoria e o efeito próprio (revogar IA, derrubar sessão, recusar anonimizado).

**Motivo:**
- O CR-2-06 corrigiu os `reactivate` e o espelho ficou para trás até virar o CR-3-01 (AP-01 e AP-02). O esqueleto continua em seis cópias.
- B2 a B7 vão desativar etapa, área, tipo de tarefa e motivo de retrabalho: copiam o esqueleto de novo.
- O projeto já tem o molde certo no trait.

**Sugestão:**
Fazer antes do B2, num PR de limpeza, com os testes espelho atuais como rede.

**Impacto:**
A próxima correção de concorrência em ativar/desativar volta a ser aplicada em uma cópia e esquecida em outra.

---

## Registrado, sem achado

- **Push e CI.** O PR #5 no GitHub está em `2b076bf`; os 43 commits dos ciclos 1, 2 e 3 e da Rodada 9 nunca passaram pelo CI. O gate verde é local. É de quem integra.
- **`AGENTS.md` não acompanhou o ciclo 3.** "Estado atual" (`:78`) ainda descreve só os ciclos 1 e 2, e a convenção nova ("escrita de usuário em `DB::transaction(..., attempts)`, callback repetível: restaurar a memória e nada fora do banco dentro dele") não está em "Um jeito só" nem em "Armadilhas". Não contradiz o código; vale incluir junto da correção do CR-4-01. Registrado no `PROJECT-PATTERNS.md` (CV-03).
- **Comentários que prometem mais que o código.** `UserService.php:258` ("Quem decide é o conjunto travado abaixo") só vale quando a releitura sem trava diz "admin"; se diz "não admin", retorna antes. A janela residual (promoção commitada entre a releitura e a trava do alvo) precisa de mais duas escritas simultâneas do mesmo admin para zerar administradores; sem impacto prático, e mais estreita que antes. `Concorrencia.php:57` ("cai por timeout (2 s)") ficou impreciso: com o `deactivate` repetindo, os dois testes de `UltimoAdminConcorrenciaTest` esperam ~3 × 2 s. Determinístico, só mais lento.
- **`before` do `update` pela memória.** `UserService::update` (`:114`) e `ClientService::update` tiram o `before` da instância sem trava no alvo; o `ContactService::update` usa `lockedNotAnonymized`. Impacto só em edição simultânea do mesmo registro, sem dado anonimizável. Pedir trava aqui seria "trava que ninguém pediu".
- **Client e Contact sem `attempts`.** Coerente: travam uma linha de negócio e depois o ator em S pela FK; nenhuma escrita do B1 trava o ator em X e depois pede cliente ou contato, então não há ciclo. O `invite` sem `attempts` também é coerente (a linha nasce no callback).
- **Repetição em `deactivate`, `reactivate` e `delete`** é segura: o `$locked` é relido a cada tentativa; sessões (`config/session.php`, driver `database`) e tokens de senha estão na conexão padrão e voltam no rollback; o `deleteIfUnused` aninhado lança `DeadlockException` e a transação externa repete inteira. A auditoria não duplica (mesma conexão, desfeita no rollback). O e-mail sai depois do commit, uma vez.
- **Segurança, §11.** As seis perguntas não trouxeram outro achado: grupo `admin.` com `auth`, `backoffice` e `password.current` (`routes/web.php:58-85`); toda ação autoriza; nenhuma rota, controller, Policy ou tela mudou desde o ciclo 3; `throttle:login` em login, forgot, reset e first-access (`web.php:26,31,37,53`); nenhum segredo no diff; `AuditService::NEVER_AUDITED` intacto.
- **Fora da tarefa:** nada. O diff do ciclo toca só os 13 arquivos das correções; sem `whereNull('deactivated_at')` à mão, sem `AuditLog::create` fora do Service. Services abaixo do teto da A6 (`UserService` 289, `ContactService` 280, `ClientService` 258).

## Está bem feito

- Os cinco achados do ciclo 3 foram tratados, um por commit, com o ID no título; quatro estão resolvidos por inteiro e com teste que discrimina.
- Os testes espelho do CR-3-01 cobrem os três cadastros, e o de Usuário confere também sessões, conexões de IA e que a segunda chamada não faz nada.
- O teste do CR-3-02 no contato (`ContactAnonimizadoConcorrenciaTest.php:111`) prova a propriedade que importa (o dado substituído não volta à auditoria), não só a mecânica.
- O `update` restaurou a memória da instância antes de repetir, com o comentário do porquê: é o molde da correção do CR-4-01.
- O CR-3-04 foi corrigido com honestidade: o comentário diz o que o teste prova e o que não prova.

---

## Resumo executivo

**Achados deste ciclo:** 5 no total.
- 0 Crítico
- 0 Alto
- 2 Médio: CR-4-01, CR-4-02
- 3 Baixo: CR-4-03, CR-4-04, CR-4-05

**Pelo vocabulário da revisão em camadas (`testes.md` §10), para a descrição do PR:**

| Classe | Achados | Observação |
|---|---|---|
| **Bloqueante** | 0 | Nenhum Crítico nem Alto |
| **Deveria corrigir** (corrige ou justifica no PR) | 2 | CR-4-01 (repetição do reenvio perde a troca obrigatória de senha e falseia a auditoria; reproduzido) · CR-4-02 (Rodada 10 do Verifier sobre as correções do ciclo 3) |
| **Detalhe** (a critério de quem fez) | 3 | CR-4-03 (releitura do papel sem teste) · CR-4-04 (repetição no 1205, decisão de configuração do Lucas) · CR-4-05 (esqueleto de ativar/desativar em seis cópias) |

**Achados anteriores (41):**
- 34 resolvidos e verificados no código (o CR-1-15 fechou neste ciclo).
- 1 parcial: CR-3-03, por causa do CR-4-01.
- 4 aguardando decisão do Lucas: CR-1-11, CR-1-12, CR-1-23 e CR-2-09, mais as abilities do CR-2-08.
- 1 em PR próprio: CR-1-01, PR #7 aberto.
- **0 regressões de ID**; 1 regressão de comportamento introduzida pela correção do CR-3-03 (CR-4-01).

**Para o Lucas decidir (não é código do Cauã):**
- CR-1-11, CR-1-12, CR-1-23, CR-2-09 e as abilities do CR-2-08.
- CR-4-04: repetir só o deadlock ou baixar o `innodb_lock_wait_timeout` da sessão.
- O merge do PR #7 (CR-1-01), depois ou junto da correção do CR-4-01.
- O push dos 43 commits com CI verde no PR #5.

**Principais riscos:**
1. O AP-01 apareceu pela quarta vez seguida dentro da própria correção: agora a repetição de transação com instância em memória já salva. O risco do B1 continua concentrado em concorrência.
2. As correções dos ciclos 1 a 3 ainda não passaram pelo CI do PR, e as do ciclo 3 não passaram pelo Verifier.

**Avaliação geral:**
- O ciclo 3 foi corrigido quase por inteiro e bem testado: escada verde (354 PHP, 68 e2e), nenhuma regressão de ID, nenhum teste afrouxado.
- O que sobra é estreito: um Médio de duas linhas mais uma asserção (CR-4-01), a rodada do Verifier e três detalhes.

**Recomendação final: apta com ajustes.**
- Antes do merge: corrigir o CR-4-01 (toca senha; não deixar para depois), rodar a Rodada 10 do Verifier (CR-4-02), depois push com CI verde no PR #5.
- CR-4-03 cabe junto da correção do CR-4-01. CR-4-04 depende do Lucas. CR-4-05 pode ir num PR de limpeza antes do B2.
