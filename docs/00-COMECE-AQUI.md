# Comece aqui: WCJ Hub

Documento de entrada. Quem abre este repositório, pessoa ou IA, lê isto antes de qualquer outra coisa.

## O produto em um parágrafo

Sistema interno da WCJ Tecnologia com três módulos sobre um núcleo comum: **Projetos** (substitui o Trello, com controle de tempo, num kanban sem trava no fluxo), **CRM** (leads, tratativas, compromissos; **v2**) e **Chamados** (último, sem demanda ainda). Cada usuário interno conecta a **própria IA** (Claude, Codex; ChatGPT) para consultar e alterar dados com as mesmas permissões da tela. **Fatia 1** (D-GER-24): Núcleo + Projetos + MCP com 6 ferramentas. Desenvolvido por **Cauã e Lucas Scholz, com IA** (D-GER-25; Vitinho e Lucas Varanda saíram em 14/10, D-GER-33, e Lucas Scholz assumiu os blocos do Vitinho em 15/10, D-GER-34), no padrão `ai-systems` 0.6.0 em trilho leve.

## Estado atual

Atualizado em 2026-10-06.

| Etapa (playbook `aplicacao-nova`) | Estado | Onde |
|---|---|---|
| 1. Necessidade | **Fatia 1 aplicada em 06/10** (D-GER-23 a D-GER-28): revisão de 01/10 aceita por inteiro (R1 a R17); CRM marcado como v2; P17, P22, P32, P34, P40, P42, P45, P47, P48, P53, P56 reescritas. Antes: Sessão C e D (24/09). Chamados é rascunho (D-GER-12) | `docs/necessidade.md` |
| 2. Banco | **APROVADO por Lucas Alencar em 06/10** (41 tabelas, D-GER-23). No mesmo dia virou a **fatia 1: 27 tabelas** (R7 a R15). A diferença (seção 7: R15 e as 3 colunas aditivas de 06/10) também foi aprovada por ele em 06/10. Migrações no PR #1 (B0). As colunas geradas foram testadas no MySQL 8.4. **PENDENTE: diferença de 13/10** (card pai, participantes, capa: 2 colunas em `tasks` + `task_participants`, 27 → 28; D-GER-32), a conferir por ele antes do B3 | `docs/banco.md` |
| Revisão das regras | **Encerrada.** Incorporada à `necessidade.md` na Sessão C; o arquivo `docs/regras-para-revisao.md` foi apagado (o git guarda as revisões 1 a 4, até o commit `da44c03`) | `docs/necessidade.md` |
| Funcionais | **Escritos em 06/10**, versão 0.1 para aprovação: F1 a F8 em `docs/funcional/` (D-GER-28). **F1 a F8 APROVADOS por Lucas Scholz em 06/10 (v1.0)**, com todos os pontos a validar respondidos; Rafael Garcia valida Projetos no uso (bugbash). Colunas aditivas decididas na aprovação: `tasks.position`, `tasks.proposed_hours`, `users.ai_write_allowed` (seção 7 do `banco.md`). Cada um tem a seção 10 "Pontos a validar" | `docs/funcional/README.md` |
| 4. Escopo | **VALIDADO por Lucas Scholz em 06/10 (etapa 5)**: blocos B0 a B10, apontando para os funcionais. Ele é o responsável por todas as travas da seção 7 | `docs/escopo.md` |
| Marca no template | **`docs/brand/` criada em 06/10**: logo em vetor, ícones, cores, nós, fontes, Brand Board e o rascunho `tokens.wcj.json` (D-GER-29). **Aplicado pelo Lucas Varanda no PR #8 (09/10)**: shadcn, Atomic Design, Storybook e marca (ADR-003, ADR-005). Ele saiu em 14/10 (D-GER-33): os componentes que faltam entram como kit por bloco. **Kit do quadro (PR #11) e kit da ficha + sino (PR #12) na `main` desde 14/10 e 15/10**, com o logo do Hub (PR #13): o front do B3 inteiro, sem backend. Próximo kit: roteiro de QA (F4), linha do tempo (F7), cartões de IA (F8), cada um antes do bloco | `docs/brand/README.md`, `resources/js/components/README.md` |
| Plano de desenvolvimento | **Escrito em 06/10**: ondas, divisão Cauã × Vitinho, rito do PR, trilhos, checklist do Lucas antes de começar | `docs/plano-de-desenvolvimento.md` |
| 3. Repositório | **B0 MERGEADO em 06/10 (PR #1)**: template `ai-systems` 0.6.0 por domínio, 27 migrações + 3 colunas de 06/10, Passport, sementes, MySQL 8 nos testes e no CI, tema da marca, `AGENTS.md`, ADR-001 e ADR-002. `composer setup` do zero em 32 s. **Na `main` desde 09/10:** B1 Núcleo (PR #5, #7), front atômico (PR #8) e B2 passo 1 (PR #6); docs de 13/10 (PR #9, #10). Próximo: **B3 (Cauã) sobre os dois kits: prompt pronto na Sessão F, abaixo** | https://github.com/WCJ-Tecnologia/wcj-hub |
| Teste técnico da IA (MCP) | A fazer, por Lucas Scholz (D-GER-17) | `docs/necessidade.md`, "Assistentes de IA" |
| `core/mcp.md` no padrão | **Publicado** no `ai-systems` 0.3.0 (PR #4) | `~/Documents/Projetos/WCJ/ai-systems/.ai/core/mcp.md` |

**Revisão de 01/10/2026** ([`docs/revisoes/2026-10-01-revisao-fable.md`](revisoes/2026-10-01-revisao-fable.md)): **aceita por inteiro por Lucas Scholz em 06/10** (R1 a R17) e aplicada na necessidade e no banco (D-GER-24 a D-GER-27). Fica como registro do porquê.

## Hierarquia dos documentos

1. **Tabela "Decisões registradas"** no topo do `docs/necessidade.md`: vence qualquer texto mais abaixo e qualquer ata. Guarda só decisões de projeto.
2. **`docs/necessidade.md`**: o requisito consolidado. As regras de produto são as G, N, P, C e IA; em Projetos, a premissa (kanban sem trava) vale acima de todas.
3. **`docs/banco.md`**: o modelo de dados aprovado; cada coluna aponta para a regra que a usa.
4. **`docs/funcional/`**: como cada bloco funciona na tela, aprovado por Lucas Scholz (D-GER-28, D-GER-31). Manda em tela, campo, ação e permissão.
5. **`docs/escopo.md`**: os blocos técnicos (tabelas, rotas, Services, ordem). Manda no técnico; não contraria o funcional.
6. **`docs/plano-de-desenvolvimento.md`**: quem faz o quê e o rito do PR.
7. **`docs/atas/`**: o que foi dito em cada reunião (fonte, não resumo final).
8. **`docs/levantamento/`**: análises de apoio (Trello, modelos, fluxo de desenvolvimento).
9. **`docs/pautas/`**: histórico de preparação; não é requisito. Os códigos antigos citados lá (RN-…, D-PRJ-…) não valem mais.
10. **`docs/revisoes/`**: revisões propostas, com tabela de decisão. A de 01/10 foi aplicada em 06/10.

## Decisões que foram revertidas (não usar)

Aparecem em atas, em pautas ou no histórico do git, mas **não valem mais**:

- Nome "WCJ Central" → **WCJ Hub** (D-GER-06).
- Integração com GitHub por webhook na v1 → **fora da v1, em avaliação** (D-GER-13). O PR entra só como link (P23).
- Migração de dados do DaaS (leads, oportunidades, contratos) → **sem migração** (C15). As funções vêm; os dados, não.
- Ordem Núcleo → Projetos → Chamados → CRM → **Núcleo → Projetos → CRM → Chamados** (D-GER-12).
- Fluxo travado (PR obrigatório, só o Tech Lead move) e depois "travas opcionais por projeto" → **kanban sem trava**: qualquer pessoa alocada move a partir do `To do`, e não há opção de exigir PR (premissa, P13, P23).
- Tarefa sem responsável não sai do Backlog → **tarefa sem dev, sem QA ou sem horas entra na sprint** (P20, P27).
- Capacidade da sprint recusa o que estoura → **só referência para o gestor** (P49).
- Sprint opcional por projeto → **sprint obrigatória**, única saída do Backlog, uma aberta por projeto (P45).
- Projeto "fora do cronograma" e "quantas sprints cabem" → **prazo do projeto pelo sistema fora da v1**; a data de entrega é só informação (P2, P9).
- Aviso só no sistema (P22 de 24/09) → **no sistema e por e-mail** ao definir dev ou QA e ao devolver (R3, 06/10).
- Só o gestor e o Administrador criam card (P56 de 24/09) → **qualquer pessoa alocada cria card no `Backlog`** (R2, 06/10).
- Flags de validação por projeto e por tarefa e parecer por validação (P32 de 24/09) → **sem flags nem parecer**; teste cruzado é marca na tarefa (R8, 06/10).
- Time da sprint com dias disponíveis (P47, P48 de 24/09) → **capacidade = carga × dias úteis da sprint**, sem time cadastrado (R9, 06/10).
- Estados de pacote cadastráveis (P42 de 24/09) → **estado derivado de duas datas** (R10, 06/10).
- Motivos de retrabalho por projeto (P40 de 24/09) → **lista geral do Administrador** (R11, 06/10).
- Tabela de chamadas da IA com tela (IA12 de 24/09) → **log em arquivo** (R13, 06/10).
- Movimento do card apontando para a auditoria (T10) → **`moved_by` + canal na própria linha** (R14, 06/10).
- FKs compostas por projeto (T7) → **asserção no Service** (R15, 06/10; a conferir pelo Lucas Alencar).
- v1 com CRM e GPT do Wilson → **fatia 1 = Núcleo + Projetos + MCP**; CRM é v2 (R1, D-GER-24).
- Execução Lucas Scholz + Cauã com Claude (D-GER-17) → **Cauã e Vitinho com IA, supervisão do Lucas Scholz** (D-GER-25).
- Excluir card por `deleted_at` (anexo da revisão 4) → **padrão 0.4.0**: card nunca usado se exclui; com histórico, se arquiva (P26). Lead se desativa (C16).
- Dev vê o próprio painel de produtividade → **só gestor e Administrador** (P54).
- Horas previstas informadas pelo dev, por pessoa, e várias pessoas por função na tarefa → **1 dev + QA; horas da tarefa inteira, opcionais ao criar, definidas ou validadas pelo gestor na sprint** (P20, P27).
- Tarefa usada e abandonada fica no Backlog → **arquivada com motivo** (P26).
- Preço da auditoria no sizing → **é o valor do lead**, obrigatório para ganhar (C2, C12).
- "Tipos de contrato" → **tipos de serviço**; contrato fora da v1 (N10).
- Testes em SQLite, como no template → **MySQL 8 em tudo**, local e no CI (D-GER-22).
- Tech Lead por projeto e modalidade "100% IA" registrados no sistema → **não ficam no sistema**: a equipe combina e o gestor acompanha (P4 e P6 saíram na Sessão D).
- Tabelas de escopos da IA (`ai_scopes`, `ai_authorization_scopes`) → **escopos no Passport**, que já os guarda em cada token (Sessão D).

## Próximas sessões

**Sessão A — `docs/banco.md` (etapa 2). FEITA em 24/09.** Proposta em `docs/banco.md`, atualizada na Sessão C.

**Sessão B — `core/mcp.md` no padrão. FEITA:** publicado no `ai-systems` 0.3.0 (PR #4).

**Sessão C — reescrever `necessidade.md` e `banco.md` a partir das regras aprovadas. FEITA em 24/09** (commits `070f138` e `357a3f2`). Prompt usado:

> No repositório `~/Documents/Projetos/WCJ/wcj-hub` (faça `git fetch` antes), leia `docs/00-COMECE-AQUI.md` e `docs/regras-para-revisao.md` (revisão 4), que tem as regras aprovadas pelo Lucas Scholz. Respeite a premissa do topo do arquivo: Projetos v1 é kanban simples, sem trava no fluxo; não crie regra nem estrutura de banco que trave. Etapa 1: reescreva `docs/necessidade.md` a partir dele. As regras de cada módulo passam a ser as do arquivo, com os mesmos códigos (G, N, P, C, IA), e substituem os antigos (RN-…, D-PRJ-…, D-CRM-…, D-NUC-…, D-IA-… de produto). A tabela de decisões fica só com as decisões de projeto (D-GER, D-IA-04, D-IA-07, D-CRM-04, D-CHM). Chamados continua como rascunho. Siga a estrutura do template `~/Documents/Projetos/WCJ/ai-systems/.ai/templates/necessidade.md`. Etapa 2: atualize `docs/banco.md`. Troque as referências pelos códigos novos e aplique o anexo "O que muda no banco.md" do arquivo de regras. Não decida as perguntas técnicas T6–T11, que são do Lucas Alencar. Não invente regra: o que faltar, pergunte. No fim, apague `docs/regras-para-revisao.md` (o git guarda) para não haver duas fontes, e atualize este arquivo. Um commit por etapa.

**Sessão D — revisão do `banco.md` pelo Lucas Scholz. FEITA em 24/09.** De 44 para 41 tabelas; o que saiu, o que entrou e o que ficou como está, cada item com o motivo, está no `banco.md`, seção 7, "Sessão D". Prompt usado:

> No repositório `~/Documents/Projetos/WCJ/wcj-hub` (faça `git fetch` antes), leia `docs/00-COMECE-AQUI.md`, as regras de `docs/necessidade.md` (a premissa de Projetos e as regras G, N, P, C e IA) e `docs/banco.md`. Vou revisar o banco antes de ele ir para o Lucas Alencar. Conduza a revisão por domínio, na ordem Platform, Projetos, CRM. Para cada tabela, em linguagem simples, diga para que serve, quais regras a usam e o que chama atenção: coluna sem regra que a use, trava que contradiga a premissa, dado repetido. Mostre um domínio por vez e espere a minha resposta antes de seguir. Não reabra o que já está decidido (tabela de decisões da `necessidade.md` e T1–T11 do banco), a não ser que eu peça. No fim, aplique no `banco.md` o que eu mudar, atualize este arquivo e faça um commit.

**Próximos passos, nesta ordem** (detalhe em `docs/plano-de-desenvolvimento.md`, seção 8):

1. ~~Funcionais~~ F1 a F8 aprovados por Lucas Scholz em 06/10. Rafael Garcia lê F2 a F7 e valida no bugbash.
2. ~~Banco~~ Lucas Alencar aprovou a fatia 1 inteira em 06/10.
3. ~~Escopo~~ validado por Lucas Scholz em 06/10 (etapa 5).
4. ~~B0~~ MERGEADO em 06/10 (PR #1). Decisões do PR aceitas pelo Lucas Scholz (primária clara `#0F4FC4`, usuários só admin, `composer audit` em PR próprio). Prompt usado abaixo, para referência.
5. **Onda 1:** B1 Núcleo (Cauã, trilho completo) ∥ B2 Projeto e configuração (Vitinho, leve), a partir desta `main`. **Revisão do Vitinho em 07/10, aplicada:** as Policies do B1 não leem `projects` (A9): contrato `Platform\Contracts\ClientManagement`, implementação vazia no B1 e real no B2; `ListService` mora em `Projetos\Services`; `rework_reasons.slug` (`other`) por migração própria; `ExemploSeeder` com o Paused em 4º (P10) até o B2 entregar o Service do quadro. Reunião de partida e `docs/brand/README.md` para o Lucas Varanda. Prova de conceito do MCP em paralelo (Lucas Scholz).
5. Prova de conceito do MCP (D-GER-17), em paralelo ao B0.
6. Reunião de partida com Cauã e Vitinho; onda 1 (B1 ∥ B2).

**Sessão F — B3 Quadro e card, PR 1 de 3 (Cauã). Prompt pronto (15/10):**

> No repositório `~/Documents/Projetos/WCJ/wcj-hub` (faça `git fetch` antes; branch `feat/b3-quadro-e-card` a partir de `origin/main`), leia nesta ordem: `docs/00-COMECE-AQUI.md`, o `AGENTS.md` (a seção "Stack e onde as coisas moram" e as linhas "Kit do quadro" e "Kit da ficha e do sino" da tabela de componentes), `docs/funcional/f3-quadro-e-card.md` inteiro (a seção 11 tem as decisões de 13/10 e 14/10), o bloco B3 do `docs/escopo.md`, as regras P13 a P26, P39, P40, P52 e P56 a P59 da `docs/necessidade.md`, e no `docs/banco.md` as tabelas `tasks`, `task_stage_movements`, `task_participants`, `task_types`, `project_areas` e a seção 7 (a diferença de 13/10 e 14/10, aprovada por Lucas Scholz em 15/10). Depois, o front que já existe: `resources/js/components/README.md` (seções "Kit do quadro" e "Kit da ficha do card e do sino"), `resources/js/types/quadro.ts` (o contrato: `BoardCard`, `BoardStage`, `BoardProject`, `BoardAbilities`, `BoardOptions`, `BoardFilters`, `MoveIntent`, `MovePayload`, `TaskDetail`), e o comentário do topo de `components/pages/w-project-board-page/w-project-board-page.tsx` e de `components/pages/w-task-detail-page/w-task-detail-page.tsx`, que listam as rotas que as duas telas chamam (proposta do kit: você confirma ou renomeia, ajustando a página e o `types/quadro.ts` no mesmo commit). Abra o Storybook (`npm run storybook`, `Pages/Project Board` e `Pages/Task Detail`) para ver o que o kit entrega: o front do B3 está pronto, sem backend; o seu trabalho é o servidor e a ligação. **PR 1 = quadro e card rápido:** a migração aditiva da seção 7 (`tasks.parent_task_id`, `tasks.cover_attachment_id`, `task_participants`, `task_types.project_id` + semente dos 5 tipos por projeto, `task_types.color`, `project_areas.color`, `tasks.task_type_id` e `tasks.task_priority_id` nulos), o `TaskPresenter` que cumpre `BoardCard`/`BoardProject`/`BoardAbilities`/`BoardOptions`, o controller do quadro com os filtros do F3 §05 (`BoardFilters`) e o Pronto só da sprint aberta e da última encerrada (P13), `TaskService` (criar rápido com o contador atômico T4 e sem tipo nem prioridade, P17/R4; mover gravando `task_stage_movements` com `moved_by` e `ai_authorization_id`, P14; `MovePayload.sequence` renumera `position` da coluna de destino, P13; sair do Backlog é pôr na sprint, só gestor e Administrador, e sem sprint aberta cria a do `new_sprint` na mesma transação, P45/R5; `pause_reason` quando o projeto exige, P15; retrabalho com origem e motivo ou `mistake`, zerando o teste cruzado, P39/P40/P37; prioridade, área e tipo pela etiqueta; participante, P58; criar e editar tipo e área do projeto pelo quadro, nome e cor), o card de suporte (P52), o aviso `TaskReturned` no sistema e por e-mail (P22, SMTP pelo `.env`, que o Lucas Scholz configura), a `TaskPolicy` (`create`, `view`, `update`, `move`, `manageSprint`, `archive`, `delete`, `comment`, `attach`, `logTime`; Comercial e não alocado = 403 auditado) e a asserção "mesmo projeto" em toda gravação (R15). Policy e migração são trilho completo: spec curta em `.specs/features/b3-quadro-e-card/` só para elas, aprovada por Lucas Scholz antes do código, Verifier em agente separado com mutação nas regras de permissão; o resto do bloco é trilho leve, com a spec na descrição do PR (modelo do plano, seção 6). Testes em MySQL 8: um de feature por regra, Policy (403 e nada muda), concorrência do contador T4 e do `position`, benchmark do quadro com 500 cards contando consultas (`ChildProgressQuery` numa consulta só; `docs/escopo.md` §10), e2e de fumaça do quadro (criar rápido, arrastar, diálogo de sprint). O *Montar sprint* do quadro (D-GER-35) já está no kit e a rota em lote já existe (`POST /projetos/{id}/sprint-aberta/tarefas/lote`, `SprintTaskService::addMany`, B5b): na página real, ligue o botão nessa rota (a página do kit já chama, com o `SprintBatchPayload`), mande `abilities.manage_sprint` e `project.sprints_count` e cubra no e2e de fumaça o fluxo marcar no Backlog → *Iniciar sprint*. Não mude o visual nem a API dos componentes do kit sem combinar com Lucas Scholz; o que faltar neles, registre no PR. **PR 2 = ficha completa** (o kit `w-task-detail-page` e as rotas do topo dele; avisos `TaskAssigned` ao definir dev e QA, P22) e **PR 3 = arquivar, desarquivar, excluir o nunca usado, sino de avisos e os pontos do F3 §10/§11 que sobrarem.** Teste cruzado dos seus PRs: Lucas Scholz (D-GER-34); você confere os dele. Escada local verde, prints no PR, CI verde; o merge é do Lucas Scholz.

**Sessão E — B0, repositório e base.** Prompt pronto:

> No repositório `~/Documents/Projetos/WCJ/wcj-hub` (faça `git fetch` antes; a `main` está só com `docs/`), leia `docs/00-COMECE-AQUI.md`, `docs/escopo.md` (bloco B0 e seções 2, 3 e 6), `docs/banco.md` (inteiro, inclusive a seção 9) e `docs/plano-de-desenvolvimento.md`. Aplique o template `ai-systems` 0.6.0 mantendo o histórico (receita que funcionou no projeto-oficinas: `git -C ../ai-systems archive HEAD | tar -x -C .`, depois `node scripts/new-project.mjs "WCJ Hub"`; o `.gitattributes` do template tem `/.github export-ignore`, então copie o `ci.yml` à mão; o archive sobrescreve `.gitignore` e `README.md`: reponha o que é do projeto). Reorganize por domínio (`app/Platform/`, `app/Domains/Projetos/`) e registre a ADR-001. Aplique a seção 9 do `banco.md` (roles no lugar de user_types; saem enterprises e o escopo por empresa; audit_logs com ai_authorization_id, outcome e changes) e confira o que a 0.6.0 mudou em relação ao que a seção 9 descreve, anotando no ADR. Crie as 27 migrações do `banco.md` na ordem das dependências, exatamente como escritas (colunas geradas, checks, uniques, `datetime` em tudo, `parent_key` VIRTUAL), mais Passport e notifications ajustados para datetime. Sementes dos domínios fechados, do cliente WCJ e do admin. Testes em MySQL 8 local e no CI (ADR-002), nunca SQLite. `composer setup` do zero em até 3 minutos, cronometrado. Preencha o `AGENTS.md` do projeto (vocabulário da necessidade.md, trilhos do plano, armadilhas da seção 9). Nada de tela nem de regra de negócio: isso é B1 em diante. Abra o PR, com a saída da escada e o tempo do setup na descrição.
