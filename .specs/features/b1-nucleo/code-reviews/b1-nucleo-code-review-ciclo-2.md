# Code Review — `feat/b1-nucleo` · Ciclo 2

**Data:** 2026-10-09
**História:** b1-nucleo (PR [WCJ-Tecnologia/wcj-hub#5](https://github.com/WCJ-Tecnologia/wcj-hub/pull/5), autor Cauã Soares)
**Branch:** `feat/b1-nucleo` (HEAD local `b7152fb`) · **Base:** `main` (branch do ambiente, `AGENTS.md` → Ambientes)
**Spec:** `.specs/features/b1-nucleo/`
**Revisor:** Claude Code (skill `wcj-code-review`, sessão de revisão com contexto zerado; três camadas em subagentes Opus sem contexto: segurança, requisitos, qualidade/regressão; consolidação e reverificação no código por esta sessão)
**Ciclos anteriores:** [Ciclo 1](b1-nucleo-code-review-ciclo-1.md) (que reverificou a revisão de 07/10, A1–A9)
**Escopo deste ciclo:** os 22 commits de correção desde o ciclo 1 (`ddd4888..b7152fb`: 55 arquivos, +1 250 / −445), com os arquivos tocados lidos inteiros, e a branch inteira contra `main` (141 arquivos, +15 376 / −637) pelas seis perguntas de `seguranca.md` §11. Working tree limpo.

> **Atenção:** o HEAD local `b7152fb` está 22 commits à frente de `origin/feat/b1-nucleo` (`2b076bf`). As correções do ciclo 1 **não estão no PR do GitHub** nem passaram pelo CI. Este ciclo revisou o HEAD local.

**Escada de verificação (worktree `wcj-hub-pr5`, 09/10, MySQL 8 da 3306, HEAD `b7152fb`):** `composer lint` ✅ · `php artisan test` ✅ 336 testes, 2 819 asserções, 0 falhas · `npm run typecheck` ✅ · `npm run lint` ✅ · `npm run build` ✅ · `npm run specs:check` ✅ (0 problemas; avisos T35 e T50 "Tests: none") · `npm run test:guard` ✅ 1 053 · Playwright ✅ 66/66 (0 skipped, 0 flaky).

---

## Status dos achados anteriores

Reverificados **no código atual** (HEAD `b7152fb`), não pelo relatório anterior nem pelas mensagens de commit.

| ID | Ciclo | Criticidade | Título | Status | Evidência |
|---|---|---|---|---|---|
| CR-1-01 | 1 | Alto | Reenviar convite não derruba as sessões abertas do alvo | ↪️ Tratado em PR próprio, fora desta branch (DA-05) | `UserService.php:72-83` segue sem `terminateOthers`, de propósito; PR [#7](https://github.com/WCJ-Tecnologia/wcj-hub/pull/7) aberto, sem merge. Achado de segurança: quem marca como corrigido é o Lucas, depois do merge e da implantação |
| CR-1-02 | 1 | Médio | CNPJ ou e-mail repetido em gravação simultânea responde 500 | ✅ Resolvido | `ClientService.php:79-81,101-103` + `duplicateRefusal()` l. 245-253 (relança o original se o unique não for o do documento); `UserService.php:117-120`; `ClientRepetidoConcorrenciaTest.php:37`; `UserServiceTest` "editar para o e-mail de outro" |
| CR-1-03 | 1 | Médio | `ContactController::update` deixa `DomainException` virar 500 | ✅ Resolvido | `ContactController.php:57-60` dentro do `attempt()`, que agora aceita closure de sucesso (`Controller.php:39-48`); `ContactControllerTest` "recusa de regra ao editar contato vira toast de erro" |
| CR-1-04 | 1 | Médio | "A WCJ não pode ser excluída" só na Policy | ✅ Resolvido | `ClientService.php:141-143`; `ClientServiceTest::test_wcj_nao_se_exclui_pelo_service`; spec Clientes AC 10 (l. 116), F1 1.2 §04, design D14. Resta teste HTTP e documentação: CR-2-04, CR-2-05 |
| CR-1-05 | 1 | Médio | NUC-13 sem porta de entrada; AC 3 sem prova | ✅ Resolvido | `choiceList(User $viewer)` com `inspectFor()->authorize()` (`UserService.php:205`, `ClientService.php:188`, `AuditService.php:52-61`); testes de dev recusado com negação auditada e de gestor sem negação; spec P2, design D13 |
| CR-1-06 | 1 | Médio | Spec reescrita sem nova aprovação | ✅ Resolvido | `spec.md:5` "Approved by: Lucas Scholz, 2026-10-09"; `spec.md:7` registra a revisão de 09/10; design D12 (aviso "ao sair do campo") |
| CR-1-07 | 1 | Médio | Filtro de data da auditoria dispara uma visita por tecla | ✅ Resolvido | `audit-log-filters.tsx:71-111` (estado local, debounce de 300 ms, blur e Enter, sincroniza com as props no render); `e2e/filtro-de-data.spec.ts` (digitação segmento a segmento, uma visita só) |
| CR-1-08 | 1 | Médio | Botões da linha de Usuários por ability global | ✅ Resolvido | `UserController.php:105-123` devolve `can` pela `UserPolicy`; `users/index.tsx:151-174` lê `row.can.*`; `UserControllerTest::test_botoes_da_linha_decididos_pela_policy` |
| CR-1-09 | 1 | Médio | Esqueleto de lista em 4 páginas, 3 padrões de linha | ✅ Resolvido | `components/list-body.tsx` nas 4 páginas (`users/index.tsx:220`, `clients/index.tsx:192`, `contacts/index.tsx:72`, `audit-logs/index.tsx:57`); `lib/situation.ts` nas 3 tabelas; `ContactPresenter.php` (ContactService caiu para 279 linhas) |
| CR-1-10 | 1 | Médio | Busca com debounce cancela escrita em voo | ✅ Resolvido | `search-input.tsx:76-98` (`before`/`start`/`finish`: a busca pendente espera a escrita terminar; ouvintes removidos no unmount); `e2e/busca-e-escrita.spec.ts` (POST segurado 600 ms) |
| CR-1-11 | 1 | Baixo | Senha provisória não expira, a spec diz que expira | ❌ Não resolvido, aguarda decisão do Lucas (DA-07) | `spec.md:226` ainda diz "senha provisória expira pela regra do template" |
| CR-1-12 | 1 | Baixo | Tela mais rígida que o servidor na carga diária | ❌ Não resolvido, aguarda decisão do Lucas (DA-07) | `user-form-dialog.tsx:87` ainda `min="0.25"`; servidor `gt:0` |
| CR-1-13 | 1 | Baixo | Reenviar convite manda "nova senha" e audita o antes vazio | ✅ Resolvido | `UserService.php:75-82` (`$before` com o valor real, `isReset: false`). Efeito colateral: CR-2-08 |
| CR-1-14 | 1 | Baixo | Erro de cliente desativado numa chave que o formulário não mostra | ✅ Resolvido | `contact-form-dialog.tsx:38-55` mostra `errors.client`; `e2e/textos-nucleo.spec.ts:177` |
| CR-1-15 | 1 | Baixo | Checar-e-agir fora da transação em `deactivate` e no contato | ⚠️ Parcial | Os três `deactivate` e o `create` de contato decidem pela linha travada (`UserService.php:129`, `ClientService.php:116`, `ContactService.php:66,104`). Mas a correção do `UserService::deactivate` inverteu a ordem das travas e abriu um deadlock (CR-2-01) |
| CR-1-16 | 1 | Baixo | `Client` depende de classe de seeder | ✅ Resolvido | `Client::WCJ_CNPJ` + `Paises::BRASIL` (`Client.php:19,40`); seeder lê do Model |
| CR-1-17 | 1 | Baixo | Reativar não sinaliza nem trava clique duplo | ✅ Resolvido | `RowActions busy` (`row-actions.tsx:21-50`) + `!busy &&` em `users/index.tsx:83`, `clients/index.tsx:84`, `contact-actions.tsx:80` |
| CR-1-18 | 1 | Baixo | `LIKE` sem escapar `%` e `_` | ✅ Resolvido | `app/Support/Like.php:13` nos 4 pontos de busca (`UserService.php:189-190`, `ClientService.php:172-174`, `ContactService.php:186-188`, `AuditLogQuery.php:167-176`); `ClientServiceTest::test_busca_trata_porcento_e_sublinhado_como_texto` |
| CR-1-19 | 1 | Baixo | Mensagens dependem de `APP_LOCALE` fora do runbook | ✅ Resolvido | `config/app.php:116` padrão `pt_BR`; `docs/DEPLOY.md` linha `APP_LOCALE` |
| CR-1-20 | 1 | Baixo | Sem prova em 375 px | ✅ Resolvido | `e2e/mobile.spec.ts` (5 telas, ficha e detalhe da auditoria, sem rolagem horizontal). O print no PR segue como rito, não código |
| CR-1-21 | 1 | Baixo | e2e crítico limpa só no fim do corpo | ✅ Resolvido | `try/finally` em `e2e/nucleo.spec.ts:52` e `e2e/smoke.spec.ts:106`; helpers em `e2e/helpers/dados.ts` |
| CR-1-22 | 1 | Baixo | Componentes e hook fora da tabela de componentes | ✅ Resolvido | Tabela "Componentes e hooks do projeto" no `AGENTS.md`; os 9 itens existem nos caminhos citados |
| CR-1-23 | 1 | Baixo | Auditoria sem índice para `created_at` | ❌ Não resolvido, aguarda decisão do Lucas (DA-07) | `2026_10_06_000170_create_audit_logs_table.php:41-43` sem índice novo (migração exige aprovação) |
| CR-1-24 | 1 | Baixo | `MAIL_MAILER=log` grava a senha provisória em claro | ✅ Resolvido | `docs/DEPLOY.md`: "nunca `log` em produção" com o motivo (a opção pedida era recusar no código **ou** documentar) |

**Evolução:** Revisão 07/10: 9 achados · Ciclo 1: 24 · Ciclo 2: 12 novos
**Neste ciclo:** 19 resolvidos · 1 parcial (CR-1-15) · 3 não resolvidos, todos aguardando decisão do Lucas (CR-1-11, CR-1-12, CR-1-23) · 1 tratado em PR próprio (CR-1-01) · 0 regressões de ID anterior

---

## Achados do Ciclo 2

## [MÉDIO] CR-2-01 — `UserService::deactivate` trava o alvo antes dos administradores: deadlock entre dois admins vira 500

**Arquivo:** `app/Platform/Services/UserService.php:129-134` (e `:236`)
**Trecho atual:**
```php
DB::transaction(function () use ($user, $actor) {
    $locked = User::query()->lockForUpdate()->findOrFail($user->getKey());   // 1ª trava: o alvo
    if (! $locked->isActive()) {
        return;
    }

    $this->ensureNotLastActiveAdmin($locked, 'desativar');                   // 2ª trava: todos os admins ativos (l. 236)
```

**Ajuste necessário:**
Travar os administradores ativos **antes** da linha do alvo, na mesma ordem do `update` e do `delete` (que chamam `ensureNotLastActiveAdmin` primeiro). Por exemplo: quando `$user->isAdmin()`, chamar `ensureNotLastActiveAdmin` antes do `findOrFail(...)->lockForUpdate()`; ou passar `attempts: 3` ao `DB::transaction`. Teste: dois admins-alvo diferentes, com a intercalação logo depois do `select … for update` do alvo (não depois da consulta dos admins, como hoje).

**Motivo:**
Introduzido pela correção do CR-1-15. Em `ddd4888` a transação só travava os admins ativos, sempre na mesma ordem. Agora a Ana desativando a Bia trava Bia e espera Ana; a Bia desativando a Ana trava Ana e espera Bia. O MySQL derruba uma com o erro 1213. O `DB::transaction` roda uma tentativa só e o `attempt()` (`Controller.php:39-48`) só pega `DomainException`: a derrubada responde 500, em vez do toast "Este é o último administrador ativo…" que o A3 da revisão de 07/10 entregava. A integridade se mantém (rollback; sempre sobra admin). O `UltimoAdminConcorrenciaTest.php:36` não vê porque intercala depois que a principal já segurou as duas travas, e o `Concorrencia` conta 1213 como "bloqueada".

**Sugestão:**
Reordenar as duas chamadas; é a correção de menor risco e mantém a decisão pela linha travada do CR-1-15.

**Impacto:**
Raro (dois admins, ao mesmo tempo, um desativando o outro), mas é 500 numa rota de formulário, no mesmo caso que o A3 tinha resolvido com toast. Anti-padrão AP-01.

---

## [MÉDIO] CR-2-02 — O Verifier e a mutação do trilho completo não cobrem as correções do ciclo 1; o `validation.md` se contradiz

**Arquivo:** `.specs/features/b1-nucleo/validation.md:394,404,739,763,796,831`; `spec.md:262`; `AGENTS.md:78`
**Trecho atual:**
```markdown
| P2 AC 3 | dev: **403** | `ClientPolicyTest.php:55,69-71` e `UserPolicyTest.php:48` (ability negada); não há rota (design D6) | ⚠️ desvio declarado, como na rodada 4 |
```

**Ajuste necessário:**
Uma rodada do Verifier em agente separado (não quem implementou) sobre `ddd4888..HEAD`, com mutação nas regras que o ciclo mexeu: a Policy dentro do `choiceList` (`UserService.php:205`, `ClientService.php:188`), a WCJ recusada no `ClientService::delete` (`:141-143`), as travas por linha do CR-1-15 e o `before` do `password_reset` (`UserService.php:75-77`). Atualizar as tabelas de evidência do `validation.md`.

**Motivo:**
O critério de sucesso da spec (`spec.md:262`) e o trilho completo do B1 (`AGENTS.md`, "Trilhos") pedem Verifier com PASS e mutação sem sobrevivente nas regras de permissão e exclusão. A última rodada registrada é a 8, anterior ao ciclo 1; o ciclo 2 só acrescentou o parágrafo da l. 739, que diz que o P2 AC 3 "deixou de ser desvio", enquanto as l. 404, 763, 796 e 831 ainda o marcam como "⚠️ desvio" e a l. 394 cita `choiceList()` sem o argumento que hoje é obrigatório. O próprio `AGENTS.md:78` registra "falta o Verifier focado na review". Os 336 testes verdes não substituem a prova de que eles **matam** a mutação nessas linhas.

**Sugestão:**
Verifier focado (como as rodadas 6 e 8), só no diff do ciclo; o `validation.md` passa a ter a "Rodada 9".

**Impacto:**
Mudanças de permissão e exclusão entram na `main` sem a verificação que o trilho exige para o B1.

---

## [MÉDIO] CR-2-03 — A trava de clique duplo foi copiada em três telas (AP-02 de novo, dentro da correção)

**Arquivo:** `resources/js/pages/admin/users/index.tsx:36-38,70-83`; `resources/js/pages/admin/clients/index.tsx:45-47,71-84`; `resources/js/components/contacts/contact-actions.tsx:26-28,66-80`
**Trecho atual:**
```tsx
const rowRequest = (row: ClientRow) => ({
    preserveScroll: true,
    onStart: () => { setBusy(true); setBusyRow(row.id); },
    onFinish: () => { setBusy(false); setBusyRow(null); setConfirm(null); },
});
// O segundo clique é ignorado enquanto a visita roda (Reativar não pede confirmação).
const rowPost = (url: string, row: ClientRow) => !busy && router.post(url, {}, rowRequest(row));
```

**Ajuste necessário:**
Um hook único (por exemplo `useRowAction()` em `hooks/`, devolvendo `busy`, `busyRow`, `request(row)` e `post(url, row)`), usado nas três telas e registrado na tabela de componentes do `AGENTS.md`.

**Motivo:**
O CR-1-09 e o CR-1-17 pediam um padrão único antes do B2; a correção do CR-1-17 nasceu como as mesmas ~15 linhas em três arquivos. É a terceira ocorrência do AP-02 ("B1 nasceu com três CRUDs paralelos; B2 a B7 copiam").

**Sugestão:**
Extrair o hook junto com a correção do CR-2-07, que muda exatamente essas linhas.

**Impacto:**
B2 a B7 copiam uma quarta, quinta… vez; um ajuste na trava vira N edições.

---

## [BAIXO] CR-2-04 — Excluir a WCJ não tem teste pela rota

**Arquivo:** `tests/Feature/Nucleo/ClientControllerTest.php:125-136,191-202`
**Trecho atual:**
```php
// AC 10: a WCJ não oferece desativar.
public function test_wcj_na_lista_nao_oferece_desativar(): void
```

**Ajuste necessário:**
Teste `DELETE /admin/clients/{wcj}`: 403 com "A WCJ não pode ser excluída", linha `denied` gravada e a WCJ intacta, no molde de `test_desativar_a_wcj_responde_403_com_a_mensagem_e_grava_a_negacao` (l. 191). Na l. 126, conferir também `can.delete === false`.

**Motivo:**
O Clientes AC 10 diz "pela tela ou chamando `ClientService::delete`". Há teste na Policy (`ClientPolicyTest.php:119`) e no Service (`ClientServiceTest.php:286`); a rota não tem.

**Impacto:**
Cobertura do AC pela metade; uma troca de ability no controller passaria.

---

## [BAIXO] CR-2-05 — Documentos não acompanharam a D14 nem a F1 1.2

**Arquivo:** `docs/necessidade.md:195`; `docs/escopo.md:77,79`; `.specs/features/b1-nucleo/spec.md:3,57`; `.specs/features/b1-nucleo/design.md:108`
**Trecho atual:**
```markdown
- **N7** … Já vem semeada na instalação, com o CNPJ 38.614.817/0001-22, e não se desativa.
```

**Ajuste necessário:**
Acrescentar "nem se exclui" ao N7 (`necessidade.md:195`), à premissa "Cliente WCJ não desativável" da spec (l. 57), à `ClientPolicy` do `design.md:108` ("`delete`: admin, nunca a WCJ") e ao `escopo.md:79`. O cabeçalho da spec (l. 3) passa a citar a F1 v1.2. No `escopo.md:77`, trocar "notificação `UserInvited` no canal `mail`" pelo Mailable `TemporaryPasswordMail` que o código usa (anterior a este ciclo).

**Motivo:**
A D14 (CR-1-04) entrou no AC 10 e na F1 §04, mas não nos documentos de regra (N7) e de escopo, que o B2 lê primeiro.

**Impacto:**
Rastreabilidade: quem ler o N7 acha que excluir a WCJ é permitido.

---

## [BAIXO] CR-2-06 — `reactivate` de Usuário e Cliente ainda decide pela instância em memória

**Arquivo:** `app/Platform/Services/UserService.php:143-153`; `app/Platform/Services/ClientService.php:125-135`
**Trecho atual:**
```php
public function reactivate(Client $client, User $actor): void
{
    if ($client->isActive()) {
        return;
    }

    DB::transaction(function () use ($client, $actor) {
```

**Ajuste necessário:**
Decidir pela linha travada dentro da transação, como `ContactService::reactivate` (`:113-125`) e os três `deactivate` já fazem.

**Motivo:**
Espelho do CR-1-15, que corrigiu só o `deactivate`. Dois "Reativar" simultâneos (clique em duas abas, dois admins) gravam duas linhas `reactivated`. Anti-padrão AP-01; "um jeito só" entre os três cadastros.

**Impacto:**
Ruído na auditoria; sem dado errado.

---

## [BAIXO] CR-2-07 — A guarda de clique duplo vale para a página, o sinal só para a linha

**Arquivo:** `resources/js/pages/admin/users/index.tsx:83,142`; `clients/index.tsx:84,118`; `contacts/contact-actions.tsx:80,85`
**Trecho atual:**
```tsx
const rowPost = (url: string, row: UserRow) => !busy && router.post(url, {}, rowRequest(row));
…
busy={busyRow === row.id}
```

**Ajuste necessário:**
Ou a guarda olha só a própria linha (`busyRow !== row.id`), ou as outras linhas também aparecem desabilitadas enquanto uma ação roda.

**Motivo:**
Enquanto a ação da linha A roda, os botões da linha B aparecem habilitados e o clique em "Reativar" de B é descartado sem sinal (regra "todo clique sinaliza"). Não há teste de clique duplo para o CR-1-17. A liberação está certa: `onFinish` roda em sucesso, erro e cancelamento.

**Impacto:**
Clique ignorado em silêncio, raro (precisa clicar noutra linha em menos que o tempo da requisição).

---

## [BAIXO] CR-2-08 — Código morto depois do CR-1-13 e do CR-1-08

**Arquivo:** `app/Platform/Services/UserService.php:65,82`; `app/Mail/TemporaryPasswordMail.php:27-29`; `resources/views/mail/temporary-password.blade.php:6`, `temporary-password-text.blade.php:5`; `app/Http/Middleware/HandleInertiaRequests.php:66,68`; `resources/js/types/index.d.ts:12,14`
**Trecho atual:**
```php
subject: $this->isReset
    ? 'Nova senha temporária, '.$first
    : 'Seu acesso ao '.config('app.name').', '.$first,
```

**Ajuste necessário:**
`isReset` é sempre `false` nos dois chamadores: tirar o parâmetro de `TemporaryPasswordService::send`, o ramo do assunto e os `@if ($isReset)` dos templates. As abilities `delete_records` e `reset_passwords` não têm mais leitor no front (só testes): registrar para o Lucas decidir, porque mexer em `abilities` exige aprovação (`AGENTS.md`).

**Motivo:**
Ramo que nenhum caminho alcança confunde quem for mexer no e-mail; ability sem uso vira "segundo jeito" de decidir botão.

**Impacto:**
Manutenção.

---

## [BAIXO] CR-2-09 — "Reenviar convite" aparece para usuário desativado e contradiz a F1 §04 (decisão do Lucas)

**Arquivo:** `app/Platform/Controllers/Admin/UserController.php:121`; `app/Platform/Services/UserService.php:72-83`; `tests/Feature/Nucleo/UserControllerTest.php:110`
**Trecho atual:**
```php
'resend_invite' => $viewer->can('resendInvite', $user),
```

**Ajuste necessário:**
Decisão do Lucas: esconder o botão (e recusar no Service) para quem está desativado, ou aceitar e ajustar a F1 §04 ("Reativar: volta a entrar com a senha de antes").

**Motivo:**
Já era assim antes deste ciclo, mas com o CR-1-13 o desativado recebe "Seu acesso ao WCJ Hub está pronto" com uma senha que o login recusa (N4), e ao ser reativado não volta com a senha de antes, porque ela foi trocada. Não é trava nova: é divergência entre a tela e a F1.

**Impacto:**
E-mail enganoso para quem perdeu o acesso; se for intencional, vira falso positivo no `PROJECT-PATTERNS.md`.

---

## [BAIXO] CR-2-10 — Filtro de data: um select trocado logo depois pode desfazer o período recém-aplicado

**Arquivo:** `resources/js/components/audit-logs/audit-log-filters.tsx:64-66,95-100,161-163`
**Trecho atual:**
```tsx
const params = filterParams(filters);   // from/to vindos do servidor
const visit = (next) => router.get(AUDIT_URL, { ...params, ...next }, { preserveState: true, replace: true });
…
onChange={(event) => visit({ [select.param]: event.target.value || undefined, … })}
```

**Ajuste necessário:**
Os selects visitarem com o período local (`period(from, to)`), não com o do servidor.

**Motivo:**
Digitar a data, sair do campo (o blur dispara a visita com a data nova) e trocar um select antes dessa visita voltar: a do select sai com o `from` antigo e cancela a primeira (visita síncrona interrompe visita síncrona). A resposta traz o `from` antigo, a sincronização das l. 78-83 não dispara (o servidor não mudou) e o campo fica mostrando uma data que não foi aplicada. O e2e do CR-1-07 cobre só "uma visita por digitação".

**Impacto:**
Lista filtrada por um período diferente do que o campo mostra; raro.

---

## [BAIXO] CR-2-11 — Captura do unique em dois formatos; o `update` de cliente sem teste de corrida

**Arquivo:** `app/Platform/Services/UserService.php:60-63,117-120`; `app/Platform/Services/ClientService.php:79-81,101-103,245-253`; `tests/Feature/Nucleo/ClientRepetidoConcorrenciaTest.php:37`
**Trecho atual:**
```php
} catch (UniqueConstraintViolationException) {
    throw ValidationException::withMessages(['email' => self::EMAIL_TAKEN]);
}
```

**Ajuste necessário:**
Um jeito só: o do `ClientService` (relê e relança o original quando o unique violado não é o esperado) é o mais seguro; levar o `UserService` para ele, ou um helper comum. Teste de corrida no `ClientService::update`, como o do `create`.

**Motivo:**
O `UserService` converte **qualquer** violação de unique em "Este e-mail já está em uso" (hoje `users` só tem o unique do e-mail, então não erra; amanhã pode). O helper sugerido no CR-1-02 não foi criado.

**Impacto:**
Manutenção; mensagem errada se um unique novo entrar em `users`.

---

## [BAIXO] CR-2-12 — A linha da lista ainda é montada em dois lugares, e o `situation()` está duplicado

**Arquivo:** `app/Platform/Controllers/Admin/UserController.php:105-124`, `ClientController.php:126-144` × `app/Platform/Services/ContactPresenter.php`, `AuditLogPresenter.php`; `resources/js/components/users/user-table.tsx:32`, `clients/client-table.tsx:31`
**Trecho atual:**
```tsx
export const situation = (row: UserRow) => (row.active ? 'Ativo' : `Desativado ${deactivation(row)}`);
```

**Ajuste necessário:**
Decidir onde a linha mora (Presenter para os quatro, ou controller para os quatro) e registrar como convenção; mover o `situation()` para `lib/situation.ts` ao lado do `deactivation()`.

**Motivo:**
O CR-1-09 tirou a terceira forma (o `ContactService::row`), mas ficaram duas; o B2 vai escolher uma delas por acaso.

**Impacto:**
Manutenção.

---

## Registrado, sem achado

- **`choiceList()` dentro de transação do chamador** (`AuditService.php:52-61`): se o B2 chamar a lista de escolha dentro de uma `DB::transaction` própria, a `AuthorizationException` desfaz a linha `denied`. Hoje ninguém chama (DA-02); vale como nota para o B2.
- **`e2e/mobile.spec.ts`** não inclui "Meu perfil" (tela do template, que a branch não toca) e, com o banco do e2e sem contato garantido, pode conferir Contatos com a lista vazia. O CR-1-20 pedia as 5 telas do Núcleo e o detalhe da auditoria: atendido.
- **CR-1-14** já estava implementado no código antes do ciclo (`contact-form-dialog.tsx:38-55` em `ddd4888`); o ciclo acrescentou a prova e2e. Não é regressão.
- **CR-1-24** foi resolvido pela via da documentação (`DEPLOY.md`), uma das duas que o achado aceitava; o código não recusa `log` em produção.
- **Escape do `LIKE`**: `addcslashes($term, '\\%_')` gera `50\%\_a\\b` para `50%_a\b`; o MySQL usa `\` como escape padrão (o `sql_mode` do `strict` não tem `NO_BACKSLASH_ESCAPES`); o termo vai por bind.
- **Fora da tarefa:** nenhum. Nenhuma dependência nova; nenhuma asserção afrouxada (as trocas nos testes são `WcjClientSeeder::CNPJ` → `Client::WCJ_CNPJ`, `choiceList()` → `choiceList($viewer)` e `[null, true]` → `[false, true]`, mais estritas); `tests/Arch/divida.php` intacto; Services abaixo do teto da A6 (`ContactService` 279, `ClientService` 265, `UserService` 262).

## Está bem feito

- Negação auditada continua num lugar só, agora `AuditService::inspectFor()` (`:52-61`), usado pelo trait dos controllers e FormRequests e pelos dois `choiceList()`; o `AGENTS.md` ("Um jeito só") acompanhou.
- `duplicateRefusal()` (`ClientService.php:245-253`) relança a exceção original quando o unique violado não é o do documento: não engole erro que não conhece.
- `SearchInput` adia a busca durante a escrita sem mudar o payload, e remove os três ouvintes do router no unmount (`search-input.tsx:95-98`).
- Os e2e novos forçam a corrida de verdade (POST segurado 600 ms, digitação segmento a segmento) em vez de esperar tempo; limpeza em `finally`; nomes com `Date.now()`.
- 22 commits pequenos, um achado por commit, com o ID no título: dá para revisar e reverter um por um.

---

## Resumo executivo

**Achados deste ciclo por criticidade:** 0 Crítico · 0 Alto · 3 Médio · 9 Baixo (12).

**Pelo vocabulário da revisão em camadas (`testes.md` §10), para a descrição do PR:**

| Classe | Achados | Observação |
|---|---|---|
| **Bloqueante** | 0 | Nenhum Crítico nem Alto. O CR-2-02 (Verifier) não é bloqueante pela criticidade, mas é **condição do trilho completo** do B1 para o merge (`AGENTS.md`, "Trilhos") |
| **Deveria corrigir** (corrige ou justifica no PR) | 3 | CR-2-01 (deadlock → 500, introduzido pela correção do CR-1-15), CR-2-02 (Verifier e mutação sobre o ciclo), CR-2-03 (trava de clique duplo copiada em três telas) |
| **Detalhe** (a critério de quem fez) | 9 | CR-2-04 a CR-2-12. Pedem decisão do Lucas: CR-2-09 (reenviar convite a desativado) e a parte de `abilities` do CR-2-08 |

**Achados anteriores:** 24 do ciclo 1 → 19 resolvidos e verificados no código · 1 parcial (CR-1-15, por causa do CR-2-01) · 3 aguardando decisão do Lucas (CR-1-11, CR-1-12, CR-1-23) · 1 em PR próprio (CR-1-01, PR #7 aberto) · **0 regressões**.

**Para o Lucas decidir (não é código do Cauã):** CR-1-11 (prazo da senha provisória), CR-1-12 (`min` da carga diária), CR-1-23 (índice da auditoria, migração), CR-2-09 (reenviar convite a desativado), abilities `delete_records` e `reset_passwords` sem leitor (CR-2-08), e o merge do PR #7 (CR-1-01).

**Principais riscos:** (1) dois admins desativando um ao outro ao mesmo tempo recebem 500 em vez do toast do último administrador; (2) as mudanças de permissão e exclusão do ciclo entram sem Verifier nem mutação; (3) o padrão da trava de clique duplo nasce em três cópias, às vésperas do B2; (4) **os 22 commits do ciclo não estão no GitHub**: o PR #5 e o CI ainda mostram `2b076bf`.

**Avaliação geral:** as correções do ciclo 1 são boas e bem provadas: 19 de 24 resolvidas com teste, sem regressão de ID, sem asserção afrouxada, escada inteira verde (336 PHP, 66 e2e). O único defeito novo de comportamento (CR-2-01) nasceu de uma das próprias correções e tem conserto de duas linhas. O resto é duplicação que o B2 vai herdar e rastreabilidade de documento.

**Recomendação final: apta com ajustes.** Antes do merge: corrigir o CR-2-01; rodar o Verifier focado no ciclo (CR-2-02, exigência do trilho completo); push dos 22 commits e CI verde no PR; corrigir ou justificar o CR-2-03. Os Baixos podem ir num PR de limpeza antes do B2.
