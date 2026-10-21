# B9 Segurança Validation (rodada 1)

## Validation: FAIL

**Result**: FAIL. Os 25 requisitos (SEC-01 a SEC-25) têm evidência `arquivo:linha`, e o gate na árvore real está verde. O sensor, porém, deixou 9 mutantes vivos que não são equivalentes, num caminho crítico (autenticação, host, A10, anexo). Todos os 9 são fraqueza de teste: nenhum aponta defeito no código de produção, e a correção é teste novo ou mais preciso. Há também 2 lacunas de precisão da spec. A feature não fecha enquanto as correções 1 a 7 (abaixo) não voltarem para uma rodada 2.

**Date**: 2026-10-21
**Spec**: `.specs/features/b9-seguranca/spec.md`, aprovada por Lucas Scholz em 21/10/2026, revisada no mesmo dia com os achados da revisão dirigida (SEC-21 a SEC-25).
**Diff range**: `bb15375..b619c1c`, 24 commits, 58 arquivos (+2642/−147).
**Verifier**: subagente independente (autor ≠ verifier), sem acesso ao raciocínio de quem implementou. Leu a spec, as tarefas, o diff e os testes. Não editou código, teste nem doc. Na árvore real escreveu só este arquivo e rodou o `lessons.py`.

---

## Tarefas

T1 a T19 estão marcadas `[x]` no `tasks.md`. O que cada uma entrega está no diff:

- **T1, T2:** `bootstrap/app.php`, `SecurityHeaders.php`, `config/filesystems.php`.
- **T3, T15:** `AuthenticatedSessionController.php`, `LoginRequest.php`, `config/auth.php`.
- **T4, T16:** os três `FormRequest` de senha, os controllers e `ResetPasswordMail.php`.
- **T5:** `UpdateProfileRequest.php`, o limitador `senha-atual`, `ThrottledAuthResponse.php` e a tela.
- **T6:** o A10 e `Busca.php`.
- **T7:** `AttachmentService::download`.
- **T8:** `ConferirProducaoCommand.php`.
- **T9:** `ci.yml` e `package-lock.json`.
- **T10, T19:** `DEPLOY.md` e `AGENTS.md`.
- **T11:** a nota para o B8.
- **T12:** o relatório do SCA.
- **T13:** a revisão dirigida.
- **T14:** `UseAppUrl.php` e `trustHosts`.
- **T17:** os `is_string`.
- **T18:** a mensagem da alocação.

Nenhuma tarefa ficou parcial.

---

## Critérios de aceite

Os critérios da spec. Caminhos de teste a partir da raiz do repositório.

### P1 Headers (SEC-01, SEC-02, SEC-03)

| Critério | Resultado da spec | Evidência (`arquivo:linha` + asserção) | Resultado |
|---|---|---|---|
| AC 1: 404 sem rota com os cinco headers (SEC-01) | 404 + CSP, `nosniff`, `SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy` | `tests/Feature/SecurityHeadersTest.php:39` `assertNotFound()` + `assertSecurityHeaders`: `:127` CSP contém `default-src 'self'`; `:128` `assertHeader('X-Content-Type-Options', 'nosniff')`; `:129` `SAMEORIGIN`; `:130` `strict-origin-when-cross-origin`; `:131` `geolocation=(), microphone=(), camera=()` | PASS |
| AC 2: `/up` e `/api/health` (SEC-01) | os mesmos cinco | `tests/Feature/SecurityHeadersTest.php:44-45` `assertOk()` + `assertSecurityHeaders` | PASS |
| AC 3: nunca `X-Powered-By` (SEC-02) | ausente, mesmo quando a rota o define | `tests/Feature/SecurityHeadersTest.php:59-63`: rota com `X-Powered-By: PHP/8.4` + `assertHeaderMissing('X-Powered-By')` na rota, no login e no 404 | PASS |
| AC 4: HSTS no 404 em produção (SEC-01) | `max-age=31536000; includeSubDomains` | `tests/Feature/SecurityHeadersTest.php:50-54` `app['env']='production'` + `assertNotFound()->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains')` | PASS |
| AC 5: `/storage/...` dá 404, sem rota (SEC-03) | 404 e nenhuma rota `storage/` | `tests/Feature/SecurityHeadersTest.php:70` `assertFalse($uris->contains(... 'storage/'))` + `:71` `assertNotFound()` | PASS |

### P1 Senha e conta (SEC-04 a SEC-07, SEC-22)

| Critério | Resultado da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: senha atual errada recusada (SEC-04) | "A senha atual não confere" em `current_password` | `tests/Feature/ProfilePasswordTest.php:44` `assertSessionHasErrors(['current_password' => UpdateProfileRequest::WRONG_CURRENT_PASSWORD])`; a constante vale `'A senha atual não confere.'` (`app/Platform/Requests/Admin/UpdateProfileRequest.php:15`); o texto literal só em `e2e/perfil-senha.spec.ts:17` `getByText('A senha atual não confere.')` | PASS com ressalva: o teste PHP compara com a constante, não com o texto (mutante M24); spec sem ponto final, código com ponto (lacuna de precisão 1) |
| AC 2: senha errada não muda senha nem sessões (SEC-04) | hash igual, outra sessão no banco | `tests/Feature/ProfilePasswordTest.php:46` `assertTrue(Hash::check('senha-atual-1', ...))`; `:47` `assertDatabaseHas('sessions', ['id' => 'outro-aparelho'])` | PASS |
| AC 3: senha certa grava e encerra as outras sessões (SEC-04) | senha nova; outras sessões apagadas | `tests/Feature/ProfilePasswordTest.php:82` `Hash::check('senha-nova-123', ...)`; `:83` `assertDatabaseMissing('sessions', ['id' => 'outro-aparelho'])` | PASS |
| AC 4: só o nome não pede a senha atual (SEC-04) | nome gravado, sem erro | `tests/Feature/ProfilePasswordTest.php:91-95`: `current_password` preenchido sem senha nova, `assertSessionHasNoErrors()`, `assertSame('Nome Novo', ...)` | PASS |
| AC 5: 6ª troca no minuto dá 429 (SEC-05) | 429 com a mensagem do limite do login | `tests/Feature/ProfilePasswordTest.php:113-118` `assertStatus(429)`, `assertJsonPath('component', 'admin/profile/index')`, `assertJsonPath('props.errors.current_password', AuthenticatedSessionController::INVALID_CREDENTIALS)`, que é a mensagem do `ThrottledAuthResponse` (`app/Http/Responses/ThrottledAuthResponse.php:37`). Limite por usuário em `:152`; nome sem limite em `:128-134` | PASS |
| AC 6: login recusado só depois do Timebox (SEC-06) | ≥ `auth.timebox_duration`; 500 ms de padrão | `tests/Feature/LoginTimingTest.php:54` `assertGreaterThanOrEqual(300, $elapsedMs)` nos três casos (sem conta, senha errada, desativada), com `auth.timebox_duration` = 300 ms no teste; `:37` `assertSame(500_000, $config['timebox_duration'])` | PASS |
| AC 7: e-mail sem conta tem a mesma mensagem (SEC-06) | `INVALID_CREDENTIALS` no `email` | `tests/Feature/LoginTimingTest.php:51` `assertSessionHasErrors(['email' => AuthenticatedSessionController::INVALID_CREDENTIALS])`; também `tests/Feature/BackofficeAccessTest.php:50` | PASS |
| AC 8: "esqueci" de conta ativa vai para a fila (SEC-07) | na fila, nada enviado na requisição | `tests/Feature/PasswordFlowTest.php:34` `Mail::assertQueued(ResetPasswordMail::class, ...hasTo...)`; `:35` `Mail::assertNothingSent()` | PASS |
| AC 9: e-mail sem conta (SEC-07) | `GENERIC_MESSAGE`, nada na fila | `tests/Feature/PasswordFlowTest.php:43` `assertSessionHas('success', GENERIC_MESSAGE)`; `:47` `Mail::assertNothingOutgoing()` | PASS |
| AC 10: "esqueci" e "redefinir" só depois do Timebox (SEC-07) | ≥ `auth.timebox_duration` para conta ativa, inativa e inexistente | `tests/Feature/PasswordFlowTest.php:339` e `:349` `assertGreaterThanOrEqual(300, $ms)` nos três e-mails, nas duas rotas | PASS |
| AC 11: conta desativada com a senha certa não loga (SEC-22) | `INVALID_CREDENTIALS`, sem o evento `Login` | `tests/Feature/LoginTimingTest.php:68` erro `INVALID_CREDENTIALS`; `:70` `Event::assertNotDispatched(Login::class)`; `:71` `assertGuest()`. A parte "não é de backoffice" é equivalente hoje: `RoleEnum::backoffice()` devolve todos os papéis (`app/Platform/Enums/RoleEnum.php:44`) | PASS |

### P1 FormRequest e busca (SEC-08, SEC-09, SEC-10)

| Critério | Resultado da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: cinco escritas em `FormRequest`, regras de antes (SEC-08) | login, esqueci, redefinir, primeiro acesso e perfil | `app/Platform/Requests/Auth/LoginRequest.php:24-27`, `ForgotPasswordRequest.php:22`, `ResetPasswordRequest.php:25-29` e `FirstAccessPasswordRequest.php:24` repetem as regras removidas dos controllers (diff). `UpdateProfileRequest.php:43-47` acrescenta só o `current_password`. O A10 verde em `tests/Arch/RotasAutorizadasTest.php:117-136` prova que nenhuma ação de escrita valida na mão. As regras continuam provadas em `tests/Feature/PasswordFlowTest.php:106-121` e em `tests/Feature/EntradaEmArrayTest.php:27-37` | PASS |
| AC 2: A10 falha com `$request->validate(` ou `Validator::make(` (SEC-09) | o teste reprova citando a ação | `tests/Arch/RotasAutorizadasTest.php:140-142` `assertTrue(validaNaMao(...validaNoRequest))` e `validaComValidator`, mais `assertFalse` em `usaOValidado` | **GAP**: o fixture `validaComValidator` (`tests/Arch/Support/AcoesDeExemploA10.php:22`) termina em `->validate()`. Então o `Validator::make(` sozinho nunca é provado (M36 vivo). Nenhum teste prova que a varredura das rotas de escrita reprova uma rota de verdade (M38 vivo) |
| AC 3: busca com mais de 200 caracteres é ignorada (SEC-10) | lista sem filtro de texto, sem 422 | `tests/Feature/BuscaTetoTest.php:36-38` `assertOk()` + `where('filters.search', '')` com 201 caracteres; `:41-43` 200 caracteres filtram, nas seis listas | PASS |
| AC 4: A5 verde, sem exceção nova (SEC-09) | A5 verde | `tests/Arch/RotasAutorizadasTest.php` (`test_a5_toda_rota_e_publica_declarada_ou_autoriza`), verde no gate; o diff do arquivo só acrescenta o A10, e `ROTAS_PUBLICAS` e `ACOES_DA_PROPRIA_CONTA` não mudam | PASS |

### P1 Anexo (SEC-11)

| Critério | Resultado da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: não imagem vai como `attachment` | nome original, `Content-Type` gravado, `nosniff` | `tests/Feature/Projetos/TaskDetailTest.php:385-387` `assertDownload('ata.pdf')`, `assertHeader('Content-Type', 'application/pdf')`, `nosniff`, `assertStringStartsWith('attachment', ...)`; nome em UTF-8 em `:365-366` | **GAP**: tirar o `Content-Type` gravado não quebra nada (M42 vivo), porque o tipo que o disco infere do arquivo é o mesmo que foi gravado. O caso de borda `.txt`/`.csv` com HTML dentro não tem teste |
| AC 2: imagem abre `inline` | `inline`, `Content-Type` gravado, `nosniff` | `tests/Feature/Projetos/TaskDetailTest.php:381-383` `assertHeader('Content-Type', 'image/png')`, `nosniff`, `assertStringStartsWith('inline', ...)` | PASS |
| AC 3: 403 auditado para quem não vê o projeto | 403 + linha `denied` | `tests/Feature/Projetos/TaskDetailTest.php:396` `assertForbidden()`; `:398-399` `denied` `sole()` com `['task', task_id, 'viewed']` | PASS |
| AC 4: anexo de outro card | 404 | `tests/Feature/Projetos/TaskDetailPolicyTest.php:196` `assertNotFound()`, com o arquivo existindo no disco (`:192`) | PASS |
| AC 5: arquivo sumido | 404 | `tests/Feature/Projetos/TaskDetailTest.php:406-408` `Storage::delete` + `assertNotFound()` | PASS |
| AC 6: o controller não serve arquivo | sem `Storage` no controller | `tests/Feature/Projetos/TaskDetailTest.php:415-416` `assertStringNotContainsString('Storage::', $source)` + `assertStringContainsString('$this->attachments->download(', $source)` | PASS |

### P1 Produção e runbook (SEC-12 a SEC-15)

| Critério | Resultado da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: debug ligado (SEC-12) | sai ≠ 0, lista "APP_DEBUG ligado" | `tests/Feature/ConferirProducaoTest.php:34-36` `expectsOutputToContain('APP_DEBUG ligado')->assertExitCode(1)` | PASS |
| AC 2: cada item errado pelo nome (SEC-12) | sai ≠ 0 e o nome de cada variável | `tests/Feature/ConferirProducaoTest.php:50-56`: `APP_ENV`, `APP_URL`, `SESSION_SECURE_COOKIE`, `MAIL_MAILER` (`log`), `QUEUE_CONNECTION`, `APP_KEY`; `:58-59` `MAIL_MAILER` com `array` | PASS |
| AC 3: configuração certa (SEC-12) | sai 0 | `tests/Feature/ConferirProducaoTest.php:27` `assertExitCode(0)` | PASS |
| AC 4: nunca imprime valor (SEC-12) | só o nome | `tests/Feature/ConferirProducaoTest.php:75-78` `doesntExpectOutputToContain('staging-secreto'/'interno-secreto'/'Q2hhdmVE')` | PASS |
| AC 5: 500 sem classe, caminho nem stack (SEC-13) | 500 sem nada do servidor | `tests/Feature/ErroSemStackTest.php:21-28` `assertStatus(500)` + `assertStringNotContainsString` de `RuntimeException`, `.php`, `#0 ` e `base_path()`, em HTML e JSON | PASS |
| AC 6: toda variável do `.env.example` no runbook, sem valor (SEC-14) | nome + onde buscar | `docs/DEPLOY.md:33-71`. Conferido por script: os nomes do `.env.example` aparecem todos no `DEPLOY.md`. Segredo só como "cofre da WCJ" (`:50`, `:66`, `:68`) | PASS |
| AC 7: ordem do runbook (SEC-14) | conferência, backup, código, dependências e build, migração, sementes, caches, reinício, verificação, voltar atrás | `docs/DEPLOY.md:90-108` (0 a 7, nessa ordem), `:194-209` (verificação), `:211-215` (voltar atrás) | PASS |
| AC 8: bloco do Nginx (SEC-14) | `server_tokens off`, headers repetidos nos assets, ponto, backup, diagnóstico, `real_ip` | `docs/DEPLOY.md:144` `server_tokens off`; `:168-176` headers dos assets; `:156-159` caminho com ponto; `:162-165` backup e log; `:147-149` `real_ip` (comentado, a preencher no B10) | PASS com lacuna de precisão 2: o bloco dos assets repete HSTS, `nosniff`, XFO e `Referrer-Policy`, mas não CSP nem `Permissions-Policy`, e a spec não diz quais repetir |
| AC 9: "Ambientes" do `AGENTS.md` (SEC-15) | aponta o `DEPLOY.md` e o que falta no B10 | `AGENTS.md:194-197` | PASS |

### P1 Dependências e revisão (SEC-16, SEC-17, SEC-18)

| Critério | Resultado da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: relatório do SCA (SEC-16) | pacote, versão, CVE/GHSA, severidade, classificação | `.specs/features/b9-seguranca/sca-2026-10-21.md:24-30` (5 avisos da `main`) e `:43-46` (SAST); gatilhos em `:34-37` e `:48` | PASS |
| AC 2: pendência corrigida ou com dono (SEC-16) | zero pendência sem dono | `sca-2026-10-21.md:5` "zero pendência"; os SAST com dono B10 em `:45-46` | PASS |
| AC 3: auditoria no CI (SEC-17) | `composer audit` e `npm audit --omit=dev --audit-level=high` em PR e push na `main` | `.github/workflows/ci.yml:76-77` e `:97-98`; gatilhos em `:7-30` | PASS |
| AC 4: as seis perguntas (SEC-18) | com arquivo e linha, por subagente de contexto zerado | `.specs/features/b9-seguranca/revisao-seguranca.md:3` (revisor), seções `:15`, `:32`, `:66`, `:84`, `:103`, `:114`; achados com dono em `:140-145` | PASS |
| AC 5: `npm audit` sem aviso alto (SEC-16) | 0 alto | rodado nesta validação: `npm audit --omit=dev --audit-level=high` → "found 0 vulnerabilities" | PASS |

### P1 Links no domínio do Hub (SEC-21)

| Critério | Resultado da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: em produção, outro `Host` dá 400 e nada vai para a fila | 400; nada na fila | `tests/Feature/HostDoHubTest.php:46` `assertBadRequest()` com `atacante.exemplo`; `:48` `Mail::assertNothingOutgoing()` | **GAP**: só um host de todo estranho é testado. Os mutantes `subdomains: true` (M05) e o padrão sem `^`/`$` (M06) sobrevivem, e aceitariam `x.hub.exemplo.wcj.test` e `hub.exemplo.wcj.test.atacante.exemplo` |
| AC 2: link do e-mail começa com o `APP_URL` mesmo por `http` | `https://…/admin/reset-password/` | `tests/Feature/HostDoHubTest.php:57` `assertQueued(..., str_starts_with($mail->resetUrl, APP_URL.'/admin/reset-password/'))` | PASS |
| AC 3: URL gerada na requisição começa com o `APP_URL` | prefixo `APP_URL` | `tests/Feature/HostDoHubTest.php:65` `assertSeeText(APP_URL.'/admin/login')` | **GAP**: sem o `URL::forceRootUrl` (M11) nada quebra, porque a requisição do teste usa o mesmo host do `APP_URL`, só que por `http`. Um `Host` com porta (`hub.exemplo.wcj.test:8080`) passa pelo `trustHosts` e mostraria a diferença |
| AC 4: `server_name` e servidor padrão 444 no `DEPLOY.md` | os dois | `docs/DEPLOY.md:133-138` (`default_server`, `return 444`) e `:142` (`server_name`) | PASS |

### P2 Entrada em array e recusa sem nome (SEC-23, SEC-24, SEC-25)

| Critério | Resultado da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: `email[]` no login, no esqueci e no redefinir (SEC-23) | erro de validação, sem 500 | `tests/Feature/EntradaEmArrayTest.php:29-36` `assertStatus(302)->assertSessionHasErrors('email')` nas três | PASS. Ressalva: a resposta 429 do redefinir com `email[]` (`ThrottledAuthResponse.php:78`, mudada no diff) não tem teste (M32 vivo) |
| AC 2: tela de redefinir com `?email[]=x` (SEC-23) | tela com e-mail vazio | `tests/Feature/EntradaEmArrayTest.php:41-43` `assertOk()` + `where('email', '')` | PASS |
| AC 3: `search`, `country`, `from`, `to` em array (SEC-23) | filtro no padrão, sem 500 | `tests/Feature/EntradaEmArrayTest.php:52-56` (`search[]` nas seis listas → `''`), `:58-60` (`country` → `null`), `:62-65` (`from`/`to` → o padrão) | PASS |
| AC 4: recusa sem o nome (SEC-24) | "A pessoa escolhida não está alocada neste projeto." | `tests/Feature/Projetos/TaskDetailTest.php:184-185` `assertSessionHas('error', 'A pessoa escolhida não está alocada neste projeto.')` com o usuário "Fulana de Fora"; `tests/Feature/Projetos/SprintEmLoteTest.php:312` `assertSame(...)` | PASS |
| AC 5: `TRUSTED_PROXIES` com FastCGI (SEC-25) | só proxy HTTP, nunca `*` | `docs/DEPLOY.md:45` e `:187-192` | PASS |

### P2 Para o B8 (SEC-19, SEC-20)

| Critério | Resultado da spec | Evidência | Resultado |
|---|---|---|---|
| AC 1: os cinco itens da nota (SEC-19) | limitador `oauth-token`, `/mcp` 60/20, headers, A5, as nove perguntas | `.specs/features/b9-seguranca/nota-para-o-b8.md:7-15`, `:17-22`, `:24-28`, `:30-34`, `:36-48` | PASS |
| AC 2: nota no repositório e citada (SEC-20) | em `.specs/features/b9-seguranca/` e na spec | o próprio arquivo; `.specs/features/b9-seguranca/spec.md:267` | PASS |

**Situação:** os 25 requisitos têm evidência. 4 critérios têm lacuna de teste: SEC-09 AC 2, SEC-11 AC 1, SEC-21 AC 1 e SEC-21 AC 3. 2 critérios têm lacuna de precisão da spec: SEC-04 AC 1 e SEC-14 AC 8.

### Casos de borda da spec

- [x] Senha atual preenchida sem senha nova grava só o nome: `tests/Feature/ProfilePasswordTest.php:91-95`.
- [x] O 429 do `senha-atual` não desloga: `tests/Feature/ProfilePasswordTest.php:121` `assertAuthenticatedAs($user)`.
- [ ] Resposta que já traz CSP é sobrescrita: comportamento de antes do B9 (`SecurityHeaders.php:27`, `set`), sem teste. Não bloqueia.
- [ ] `.txt` ou `.csv` com HTML dentro baixa como `attachment` com `text/plain` ou `text/csv` e `nosniff`: **sem teste** (correção 2).
- [x] Fila parada: o "esqueci" responde `GENERIC_MESSAGE` sem esperar o envio, provado pelo `assertQueued` + `assertNothingSent` em `tests/Feature/PasswordFlowTest.php:34-35`.

---

## Sensor de discriminação

**Profundidade:** completa à mão (caminho crítico: autenticação, limitador, headers e host; o projeto não tem Infection). 66 mutantes, cobrindo cada item pedido e os dois sentidos dos ramos.

**Isolamento:** worktree descartável `/tmp/tlc-b9-seguranca-1` (`git worktree add … HEAD --detach`), com `node_modules` ligado por `ln -s`.

O `vendor` foi **copiado** com `cp -Rc` (clone do APFS), não ligado. Com `vendor` ligado por `ln -s`, a primeira rodada deu 8 de 8 sobreviventes. O `__DIR__` do autoload do Composer resolve o link, então o `ClassLoader` e o `Application::inferBasePath()` apontavam para a árvore real: os testes rodavam o código sem mutação. A rodada foi descartada e o `vendor` copiado.

Linha de base na worktree, sem mutação: **734 passed (6377 assertions)**. Um mutante de controle (M07) morreu antes da rodada.

Porcelain da árvore real antes e depois: vazio e vazio. A worktree foi removida.

**Resultado:** 53 mortos, 4 equivalentes, 9 sobreviventes não equivalentes.

| # | `arquivo:linha` | Mutação | Resultado (teste que morreu) |
|---|---|---|---|
| M01 | `bootstrap/app.php:27` | `SecurityHeaders` volta ao grupo `web` | morto: `SecurityHeadersTest` 404, health e HSTS no 404 (4 testes) |
| M02 | `bootstrap/app.php:32` | `trustHosts` removido | morto: `HostDoHubTest::test_a_foreign_host_is_refused…` |
| M03 | `bootstrap/app.php:33` | padrão `.*` (qualquer host) | morto: idem |
| M04 | `bootstrap/app.php:35` | sem `prepend(UseAppUrl)` | morto: `HostDoHubTest` link por `http` e URL gerada |
| M05 | `bootstrap/app.php:34` | `subdomains: true` | **sobreviveu** (gap, correção 3) |
| M06 | `bootstrap/app.php:33` | padrão sem `^` e `$` | **sobreviveu** (gap, correção 3) |
| M07 | `app/Http/Middleware/SecurityHeaders.php:35` | não remove `X-Powered-By` | morto: `SecurityHeadersTest::test_x_powered_by_is_never_sent` |
| M08 | `app/Http/Middleware/SecurityHeaders.php:38` | sem `header_remove` | equivalente no teste: o header do SAPI não passa pelo objeto `Response` do teste. Coberto pela verificação pós-deploy (`docs/DEPLOY.md:203`) |
| M09 | `app/Http/Middleware/UseAppUrl.php:25` | sem `forceScheme` | morto: `HostDoHubTest` link por `http` e URL gerada |
| M10 | `app/Http/Middleware/UseAppUrl.php:21` | `if (true)` no lugar de `isProduction()` | **sobreviveu** na suíte Feature inteira (650 testes). Gap, correção 6 |
| M11 | `app/Http/Middleware/UseAppUrl.php:24` | sem `forceRootUrl` | **sobreviveu** (gap, correção 4) |
| M12 | `config/filesystems.php:36` | `serve => true` | morto: `SecurityHeadersTest::test_the_private_disk_is_not_served_over_http` |
| M13 | `AuthenticatedSessionController.php:39-42` | `attemptWhen` sem o callback | morto: `LoginTimingTest` (2) e `BackofficeAccessTest` mensagem única |
| M14 | `AuthenticatedSessionController.php:41` | callback sem `isActive()` | morto: idem (3 testes) |
| M15 | `AuthenticatedSessionController.php:41` | callback sem `isBackofficeUser()` | equivalente: `RoleEnum::backoffice()` = `self::cases()` (`app/Platform/Enums/RoleEnum.php:44`) |
| M16 | `AuthenticatedSessionController.php:39-46` | volta ao `attempt` + `logout` depois | morto: `LoginTimingTest` Timebox e `Event::assertNotDispatched(Login)` |
| M17 | `config/auth.php:145` | padrão 200 ms | morto: `LoginTimingTest::test_the_production_timebox_is_500_ms_by_default` |
| M18 | `ForgotPasswordController.php:34` | esqueci sem `Timebox` | morto: `PasswordFlowTest::test_forgot_and_reset_answer_only_after_the_timebox…` |
| M19 | `NewPasswordController.php:39` | redefinir sem `Timebox` | morto: idem |
| M20 | `app/Mail/ResetPasswordMail.php:19` | sem `ShouldQueue` | morto: `PasswordFlowTest` fila e `HostDoHubTest` link |
| M21 | `UpdateProfileRequest.php:45` | sem a regra `current_password` | morto: `ProfilePasswordTest` (3 testes) |
| M22 | `UpdateProfileRequest.php:33` | `prepareForValidation` não zera `current_password` | morto: `ProfilePasswordTest::test_saving_only_the_name…` |
| M23 | `UpdateProfileRequest.php:45` | sem `required_with:password` | morto: `ProfilePasswordTest::test_a_missing_current_password…` |
| M24 | `UpdateProfileRequest.php:15` | texto da mensagem trocado | **sobreviveu** no PHPUnit, que compara com a constante (gap, correção 7). O e2e `e2e/perfil-senha.spec.ts:17` tem o texto literal, mas não rodou neste gate |
| M25 | `AppServiceProvider.php:114-116` | `senha-atual` sem o `Limit::none()` | morto: `ProfilePasswordTest::test_saving_only_the_name_is_not_throttled` |
| M26 | `AppServiceProvider.php:119` | chave por IP | morto: `ProfilePasswordTest::test_the_limit_is_per_user` |
| M27 | `AppServiceProvider.php:118` | 6 por minuto | morto: `ProfilePasswordTest::test_the_sixth_password_change…` |
| M28 | `AppServiceProvider.php:98` | chave do `login` volta ao `(string)` de array | morto: `EntradaEmArrayTest::test_the_public_credential_forms…` |
| M29 | `routes/web.php:81` | rota do perfil sem `throttle:senha-atual` | morto: `ProfilePasswordTest` 6ª troca |
| M30 | `ThrottledAuthResponse.php:29` | sem a entrada do perfil | morto: idem |
| M31 | `ThrottledAuthResponse.php:53` | erro do 429 no campo `email` | morto: idem |
| M32 | `ThrottledAuthResponse.php:78` | 429 do redefinir volta ao `(string)` de array | **sobreviveu** (gap, correção 5) |
| M33 | `app/Support/Busca.php:15` | teto 201 | morto: `BuscaTetoTest` |
| M34 | `app/Support/Busca.php:25` | `>=` (corta 200) | morto: `BuscaTetoTest` |
| M35 | `app/Support/Busca.php:20-22` | sem `is_string` | morto: `EntradaEmArrayTest::test_the_lists_fall_back…` |
| M36 | `tests/Arch/RotasAutorizadasTest.php:115` | regex sem `Validator::make\(` | **sobreviveu** (gap, correção 1) |
| M37 | `tests/Arch/RotasAutorizadasTest.php:115` | regex sem `->validate\(` | morto: `RotasAutorizadasTest::test_a10_o_detector…` |
| M38 | `tests/Arch/RotasAutorizadasTest.php:124` | A10 percorre só as rotas GET | **sobreviveu** (gap, correção 1) |
| M39 | `AttachmentService.php:91` | imagem como download | morto: `TaskDetailTest::test_an_image_attachment_opens_inline…` |
| M40 | `AttachmentService.php:91` | todo anexo `inline` | morto: `TaskDetailTest` (2 testes) |
| M41 | `AttachmentService.php:89` | sem `nosniff` no Service | equivalente: o `SecurityHeaders` global põe `nosniff` em toda resposta (`SecurityHeaders.php:31`) |
| M42 | `AttachmentService.php:89` | sem o `Content-Type` gravado | **sobreviveu** (gap, correção 2) |
| M43 | `AttachmentService.php:85-87` | sem a checagem de arquivo sumido | morto: `TaskDetailTest::test_an_attachment_whose_file_is_gone_answers_404` |
| M44 | `TaskDetailController.php:137` | download sem `authorize` | morto: `TaskDetailPolicyTest` 403 em toda rota e `TaskDetailTest` (5 testes) |
| M45 | `TaskDetailController.php:136` | download sem `belongsToCard` | equivalente: a rota fica dentro do `Route::scopeBindings()` (`routes/web.php:133-210`), e o anexo de outro card já é 404 no binding |
| M46-M52 | `ConferirProducaoCommand.php:21-27` | cada uma das sete condições invertida | 7 mortos: `test_the_right_configuration_passes` e o item no laço ou `debug_on` |
| M53-M59 | `ConferirProducaoCommand.php:21-27` | cada uma das sete condições removida (M58: `MAIL_MAILER` sem o `array`) | 7 mortos: `test_every_wrong_item…` ou `test_debug_on…` |
| M60 | `ConferirProducaoCommand.php:38` | imprime o `APP_URL` | morto: `test_no_value_is_ever_printed` |
| M61 | `ConferirProducaoCommand.php:38` | imprime a `APP_KEY` | morto: idem |
| M62 | `ConferirProducaoCommand.php:41` | sai 0 com erro | morto: 3 testes |
| M63 | `TaskAssignmentService.php:117` | mensagem com o nome de volta | morto: `TaskDetailTest::test_dev_and_qa…` e `SprintEmLoteTest::test_service_refuses…` |
| M64 | `ClientController.php:33` | país sem `is_string` | morto: `EntradaEmArrayTest::test_the_lists_fall_back…` |
| M65 | `TimeEntryController.php:77` | datas sem `is_string` | morto: idem |
| M66 | `NewPasswordController.php:27` | tela de redefinir sem `is_string` | morto: `EntradaEmArrayTest::test_the_reset_screen…` |

---

## Qualidade

| Princípio | Status |
|---|---|
| Código mínimo, sem escopo a mais | ok. O `PERSON_NOT_ALLOCATED` convive com o `NOT_ALLOCATED`, que segue em uso pelas outras mensagens |
| Mudança cirúrgica | ok. A10 e `Busca` reaproveitam a enumeração do A5 e o padrão das listas |
| Segue os padrões do projeto | ok. `FormRequest`, `RateLimiter::for`, `ThrottledAuthResponse`, Service |
| O valor afirmado é o da spec | com lacunas: SEC-09 AC 2, SEC-11 AC 1, SEC-21 AC 1 e AC 3 (acima) |
| Todo teste aponta para um requisito | ok. Os nove arquivos novos ou mudados citam SEC-xx |
| Guias seguidos | `AGENTS.md`, `.ai/core/especificacao.md` §5 e §6, `.ai/core/testes.md` §10, `.ai/core/seguranca.md` §3 (limite provado por sequência real) |

## Gate (árvore real, HEAD `b619c1c`, sem edição)

| Comando | Resultado |
|---|---|
| `composer lint` | passou (pint) |
| `caffeinate -i php artisan test` | **734 passed (6377 assertions)**, 0 falhas, 0 pulados, 140 s |
| `npm run typecheck` | exit 0 |
| `npm run lint` | exit 0 |
| `npm run build` | exit 0 |
| `npm run test:guard` | 1053 testes, 1053 pass |
| `npm audit --omit=dev --audit-level=high` | 0 vulnerabilidades |
| `node .ai/skills/conferir-specs.mjs` | antes deste arquivo, reprovou só o b9 por falta do `validation.md` (esperado), com 0 erro no `tasks.md` |

Os métodos de teste em `tests/` passaram de 573 (`bb15375`) para 608, ou seja, +35. Nenhum teste saiu. A asserção de `SprintEmLoteTest.php:312` trocou de texto, pelo SEC-24, sem perder força. A dívida A6 do perfil e do `NewPasswordController::store` saiu do `tests/Arch/divida.php`, pagando dívida.

---

## Correções necessárias (rodada 1)

Em ordem de prioridade. Todas são de teste, salvo a 7, que pode tocar a spec.

1. **A10 não prova o que a SEC-09 AC 2 diz** (M36, M38; `tests/Arch/RotasAutorizadasTest.php:115-148`, `tests/Arch/Support/AcoesDeExemploA10.php:22`). Prioridade: média.
   - Trocar o fixture `validaComValidator` por `Validator::make(...)->fails()`, sem `->validate(`.
   - Acrescentar um teste que registra uma rota `POST` de mentira para `AcoesDeExemploA10::validaNoRequest` e uma `GET` para a mesma ação. Ele roda a mesma varredura do `test_a10_escrita…`, extraída para um método que devolve as violações, e afirma que só a `POST` aparece, com o nome da ação.
   - Feito quando M36 e M38 morrem.
2. **`Content-Type` gravado sem discriminação, e o caso de borda `.txt`/`.csv` com HTML sem teste** (M42; `app/Domains/Projetos/Services/AttachmentService.php:89`; `tests/Feature/Projetos/TaskDetailTest.php:385`). Prioridade: média.
   - Teste com um `.txt` (ou `.csv`) cujo conteúdo é HTML, enviado pela rota.
   - Afirma `Content-Disposition: attachment`, `Content-Type` igual ao `mime_type` gravado (`text/plain` ou `text/csv`, nunca `text/html`) e `nosniff`.
   - Se o envio recusar esse arquivo pelo tipo dos bytes, gravar o anexo direto (como em `TaskDetailPolicyTest.php:193`), com o `mime_type` diferente do que o disco inferiria.
   - Feito quando M42 morre.
3. **`trustHosts` sem prova de que recusa subdomínio e host que só contém o do `APP_URL`** (M05, M06; `bootstrap/app.php:32-34`; `tests/Feature/HostDoHubTest.php:42-49`). Prioridade: média (autenticação).
   - Em produção, `POST` para `http://x.hub.exemplo.wcj.test/...` e para `http://hub.exemplo.wcj.test.atacante.exemplo/...`, cada um com `assertBadRequest()` e `Mail::assertNothingOutgoing()`.
   - Feito quando M05 e M06 morrem.
4. **`forceRootUrl` sem discriminação** (M11; `app/Http/Middleware/UseAppUrl.php:24`; `tests/Feature/HostDoHubTest.php:51-66`). Prioridade: baixa a média.
   - Em produção, pedido ao host do `APP_URL` com outra porta (`http://hub.exemplo.wcj.test:8080/...`).
   - Afirma que o link do e-mail e a URL gerada começam com `https://hub.exemplo.wcj.test/`, sem a porta.
   - Feito quando M11 morre.
5. **429 do redefinir com `email[]`** (M32; `app/Http/Responses/ThrottledAuthResponse.php:78`). Prioridade: baixa.
   - Teste que esgota o limitador `login` e manda a 6ª requisição Inertia ao `POST /admin/reset-password` com `email[]`.
   - Afirma 429 com `props.email` igual a `''`, nunca 500.
6. **`UseAppUrl` fora de produção** (M10; `app/Http/Middleware/UseAppUrl.php:21`). Prioridade: baixa. A premissa confirmada "fora de produção, nada muda" não tem teste.
   - Fora de produção, `GET http://outro-host.exemplo/_test/url` (rota que devolve `route('login')`).
   - Afirma que a URL sai com `http://outro-host.exemplo`.
   - Feito quando M10 morre.
7. **Mensagem da senha atual** (M24; `tests/Feature/ProfilePasswordTest.php:44`; lacuna de precisão 1). Prioridade: baixa.
   - Afirmar o texto literal no teste PHP, não só a constante.
   - Alinhar a spec (SEC-04 AC 1, "A senha atual não confere") com o código (`'A senha atual não confere.'`), ou o contrário. Decisão de Lucas Scholz.
8. **Lacuna de precisão 2** (`docs/DEPLOY.md:168-176`; SEC-14 AC 8). Prioridade: baixa, só documento. A spec não diz quais headers o bloco dos assets repete. Hoje ficam de fora a CSP e o `Permissions-Policy`. Decidir e escrever na spec, ou acrescentar os dois ao bloco.

## Rastreabilidade

| Requisito | Antes | Agora |
|---|---|---|
| SEC-01, SEC-02, SEC-03, SEC-05 a SEC-08, SEC-10, SEC-12 a SEC-20, SEC-22 a SEC-25 | Implementing | Verified |
| SEC-04 | Implementing | Verified, com lacuna de precisão (correção 7) |
| SEC-09 | Implementing | Needs Fix (correção 1) |
| SEC-11 | Implementing | Needs Fix (correção 2) |
| SEC-21 | Implementing | Needs Fix (correções 3, 4 e 6) |

## Nota para quem conduz (fora do código do B9)

O `.ai/core/especificacao.md` §5 item 8 manda ligar o `vendor` da árvore real com `ln -s`. No Laravel, isso faz o sensor testar a árvore real, e todo mutante sobrevive. Vale corrigir a regra da casa para copiar o `vendor` (`cp -Rc`, clone do APFS, segundos) ou instalar.
