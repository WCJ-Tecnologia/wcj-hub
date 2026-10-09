# Padrões do Projeto — aprendidos via code review

> Mantido pela skill `wcj-code-review`. Editável à mão.
> Atualizado ao final de cada ciclo de review.

**Última atualização:** 2026-10-08 · **Reviews acumulados:** 1 (b1-nucleo ciclo 1; a revisão de 07/10 em `docs/revisoes/` é anterior à skill)

---

## Convenções confirmadas

### CV-01 — Negação auditada passa só por `AuditsDeniedAbilities`
- **Escopo:** `app/Http/Controllers/Controller.php::authorize()` e os FormRequests com `AuthorizesBeforeValidation`
- **Evidência:** `app/Http/Concerns/AuditsDeniedAbilities.php:18-28`; `app/Platform/Requests/Concerns/AuthorizesBeforeValidation.php:18-23`
- **Visto em:** b1-nucleo (ciclo 1). FormRequest que valida payload autoriza antes de validar; o controller **não** chama `authorize()` de novo nessas ações.

### CV-02 — Recusa de regra do Service é `DomainException` e vira toast pelo `attempt()`
- **Escopo:** controllers do Núcleo
- **Evidência:** `Controller.php:36-45`; `UserService.php:156`, `ClientService.php:86,101,136`
- **Visto em:** b1-nucleo. "Já usado", último administrador, WCJ e anonimizado respondem toast de erro, não 403 (D6 do Lucas, 07/10). Ação de escrita fora do `attempt()` é desvio (CR-1-03).

### CV-03 — Trava "um só" é `lockForUpdate` dentro da transação, com teste de intercalação
- **Escopo:** `app/Platform/Services/**`
- **Evidência:** `UserService.php:213-224`; `ContactService.php:265-274`; `tests/Support/Concorrencia.php`; `UltimoAdminConcorrenciaTest.php`, `ContactAnonimizadoConcorrenciaTest.php`
- **Visto em:** b1-nucleo.

### CV-04 — Dado de outro domínio entra no Núcleo por contrato em lote, memorizado por requisição
- **Escopo:** `app/Platform/Contracts/ClientManagement`, `MemoizedClientManagement` (ligação `scoped`)
- **Evidência:** `ClientController.php:107-115`; `MemoizedClientManagement.php:27-30`; `ClientManagementPorRequisicaoTest.php`
- **Visto em:** b1-nucleo. Lista pede em lote (`openProjectsCountByClient`, `managedClientIds`), nunca por linha.

### CV-05 — `can` por linha vem do servidor, calculado pela Policy
- **Escopo:** linhas das listas (`ClientController::row`, `ContactService::row`)
- **Evidência:** `ClientController.php:137-142`; `ContactService.php:228-234`
- **Visto em:** b1-nucleo. Usuários ainda usa ability global + regra copiada no front (CR-1-08): alinhar.

---

## Anti-padrões recorrentes

### AP-01 — Check-then-act fora da transação
- **Ocorrências:** revisão 07/10 (A3 último admin, A4 anonimizado; corrigidos), b1-nucleo ciclo 1 (CR-1-02 CNPJ/e-mail repetido → 500; CR-1-15 `deactivate` nos 3 cadastros e cliente ativo ao criar contato)
- **Regressões:** 0
- **Sinal:** o estado é lido do modelo em memória antes do `DB::transaction`; unique do banco segura o dado mas a exceção vira 500
- **Ação sugerida:** começar o review pelos Services procurando `if (...)` antes de `DB::transaction`; `UniqueConstraintViolationException` tratada como em `UserService::invite`

### AP-02 — Mesma coisa em dois ou três lugares entre os cadastros
- **Ocorrências:** revisão 07/10 (A9 `attempt()` em 3 controllers; corrigido), b1-nucleo ciclo 1 (CR-1-09 esqueleto de lista em 4 páginas, 3 padrões de `row()`; CR-1-08)
- **Regressões:** 0
- **Sinal:** B1 nasceu com três CRUDs paralelos; B2 a B7 copiam
- **Ação sugerida:** antes do B2, extrair `ListBody` e o helper de "Desativado em … por …"

### AP-03 — Correção no teste e2e sem correção no produto
- **Ocorrências:** b1-nucleo (A5 → CR-1-10: `SearchInput` cancela escrita em voo; o page object passou a esperar a busca)
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
- **Origem:** design aprovado pelo Cauã; **pendente a palavra do Lucas** (CR-1-05)
- **Implica:** se aceita, o AC 3 passa a valer no nível da Policy; o B2 precisa chamar `authorize('viewChoiceList')` antes de `choiceList()`

### DA-03 — Rotas em inglês, abilities `view_clients`/`view_contacts`/`view_audit_logs`, `delete_records` e `reset_passwords` por `isAdmin()`
- **Motivo:** D4 e D5 do Lucas, 07/10
- **Origem:** `docs/escopo.md` atualizado em 08/10
- **Implica:** não apontar divergência com os nomes antigos do escopo

### DA-04 — Memória do `ClientManagement` é `scoped` + listener `RequestHandled`
- **Motivo:** Policies perguntam linha a linha; Octane e fila limpam o scoped sozinhos, o listener serve aos testes
- **Origem:** b1-nucleo, revisão do Vitinho (07/10)
- **Implica:** não apontar "singleton vaza entre requisições"

---

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
