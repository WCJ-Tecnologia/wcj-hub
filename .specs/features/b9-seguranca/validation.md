# B9 Segurança Validation (rodada 2)

## Validation: PASS

**Result**: PASS na rodada 2. O `729c877` (T20) fecha as 8 correções da rodada 1 e só mexe em teste, na spec e num comentário do `docs/DEPLOY.md`. O código de produção é o mesmo da rodada 1: `git diff b619c1c..729c877 -- app routes bootstrap config resources` sai vazio.

Os 9 sobreviventes da rodada 1 morrem numa cópia descartável:
- M05, M06, M10 e M11 (host e raiz das URLs);
- M24 (texto da senha atual);
- M32 (429 do redefinir com `email[]`);
- M36 e M38 (A10);
- M42 (`Content-Type` do anexo).

Uma amostra de 24 mutantes que já tinham morrido na rodada 1 voltou a morrer, cobrindo autenticação, Timebox, limitador, headers, host, A10 e anexo. A mudança nos testes não enfraqueceu nada. As 2 lacunas de precisão estão fechadas na spec. Os 25 requisitos (SEC-01 a SEC-25) estão verificados.

Gate verde na árvore real (HEAD `729c877`):
- `composer lint`: passou.
- `php artisan test`: **739 passed (6401 assertions)**. Eram 734 (6377): +5 testes, os novos da T20.
- `npm run typecheck`, `npm run lint` e `npm run build`: exit 0.
- `npm run test:guard`: 1053 de 1053.

O PASS não encerra a feature (`.ai/core/especificacao.md` §5 item 9).

**Date**: 2026-10-21
**Spec**: `.specs/features/b9-seguranca/spec.md`, aprovada por Lucas Scholz em 21/10/2026 e revisada no mesmo dia (SEC-21 a SEC-25). A T20 muda duas linhas: SEC-04 AC 1 ganha o ponto final (`spec.md:101`), e SEC-14 AC 8 lista os headers do bloco dos assets (`spec.md:168`).
**Diff range (rodada 2)**: `b619c1c..729c877`. O `2d15eae` no meio é só o `validation.md` da rodada 1 e as lições. O `729c877` mexe em:
- `tests/Arch/RotasAutorizadasTest.php` (+38/−12) e `tests/Arch/Support/AcoesDeExemploA10.php` (+4/−1);
- `tests/Feature/HostDoHubTest.php` (+32);
- `tests/Feature/EntradaEmArrayTest.php` (+18);
- `tests/Feature/Projetos/TaskDetailTest.php` (+19);
- `tests/Feature/ProfilePasswordTest.php` (+1/−2);
- `spec.md` (2 linhas), `tasks.md` (T20) e `docs/DEPLOY.md` (comentário de 2 linhas).

**Verifier (rodada 2)**: subagente independente (autor ≠ verifier), sem acesso ao raciocínio de quem implementou. Leu a spec, a rodada 1, o diff e os testes. Não editou código, teste nem doc. Na árvore real escreveu só este arquivo.

---

## Rodada 1 (resumo)

Diff `bb15375..b619c1c`, 24 commits, 58 arquivos. Gate verde: 734 passed (6377 assertions). Os 25 requisitos tinham evidência, mas o sensor deu **66 mutantes: 53 mortos, 4 equivalentes (M08, M15, M41, M45), 9 sobreviventes** (M05, M06, M10, M11, M24, M32, M36, M38, M42). Todos os 9 eram fraqueza de teste, nenhum defeito de produção. Havia também 2 lacunas de precisão da spec. Veredito: FAIL.

Correções pedidas:
1. A10: fixture só com `Validator::make(`, e varredura sobre rota de mentira POST e GET (M36, M38).
2. Anexo: `.txt` com HTML servido com o tipo gravado (M42, caso de borda do SEC-11).
3. `trustHosts`: subdomínio e host com sufixo recusados (M05, M06).
4. `forceRootUrl`: pedido com outra porta (M11).
5. 429 do redefinir com `email[]` (M32).
6. `UseAppUrl` fora de produção (M10).
7. Texto literal da senha atual, e spec alinhada com o ponto final (M24, lacuna 1).
8. Headers do bloco dos assets na spec ou no `DEPLOY.md` (lacuna 2).

---

## Correções da rodada 1 → evidência (rodada 2)

| # | Critério | Evidência (`arquivo:linha` + asserção) | Resultado |
|---|---|---|---|
| 1 | SEC-09 AC 2 | O fixture `validaComValidator` agora é `Validator::make(...)->fails()` sem `->validate(` (`tests/Arch/Support/AcoesDeExemploA10.php:22`). Detector em `tests/Arch/RotasAutorizadasTest.php:132-134`. A varredura saiu para `violacoesA10()` (`:155-169`), e o A10 real a chama em `:122` com as rotas do roteador e o `funcaoDaRota`. O teste novo (`:130-148`) chama **o mesmo método** em `:146` sobre uma `RouteCollection` com `POST _a10/escrita` e `GET|HEAD _a10/leitura`, as duas para `validaNoRequest`. `:144-145` `assertSame(['POST /_a10/escrita -> …AcoesDeExemploA10@validaNoRequest'], …)`: só a escrita aparece, citando a ação. Não é cópia da varredura: o filtro GET/HEAD e o texto da violação existem só em `violacoesA10()`. Só o resolvedor da função muda (`:142`), porque a ação de exemplo não é `App\` | PASS (M36 e M38 mortos) |
| 2 | SEC-11 AC 1 e caso de borda `.txt` com HTML | `tests/Feature/Projetos/TaskDetailTest.php:424-436`. Grava `tasks/nota.txt` com `<!DOCTYPE html>…<script>` e `mime_type` = `text/plain`. `:432` `assertHeader('X-Content-Type-Options', 'nosniff')`; `:434` `Content-Type` começa com `text/plain`; `:435` `Content-Disposition` começa com `attachment` | PASS (M42 morto: sem o tipo gravado, o disco infere `text/html` do conteúdo) |
| 3 | SEC-21 AC 1 | `tests/Feature/HostDoHubTest.php:74-83`, em produção: `:78-79` `x.hub.exemplo.wcj.test` e `hub.exemplo.wcj.test.atacante.exemplo`, cada um com `assertBadRequest()`; `:82` `Mail::assertNothingOutgoing()` | PASS (M05 e M06 mortos) |
| 4 | SEC-21 AC 2 e AC 3 | `tests/Feature/HostDoHubTest.php:86-95`, em produção, pedido para `hub.exemplo.wcj.test:8080`. `:91` `assertSeeText(APP_URL.'/admin/login')`; `:94` `assertQueued` com `resetUrl` começando com `APP_URL.'/admin/reset-password/'`, sem a porta | PASS (M11 morto) |
| 5 | SEC-23 AC 1 (429 do redefinir) | `tests/Feature/EntradaEmArrayTest.php:71-84`. Cinco `POST /admin/reset-password` com `email[]` dão 302 (`:76`). A 6ª, Inertia, dá `:81` `assertStatus(429)`, `:82` `component` = `auth/reset-password` e `:83` `props.email` = `''`. O `freezeTime()` em `:26` evita a virada do minuto | PASS (M32 morto) |
| 6 | SEC-21, premissa "fora de produção nada muda" | `tests/Feature/HostDoHubTest.php:98-103`: fora de produção, `GET http://outro-host.exemplo/_test/url`, com `:102` `assertSeeText('http://outro-host.exemplo/admin/login')` | PASS (M10 morto) |
| 7 | SEC-04 AC 1 | `tests/Feature/ProfilePasswordTest.php:43` `assertSessionHasErrors(['current_password' => 'A senha atual não confere.'])`, texto literal (o `use` da constante saiu). Spec em `spec.md:101`, agora com o ponto. Código: `UpdateProfileRequest.php:15`. E2E: `e2e/perfil-senha.spec.ts:17` | PASS (M24 morto; lacuna 1 fechada) |
| 8 | SEC-14 AC 8 | `spec.md:168` nomeia os quatro headers repetidos nos assets (`Strict-Transport-Security`, `X-Content-Type-Options`, `X-Frame-Options` e `Referrer-Policy`) e diz por que CSP e `Permissions-Policy` ficam de fora. O `docs/DEPLOY.md:170-173` tem exatamente esses quatro `add_header … always` no `location` dos assets (`:169`). O comentário em `:167-168` explica | PASS (lacuna 2 fechada) |

---

## Critérios de aceite tocados pela T20 (spec-anchored)

| Req · AC | Valor da spec (`spec.md`) | Evidência (`arquivo:linha` + asserção) | Resultado |
|---|---|---|---|
| SEC-04 AC 1 | `:101` recusa com "A senha atual não confere." em `current_password` | `tests/Feature/ProfilePasswordTest.php:43` texto literal igual | PASS |
| SEC-04 AC 2 | `:102` senha e outras sessões intactas | `ProfilePasswordTest.php:45` `Hash::check('senha-atual-1')`; `:46` `assertDatabaseHas('sessions', ['id' => 'outro-aparelho'])` | PASS |
| SEC-04 AC 3 | `:103` grava a nova e encerra as outras | `ProfilePasswordTest.php:81` `Hash::check('senha-nova-123')`; `:82` `assertDatabaseMissing('sessions', …)` | PASS |
| SEC-04 AC 4 | `:104` só o nome não pede a senha atual | `ProfilePasswordTest.php:91` `assertSessionHasNoErrors()`; `:94` `assertSame('Nome Novo', …)` | PASS |
| SEC-05 (mesmo arquivo) | 6ª troca no minuto: 429 com a mensagem do login, por usuário | `ProfilePasswordTest.php:115-117` 429, `admin/profile/index`, `props.errors.current_password` = `INVALID_CREDENTIALS`; `:120` `assertAuthenticatedAs`; por usuário em `:136-151` | PASS |
| SEC-09 AC 2 | `:126` A10 falha com `$request->validate(` ou `Validator::make(` em ação que não é GET, citando a ação | `tests/Arch/RotasAutorizadasTest.php:132-133` as duas formas; `:144-146` só a POST, com o nome da ação | PASS |
| SEC-09 AC 4 | `:128` A5 verde, sem exceção nova | `RotasAutorizadasTest.php:76` (`test_a5_…`), verde no gate; o `729c877` não toca `ROTAS_PUBLICAS` nem `ACOES_DA_PROPRIA_CONTA` | PASS |
| SEC-11 AC 1 | `:142` `attachment`, nome original, `Content-Type` gravado, `nosniff` | `tests/Feature/Projetos/TaskDetailTest.php:385-387` (`ata.pdf`, `application/pdf`, `attachment`); `:432-435` (`.txt` com HTML: `text/plain`, `attachment`, `nosniff`) | PASS |
| SEC-11 AC 2 a 6 | `:143-147` | `TaskDetailTest.php:381-383` (imagem `inline`), `:396-399` (403 + `denied`), `:406-408` (arquivo sumido 404), `:415-416` (controller sem `Storage::`); `TaskDetailPolicyTest.php:196` (outro card 404). Linhas iguais às da rodada 1: o teste novo entrou no fim do arquivo | PASS |
| SEC-14 AC 8 | `:168` `server_tokens off`, os quatro headers nos assets, ponto, backup, diagnóstico, `real_ip` | `docs/DEPLOY.md:144` `server_tokens off`; `:170-173` os quatro headers; os demais itens como na rodada 1 (`:147-149`, `:156-165`) | PASS |
| SEC-21 AC 1 | `:201` outro `Host` em produção: 400, nada na fila | `tests/Feature/HostDoHubTest.php:46` + `:48` (host estranho); `:78-79` + `:82` (subdomínio e sufixo) | PASS |
| SEC-21 AC 2 | `:202` link do e-mail com o `APP_URL` mesmo por `http` | `HostDoHubTest.php:57` (`http`); `:94` (outra porta) | PASS |
| SEC-21 AC 3 | `:203` URL gerada começa com o `APP_URL` | `HostDoHubTest.php:65` (`http`); `:91` (outra porta); fora de produção segue o host (`:102`) | PASS |
| SEC-21 AC 4 | `:204` `server_name` e servidor padrão 444 | `docs/DEPLOY.md:133-138` e `:142`, sem mudança na T20 | PASS |
| SEC-23 AC 1 | `:218` `email` em array no login, esqueci e redefinir: erro de validação, sem 500 | `tests/Feature/EntradaEmArrayTest.php:32`, `:34`, `:38` 302 + erro em `email`; 429 do redefinir sem 500 em `:81-83` | PASS |
| SEC-23 AC 2 | `:219` tela de redefinir com `?email[]=x` e e-mail vazio | `EntradaEmArrayTest.php:44-45` `assertOk()` + `where('email', '')` | PASS |
| SEC-23 AC 3 | `:220` `search`, `country`, `from`, `to` em array caem no padrão | `EntradaEmArrayTest.php:56-57` (`search` → `''`), `:61-62` (`country` → `null`), `:66-67` (`from`/`to` → o padrão) | PASS |

Os critérios dos outros requisitos (SEC-01 a SEC-03, SEC-06 a SEC-08, SEC-10, SEC-12, SEC-13, SEC-15 a SEC-20, SEC-22, SEC-24 e SEC-25) são os da rodada 1. Os arquivos de teste deles não mudaram na T20, e as linhas citadas lá continuam valendo. O gate desta rodada os roda de novo, verdes.

---

## Sensor de discriminação (rodada 2)

**Isolamento:**
- worktree descartável `/tmp/tlc-b9-seguranca-2` (`git worktree add … HEAD --detach` em `729c877`);
- `node_modules` ligado por `ln -s`;
- `vendor` **copiado** com `cp -Rc` (lição da rodada 1: com `ln -s`, o autoload aponta para a árvore real).

**Linha de base na cópia, sem mutação:** `php artisan test` deu **739 passed (6401 assertions)**, igual à árvore real.

**Método:**
- Cada mutante é substituição de texto exata, conferida como única no arquivo antes de aplicar.
- Roda o(s) arquivo(s) de teste do requisito e restaura o arquivo original em seguida.
- Depois do último mutante, o `git status --porcelain` da cópia estava vazio.

A cópia foi removida com `git worktree remove --force`. O porcelain da árvore real ficou igual antes e depois (vazio).

### Os 9 sobreviventes da rodada 1

| # | `arquivo:linha` | Mutação (igual à rodada 1) | Rodada 1 | Rodada 2: quem matou |
|---|---|---|---|---|
| M05 | `bootstrap/app.php:34` | `subdomains: true` | vivo | **morto**: `HostDoHubTest::test_a_subdomain_or_a_lookalike_host_is_refused_in_production` |
| M06 | `bootstrap/app.php:33` | padrão sem `^` e `$` | vivo | **morto**: idem |
| M10 | `app/Http/Middleware/UseAppUrl.php:21` | `if (true)` no lugar de `isProduction()` | vivo | **morto**: `HostDoHubTest::test_outside_production_generated_urls_follow_the_request_host` |
| M11 | `app/Http/Middleware/UseAppUrl.php:24` | sem `URL::forceRootUrl` | vivo | **morto**: `HostDoHubTest::test_a_request_on_another_port_still_generates_app_url_links` |
| M24 | `app/Platform/Requests/Admin/UpdateProfileRequest.php:15` | texto trocado para "Senha atual incorreta." | vivo | **morto**: `ProfilePasswordTest::test_a_wrong_current_password_is_refused_and_nothing_changes` |
| M32 | `app/Http/Responses/ThrottledAuthResponse.php:78` | `'email' => (string) $request->input('email')` | vivo | **morto**: `EntradaEmArrayTest::test_the_throttled_reset_with_an_email_array_answers_429_with_an_empty_email` |
| M36 | `tests/Arch/RotasAutorizadasTest.php:116` | regex sem `\bValidator::make\(` | vivo | **morto**: `RotasAutorizadasTest::test_a10_o_detector_pega_a_validacao_na_mao_e_so_na_escrita` |
| M38 | `tests/Arch/RotasAutorizadasTest.php:159` | filtro invertido (`!== []`): o A10 percorre só GET/HEAD | vivo | **morto**: idem (a lista sai `[]`, ou só a leitura) |
| M42 | `app/Domains/Projetos/Services/AttachmentService.php:89` | `$headers` sem o `Content-Type` gravado | vivo | **morto**: `TaskDetailTest::test_a_text_attachment_with_html_inside_downloads_with_the_stored_type` |

### Amostra dos mortos na rodada 1 (24)

| # | `arquivo:linha` | Mutação | Rodada 2: quem matou |
|---|---|---|---|
| M01 | `bootstrap/app.php:27` | `SecurityHeaders` volta ao grupo `web` | `SecurityHeadersTest`: 404, health, HSTS no 404, `X-Powered-By` (4) |
| M02 | `bootstrap/app.php:32` | `trustHosts` desligado (`false && …`) | `HostDoHubTest`: host estranho e o teste novo de subdomínio (2) |
| M03 | `bootstrap/app.php:33` | padrão `.*` | idem (2) |
| M04 | `bootstrap/app.php:35` | sem `prepend(UseAppUrl)` | `HostDoHubTest`: link por `http`, URL gerada, outra porta (3) |
| M07 | `app/Http/Middleware/SecurityHeaders.php:35` | não remove `X-Powered-By` | `SecurityHeadersTest::test_x_powered_by_is_never_sent` |
| M09 | `app/Http/Middleware/UseAppUrl.php:25` | sem `forceScheme` | `HostDoHubTest` (3) |
| M12 | `config/filesystems.php:36` | `serve => true` | `SecurityHeadersTest::test_the_private_disk_is_not_served_over_http` |
| M14 | `AuthenticatedSessionController.php:41` | callback sem `isActive()` | `LoginTimingTest` (2) e `BackofficeAccessTest` mensagem única |
| M17 | `config/auth.php:145` | Timebox padrão 200 ms | `LoginTimingTest::test_the_production_timebox_is_500_ms_by_default` |
| M18 | `ForgotPasswordController.php:34` | "esqueci" sem `Timebox` | `PasswordFlowTest::test_forgot_and_reset_answer_only_after_the_timebox_for_every_email` |
| M19 | `NewPasswordController.php:39` | "redefinir" sem `Timebox` | idem |
| M21 | `UpdateProfileRequest.php:45` | sem a regra `current_password` | `ProfilePasswordTest` (3) |
| M22 | `UpdateProfileRequest.php:33` | `prepareForValidation` não zera `current_password` | `ProfilePasswordTest::test_saving_only_the_name_does_not_ask_for_the_current_password` |
| M26 | `AppServiceProvider.php:119` | chave do `senha-atual` por IP | `ProfilePasswordTest::test_the_limit_is_per_user` |
| M27 | `AppServiceProvider.php:118` | `senha-atual` com 6 por minuto | `ProfilePasswordTest::test_the_sixth_password_change…` |
| M28 | `AppServiceProvider.php:98` | chave do `login` com `(string)` de array | `EntradaEmArrayTest`: formulários públicos e o 429 novo (2) |
| M29 | `routes/web.php:81` | perfil sem `throttle:senha-atual` | `ProfilePasswordTest::test_the_sixth_password_change…` |
| M31 | `ThrottledAuthResponse.php:53` | erro do 429 do perfil em `email` | idem |
| M35 | `app/Support/Busca.php:20-22` | sem `is_string` | `EntradaEmArrayTest::test_the_lists_fall_back…` |
| M37 | `tests/Arch/RotasAutorizadasTest.php:116` | regex sem `->validate\(` | `RotasAutorizadasTest::test_a10_o_detector_pega_a_validacao_na_mao_e_so_na_escrita` |
| M39 | `AttachmentService.php:91` | imagem como download (`return false ? …`) | `TaskDetailTest::test_an_image_attachment_opens_inline…` |
| M40 | `AttachmentService.php:91` | todo anexo `inline` | `TaskDetailTest` (3, inclusive o novo do `.txt`) |
| M43 | `AttachmentService.php:85` | sem a checagem de arquivo sumido | `TaskDetailTest::test_an_attachment_whose_file_is_gone_answers_404` |
| M44 | `TaskDetailController.php:137` | download sem `authorize` | `TaskDetailTest` (2) e `TaskDetailPolicyTest` (3) |

**Resultado da rodada 2:** 33 mutantes rodados (os 9 vivos e 24 da amostra), **33 mortos**, 0 sobreviventes. Os 4 equivalentes da rodada 1 (M08, M15, M41, M45) seguem equivalentes: o código deles não mudou.

---

## Qualidade

| Princípio | Status |
|---|---|
| Código mínimo, sem escopo a mais | ok. A T20 não toca produção |
| Mudança cirúrgica | ok. A varredura do A10 foi extraída, não duplicada. Os testes novos entram no fim dos arquivos existentes |
| Segue os padrões do projeto | ok. `emProducao()`, `Storage::fake`, `freezeTime()` como no `LoginThrottleTest`, rota `_test/url` como no teste existente |
| O valor afirmado é o da spec | ok. Texto literal da senha atual; os quatro headers dos assets iguais na spec e no `DEPLOY.md` |
| Todo teste aponta para um requisito | ok. Os cinco testes novos citam o mutante e a rodada (docblocks) |
| Nenhum teste enfraquecido | ok. O teste do detector do A10 foi renomeado e ganhou asserções. O `ProfilePasswordTest` trocou a constante pelo texto (mais forte). A amostra de 24 voltou a morrer |

## Gate (árvore real, HEAD `729c877`, sem edição)

| Comando | Resultado |
|---|---|
| `git diff b619c1c..729c877 -- app routes bootstrap config resources` | vazio |
| `composer lint` | passou (`pint --test`) |
| `caffeinate -i php artisan test` | **739 passed (6401 assertions)**, 0 falhas, 0 pulados, 151 s |
| `npm run typecheck` | exit 0 |
| `npm run lint` | exit 0 |
| `npm run build` | exit 0 |
| `npm run test:guard` | 1053 testes, 1053 pass |
| `npm audit --omit=dev --audit-level=high` | 0 vulnerabilidades |
| `node .ai/skills/conferir-specs.mjs` | antes deste arquivo, reprovou só o b9 pelo veredito FAIL da rodada 1 (esperado), com `validate_tasks` 0 erro e 21 avisos (granularidade e "Tests: none" de tarefas de doc) |

Os testes passaram de 734 para 739 (+5): 3 no `HostDoHubTest`, 1 no `EntradaEmArrayTest` e 1 no `TaskDetailTest`. O detector do A10 foi renomeado, sem sair. Nenhum teste saiu.

---

## Gaps restantes (ranqueados, nenhum bloqueia)

1. **Texto sem ponto na tabela de decisões** (`.specs/features/b9-seguranca/spec.md:48`). A linha da PARADA ainda diz "A senha atual não confere", sem o ponto. O critério que vale (`spec.md:101`) já está certo. Só documento. Prioridade: baixa.
2. **Caso de borda do SEC-11 só com `.txt`** (`spec.md:244` fala de `.txt` ou `.csv`; `tests/Feature/Projetos/TaskDetailTest.php:424-436` cobre o `.txt`). O `.csv` passa pelo mesmo caminho do `AttachmentService::download` (`app/Domains/Projetos/Services/AttachmentService.php:89-93`), e o M42 já morre com o `.txt`. Prioridade: baixa.
3. **Equivalentes da rodada 1 sem teste próprio**, como já registrado:
   - M08: `header_remove` do SAPI (`app/Http/Middleware/SecurityHeaders.php:38`), coberto pela verificação pós-deploy (`docs/DEPLOY.md`);
   - M15: `isBackofficeUser()`, hoje igual a todos os papéis (`app/Platform/Enums/RoleEnum.php:44`);
   - M41: `nosniff` no Service, que o middleware global também põe;
   - M45: `belongsToCard`, coberto pelo `scopeBindings()`.

   Nenhum é falha, e nada muda na rodada 2.

Fora do escopo do B9 (observação, não rodada): o `funcaoDaRota` (`tests/Arch/RotasAutorizadasTest.php:177`) é comum ao A5 e ao A10, e os dois pulam a rota cujo resolvedor devolve `null`. Um resolvedor quebrado deixaria os dois vazios. É comportamento do A5 desde o B1, não da T20.

## Rastreabilidade

| Requisito | Rodada 1 | Rodada 2 |
|---|---|---|
| SEC-01, SEC-02, SEC-03, SEC-05 a SEC-08, SEC-10, SEC-12, SEC-13, SEC-15 a SEC-20, SEC-22, SEC-24, SEC-25 | Verified | Verified (gate da rodada 2 verde; amostra do sensor morta) |
| SEC-04 | Verified, com lacuna de precisão | Verified (M24 morto; `spec.md:101` com o ponto) |
| SEC-09 | Needs Fix | Verified (M36 e M38 mortos; `RotasAutorizadasTest.php:130-148`) |
| SEC-11 | Needs Fix | Verified (M42 morto; `TaskDetailTest.php:424-436`) |
| SEC-14 | Verified, com lacuna de precisão | Verified (`spec.md:168` = `docs/DEPLOY.md:170-173`) |
| SEC-21 | Needs Fix | Verified (M05, M06, M10 e M11 mortos; `HostDoHubTest.php:74-103`) |
| SEC-23 | Verified, com ressalva (M32) | Verified (M32 morto; `EntradaEmArrayTest.php:71-84`) |
