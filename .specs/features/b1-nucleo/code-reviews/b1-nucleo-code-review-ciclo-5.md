# Code Review — `feat/b1-nucleo` · Ciclo 5

**Data:** 2026-10-09
**História:** b1-nucleo (PR [WCJ-Tecnologia/wcj-hub#5](https://github.com/WCJ-Tecnologia/wcj-hub/pull/5), autor Cauã Soares)
**Branch:** `feat/b1-nucleo` (HEAD local `f2a4d7e`) · **Base:** `main` (branch do ambiente, `AGENTS.md` → Ambientes; `origin/main` = `2056834`)
**Spec:** `.specs/features/b1-nucleo/`
**Revisor:** Claude Code (skill `wcj-code-review`, sessão de revisão com contexto zerado). Três camadas em subagentes Opus sem contexto (segurança, requisitos, qualidade/regressão) e a escada num subagente Sonnet. Esta sessão consolidou, reverificou no código e reproduziu em execução o achado principal (CR-5-01).
**Ciclos anteriores:** [Ciclo 1](b1-nucleo-code-review-ciclo-1.md) · [Ciclo 2](b1-nucleo-code-review-ciclo-2.md) · [Ciclo 3](b1-nucleo-code-review-ciclo-3.md) · [Ciclo 4](b1-nucleo-code-review-ciclo-4.md)
**Escopo deste ciclo:**
- As correções do ciclo 4 (`64d1988..f2a4d7e`): CR-4-01 (`01f3896`), CR-4-03 (`38bb08e`), CR-4-04 com o trait `RetriesDeadlock` (`596f37e`), CR-4-05 com o `Deactivatable::setActiveLocked` (`df07195`), `AGENTS.md` (`7b96b12`), Fix 6 da rodada 10 (`7c228a8`) e as rodadas 10 e 11 do `validation.md`. Código: 8 arquivos, +237 / −74. Services, `Deactivatable` e `RetriesDeadlock` lidos inteiros; `vendor/laravel` (12.69.3) conferido em `ManagesTransactions`, `DeadlockException`, `QueryException`, `HasAttributes::originalIsEquivalent`/`setRawAttributes`, `Model::finishSave`.
- A branch inteira contra `main`, pelas seis perguntas de `seguranca.md` §11. Desde o ciclo 4 nenhuma rota, controller, FormRequest, Policy, middleware ou tela mudou.
- Working tree limpo.

> **Atenção:** o HEAD local `f2a4d7e` está 52 commits à frente de `origin/feat/b1-nucleo` (`2b076bf`). O PR #5 e o CI ainda mostram o estado anterior ao ciclo 1. O push é de quem integra, depois deste ciclo, e não entra como achado.

**Escada de verificação (worktree `wcj-hub-pr5`, 09/10, MySQL 8 local da 3306, HEAD `f2a4d7e`):**
- `composer lint` ✅
- `php artisan test` ✅ 358 testes, 2 921 asserções, 0 falhas, 0 skipped (57 s)
- `npm run typecheck` ✅ · `npm run lint` ✅ · `npm run build` ✅
- `npm run specs:check` ✅ 0 problemas; avisos T35 e T50 "Tests: none", como antes
- `npm run test:guard` ✅ 1 053
- Playwright ✅ 68/68 (67 testes + setup; 0 skipped, 0 flaky)
- `git status` vazio ao final; servidor da 8000 morto

**Reprodução do CR-5-01:** teste fora do repositório (scratchpad), contra o MySQL de teste, com `RefreshDatabase`: alvo carregado com `must_change_password = true`, flag zerada por outra instância, `resendInvite` pela instância velha. Resultado: `must_change_password` no banco = `false`; auditoria `password_reset` com `changes = null`; e-mail com a senha provisória enviado.

---

## Status dos achados anteriores

Reverificados **no código atual** (HEAD `f2a4d7e`). Desde o HEAD do ciclo 4 (`6dec4f4`) só mudaram `Deactivatable.php`, `RetriesDeadlock.php` (novo), os três Services, `UserDeadlockRepeteTest.php`, `UserServiceTest.php`, `AGENTS.md` e documentos de `.specs/` (`git diff --name-only 6dec4f4 HEAD`). Os achados cuja evidência está em arquivo intocado mantêm a evidência do ciclo 4, conferida por amostragem; os que dependem dos arquivos alterados foram relidos.

### Ciclo 1

| ID | Criticidade | Status | Evidência |
|---|---|---|---|
| CR-1-01 | Alto | ↪️ Tratado em PR próprio, fora desta branch (DA-05) | `UserService.php:76-93` segue sem `terminateOthers`, de propósito. PR [#7](https://github.com/WCJ-Tecnologia/wcj-hub/pull/7) OPEN, sem merge (`gh pr view 7`, 09/10). Ao integrar: o `terminateOthers` entra no callback do `transactionRetryingDeadlock` (repetível: linhas de `sessions` na conexão padrão). Achado de segurança: quem marca como corrigido é o Lucas |
| CR-1-02 | Médio | ✅ Resolvido | `RefusesDuplicates`; `ClientService.php:74-79,93-97`; `UserService.php:54-67,107-132` (o 1062 passa pelo `catch` do `RetriesDeadlock`, é relançado e cai no `writeOrRefuseDuplicate`) |
| CR-1-03 | Médio | ✅ Resolvido | `Platform/Controllers/Admin/ContactController.php:57` dentro do `attempt()` |
| CR-1-04 | Médio | ✅ Resolvido | `ClientService.php:126-127`; `ClientPolicy.php:67` |
| CR-1-05 | Médio | ✅ Resolvido | `UserService.php:210`, `ClientService.php:172` (`inspectFor()->authorize()`) |
| CR-1-06 | Médio | ✅ Resolvido | `spec.md:5` "Approved by: Lucas Scholz, 2026-10-09" |
| CR-1-07 | Médio | ✅ Resolvido | `components/audit-logs/audit-log-filters.tsx` intocado desde o ciclo 4; `e2e/filtro-de-data.spec.ts` verde |
| CR-1-08 | Médio | ✅ Resolvido | `UserPresenter.php:28-34` |
| CR-1-09 | Médio | ✅ Resolvido | `ListBody` nas quatro listas; quatro Presenters |
| CR-1-10 | Médio | ✅ Resolvido | `components/search-input.tsx` intocado; `e2e/busca-e-escrita.spec.ts` verde |
| CR-1-11 | Baixo | ❌ Não resolvido, aguarda decisão do Lucas (DA-07) | `spec.md:226` igual |
| CR-1-12 | Baixo | ❌ Não resolvido, aguarda decisão do Lucas (DA-07) | `components/users/user-form-dialog.tsx:87` `min="0.25"` |
| CR-1-13 | Baixo | ✅ Resolvido | `UserService.php:85-87` (`before` real); `TemporaryPasswordMail` intocado |
| CR-1-14 | Baixo | ✅ Resolvido | `components/contacts/contact-form-dialog.tsx` intocado |
| CR-1-15 | Baixo | ✅ Resolvido | Os três `deactivate` decidem, gravam e auditam pelo `$locked` via `setActiveLocked` (`Deactivatable.php:134-149`; `UserService.php:144`, `ClientService.php:109`, `ContactService.php:104`); testes espelho `UserServiceTest.php:399,448`, `ClientServiceTest.php:252,300`, `ContactServiceTest.php:263,281` |
| CR-1-16 | Baixo | ✅ Resolvido | `Client.php` intocado |
| CR-1-17 | Baixo | ✅ Resolvido | `hooks/use-row-action.ts` intocado |
| CR-1-18 | Baixo | ✅ Resolvido | `Like::contains` em `UserService.php:194-195` e nos demais, intocados |
| CR-1-19 | Baixo | ✅ Resolvido | `config/app.php`, `docs/DEPLOY.md` intocados |
| CR-1-20 | Baixo | ✅ Resolvido | `e2e/mobile.spec.ts` verde |
| CR-1-21 | Baixo | ✅ Resolvido | `finally` nos e2e, intocados |
| CR-1-22 | Baixo | ✅ Resolvido | Tabela de componentes do `AGENTS.md` intacta |
| CR-1-23 | Baixo | ❌ Não resolvido, aguarda decisão do Lucas (DA-07) | Sem migração nova |
| CR-1-24 | Baixo | ✅ Resolvido (pela documentação) | `docs/DEPLOY.md` intocado |

### Ciclo 2

| ID | Criticidade | Status | Evidência |
|---|---|---|---|
| CR-2-01 | Médio | ✅ Resolvido | `UserService.php:141` (admins, via `:251`) antes de `:144` (alvo, no `setActiveLocked`); `UserServiceTest.php:516` prova a ordem pela consulta |
| CR-2-02 | Médio | ✅ Resolvido | `validation.md` rodada 9 |
| CR-2-03 | Médio | ✅ Resolvido | Nenhuma tela mudou |
| CR-2-04 | Baixo | ✅ Resolvido | `ClientControllerTest.php` intocado |
| CR-2-05 | Baixo | ✅ Resolvido | `necessidade.md` intocado |
| CR-2-06 | Baixo | ✅ Resolvido | `reactivate` pelo `setActiveLocked(true, …)`: `UserService.php:156`, `ClientService.php:117`, `ContactService.php:113`; `UserServiceTest.php:417,433`, `ClientServiceTest.php:270,286` |
| CR-2-07 | Baixo | ✅ Resolvido | `use-row-action.ts` intocado |
| CR-2-08 | Baixo | ✅ Resolvido (`isReset`) · ❌ aguarda decisão do Lucas (abilities, DA-07) | `isReset`: zero em `app`, `resources`, `tests`. `delete_records`/`reset_passwords` seguem sem leitor (`HandleInertiaRequests.php:66,68`; `types/index.d.ts:12,14`) |
| CR-2-09 | Baixo | ❌ Não resolvido, aguarda decisão do Lucas (DA-07) | `UserPresenter.php:33` sem condição de ativo |
| CR-2-10 | Baixo | ✅ Resolvido | `audit-log-filters.tsx` intocado |
| CR-2-11 | Baixo | ✅ Resolvido | `RefusesDuplicates`; `UserService::emailTaken` `:221-226` |
| CR-2-12 | Baixo | ✅ Resolvido | Presenters e `lib/situation.ts` intocados |

### Ciclo 3

| ID | Criticidade | Status | Evidência |
|---|---|---|---|
| CR-3-01 | Médio | ✅ Resolvido | `setActiveLocked` decide, grava e passa o `$locked` ao `$then` (`Deactivatable.php:136-146`); releitura do papel `UserService.php:247` |
| CR-3-02 | Baixo | ✅ Resolvido | `snapshot($deleted)` dentro do `deleteIfUnused`: `UserService.php:168-171`, `ClientService.php:131-133`, `ContactService.php:123-125`; `ContactAnonimizadoConcorrenciaTest.php:111` |
| CR-3-03 | Baixo | ✅ Resolvido (era ⚠️ no ciclo 4) | As cinco escritas do `UserService` em `transactionRetryingDeadlock` (`:83,107,138,156,164`); callbacks repetíveis (`:84`, `:108`; os demais relêem o `$locked`); `UserDeadlockRepeteTest.php:76,89,108` |
| CR-3-04 | Baixo | ✅ Resolvido | `UltimoAdminConcorrenciaTest.php` intocado; com o 1205 sem repetição, o comentário de `Concorrencia.php` ("cai por timeout (2 s)") voltou a ser exato |
| CR-3-05 | Baixo | ✅ Resolvido | Documentos intocados |

### Ciclo 4

| ID | Criticidade | Status | Evidência |
|---|---|---|---|
| CR-4-01 | Médio | ✅ Resolvido (a repetição) | `UserService.php:80,84-85`: memória restaurada e `before` tirado a cada tentativa; `UserDeadlockRepeteTest.php:100` confere a coluna com `fresh()`; mutante "e" morto na rodada 10. A mesma consequência por outro caminho (instância velha, sem deadlock) é o CR-5-01, que é anterior e não regressão |
| CR-4-02 | Médio | ✅ Resolvido | `validation.md`: rodada 10 sobre `fef3766..7b96b12` (FAIL Minor, só o mutante g2) e rodada 11 sobre `b0a8d6f..7c228a8` (PASS). `7c228a8..HEAD` só mexe no `validation.md`. Amostragem das linhas de evidência conferida no código atual |
| CR-4-03 | Baixo | ✅ Resolvido | `UserServiceTest.php:491-511` (dev promovida, instância velha: desativar, excluir e rebaixar recusam com a mensagem exata); mutante "d" morto na rodada 10 |
| CR-4-04 | Baixo | ✅ Resolvido | `RetriesDeadlock.php:34-41` repete só quando `errorInfo[1] === 1213`; `UserDeadlockRepeteTest.php:123` (1205 sobe na 1ª tentativa, nada gravado, nenhum e-mail). Opção "repetir só o 1213" escolhida no `596f37e`, autor Lucas Scholz ("decisão de quem integra") |
| CR-4-05 | Baixo | ✅ Resolvido | `Deactivatable::setActiveLocked` (`:134-149`) nas seis chamadas (`UserService.php:144,156`; `ClientService.php:109,117`; `ContactService.php:104,113`); `grep '->setActive('` em `app` só acha o próprio trait, o `anonymize` (`ContactService.php:144`, outro fluxo) e o `LimparExemploCommand.php:76` (CLI sem ator, já na `main`). Lacuna menor de desenho: CR-5-02 |

**Evolução:**

| Rodada | Achados novos |
|---|---|
| Revisão 07/10 | 9 |
| Ciclo 1 | 24 |
| Ciclo 2 | 12 |
| Ciclo 3 | 5 |
| Ciclo 4 | 5 |
| Ciclo 5 | 2 |

**Status neste ciclo, dos 46 achados anteriores:**
- 40 ✅ resolvidos: 20 do ciclo 1, 10 do ciclo 2 (o CR-2-08 conta pela parte do `isReset`), 5 do ciclo 3 (o CR-3-03 fechou) e 5 do ciclo 4.
- 0 ⚠️ parciais.
- 4 ❌ aguardando decisão do Lucas: CR-1-11, CR-1-12, CR-1-23 e CR-2-09, mais as abilities do CR-2-08.
- 1 em PR próprio: CR-1-01 (PR #7).
- **0 regressões**, de ID e de comportamento. É o primeiro ciclo do B1 em que a correção de concorrência não abriu defeito novo de concorrência.

---

## Achados do Ciclo 5

## [MÉDIO] CR-5-01 — Reenviar convite grava pela instância carregada na requisição: se o alvo troca a senha no mesmo instante, a senha provisória vai por e-mail e vale sem troca obrigatória

**Arquivo:** `app/Platform/Services/UserService.php:80-92`; `app/Platform/Services/TemporaryPasswordService.php:29-39`
**Trecho atual:**
```php
[$attributes, $original] = [$user->getAttributes(), $user->getRawOriginal()];

$plain = $this->transactionRetryingDeadlock(function () use ($user, $actor, $attributes, $original) {
    $user->setRawAttributes($original, true)->setRawAttributes($attributes);
    $before = ['must_change_password' => (bool) $user->must_change_password];
    $plain = $this->passwords->issue($user);   // forceFill(['password' => …, 'must_change_password' => true])->save()
    $this->audit->log(AuditActionEnum::PASSWORD_RESET, $user, $before, ['must_change_password' => true], $actor->id);

    return $plain;
});

return $this->passwords->send($user, $plain);   // e-mail da memória
```

**Ajuste necessário:**
- Emitir a senha numa linha relida com trava dentro do callback: `$locked = User::query()->lockForUpdate()->findOrFail($user->getKey())`, `$before` dela, `issue($locked)`, `audit->log(…, $locked, …)` e devolver `[$locked, $plain]`; `send($locked, $plain)` depois do commit.
- Com isso o callback fica repetível por construção e as duas linhas de restauração saem.
- Teste: instância carregada com a flag `true`, flag zerada por outra instância, `resendInvite` pela velha; `assertTrue($dev->fresh()->must_change_password)` e auditoria `{"must_change_password":[false,true]}`.

**Motivo:**
- A correção do CR-4-01 escolheu restaurar a memória: o callback ficou repetível em relação à instância de chegada, não ao banco. O que vai para o `UPDATE` continua decidido pelo "sujo" dessa instância.
- Caminho pela rota (`POST admin/users/{user}/reset-password`, `UserController.php:84-86`):
  1. o route binding carrega o alvo com `must_change_password = true` (convidado que ainda não entrou);
  2. antes do `UPDATE` do admin, o próprio alvo conclui o primeiro acesso ou o "esqueci a senha" (`ChangePasswordController.php:50-53`, `NewPasswordController.php:50-53`) e grava a flag `false`;
  3. no `issue()`, `forceFill(true)` compara com o original `true` e não fica sujo (`originalIsEquivalent`); o `UPDATE` grava só `password` e `updated_at`.
- Resultado: a senha provisória trafega em claro por e-mail e passa a valer sem troca (`EnsurePasswordIsCurrent.php:19` só obriga com a flag `true`; N1, Usuários AC 3). A auditoria grava `password_reset` com `changes = null` (o `before` e o `after` são `true`; `AuditService.php:155` filtra), escondendo que a flag ficou `false` (G3, AC 16).
- **Reproduzido** em execução (cabeçalho): banco `false`, `changes = null`, e-mail enviado.
- Variante menor do mesmo mecanismo: o e-mail sai de `$user->email` da memória; outro admin que troque o e-mail do alvo nessa janela faz a senha ir para o endereço antigo.
- É o sinal do AP-01 ("escreve pela instância em memória") e desvio do CV-03. Os seis ativar/desativar e os três `delete` já escrevem pela linha travada; o `resendInvite` é a escrita de usuário que ficou de fora.
- Não é regressão: o comportamento existe desde o B1. O ciclo 4 descreveu as duas saídas ("restaurar … ou emitir a senha numa instância relida"); a escolhida fecha a repetição e deixa este caminho aberto.

**Sugestão:**
A releitura com trava, como acima. A ordem de travas não muda: o `UPDATE` já pegava X no alvo antes da S do ator pela FK da auditoria, e o `resendInvite` não trava admins. Fazer junto do merge do PR #7 (CR-1-01), cujo `terminateOthers` entra neste mesmo callback e também deve receber o `$locked`.

**Impacto:**
- Gatilho raro: o admin reenviar o convite no mesmo instante em que o convidado conclui o primeiro acesso (janela da ordem de milissegundos); cresce com quem chamar o Service guardando a instância (B2, MCP no B8).
- Quando acontece, a propriedade de segurança se perde em silêncio: senha que passou por e-mail fica permanente e a auditoria não registra a mudança. Mesma consequência do CR-4-01, que foi classificado Médio.

---

## [BAIXO] CR-5-02 — `setActiveLocked` depende de transação aberta por quem chama, sem conferir; o `deleteIfUnused`, do mesmo trait, abre a dele

**Arquivo:** `app/Platform/Models/Concerns/Deactivatable.php:134-149` (contra `:158-175`)
**Trecho atual:**
```php
public function setActiveLocked(bool $active, User $actor, ?Closure $then = null): bool
{
    $locked = static::query()->lockForUpdate()->findOrFail($this->getKey());
    …
    $locked->setActive($active, $actor)->save();
    if ($then !== null) {
        $then($locked);
    }
```

**Ajuste necessário:**
Envolver o corpo em `DB::transaction(...)`, como o `deleteIfUnused` (aninhada, cria savepoint; um deadlock sobe como `DeadlockException`, que o `RetriesDeadlock.php:39` já trata), ou recusar com `LogicException` quando `DB::transactionLevel() === 0`.

**Motivo:**
- O docblock diz "Call inside the write's transaction"; nada garante. Hoje os seis chamadores estão em transação (`ClientService.php:109,117`, `ContactService.php:104,111`, `UserService.php:138,156`).
- Chamada solta (B2 a B7 vão desativar etapa, área, tipo de tarefa, motivo de retrabalho, copiando o padrão) solta a trava no autocommit do `SELECT … FOR UPDATE`, e gravar e auditar viram dois commits, contra o G3 ("na mesma transação") e o CV-03.
- Nenhum teste pegaria: o `RefreshDatabase` envolve cada teste numa transação e esconde a falta dela no Service.
- O CR-4-05 pediu o molde do `deleteIfUnused`; o molde abre a própria transação.

**Sugestão:**
`return DB::transaction(function () use ($active, $actor, $then) { … });` no `setActiveLocked`. Cabe no PR de limpeza antes do B2 ou junto do CR-5-01.

**Impacto:**
Nenhum defeito hoje. Protege as cópias dos próximos blocos (AP-02).

---

## Registrado, sem achado

- **Push e CI.** O PR #5 no GitHub está em `2b076bf`; os 52 commits dos ciclos 1 a 4 e das rodadas 9 a 11 nunca passaram pelo CI. O gate verde é local. É de quem integra.
- **Estado do Verifier nos documentos.** `AGENTS.md:78` diz "Verifier rodadas 9 e 10" (a 10 é FAIL; a vigente é a 11, PASS) e `spec.md:262` marca o critério só com as rodadas 8 e 9. Incompleto, não contradiz código nem decisão: vale atualizar junto da próxima correção.
- **`AGENTS.md:231`** "repete só no 1213, até 3 vezes": são 3 tentativas (2 repetições). Imprecisão de texto.
- **`UserService.php:25`** "Escritas em transactionRetryingDeadlock": o `invite` segue em `DB::transaction`, coerente (a linha nasce no callback; registrado no ciclo 4).
- **`ContactService::reactivate`** trava a mesma linha duas vezes (`lockedNotAnonymized` e `setActiveLocked`, `:113`): uma consulta a mais, sem efeito. O `bool` de retorno do `setActiveLocked` não é lido por nenhum chamador.
- **`LimparExemploCommand.php:76`** usa `setActive` direto: CLI sem ator, já na `main`, não pode usar `setActiveLocked` (exige `User`). A regra nova do "Um jeito só" não cita a exceção.
- **1205 agora vira 500 na primeira tentativa** (≈ 50 s em produção): coerente com o CV-02 ("erro de banco vira 500") e com Client e Contact.
- **Equivalência do `RetriesDeadlock` com o laço do Laravel:** cada tentativa é um `DB::transaction` completo (`beginTransaction`, `rollBack`, descarte dos `afterCommit` pendentes); nenhum `afterCommit` no `app`. O 1062 não é 1213 e chega ao `writeOrRefuseDuplicate`. Transação de fora: o aninhado lança `DeadlockException` e o trait relança (`transactionLevel() > 0`), provado pelo mutante g2 na rodada 11.
- **Commit com 1213 no `COMMIT`** relançado sem repetir: não é caminho realista no InnoDB.
- **Segurança, §11.** As seis perguntas não trouxeram outro achado: grupo `admin.` com `auth`, `backoffice` e `password.current` (`routes/web.php:58-85`); toda ação autoriza; nenhuma rota, controller, Policy ou tela mudou; `throttle:login` em login, forgot, reset e first-access (`web.php:26,31,37,53`); nenhum segredo no diff; `AuditService::NEVER_AUDITED` intacto; senha só com `#[\SensitiveParameter]` no `send()`.
- **Requisitos.** Nenhum AC sem código nem sem teste que discrimine; a matriz do `validation.md` não tem ❌ aberto. Porta de entrada completa nos três cadastros (criar, listar, editar, desativar/reativar, excluir; contato também anonimizar).
- **Fora da tarefa:** nada. O diff do ciclo toca só os arquivos das correções.

## Está bem feito

- Os cinco achados do ciclo 4 foram tratados, um por commit, com o ID no título, e todos têm teste que discrimina e mutante morto na rodada 10 ou 11.
- O `RetriesDeadlock` lê o código do MySQL (`errorInfo[1]`), não a mensagem, trata o `DeadlockException` aninhado e não repete transação aberta por fora; os quatro testes simulam o erro como o PDO o entrega e contam as tentativas.
- O `setActiveLocked` tirou as seis cópias sem mudar ordem de trava, auditoria nem efeito; a recusa do anonimizado continua antes da decisão.
- O Fix 6 da rodada 10 foi fechado com teste e rodada própria do Verifier antes deste ciclo.

---

## Resumo executivo

**Achados deste ciclo:** 2 no total.
- 0 Crítico
- 0 Alto
- 1 Médio: CR-5-01
- 1 Baixo: CR-5-02

**Pelo vocabulário da revisão em camadas (`testes.md` §10), para a descrição do PR:**

| Classe | Achados | Observação |
|---|---|---|
| **Bloqueante** | 0 | Nenhum Crítico nem Alto |
| **Deveria corrigir** (corrige ou justifica no PR) | 1 | CR-5-01 (reenvio pela instância de chegada: senha provisória sem troca obrigatória e auditoria vazia; reproduzido) |
| **Detalhe** (a critério de quem fez) | 1 | CR-5-02 (`setActiveLocked` sem transação própria) |

**Achados anteriores (46):**
- 40 resolvidos e verificados no código (CR-3-03 e os cinco do ciclo 4 fecharam neste ciclo).
- 0 parciais.
- 4 aguardando decisão do Lucas: CR-1-11, CR-1-12, CR-1-23 e CR-2-09, mais as abilities do CR-2-08.
- 1 em PR próprio: CR-1-01, PR #7 aberto.
- **0 regressões.**

**Para o Lucas decidir (não é código do Cauã):**
- CR-1-11, CR-1-12, CR-1-23, CR-2-09 e as abilities do CR-2-08.
- O merge do PR #7 (CR-1-01), junto da correção do CR-5-01 (mesmo callback).
- O push dos 52 commits com CI verde no PR #5.

**Principais riscos:**
1. O AP-01 segue no `resendInvite`, agora sem deadlock: é a única escrita de usuário que ainda grava pela instância em memória.
2. As correções dos ciclos 1 a 4 ainda não passaram pelo CI do PR.

**Avaliação geral:**
- O ciclo 4 foi corrigido por inteiro, com prova independente (rodadas 10 e 11) e escada verde (358 PHP, 68 e2e). Nenhuma regressão, nenhum teste afrouxado, e a correção de concorrência deste ciclo não abriu defeito novo.
- O que sobra é um Médio pequeno (releitura com trava no reenvio, mais um teste) e um detalhe de desenho.

**Recomendação final: apta com ajustes.**
- Antes do merge: corrigir o CR-5-01 (toca senha), de preferência junto do merge do PR #7; depois push com CI verde no PR #5.
- CR-5-02 cabe junto ou num PR de limpeza antes do B2.
