# Padrões do Projeto — aprendidos via code review

> Mantido pela skill `wcj-code-review`. Editável à mão.
> Atualizado ao final de cada ciclo de review.

**Última atualização:** 2026-10-09 · **Reviews acumulados:** 2 (b1-nucleo ciclos 1 e 2; a revisão de 07/10 em `docs/revisoes/` é anterior à skill)

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
- **Visto em:** b1-nucleo (ciclos 1 e 2). Desde o ciclo 2 os `deactivate` decidem pela linha relida com `lockForUpdate()`, não pela instância em memória. **Ordem das travas importa:** quem trava várias linhas trava sempre na mesma ordem (no `UserService`, os admins ativos antes do alvo); o `intercalarDepoisDe` só prova a ordem se a intercalação cair entre a 1ª e a 2ª trava (CR-2-01)

### CV-04 — Dado de outro domínio entra no Núcleo por contrato em lote, memorizado por requisição
- **Escopo:** `app/Platform/Contracts/ClientManagement`, `MemoizedClientManagement` (ligação `scoped`)
- **Evidência:** `ClientController.php:107-115`; `MemoizedClientManagement.php:27-30`; `ClientManagementPorRequisicaoTest.php`
- **Visto em:** b1-nucleo. Lista pede em lote (`openProjectsCountByClient`, `managedClientIds`), nunca por linha.

### CV-05 — `can` por linha vem do servidor, calculado pela Policy
- **Escopo:** linhas das listas (`UserController::row`, `ClientController::row`, `ContactPresenter::row`)
- **Evidência:** `UserController.php:116-122`; `ClientController.php:137-142`; `ContactPresenter.php:28-34`
- **Visto em:** b1-nucleo (ciclos 1 e 2; Usuários alinhado no CR-1-08). A tela lê `row.can.*`; ability global (`delete_records`, `reset_passwords`) não decide botão de linha. Onde a linha mora ainda está em dois lugares (controller × Presenter, CR-2-12)

### CV-06 — Busca com `LIKE` só por `App\Support\Like::contains()`
- **Escopo:** toda busca textual dos Services e do `AuditLogQuery`
- **Evidência:** `app/Support/Like.php:13`; `UserService.php:189-190`, `ClientService.php:172-174`, `ContactService.php:186-188`, `AuditLogQuery.php:167-176`
- **Visto em:** b1-nucleo ciclo 2 (CR-1-18). `"%{$search}%"` à mão é desvio (o `%` e o `_` digitados viram curinga)

### CV-07 — Estado de lista pelo `ListBody`; texto de situação pelo `lib/situation.ts`
- **Escopo:** páginas de listagem em `resources/js/pages/admin/**`
- **Evidência:** `components/list-body.tsx`; `lib/situation.ts`; tabela "Componentes e hooks do projeto" no `AGENTS.md`
- **Visto em:** b1-nucleo ciclo 2 (CR-1-09, CR-1-22). Lista nova não reescreve erro/carregando; componente novo entra na tabela do `AGENTS.md` no mesmo commit

---

## Anti-padrões recorrentes

### AP-01 — Check-then-act fora da transação, e a correção que inverte a ordem das travas
- **Ocorrências:** revisão 07/10 (A3 último admin, A4 anonimizado; corrigidos), b1-nucleo ciclo 1 (CR-1-02 CNPJ/e-mail repetido → 500; CR-1-15 `deactivate` nos 3 cadastros e cliente ativo ao criar contato; ambos corrigidos no ciclo 2), b1-nucleo ciclo 2 (**CR-2-01**: a correção do CR-1-15 travou o alvo antes dos admins ativos → deadlock 1213 → 500; CR-2-06: o `reactivate` de Usuário e Cliente, espelho do que o CR-1-15 corrigiu, ficou de fora)
- **Regressões:** 0 de ID; 1 de comportamento (o A3 respondia toast na corrida de dois admins; com o CR-2-01 uma das duas responde 500)
- **Sinal:** o estado é lido do modelo em memória antes do `DB::transaction`; unique do banco segura o dado mas a exceção vira 500; correção de concorrência que acrescenta um `lockForUpdate()` **antes** de uma trava que já existia; correção aplicada a uma ação e não à ação espelho (`deactivate` × `reactivate`)
- **Ação sugerida:** começar o review pelos Services procurando `if (...)` antes de `DB::transaction`; em cada correção de concorrência, listar as linhas travadas e a ordem, e comparar com as outras escritas que travam as mesmas linhas; conferir a ação espelho; `UniqueConstraintViolationException` tratada como no `ClientService::duplicateRefusal()` (relança o que não reconhece)

### AP-02 — Mesma coisa em dois ou três lugares entre os cadastros
- **Ocorrências:** revisão 07/10 (A9 `attempt()` em 3 controllers; corrigido), b1-nucleo ciclo 1 (CR-1-09 esqueleto de lista em 4 páginas, 3 padrões de `row()`; CR-1-08; corrigidos no ciclo 2), b1-nucleo ciclo 2 (**CR-2-03**: a trava de clique duplo do CR-1-17 nasceu copiada em 3 telas; CR-2-11: captura do unique em dois formatos; CR-2-12: linha da lista em controller × Presenter e `situation()` duplicado)
- **Regressões:** 0
- **Sinal:** B1 nasceu com três CRUDs paralelos; B2 a B7 copiam. A própria correção de um achado de duplicação pode nascer em N cópias, quando é aplicada tela por tela
- **Ação sugerida:** quando a correção toca as três telas, pedir o hook ou o componente no mesmo achado (ex.: `useRowAction()` para `busy`/`busyRow`/`rowPost`); `ListBody` e `deactivation()` já existem (CV-07)

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
- **Implica:** reverificar o status ("❌ Não resolvido, aguarda decisão"), sem reabrir como achado novo nem subir a criticidade; D16 do design. Se o Lucas aceitar o comportamento atual do CR-2-09, ele vira `FP-`

### DA-08 — Trilho completo do B1: correção de review também passa pelo Verifier
- **Motivo:** `AGENTS.md` ("Trilhos": Verifier em agente separado com PASS e mutação em B1, Policy e exclusão) e `spec.md:262`
- **Origem:** b1-nucleo ciclo 2 (CR-2-02): 22 commits de correção, incluindo Policy dentro do Service e trava de exclusão, sem rodada nova no `validation.md`
- **Implica:** no ciclo N > 1 de bloco de trilho completo, conferir se o `validation.md` tem rodada posterior às correções; se não, é achado (Médio), não detalhe

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
