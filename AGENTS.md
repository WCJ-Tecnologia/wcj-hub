<!-- wcj-ai:inicio — bloco gerado pelo padrão WCJ. Não edite aqui: mude no repositório do padrão. -->
# Padrão WCJ de desenvolvimento com IA

Você está trabalhando num projeto da WCJ Tecnologia. Estas regras valem para qualquer stack, qualquer ferramenta de IA e qualquer pessoa. A stack do projeto e o que é específico dele estão depois deste bloco, na seção "Stack e onde as coisas moram".

## Os 12 inegociáveis

1. **Evidência antes de afirmação.** Não diga "funciona", "corrigi" ou "está no ar" sem ter rodado o comando e visto a saída. Se não rodou, diga "implementei, mas não verifiquei".
2. **Não invente.** Regra de negócio, campo, tela, rota ou permissão que não está no escopo não existe. Faltou informação, pare e pergunte.
3. **Leia antes de escrever.** Reutilize o componente, o layout e o padrão que o projeto já tem. Não crie um segundo jeito de fazer a mesma coisa.
4. **Fatie.** Uma tarefa por vez, com entrega que uma pessoa consegue revisar. Uma feature, um PR, um assunto.
5. **Pare e chame um humano** antes de: modelar ou remodelar banco; migração destrutiva; mexer em autenticação ou permissão; tocar em produção (banco, servidor, DNS, firewall); desligar trava de escrita; rodar teste de carga ou varredura contra ambiente que não é o seu; enviar código de cliente a serviço externo; marcar achado de segurança como corrigido; decidir de que lado vale uma regra quando tela e servidor divergem.
6. **Segredo nunca vai para o código, para o git nem para documento.** Runbook cita o nome da variável e onde buscar o valor, nunca o valor.
7. **Bom senso de custo.** Ajuste trivial (texto, cor, espaçamento) não passa pelo fluxo completo. Trivial é o que não toca banco, rota, permissão nem regra de negócio.
8. **Autorização em duas camadas e escopo por ID.** Grupo de rotas com `auth` por padrão, rota pública é exceção declarada. Toda rota que recebe ID de empresa, loja, cliente ou pedido valida o vínculo com a sessão antes de ler ou gravar.
9. **Regra de negócio vale no servidor**, no mesmo ponto que efetiva a ação. Validação na tela é conveniência.
10. **`git fetch` e confira a branch que é o ambiente antes de editar.** Se o servidor ou o banco tem algo que o seu clone não conhece, o clone está desatualizado. Não apague.
11. **Funcionalidade sem porta de entrada não existe.** Ao fechar uma feature, confira por entidade: dá para criar a primeira, listar, editar e desfazer?
12. **Você nunca lê, digita nem pede segredo, nem monta chamada com credencial.** Monte o caminho e deixe o humano colar o valor; sistema externo se acessa por ferramenta que guarda a credencial. Segredo que passou por chat, log ou ticket está vazado e precisa ser trocado. Sessão com dado de cliente não navega na web.

## O que ler conforme a tarefa

| Vai fazer o quê | Leia antes |
|---|---|
| Criar aplicação do zero | `.ai/playbooks/aplicacao-nova.md` |
| Mudar ou evoluir sistema existente | `.ai/playbooks/manutencao.md` |
| Feature nova ou mudança que não é trivial | A skill `tlc-spec-driven`, com as regras da casa em `.ai/core/especificacao.md` |
| Corrigir bug | `.ai/playbooks/bug.md` |
| Produção fora do ar ou dado perdido | `.ai/playbooks/incidente.md` |
| Teste de carga, stress ou pentest | `.ai/playbooks/teste-carga-e-pentest.md` |
| Teste de arquitetura, banco de verdade ou benchmark | `.ai/core/testes.md`, seção 11 |
| Mexer em tabela, migração ou consulta | `.ai/core/banco.md` |
| Mexer em rota, login, permissão, upload, header | `.ai/core/seguranca.md` |
| Listagem, cache, fila, consulta pesada | `.ai/core/performance.md` |
| Criar ou mudar tela | `.ai/stacks/ui.md` (o método vale para qualquer stack; caminhos e componentes são os do template Laravel + Inertia) |
| Escrever código | A seção "Stack e onde as coisas moram", depois deste bloco. Projeto Laravel + Inertia + React: `.ai/stacks/laravel-inertia.md` |
| Commit, branch, PR | `.ai/core/git.md` |
| Dizer que terminou | `.ai/core/testes.md` (seção 10: o Verifier da skill e a revisão em camadas) |
| Sessão com dado de cliente, credencial ou serviço externo | `.ai/core/seguranca.md`, seção 12 |
| Publicar servidor MCP ou API para a IA do usuário | `.ai/core/mcp.md` |
| Publicar | `.ai/core/deploy.md` |
| Uma trava de agente bloqueou ou pediu confirmação | `.ai/adapters/README.md`. Não contorne: explique ao humano o que queria fazer |

Feature e mudança que não é trivial seguem a skill `tlc-spec-driven`. Valem mais que ela os 12 inegociáveis, as paradas e as regras da casa de `.ai/core/especificacao.md`, seção 5. Na prática:

- aprovar a spec ou as tarefas não autoriza o que o inegociável 5 manda parar;
- decisão crítica tem ADR em `docs/adr/`;
- sessão com dado de cliente não usa a busca na web nem o Context7 da skill;
- o Verifier nunca é quem implementou;
- o rascunho do sensor de mutação fica em `/tmp`;
- `.specs/` vai inteiro para o git.

Os princípios com o porquê de cada um estão em `.ai/core/principios.md`.
<!-- wcj-ai:fim -->

# WCJ Hub

## O que é

Sistema interno da WCJ Tecnologia, para até 14 usuários, que substitui o Trello na gestão de projetos e dá a cada usuário uma conexão da própria IA (Claude, Codex, ChatGPT) com as mesmas permissões da tela. A **fatia 1** (D-GER-24) é Núcleo + Projetos + servidor MCP com 6 ferramentas. CRM é v2; Chamados vem depois. Entrada e ordem de leitura: `docs/00-COMECE-AQUI.md`.

**Não assuma:**

- **Não há multiempresa.** É single-tenant: sem `enterprise_id`, sem escopo por empresa. O escopo de acesso é papel + alocação no projeto (`project_members`). O template tinha `enterprises`, `scopedEnterpriseIds()` e `canAccessEnterprise()`: saíram no B0 (seção 9 do `banco.md`).
- **O kanban não trava.** O sistema nunca recusa mover card, pôr na sprint, encerrar sprint ou montar pacote por falta de dev, horas, PR, parecer ou capacidade. Número de controle (capacidade, data prevista) é referência. Recusa só por permissão, dado inválido e integridade do banco (premissa da `necessidade.md`).
- **Não há CRM, contrato, chamado, tipo de serviço nem GitHub por webhook** na fatia 1. As tabelas do CRM (seção 3 do `banco.md`) são proposta da v2: não crie.
- **A IA do sistema não existe.** O Hub não chama IA nem guarda chave de provedor (IA2): é a IA do usuário que chama o Hub, pelo MCP.
- **Dinheiro não existe** na fatia 1.
- O "Tech Lead" não é papel nem fica no sistema; o "Comercial" existe como papel, sem uso na fatia 1.

## Estado atual

Atualizado em 2026-10-14.

| Bloco | Estado |
|---|---|
| B0 repositório, 27 migrações, sementes, CI em MySQL 8, tema da marca | na `main` (PR #1) |
| B1 Núcleo: usuários, clientes, contatos, auditoria | na `main` (PR #5 e #7, 09/10): `wcj-code-review` em 6 ciclos (`.specs/features/b1-nucleo/code-reviews/`), Verifier rodadas 9 a 13 |
| Front: shadcn + Atomic Design + Storybook + marca WCJ (ADR-003, ADR-005) | na `main` (PR #8, 09/10, Lucas Varanda), com o Núcleo portado para a estrutura atômica |
| B2 passo 1: lista, cadastro, encerrar, reabrir e excluir projeto; `ProjectClientManagement` | na `main` (PR #6, 09/10, Vitinho), com as correções dos ciclos 1 e 2 da `wcj-code-review`; criar/editar por modal |
| Docs de 13/10: `banco.md` (PR #9) e card do quadro, D-GER-32 (PR #10) | na `main` |
| **Kit do quadro** (front do B3 sem backend: card, colunas, kanban com arrastar e soltar, diálogos, hook de movimento, página de exemplo; tudo no Storybook) | PR #11 (`feat/front-kit-quadro`, Lucas Scholz com IA): contrato em `resources/js/types/quadro.ts`, regras de tela em `lib/quadro.ts`, fixtures em `.storybook/fixtures-quadro.ts`. O B3 liga o backend nesses componentes |
| B2 passo 2 (Pessoas, Etapas, Áreas, Padrões, listas gerais, "Novo cliente" inline) e B3 a B10 | não começados; ordem em `docs/plano-de-desenvolvimento.md`. **Lucas Varanda e Vitinho saíram em 14/10 (D-GER-33):** o Cauã segue B3 → B4 → B6 → B8; os blocos do Vitinho estão a redistribuir |

## Vocabulário do produto

Completo na `docs/necessidade.md`, seção "Vocabulário". O que mais aparece no código:

| Termo na tela | Significa | No código / banco | Nunca chame de |
|---|---|---|---|
| Cliente | Empresa atendida, inclusive a própria WCJ (N7) | `clients` | empresa, enterprise |
| Contato | Pessoa de um cliente (N8) | `contacts` | usuário |
| Papel | Administrador, Gestor de projetos, Desenvolvedor, Comercial (N2) | `roles`, `RoleEnum` | perfil, tipo de usuário (`user_types` saiu) |
| Carga diária | Horas por dia da pessoa (N3) | `users.daily_hours` | jornada |
| Projeto de entrega / produto contínuo | Com ou sem data de entrega (P2) | `projects.due_on` nulo = contínuo | — |
| Tarefa, card | Item do quadro | `tasks` | ticket, chamado |
| Chave | `SGM-17` = prefixo + número (P16) | `projects.key_prefix` + `tasks.number`, sem coluna gravada | id |
| Etapa | Coluna do quadro, com papel fixo (P11) | `board_stages` + `stage_roles` | status |
| Backlog, To do | Fila fora da sprint; sprint não começada | etapas de papel `queue` e `sprint` | Esteira (nome antigo do Trello) |
| Sprint | Período que agrupa tarefas; "Fase" no histórico (P51) | `sprints`, aberta = `closed_at` nulo | ciclo |
| Pacote, versão | Tarefas que vão juntas para homologação e produção (P41) | `packages`, estado pelas duas datas | release |
| Critério de aceite (`CA_n`) | Item do requisito e do roteiro de QA (P17, P36) | `task_acceptance_criteria` | checklist |
| Teste cruzado | Teste por outro dev antes do QA (P34) | `tasks.cross_tested_by/at` | validação, parecer |
| Retrabalho | Volta para desenvolvimento com origem e motivo (P39) | movimento com `rework_origin_id` | bug |
| Card de suporte | Pedido do cliente registrado pelo gestor (P52) | `task_types.slug = support` | chamado |
| Card pai, card filho | Tarefa que agrupa outras, um nível; o filho é card normal (P57) | `tasks.parent_task_id`; progresso derivado da etapa dos filhos | épico, subtask |
| Participante | Quem acompanha a tarefa sem ser dev nem QA (P58) | `task_participants` | responsável, assignee |
| Capa | Anexo de imagem mostrado no card do quadro (P59) | `tasks.cover_attachment_id` | thumbnail |
| Arquivar | Tirar do quadro um card com histórico, com motivo (P26) | `tasks.archived_at` + `archive_reason` | excluir |
| Desativar | Tirar de uso um cadastro com histórico (G2) | `deactivated_at/by` | excluir, inativar por booleano |
| Conector, conexão de IA | Autorização de um assistente em nome do usuário (IA4) | `ai_authorizations` | integração, API key |

## Stack e onde as coisas moram

Laravel 12 + Inertia 2 + React 19 + TypeScript, do template `ai-systems` 0.6.0 (`.ai/stacks/laravel-inertia.md`). O que difere do template:

- **Por domínio** (ADR-001): `app/Platform/` (o Núcleo: usuários, papéis, clientes, contatos, auditoria, conexões de IA) e `app/Domains/Projetos/`, cada um com `Controllers`, `Models`, `Policies`, `Requests`, `Services` e `Mcp`. Infraestrutura fica onde o template põe (`app/Http/Middleware`, `app/Console`, `app/Mail`, `app/Providers`, `app/Rules`, `app/Support`). O Platform não usa nada de `App\Domains\` (teste A9): dado de módulo que o Núcleo precisa entra por interface em `app/Platform/Contracts/`, com a implementação real no módulo e a ligação no `AppServiceProvider` (ex.: `ClientManagement`). Lista geral de um módulo (tipos de tarefa, motivos de retrabalho) é Model e Service do módulo.
- **MySQL 8 em tudo, inclusive nos testes** (ADR-002). Nunca SQLite.
- **Auditoria** em `audit_logs` com `ai_authorization_id` (o canal: nulo = tela), `outcome` (`success`/`denied`) e `changes` (`{"campo": [antes, depois]}`); gravada pelo `Platform\Services\AuditService`.
- **Morph map** (`AppServiceProvider::registerMorphMap`, `enforceMorphMap`): `audit_logs.auditable_type` guarda o alias (`user`, `task`…), não o nome da classe.
- **Passport 13** instalado (tabelas e chaves), com as rotas `/oauth/*` desligadas até o B8 (`Passport::ignoreRoutes`). `laravel/mcp` entra no B8.
- **Parâmetros** em `config/hub.php` + `.env` (T5); não há tabela de configurações.
- **Tema** da marca WCJ: `design/tokens.json` (a partir de `docs/brand/tokens.wcj.json`), fontes em `public/fonts` (`resources/css/fonts.css`). Cor, fonte e raio só por token. Marca no front pelo ADR-005:
  - dois azuis: `bg-primary` (`#1A6CF3`) em botão e foco, `text-primary-text` (`#0F4FC4`) em texto e link, nunca `text-primary`;
  - cantos de 5 px;
  - logo só pelo `WBrandLogoAtom`, com os SVG oficiais em `public/brand/`, que não se redesenham: completo e horizontal são o logo do produto, símbolo + "Hub" (ADR-006, gerado no mestre da marca), e o símbolo sozinho é o da WCJ;
  - eyebrow, rótulo e número em Space Mono;
  - padrão de nós só no painel do login;
  - e-mail com hex inline da marca (`resources/views/components/mail-layout.blade.php`).
- **Front em Atomic Design** (ADR-003, diverge do `.ai/stacks/ui.md` e do `laravel-inertia.md`, que ainda citam `components/ui/`, `layouts/` e o Select nativo):
  - primitivos do shadcn planos em `resources/js/components/shadcn/`; o `components.json` aponta para lá e o primitivo novo entra por `npm run shadcn -- add <nome>`;
  - componentes do Hub em `components/{atoms,molecules,organisms,templates,pages}/w-<nome>-<nível>/`, cada um com `.tsx`, `.md` (manual) e `.stories.tsx` quando há o que ver;
  - `resources/js/pages/*.tsx` só reexporta a tela de `components/pages/`;
  - os layouts são os templates (`w-app-layout-template`, `w-auth-layout-template`);
  - Select, Sidebar, Table, Pagination, toast (Sonner, no topo e centralizado) e campo de data (`w-date-picker-molecule`, sobre Calendar + Popover) são os do shadcn; nada de `<input type="date">`;
  - convenção completa em `resources/js/components/README.md`.
- **Storybook 10** (`npm run storybook`, porta 6006): catálogo dos componentes, com o `@inertiajs/react` trocado por `.storybook/inertia-mock.tsx` e dados de exemplo em `.storybook/fixtures.ts`. O CI roda `npm run build-storybook`.
- **Componentes e hooks do projeto**, além da tabela do template (`.ai/stacks/ui.md` §5, que não se edita aqui). Quem criar componente registra nesta tabela no mesmo commit (`ui.md` §5):

| Componente | Onde | Uso |
|---|---|---|
| `WListBodyMolecule` | `components/molecules/w-list-body-molecule/` | estados de erro ("Não foi possível carregar…", Tentar de novo) e carregando (`aria-busy`, `role="status"`) em volta da tabela de uma lista, com o `useListVisit` |
| `useListVisit` | `hooks/use-list-visit.ts` | carregando e erro só das visitas GET de uma lista (busca, filtro, página) |
| `WShowInactiveToggleMolecule` | `components/molecules/w-show-inactive-toggle-molecule/` | a chave "Mostrar desativados" das listas (F1 §05) |
| `AlphanumericCnpjInput` | `components/shadcn/masked-input.tsx` | CNPJ alfanumérico com máscara, maiúsculo (N5) |
| `deactivation()`, `situation()` | `lib/situation.ts` | "em DD/MM/AAAA por Fulano" e "Ativo" / "Desativado em…" / "Anonimizado", nas três tabelas e nos formulários |
| `WStatusPillAtom` `kind="contact"` e `kind="audit"` | `components/atoms/w-status-pill-atom/` | ativo / desativado / anonimizado do contato; ok / negado da auditoria |
| `WDataTableOrganism` `rowClassName` | `components/organisms/w-data-table-organism/` | linha negada em destaque na auditoria |
| `WRowActionsMolecule` `busy` e `disabled` | `components/molecules/w-row-actions-molecule/` | `busy`: esta linha roda (desabilita, esmaece, `aria-busy`); `disabled`: outra linha roda (só desabilita) |
| `useRowAction` | `hooks/use-row-action.ts` | a única trava de clique duplo das ações de linha: `post`/`del` ignoram o clique enquanto qualquer ação de linha roda (uma visita não-GET cancelaria a outra no Inertia 2), `isBusy(row)`/`isLocked(row)` alimentam o `WRowActionsMolecule`, `busy` é o `loading` dos `WConfirmDialogMolecule` |
| `WSearchInputMolecule` | `components/molecules/w-search-input-molecule/` | do template, mas aqui espera a escrita em voo terminar antes de buscar (CR-1-10) |
| `useContactActions` | `hooks/use-contact-actions.tsx` | criar, editar e as ações de linha do contato, as mesmas na lista geral e na ficha do cliente; devolve `openCreate`, `rowActions` e `dialogs` |
| `WDatePickerMolecule` | `components/molecules/w-date-picker-molecule/` | campo de data em `AAAA-MM-DD`, no lugar do `<input type="date">`; aceita `id`, `aria-invalid` e `aria-describedby` do `WFormFieldMolecule` |
| `dateBr()` | `lib/utils.ts` | data de negócio `AAAA-MM-DD` (coluna `date`) para a tela, `DD/MM/AAAA`, sem passar por `Date` (que leria meia-noite UTC e mostraria o dia anterior) |
| `WStatusPillAtom` `kind="project"` | `components/atoms/w-status-pill-atom/` | Aberto / Encerrado do projeto (P7) |
| `w-project-filters-organism`, `w-project-form-dialog-organism` | `components/organisms/` | busca e filtros da lista de projetos (F2 §05) e o modal de criar/editar (F2 §02); a tela é `components/pages/w-projects-page/` |
| **Kit do quadro (F3)** | `types/quadro.ts`, `lib/quadro.ts`, `.storybook/fixtures-quadro.ts`; `atoms/w-{task-key,priority-mark}-atom/`; `molecules/w-{people-avatars,task-marks,child-progress,card-counters,quick-create,board-order-toggle}-molecule/`; `organisms/w-{task-card,kanban-column,kanban-board,board-filters}-organism/`, `organisms/w-{pause-reason,rework,archive,support-card,new-sprint}-dialog-organism/`; `hooks/use-board-move.tsx`; `lib/dias-uteis.ts`; `pages/w-project-board-page/` | o quadro inteiro sem backend (seção "Kit do quadro" do `resources/js/components/README.md`): o B3 liga as rotas na página e manda as formas do `quadro.ts`. `classifyMove` escolhe o diálogo; a recusa é do servidor. `addBusinessDays`/`businessDaysBetween`: dias úteis seg–sex sem feriado (P29) em `AAAA-MM-DD`, sem `new Date()` |
| Tabelas e formulários do Núcleo | `components/organisms/w-{user,client,contact,audit-log}-table-organism/`, `w-{user,client,contact}-form-dialog-organism/`, `w-anonymize-contact-dialog-organism/`, `w-audit-log-filters-organism/`, `w-audit-log-detail-dialog-organism/` | as colunas do F1 §03 e os modais do F1 §02/§04, um por entidade; a tela (`components/pages/w-*-page/`) só junta |

## Como rodar

```bash
git clone git@github.com:WCJ-Tecnologia/wcj-hub.git && cd wcj-hub
composer setup                       # sobe o MySQL (docker compose), instala, migra, semeia, dado de exemplo, build
php artisan app:create-admin voce@wcjtecnologia.com.br   # imprime a senha uma vez; troca no 1º acesso
composer dev                         # servidor, fila, logs e Vite

composer lint && php artisan test    # MySQL 8 (wcj_hub_test); precisa do docker compose up -d mysql
npm run typecheck && npm run lint && npm run build
npm run storybook                    # catálogo dos componentes em http://localhost:6006
npm run e2e:prepare && npm run e2e:serve   # em outro terminal: npx playwright test
npm run test:guard
php artisan app:limpar-exemplo       # apaga o dado de exemplo ("[Exemplo] …", @exemplo.wcj.test)
```

Sem Docker: um MySQL 8 local com os bancos `wcj_hub`, `wcj_hub_test` e `wcj_hub_e2e` e o usuário do `.env` (ver o `mysql_init` do `docker-compose.yml`).

## Ambientes

| Ambiente | URL | Branch | Deploy |
|---|---|---|---|
| produção | `https://hub.wcjtecnologia.com.br` (a criar, B10) | `main` | `docs/DEPLOY.md` |
| homologação | a definir com Alaor Rodrigues (B10) | `main` | `docs/DEPLOY.md` |

## Autorização

Papéis em `roles` (`RoleEnum`, ids fixos 1 a 4); quadro completo em `docs/escopo.md` §4.

| Papel | Vê e faz |
|---|---|
| Administrador (`admin`) | Tudo: usuários, clientes, contatos, auditoria, listas gerais, qualquer projeto, conexões de IA de todos |
| Gestor de projetos (`project_manager`) | Tudo nos projetos que gere (`projects.manager_id` ou alocado com esse papel); contatos dos clientes desses projetos |
| Desenvolvedor (`developer`) | Projetos em que está alocado: cria card no Backlog, move a partir do To do, edita, comenta, anexa, lança as próprias horas, teste cruzado. Não exclui nem arquiva card; não mexe em sprint, pacote nem configuração |
| Comercial (`sales`) | Nada na fatia 1 (o papel existe para a v2) |

- Regra na Policy, `authorize()` em todo método; `abilities` no front só esconde botão. Ability nova: `User`, `HandleInertiaRequests`, `types/index.d.ts` e Policy no mesmo commit.
- Policy fora de `app/Policies`: registrar com `Gate::policy()` no `AppServiceProvider`.
- A IA conectada age como o usuário dono do token: mesmas Policies; escopo do token nunca passa do papel (IA5).
- Policies do Núcleo (B1): `UserPolicy`, `ClientPolicy`, `ContactPolicy`, `AuditLogPolicy`. De Projetos (B2): `ProjectPolicy` em `app/Domains/Projetos/Policies` (`viewAny`, `view`, `create`, `manage`, `delete`; "gere" = responsável ou alocado, com papel Gestor de projetos; `Project::scopeVisibleTo` espelha `view`), registrada no `AppServiceProvider`.
- Toda negação de `$this->authorize()` vira linha `outcome = denied` na auditoria, pelo `authorize()` do `App\Http\Controllers\Controller`. FormRequest que valida payload autoriza antes de validar, pelo trait `AuthorizesBeforeValidation` (mesmo caminho auditado), e a ação não chama `authorize()` de novo: sem isso, quem não pode recebe o erro de validação (que revela dado) e a tentativa não vira `denied`. Ability nova entra em `AuditActionEnum::forAbility()`; sem mapeamento, a negação grava o nome cru da ability e a tela mostra "Outra ação". `UserService::choiceList()` e `ClientService::choiceList()` conferem `viewChoiceList` dentro do Service (decisão do Lucas Scholz, 09/10): quem chamar sem permissão recebe `AuthorizationException` e a negação é auditada.
- "Gestor do cliente" (o gestor gere algum projeto do cliente) vem do contrato `App\Platform\Contracts\ClientManagement` (ADR-001): o Núcleo não lê `projects`. A implementação é `Projetos\Services\ProjectClientManagement` (B2: responsável ou alocado com papel gestor em algum projeto do cliente), ligada no `AppServiceProvider` pelo `give()` do `MemoizedClientManagement`, que memoriza "quem gere o quê" por requisição (as Policies perguntam linha a linha). A vazia `NoClientManagement` do B1 saiu. Lista pede o que é do contrato em lote (`openProjectsCountByClient`), nunca por linha. Teste de gestor usa o dublê `tests/Support/ClientManagementFalso.php`, que fica atrás da mesma memória.

## Regras de domínio

As regras são as G, N, P e IA da `docs/necessidade.md`; o funcional de cada bloco (`docs/funcional/`) manda em tela, campo, ação e permissão; o `docs/escopo.md`, em tabela, rota, Service e ordem. Antes de tocar num fluxo:

1. **Premissa de Projetos:** nenhuma regra nem coluna existe só para travar o fluxo (acima).
2. **Mesmo projeto (R15):** etapa, sprint, pacote, área, tarefa de origem, dev e QA de uma tarefa são do projeto dela. É **asserção no Service**, em toda gravação, coberta por teste. Não há FK composta.
3. **Chave da tarefa (T4):** número pelo contador atômico `UPDATE projects SET last_task_number = LAST_INSERT_ID(last_task_number + 1)`, na transação da criação. Nunca `max + 1`.
4. **Uma sprint aberta por projeto (P45), uma etapa ativa de `queue`, `sprint` e `support` por projeto (P11), uma conexão ativa por usuário e assistente (IA4):** as duas últimas o banco trava (colunas geradas + unique); a primeira é do Service, com a linha do projeto travada.
5. **Excluir quase nunca (G2, banco.md §4):** cadastro com histórico se desativa (`Deactivatable`), card com histórico se arquiva; só o nunca usado se exclui, com `deleteIfUnused()`. "Já usado" = alguma FK aponta para a linha (`isReferenced()` lê o catálogo do MySQL).
6. **Auditoria (G3):** toda escrita e toda negação em `audit_logs`, na mesma transação, com o canal. Nunca editar nem apagar, exceto a anonimização do contato (N12). Senha e token nunca em `changes`.
7. **Datas:** `datetime` em UTC (nunca `TIMESTAMP`); data de negócio (`date`) no fuso America/Sao_Paulo; dias úteis = segunda a sexta, sem feriado (P29).

## Módulos e flags

Nenhum módulo ligável na fatia 1. CRM (v2) e Chamados entram como `app/Domains/Crm` e `app/Domains/Chamados`, com migração aditiva.

## Testes técnicos

Pelo perfil de uso (`docs/escopo.md` §10): **arquitetura** sim (`tests/Arch`: A1 a A7, A9 "domínio não lê tabela de outro", A5 `RotasAutorizadasTest`); **banco de verdade** sim, em tudo (ADR-002); **benchmark** só no quadro (B3) e nos indicadores (B7), contagem de consultas com 500 cards; **carga** não. Teste de concorrência onde há contador ou "um só aberto" (`tests/Support/Concorrencia.php`). A régua (dívida, limites) só muda com aprovação do Lucas Scholz.

## O que exige aprovação humana neste projeto

Além do padrão (inegociável 5):

- **Toda migração.** As 27 tabelas vieram no B0, exatamente como o `docs/banco.md`. A diferença de 13/10 (`tasks.parent_task_id`, `tasks.cover_attachment_id`, `task_participants`; seção 7 do `banco.md`) entra no B3 como migração aditiva, só depois da conferência do Lucas Alencar. Coluna ou tabela que não está lá: pare e pergunte (o bloco não cria migração por conta própria).
- **Policy nova ou alterada**, e qualquer mudança em `abilities`.
- **Exclusão de verdade** (qualquer `delete` de registro de negócio) e a anonimização do contato.
- **OAuth e MCP:** escopos do Passport, ferramentas MCP, o que a IA pode escrever (`users.ai_write_allowed`, `ai_assistants.write_allowed`), limites do `config/hub.php`.
- Trilho completo (spec na skill + Verifier) em B1, B8, B9 e em toda Policy, exclusão de verdade e migração que apaga ou reescreve.

## Trilhos

Do `docs/plano-de-desenvolvimento.md` §5:

| | Trilho completo | Trilho leve |
|---|---|---|
| Quando | B1, B8, B9; em qualquer bloco: Policy nova, exclusão de verdade, migração que apaga ou reescreve | B2 a B7 |
| Spec | `.specs/features/<bloco>/` pela skill `tlc-spec-driven`, aprovada pelo Lucas Scholz antes do código | Na descrição do PR: objetivo, regras (códigos), o que não entra, premissas decididas |
| Verificação | Verifier em agente separado (PASS com evidência), mutação nas regras de permissão e exclusão, revisão em camadas | Teste por regra + uso no navegador com print; teste cruzado pelo outro dev |
| Critérios de piso (`.ai/core/especificacao.md` §6) | Sim | Sim (o "escopo por empresa" do piso vira "usuário não alocado no projeto") |

Dúvida de regra: Lucas Scholz, antes da spec. Branch `feat/b<n>-<nome>` a partir de `origin/main`.

## Um jeito só

- Papel: `RoleEnum` / `roles.slug`. Nunca o rótulo, nunca `user_type`.
- Ativo: `->active()` / `isActive()` do `Deactivatable`. Nunca `whereNull('deactivated_at')` à mão.
- Auditoria: `AuditService::log()`. Nunca `AuditLog::create()` fora dele (os testes podem). Negação: `AuditService::inspectFor()` (confere a Policy para um usuário e grava `denied`), chamado pelo trait `App\Http\Concerns\AuditsDeniedAbilities` (o `authorize()` do Controller base e o dos FormRequests) e pelos `choiceList()` dos Services. Nunca `AuditService::denied()` direto. Limpar dado pessoal (anonimização): `AuditService::scrubPersonalData()`.
- Desativar e reativar: ação própria na linha (`POST …/deactivate`, `…/reactivate`), nunca uma caixa "ativo" no formulário (D3 do B1).
- CNPJ: `App\Rules\AlphanumericCnpj` (alfanumérico, gravado sem máscara e em maiúscula). País: `App\Support\Paises`.
- Telefone de contato: texto como foi digitado (com o "+"), sem máscara do Brasil; a busca compara só os dígitos (D3 da review do B1).
- Domínio fechado: compare pelo id da constante do Model (`StageRole::QUEUE`, `TaskPriority::NORMAL`, `ContactChannel::WHATSAPP`) ou pelo slug; os ids são os da semente.
- Dado de exemplo: só pelo `ExemploSeeder`, marcado por `App\Support\Exemplo`.
- Componente: procure em `resources/js/components/` (leia o `.md` da pasta) antes de criar; primitivo só pelo `npm run shadcn -- add`; um `cn` só, o de `@/lib/utils`.
- Linha de lista para a tela: um Presenter em `app/Platform/Services` (`UserPresenter`, `ClientPresenter`, `ContactPresenter`, `AuditLogPresenter`) ou no módulo (`Projetos\Services\ProjectPresenter`), com o `can` decidido pela Policy; o controller só chama `->row()`. Nunca montar a linha no controller.
- Unique do banco em gravação simultânea: o trait `App\Platform\Services\Concerns\RefusesDuplicates` (`writeOrRefuseDuplicate`), que vira `ValidationException` só quando o índice violado é o esperado e relança o resto. Nunca `catch (UniqueConstraintViolationException)` solto.
- Busca por texto: `App\Support\Like::contains()`; nunca `"%{$termo}%"` à mão.
- Ativar e desativar: `setActiveLocked()` do `Deactivatable`, que abre a própria transação como o `deleteIfUnused` (savepoint quando quem chama já abriu uma), relê com `lockForUpdate`, decide pela linha travada, grava e chama o `$then($locked)` com a auditoria e o efeito próprio. Nunca `isActive()` da instância em memória antes da transação.
- Escrita que pode cair em deadlock (travas de usuário e de administradores): o trait `App\Platform\Services\Concerns\RetriesDeadlock` (`transactionRetryingDeadlock`), que repete só no 1213, até 3 vezes; o lock wait timeout (1205) não se repete. Todo callback repetido precisa ser repetível: de preferência relendo a linha com `lockForUpdate` dentro do callback e escrevendo nela (`UserService::resendInvite`, `setActiveLocked`); quando a escrita precisa da instância recebida, restaure-a (`getAttributes()`/`getRawOriginal()`) no início de cada tentativa (`UserService::update`).

## Armadilhas que já custaram tempo

- `datetimes()` do Laravel cria `created_at`/`updated_at` **nulos**; o `banco.md` pede not null. As migrações do Hub declaram `dateTime('created_at')` e `dateTime('updated_at')` à mão (as do framework e do Passport seguem com `datetimes()`).
- `task_acceptance_criteria.parent_key` é **VIRTUAL**, não STORED: com STORED o MySQL recusa a FK com cascade em `parent_id` (erro 1215). Coluna usada em CHECK não aceita FK com cascade ou set null (erro 3823).
- Coluna gerada (`single_role_key`, `active_assistant_id`, `parent_key`) nunca entra em `insert`/`update`: o MySQL recusa.
- `users` não tem `remember_token`: `Auth::attempt` sem "lembrar"; `$rememberTokenName = ''` no `User`.
- `users.password` é not null: o usuário convidado nasce já com a senha provisória (`TemporaryPasswordService::issue` grava a linha).
- Model fora de `App\Models` precisa de `newFactory()`, e entra no morph map; senão a factory não resolve e o `auditable_type` falha.
- O `.gitignore` antigo do repositório (só `docs/`) ignorava `*.json`; os JSON de `docs/brand/` entraram no B0.
- A trava global de segredo barra `.env.testing` e `.env.e2e.example` pelo nome: falso positivo (chave de teste e o MySQL local); commit com `--no-verify` documentado.

## Para ir mais fundo

| Assunto | Onde |
|---|---|
| Entrada e ordem de leitura | `docs/00-COMECE-AQUI.md` |
| Requisito e regras (G, N, P, IA) | `docs/necessidade.md` |
| Funcional por bloco | `docs/funcional/` |
| Escopo (blocos B0 a B10) | `docs/escopo.md` |
| Plano, rito do PR, trilhos | `docs/plano-de-desenvolvimento.md` |
| Banco | `docs/banco.md` |
| Marca | `docs/brand/README.md` |
| Deploy | `docs/DEPLOY.md` |
| Decisões | `docs/adr/` e `.specs/STATE.md` |
| Specs das features, retomada e lições | `.specs/` (`.ai/core/especificacao.md`) |
