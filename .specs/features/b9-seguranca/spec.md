# B9 Segurança (antes da primeira publicação) — Specification

Feature `b9-seguranca` · trilho completo (D-GER-25) · fonte: [`docs/escopo.md`](../../../docs/escopo.md) B9, [`docs/plano-de-desenvolvimento.md`](../../../docs/plano-de-desenvolvimento.md) (onda 4: B9 = segurança + `docs/DEPLOY.md`), [`.ai/core/seguranca.md`](../../../.ai/core/seguranca.md) §1 a §11, [`.ai/core/mcp.md`](../../../.ai/core/mcp.md) §7 e §12. Escrita em 21/10/2026 por Lucas Scholz com IA (D-GER-34), sobre a `main` `bb15375`. Escopo da skill: **Large** (autenticação, upload, headers, teste de arquitetura, CI, runbook).

**Approved by**: Lucas Scholz, 2026-10-21

**Aprovada por Lucas Scholz em 21/10/2026**, com as 8 PARADAS (headers globais, `X-Powered-By`, `storage/{path}`, senha atual no perfil, limitador `senha-atual`, hash descartável, e-mail de redefinição na fila, download pelo Service) confirmadas uma a uma.

## Problem Statement

O Hub vai para a homologação pública no B10, e a homologação precisa ser HTTPS na internet porque os assistentes de IA chamam o `/mcp` da nuvem (B8). O B1 trouxe a maior parte do checklist (`auth` em todo grupo, A5, limitador do login, headers, auditoria de negação) e o B3 trouxe o anexo com tipo conferido pelos bytes. O levantamento de 21/10 achou o que ainda falta antes de alguém de fora alcançar a tela de login: headers que não saem em 404 nem no `/up`, a troca de senha do perfil sem a senha atual e sem limite, diferença de tempo no login e no "esqueci a senha" que revela se o e-mail existe, entrada que não passa por `FormRequest`, o download do anexo fora do Service, uma rota `storage/{path}` que ninguém usa, nenhuma auditoria de dependência no CI e o runbook de publicação ainda em esboço.

## Goals

- [ ] As seis perguntas de `seguranca.md` §11 respondidas por escrito, arquivo por arquivo, por um subagente de contexto zerado, com todo achado "corrigido" (commit + teste) ou "em aberto com correção proposta" e dono.
- [ ] Toda resposta HTTP do Laravel, inclusive 404, 500, `/up` e `api/health`, sai com os headers de segurança e sem `X-Powered-By`.
- [ ] Nenhum caminho de autenticação revela se um e-mail tem conta, nem pela mensagem nem pelo tempo de resposta.
- [ ] Toda escrita valida a entrada num `FormRequest`, e um teste de arquitetura falha se alguém validar fora dele.
- [ ] SCA do padrão `wcj-pentest` rodado, cada aviso classificado por alcance, e `composer audit` e `npm audit` rodando no CI.
- [ ] `docs/DEPLOY.md` vira runbook completo (variáveis por nome e onde buscar, `APP_DEBUG=false`, nginx, fila, migração, backup, voltar atrás), sem nenhum valor de segredo.

## Out of Scope

| Feature | Reason |
|---|---|
| Limitador do `/oauth/token` e do `/mcp`, e a revisão do `mcp.md` §12 | As rotas não existem na `main` (`Passport::ignoreRoutes`, `AppServiceProvider.php:68`) e o B8 do Cauã não começou. Viram regra nesta spec (seção "Para o B8") e nota em [`nota-para-o-b8.md`](nota-para-o-b8.md); o código entra no B8 |
| Configurar nginx, HTTPS, firewall, `real_ip`, a sonda de `.env`/`.git` em servidor de verdade | É o B10, com o Alaor (D-GER-19). O B9 só escreve no `DEPLOY.md` o que o B10 executa e confere |
| Pentest dinâmico (DAST, IDOR por replay entre contas) contra a homologação | Precisa de ambiente vivo; entra no B10, antes do bugbash. O B9 cobre o lado caixa-cinza (código e dependências) |
| Bundle do administrativo separado do bundle do usuário | Todo usuário é interno (até 14), não há visitante anônimo depois do login, não há mapa de rotas (sem Ziggy) e as telas carregam sob demanda (`app.tsx:12`). Gatilho escrito: "se abrir tela para contato de cliente, separar por área" |
| Teto por conta independente do IP (ataque distribuído contra um e-mail) | A regra da casa é IP+conta 5/min e IP 20/min (`seguranca.md` §3), já cumprida e testada (`LoginThrottleTest`) |
| Senha "não vazada" (`Password::uncompromised()`), MFA | `uncompromised` consulta serviço externo (HIBP); a política mínima da casa já está (`AppServiceProvider.php:110-113`). MFA não está em nenhuma regra |
| Criptografar a sessão no banco (`SESSION_ENCRYPT`) | Nenhuma regra pede; a sessão não guarda senha nem token. Fica documentada no `DEPLOY.md` como opção |
| `Content-Security-Policy: sandbox` no download do anexo | Mudar a CSP é ADR (`SecurityHeaders.php`, cabeçalho da classe). O risco já está coberto: só imagem conferida pelos bytes abre na página, o resto baixa, sempre com `nosniff` |
| Tirar o `'unsafe-inline'` do `style-src` | Os primitivos do shadcn/Radix escrevem `style` inline; mudar é ADR e retrabalho de front |
| Endurecer a heurística do A5 (`->can(` dentro de `if` conta como autorização) | Hoje nenhuma ação depende só disso (levantamento de 21/10). Gatilho anotado na revisão dirigida |

---

## Assumptions & Open Questions

Cada linha marcada **PARADA** mexe em autenticação, upload ou headers (inegociável 5). Aprovar a spec não basta (`especificacao.md` §5.1): o "y" de Lucas Scholz em cada uma é a autorização daquela mudança.

| Assumption / decision | Chosen default | Rationale | Confirmed? |
|---|---|---|---|
| **PARADA (headers).** Onde o `SecurityHeaders` roda | Sai do grupo `web` e vira middleware global (`$middleware->append`), sem mudar nenhum valor de header nem a CSP | Hoje 404 sem rota, `/up`, `api/health` e `storage/*` saem sem CSP e sem `nosniff` (medido em 21/10). Global cobre tudo que passa pelo PHP; os assets estáticos ficam com o nginx (B10) | y |
| **PARADA (headers).** `X-Powered-By` | O mesmo middleware remove o header (`header_remove('X-Powered-By')` e `$headers->remove`), e o `DEPLOY.md` manda `expose_php = Off` e `server_tokens off` | `seguranca.md` §5; hoje depende só do `php.ini` do servidor | y |
| **PARADA (upload).** Rota `storage/{path}` | `'serve' => false` no disco `local` (`config/filesystems.php:36`) | A rota existe só porque o padrão do Laravel 12 liga; o Hub não usa `temporaryUrl` (grep de 21/10). Superfície pública sem uso | y |
| **PARADA (autenticação).** Trocar a senha pelo perfil | Passa a pedir a senha atual (`current_password`), com mensagem "A senha atual não confere" | Hoje quem pega uma sessão aberta troca a senha e toma a conta (`ProfileController.php:41-56`). F1 §02 "Perfil (do template)" não fala da senha atual; é o padrão da casa | y |
| **PARADA (autenticação).** Limite na troca de senha pelo perfil | Limitador novo `senha-atual`: 5 tentativas por minuto por usuário logado, com a mesma resposta 429 do login (`ThrottledAuthResponse`) | Com a senha atual exigida, o perfil vira um lugar de adivinhar senha; o limitador do login usa o e-mail do formulário, que o perfil não tem | y |
| **PARADA (autenticação).** Tempo do login com e-mail que não existe | Quando não há usuário com o e-mail, o login confere a senha contra um hash descartável (`Hash::check` num hash fixo gerado com o custo atual), antes de responder `INVALID_CREDENTIALS` | `seguranca.md` §3: mesma mensagem **e** mesmo tempo. O `EloquentUserProvider` não faz isso sozinho | y |
| **PARADA (autenticação).** Tempo do "esqueci a senha" | `ResetPasswordMail` passa a `ShouldQueue`: a resposta não espera o SMTP, exista a conta ou não | Hoje só o e-mail que existe paga o envio síncrono (`User.php:68`). Efeito colateral: sem o worker da fila, o e-mail atrasa. O `DEPLOY.md` já põe o worker no passo a passo e a verificação pós-deploy confere a fila | y |
| **PARADA (upload).** Download do anexo | Sai do controller e vai para `AttachmentService::download(TaskAttachment): StreamedResponse`, com o mesmo comportamento de hoje (imagem na página, resto baixa, `Content-Type` gravado, `nosniff`, nome original) | O escopo do B9 pede "download pelo Service"; hoje o controller usa o `Storage` direto (`TaskDetailController.php:138-149`) | y |
| `FormRequest` na escrita | As cinco escritas com `$request->validate` inline viram `FormRequest`: login, esqueci a senha, redefinir senha, primeiro acesso e perfil. As regras não mudam (fora o `current_password` da linha acima) | `seguranca.md` §7; as outras 27 escritas já usam `FormRequest` | y |
| Filtros das listas (GET) | Ficam como estão (coerção, valor inválido cai no padrão, sem 422), mas toda busca por texto ganha teto de 200 caracteres, o mesmo do quadro e dos indicadores. A obrigação de `FormRequest` vale para escrita | Trocar filtro por `FormRequest` muda o comportamento da tela (erro em vez de padrão) sem ganho de segurança; o que falta é o teto (`ClientController.php:34`, `UserController.php:30`, `ContactController.php:33`, `ProjectController.php:40`, `SprintController.php:95`, `TimeEntryController.php:85`) | y |
| Teste de arquitetura para o `FormRequest` | A10 novo em `tests/Arch`: ação de rota que não é GET não chama `$request->validate(`, `->validate(` de `Request` nem `Validator::make(`; a validação mora no `FormRequest` | Proteção que depende de lembrar falha no próximo endpoint (`seguranca.md` §2) | y |
| Erro em produção | Sem tela de erro nova: as views padrão do Laravel servem. Um teste prova que, com `app.debug = false`, um 500 não traz nome de classe, caminho de arquivo nem stack | `seguranca.md` §5; hoje nada prova isso | y |
| Conferência de produção antes do deploy | Comando `php artisan app:conferir-producao` que falha (código ≠ 0) e lista o que está errado quando: `APP_ENV` ≠ `production`, `APP_DEBUG` ligado, `APP_URL` sem `https://`, `SESSION_SECURE_COOKIE` desligado, `MAIL_MAILER` = `log` ou `array`, `QUEUE_CONNECTION` = `sync`, `APP_KEY` vazio. Só lê a configuração carregada; nunca imprime valor | Escrever `APP_DEBUG=false` no runbook não impede o esquecimento; o comando vira o passo 0 do deploy no `DEPLOY.md` | y |
| Auditoria de dependências no CI | Um passo `composer audit` no job `php` e um `npm audit --omit=dev --audit-level=high` no job `js` do `ci.yml`, sem job novo | `seguranca.md` §10; CI econômico (job novo custa minuto de máquina, passo não) | y |
| Avisos do `npm audit` de 21/10 | `follow-redirects` (via axios) e `launch-editor` (via vite) sobem de versão pelo `npm audit fix`, sem `--force`; a classificação de alcance vai no relatório do SCA | Os dois têm correção sem quebra; o axios no navegador usa XHR e não carrega o `follow-redirects`, e o `launch-editor` só roda no `vite dev`: alcance baixo, mas a correção é barata | y |
| SAST junto do SCA | O `scan.sh <repo> sast` (Semgrep) roda junto do `sca`, e os achados entram no mesmo relatório, triados | Custa minutos, local, e responde parte da pergunta 4 e 5 da revisão dirigida | y |
| Revisão dirigida | Um subagente de contexto zerado (Opus) responde as seis perguntas de `seguranca.md` §11 arquivo por arquivo em `revisao-seguranca.md`, depois do código e antes do Verifier; achado novo vira tarefa de correção ou fica "em aberto com correção proposta" com dono | `seguranca.md` §11: "feita por subagente com contexto zerado antes do humano" | y |
| Valores dos limites | Login 5/min por IP+conta e 20/min por IP (já em `config/auth.php:128-131`); `senha-atual` 5/min por usuário; IA 60/20 por minuto por autorização (B8, `config/hub.php:12-13`) | Os da casa; `seguranca.md` traz "a validar com o Alaor" | y |

**Open questions:** none - all resolved or logged above (confirmadas por Lucas Scholz em 21/10/2026).

---

## User Stories

### P1: Headers em toda resposta ⭐ MVP

**User Story**: As a Administrador do Hub, I want que toda resposta do servidor saia com os headers de segurança, so that uma página de erro ou o `/up` não virem a porta que a CSP fecha.

**Why P1**: O Hub vai para a internet no B10 (HTTPS público por causa do MCP). Header que só sai na tela feliz não protege a página de erro.

**Acceptance Criteria**:

1. WHEN alguém pede uma URL que não tem rota THEN o sistema SHALL responder 404 com `Content-Security-Policy`, `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy` e `Permissions-Policy`.
2. WHEN alguém pede `GET /up` ou `GET /api/health` THEN o sistema SHALL responder com os mesmos cinco headers.
3. The sistema SHALL nunca emitir o header `X-Powered-By` em resposta nenhuma.
4. WHILE `APP_ENV` = `production` the sistema SHALL emitir `Strict-Transport-Security: max-age=31536000; includeSubDomains` também na resposta 404.
5. WHEN alguém pede `GET /storage/qualquer-coisa` THEN o sistema SHALL responder 404, sem rota `storage/{path}` registrada.

**Independent Test**: `curl -I` numa URL inexistente, no `/up` e no `/api/health` mostra os headers; `php artisan route:list` não lista `storage/{path}`.

---

### P1: Senha e conta sem atalho ⭐ MVP

**User Story**: As a usuário do Hub, I want que ninguém consiga descobrir se meu e-mail tem conta nem trocar minha senha com uma sessão esquecida aberta, so that minha conta não seja tomada.

**Why P1**: Login e recuperação de senha são as únicas telas públicas; o perfil é o atalho para a tomada de conta.

**Acceptance Criteria**:

1. IF o usuário logado envia a troca de senha pelo perfil com a senha atual errada THEN o sistema SHALL recusar com "A senha atual não confere" no campo `current_password`.
2. IF o usuário logado envia a troca de senha pelo perfil com a senha atual errada THEN o sistema SHALL manter a senha gravada sem mudança e as outras sessões abertas.
3. WHEN o usuário logado envia a troca de senha pelo perfil com a senha atual certa e uma senha nova válida THEN o sistema SHALL gravar a senha nova e encerrar as outras sessões da conta.
4. WHEN o usuário logado salva só o nome no perfil, sem preencher senha THEN o sistema SHALL gravar o nome sem pedir a senha atual.
5. IF o mesmo usuário logado envia a 6ª troca de senha pelo perfil dentro de um minuto THEN o sistema SHALL responder 429 com a mensagem de limite do login.
6. WHEN alguém tenta entrar com um e-mail que não tem conta THEN o sistema SHALL conferir a senha contra um hash descartável antes de responder.
7. WHEN alguém tenta entrar com um e-mail que não tem conta THEN o sistema SHALL responder com a mesma mensagem `INVALID_CREDENTIALS` de senha errada.
8. WHEN alguém pede "esqueci a senha" para um e-mail que tem conta ativa THEN o sistema SHALL pôr o e-mail de redefinição na fila, sem enviá-lo dentro da requisição.
9. WHEN alguém pede "esqueci a senha" para um e-mail que não tem conta THEN o sistema SHALL responder com a mesma mensagem `GENERIC_MESSAGE` e sem e-mail na fila.

**Independent Test**: no navegador, trocar a senha pelo perfil com a senha atual errada mostra o erro; o teste de feature com `Hash` espionado mostra a conferência no e-mail inexistente; `Mail::fake()` mostra o e-mail de redefinição na fila.

---

### P1: Toda escrita valida num FormRequest ⭐ MVP

**User Story**: As a dono do sistema, I want que toda escrita valide a entrada num `FormRequest` e que um teste impeça o próximo endpoint de escapar disso, so that a entrada hostil nunca chegue ao Service sem regra explícita.

**Why P1**: Item do checklist do B9 (`seguranca.md` §7, pergunta 3 da revisão dirigida).

**Acceptance Criteria**:

1. The sistema SHALL validar login, "esqueci a senha", redefinir senha, primeiro acesso e perfil em classes `FormRequest`, com as mesmas regras e mensagens de hoje.
2. IF uma ação de rota que não é GET chama `$request->validate(` ou `Validator::make(` THEN o teste de arquitetura A10 SHALL falhar citando a ação.
3. IF alguém envia uma busca com mais de 200 caracteres na lista de clientes, contatos, usuários, projetos, sprints ou *Minhas horas* THEN o sistema SHALL ignorar a busca e responder a lista sem filtro de texto, como faz hoje com filtro inválido.
4. The teste de arquitetura A5 (`RotasAutorizadasTest`) SHALL continuar verde, sem rota nova na lista de exceções.

**Independent Test**: `php artisan test tests/Arch` verde; um A10 escrito contra uma ação de mentira com `$request->validate(` falha.

---

### P1: Anexo baixado pelo Service ⭐ MVP

**User Story**: As a dev do Hub, I want que o download do anexo passe pelo `AttachmentService`, so that a regra de como servir arquivo de usuário mora num lugar só.

**Why P1**: Item do checklist do B9 ("download pelo Service").

**Acceptance Criteria**:

1. WHEN quem vê o card baixa um anexo que não é imagem THEN o sistema SHALL responder com `Content-Disposition: attachment`, o nome original, o `Content-Type` gravado e `X-Content-Type-Options: nosniff`.
2. WHEN quem vê o card abre um anexo de imagem (jpg, png, gif, webp) THEN o sistema SHALL responder `inline`, com o `Content-Type` gravado e `nosniff`.
3. IF quem não vê o projeto pede o download de um anexo THEN o sistema SHALL responder 403 e registrar a negação na auditoria.
4. IF o anexo pedido é de outro card THEN o sistema SHALL responder 404.
5. IF o arquivo do anexo não existe mais no disco THEN o sistema SHALL responder 404.
6. The controller SHALL não chamar `Storage` para servir anexo; o download sai do `AttachmentService`.

**Independent Test**: os testes de download que já existem (`TaskDetailTest.php:342-368`) continuam verdes contra o Service; teste novo para o 404 de arquivo sumido.

---

### P1: Produção conferida e runbook completo ⭐ MVP

**User Story**: As a quem publica o Hub (Lucas Scholz e Alaor Rodrigues), I want um runbook completo e um comando que recusa a configuração errada, so that o Hub não suba com `APP_DEBUG` ligado, cookie sem `secure` ou e-mail no log.

**Why P1**: Checklist do B9 (`APP_DEBUG=false`, `.env` de produção) e a linha da onda 4 do plano (B9 = segurança + `DEPLOY.md`).

**Acceptance Criteria**:

1. IF `app:conferir-producao` roda com `app.debug` ligado THEN o comando SHALL sair com código diferente de zero e listar "APP_DEBUG ligado".
2. IF `app:conferir-producao` roda com `APP_ENV` ≠ `production`, `APP_URL` sem `https://`, `SESSION_SECURE_COOKIE` desligado, `MAIL_MAILER` em `log` ou `array`, `QUEUE_CONNECTION` em `sync` ou `APP_KEY` vazio THEN o comando SHALL sair com código diferente de zero e listar cada item errado pelo nome da variável.
3. WHEN `app:conferir-producao` roda com a configuração certa THEN o comando SHALL sair com código zero.
4. The comando SHALL nunca imprimir o valor de nenhuma variável, só o nome e o que está errado.
5. WHILE `app.debug` = `false`, WHEN uma ação lança exceção não tratada THEN o sistema SHALL responder 500 sem o nome da classe da exceção, sem caminho de arquivo e sem stack.
6. The `docs/DEPLOY.md` SHALL listar toda variável do `.env.example` que produção usa, com onde buscar o valor, e nenhum valor de segredo.
7. The `docs/DEPLOY.md` SHALL ter, em ordem: conferência (`app:conferir-producao`), backup, código, dependências e build, migração, sementes, caches, reinício do php-fpm e do worker, verificação pós-deploy e voltar atrás (código e banco).
8. The `docs/DEPLOY.md` SHALL ter o bloco de nginx que o B10 aplica: `server_tokens off`, os headers repetidos no `location` dos assets, bloqueio de todo caminho começado por ponto, de backup e de diagnóstico, e `real_ip` com o proxy da VPS.
9. The seção "Ambientes" do `AGENTS.md` SHALL apontar para o `DEPLOY.md` e dizer o que falta definir no B10 (URL da homologação, servidor, disparo).

**Independent Test**: rodar `php artisan app:conferir-producao` no `.env` de dev lista os itens errados e sai com 1; o teste de feature cobre o caminho certo.

---

### P1: Dependências e revisão dirigida ⭐ MVP

**User Story**: As a dono do sistema, I want o SCA rodado e a revisão de segurança dirigida respondida por escrito, so that o Hub não vá ao ar com CVE conhecida nem com uma das seis falhas de sempre.

**Why P1**: Itens do checklist do B9 e do `seguranca.md` §10 e §11.

**Acceptance Criteria**:

1. WHEN o SCA do `wcj-pentest` roda na branch THEN o relatório em `.specs/features/b9-seguranca/sca-2026-10-21.md` SHALL listar cada aviso com pacote, versão, CVE/GHSA, severidade e a classificação por alcance ("pendência" ou "acompanhamento com gatilho").
2. IF um aviso é classificado "pendência" THEN o B9 SHALL corrigi-lo antes do PR ou registrá-lo "em aberto com correção proposta" com dono.
3. WHEN alguém abre um PR ou faz push na `main` THEN o CI SHALL rodar `composer audit` e `npm audit --omit=dev --audit-level=high` e falhar se houver aviso.
4. The `revisao-seguranca.md` SHALL responder as seis perguntas de `seguranca.md` §11 com arquivo e linha, escrito por subagente de contexto zerado.
5. The `npm audit` local SHALL terminar sem aviso alto, depois do `npm audit fix` sem `--force`.

**Independent Test**: abrir o relatório e conferir uma linha contra o `npm audit`; o `ci.yml` mostra os dois passos.

---

### P2: Regras para o B8 (OAuth e MCP)

**User Story**: As a Cauã (B8), I want as regras de segurança do `/oauth/token` e do `/mcp` escritas antes de eu começar, so that o B8 nasça com o limitador e a revisão do `mcp.md` §12.

**Why P2**: O código é do B8; aqui só a regra e a nota.

**Acceptance Criteria**:

1. The `nota-para-o-b8.md` SHALL listar: limitador nomeado `oauth-token` no `/oauth/token` (IP+`client_id` 5/min e IP 20/min, resposta 429 em JSON OAuth `slow_down`/`too_many_requests`), limite por autorização 60 leituras e 20 escritas por minuto no `/mcp` (T5, `config/hub.php`), `/oauth/*` e `/mcp` dentro do `SecurityHeaders` global, o A5 cobrindo as rotas novas (as do Passport entram na lista de exceção declarada, uma a uma) e as nove perguntas do `mcp.md` §12.
2. WHEN o B8 for aberto THEN a nota SHALL estar no repositório (`.specs/features/b9-seguranca/`) e citada na seção "Para o B8" desta spec.

---

## Edge Cases

- IF o usuário do perfil não preenche a senha nova, mas preenche a senha atual THEN o sistema SHALL gravar só o nome, sem conferir a senha atual.
- IF o limite `senha-atual` estoura THEN o sistema SHALL manter a sessão do usuário de pé (o 429 não desloga).
- IF uma resposta já traz `Content-Security-Policy` (um download, no futuro) THEN o `SecurityHeaders` SHALL sobrescrever com a CSP da aplicação, como hoje; mudar isso é ADR.
- WHEN o `AttachmentService::download` serve um `.txt` ou `.csv` com HTML dentro THEN o sistema SHALL responder `attachment` com `Content-Type: text/plain` ou `text/csv` e `nosniff`.
- IF a fila estiver parada THEN o "esqueci a senha" SHALL continuar respondendo `GENERIC_MESSAGE` na hora, e o e-mail sai quando o worker voltar (a verificação pós-deploy confere a fila).

---

## Implicit-Requirement Dimensions (sweep, Large)

| Dimensão | Requisito ou N/A |
|---|---|
| Validação de entrada e limites | SEC-08 a SEC-10 (FormRequest, A10, teto da busca); anexo já limitado por `config/hub.php` (B3 PR 2) |
| Falha e falha parcial | SEC-13 (500 sem stack); fila parada no "esqueci a senha" (Edge Cases) |
| Repetição e duplicidade | N/A porque o B9 não cria escrita nova; o limitador cobre a repetição de senha (SEC-05) |
| Autorização e limite de taxa | SEC-04, SEC-05, SEC-11 (403 no download), SEC-19 (B8) |
| Concorrência e ordem | N/A porque nenhuma escrita nova lê e grava a mesma linha; o limitador usa o cache atômico do Laravel |
| Ciclo de vida do dado | N/A porque o B9 não cria nem apaga cadastro; a sessão encerrada na troca de senha já existe (`SessionService`) |
| Observabilidade | A negação do download continua na auditoria (SEC-11); o 429 não grava auditoria, como o do login hoje |
| Dependência externa | SMTP vira assíncrono (SEC-07); SCA usa a base OSV local do `osv-scanner` |
| Integridade de transição de estado | N/A porque o B9 não muda estado de card, sprint ou usuário |

---

## Para o B8

O Cauã segue B3 PR 3 → B4 → B6 → B8; não há branch do B8 em 21/10 (`git branch -r`). O que o B8 tem de cumprir, além do `mcp.md` inteiro, está em [`nota-para-o-b8.md`](nota-para-o-b8.md) (SEC-19, SEC-20). O B9 não escreve código de OAuth nem de MCP.

---

## Requirement Traceability

| Requirement ID | Story | Phase | Status |
|---|---|---|---|
| SEC-01 | P1: Headers (AC 1, 2, 4) | Tasks | Pending |
| SEC-02 | P1: Headers (AC 3, `X-Powered-By`) | Tasks | Pending |
| SEC-03 | P1: Headers (AC 5, `storage/{path}`) | Tasks | Pending |
| SEC-04 | P1: Senha (AC 1 a 4, senha atual no perfil) | Tasks | Pending |
| SEC-05 | P1: Senha (AC 5, limitador `senha-atual`) | Tasks | Pending |
| SEC-06 | P1: Senha (AC 6, 7, hash descartável) | Tasks | Pending |
| SEC-07 | P1: Senha (AC 8, 9, e-mail na fila) | Tasks | Pending |
| SEC-08 | P1: FormRequest (AC 1) | Tasks | Pending |
| SEC-09 | P1: FormRequest (AC 2, 4, A10) | Tasks | Pending |
| SEC-10 | P1: FormRequest (AC 3, teto da busca) | Tasks | Pending |
| SEC-11 | P1: Anexo (AC 1 a 6) | Tasks | Pending |
| SEC-12 | P1: Produção (AC 1 a 4, `app:conferir-producao`) | Tasks | Pending |
| SEC-13 | P1: Produção (AC 5, 500 sem stack) | Tasks | Pending |
| SEC-14 | P1: Produção (AC 6 a 8, `DEPLOY.md`) | Tasks | Pending |
| SEC-15 | P1: Produção (AC 9, `AGENTS.md` Ambientes) | Tasks | Pending |
| SEC-16 | P1: Dependências (AC 1, 2, 5, SCA e `npm audit fix`) | Tasks | Pending |
| SEC-17 | P1: Dependências (AC 3, CI) | Tasks | Pending |
| SEC-18 | P1: Dependências (AC 4, revisão dirigida) | Tasks | Pending |
| SEC-19 | P2: B8 (AC 1, nota) | Tasks | Pending |
| SEC-20 | P2: B8 (AC 2, nota no repositório) | Tasks | Pending |

**Coverage:** 20 total, 0 mapped to tasks (a fase Tasks vem depois da aprovação), 20 unmapped ⚠️

---

## Success Criteria

- [ ] Escada local verde: `composer lint`, `caffeinate -i php artisan test`, `npm run typecheck && npm run lint && npm run build`, `npm run test:guard`.
- [ ] `revisao-seguranca.md` com as seis perguntas respondidas e zero achado "em aberto" sem dono.
- [ ] Relatório do SCA com zero "pendência" sem correção ou dono.
- [ ] Verifier (Opus, agente separado) com PASS em `validation.md`, mutação completa nas regras de autenticação (senha atual, limitador, hash descartável) e nos headers.
- [ ] `docs/DEPLOY.md` revisado por Lucas Scholz, sem valor de segredo.
