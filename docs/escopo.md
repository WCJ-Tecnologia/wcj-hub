# Escopo: WCJ Hub, fatia 1

Etapa 4 do `playbooks/aplicacao-nova.md` (padrão `ai-systems` 0.6.0). Gerado com apoio de IA em 06/10/2026 a partir de `necessidade.md`, `banco.txt` (aprovado por Lucas Alencar em 06/10, D-GER-23) e dos documentos funcionais de `docs/funcional/`. Depois de validado inteiro por uma pessoa (etapa 5), é a referência: a IA não desenvolve o que não está aqui, e nada aqui contraria o funcional do bloco.

Validado por: ____________ em ____-__-__.

## 1. Contexto em uma página

Sistema interno da WCJ Tecnologia, para até 14 usuários, que substitui o Trello na gestão de projetos e dá a cada usuário uma conexão da própria IA (Claude, Codex, ChatGPT) para consultar e alterar dados com as permissões da tela. A **fatia 1** (D-GER-24) entrega três coisas: o **Núcleo** mínimo (usuários, papéis, clientes, contatos, auditoria), o módulo **Projetos** inteiro (quadro kanban sem trava, card com documento de requisição e critérios, sprint, horas, data prevista, teste cruzado e roteiro de QA, pacote, indicadores) e o **servidor MCP** com 6 ferramentas. CRM, GPT "WCJ Hub" e Chamados ficam para depois.

Restrições estruturais que governam tudo:

- **Single-tenant** (`banco.txt` 6.1): não há `enterprise_id`. O escopo de acesso é papel + vínculo (alocação no projeto).
- **Kanban sem trava** (premissa da `necessidade.md`): o sistema nunca recusa mover card, pôr na sprint ou montar pacote por falta de dev, horas, PR ou parecer. Número de controle é referência. Recusa só por permissão, dado inválido e integridade do banco.
- **Banco enxuto** (D-GER-20): só as 27 tabelas do `banco.txt`. Campo novo exige regra que o leia e nova revisão.
- **Código por domínio desde o primeiro commit** (D-GER-15): `app/Platform/` e `app/Domains/Projetos/`, cada domínio com `Controllers`, `Models`, `Policies`, `Requests`, `Services` e `Mcp`.
- **Tela e IA chamam o mesmo Service** (IA5, `core/mcp.md`). Não existe regra só na tela nem só no MCP.
- **Auditoria em tabela única** com o canal (tela ou qual IA) em toda escrita e toda negação (G3, IA11).
- **MySQL 8 em tudo**, inclusive nos testes (D-GER-22). Data e hora em `datetime` UTC, nunca `TIMESTAMP`.

## 2. Fronteira: o que já existe

O template `ai-systems` 0.6.0 fornece e **não se reescreve**: autenticação com guard `web`, senha provisória e troca no primeiro acesso, limitador de login, papéis em tabela com objeto `abilities` compartilhado pelo Inertia, Policies com `authorize()` em todo método e o teste de arquitetura `RotasAutorizadasTest`, shell de navegação, gabarito de CRUD (`stacks/ui.md`), trait `Deactivatable` (desativar, reativar, `hasHistory`), `LocalTime`, auditoria base, headers de segurança, `DateTimeConventionsTest`, suíte e2e de smoke, CI, `composer setup`, skill `tlc-spec-driven` e o `AGENTS.md` padrão.

O que muda do template está na seção 9 do `banco.txt`: `user_types` vira `roles`; saem `enterprises` e `user_has_enterprises` (single-tenant), e com elas o escopo por empresa (`scopedEnterpriseIds`, `canAccessEnterprise`) e a tela de empresas, que serve de base para a de clientes; `audit_logs` ganha `ai_authorization_id`, `outcome` e `changes`; testes passam de SQLite para MySQL 8. Essa adaptação é o bloco B0.

## 3. Regras que valem para tudo

- Autorização em duas camadas: Policy no servidor em toda rota com ID; `abilities` no front só esconde botão (`core/seguranca.md`). Sem escopo por empresa; o escopo de Projetos é a alocação (`project_members`) e o papel.
- Regra de negócio no Service. Controller valida com FormRequest, autoriza e chama o Service. A tela só reflete. A ferramenta MCP chama o mesmo Service.
- Toda escrita grava em `audit_logs` na mesma transação, com `user_id`, `ai_authorization_id` (nulo = tela), `action`, `outcome`, `changes` e `ip_address`. Tentativa negada grava `outcome = denied`. Senha e token nunca entram em `changes`.
- Nada se exclui com `deleted_at`. Cadastro com histórico se desativa (`deactivated_at/by`), card com histórico se arquiva, e só o que nunca foi usado se exclui de verdade, conferido com a linha travada (`banco.md` §4).
- Datas de negócio (`date`) no fuso America/Sao_Paulo; dias úteis = segunda a sexta, sem feriado (P29).
- CRUD segue o gabarito (`.ai/stacks/ui.md`): listagem com busca e filtros, estados vazio, erro e carregando, modal ou página de criar e editar, confirmação em ação destrutiva.
- Trilho por bloco (D-GER-25, R6): **completo** (spec na skill, Verifier, mutação) em B1, B8 e B9 e em toda exclusão de verdade e Policy; **leve** (spec na descrição do PR, teste por regra, uso no navegador com print) no resto. Os critérios de piso de `core/especificacao.md` §6 (permissão, regra recusada, auditoria) entram em todo bloco, nos dois trilhos; o item 3 e 4 do piso (escopo por empresa) viram "usuário não alocado no projeto".
- Textos de tela em português do Brasil; nomes de etapa padrão em inglês (P10). Dinheiro não existe na fatia 1.

## 4. Papéis e acesso

| Papel (`roles.slug`) | Vê | Faz | Não faz |
|---|---|---|---|
| Administrador (`admin`) | Tudo | Tudo: usuários, clientes, contatos, auditoria, listas gerais, qualquer projeto, conexões de IA de todos | — |
| Gestor de projetos (`project_manager`) | Projetos que gere; lista de clientes e de usuários ativos (para escolher) | Tudo nos projetos dele: configuração, alocação, cards, sprint, pacote, indicadores; contatos dos clientes dos projetos dele | Criar cliente e usuário; auditoria geral; projetos de outro gestor |
| Desenvolvedor (`developer`) | Projetos em que está alocado | Cria card no `Backlog`; move a partir do `To do`; edita card, comenta, anexa, cola PR, lança e corrige as próprias horas; marca teste cruzado; roda o roteiro de QA quando é o QA | Excluir ou arquivar card; sprint, pacote e configuração; indicadores por pessoa de outros; Núcleo |
| Comercial (`sales`) | — | — (sem uso na fatia 1; o papel existe para a v2) | Projetos (C14) |

A IA conectada age como o usuário: mesmas Policies, e o escopo do token nunca passa do papel (IA5). Abilities da fatia 1 (nomes finais na spec do B1): `manage_users`, `manage_clients`, `manage_contacts`, `view_audit`, `manage_lists`, `manage_projects` (criar projeto), e por projeto, via Policy, `view`, `manage` (gestor e admin) e `work` (alocado). Adicionar ability: `User`, `HandleInertiaRequests`, `types/index.d.ts` e Policy no mesmo commit.

## 5. Blocos de trabalho

Um bloco = uma feature na skill `tlc-spec-driven` (trilho completo) ou um PR com spec na descrição (trilho leve). Cada bloco aponta para o funcional que o descreve na linguagem do usuário; **o funcional manda** em tela, campo, ação e permissão, e este escopo manda em tabela, rota, Service e ordem. Os requisitos com ID e os critérios em EARS nascem na spec, quando o bloco começa.

### B0. Repositório, migrações e base (etapa 3)

**Objetivo.** Repositório com o template 0.6.0 adaptado ao Hub, as 27 tabelas migradas e semeadas, CI em MySQL 8, subindo do zero em até 3 minutos.

**Dados.** Todas as tabelas do `banco.txt` (seções 1, 2 e 4), numa migração por tabela, na ordem das dependências; Passport e `notifications` publicados e ajustados para `datetime`. Sementes: `roles` (4), `stage_roles` (8), `task_priorities` (4), `test_results` (3), `validation_types` (4), `task_types` (4, com `improvement` e `support`), `rework_reasons` (5), `ai_assistants` (4), `contact_channels` (5), o cliente WCJ (N7, CNPJ 38.614.817/0001-22, não desativável) e o primeiro Administrador (`app:create-admin`). Dado de exemplo marcado como exemplo, com comando de limpeza.

**Telas.** Nenhuma nova. A tela de empresas do template sai; usuários passam a mostrar papel e carga diária (ajuste mínimo; a tela completa é B1).

**Regras.** Seção 9 do `banco.txt` (o que muda do template); T4 (contador atômico, ainda sem uso); T6 (MySQL nos testes: `phpunit.xml` e CI com serviço MySQL 8); as colunas geradas de T1 e T9 criadas exatamente como no `banco.txt` (`VIRTUAL` em `parent_key`).

**Permissão.** Não se aplica.

**Entrega.** `AGENTS.md` do projeto preenchido (vocabulário, armadilhas da seção 9, trilhos), ADR-001 (por domínio, D-GER-15), ADR-002 (MySQL nos testes, D-GER-22), tema da marca a partir de `docs/brand/tokens.wcj.json` e fontes em `public/fonts/` (Lucas Varanda, D-GER-29; pode ser PR separado logo depois do B0), `.env.example` completo com integrações desligadas, `docs/DEPLOY.md` esboçado, trava de segredo conferida, `composer setup` cronometrado. Testes de arquitetura: a regra "domínio não lê tabela de outro" entra aqui (ArchUnit do padrão `wcj-testes`).

### B1. Núcleo: usuários, clientes, contatos e auditoria

**Funcional.** [`docs/funcional/f1-nucleo.md`](funcional/f1-nucleo.md). **Trilho completo.**

**Objetivo.** O Administrador convida pessoas, cadastra clientes e contatos, desativa, reativa, anonimiza e vê a auditoria.

**Dados.** `roles`, `users`, `clients`, `contacts`, `contact_channels`, `audit_logs`.

**Telas.** `/admin/usuarios` (lista, convidar, editar, desativar, reativar, excluir, histórico); `/admin/clientes` e `/admin/clientes/{id}` com a aba de contatos; `/admin/contatos` (lista geral); `/admin/auditoria` (lista com filtros e detalhe antes/depois); `/perfil` (do template). Campos, colunas, filtros e mensagens: seções 02 a 07 do F1.

**Regras.** N1 a N9, N11, N12, G2, G3 em `Platform\Services\UserService` (convidar com e-mail de convite e senha provisória, notificação `UserInvited` no canal `mail`, reenviar convite, trocar papel, carga e a marca "IA pode escrever", desativar revogando sessões e conexões de IA, reativar, excluir só sem histórico), `ClientService` (CNPJ alfanumérico, unique por país, desativar sem trava), `ContactService` (aviso de e-mail repetido, anonimizar numa transação limpando `audit_logs.changes`), `AuditService` (gravação única usada por todos os Services; consulta com filtros).

**Permissão.** `UserPolicy` (só admin), `ClientPolicy` (`create`: admin e gestor; `update`: admin e gestor dos projetos do cliente; `deactivate` e `delete`: admin), `ContactPolicy` (gestor cria e edita contato nos clientes dos projetos dele), `AuditLogPolicy` (só admin). Dev vê nomes de usuários só pelas telas de Projetos.

### B2. Projeto e configuração do quadro

**Funcional.** [`docs/funcional/f2-projeto-e-configuracao.md`](funcional/f2-projeto-e-configuracao.md). **Trilho leve** (Policy e exclusão com critérios de piso).

**Objetivo.** O gestor cria o projeto, aloca pessoas e ajusta etapas, áreas e padrões; o Administrador edita as listas gerais.

**Dados.** `projects`, `project_members`, `stage_roles`, `board_stages`, `project_areas`, `task_types`, `rework_reasons`.

**Telas.** `/projetos` (lista: os que o papel vê); `/projetos/novo` e `/projetos/{id}/editar`, com o botão *Novo cliente* inline que chama o `ClientService` do Platform (N6); `/projetos/{id}/configuracao` com as abas Pessoas, Etapas, Áreas e Padrões; `/admin/listas` com Tipos de tarefa e Motivos de retrabalho. Encerrar e reabrir na ficha do projeto.

**Regras.** P1 (cliente fixo após criar; gestor responsável + gestores alocados), P2, P3 (prefixo congela após a 1ª tarefa), P5 (tirar pessoa limpa dev e QA das tarefas na mesma transação), P7, P8, P10 (nasce com as 8 etapas), P11 (papel fixo; uma etapa ativa de `queue`, `sprint` e `support`, travada pela coluna gerada `single_role_key`; "pelo menos uma" no Service), P12 (arquivar etapa com tarefas pede destino), P15 e P46 (padrões), P18, P40, G7, em `Projetos\Services\ProjectService`, `BoardStageService`, `ProjectAreaService` e `Platform\Services\ListService`.

**Permissão.** `ProjectPolicy`: `view` (admin, `manager_id`, alocado), `manage` (admin, `manager_id`, ou alocado com papel `project_manager`: pode haver mais de um gestor por projeto, P1/P5), `create` (admin, gestor). `client_id` só na criação (P1). Listas gerais: só admin.

### B3. Quadro e card

**Funcional.** [`docs/funcional/f3-quadro-e-card.md`](funcional/f3-quadro-e-card.md). **Trilho leve**, exceto excluir de verdade e arquivar (critérios de piso e teste de concorrência do contador).

**Objetivo.** O Trello da WCJ: quadro por projeto, card rápido, ficha completa, mover com histórico, pausa com motivo, retrabalho com origem e motivo, card de suporte, arquivar e excluir, avisos.

**Dados.** `tasks` (inclusive `position`, ordem manual, e `proposed_hours`, reestimativa proposta pelo dev), `task_stage_movements`, `task_priorities`, `task_acceptance_criteria`, `task_pull_requests`, `task_comments`, `task_attachments`, `validation_types`, `rework_reasons`, `contacts`, `contact_channels`, `notifications`.

**Telas.** `/projetos/{id}/quadro` (colunas = etapas ativas na ordem; arrastar e soltar; criar rápido no `Backlog`); `/projetos/{id}/cards/{chave}` (ficha: campos, requisição, critérios com subitens, PRs, comentários, anexos, dev/QA/horas previstas, sprint e pacote só leitura, botão Histórico que abre B7); diálogo de motivo da pausa; diálogo de origem e motivo do retrabalho; diálogo de arquivar com motivo; formulário do card de suporte; sino de avisos no shell.

**Regras.** P13 a P26, P39, P40, P52, P56, P7, P16 em `Projetos\Services\TaskService` (criar com contador atômico T4 e padrões `improvement`/`normal`; **asserção "mesmo projeto"** em toda gravação, R15; mover gravando `task_stage_movements` com `moved_by` e `ai_authorization_id`; pedir motivo da pausa e origem+motivo do retrabalho conforme os papéis das etapas, com a marca "foi engano" que grava o movimento sem retrabalho (P39); reordenar na coluna renumerando `position` (P13); propor, aprovar e recusar reestimativa (`proposed_hours` → `estimated_hours`, recalculando `due_on` via `DueDateService`, P27); coluna de pronto limitada à sprint aberta e à última encerrada (P13); zerar `cross_tested_*` e voltar critérios para `pending` na volta para desenvolvimento, P37; arquivar e desarquivar; excluir só o nunca usado com a linha travada; card de suporte na etapa `support`; bug de produção com `origin_task_id`), `CriterionService` (número estável T9, excluir só não testado), `AttachmentService` (apaga arquivo e linha juntos). Notificações `TaskAssigned` e `TaskReturned` nos canais `database` e `mail` (P22, R3), com SMTP Zoho da WCJ.

**Permissão.** `TaskPolicy`: `create` (alocado), `view` (alocado), `update` e `move` (alocado; sair do `Backlog` = `manageSprint`, gestor e admin), `archive`/`delete` (gestor e admin), `comment`/`attach`/`logTime` (alocado). Comercial e não alocado: 403, auditado.

### B4. Teste cruzado e roteiro de QA

**Funcional.** [`docs/funcional/f4-teste-e-qualidade.md`](funcional/f4-teste-e-qualidade.md). **Trilho leve.**

**Objetivo.** Outro dev marca o teste cruzado; o QA executa o roteiro por critério, com evidência, e aprova ou devolve.

**Dados.** `tasks` (`cross_tested_by`, `cross_tested_at`, `qa_id`, `developer_id`), `task_acceptance_criteria`, `test_results`, `task_attachments`.

**Telas.** Na ficha do card: bloco Teste cruzado (marcar e desmarcar) e bloco Roteiro de QA (item por critério: a testar, aprovado, reprovado, anexo de evidência). `/minhas-tarefas` ganha a aba "Para testar" (sou QA e a tarefa está em etapa de papel `test`).

**Regras.** P32 a P38 em `TaskService::markCrossTested` (recusa o dev da tarefa, P34) e `CriterionService::setResult` (critério editado volta para `pending`, P37; evidência é anexo ligado ao critério). Reprovar leva ao diálogo de retrabalho do B3 com a origem já preenchida e o destino padrão `To do` (P39). Mover para etapa de papel `done` com critério `failed` ou `pending` devolve um aviso que a tela mostra e deixa seguir (P38).

**Permissão.** `TaskPolicy::crossTest` (alocado, não o dev), `TaskPolicy::runQa` (alocado; o QA da tarefa é o caso normal, sem trava).

### B5. Sprint, horas e data prevista

**Funcional.** [`docs/funcional/f5-sprint-e-horas.md`](funcional/f5-sprint-e-horas.md). **Trilho leve**, com teste de concorrência em "uma sprint aberta por projeto".

**Objetivo.** Arrastar para o `To do` põe na sprint aberta (ou cria uma, já preenchida); o gestor encerra decidindo o destino de cada card; horas previstas, capacidade por dev, data prevista e horas realizadas.

**Dados.** `sprints`, `tasks` (`sprint_id`, `estimated_hours`, `due_on`, `developer_id`), `time_entries`, `users.daily_hours`, `projects.sprint_days`.

**Telas.** `/projetos/{id}/sprints` (aberta em destaque, encerradas abaixo, cards de cada uma, capacidade por dev); diálogo "criar sprint" proposto ao arrastar sem sprint aberta (R5); diálogo de encerrar com a lista de cards não concluídos e o destino de cada um (padrão: próxima sprint); horas previstas e data prevista na ficha do card (B3); lançamento de horas na ficha e em `/minhas-horas` (por dia, decimais, corrigir as próprias).

**Regras.** P27 a P31, P45, P46, P48 a P51, N3 em `SprintService` (criar; pôr e tirar card trocando a etapa de papel `queue`/`sprint`; uma aberta por projeto com a linha do projeto travada; encerrar criando a próxima, com início no 1º dia útil após o fim da anterior, e aplicando os destinos; reabrir só sem outra aberta, sem mover tarefa, P50), `DueDateService` (1ª entrada em etapa `development`: `estimated_hours ÷ daily_hours` em dias úteis seg–sex; recalcula só ao reestimar; nulo sem dev ou sem horas), `CapacityQuery` (por dev com tarefa na sprint: restante = previsto − lançado, mínimo 0, contra `daily_hours ×` dias úteis da sprint; só referência), `TimeEntryService` (0 < horas ≤ 24; dono corrige as próprias, gestor e admin qualquer).

**Permissão.** `SprintPolicy` (gestor e admin para criar, pôr, tirar e encerrar; alocado vê). `TimeEntryPolicy` (`create` alocado; `update` dono, gestor, admin).

### B6. Pacote (versão)

**Funcional.** [`docs/funcional/f6-pacote.md`](funcional/f6-pacote.md). **Trilho leve.**

**Objetivo.** O gestor junta tarefas numa versão, marca homologação e produção pelas datas, e devolve tarefas reprovadas na homologação.

**Dados.** `packages`, `tasks.package_id`, `task_stage_movements` (retrabalho de origem `homologation`).

**Telas.** `/projetos/{id}/pacotes` (lista com estado derivado); ficha do pacote (nome, data planejada, as duas datas, tarefas; pôr e tirar de qualquer etapa; "Reprovar na homologação" escolhendo as tarefas); pacote na ficha do card (B3).

**Regras.** P41 a P44, P39 em `PackageService` (estado = `homologation_started_at`/`released_at`; marcar e limpar datas; reprovar = mover as tarefas escolhidas para a etapa escolhida, padrão a de papel `sprint` (To do, P39), com `rework_origin_id = homologation` e motivo, sem tirar do pacote).

**Permissão.** `PackagePolicy` (gestor e admin gerenciam; alocado vê).

### B7. Indicadores e histórico

**Funcional.** [`docs/funcional/f7-indicadores.md`](funcional/f7-indicadores.md). **Trilho leve**, com teste por regra de cálculo (função pura por indicador, testada com movimentos de exemplo).

**Objetivo.** Linha do tempo do card; por projeto e por pessoa: tempo por etapa, previsto × realizado com fallback, atraso descontando pausa, tarefas paradas, bugs de produção.

**Dados.** Leitura de `task_stage_movements`, `time_entries`, `tasks`, `stage_roles`, `users.daily_hours`.

**Telas.** `/projetos/{id}/cards/{chave}/historico`; `/projetos/{id}/indicadores` (por tarefa e por pessoa); `/indicadores/pessoas` (admin: todos; gestor: pessoas dos projetos dele). O dev vê só a aba *Minhas tarefas e horas*: nos indicadores, nada que não seja dele (P54; `status_do_projeto` negado ao dev).

**Regras.** P11 (lê o papel da etapa), P14, P15, P25, P26, P53, R16, R17 em `Projetos\Queries\StageTimeQuery`, `DelayQuery` (atraso = hoje − `due_on` − dias em etapa `paused`), `EffortQuery` (lançado em `time_entries` e, ao lado, dias úteis em etapa `development` × `daily_hours`), `PausedTasksQuery`, `ProductionBugsQuery`. Tarefa arquivada sai de prazo e atraso; as horas dela ficam.

**Permissão.** `ProjectPolicy::viewIndicators` (gestor do projeto e admin) para as abas do projeto e para `status_do_projeto`; `viewPeopleIndicators` (gestor e admin); o dev só `/minhas-tarefas` e a linha do tempo dos cards dos projetos dele.

### B8. Conexões de IA e as 6 ferramentas MCP

**Funcional.** [`docs/funcional/f8-conexoes-de-ia.md`](funcional/f8-conexoes-de-ia.md). **Trilho completo** (`core/mcp.md`). Depende da prova de conceito (D-GER-17).

**Objetivo.** O usuário conecta Claude, Codex ou ChatGPT por OAuth, com escopos pelo papel; o servidor MCP expõe 6 ferramentas que chamam os Services dos blocos anteriores; o Administrador vê e revoga tudo.

**Dados.** `ai_assistants`, `ai_authorizations` (uma ativa por usuário e assistente, coluna gerada), tabelas do Passport, `audit_logs.ai_authorization_id`, log em arquivo (R13).

**Telas.** `/perfil/conexoes` (cartão por assistente: conectar, desconectar, o que pode); `/admin/conexoes` (todas, revogar); tela de autorização OAuth (nome do assistente, escopos). Endpoints: `/oauth/*` (Passport, OAuth 2.1 com PKCE; registro dinâmico ou pré-cadastro conforme a prova de conceito), `/mcp` (`laravel/mcp`, HTTP, só HTTPS).

**Regras.** IA1 a IA14, G4, N4 em `Platform\Services\AiAuthorizationService` (conectar revogando a anterior; revogar = `revoked_at` + tokens do Passport na mesma transação; revogar tudo ao desativar usuário ou trocar papel), escopos `core:read`, `projects:read`, `projects:write` em `Passport::tokensCan` com a relação papel × escopo no código (IA5; ChatGPT só `:read`; `users.ai_write_allowed = false` também só `:read`, IA4, e mudar a marca revoga as conexões), limite 60/20 por minuto por autorização no cache (T5), middleware que resolve o usuário e a autorização do token e os injeta no contexto de auditoria, log diário com rotação de 180 dias. Ferramentas em `Domains\Projetos\Mcp\`: `MinhasTarefas`, `StatusDoProjeto` (leitura), `EscreverTarefa`, `MoverTarefa`, `ComentarTarefa`, `LancarHoras` (escrita, marcadas como consequenciais, IA9), cada uma chamando o Service do bloco correspondente e devolvendo erro legível em negado, escopo insuficiente, limite e dado inválido.

**Permissão.** As mesmas Policies dos blocos B3 e B5, avaliadas para o usuário dono do token; `AiAuthorizationPolicy` (dono vê e revoga as próprias; admin todas).

### B9. Segurança (obrigatório antes da primeira publicação)

Checklist de `core/seguranca.md` §11 e `core/mcp.md`: `auth` em todo grupo, Policy em toda rota com ID (teste de arquitetura verde), FormRequest em toda entrada, limitador no login e no `/oauth/token`, headers, `APP_DEBUG=false`, `.env` de produção, upload de anexo com tipo e tamanho limitados e download pelo Service, revisão de segurança dirigida respondida, SCA do padrão `wcj-pentest` rodado.

### B10. Publicação

`docs/DEPLOY.md` (modelo do template): containers do Hub na VPS do DaaS, subdomínio `hub.wcjtecnologia.com.br`, MySQL 8 próprio com backup diário testado, Nginx com HTTPS, variáveis no servidor, SMTP Zoho, filas (`jobs`) com worker, ambiente de homologação público para o MCP (os assistentes chamam da nuvem). Definido com Alaor Rodrigues (D-GER-19). Deploy automático por merge na `main`, como no sistema de obras (ADR-003 de lá), depois que a homologação estiver estável.

## 6. O que não pode

Copiado e ampliado de `necessidade.md`:

- Trava no fluxo de Projetos: exigir dev, horas, PR, parecer ou capacidade para mover card, pôr na sprint, encerrar sprint ou montar pacote (premissa, P49).
- O dev excluir ou arquivar card, ou mexer em sprint, pacote e configuração (P26, P45). Criar card ele pode (P56).
- Coluna ou tabela fora do `banco.txt`: nada de `deleted_at`, `created_by`, cache de status, `enterprise_id`, `key` gravada, `project_id` em `task_stage_movements`, FK composta, tabelas de CRM, `task_validations`, `sprint_members`, `package_statuses`, `ai_tool_calls`, `settings` (parâmetros em `config/hub.php` + `.env`).
- Editar ou apagar `audit_logs`, exceto a anonimização do contato (G3, N12).
- Ferramenta de IA que exclua, altere em lote ou execute consulta livre (IA8); ferramenta além das 6 da fatia 1 (IA14); IA chamada pelo próprio sistema ou chave de provedor guardada (IA2); conexão por chave colada (IA4).
- E-mail além dos dois da P22; convite `.ics` e resumo diário são da v2.
- CRM, GPT "WCJ Hub", Chamados, contrato, GitHub por webhook, importação do Trello, WhatsApp, app nativo, cronômetro de horas, feriados, aprovação em dois níveis, ranking ou metas (G6, D-GER-13, D-GER-24).
- SQLite nos testes (D-GER-22). `TIMESTAMP` no MySQL. `timestamps()` do Laravel.
- Painel de produtividade por dev, PDF de histórico, relatório de fechamento, notas de versão (P54, P55).
- Cor, fonte ou raio fora de `design/tokens.json`; logo ou cor copiada à mão. A marca WCJ entra no Hub (é sistema interno) só pelos arquivos de `docs/brand/` e pelo `tokens.json` (D-GER-29).

## 7. Travas

O que para o desenvolvimento e exige humano:

| Trava | Quem resolve | Bloqueia |
|---|---|---|
| Funcionais F2 a F7 aprovados por Rafael Garcia; F1 e F8 por Lucas Scholz (D-GER-28). Os "pontos a validar" de cada um respondidos. | Rafael, Lucas Scholz | A spec de cada bloco. B0 não espera. |
| Lucas Alencar confere a diferença da fatia 1 (`banco.txt` §7), em especial a R15. Se ele preferir as FKs compostas, elas voltam como migração aditiva, sem mudar Service. | Lucas Alencar | Nada: B0 segue com a asserção no Service. |
| Prova de conceito do MCP (D-GER-17): OAuth com Claude app, Claude Code, Codex, ChatGPT Pro; identificação de cada cliente; Claude app e Claude Code na mesma conexão. | Lucas Scholz | B8 |
| Credenciais do SMTP Zoho da WCJ (as mesmas do sistema de obras). | Lucas Scholz | O canal `mail` do B3 (o `database` não espera). |
| Acesso à VPS do DaaS, capacidade e separação do banco; DNS do subdomínio. | Alaor Rodrigues, Lucas Scholz | B10 e a homologação pública do B8. |
| Diferenças do template 0.6.0 em relação à seção 9 do `banco.txt` (escrita sobre a 0.4.0). | Quem fizer o B0 | B0: conferir e anotar no ADR. |

## 8. Ordem de execução

Duas pessoas (Cauã e Vitinho) em paralelo, com Lucas Scholz supervisionando. Detalhe de quem faz o quê e do rito do PR em [`docs/plano-de-desenvolvimento.md`](plano-de-desenvolvimento.md).

1. **B0** repositório, migrações e base (depende de nada). Em paralelo, a prova de conceito do MCP.
2. **B1** Núcleo ∥ **B2** Projeto e configuração (dependem de B0).
3. **B3** Quadro e card (depende de B1 e B2) ∥ **B5** Sprint e horas (depende de B2; a integração "arrastar para o To do" fecha quando B3 entrar).
4. **B4** Teste e QA e **B6** Pacote (dependem de B3) ∥ **B7** Indicadores (depende de B3 e B5).
5. **B8** Conexões de IA (depende de B3, B5 e da prova de conceito).
6. **B9** Segurança, antes da primeira publicação.
7. **B10** Publicação em homologação; bugbash com o Rafael no quadro Wpay; produção; o Trello para de ser usado.

Com B0 a B3 no ar em homologação, o quadro Wpay já pode ser usado para valer, e o resto entra por cima.

Em paralelo às ondas, **Lucas Varanda** cuida do front: tema da marca e fontes (com o B0), shell e componentes que o template não tem (kanban com arrastar e soltar, ficha do card com seções recolhidas, roteiro de QA por item, linha do tempo), propostos antes de B3 e B5 começarem (`docs/brand/README.md`, D-GER-29).

## 9. Fora deste escopo

- **v2:** CRM inteiro (seção 3 da `necessidade.md` e do `banco.txt`), `service_types`, GPT "WCJ Hub" com Actions, convite `.ics`, resumo diário, as ferramentas de IA de CRM. Terá a própria rodada de necessidade, banco e funcional.
- **Depois da fatia 1, com os dados já gravados:** as 9 ferramentas de IA de Projetos e Núcleo que ficaram para depois (IA14), painel de produtividade e retrabalho (P54), histórico de desenvolvimento em PDF, relatório de fechamento, notas de versão e documento de requisição gerado (P55), tela das chamadas da IA (IA12), time da sprint com férias e folga (P47, P48), feriados (P29).
- **Chamados** (D-GER-12, D-CHM-01) e contrato (N10).
- Integração com o GitHub (D-GER-13), importação do Trello, DaaS consultando contratos (D-CRM-04).

## 10. Perfil de uso e testes técnicos

- **Volume:** 14 usuários, 6 clientes, uns 10 projetos ativos. Tabelas que mais crescem: `audit_logs` (toda escrita; estimativa de 200 a 500 linhas por dia útil), `task_stage_movements` (50 a 150 por dia), `time_entries` (30 a 80 por dia). Em 12 meses, nenhuma passa de 150 mil linhas. Estimado, não medido.
- **Usuários:** internos; média de 5 simultâneos, pico de 14. A IA soma, no máximo, 60 leituras e 20 escritas por minuto por conexão (IA7).
- **Picos:** nenhum por calendário. Encerramento de sprint move dezenas de cards de uma vez, numa transação.
- **SLA:** nenhum formal. Sistema interno; indisponibilidade atrapalha, não para cliente.
- **Motor de cálculo, importação ou relatório pesado:** as consultas de indicadores (B7) leem `task_stage_movements` por projeto; os cálculos são funções puras com teste. Nenhuma importação.

| Teste | Vale? | Por quê |
|---|---|---|
| Arquitetura | sim | todo projeto; aqui também a regra "domínio não lê tabela de outro" e `RotasAutorizadasTest` |
| Banco de verdade | sim | MySQL 8 em todo teste, local e no CI (D-GER-22); as colunas geradas e o `LAST_INSERT_ID(expr)` só existem lá |
| Benchmark | parcial | só o quadro (B3) e os indicadores (B7): contagem de consultas (N+1) com um projeto de 500 cards semeado; sem meta de tempo |
| Carga | não | 14 usuários internos; nenhum pico. Volta se o produto for revendido |
