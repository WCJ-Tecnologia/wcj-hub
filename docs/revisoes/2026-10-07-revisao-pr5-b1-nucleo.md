# Revisão de 07/10/2026: PR #5, B1 Núcleo (Cauã)

Revisão do PR [WCJ-Tecnologia/wcj-hub#5](https://github.com/WCJ-Tecnologia/wcj-hub/pull/5) (`feat/b1-nucleo`, HEAD `bf82585`), feita por Claude (Fable 5.1) a pedido de Lucas Scholz, em três frentes: conformidade com o funcional `docs/funcional/f1-nucleo.md` (agente Opus, matriz item a item), correção do código (revisão linha a linha do backend) e a escada de verificação rodada localmente num worktree limpo (agente Sonnet). CI do PR: as 4 checagens verdes.

**Situação:** achados A1 a A9 aguardam correção do Cauã; as divergências D1 a D11 foram DECIDIDAS por Lucas Scholz em 07/10/2026 (tabela "Decisões" no fim). Nada foi alterado na branch por esta revisão.

## Veredito

O B1 está bem construído: 287 testes PHP em MySQL, Policies por papel, auditoria com negação, anonimização em transação, CNPJ alfanumérico com o exemplo oficial da Receita. **Não deve ser mergeado ainda** por dois achados de média gravidade que o PR não lista (A1 e A2) e por um e2e que falha fora do CI (A5). O resto é decisão de produto ou ajuste pequeno.

## Escada de verificação (worktree limpo, 07/10)

| Passo | Resultado |
|---|---|
| `composer lint` | passou |
| `php artisan test` (MySQL 8.4) | **287 passaram**, 2.447 asserções, 0 pulados, 82 s |
| `npm run typecheck`, `npm run lint`, `npm run build` | passaram |
| `npm run test:guard` | 1.053 passaram |
| `npm run specs:check` | 0 problemas, 1 aviso (T35 sem teste; esperado) |
| `npx playwright test` (51) | **50 passaram, 1 falhou** (`nucleo.spec.ts:11`, o `@critical`); reproduzido 2 vezes em banco recriado; passa isolado |

## Achados, do mais grave ao menos grave

### A1. FormRequest valida antes da Policy: vaza e-mails de usuários e a negação some da auditoria (média, não está no PR)

`StoreUserRequest`, `StoreClientRequest`, `StoreContactRequest` e `AnonymizeContactRequest` têm `authorize(): true`; a Policy só é consultada no corpo do controller, depois que o Laravel já validou o FormRequest. Consequências:

- Um Desenvolvedor ou Gestor faz `POST /admin/users` com um e-mail e campos inválidos: recebe 422 com "Este e-mail já está em uso." quando o e-mail existe (`StoreUserRequest.php:38`, `unique:users,email`) e outro erro quando não existe. Enumera os logins, que o F1 §09 não deixa o gestor ver.
- Qualquer tentativa com payload inválido não vira linha `denied` (G3: "toda tentativa negada"), porque `Controller::authorize()` nunca roda.
- Os testes "403 e nada muda" usam sempre payload válido, então o caso está sem cobertura.

**Correção:** autorizar antes de validar. O `authorize()` do FormRequest chama `Gate::inspect` e, se negado, `AuditService::denied()` (mesmo caminho do Controller base), ou middleware `can:` nas rotas. Teste: não-admin com payload inválido recebe 403 e grava `denied`.

### A2. A WCJ perde a proteção do N7 se alguém editar o CNPJ ou o país dela (média, o autor apontou)

`Client::isWcj()` compara `country` + `document` (`Client.php:35-38`), e `ClientService::update` deixa os dois campos mudarem. Depois disso Desativar e Excluir liberam. Com a `ProjectClientManagement` real (B2), o gestor de projeto interno gere a WCJ e também edita.

**Correção:** em `ClientService::update`, recusar com `DomainException` a troca de `country`/`document` quando `$client->isWcj()`, com teste. Alternativa: identificar a WCJ pelo id semeado.

### A3. Trava do último administrador é check-then-act fora da transação (média-baixa)

`UserService::ensureNotLastActiveAdmin()` (linha 207) roda antes de `DB::transaction` e sem lock, nas três ações (linhas 88, 120, 146). Dois admins que se desativam, rebaixam ou excluem ao mesmo tempo deixam o sistema sem administrador. O `AGENTS.md` pede teste de concorrência onde há "um só"; `tests/Support/Concorrencia.php` existe e não é usado aqui.

**Correção:** a checagem dentro da transação com `lockForUpdate()` nas linhas de admin ativo, e um teste com o helper de concorrência.

### A4. Checagem de anonimizado sem lock pode regravar dado pessoal (baixa)

`ContactService::update()` e `reactivate()` chamam `ensureNotAnonymized` antes de abrir a transação (linhas 82 e 109); `anonymize()` trava a linha (144). Uma edição que passou na checagem e esperou o lock grava nome, e-mail e telefone de novo, inclusive em `audit_logs.changes`, depois de o contato estar anonimizado (N12).

**Correção:** checar dentro da transação, sobre a linha travada, como `anonymize()` faz.

### A5. O e2e `@critical` falha por corrida com a busca da lista (teste, não produto)

`ClientsPage.edit()` (`e2e/pages/clients.page.ts:44-50`) digita no campo Buscar, clica em Editar, preenche e envia em menos de 300 ms. O `SearchInput` do template tem debounce de 300 ms e dispara `router.get(...?search=)` logo depois do `PUT`; o Inertia cancela a visita em andamento e o `PUT` morre com status -1 (visto no trace: PUT às 36.720, GET às 36.757). O diálogo fica aberto e o teste estoura. No CI passa porque a máquina é mais lenta e o debounce dispara antes do clique.

**Correção:** `find()` espera a navegação da busca (`await expect(page).toHaveURL(/search=/)`) antes de devolver. Vale para `openSheet()` e `deleteRow()` também. Nota de produto: um usuário que digita na busca e clica numa ação em menos de 300 ms perde a gravação em silêncio; é raro, mas é o mesmo mecanismo.

### A6. `AuditActionEnum::forAbility()` dá 500 para ability sem mapeamento (baixa, o autor apontou)

Linha 50: `throw InvalidArgumentException`. Toda Policy nova do B2 em diante que esquecer o mapeamento responde 500 em vez de 403, e sem linha `denied`. **Correção:** fallback (gravar o nome cru da ability ou uma ação genérica) e manter o `match` só como rótulo.

### A7. `openProjectsCount()` e `manages()` por linha viram N+1 no B2 (baixa, desempenho)

`ClientController::row()` chama `openProjectsCount($client)` para cada linha (linha 129) e as Policies chamam `manages()` por linha (4 a 5 `can()` por cliente e por contato). Com a implementação real do contrato, 20 linhas = 20 consultas em `projects`, para um texto de confirmação que talvez nunca abra. **Correção:** `managedClientIds($user)` uma vez por requisição e a contagem só ao abrir o diálogo (ou método em lote no contrato).

### A8. `from` inválido na auditoria derruba o período padrão (baixa)

`AuditLogQuery::filters()` linha 66: `from=07/10/2026` (formato BR) é rejeitado pelo Validator, mas `array_key_exists('from', $input)` é verdadeiro e o filtro vira nulo, listando o histórico inteiro. **Correção:** tratar como "sem período" só quando `$input['from'] === ''`.

### A9. `attempt()` copiado idêntico em três controllers (limpeza)

`UserController:105`, `ClientController:106`, `ContactController:101`. Subir para `App\Http\Controllers\Controller`, ao lado do `authorize()`.

## Conformidade com o F1

Matriz completa conferida item a item (§02 campos, §03 listas, §04 ações com texto caractere a caractere, §05 filtros, §07 e-mail, §09 permissões, regras N1 a N12, G2 e G3). Resultado: conforme na grande maioria. Divergências que precisam da decisão do Lucas Scholz:

| # | Item | O que o F1 diz | O que o código faz | Decisão |
|---|---|---|---|---|
| D1 | Gestor cria cliente na lista | §04 e §09: "pelo botão Novo cliente do formulário do projeto" | `ClientPolicy::create` libera o gestor e `clients/index.tsx:101` mostra o botão em Administração → Clientes | esconder o botão para o gestor, ou aprovar a porta extra |
| D2 | Aviso de e-mail repetido cruza clientes | §07: "Já existe Fulano (Cliente X) com este e-mail" | mostra ao gestor nome e cliente de contato que não é dele (`ContactService.php:247-255`); conflita com §09 | para não-admin, "Já existe um contato com este e-mail"? |
| D3 | Telefone | §02: "texto, até 20" | servidor guarda só dígitos (some o "+"), tela mascara 10–11 dígitos como Brasil | guardar como texto? há cliente na Holanda |
| D4 | Caminhos das rotas | `escopo.md` B1: `/admin/usuarios`, `/clientes`, `/contatos`, `/auditoria` | `/admin/users`, `/clients`, `/contacts`, `/audit-logs` (D2 do PR) | aprovar explicitamente |
| D5 | Abilities do front | `escopo.md`: `manage_clients`, `manage_contacts`, `view_audit` | `view_clients`, `view_contacts`, `view_audit_logs`; `delete_records` e `reset_passwords` seguem `isAdmin()` fixo | AGENTS exige aprovação de mudança em abilities |
| D6 | Excluir registro já usado | spec pede 403; F1 não define | toast de erro com a mensagem da spec (SPEC_DEVIATION nos 3 `destroy`) | concordo com o toast; corrigir a spec |
| D7 | 3º método do contrato `openProjectsCount` e textos para N = 0 e N = 1 | F1 §04 só define "tem N projetos abertos" | método acrescentado; textos inventados | aprovar o método; definir os textos |
| D8 | Aviso de e-mail só entre contatos ativos | §02: "se já existe contato" | `->active()` em `duplicateWarning` | premissa aceitável |
| D9 | Carga diária com `step 0.25` e papel padrão Desenvolvedor no convite | não pede | `user-form-dialog.tsx:87-89`, `users/index.tsx:28`; o navegador recusa 7,3 | tirar o `step` |
| D10 | Lista geral de contatos vazia | §03: "frase e o botão de criar" | botão "Ir para Clientes" (`contacts/index.tsx:93-101`); para o gestor aponta ação que ele não tem até o B2 | aceitável até o B2 |
| D11 | Contato excluído deixa dado pessoal em `changes` da linha `deleted` | N12 fala só do anonimizado | se o titular pedir depois, a ficha sumiu e não há caminho | registrar como limitação ou anonimizar antes de excluir |

Fora do B1 e anterior ao diff (registrar, não bloquear): o painel do template mostra ao gestor a contagem de usuários (F1 §09); o "esqueci a senha" do template contradiz "único e-mail do Núcleo" (§07); busca da auditoria por chave de card é do B3.

Textos de confirmação conferidos e iguais ao F1: "Fulano perde o acesso imediatamente. Continuar?", "As conexões de IA de Fulano serão desconectadas. Continuar?", "Excluir de verdade. Não dá para desfazer.", "Isto atende a um pedido do titular (LGPD) e não pode ser desfeito. Digite ANONIMIZAR para confirmar.", "Já existe o cliente X com este CNPJ". Os três primeiros não têm teste que confira o texto.

## Sem cobertura de teste

- Negação com payload inválido (A1).
- Edição do CNPJ ou país da WCJ (A2).
- Corrida do último administrador (A3).
- Textos das confirmações de desativar, desconectar IA e excluir.
- Limites de 120 e 160 caracteres do usuário; estado vazio sem filtro de Usuários e Contatos.

## Leitura das "Decisões para você vetar" do PR

1. `openProjectsCount`: necessário para o texto do F1; aprovar, com o N+1 de A7 resolvido.
2. D3 (desativar e reativar na linha): concordo.
3. ADR-004 (seções em vez de abas): aceitável.
4. D2 (caminhos em inglês): pede aprovação explícita, porque o escopo manda em rota (D4 acima).

## Decisões de Lucas Scholz (07/10/2026)

| # | Decisão | O que muda no PR |
|---|---|---|
| D1 | **Aprovado:** o gestor cria cliente também em Administração → Clientes | Nada no código. O F1 §04 e §09 ganham a frase "e em Administração → Clientes" na linha do gestor |
| D2 | Para quem não é Administrador, o aviso vira "Já existe um contato com este e-mail"; o Administrador segue com "Já existe Fulano (Cliente X) com este e-mail" | `ContactService::duplicateWarning` recebe o `$viewer` e escolhe o texto; teste para os dois papéis |
| D3 | Telefone é **texto**, até 20, como o F1 diz | Tirar `normalizeDigits(['phone'])` do `StoreContactRequest`; gravar o que a pessoa digitou (`trim`); na tela, sem máscara forçada de Brasil (o "+" fica). A busca por telefone continua aceitando dígitos |
| D4 | Rotas em inglês aprovadas (`/admin/users`, `/clients`, `/contacts`, `/audit-logs`) | Nada no código; `escopo.md` B1 passa a citar os caminhos em inglês |
| D5 | Abilities `view_clients`, `view_contacts`, `view_audit_logs` aprovadas; `delete_records` e `reset_passwords` seguem `isAdmin()` | Nada no código; `escopo.md` §4 passa a usar esses nomes |
| D6 | Toast de erro para "já usado", não 403 | Nada no código; corrigir Usuários AC 12, Clientes AC 12 e Contatos AC 9 na `spec.md` e tirar os `SPEC_DEVIATION` |
| D7 | 3º método `openProjectsCount(Client)` aprovado, com os textos para N = 0 ("O cliente X sai das listas de escolha. Desativar?") e N = 1 como estão | Resolver o N+1 (A7) antes do B2 ligar a implementação real |
| D8 | Aviso de e-mail só entre contatos ativos: aceito | Nada |
| D9 | Tirar o `step 0.25` da carga diária (aceita 7,3) | `user-form-dialog.tsx:87-89`: `step="0.01"` ou `step="any"`, `min` continua |
| D10 | Lista geral de contatos vazia com "Ir para Clientes": aceito até o B2 | Nada |
| D11 | Dado pessoal no `changes` da linha `deleted` do contato: registrado como limitação | Nada no código; uma linha na `necessidade.md` (N12) ou no `AGENTS.md` dizendo que contato excluído não tem caminho de anonimização |

## Para retomar

Worktree da revisão: `~/Documents/Projetos/WCJ/wcj-hub-pr5` (branch `origin/feat/b1-nucleo`, com `vendor`, `node_modules` e `.env` prontos). Saídas das suítes em `scratchpad/phpunit.txt` e `e2e.txt` da sessão. Este arquivo não está commitado.
