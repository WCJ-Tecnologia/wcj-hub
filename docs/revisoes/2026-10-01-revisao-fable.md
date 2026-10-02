# Revisão de 01/10/2026: fatiar a v1 e simplificar

Revisão independente do WCJ Hub feita por Claude (Fable 5.1) a pedido de Lucas Scholz, com duas perguntas: o sistema de gestão de projetos é fácil de construir com o time que vai construir, e é fácil de usar no dia a dia do Rafael e dos devs. Foram lidos `necessidade.md`, `banco.txt`, as atas, os levantamentos, o `00-COMECE-AQUI.md` e o playbook `aplicacao-nova` do `ai-systems`.

**Situação:** PROPOSTA. Nada daqui foi aplicado na `necessidade.md` nem no `banco.txt`. Cada item (R1 a R17) tem uma linha na tabela de decisão do fim, para Lucas Scholz marcar. O que for aceito é aplicado nos dois documentos e registrado na tabela de decisões da `necessidade.md` (D-GER-23 em diante). Os códigos P, C, IA, D-GER e T são os da `necessidade.md` e do `banco.txt`.

## Veredito

O desenho de base está certo e fica: kanban sem trava, colunas por projeto com papel fixo, critério de aceite igual a item de teste, versão como entidade, auditoria em tabela única e a IA de cada usuário por MCP. O que pesa é o tamanho do que foi chamado de v1 e quatro regras que vão contra o uso diário. A proposta tem três partes: fatiar a v1, reverter quatro regras de uso e enxugar banco e escopo da primeira fatia.

## 1. Plano e uso

### R1. Fatiar a v1: só Núcleo mínimo + Projetos

**Hoje:** a v1 é Núcleo + Projetos + CRM + interface de IA com 24 ferramentas MCP + API REST com OAuth para o GPT "WCJ Hub" + convite de calendário e resumo diário por e-mail, tudo antes de alguém usar qualquer coisa.

**Proposta:** a primeira entrega é "o Trello da WCJ com horas", no quadro Wpay. Entram: Núcleo mínimo (usuários, papéis, clientes, contatos), Projetos inteiro e o servidor MCP com seis ferramentas (R12). O CRM vira a v2, com a própria rodada de necessidade e banco antes de construir; o GPT do Wilson vai junto com o CRM. O funil do DaaS, que está no ar, continua como está até lá: a D-GER-03 muda só no prazo, não na direção. O comitê e Lucas Alencar validam só essa fatia agora.

**Por quê:** execução por Lucas Scholz e Cauã (D-GER-17), prazo "o mais rápido possível" (D-GER-08) e três produtos de uma vez não sai. A ata do Rafael já diz que cada módulo tem a sua v1 (D-PRJ-06). Quando Projetos estiver em uso, as seções de CRM e de IA escritas em 24/09 estarão defasadas; é melhor revisá-las na hora de construir.

**O que muda nos docs:** a `necessidade.md` ganha a marca "v2" nas seções de CRM e nas ferramentas de CRM da IA14; o `banco.txt` mantém a seção 3 (CRM) como proposta não validada, fora da aprovação de agora.

### R2. Dev cria card no Backlog (reverte a P56)

**Hoje:** só gestor e Administrador criam card (P56); o dev executa.

**Proposta:** qualquer pessoa alocada cria card, que nasce no `Backlog`. Entrar na sprint continua sendo só do gestor e do Administrador (P45); excluir e arquivar também (P26).

**Por quê:** o item 20 da ata do Rafael é o problema que a P56 recria: o dev acha um problema, avisa alguém, a pessoa esquece, vira chamado. No Trello hoje qualquer um cria card. O controle que importa é o da sprint, e ele não muda. É mais coerente com a premissa ("na dúvida, fica a opção mais aberta").

**Custo:** nenhum no banco. Muda a Policy e o texto da P56; `escrever_tarefa` passa a valer para o dev também (G4).

### R3. E-mail ao atribuir e ao devolver (reverte a P22)

**Hoje:** aviso só dentro do sistema (P22).

**Proposta:** dois e-mails: quando a pessoa é definida como dev ou QA da tarefa, e quando a tarefa volta para desenvolvimento (retrabalho, P39). O aviso no sistema continua.

**Por quê:** sino dentro de um sistema que a pessoa abre uma vez por dia não resolve o item 20. O SMTP já é necessário no CRM (C9), e a WCJ decidiu em 01/10 usar o Zoho pago no sistema de obras; é a mesma configuração.

**Custo:** canal `mail` nas duas notificações do Laravel. Sem tabela nova.

### R4. Card tão rápido de criar quanto no Trello

**Hoje:** a tarefa é escrita no modelo de requisição (P17): Sendo / Quero / Para + critérios. No banco os campos são nulos, mas a regra e a tela podem induzir o formulário completo.

**Proposta:** regra de tela para o escopo (etapa 4): título + Enter cria o card no `Backlog`, com tipo "Melhoria" e prioridade "Normal". História de usuário, critérios, horas, dev, QA e área ficam recolhidos e opcionais. O modelo de requisição é a forma de escrever a tarefa de projeto de cliente, não a exigência de todo card.

**Por quê:** no Wpay a descrição mediana tem 230 caracteres e poucos cards usam história de usuário (`levantamento/trello-wpay.md`, item 7). Se o formulário pesar, o time volta ao Trello.

**Custo:** nenhum no banco. Vai para o escopo como regra de tela e para a P17 como "quando houver".

### R5. Sprint sem cerimônia

**Hoje:** a sprint é a única saída do `Backlog` (P45): o gestor cria a sprint e põe os cards nela.

**Proposta:** arrastar o card do `Backlog` para o `To do` é "pôr na sprint aberta" (a mesma ação do Service). Sem sprint aberta, o sistema abre o diálogo de criar sprint já preenchido (nome "Sprint N", início hoje, fim por `sprint_days`) e segue. O objetivo da sprint (`sprints.goal`) vira opcional.

**Por quê:** no Wpay, 10 de 21 saídas do `Backlog` pularam a Esteira. A sprint continua obrigatória e única por projeto; só a cerimônia some.

**A decidir:** sprint opcional por projeto para os projetos 100% IA de Lucas Scholz. Custa um boolean no projeto e um desvio no Service. Recomendação: começar sem e ver se o gesto resolve.

### R6. Trilho leve desde o primeiro bloco

**Hoje:** o padrão `ai-systems` vale por inteiro (D-GER-09, D-GER-17): toda feature com spec aprovada, Verifier com PASS, mutação e revisão em camadas.

**Proposta:** a regra adotada no sistema de obras em 01/10: trilho completo só em autenticação e papéis, Policies, OAuth e MCP, auditoria, desativação e exclusão. Kanban, card, comentário, anexo, horas, sprint e pacote seguem com spec no PR, teste e uso no navegador.

**Por quê:** sistema interno, 14 usuários, sem dinheiro. O trilho completo em tudo foi testado no P&P e pesou mais do que protegeu.

**Custo:** registrar como decisão (D-GER-23) e no `AGENTS.md` do projeto na etapa 3.

## 2. Cortes na fatia 1 (banco e escopo)

| | Hoje | Fatia 1 |
|---|---|---|
| Tabelas | 41 | 27 |
| Ferramentas MCP | 24 | 6 |

| # | Item | Hoje | Proposta | Por quê |
|---|---|---|---|---|
| R7 | CRM e `service_types` | 9 tabelas de CRM + `service_types` no Núcleo | Saem da fatia 1; voltam na v2 (R1) | Nada em Projetos lê essas tabelas |
| R8 | `task_validations` e os seis `requires_*` | Parecer por validação (code review, teste cruzado, QA); três flags no projeto e três na tarefa (P32) | Sai a tabela e saem os seis booleanos. Teste cruzado = `tasks.cross_tested_by` + `cross_tested_at` (nulos; o Service zera no retrabalho). Code review = o PR (P23). QA = o roteiro por critério, que já existe (P36). P33 a P35 ficam como prática | Sem trava, o parecer é registro sem consumidor. A P34 lê quem fez o teste cruzado, então a coluna é permitida pela D-GER-20 |
| R9 | `sprint_members` | Time da sprint com foto da carga e dias disponíveis (P47, P48) | Sai. Capacidade = `users.daily_hours` × dias úteis da sprint, por dev com tarefa na sprint | A capacidade é só referência (P49). Férias e folga entram depois, se alguém pedir, com a tabela de volta |
| R10 | `package_statuses` | Estados do pacote cadastráveis por projeto, com `is_production` (P42) | Sai. O estado se deriva de duas datas em `packages`: `homologation_started_at` e `released_at` (P44). As duas nulas = em montagem | Ninguém pediu estado de pacote configurável; a P42 veio da G1 aplicada em geral. Segue a convenção "derivar, não guardar" da seção 0 e tira uma tela de configuração por projeto |
| R11 | `rework_reasons` por projeto | Lista copiada para cada projeto, editada pelo gestor (P40) | Lista geral, editada pelo Administrador (G7) | Uma tela a menos por projeto, sem cópia na criação, sem FK composta |
| R12 | Ferramentas MCP | 24 (IA14) | 6: `minhas_tarefas`, `status_do_projeto`, `escrever_tarefa`, `mover_tarefa`, `comentar_tarefa`, `lancar_horas` | Cada ferramenta é Service + Policy + definição + teste. As outras entram com uso real |
| R13 | `ai_tool_calls` | Tabela com as chamadas de leitura, 180 dias, tela do admin (IA12) | Log em arquivo (canal `daily` do Laravel com rotação de 180 dias). A tela fica para quando alguém pedir | A escrita já está em `audit_logs` (IA11). O limite 60/20 roda no cache (T5) e não depende da tabela |
| R14 | `task_stage_movements` apontando para `audit_logs` | O movimento aponta para a linha de auditoria para saber quem moveu e por onde (T10) | `moved_by` (fk users) e `ai_authorization_id` (fk, nulo = tela) na própria linha; a auditoria continua gravando o movimento (G3) | A P14 lê quem moveu, então a coluna é permitida pela convenção da seção 0. A tela de histórico e `linha_do_tempo_da_tarefa` leem uma tabela só, e gravar o movimento não depende da ordem de gravação da auditoria |
| R15 | FKs compostas por projeto (T7) | `unique (id, project_id)` em 7 tabelas e FK composta em `tasks`, `task_stage_movements` e `packages`; `project_id` em `task_stage_movements` só para isso | Uma asserção no Service ("todos do mesmo projeto"), coberta por teste, se Lucas Alencar concordar | É o ponto mais pesado do banco para um time de dois. O Eloquent não usa chave composta em relação, então a garantia é só DDL, e com coluna nula o MySQL não confere (MATCH SIMPLE) |

Com R7 a R11 e R13 a fatia 1 fica com 27 tabelas. Núcleo, 8: `roles`, `users`, `clients`, `contacts`, `contact_channels`, `audit_logs`, `ai_assistants`, `ai_authorizations`. Projetos, 19: `projects`, `project_members`, `stage_roles`, `board_stages`, `task_types`, `project_areas`, `task_priorities`, `tasks`, `task_stage_movements`, `rework_reasons`, `task_acceptance_criteria`, `test_results`, `validation_types` (fica como a lista de origens de retrabalho), `task_pull_requests`, `task_comments`, `task_attachments`, `time_entries`, `packages`, `sprints`.

## 3. Indicadores (sem custo de banco)

- **R16. Atraso descontando a pausa.** A data prevista continua como na P28. Na conta do atraso (P53), o tempo em etapa de papel `paused` é descontado, já que ele é medido à parte (P15). Sem isso, toda tarefa que pausou por impedimento externo aparece atrasada.
- **R17. Realizado com fallback.** Previsto × realizado mostra as horas lançadas (P30) e, ao lado, o tempo em etapa de papel `development` calculado dos movimentos (dias úteis × carga do dev). Quando o dev esquece de lançar, o gestor ainda tem um número.

## 4. O que fica como está

Premissa sem trava; colunas por projeto com papel fixo (P10, P11); chave prefixo + número com contador atômico (P16, T4); critério de aceite como item do roteiro com evidência (P17, P36); pacote como entidade (P41); coluna `Support` no lugar de Chamados (P52); MySQL 8 nos testes (D-GER-22); colunas geradas já testadas no 8.4 (T1, T9); MCP por OAuth com `laravel/mcp` + Passport e a prova de conceito antes da etapa 4 (D-GER-17); GLPI em duas fases (D-CHM-01, opção C); hospedagem na VPS do DaaS (D-GER-14).

## 5. Decisão

| # | Item | Reverte ou altera | Decisão de Lucas Scholz |
|---|---|---|---|
| R1 | Fatiar a v1 | D-GER-03 (prazo) | |
| R2 | Dev cria card | P56 | |
| R3 | E-mail ao atribuir e devolver | P22 | |
| R4 | Card rápido | regra de tela; P17 "quando houver" | |
| R5 | Sprint sem cerimônia | regra de tela; `goal` opcional | |
| R6 | Trilho leve | D-GER-17 (alcance) | |
| R7 | CRM e `service_types` fora da fatia 1 | | |
| R8 | `task_validations` e `requires_*` | P32 | |
| R9 | `sprint_members` | P47, P48 | |
| R10 | `package_statuses` | P42 | |
| R11 | `rework_reasons` geral | P40 | |
| R12 | MCP com 6 ferramentas | IA14 | |
| R13 | `ai_tool_calls` em arquivo | IA12 | |
| R14 | `moved_by` no movimento | T10 | |
| R15 | FKs compostas viram asserção | T7 (com Lucas Alencar) | |
| R16 | Atraso descontando pausa | | |
| R17 | Realizado com fallback | | |

Aceito: aplicar na `necessidade.md` e no `banco.txt` (um commit cada), registrar D-GER-23 em diante e atualizar o `00-COMECE-AQUI.md`. Recusado: fica registrado aqui como avaliado.
