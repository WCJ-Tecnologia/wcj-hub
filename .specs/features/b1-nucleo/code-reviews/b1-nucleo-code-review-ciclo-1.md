# Code Review — `feat/b1-nucleo` · Ciclo 1

**Data:** 2026-10-08
**História:** b1-nucleo (PR [WCJ-Tecnologia/wcj-hub#5](https://github.com/WCJ-Tecnologia/wcj-hub/pull/5), autor Cauã Soares)
**Branch:** `feat/b1-nucleo` (HEAD `2b076bf`) · **Base:** `main` (branch do ambiente, `AGENTS.md` → Ambientes)
**Spec:** `.specs/features/b1-nucleo/`
**Revisor:** Claude Code (skill `wcj-code-review`, sessão de revisão com contexto zerado; três camadas em subagentes Opus sem contexto: segurança, requisitos, qualidade/regressão; escada rodada por subagente Sonnet)
**Ciclos anteriores:** nenhum desta skill. Existe a revisão de 07/10/2026 (`docs/revisoes/2026-10-07-revisao-pr5-b1-nucleo.md`, HEAD `bf82585`, achados A1–A9 e decisões D1–D11), reverificada abaixo.
**Escopo deste ciclo:** a branch inteira contra `main` (123 arquivos, +13 771 / −606), incluindo os 13 commits de correção posteriores à revisão de 07/10. Working tree limpo (só o arquivo da revisão de 07/10, não versionado).

**Escada de verificação (worktree `wcj-hub-pr5`, 08/10, MySQL 8 da 3306):** `composer lint` ✅ · `php artisan test` ✅ 322 testes, 2 761 asserções, 0 falhas · `npm run typecheck` ✅ · `npm run lint` ✅ · `npm run build` ✅ · `npm run specs:check` ✅ (0 problemas; avisos T35 e T50 "Tests: none") · `npm run test:guard` ✅ 1 053 · Playwright ✅ 56/56. CI do PR: 4 checagens verdes (PHP, JS, E2E, Secrets scan).

---

## Status dos achados anteriores (revisão de 07/10, pré-ciclo)

Reverificados **no código atual**, não pelo relatório nem pelos commits.

| ID (07/10) | Criticidade | Título | Status | Evidência |
|---|---|---|---|---|
| A1 | Média | FormRequest valida antes da Policy: vaza e-mail e some a negação | ✅ Resolvido | trait `AuthorizesBeforeValidation` (`app/Platform/Requests/Concerns/AuthorizesBeforeValidation.php:18-23`) nos 4 FormRequests; `abilityAndArguments()` em cada um; teste `tests/Feature/Nucleo/AutorizaAntesDeValidarTest.php:42-157` (403, nenhum erro de validação, uma linha `denied`) |
| A2 | Média | WCJ perde a proteção do N7 se editar CNPJ ou país | ✅ Resolvido | `ClientService.php:83-87` (`DomainException` `WCJ_IDENTITY_LOCKED`); `ClientServiceTest.php:291-297`, `ClientControllerTest.php:211` |
| A3 | Média-baixa | Trava do último administrador fora da transação | ✅ Resolvido | `UserService.php:213-224`: dentro de `DB::transaction` (l. 87, 120, 147) com `lockForUpdate()` nos admins ativos; `UltimoAdminConcorrenciaTest.php:36` |
| A4 | Baixa | Checagem de anonimizado sem lock regrava dado pessoal | ✅ Resolvido | `ContactService::lockedNotAnonymized()` (l. 265-274) chamado dentro da transação em `update` (l. 86-87) e `reactivate` (l. 113-114); `ContactAnonimizadoConcorrenciaTest.php:63,87` |
| A5 | Teste | e2e `@critical` falha por corrida com a busca | ✅ Resolvido (no teste) | `e2e/pages/admin.page.ts:21-35`: `search()` espera a resposta da busca e a URL; `clients.page.ts:24-25`. 56/56 locais e no CI. **O mecanismo no produto continua** (ver CR-1-10) |
| A6 | Baixa | `forAbility()` dá 500 para ability sem mapeamento | ✅ Resolvido | `AuditActionEnum::forAbility()` devolve `null` no `default`; `AuditService::denied()` grava o nome cru (`mb_substr(... ?? $ability, 0, 40)`); `AbilitySemMapeamentoTest.php:32,47` |
| A7 | Baixa | `openProjectsCount()` e `manages()` por linha (N+1 no B2) | ✅ Resolvido | contrato com `managedClientIds()` + `openProjectsCountByClient()` (`ClientManagement.php`); `MemoizedClientManagement.php:27-30` memoriza por requisição; `ClientController.php:114` pede em lote; `ClientManagementPorRequisicaoTest.php:45` |
| A8 | Baixa | `from` inválido derruba o período padrão | ✅ Resolvido | `AuditLogQuery.php:65-66,70`: só `''`/`null` é "sem período"; `AuditLogQueryTest.php:84-92` |
| A9 | Limpeza | `attempt()` copiado em 3 controllers | ✅ Resolvido | único `attempt()` em `app/Http/Controllers/Controller.php:36-45` |
| D2 | Decisão | Aviso de e-mail repetido por papel | ✅ Aplicada | `ContactService::duplicateWarning()` (l. 242-257) recebe o `$viewer`; `ContactControllerTest.php:155,171,183` |
| D3 | Decisão | Telefone como texto, até 20, com o "+" | ✅ Aplicada | `ContactService.php:43,283` (`trim`, sem `normalizeDigits`); busca pelos dígitos em l. 186 |
| D9 | Decisão | Tirar o `step 0.25` da carga diária | ✅ Aplicada | `user-form-dialog.tsx:90` `step="any"` (o `min="0.25"` ficou: CR-1-12) |

**Evolução:** Revisão 07/10: 9 achados + 3 decisões de código · Ciclo 1: 24 achados novos
**Neste ciclo:** 12 resolvidos · 0 parcial · 0 não resolvido · 0 regressão · 24 novos

---

## Achados do Ciclo 1

### [ALTO] CR-1-01 — "Reenviar convite" não derruba as sessões abertas do alvo

**Arquivo:** `app/Platform/Services/UserService.php:68-78`
**Trecho atual:**
```php
public function resendInvite(User $user, User $actor): bool
{
    $plain = DB::transaction(function () use ($user, $actor) {
        $plain = $this->passwords->issue($user);
        $this->audit->log(AuditActionEnum::PASSWORD_RESET, $user, null, ['must_change_password' => true], $actor->id);

        return $plain;
    });

    return $this->passwords->send($user, $plain, isReset: true);
}
```

**Ajuste necessário:**
Dentro da transação do `resendInvite`, chamar `$this->sessions->terminateOthers($user)` (o mesmo que `deactivate` faz na l. 125 e que `NewPasswordController.php:55` faz no "esqueci a senha"). Quando o alvo é a própria pessoa, o controller já faz logout.

**Motivo:**
A spec (Usuários AC 3) manda "invalidar a anterior". A senha anterior deixa de valer, mas a sessão aberta sobrevive: `EnsurePasswordIsCurrent` só leva o portador para `/admin/first-access`, e o `ChangePasswordController::update` (l. 36-55) troca a senha sem pedir a provisória. Quem tem um cookie de sessão roubado escolhe a senha nova, derruba a sessão do dono e fica com a conta; a senha provisória que chegou ao e-mail do dono já não vale. Verificado: na `main` o `resetPassword` do template tem o mesmo buraco (`git show main:app/Platform/Controllers/Admin/UserController.php`), então **não é regressão nem foi introduzido pelo PR**; mas o PR reescreveu o fluxo no Service e não fechou.

**Sugestão:**
Uma linha no Service + teste "sessão aberta do alvo cai ao reenviar convite" (há `SessionService::terminateOthers` e sessões em banco). Opcional: exigir a senha provisória no `ChangePasswordController::update`. Mexe em autenticação: **inegociável 5, decisão do Lucas**; sair em branch própria (correção de segurança, `git.md` §3).

**Impacto:**
Reenviar convite deixa de ser o remédio para "acho que minha sessão foi usada por outra pessoa"; só "Desativar" derruba a sessão. Exige um cookie já roubado, por isso não bloqueia o merge.

---

### [MÉDIO] CR-1-02 — CNPJ ou e-mail repetido em gravação simultânea responde 500

**Arquivo:** `app/Platform/Services/ClientService.php:66-75` (também `:89-93` e `UserService::update` `:87-110`)
**Trecho atual:**
```php
$clean = $this->validated($data, null);   // confere o repetido fora da transação

return DB::transaction(function () use ($clean, $actor) {
    $client = Client::query()->create($clean);
```

**Ajuste necessário:**
Capturar `UniqueConstraintViolationException` em `create`/`update` de cliente e no `update` de usuário e relançar como `ValidationException` com a mesma mensagem de `validated()` ("Já existe o cliente X com este CNPJ" / `EMAIL_TAKEN`), como `UserService::invite` já faz (l. 59-62). Teste com `tests/Support/Concorrencia.php`, no molde de `UserServiceTest.php:500`.

**Motivo:**
Check-then-act: o unique `(country, document)` (`2026_10_06_000120_create_clients_table.php:31`) segura o dado, mas a exceção não é convertida e vira 500 para o usuário.

**Sugestão:**
Um helper `catchUnique(Closure, string $field, string $message)` no Service base, ou o mesmo bloco try/catch do `invite`.

**Impacto:**
Raro (duas pessoas cadastrando o mesmo CNPJ ao mesmo tempo), mas é a única porta de 500 numa rota de formulário.

---

### [MÉDIO] CR-1-03 — `ContactController::update` deixa `DomainException` virar 500

**Arquivo:** `app/Platform/Controllers/Admin/ContactController.php:50-55`
**Trecho atual:**
```php
public function update(StoreContactRequest $request, Contact $contact): RedirectResponse
{
    $warning = $this->contacts->update($contact, $request->validated(), $request->user());

    return $this->withWarning(back()->with('success', 'Contato atualizado.'), $warning);
}
```

**Ajuste necessário:**
Capturar `DomainException` (ou estender `attempt()` para aceitar uma closure que devolve a resposta) e responder `back()->with('error', ...)`, preservando o `warning` no caminho feliz. Teste HTTP na ordem inversa da corrida: anonimiza, depois `PUT`, espera toast.

**Motivo:**
É a única ação de escrita do PR fora do `attempt()`. `ContactService::update` lança `DomainException(ContactPolicy::ANONYMIZED)` exatamente na corrida que o A4 passou a cobrir (`lockedNotAnonymized`, l. 265-274). A trava funciona e nada é gravado, mas a resposta é 500 em vez do toast. O teste de concorrência chama o Service direto e por isso não vê o 500.

**Sugestão:**
```php
try { $warning = $this->contacts->update(...); }
catch (DomainException $e) { return back()->with('error', $e->getMessage()); }
```

**Impacto:**
Quem editava um contato enquanto outro admin o anonimizava vê erro genérico em vez de "Contato anonimizado".

---

### [MÉDIO] CR-1-04 — "A WCJ não pode ser excluída" só existe na Policy, e a regra não está na spec nem na F1

**Arquivo:** `app/Platform/Services/ClientService.php:127-138` e `app/Platform/Policies/ClientPolicy.php:59-68`
**Trecho atual:**
```php
public function delete(Client $client, User $actor): void
{
    $before = $this->snapshot($client);

    $deleted = $client->deleteIfUnused(function (Client $deleted) use ($before, $actor) {
```

**Ajuste necessário:**
(1) No `ClientService::delete`, `if ($client->isWcj()) throw new DomainException(ClientPolicy::WCJ_NOT_DELETABLE);` no começo, como `deactivate` (l. 99-102) e `update` (l. 85) já fazem "para quem chamar o Service direto"; teste de Service. (2) O Lucas confirma a regra e ela entra em Clientes AC 10 e na F1 §04, ou sai. O N7 só diz "não se desativa"; a trava de exclusão apareceu no commit `3ec88cd` e só consta no `validation.md`. O `AGENTS.md` exige aprovação para Policy nova ou alterada (inegociável 2: regra que não está no escopo não existe).

**Motivo:**
O B2 e o MCP do B8 chamam os Services. Um chamador que pule a Policy apaga a WCJ recém-instalada (sem histórico, passa no `deleteIfUnused`), e o N7 diz que ela existe em toda instalação. Hoje a rota HTTP está protegida (`ClientController.php:95`).

**Sugestão:**
Mesma linha que em `deactivate`; registrar a decisão na spec. A revisão de 07/10 (A2) já tratava "Excluir libera" como problema, então a intenção parece ser manter a trava.

**Impacto:**
Sem a trava no Service, o inegociável 9 (regra no servidor, no ponto que efetiva) fica pela metade para esta regra.

---

### [MÉDIO] CR-1-05 — NUC-13 (lista de escolha) não tem porta de entrada e o AC 3 (dev recebe 403) não é exercido por rota nenhuma

**Arquivo:** `app/Platform/Services/UserService.php:188-196`, `ClientService.php:169-177`, `app/Platform/Policies/UserPolicy.php:56-59`, `ClientPolicy.php:71-74`
**Trecho atual:**
```php
public function choiceList(): array
{
    return User::query()
        ->active()
        ->orderBy('name')
        ->get(['id', 'name', 'role_id', 'deactivated_at'])
```

**Ajuste necessário:**
Registrar a D6 do `design.md` ("Métodos de Service + `viewChoiceList` nas Policies, sem rota JSON; o B2 entrega as listas como props") como desvio da spec **aprovado pelo Lucas**, ou entregar a prop/rota com `authorize('viewChoiceList', ...)` e um teste HTTP de 403 para o dev. Hoje nenhum código fora de Service e Policy chama `choiceList()` nem `viewChoiceList` (grep em `app/`, `routes/`, `resources/js`).

**Motivo:**
A spec aprovada (P2: "é o endpoint de escolha que o B2 e o B3 consomem"; AC 3: "IF um Desenvolvedor pede ... THEN 403") pede comportamento HTTP. Quem decidiu "sem rota" foi o design, aprovado pelo Cauã, não o Lucas. O risco concreto é o B2 chamar `choiceList()` sem `authorize('viewChoiceList')`, porque o Service não consulta a Policy.

**Sugestão:**
Aceitar a D6 e reescrever o AC 3 no nível da Policy (lição L-004 do `LESSONS.md` diz exatamente isso), ou pôr a chamada à Policy dentro de `choiceList()`.

**Impacto:**
Requisito com AC que nenhuma requisição prova; dívida de permissão que o B2 herda sem aviso.

---

### [MÉDIO] CR-1-06 — A spec foi reescrita em 08/10 sem nova aprovação, e uma mudança não tem decisão correspondente

**Arquivo:** `.specs/features/b1-nucleo/spec.md:10-12` e a premissa "Aviso de e-mail repetido no contato" (commit `96569b9`, Cauã, 08/10)
**Trecho atual:**
```diff
-| Aviso de e-mail repetido no contato | Mensagem na tela ao sair do campo e no envio, sem recusar; …
+| Aviso de e-mail repetido no contato | Mensagem na tela depois do envio, sem recusar; … (D2, 07/10)
```

**Ajuste necessário:**
O Lucas aprova a revisão de 08/10 (linha "Approved by" datada; hoje segue `2026-10-06`) e decide sobre o aviso "ao sair do campo", que saiu da premissa sem constar em D1–D11; ou o Cauã o implementa. A F1 foi para a v1.1 mantendo "Aprovado em 06/10".

**Motivo:**
O trilho completo do projeto diz "spec aprovada pelo Lucas Scholz antes do código". As demais mudanças de 08/10 (AC 12/12/9 → toast, AC 5 de Clientes, AC 9 de Clientes, AC 10 de Clientes, AC 1/3/13 de Contatos, AC 2 de Auditoria, premissa de autorização antes da validação) correspondem a D1–D11 e A1/A2. Só esta não.

**Sugestão:**
Uma linha de aprovação no cabeçalho da spec e uma linha na tabela de decisões do design; nada no código se a retirada for aceita.

**Impacto:**
Rastreabilidade: o próximo bloco não sabe o que foi decidido e o que foi editado.

---

### [MÉDIO] CR-1-07 — Filtro de data da auditoria é controlado só pelas props do servidor e dispara uma visita por tecla

**Arquivo:** `resources/js/components/audit-logs/audit-log-filters.tsx:81-97`
**Trecho atual:**
```tsx
<Input
    id="audit-from"
    type="date"
    value={filters.from ?? ''}
    onChange={(event) => visit({ from: event.target.value })}
/>
```

**Ajuste necessário:**
Estado local (`useState`) para "De" e "Até", com a visita disparada por debounce ou no `onBlur`, no molde do `SearchInput` (`components/search-input.tsx:36-40`).

**Motivo:**
O campo só muda quando o servidor responde. Como o "De" já vem preenchido (7 dias), cada segmento digitado produz uma data válida e uma visita, e o React devolve o valor antigo até a resposta chegar; visitas síncronas do Inertia 2 cancelam umas às outras. Os e2e usam `fill` (valor de uma vez) e não pegam o caso. Comportamento exato por navegador: incerto; o risco é concreto no teclado.

**Sugestão:**
Reaproveitar o padrão do `SearchInput`; nenhum componente novo.

**Impacto:**
Digitar uma data no filtro pode "pular" ou travar o campo; o usuário recorre ao seletor do navegador.

---

### [MÉDIO] CR-1-08 — Botões da linha de Usuários decidem por ability global e copiam regra da Policy; Clientes e Contatos recebem `can` por linha

**Arquivo:** `resources/js/pages/admin/users/index.tsx:166`, comparado com `app/Platform/Controllers/Admin/ClientController.php:137-142` e `ContactService.php:228-234`
**Trecho atual:**
```tsx
hidden: !can.delete_records || row.last_login_at !== null || row.id === currentUser?.id,
```

**Ajuste necessário:**
`UserController::row` devolver `can` (`update`, `deactivate`, `reactivate`, `delete`, `resendInvite`) calculado pela `UserPolicy`, como os outros dois cadastros; a tela lê `row.can.*`.

**Motivo:**
Dois jeitos de decidir a mesma coisa (inegociável 3), e a regra "não a si mesmo" de `UserPolicy::delete` está duplicada no front. Quando a Policy mudar, o botão diverge. A segurança não está em risco: o servidor confere.

**Sugestão:**
Mesmo formato de `ClientController::row`.

**Impacto:**
Manutenção; a ability `delete_records` deixa de ter sentido por linha.

---

### [MÉDIO] CR-1-09 — Esqueleto de lista repetido em 4 páginas e três padrões para montar a linha; `ContactService` a 2 linhas do teto da A6

**Arquivo:** `resources/js/pages/admin/users/index.tsx:212-237`, `clients/index.tsx:183-208`, `contacts/index.tsx:72-105`, `audit-logs/index.tsx:57-86`; `app/Platform/Services/ContactService.php:217-236` (298 linhas; teto A6 = 300)
**Trecho atual:**
```tsx
{list.failed ? (<EmptyState icon={<AlertTriangle …/>} title="Não foi possível carregar …" action={<Button onClick={list.retry}>Tentar de novo</Button>} />)
 : (<div aria-busy={list.loading} className={cn('transition-opacity', list.loading && 'opacity-60')}>
      <p role="status" className="sr-only">{list.loading ? 'Carregando …' : ''}</p>
```

**Ajuste necessário:**
Front: um componente `ListBody` (ou `ListState`) com `failed`, `loading`, rótulo e filhos, e um helper único para "Desativado em … por …" (hoje em `user-table.tsx:30-36`, `client-table.tsx:31-37`, `contact-table.tsx:29-37`), registrados na tabela de componentes do projeto. Back: a linha da lista mora no controller (`UserController::row`, `ClientController::row`), no Service (`ContactService::row`) e numa classe própria (`AuditLogPresenter`). Tirar `ContactService::row()` para um `ContactPresenter` no padrão do `AuditLogPresenter`.

**Motivo:**
B2 a B7 vão copiar mais 4 ou 5 vezes; um ajuste no estado de erro ou de carregando vira N edições. Qualquer acréscimo no `ContactService` quebra a A6.

**Sugestão:**
Pode ser PR próprio, antes do B2, para o padrão nascer uma vez só.

**Impacto:**
Dívida que cresce bloco a bloco.

---

### [MÉDIO] CR-1-10 — A busca com debounce cancela uma escrita em voo; o A5 corrigiu o teste, não o produto

**Arquivo:** `resources/js/components/search-input.tsx:36-40` (registrado em `e2e/pages/admin.page.ts:21-25`)
**Trecho atual:**
```ts
 * SearchInput navega 300 ms depois da digitação (debounce); uma ação clicada antes disso é
 * cancelada por essa visita: o PUT do Editar morria com status -1 (A5 da review de 07/10).
```

**Ajuste necessário:**
No `SearchInput`, cancelar o timer pendente quando começar uma visita que não é GET (`router.on('before')`), ou enviar as ações de linha com `async: true`.

**Motivo:**
Visita síncrona interrompe visita síncrona no Inertia 2. Um "Reativar" (sem confirmação) clicado logo depois de digitar na busca é cancelado no cliente, sem toast nem erro: a escrita se perde em silêncio. Em velocidade humana é raro, mas é perda silenciosa.

**Sugestão:**
Duas linhas no `SearchInput`; e2e que digita e clica em menos de 300 ms e espera o toast.

**Impacto:**
Ação do usuário descartada sem feedback (fere "todo clique sinaliza").

---

### [BAIXO] CR-1-11 — A senha provisória não expira, mas a spec diz que expira "pela regra do template"

**Arquivo:** `app/Platform/Services/TemporaryPasswordService.php:29-39`; spec, tabela de dimensões implícitas ("Data lifecycle")
**Trecho atual:**
```php
// grava password e must_change_password, sem data; o único expire (config/auth.php:99) é do "esqueci a senha"
```

**Ajuste necessário:** decisão do Lucas: corrigir a spec (não expira) ou criar o prazo. Prazo precisa de coluna (aprovação de migração) ou de ler a linha `password_reset` da auditoria.
**Motivo:** a regra citada pela spec não existe no template.
**Sugestão:** corrigir a spec no B1; prazo, se quiser, em bloco próprio.
**Impacto:** senha que ficou numa caixa de e-mail vale até o primeiro uso.

---

### [BAIXO] CR-1-12 — Tela mais rígida que o servidor na carga diária

**Arquivo:** `resources/js/components/users/user-form-dialog.tsx:87-90`
**Trecho atual:**
```tsx
min="0.25"
max="24"
step="any"
```

**Ajuste necessário:** `min="0"` (ou tirar) com `step="any"`; o servidor (`StoreUserRequest.php:49`, `gt:0`) e o CHECK do banco aceitam 0,1. Tela e servidor divergem: inegociável 5, o Lucas diz qual lado vale (a D9 tirou o `step`, o `min` ficou).
**Motivo:** regra que só existe na tela.
**Impacto:** 0,1 a 0,24 recusados pelo navegador.

---

### [BAIXO] CR-1-13 — Reenviar convite manda o e-mail de "nova senha", não "o mesmo e-mail", e a auditoria grava o "antes" vazio

**Arquivo:** `app/Platform/Services/UserService.php:71-77`, `app/Mail/TemporaryPasswordMail.php:27-29`
**Trecho atual:**
```php
$this->audit->log(AuditActionEnum::PASSWORD_RESET, $user, null, ['must_change_password' => true], $actor->id);
…
return $this->passwords->send($user, $plain, isReset: true);
```

**Ajuste necessário:** `isReset: false` no reenvio de convite (premissa da spec: "manda o mesmo e-mail") e o valor anterior de `must_change_password` como `before` (AC 16).
**Motivo:** o reenvio sai com assunto "Nova senha temporária" e a auditoria registra `[null, true]`.
**Impacto:** texto e histórico diferentes do que a spec descreve.

---

### [BAIXO] CR-1-14 — Contato em cliente desativado devolve o erro numa chave que o formulário não mostra

**Arquivo:** `app/Platform/Services/ContactService.php:63-66`; `resources/js/components/contacts/contact-form-dialog.tsx`
**Trecho atual:**
```php
if (! $client->isActive()) {
    throw ValidationException::withMessages(['client' => self::CLIENT_INACTIVE]);
}
```

**Ajuste necessário:** mostrar `form.errors.client` no diálogo ou devolver flash de erro.
**Motivo:** o diálogo só exibe `name`, `email` e `phone`; se o cliente foi desativado com o diálogo aberto, o envio falha sem mensagem.
**Impacto:** feedback perdido (regra "todo clique sinaliza").

---

### [BAIXO] CR-1-15 — Checar-e-agir fora da transação em `deactivate` (3 cadastros) e no cliente ativo ao criar contato

**Arquivo:** `UserService.php:114-127`, `ClientService.php:97-112`, `ContactService.php:64-73` e `:99-109`
**Trecho atual:**
```php
if (! $user->isActive()) { return; }
DB::transaction(function () use ($user, $actor) {
```

**Ajuste necessário:** reler com `lockForUpdate()` dentro da transação, como `ContactService::reactivate` (l. 113-114) já faz; no `create` de contato, travar a linha do cliente antes de checar `isActive()`.
**Motivo:** o estado é lido do modelo em memória. Dois cliques ou dois admins gravam duas linhas `deactivated` na auditoria; em corrida com a anonimização, `deactivate` regrava `deactivated_at/by` e acrescenta `deactivated` depois de `anonymized`. Um contato pode nascer num cliente desativado ao mesmo tempo (N11). Sem dado pessoal vazado nem dado errado: ruído na auditoria.
**Impacto:** histórico com linhas duplicadas.

---

### [BAIXO] CR-1-16 — `Client` depende de classe de seeder e compara `'BR'` literal em vez de `Paises::BRASIL`

**Arquivo:** `app/Platform/Models/Client.php:7,35-38`
**Trecho atual:**
```php
use Database\Seeders\WcjClientSeeder;
return $this->country === 'BR' && $this->document === WcjClientSeeder::CNPJ;
```

**Ajuste necessário:** constante em `Client` (ou `config/hub.php`) que o seeder lê; `Paises::BRASIL` ("Um jeito só" do `AGENTS.md`).
**Motivo:** código de produção lendo constante de seeder (funciona porque `Database\Seeders` está no `autoload`, mas o acoplamento está invertido).
**Impacto:** organização.

---

### [BAIXO] CR-1-17 — "Reativar" em Usuários e Clientes não sinaliza o clique nem trava clique duplo

**Arquivo:** `resources/js/pages/admin/users/index.tsx:157`, `clients/index.tsx:123`, comparado com `contacts/contact-actions.tsx:74`
**Trecho atual:**
```tsx
onClick: () => rowPost(`/admin/users/${row.id}/reactivate`),
```

**Ajuste necessário:** a trava `!busy &&` de Contatos e um sinal na linha (ícone de carregando ou opacidade).
**Motivo:** o `busy` só aparece no `ConfirmDialog`, fechado nesse momento; fora a barra do Inertia, nada mostra que a ação roda até o toast.
**Impacto:** regra "todo clique sinaliza".

---

### [BAIXO] CR-1-18 — `LIKE` sem escapar `%` e `_`

**Arquivo:** `UserService.php:175-177`, `ClientService.php:155-158`, `ContactService.php:183-186`, `AuditLogQuery.php:166`
**Trecho atual:**
```php
->where('name', 'like', "%{$search}%")
```

**Ajuste necessário:** escapar `%`, `_` e `\` num helper único.
**Motivo:** consulta parametrizada (sem injeção), mas `_` e `%` digitados viram curinga.
**Impacto:** busca por "a_b" encontra "aXb".

---

### [BAIXO] CR-1-19 — Mensagens em português dependem de `APP_LOCALE`, que o `DEPLOY.md` não cita

**Arquivo:** `config/app.php:116` (`env('APP_LOCALE', 'en')`); `docs/DEPLOY.md`
**Ajuste necessário:** padrão `pt_BR` no config, ou citar a variável no `DEPLOY.md`.
**Motivo:** `lang/pt_BR/validation.php` só vale com `APP_LOCALE=pt_BR`; está no `.env.example` e no `.env.testing`, não no runbook de produção.
**Impacto:** produção configurada sem a variável valida em inglês.

---

### [BAIXO] CR-1-20 — Sem prova em 375 px

**Arquivo:** `playwright.config.ts:32` (só `Desktop Chrome`); `validation.md` deixa "print do Playwright no PR" como próximo passo
**Ajuste necessário:** projeto mobile ou smoke com `setViewportSize(375, …)` nas 5 telas e no detalhe da auditoria; print no PR (rito do PR, `ui.md` §8, `git.md` §3).
**Motivo:** tema escuro está coberto pelo smoke com axe; 375 px não.
**Impacto:** pendência do rito, não defeito conhecido.

---

### [BAIXO] CR-1-21 — e2e crítico usa CNPJ fixo e só limpa no fim do corpo do teste

**Arquivo:** `e2e/nucleo.spec.ts:21,45-49`
**Trecho atual:**
```ts
await clients.submitForm(name, '12.ABC.345/01DE-35');
…
await clients.deleteRow(renamed);
```

**Ajuste necessário:** limpeza em `afterEach` ou `try/finally` (o helper `e2e/helpers/dados.ts` já existe). Mesmo caso em `withAnonymizeDialog` no smoke.
**Motivo:** falha no meio deixa o cliente no banco e as execuções seguintes caem em "Já existe…" até rodar `e2e:prepare`.
**Impacto:** flakiness em cascata.

---

### [BAIXO] CR-1-22 — Componentes e hook novos fora da tabela de componentes

**Arquivo:** `ShowInactiveToggle`, `AlphanumericCnpjInput`, `useListVisit`, tipos `contact`/`audit` do `StatusPill`, `rowClassName` do `DataTable`
**Ajuste necessário:** listar na seção do projeto do `AGENTS.md` (ou numa tabela de componentes do projeto), já que `.ai/` é gerado e não se edita.
**Motivo:** `ui.md` §5 manda registrar no mesmo commit.
**Impacto:** o próximo bloco não sabe que existem e cria de novo.

---

### [BAIXO] CR-1-23 — Auditoria sem índice para o filtro padrão por `created_at`

**Arquivo:** `database/migrations/2026_10_06_000170_create_audit_logs_table.php:41-43`; `AuditLogQuery.php:89,98`
**Trecho atual:**
```php
$table->index(['auditable_type', 'auditable_id', 'created_at']);
$table->index('user_id');
$table->index('ai_authorization_id');
```

**Ajuste necessário:** registrar como dívida (índice em `created_at` e em `(user_id, action, created_at)`) para o Lucas decidir; migração exige aprovação, o bloco não cria.
**Motivo:** a lista padrão filtra `created_at >= ?` e ordena `created_at desc, id desc` sem índice que sirva. Com 14 usuários não dói; a tabela só cresce (todo login e toda negação).
**Impacto:** desempenho futuro.

---

### [BAIXO] CR-1-24 — Com `MAIL_MAILER=log`, a senha provisória vai em claro para `storage/logs`

**Arquivo:** `.env.example:78`, `.env.e2e.example:32`; `TemporaryPasswordService::send`
**Ajuste necessário:** opcional: recusar `log` quando `app()->isProduction()`. O `docs/DEPLOY.md:21` manda `smtp` em produção.
**Motivo:** em dev, e2e ou servidor configurado errado, a senha fica no log e a tela diz "Convite enviado".
**Impacto:** só fora de produção, se o runbook for seguido.

---

## Registrado, sem achado

- **Oráculo de e-mail de contato para não-admin** (`ContactService.php:242-256`): "Já existe um contato com este e-mail" revela existência em qualquer cliente, sem nome nem cliente. Decisão consciente do Lucas (D2). Efeito só a partir do B2.
- **Limite de login** (`AppServiceProvider.php:85-97`): 5/min por IP+conta, 20/min por IP, sem teto global por conta. Do template; o PR não tocou.
- **Ficha do cliente** (`show.tsx`) recebe `client.can` mas não oferece Editar, Desativar nem Excluir; a lista oferece. Não falta porta.
- **`UserPolicy::view`** sem rota `show`; não há tela que a use.
- **Formulário da WCJ** deixa editar CNPJ e país, que o servidor recusa com toast. Sugestão de UX: desabilitar os dois campos quando `isWcj`.
- **Filtro "Situação"** é a chave "Mostrar desativados" do gabarito do template (não lista só os desativados). A F1 §03 cita a chave; aceitável.
- **D11** (dado pessoal na linha `deleted` do contato) já registrado na `necessidade.md`.
- **Fora da tarefa:** nenhuma dependência nova (`package-lock.json` só renomeia o pacote; `composer.lock` intacto); `tests/Arch/divida.php` só diminuiu (saíram 3 entradas A6); `tests/Feature/UserControllerTest.php` foi adaptado às rotas `deactivate`/`reactivate` e ganhou asserção, nada afrouxado; `Controller.php`, `HandleInertiaRequests.php`, `AppServiceProvider.php`, `app-layout.tsx` (toast no topo), `status-pill.tsx` ("Inativo" → "Desativado"), `app-sidebar.tsx` ("Cadastros" → "Administração") mudaram com justificativa no design ou nas decisões D5/ADR-004.

## Está bem feito

- Negação auditada num lugar só (`AuditsDeniedAbilities.php:18-28`), usada pelo controller base e pelos FormRequests; quem não pode recebe 403 sem o erro de validação que revelaria dado.
- Trava do último administrador de verdade: `lockForUpdate` nos admins ativos (`UserService.php:213-224`) e teste de intercalação forçada.
- "Anonimizado é estado final" vale também na corrida (`ContactService.php:265-274`), com teste de edição e de reativação.
- Sem N+1 nas listas: contrato em lote (`ClientController.php:107-115`), memória por requisição (`MemoizedClientManagement.php:27-30`) e teste que conta as chamadas.
- Auditoria resolve nomes em lote, uma consulta por tipo (`AuditLogQuery.php:187-217`); período pelo dia local testado nas bordas 00:30 e 23:30.
- Gravar e enviar a senha provisória estão separados (`issue`/`send`): falha de SMTP não desfaz o usuário; convite simultâneo vira erro no campo.
- Testes em MySQL de verdade (ADR-002), sem `skip`, `only` nem `waitForTimeout`; e-mails e credenciais de e2e só por `.env` ignorado; sem segredo no diff (Secrets scan verde).

---

## Resumo executivo

**Achados deste ciclo por criticidade:** 0 Crítico · 1 Alto · 9 Médio · 14 Baixo (24).

**Pelo vocabulário da revisão em camadas (`testes.md` §10), para a descrição do PR:**

| Classe | Achados | Observação |
|---|---|---|
| **Bloqueante** | 0 | O Alto (CR-1-01) toca autenticação, mas é herdado da `main` (o `resetPassword` do template tem o mesmo buraco) e exige cookie já roubado: não é regressão nem introduzido pelo PR |
| **Deveria corrigir** (corrige ou justifica no PR) | 10 | CR-1-01 a CR-1-10. Quatro são decisão do Lucas, não código do Cauã: CR-1-01 (autenticação, inegociável 5), CR-1-04 (regra nova de Policy), CR-1-05 (desvio D6 da spec), CR-1-06 (aprovação da spec de 08/10) |
| **Detalhe** (a critério de quem fez) | 14 | CR-1-11 a CR-1-24; dois pedem decisão (CR-1-11 prazo da senha, CR-1-12 lado que vale na carga) e um pede migração (CR-1-23) |

**Achados anteriores (revisão de 07/10):** 12 de 12 resolvidos e verificados no código (A1–A9, D2, D3, D9); 0 parciais; 0 regressões.

**Principais riscos:** (1) reenviar convite não encerra sessão roubada; (2) 500 em duas corridas raras (CNPJ repetido; editar durante anonimização); (3) trava de exclusão da WCJ só na Policy, à espera do B2/B8 que chamam os Services; (4) escrita descartada em silêncio quando o clique vem logo depois da busca.

**Avaliação geral:** branch bem construída, acima do padrão do template: autorização antes da validação, auditoria de negação, travas de concorrência com teste, contrato de domínio sem N+1, 322 testes PHP em MySQL e 56 e2e verdes localmente e no CI. Os achados são bordas (corridas, 500 em vez de toast) e rastreabilidade de decisões, não defeitos do caminho feliz.

**Recomendação final: apta com ajustes.** Merge condicionado a: o Lucas dar a palavra em CR-1-01 (branch própria depois), CR-1-04, CR-1-05 e CR-1-06; o Cauã corrigir CR-1-02, CR-1-03 e CR-1-07 a CR-1-10, ou justificar no PR. Os Baixos podem ficar para o B2 ou para um PR de limpeza antes dele.
