# Code Review — `feat/b1-nucleo` · Ciclo 6

**Data:** 2026-10-09
**História:** b1-nucleo (PR [WCJ-Tecnologia/wcj-hub#5](https://github.com/WCJ-Tecnologia/wcj-hub/pull/5), autor Cauã Soares)
**Branch:** `feat/b1-nucleo` (HEAD local `09d506c`) · **Base:** `main` (branch do ambiente, `AGENTS.md` → Ambientes; `origin/main` = `2056834`)
**Spec:** `.specs/features/b1-nucleo/`
**Revisor:** Claude Code (skill `wcj-code-review`, sessão de revisão com contexto zerado). Três camadas em subagentes Opus sem contexto e só leitura (segurança, requisitos, qualidade/regressão) e a escada num subagente Sonnet. Esta sessão consolidou e reverificou no código cada ponto levantado (inclusive `vendor/laravel/framework/src/Illuminate/Database/Concerns/ManagesTransactions.php`, `transaction()` e `handleTransactionException()`).
**Ciclos anteriores:** [Ciclo 1](b1-nucleo-code-review-ciclo-1.md) · [Ciclo 2](b1-nucleo-code-review-ciclo-2.md) · [Ciclo 3](b1-nucleo-code-review-ciclo-3.md) · [Ciclo 4](b1-nucleo-code-review-ciclo-4.md) · [Ciclo 5](b1-nucleo-code-review-ciclo-5.md)
**Escopo deste ciclo:**
- As correções do ciclo 5 (`git diff 03ae0f3...HEAD`): CR-5-01 (`ba6ed33`, `resendInvite` emite a senha na linha relida com `lockForUpdate`), CR-5-02 (`7487b53`, `setActiveLocked` abre a própria `DB::transaction`), os testes `7f65222` (`SetActiveLockedTransacaoTest`, Fix 7 da rodada 12) e `fc7b73d` (busca de clientes sem CNPJ aleatório, Fix 8), e as rodadas 12 e 13 do `validation.md` (`56139b2`, `09d506c`). Código: 2 arquivos de produção (+26 / −25) e 3 de teste; mais `.specs/LESSONS.md` e `.specs/lessons.json` (L-013, L-014, do `lessons.py`).
- A branch inteira contra `main` pelas seis perguntas de `seguranca.md` §11. Desde o ciclo 5 nenhuma rota, controller, FormRequest, Policy, middleware ou tela mudou.
- Working tree limpo.

> **Atenção:** o HEAD local `09d506c` está 59 commits à frente de `origin/feat/b1-nucleo` (`2b076bf`). O PR #5 e o CI ainda mostram o estado anterior ao ciclo 1. O push é de quem integra, depois deste ciclo, e não entra como achado.

**Escada de verificação (worktree `wcj-hub-pr5`, 09/10, MySQL 8 local da 3306, HEAD `09d506c`):**
- `composer lint` ✅
- `php artisan test` ✅ 360 testes, 2 934 asserções, 0 falhas, 0 skipped (52 s). `SetActiveLockedTransacaoTest` rodou e passou (4 asserções, não skipped); `test_reenviar_convite_pela_instancia_velha_…` passou (9 asserções)
- `npm run typecheck` ✅ · `npm run lint` ✅ · `npm run build` ✅
- `npm run specs:check` ✅ 0 problemas; avisos T35 e T50 "Tests: none", como antes
- `npm run test:guard` ✅ 1 053
- Playwright ✅ 68/68 (67 testes + setup; 0 skipped, 0 flaky)
- `git status` vazio ao final; servidor da 8000 morto, porta livre

---

## Status dos achados anteriores

Reverificados **no código atual** (HEAD `09d506c`). Desde o HEAD de código do ciclo 5 (`f2a4d7e`) só mudaram `Deactivatable.php`, `UserService.php`, três arquivos de teste e documentos de `.specs/` (`git diff --name-only f2a4d7e HEAD`). Achados cuja evidência está em arquivo intocado mantêm a evidência do ciclo 5, conferida por amostragem; os que dependem dos arquivos alterados foram relidos e as linhas abaixo são as de hoje.

### Ciclo 1

| ID | Criticidade | Status | Evidência |
|---|---|---|---|
| CR-1-01 | Alto | ↪️ Tratado em PR próprio, fora desta branch (DA-05) | `UserService.php:76-92` segue sem `terminateOthers`, de propósito. PR [#7](https://github.com/WCJ-Tecnologia/wcj-hub/pull/7) OPEN, sem merge (`gh pr view 7`, 09/10). Ao integrar, a chamada entra no callback do `resendInvite` (`:82-89`); `SessionService::terminateOthers` filtra por `user_id`, então `$user` ou `$locked` dá no mesmo. Achado de segurança: quem marca como corrigido é o Lucas |
| CR-1-02 | Médio | ✅ Resolvido | `RefusesDuplicates`; `ClientService.php:74,93`; `UserService.php:54,106`; `emailTaken` `:220` |
| CR-1-03 | Médio | ✅ Resolvido | `ContactController.php` intocado, dentro do `attempt()` |
| CR-1-04 | Médio | ✅ Resolvido | `ClientService.php` (WCJ) e `ClientPolicy.php` intocados desde o ciclo 5 |
| CR-1-05 | Médio | ✅ Resolvido | `UserService.php:209`, `ClientService.php:172` (`inspectFor()->authorize()`) |
| CR-1-06 | Médio | ✅ Resolvido | `spec.md:5` aprovada por Lucas Scholz, 2026-10-09 |
| CR-1-07 | Médio | ✅ Resolvido | `audit-log-filters.tsx` intocado; e2e verde |
| CR-1-08 | Médio | ✅ Resolvido | `UserPresenter.php` intocado |
| CR-1-09 | Médio | ✅ Resolvido | `ListBody` e Presenters intocados |
| CR-1-10 | Médio | ✅ Resolvido | `search-input.tsx` intocado; e2e verde |
| CR-1-11 | Baixo | ❌ Não resolvido, aguarda decisão do Lucas (DA-07) | `spec.md` igual |
| CR-1-12 | Baixo | ❌ Não resolvido, aguarda decisão do Lucas (DA-07) | `user-form-dialog.tsx:87` `min="0.25"` |
| CR-1-13 | Baixo | ✅ Resolvido | `UserService.php:84` (`before` real, agora da linha travada) |
| CR-1-14 | Baixo | ✅ Resolvido | `contact-form-dialog.tsx` intocado |
| CR-1-15 | Baixo | ✅ Resolvido | Os três `deactivate` pelo `setActiveLocked` (`Deactivatable.php:136-153`; `UserService.php:143`, `ClientService.php:109`, `ContactService.php:104`); testes espelho `UserServiceTest.php:417,466` |
| CR-1-16 | Baixo | ✅ Resolvido | `Client.php` intocado |
| CR-1-17 | Baixo | ✅ Resolvido | `use-row-action.ts` intocado |
| CR-1-18 | Baixo | ✅ Resolvido | `Like::contains` em `UserService.php:193-194`, `ClientService.php:156-158`; teste `ClientServiceTest.php:500-511` agora determinístico (lacuna de cobertura no CNPJ: CR-6-03) |
| CR-1-19 | Baixo | ✅ Resolvido | `config/app.php`, `docs/DEPLOY.md` intocados |
| CR-1-20 | Baixo | ✅ Resolvido | `e2e/mobile.spec.ts` verde |
| CR-1-21 | Baixo | ✅ Resolvido | e2e intocados |
| CR-1-22 | Baixo | ✅ Resolvido | Tabela de componentes do `AGENTS.md` intacta |
| CR-1-23 | Baixo | ❌ Não resolvido, aguarda decisão do Lucas (DA-07) | Sem migração nova |
| CR-1-24 | Baixo | ✅ Resolvido (pela documentação) | `docs/DEPLOY.md` intocado |

### Ciclo 2

| ID | Criticidade | Status | Evidência |
|---|---|---|---|
| CR-2-01 | Médio | ✅ Resolvido | `UserService.php:140` (admins, via `:250`) antes de `:143` (alvo, no `setActiveLocked`); o savepoint novo não solta nem reordena trava; `UserServiceTest.php:534` prova a ordem |
| CR-2-02 | Médio | ✅ Resolvido | `validation.md` rodada 9 |
| CR-2-03 | Médio | ✅ Resolvido | Nenhuma tela mudou |
| CR-2-04 | Baixo | ✅ Resolvido | `ClientControllerTest.php` intocado |
| CR-2-05 | Baixo | ✅ Resolvido | `necessidade.md` intocado |
| CR-2-06 | Baixo | ✅ Resolvido | `setActiveLocked(true, …)`: `UserService.php:155`, `ClientService.php:117`, `ContactService.php:113` (recusa do anonimizado antes); `UserServiceTest.php:435,451` |
| CR-2-07 | Baixo | ✅ Resolvido | `use-row-action.ts` intocado |
| CR-2-08 | Baixo | ✅ Resolvido (`isReset`) · ❌ aguarda decisão do Lucas (abilities, DA-07) | `isReset`: zero em `app`, `resources`, `tests`. `delete_records`/`reset_passwords` sem leitor no front (`HandleInertiaRequests.php:66,68`; `types/index.d.ts:12,14`) |
| CR-2-09 | Baixo | ❌ Não resolvido, aguarda decisão do Lucas (DA-07) | `UserPresenter.php:33` sem condição de ativo |
| CR-2-10 | Baixo | ✅ Resolvido | `audit-log-filters.tsx` intocado |
| CR-2-11 | Baixo | ✅ Resolvido | `RefusesDuplicates`; `UserService::emailTaken` `:220` |
| CR-2-12 | Baixo | ✅ Resolvido | Presenters e `lib/situation.ts` intocados |

### Ciclo 3

| ID | Criticidade | Status | Evidência |
|---|---|---|---|
| CR-3-01 | Médio | ✅ Resolvido | `setActiveLocked` decide, grava e passa o `$locked` ao `$then` (`Deactivatable.php:139-148`) |
| CR-3-02 | Baixo | ✅ Resolvido | `snapshot($deleted)` dentro do `deleteIfUnused`: `UserService.php:169`, `ClientService.php:131-133`, `ContactService.php:123-125` |
| CR-3-03 | Baixo | ✅ Resolvido | As cinco escritas do `UserService` em `transactionRetryingDeadlock` (`:82,106,137,155,163`); callbacks repetíveis (o `resendInvite` agora por releitura, `:83`) |
| CR-3-04 | Baixo | ✅ Resolvido | `UltimoAdminConcorrenciaTest.php` intocado |
| CR-3-05 | Baixo | ✅ Resolvido | Documentos intocados |

### Ciclo 4

| ID | Criticidade | Status | Evidência |
|---|---|---|---|
| CR-4-01 | Médio | ✅ Resolvido | A repetição recomeça do banco: `UserService.php:83` relê com trava a cada tentativa; `UserDeadlockRepeteTest.php:89` confere a coluna com `fresh()` e segue matando o mutante "sem releitura" (rodada 12, mutante a) |
| CR-4-02 | Médio | ✅ Resolvido | Rodadas 10 e 11 do `validation.md` |
| CR-4-03 | Baixo | ✅ Resolvido | `UserServiceTest.php` (dev promovida, instância velha) intocado |
| CR-4-04 | Baixo | ✅ Resolvido | `RetriesDeadlock.php:39-40` repete só no 1213; `UserDeadlockRepeteTest.php:123` |
| CR-4-05 | Baixo | ✅ Resolvido | `setActiveLocked` nas seis chamadas (`UserService.php:143,155`; `ClientService.php:109,117`; `ContactService.php:104,113`) |

### Ciclo 5

| ID | Criticidade | Status | Evidência |
|---|---|---|---|
| CR-5-01 | Médio | ✅ Corrigido no código e verificado (`ba6ed33`; sem push, merge nem implantação: como toca senha, o fechamento formal é do Lucas) | `UserService.php:83` relê com `lockForUpdate`; `before` da linha travada `:84`; `issue($locked)` `:85`; auditoria `:86`; e-mail para `$locked` `:91`. Teste `UserServiceTest.php:178-190`: instância velha, flag `true` no banco (`:186`), `changes` `[false, true]` (`:187`), senha no endereço da linha travada (`:189`). Mutantes a, b, c mortos (rodada 12). Lacuna: a trava em si não tem teste que a discrimine (CR-6-02) |
| CR-5-02 | Baixo | ✅ Resolvido | `Deactivatable.php:138` `DB::transaction` (savepoint dentro da de quem chama); `SetActiveLockedTransacaoTest.php:35-52` chama fora de transação com `$then` que lança e confere pelo banco; mutante d morto na rodada 13. Deadlock aninhado: o Laravel baixa o nível e lança `DeadlockException` com o 1213 dentro (`ManagesTransactions.php:89-100`); a transação de fora faz o `rollBack()` e o `RetriesDeadlock.php:39-40` repete com `transactionLevel() == 0`, o mesmo caminho já provado no `deleteIfUnused` (`UserDeadlockRepeteTest.php:108`) |

**Evolução:**

| Rodada | Achados novos |
|---|---|
| Revisão 07/10 | 9 |
| Ciclo 1 | 24 |
| Ciclo 2 | 12 |
| Ciclo 3 | 5 |
| Ciclo 4 | 5 |
| Ciclo 5 | 2 |
| Ciclo 6 | 3 (todos Baixo) |

**Status neste ciclo, dos 48 achados anteriores:**
- 42 ✅ resolvidos: 20 do ciclo 1, 10 do ciclo 2 (o CR-2-08 conta pela parte do `isReset`), 5 do ciclo 3, 5 do ciclo 4 e os 2 do ciclo 5.
- 0 ⚠️ parciais.
- 4 ❌ aguardando decisão do Lucas: CR-1-11, CR-1-12, CR-1-23 e CR-2-09, mais as abilities do CR-2-08.
- 1 em PR próprio: CR-1-01 (PR #7).
- **0 regressões**, de ID e de comportamento. Segundo ciclo seguido em que a correção de concorrência não abre defeito novo de concorrência: a ordem das travas do `resendInvite` é a de antes (X no alvo, agora no `SELECT … FOR UPDATE` em vez do `UPDATE`, depois S no ator pela FK da auditoria) e o savepoint do `setActiveLocked` não reordena nem solta trava.

---

## Achados do Ciclo 6

## [BAIXO] CR-6-01 — `AGENTS.md` ensina o padrão que o CR-5-01 abandonou: cita o `resendInvite` como exemplo de "restaure a instância" e o `setActiveLocked` como dependente da transação de quem chama

**Arquivo:** `AGENTS.md:230-231`
**Trecho atual:**
```markdown
- Ativar e desativar: `setActiveLocked()` do `Deactivatable` dentro da transação de quem chama (relê com `lockForUpdate`, …).
- Escrita que pode cair em deadlock (…): o trait `App\Platform\Services\Concerns\RetriesDeadlock` (…). Todo callback repetido precisa ser repetível: restaure a instância (`getAttributes()`/`getRawOriginal()`) no início de cada tentativa, como `UserService::update` e `::resendInvite`.
```

**Ajuste necessário:**
- `:231`: tirar o `::resendInvite` do exemplo e dizer a regra como ela ficou: "releia a linha com `lockForUpdate` dentro do callback; restaurar a instância (`getAttributes()`/`getRawOriginal()`) só serve quando o que se grava não depende do 'sujo' da instância (ex.: `UserService::update`)".
- `:230`: o `setActiveLocked` abre a própria transação (savepoint dentro da de quem chama); trocar "dentro da transação de quem chama" por isso.

**Motivo:**
- Desde o `ba6ed33` o `resendInvite` não restaura a instância: relê com trava (`UserService.php:82-89`). Desde o `7487b53` o `setActiveLocked` abre a transação dele (`Deactivatable.php:138`). O documento contradiz o código nos dois pontos.
- A linha `:231` recomenda justamente a saída que o CR-5-01 mostrou insuficiente (o `forceFill` de um valor que a memória já tem não fica sujo e não é gravado). O CV-03 do `PROJECT-PATTERNS.md` já registrou "releia com trava"; o docblock do `RetriesDeadlock.php:17-18` já diz "(ou relida)". O `AGENTS.md` ficou para trás.
- É a terceira ocorrência do AP-04 (decisão aplicada no código e não no documento que guia o próximo bloco).

**Sugestão:**
Editar as duas linhas no mesmo commit que fechar os outros detalhes deste ciclo; aproveitar e atualizar `AGENTS.md:78` (estado do B1: ciclos e rodadas vigentes), já registrado no ciclo 5.

**Impacto:**
Nenhum defeito hoje. O `AGENTS.md` é a instrução que as IAs seguem no B2 a B7: quem copiar o exemplo repete o CR-4-01/CR-5-01 numa escrita nova.

---

## [BAIXO] CR-6-02 — A trava do `resendInvite` não tem teste que a discrimine: tirar só o `lockForUpdate()` mantém a suíte verde

**Arquivo:** `app/Platform/Services/UserService.php:83`; `tests/Feature/Nucleo/UserServiceTest.php:178-190`
**Trecho atual:**
```php
$locked = User::query()->lockForUpdate()->findOrFail($user->getKey());
```
```php
$velha = User::query()->findOrFail($joao->id);
$joao->forceFill(['must_change_password' => false, 'email' => 'joao.novo@example.com'])->save();

$this->assertTrue($this->service->resendInvite($velha, $this->admin));
```

**Ajuste necessário:**
Um teste que reprove o mutante `User::query()->findOrFail(...)` (releitura sem trava). Duas formas no molde do projeto:
- contar a consulta `select … from users … for update` do alvo no `resendInvite`, como `UserServiceTest.php:534-552` faz para a ordem de trava do `deactivate`; ou
- `Concorrencia::intercalarDepoisDe` zerando a flag logo depois do `SELECT` do alvo e conferindo `must_change_password = true` com `fresh()`.

**Motivo:**
- O teste do CR-5-01 zera a flag **antes** da chamada (`:181`): uma releitura sem trava já lê `false` e passa. O `UserDeadlockRepeteTest.php:89` relê a cada tentativa, com ou sem trava. Nenhum teste do `resendInvite` usa `Concorrencia` nem confere `for update` (`grep "for update" tests`: só `UserServiceTest.php:539`, `ContactAnonimizadoConcorrenciaTest.php:116`, `UltimoAdminConcorrenciaTest.php:76`).
- Os mutantes da rodada 12 cobriram a releitura (a: `$locked = $user`), o destino do e-mail (b) e o `before` (c); nenhum tirou só a trava.
- Sem a trava, a leitura é por snapshot: se o convidado conclui o primeiro acesso entre o `SELECT` e o `UPDATE`, o `forceFill(true)` não fica sujo e a flag termina `false`. É o defeito do CR-5-01 com janela menor; o comentário `:78` e o commit prometem "pela linha travada", e só a releitura tem prova.
- Análise de código; o mutante não foi executado nesta sessão (revisor só lê).

**Sugestão:**
A contagem de `for update`, que é determinística e já tem molde (`UserServiceTest.php:534`). Mesma família das lições L-012 e L-013 ("trava/transação que a suíte não enxerga").

**Impacto:**
Nenhum defeito hoje: o código está certo. Sem o teste, uma refatoração que tire o `lockForUpdate()` (ou troque por `fresh()`) reabre o CR-5-01 em silêncio.

---

## [BAIXO] CR-6-03 — O teste do CR-1-18 deixou de cobrir o escape do `LIKE` na coluna `document`

**Arquivo:** `tests/Feature/Nucleo/ClientServiceTest.php:504-505`; `app/Platform/Services/ClientService.php:157-158`
**Trecho atual:**
```php
foreach (['a_b', 'aXb', 'Promoção 100%', 'Promoção 1000'] as $name) {
    Client::factory()->create(['name' => $name, 'country' => 'US', 'document' => null]);
}
```

**Ajuste necessário:**
Um caso fixo no documento, por exemplo um cliente `US` com `document = 'A1B'` que **não** pode aparecer na busca por `a_b` (com `"%{$search}%"` à mão o `_` casaria o `1`). Evitar `100` no documento: o termo `100%` sem máscara vira `100` e casaria de forma legítima.

**Motivo:**
- O Fix 8 (`fc7b73d`) tirou o CNPJ aleatório para acabar com a intermitência: correto. Mas com `document = null` nos quatro clientes, os dois `orWhere('document', 'like', Like::contains(...))` (`ClientService.php:157-158`) ficam sem caso no teste; o mutante `"%{$search}%"` nessas linhas sobrevive sempre. Antes, só morria por acaso (que era a intermitência).
- `ClientControllerTest.php:92-95` só busca termos sem curinga.
- Não é enfraquecimento real (a cobertura anterior era aleatória), mas o CV-06 ficou provado só na coluna `name`.

**Sugestão:**
O caso fixo acima, no mesmo teste.

**Impacto:**
Nenhum defeito hoje. Uma troca do `Like::contains` por concatenação na busca por CNPJ passaria verde.

---

## Registrado, sem achado

- **Toast do reenvio com a instância de chegada** (`UserController.php:96,99`): `"Convite reenviado para {$user->email}"` usa o e-mail do route binding; desde o CR-5-01 a senha vai para `$locked->email`. Só diverge se outro admin trocar o e-mail do alvo no mesmo instante; nada vaza (o admin já vê todos os e-mails) e o e-mail vai para o endereço certo (`UserServiceTest.php:189`). Cosmético. Se quiser alinhar, o `resendInvite` devolve o e-mail junto do `bool`.
- **Deadlock dentro do `setActiveLocked` aninhado** (desativar e reativar usuário): sem teste próprio; o mecanismo (`DeadlockException` com o 1213 dentro, `rollBack()` de fora, repetição com `transactionLevel() == 0`) é o mesmo do `deleteIfUnused`, provado em `UserDeadlockRepeteTest.php:108`. Não pedir.
- **`DB::transaction` de fora redundante** em `ClientService.php:109,117` e `ContactService.php:104`: depois do CR-5-02, um savepoint a mais, sem efeito. No `UserService` o de fora segue necessário (é ele que repete). No `ContactService.php:111` o de fora segura também o `lockedNotAnonymized`.
- **Client e Contact:** o deadlock aninhado agora sobe como `DeadlockException` em vez de `QueryException`; nenhum código distingue as duas (o `attempt()` só pega `DomainException`; `bootstrap/app.php` sem renderização especial): segue 500, como o CV-02. O 1205 aninhado também vira `DeadlockException` (o detector do Laravel olha a mensagem); o `RetriesDeadlock` lê o 1205 e relança, coerente com o CV-11.
- **Ganho colateral do CR-5-01:** alvo excluído entre o route binding e o reenvio agora dá `ModelNotFoundException` (404); antes a auditoria era gravada sobre linha inexistente e o e-mail saía.
- **Comentário `UserService.php:78`** "como toda escrita de usuário (CV-03)": o `update` ainda decide pela memória restaurada (registrado no ciclo 4). Imprecisão de comentário.
- **`validation.md`:** a linha `Result` do topo cobre só a rodada 13 (`56139b2..fc7b73d`, só testes); com a rodada 12 (`03ae0f3..7487b53`) as duas cobrem o ciclo inteiro. DA-08 atendido. Cinco linhas de evidência conferidas contra o código atual: batem. Na rodada 12, "`UserService.php:137,155`" cita a abertura da transação, não a chamada (`:143`): sem efeito.
- **`.specs/LESSONS.md` / `lessons.json`** (L-013, L-014): coerentes com o código e os testes.
- **`AGENTS.md:78` e `spec.md:262`** seguem sem citar o ciclo 5 e as rodadas 12 e 13: incompleto, já registrado no ciclo 5 (vai junto do CR-6-01).
- **Segurança, §11.** As seis perguntas não trouxeram achado: grupo `admin.` com `auth`, `backoffice` e `password.current` (`routes/web.php:58-85`); `resetPassword`, `deactivate`, `reactivate` e `destroy` autorizam (`UserController.php:61,68,76,84`); o ciclo não trouxe entrada nova; nenhuma regra só na tela; nenhum segredo no diff (só `joao@example.com`/`joao.novo@example.com` e o nome `DB_TESTE`); `throttle:login` em login, forgot, reset e first-access (`web.php:27,32,37,53`). A senha provisória não vai para a auditoria (`UserService.php:86`, `AuditService::NEVER_AUDITED`), o `send()` tem `#[\SensitiveParameter]` e o `TemporaryPasswordMail` não é `ShouldQueue` (a senha em claro não é serializada em `jobs`).
- **Requisitos.** Nenhum AC sem código nem sem teste que discrimine (as lacunas de mutante são o CR-6-02 e o CR-6-03); porta de entrada completa nos quatro cadastros (usuários, clientes, contatos, auditoria).
- **Fora da tarefa:** nada.

## Está bem feito

- Os dois achados do ciclo 5 foram tratados um por commit, com o ID no título e teste que discrimina; a correção do CR-5-01 seguiu a sugestão (releitura com trava) e tirou as duas linhas de restauração, deixando o callback repetível por construção.
- O e-mail agora sai para o endereço da linha travada, e o teste prova isso (fecha a "variante menor" do CR-5-01).
- O Fix 7 da rodada 12 (mutante d sobrevivente) foi fechado com teste em `DatabaseTruncation`, sem `RefreshDatabase`, que é a única forma de provar a transação do helper; e o Fix 8 tirou uma intermitência real da suíte.
- Segundo ciclo seguido sem regressão e sem defeito novo de concorrência vindo de correção.

---

## Resumo executivo

**Achados deste ciclo:** 3 no total.
- 0 Crítico
- 0 Alto
- 0 Médio
- 3 Baixo: CR-6-01, CR-6-02, CR-6-03

**Pelo vocabulário da revisão em camadas (`testes.md` §10), para a descrição do PR:**

| Classe | Achados | Observação |
|---|---|---|
| **Bloqueante** | 0 | Nenhum Crítico nem Alto na branch (o CR-1-01, Alto, está no PR #7 por decisão do Lucas, DA-05) |
| **Deveria corrigir** (corrige ou justifica no PR) | 0 | — |
| **Detalhe** (a critério de quem fez) | 3 | CR-6-01 (`AGENTS.md:230-231` contradiz o código e ensina o padrão abandonado), CR-6-02 (trava do `resendInvite` sem teste que a discrimine), CR-6-03 (escape do `LIKE` no CNPJ sem caso fixo) |

**Achados anteriores (48):**
- 42 resolvidos e verificados no código (os dois do ciclo 5 fecharam; o CR-5-01, por tocar senha, tem fechamento formal do Lucas depois do merge e da implantação).
- 0 parciais.
- 4 aguardando decisão do Lucas: CR-1-11, CR-1-12, CR-1-23 e CR-2-09, mais as abilities do CR-2-08.
- 1 em PR próprio: CR-1-01, PR #7 aberto.
- **0 regressões.**

**Para o Lucas decidir (não é código do Cauã):**
- CR-1-11, CR-1-12, CR-1-23, CR-2-09 e as abilities do CR-2-08.
- O merge do PR #7 (CR-1-01): o `terminateOthers` entra no callback do `resendInvite` (`UserService.php:82-89`).
- O push dos 59 commits com CI verde no PR #5.

**Principais riscos:**
1. As correções dos ciclos 1 a 5 ainda não passaram pelo CI do PR (gate verde só local).
2. O `AGENTS.md` (CR-6-01) é o que os blocos B2 a B7 vão copiar; hoje ensina o padrão que gerou o CR-4-01 e o CR-5-01.

**Avaliação geral:**
- O ciclo 5 foi corrigido por inteiro, com prova independente (rodadas 12 e 13, mutantes a a d mortos) e escada verde (360 PHP, 68 e2e, 1 053 guard). Nenhuma regressão, nenhum teste afrouxado, nenhum defeito de comportamento novo.
- O que sobra são três detalhes: um documento e duas lacunas de teste que só aparecem por mutação.

**Recomendação final: apta para merge.**
- Condições de quem integra (não são achados): push com CI verde no PR #5 e o PR #7 integrado no mesmo movimento (DA-05).
- Os três detalhes cabem num commit antes do merge ou num PR de limpeza antes do B2; o CR-6-01 é o mais barato e o que mais protege os próximos blocos.
