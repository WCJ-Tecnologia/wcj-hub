# Padrões do Projeto — aprendidos via code review

> Mantido pela skill `wcj-code-review`. Editável à mão.
> Atualizado ao final de cada ciclo de review.

**Última atualização:** 2026-10-09 · **Reviews acumulados:** 5 (b1-nucleo ciclos 1 a 5; a revisão de 07/10 em `docs/revisoes/` é anterior à skill)

---

## Convenções confirmadas

### CV-01 — Negação auditada passa só por `AuditsDeniedAbilities`
- **Escopo:** `app/Http/Controllers/Controller.php::authorize()` e os FormRequests com `AuthorizesBeforeValidation`
- **Evidência:** `app/Http/Concerns/AuditsDeniedAbilities.php:18-28`; `app/Platform/Requests/Concerns/AuthorizesBeforeValidation.php:18-23`
- **Visto em:** b1-nucleo (ciclos 1 e 2). FormRequest que valida payload autoriza antes de validar; o controller **não** chama `authorize()` de novo nessas ações. Desde o ciclo 2 a checagem + gravação do `denied` mora em `AuditService::inspectFor()` (`AuditService.php:52-61`), usada pelo trait e pelos `choiceList()` dos Services; `AuditService::denied()` direto é desvio

### CV-02 — Recusa de regra do Service é `DomainException` e vira toast pelo `attempt()`
- **Escopo:** controllers do Núcleo
- **Evidência:** `Controller.php:39-48` (`attempt(Closure $action, string|Closure $success)`: a closure de sucesso recebe o retorno da ação, ex. o aviso do contato em `ContactController.php:57-60`)
- **Visto em:** b1-nucleo. "Já usado", último administrador, WCJ e anonimizado respondem toast de erro, não 403 (D6 do Lucas, 07/10). Ação de escrita que pode lançar `DomainException` fora do `attempt()` é desvio (CR-1-03, resolvido no ciclo 2). Erro de banco (deadlock, unique não tratado) **não** passa pelo `attempt()`: vira 500 (CR-2-01)

### CV-03 — Trava "um só" é `lockForUpdate` dentro da transação, com teste de intercalação
- **Escopo:** `app/Platform/Services/**`
- **Evidência:** `UserService.php:230-241`; `ContactService.php:240-255` (`lockedNotAnonymized`, `lockedForWrite`); `tests/Support/Concorrencia.php`; `UltimoAdminConcorrenciaTest.php`, `ContactAnonimizadoConcorrenciaTest.php`, `ClientRepetidoConcorrenciaTest.php`
- **Visto em:** b1-nucleo (ciclos 1, 2 e 3). Decidir **e escrever** pela linha relida com `lockForUpdate()` (`$locked`), nunca pela instância em memória: os `reactivate` fazem isso desde o CR-2-06; os `deactivate` decidem pelo `$locked` mas ainda gravam pela memória (CR-3-01). Snapshot de `before` também sai da linha travada (CR-3-02). **Ordem das travas importa:** quem trava várias linhas trava sempre na mesma ordem (no `UserService`, os admins ativos antes do alvo); a FK `audit_logs.user_id` e `deactivated_by` pega S na linha do ator e conta como trava (CR-3-03). **O `Concorrencia::intercalarDepoisDe` não reproduz deadlock** (a principal fica parada no PHP) e `bloqueada` aceita 1205 e 1213 juntos: ordem de trava se prova pela sequência de consultas `for update` (ex.: `UserServiceTest::test_desativar_admin_trava_os_administradores_antes_do_alvo`), não pelo teste de corrida (CR-3-04). **Desde o ciclo 4:** os três `deactivate` e `reactivate` decidem, gravam e auditam pelo `$locked` (CR-3-01); o `ensureNotLastActiveAdmin` relê o papel do banco e decide pelo conjunto travado (`UserService.php:255-268`); as escritas do `UserService` usam `DB::transaction(..., self::ATTEMPTS)` (CR-3-03), e o Laravel repete no 1213 **e no 1205**. **Callback repetido tem de ser repetível:** instância em memória salva dentro dele precisa ser restaurada no início do callback (`update`, `UserService.php:102-108`) ou relida; senão o `syncOriginal` da tentativa desfeita faz a repetição não gravar a coluna (CR-4-01, `resendInvite`). E-mail e efeito fora do banco ficam depois do commit. **Desde o ciclo 5:** a repetição é o `RetriesDeadlock` (CV-11), não mais `DB::transaction(..., self::ATTEMPTS)`, e repete só o 1213; ativar e desativar passam pelo `setActiveLocked` (CV-10). Restaurar a memória deixa o callback repetível em relação à instância **de chegada**, não ao banco: se a decisão de gravar depende do "sujo" da instância (`forceFill` de valor que a memória já tem), releia com trava (CR-5-01, `resendInvite`)

### CV-04 — Dado de outro domínio entra no Núcleo por contrato em lote, memorizado por requisição
- **Escopo:** `app/Platform/Contracts/ClientManagement`, `MemoizedClientManagement` (ligação `scoped`)
- **Evidência:** `ClientController.php:107-115`; `MemoizedClientManagement.php:27-30`; `ClientManagementPorRequisicaoTest.php`
- **Visto em:** b1-nucleo. Lista pede em lote (`openProjectsCountByClient`, `managedClientIds`), nunca por linha.

### CV-05 — Linha de lista num Presenter, com o `can` calculado pela Policy
- **Escopo:** `app/Platform/Services/{User,Client,Contact,AuditLog}Presenter.php`
- **Evidência:** `UserPresenter.php:28-34`; `ClientPresenter.php:52-57`; `ContactPresenter.php:28-34`; `AGENTS.md` "Um jeito só" (linha de lista)
- **Visto em:** b1-nucleo (ciclos 1, 2 e 3; Usuários alinhado no CR-1-08, os quatro no Presenter desde o CR-2-12). A tela lê `row.can.*`; ability global (`delete_records`, `reset_passwords`) não decide botão de linha; controller só chama `->row()`. Montar linha no controller é desvio

### CV-06 — Busca com `LIKE` só por `App\Support\Like::contains()`
- **Escopo:** toda busca textual dos Services e do `AuditLogQuery`
- **Evidência:** `app/Support/Like.php:13`; `UserService.php:189-190`, `ClientService.php:172-174`, `ContactService.php:186-188`, `AuditLogQuery.php:167-176`
- **Visto em:** b1-nucleo ciclo 2 (CR-1-18). `"%{$search}%"` à mão é desvio (o `%` e o `_` digitados viram curinga)

### CV-08 — Unique do banco capturado só pelo trait `RefusesDuplicates`
- **Escopo:** gravações dos Services que têm unique no banco
- **Evidência:** `app/Platform/Services/Concerns/RefusesDuplicates.php:25-31`; `UserService::emailTaken()` (confere o nome do índice); `ClientService::duplicateRefusal()` (relê o banco)
- **Visto em:** b1-nucleo ciclo 3 (CR-2-11). A `$refusal` devolve `null` para unique que não reconhece e o trait relança o original. `catch (UniqueConstraintViolationException)` à mão é desvio

### CV-09 — Ação de linha pelo `useRowAction`
- **Escopo:** botões de linha das listagens (Reativar, Desativar, Excluir…)
- **Evidência:** `resources/js/hooks/use-row-action.ts`; `RowActions busy`/`disabled`; `e2e/clique-duplo.spec.ts`
- **Visto em:** b1-nucleo ciclo 3 (CR-2-03, CR-2-07). Guarda por ref (dois cliques no mesmo quadro), outras linhas desabilitadas enquanto uma roda, liberação no `onFinish`. `useState` de `busy` copiado na página é desvio. Nota: a trava liga antes do `onStart`; ouvinte `router.on('before')` que cancele visita a prenderia

### CV-10 — Ativar e desativar só por `Deactivatable::setActiveLocked`
- **Escopo:** `deactivate`/`reactivate` de todo cadastro com `Deactivatable`
- **Evidência:** `app/Platform/Models/Concerns/Deactivatable.php:134-149`; chamadas em `UserService.php:144,156`, `ClientService.php:109,117`, `ContactService.php:104,113`; `AGENTS.md:230`
- **Visto em:** b1-nucleo ciclo 5 (CR-4-05). Relê com `lockForUpdate`, decide e grava pela linha travada e passa o `$locked` ao `$then` (auditoria e efeito próprio). Esqueleto trava-decide-`setActive`-`save`-`log` copiado num Service é desvio. Exceções conhecidas: `ContactService::anonymize` (outro fluxo) e `LimparExemploCommand` (CLI sem ator). Ainda depende da transação de quem chama (CR-5-02)

### CV-11 — Repetição de transação só no deadlock, pelo `RetriesDeadlock`
- **Escopo:** escritas que travam linha de usuário (`UserService`)
- **Evidência:** `app/Platform/Services/Concerns/RetriesDeadlock.php:31-44`; `UserDeadlockRepeteTest.php:76,89,108,123,146`; `AGENTS.md:231`
- **Visto em:** b1-nucleo ciclo 5 (CR-4-04, Fix 6 da rodada 10). Lê `errorInfo[1]` (no `DeadlockException` aninhado, o do `getPrevious()`), repete só o 1213, até 3 tentativas, e relança dentro de transação aberta por fora. `DB::transaction(..., attempts)` do Laravel numa escrita de usuário é desvio (repete o 1205). Client e Contact sem repetição seguem coerentes (ciclo 4)

### CV-07 — Estado de lista pelo `ListBody`; texto de situação pelo `lib/situation.ts`
- **Escopo:** páginas de listagem em `resources/js/pages/admin/**`
- **Evidência:** `components/list-body.tsx`; `lib/situation.ts`; tabela "Componentes e hooks do projeto" no `AGENTS.md`
- **Visto em:** b1-nucleo ciclo 2 (CR-1-09, CR-1-22). Lista nova não reescreve erro/carregando; componente novo entra na tabela do `AGENTS.md` no mesmo commit

---

## Anti-padrões recorrentes

### AP-01 — Check-then-act fora da transação, e a correção que inverte a ordem das travas
- **Ocorrências:** revisão 07/10 (A3 último admin, A4 anonimizado; corrigidos), b1-nucleo ciclo 1 (CR-1-02 CNPJ/e-mail repetido → 500; CR-1-15 `deactivate` nos 3 cadastros e cliente ativo ao criar contato; ambos corrigidos no ciclo 2), b1-nucleo ciclo 2 (**CR-2-01**: a correção do CR-1-15 travou o alvo antes dos admins ativos → deadlock 1213 → 500; CR-2-06: o `reactivate` de Usuário e Cliente ficou de fora; ambos corrigidos no ciclo 3), b1-nucleo ciclo 3 (**CR-3-01**: os `deactivate` decidem pelo `$locked` e gravam e auditam pela instância em memória → auditoria de desativação que não aconteceu; CR-3-02: `before` do `delete` tirado antes da trava → anonimização no meio devolve dado pessoal à auditoria; CR-3-03: escrita em admin-alvo trava alvo → ator pela FK da auditoria, contra a trava dos admins em ordem de índice)
- **Ocorrência do ciclo 4:** **CR-4-01**: a correção do CR-3-03 (`attempts: 3`) repete o `resendInvite` com a instância já salva na tentativa desfeita; o `must_change_password = true` não fica sujo e não é regravado (reproduzido: banco `false`, auditoria `[false, true]`). O teste novo simulava o caminho e não conferia a coluna
- **Ocorrência do ciclo 5:** **CR-5-01**: o `resendInvite` grava pela instância de chegada; se o convidado conclui o primeiro acesso entre o route binding e o `UPDATE`, o `forceFill(must_change_password = true)` não fica sujo e a senha provisória vale sem troca (reproduzido: banco `false`, `changes = null`). Anterior ao ciclo: a correção do CR-4-01 escolheu restaurar a memória em vez de reler com trava
- **Regressões:** 0 de ID; de comportamento, 1 no ciclo 2 (CR-2-01, corrigido) e 1 no ciclo 4 (CR-4-01, vindo do CR-3-03; corrigido no ciclo 5). Nos ciclos 1 a 4 a correção de concorrência abriu um achado novo de concorrência; o ciclo 5 é o primeiro em que não abriu (o CR-5-01 é anterior)
- **Sinal:** `DB::transaction(..., attempts)` cujo callback salva uma instância recebida de fora (`forceFill`/`fill` + `save`) sem restaurá-la; teste de repetição que confere a auditoria e não a linha do banco; o estado é lido do modelo em memória antes do `DB::transaction`; **decide pela linha travada e escreve pela instância em memória** (`setActive()` não faz nada se a memória já está no estado pedido); unique do banco segura o dado mas a exceção vira 500; correção de concorrência que acrescenta um `lockForUpdate()` **antes** de uma trava que já existia; FK para `users` na gravação da auditoria (trava S no ator) depois de escrever o alvo; correção aplicada a uma ação e não à ação espelho (`deactivate` × `reactivate`)
- **Ação sugerida:** começar o review pelos Services procurando `if (...)` antes de `DB::transaction` e por variável em memória usada **depois** de um `lockForUpdate()` (no `setActive`, no `log`, no `snapshot`); em cada correção de concorrência, listar as linhas travadas (inclusive as S das FKs) e a ordem, e comparar com as outras escritas que travam as mesmas linhas; conferir a ação espelho e pedir o teste espelho ("instância carregada antes da mudança"); `DB::transaction(..., attempts: 3)` onde a ordem não puder ser garantida, **com o callback repetível** (todo teste de repetição confere a linha com `fresh()`, coluna por coluna do que o callback grava)

### AP-02 — Mesma coisa em dois ou três lugares entre os cadastros
- **Ocorrências:** revisão 07/10 (A9 `attempt()` em 3 controllers; corrigido), b1-nucleo ciclo 1 (CR-1-09 esqueleto de lista em 4 páginas, 3 padrões de `row()`; CR-1-08; corrigidos no ciclo 2), b1-nucleo ciclo 2 (**CR-2-03**: a trava de clique duplo do CR-1-17 nasceu copiada em 3 telas; CR-2-11: captura do unique em dois formatos; CR-2-12: linha da lista em controller × Presenter e `situation()` duplicado; todos corrigidos no ciclo 3 com `useRowAction`, `RefusesDuplicates` e os Presenters: CV-05, CV-08, CV-09). Ciclo 3: nenhuma ocorrência nova. Ciclo 4: **CR-4-05**: o esqueleto de ativar/desativar (trava, decide, `setActive`, `save`, `log`) segue em seis métodos dos três Services; o CR-3-01 nasceu de uma cópia que ficou para trás. Molde já existente: `Deactivatable::deleteIfUnused(?Closure $then)`. Ciclo 5: CR-4-05 resolvido com `setActiveLocked` (CV-10); detalhe CR-5-02 (o método não abre a própria transação, ao contrário do `deleteIfUnused`)
- **Regressões:** 0
- **Sinal:** B1 nasceu com três CRUDs paralelos; B2 a B7 copiam. A própria correção de um achado de duplicação pode nascer em N cópias, quando é aplicada tela por tela
- **Ação sugerida:** quando a correção toca as três telas, pedir o hook ou o componente no mesmo achado (ex.: `useRowAction()` para `busy`/`busyRow`/`rowPost`); `ListBody` e `deactivation()` já existem (CV-07)

### AP-04 — Decisão registrada aplicada só em parte dos documentos
- **Ocorrências:** b1-nucleo ciclo 2 (CR-2-05: a D14 entrou no AC e na F1, não no N7 nem no escopo; corrigido no ciclo 3), b1-nucleo ciclo 3 (CR-3-05: a D-GER-31 corrigiu F2 a F7 e o escopo, mas não `plano-de-desenvolvimento.md:12,113`, `00-COMECE-AQUI.md:33` e `funcional/README.md:3,47`)
- **Regressões:** 0
- **Sinal:** commit de decisão que lista os documentos tocados; a porta de leitura (`00-COMECE-AQUI.md`) e o plano ficam de fora
- **Ação sugerida:** em commit de decisão, `git grep` do termo antigo ("dono do módulo", o nome da regra) em `docs/` e `.specs/` antes de fechar

### AP-03 — Correção no teste e2e sem correção no produto
- **Ocorrências:** b1-nucleo (A5 → CR-1-10: `SearchInput` cancela escrita em voo; o page object passou a esperar a busca; o produto foi corrigido no ciclo 2, com e2e que força a corrida)
- **Regressões:** 0
- **Sinal:** e2e verde com o mecanismo intacto
- **Ação sugerida:** quando um e2e falha por corrida, perguntar se o usuário real cai na mesma corrida

---

## Decisões arquiteturais

### DA-01 — Excluir o já usado responde toast de erro, não 403
- **Motivo:** decisão do Lucas, D6 da revisão de 07/10
- **Origem:** spec Usuários AC 12, Clientes AC 12, Contatos AC 9 (revisão de 08/10)
- **Implica:** não apontar "deveria ser 403" nos `destroy`

### DA-02 — Listas de escolha (NUC-13) sem rota JSON; o B2 entrega como props
- **Motivo:** D6 do `design.md` ("sem fetch direto: tudo via Inertia")
- **Origem:** design aprovado pelo Cauã; **aceita pelo Lucas em 09/10/2026** como desvio da spec (D13 do design; CR-1-05)
- **Implica:** o P2 AC 3 vale no nível da Policy; `choiceList(User $viewer)` confere `viewChoiceList` dentro do Service por `AuditService::inspectFor()` e audita a negação. O B2 não precisa chamar `authorize('viewChoiceList')` antes: o Service já o faz. Não apontar "falta rota JSON" nem "AC 3 sem rota"

### DA-03 — Rotas em inglês, abilities `view_clients`/`view_contacts`/`view_audit_logs`, `delete_records` e `reset_passwords` por `isAdmin()`
- **Motivo:** D4 e D5 do Lucas, 07/10
- **Origem:** `docs/escopo.md` atualizado em 08/10
- **Implica:** não apontar divergência com os nomes antigos do escopo

### DA-04 — Memória do `ClientManagement` é `scoped` + listener `RequestHandled`
- **Motivo:** Policies perguntam linha a linha; Octane e fila limpam o scoped sozinhos, o listener serve aos testes
- **Origem:** b1-nucleo, revisão do Vitinho (07/10)
- **Implica:** não apontar "singleton vaza entre requisições"

---

### DA-05 — CR-1-01 (reenviar convite derruba sessões) sai em PR próprio, não no PR #5
- **Motivo:** toca autenticação (inegociável 5); decisão do Lucas Scholz em 09/10/2026 (D15 do design)
- **Origem:** ciclo 1, CR-1-01; branch `fix/reenviar-convite-derruba-sessoes` a partir da `main`, PR [WCJ-Tecnologia/wcj-hub#7](https://github.com/WCJ-Tecnologia/wcj-hub/pull/7), aberto e sem merge
- **Implica:** na branch `feat/b1-nucleo` o `UserService::resendInvite` continua sem `terminateOthers` de propósito; o status do CR-1-01 nos ciclos seguintes é "tratado em PR próprio (#7), fora desta branch", não "não resolvido" nem "regressão". Ao integrar os dois PRs, a chamada vai para dentro da transação do `resendInvite` (nota "Atenção ao mergear" do #7). Quem marca o achado de segurança como corrigido é o Lucas, depois do merge e da implantação

### DA-06 — Trava do Service é a mesma da Policy para a WCJ (desativar e excluir)
- **Motivo:** decisão do Lucas Scholz em 09/10/2026 (D14 do design; CR-1-04): a regra "a WCJ não pode ser excluída" fica na Policy e no Service, como a de desativar
- **Origem:** ciclo 1; spec Clientes AC 10 e F1 §04 v1.2
- **Implica:** não apontar como "trava que ninguém pediu"

### DA-07 — Decisões pendentes do Lucas (não são achados novos)
- **Origem:** ciclo 1: CR-1-11 (prazo da senha provisória: a spec cita regra que o template não tem; prazo pede coluna ou leitura da auditoria), CR-1-12 (`min="0.25"` da carga diária na tela × `gt:0` no servidor), CR-1-23 (índice da auditoria por `created_at`: migração). Ciclo 2: CR-2-09 ("Reenviar convite" para usuário desativado × F1 §04) e a parte de `abilities` do CR-2-08 (`delete_records` e `reset_passwords` sem leitor no front; mexer em `abilities` exige aprovação)
- **Implica:** reverificar o status ("❌ Não resolvido, aguarda decisão"), sem reabrir como achado novo nem subir a criticidade; D16 do design. Ciclos 3, 4 e 5: os cinco seguem sem decisão. Se o Lucas aceitar o comportamento atual do CR-2-09, ele vira `FP-`. O CR-4-04 (repetir só o 1213 ou baixar `innodb_lock_wait_timeout`) saiu desta lista no ciclo 5: "repetir só o 1213" foi escolhido no `596f37e`, autor Lucas Scholz ("decisão de quem integra"); não reabrir pedindo timeout de sessão

### DA-08 — Trilho completo do B1: correção de review também passa pelo Verifier
- **Motivo:** `AGENTS.md` ("Trilhos": Verifier em agente separado com PASS e mutação em B1, Policy e exclusão) e `spec.md:262`
- **Origem:** b1-nucleo ciclo 2 (CR-2-02): 22 commits de correção, incluindo Policy dentro do Service e trava de exclusão, sem rodada nova no `validation.md`
- **Implica:** no ciclo N > 1 de bloco de trilho completo, conferir se o `validation.md` tem rodada posterior às correções; se não, é achado (Médio), não detalhe. Ciclo 3: a Rodada 9 (20/20 mutantes) atendeu ao CR-2-02; conferir por amostragem as linhas de evidência e não pedir de novo o que ela provou. As correções do ciclo 3 (CR-3-01 mexe em desativação) pedem rodada nova. Ciclo 4: não houve rodada sobre `e9b2731..6dec4f4` → CR-4-02 (Médio). Referência: o `validation.md` mais recente cobre o intervalo citado na linha `Result` do topo; conferir o intervalo, não só o PASS. Ciclo 5: rodadas 10 (`fef3766..7b96b12`, FAIL Minor só pelo mutante g2) e 11 (`b0a8d6f..7c228a8`, PASS) atenderam ao CR-4-02. A correção do CR-5-01 mexe em senha e pede rodada nova focada (mutante "emite pela instância em memória")

## Falsos positivos

### FP-01 — Gestor cria cliente em Administração → Clientes (não só pelo formulário do projeto)
- **Apontado em:** revisão 07/10 (D1)
- **Por que não é problema:** aprovado pelo Lucas em 07/10; F1 §04 e §09 ganharam a frase
- **Reabrir apenas se:** a F1 mudar

### FP-02 — Lista geral de contatos vazia com "Ir para Clientes" apontando ação que o gestor não tem
- **Apontado em:** revisão 07/10 (D10)
- **Por que não é problema:** aceito até o B2 ligar a `ProjectClientManagement`
- **Reabrir apenas se:** o B2 entrar e o botão continuar sem sentido para o gestor

### FP-03 — Aviso "Já existe um contato com este e-mail" revela existência em cliente que o gestor não gere
- **Apontado em:** b1-nucleo ciclo 1 (registrado sem achado)
- **Por que não é problema:** decisão consciente do Lucas (D2): sem nome nem cliente para não-admin
- **Reabrir apenas se:** a regra N8/F1 §09 mudar

### FP-04 — Dado pessoal na linha `deleted` do contato excluído
- **Apontado em:** revisão 07/10 (D11)
- **Por que não é problema:** registrado como limitação na `necessidade.md` (N12 cobre só o anonimizado)
- **Reabrir apenas se:** um titular pedir anonimização de contato já excluído

### FP-05 — Filtro "Situação" é só a chave "Mostrar desativados"
- **Apontado em:** b1-nucleo ciclo 1 (registrado sem achado)
- **Por que não é problema:** gabarito do template; a F1 §03 cita a chave
- **Reabrir apenas se:** alguém precisar listar só desativados ou só anonimizados

### Registrado sem achado no ciclo 3 (não é FP: o Lucas não foi consultado)
- Evidência de rodada antiga do `validation.md` (ex.: U3 da rodada 3 citando `isReset`) não se reescreve: o relatório é por rodadas e a mais nova é a vigente. Não apontar.
- Commit de governança do próprio Lucas dentro do PR do B1 (`f1bebc7`, D-GER-31): não contradiz código; só vira achado se um documento contradisser a decisão (aí é o AP-04).
- Push e CI do PR são do Lucas: registrar no resumo, não como achado de código (pedido do coordenador do ciclo 3).

### Registrado sem achado no ciclo 4 (não é FP: o Lucas não foi consultado)
- `UserService::update` e `ClientService::update` tiram o `before` da instância sem travar o alvo (o `ContactService::update` trava). Só edição simultânea do mesmo registro, sem dado anonimizável: pedir trava seria "trava que ninguém pediu". Não apontar sem caso de dado pessoal.
- Client e Contact sem `attempts`: coerente (travam a linha de negócio e depois o ator em S; não há ciclo). `invite` sem `attempts`: coerente. Não apontar "inconsistência".
- Comentários que prometem mais que o código (`UserService.php:258`, `Concorrencia.php:57`) e o `AGENTS.md` sem a convenção da repetição: registrar, não achado (documento não contradiz decisão).
- Asserção `assertSame(1205, $corrida->codigo)` do `UltimoAdminConcorrenciaTest` é fraca (1213 não se forma no harness), e o comentário diz isso: não reabrir o CR-3-04.

### Registrado sem achado no ciclo 5 (não é FP: o Lucas não foi consultado)
- `AGENTS.md:78` ("Verifier rodadas 9 e 10") e `spec.md:262` (rodadas 8 e 9) não citam a rodada 11, a vigente: incompleto, não contradiz. `AGENTS.md:231` "até 3 vezes" são 3 tentativas.
- `ContactService::reactivate` trava a mesma linha duas vezes (`lockedNotAnonymized` + `setActiveLocked`); o `bool` do `setActiveLocked` não é lido: sem efeito.
- 1205 sobe como 500 na primeira tentativa: coerente com o CV-02. 1213 no `COMMIT` não repete: não é caminho realista.
- O `$before` do `resendInvite` vem da memória restaurada: some com a correção do CR-5-01; não apontar à parte.
