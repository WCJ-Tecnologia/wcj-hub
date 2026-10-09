# F7. Indicadores e histórico

**Documento funcional.** Versão 1.0, de 06/10/2026. **Aprovado por Lucas Scholz em 06/10/2026**, com os pontos a validar respondidos (seção 10). Rafael Garcia valida o uso no bugbash. Estrutura e fluxo em [`README.md`](README.md).

| | |
|---|---|
| **Objetivo** | Mostrar ao gestor e à equipe o que aconteceu com cada card e quanto tempo levou: a linha do tempo do card, o tempo por etapa, as horas previstas × realizadas, a data prevista e o atraso, por projeto e por pessoa. Nada aqui se digita: tudo sai do que o quadro e o lançamento de horas já gravam. |
| **Regras que alimentam** | P11, P14, P15, P25, P26, P53 a P55 (`docs/necessidade.md`), com R14, R16 e R17 da revisão de 01/10. Usa a data prevista e as horas de [F5](f5-sprint-e-horas.md) (P27 a P30) e o retrabalho de [F3](f3-quadro-e-card.md) (P39). |
| **Tabelas do `banco.txt`** | Só leitura: `task_stage_movements`, `time_entries`, `tasks` (data prevista, arquivamento, tarefa de origem), `stage_roles`, `board_stages`, `users` (carga diária), `validation_types` e `rework_reasons` (origem e motivo do retrabalho). |
| **Depende de** | F3 (movimentos do card e botão *Histórico*), F5 (horas previstas, horas lançadas, data prevista, sprint). |
| **Quem aprova** | Lucas Scholz (D-GER-31). Rafael Garcia, gestor de projetos (D-GER-02), participou com feedback e elaboração e valida o uso no bugbash. |

## 01. Fluxo funcional

**Histórico do card.** Em qualquer card, o botão *Histórico* (F3) abre a linha do tempo daquele card. Ela mostra cada mudança de etapa: quem moveu, quando, de onde, para onde e por qual canal, a tela ou qual IA (P14, R14). Nas voltas para desenvolvimento aparecem a origem e o motivo do retrabalho (P39); nas pausas, o motivo (P15). Cada linha diz quanto tempo o card ficou na etapa. No topo, um resumo: etapa atual, data prevista, atraso, horas e tempo por etapa. É a cronologia que o Rafael hoje monta à mão para contar ao cliente.

**Indicadores do projeto.** No quadro do projeto, o gestor abre *Indicadores*. A tela tem cinco abas: *Situação*, *Tempo por etapa*, *Horas*, *Por pessoa* e *Qualidade*. Ele escolhe a sprint (padrão: a sprint em andamento) e lê. Toda linha que cita uma tarefa abre o card; do card, o *Histórico*. O dev abre a mesma tela e vê uma aba só: *Minhas tarefas e horas*. Nos indicadores, o dev não vê nada que não seja dele (decisão de 06/10); o quadro (F3) e a linha do tempo dos cards ele continua vendo.

**Pela IA.** A pessoa pergunta ao assistente dela. `status_do_projeto` devolve o mesmo que a aba *Situação*, sem as pausas; `minhas_tarefas` devolve o mesmo que *Minhas tarefas e horas*, juntando os projetos da pessoa ([F8](f8-conexoes-de-ia.md)). As outras ferramentas de leitura destes dados ficam para depois da fatia 1 (IA14).

## 02. Telas e campos

As telas deste bloco são só de leitura: nenhum campo se preenche. As tabelas abaixo dizem o que cada informação mostra e de qual regra vem.

### Linha do tempo do card: resumo no topo

| Informação | O que mostra | Regra |
|---|---|---|
| Etapa atual | Nome da etapa e desde quando o card está nela. | (P14) |
| Data prevista | DD/MM/AAAA, ou "Sem data prevista" com o que falta: dev, horas, ou o card ainda não entrou em desenvolvimento. | (P28, F5) |
| Atraso | "N dias úteis de atraso, descontada a pausa" ou "No prazo". Não aparece em card pronto nem arquivado. | (P53, R16, P26) |
| Concluída em | Data da última chegada a uma etapa de papel pronto. Só no card pronto. | (P53) |
| Horas previstas | O total Dev + QA. | (P27) |
| Horas lançadas | A soma de todos os lançamentos do card. O detalhe por pessoa e por dia fica no card (F5). | (P30) |
| Tempo em desenvolvimento | Dias úteis em etapa de desenvolvimento × carga diária do dev da tarefa, em horas, com a marca "estimado pelo quadro". Sem dev ou sem carga: "—". | (R17) |
| Tempo por etapa | Uma linha por papel de etapa por onde o card passou, com o tempo somado. A pausa aparece em linha própria. | (P11, P15) |
| Bug de produção de | Se este card é bug de produção, a chave da tarefa original, que abre o card. | (P25) |
| Bugs de produção ligados | Se outros cards apontam para este como original, as chaves deles. | (P25) |
| Arquivado | Data e motivo, se o card foi arquivado. | (P26) |

### Linha do tempo do card: cada movimento

| Informação | O que mostra | Regra |
|---|---|---|
| Data e hora | DD/MM/AAAA HH:MM, no horário de Brasília. | (P14) |
| Quem | Nome de quem moveu; "(desativado)" ao lado, se a pessoa foi desativada depois. | (P14) |
| Canal | Tela, ou o assistente: Claude, Codex, ChatGPT. | (IA11, R14) |
| De → Para | Nome das etapas. A primeira linha é a criação: "Criou o card em Backlog" (ou em Support, no card de suporte). Etapa removida depois aparece com o nome que tinha e a marca "(removida)". | (P14, P12) |
| Tempo na etapa | Quanto tempo o card ficou na etapa de destino até o movimento seguinte; na última linha, "até agora". | (P53) |
| Retrabalho | Só nas voltas que contam como retrabalho: a origem (code review, teste cruzado, QA ou homologação) e o motivo, com o texto quando o motivo é "outro". | (P39, P40) |
| Motivo da pausa | Só na entrada em etapa de pausa. "Sem motivo" quando o projeto desligou o motivo. | (P15) |

Pôr e tirar da sprint, encerrar a sprint e desarquivar aparecem como movimentos comuns, sem marca de retrabalho (P26, P45, P50). Pôr o card de suporte na sprint não troca de etapa e por isso não aparece aqui (P52).

### Indicadores do projeto

Cinco abas para o gestor e o Administrador, duas para o dev. O conteúdo de cada uma está na seção 06; as colunas, na 03.

## 03. Listagem

| Lista | Colunas | Ordem padrão | Observação |
|---|---|---|---|
| Linha do tempo do card | As da seção 02 | Do mais antigo para o mais recente | Lê como a história do card. Vazia nunca fica: sempre há a criação. Só com ela: "Nenhum movimento além da criação". |
| Situação: tarefas por etapa | Etapa, Quantidade | Ordem do quadro | Conta os cards fora do arquivo. |
| Situação: atrasadas | Chave, Título, Dev, Data prevista, Tempo em pausa, Atraso | Maior atraso primeiro | Vazia: "Nenhuma tarefa atrasada". |
| Situação: próximas datas | Chave, Título, Dev, Data prevista | Data mais próxima primeiro | Tarefas abertas com data prevista ainda não vencida. Vazia: "Nenhuma data prevista pela frente". |
| Situação: em pausa | Chave, Título, Dev, Motivo, Pausada desde, Quem pausou | Pausa mais antiga primeiro | Vazia: "Nenhuma tarefa em pausa". |
| Situação: sem data prevista | Chave, Título, O que falta (dev, horas) | Chave | Só tarefas que já entraram em desenvolvimento. Vazia: "Toda tarefa em andamento tem data prevista". |
| Tempo por etapa | Papel da etapa, Etapas do quadro com esse papel, Tarefas que passaram, Tempo somado, Tempo médio por tarefa | Ordem do quadro | Vazia: "Nenhuma tarefa saiu do Backlog com esses filtros". |
| Horas | Chave, Título, Dev, Etapa atual, Previstas, Lançadas, Diferença, Tempo em desenvolvimento (estimado) | Chave | Linha de total no fim. Arquivada aparece com a marca "arquivada". Vazia: "Nenhuma tarefa com horas previstas ou lançadas com esses filtros". |
| Por pessoa | Pessoa, Carga diária, Tarefas como dev, Previstas, Lançadas por ela, Tempo em desenvolvimento (estimado), Atrasadas, Em pausa | Nome | Uma linha "Sem dev" junta as tarefas sem dev. Vazia: "Nenhuma tarefa com dev com esses filtros". |
| Qualidade: bugs de produção | Bug (chave e título), Tarefa original, Criado em, Etapa atual | Mais recente primeiro | Total no topo. Vazia: "Nenhum bug de produção registrado neste projeto". |
| Minhas tarefas e horas | Chave, Título, Meu papel na tarefa (dev ou QA), Etapa, Data prevista, Atraso, Previstas, Lançadas por mim, Tempo em desenvolvimento (estimado, só onde sou dev) | Atrasadas primeiro, depois data prevista | Tarefas abertas em que a pessoa é dev ou QA. Vazia: "Nenhuma tarefa sua neste projeto". |

## 04. Ações

| Ação | Quem | O que faz | Confirmação / aviso |
|---|---|---|---|
| Histórico | Quem vê o card (seção 09) | Abre a linha do tempo do card. | |
| Ver na auditoria | Administrador | Na linha do tempo, abre a auditoria filtrada no card ([F1](f1-nucleo.md)), com o que a linha do tempo não mostra: edições de campos, reestimativas, trocas de dev e de QA (G3). | |
| Indicadores | Quem vê o projeto | Abre a tela de indicadores do projeto: gestor e Administrador na aba *Situação*; o dev, direto em *Minhas tarefas e horas*. | |
| Abrir card | Quem vê o card | Em qualquer lista, a chave abre o card. | |
| Trocar sprint e filtros | Quem vê a aba | Refaz as contas com o filtro escolhido (seção 05). | |

Nada nesta tela altera dado. Pela IA, na fatia 1:

| Ferramenta | Tipo | O que devolve | Na fatia 1? |
|---|---|---|---|
| `status_do_projeto` | leitura | Tarefas por etapa, atrasadas e próximas datas previstas: a aba *Situação* sem as pausas. | Sim ([F8](f8-conexoes-de-ia.md)) |
| `minhas_tarefas` | leitura | As tarefas abertas da pessoa, com filtro por prazo e projeto. | Sim (F8) |
| `linha_do_tempo_da_tarefa`, `horas_do_projeto`, `retrabalho_do_projeto`, `tarefas_paradas` | leitura | Linha do tempo, horas, voltas e pausas. | Não: depois da fatia 1, com uso real (IA14). |

## 05. Filtros e busca

| Tela | Busca | Filtros |
|---|---|---|
| Linha do tempo do card | | Nenhum: mostra todos os movimentos do card. |
| Situação, Qualidade | | Nenhum: sempre o projeto inteiro, como `status_do_projeto`. |
| Tempo por etapa, Horas, Por pessoa | Chave ou título | Sprint (padrão: em andamento; cada sprint; Todas; Fora de sprint); Pessoa (dev da tarefa); Tipo de tarefa; Área. |
| Minhas tarefas e horas | Chave ou título | Prazo (atrasadas; vencem até uma data; sem data prevista). |

A tarefa conta na sprint em que está agora. A que passou de uma sprint para a seguinte aparece só na seguinte; a tarefa pronta fica na sprint em que foi feita (P50, P51).

## 06. Indicadores

### Como as contas são feitas

- **Dia útil:** segunda a sexta, no horário de Brasília. Feriado conta como dia útil na v1 (P29).
- **Tempo numa etapa:** do movimento que levou o card para a etapa até o que o tirou dela (ou até agora), contando só os dias úteis. Mostrado em dias úteis com uma casa decimal: 1,5 dia útil é um dia e meio de segunda a sexta (ver 10.1).
- **Pelo papel, não pelo nome (P11):** o tempo por etapa soma por papel (fila, sprint, desenvolvimento, code review, teste, pausa, suporte). Renomear a etapa não muda a conta, e duas etapas com o mesmo papel somam juntas. O papel pronto fica fora, porque não tem fim.
- **Data prevista:** vem de F5. É calculada na primeira entrada em desenvolvimento e só muda se o gestor reestimar; nem pausa nem retrabalho a empurram (P28).
- **Atraso (P53, R16):** dias úteis passados depois da data prevista, contando hoje, menos o tempo em etapa de pausa desde a primeira entrada em desenvolvimento. Se o resultado passa de zero e o card não está pronto, a tarefa está atrasada. Exemplo: prevista para sexta, 09/10; hoje é quarta, 14/10, então são 3 dias úteis passados; o card ficou 1,5 dia útil em pausa; atraso de 1,5 dia útil. Enquanto o card está em pausa, o atraso não cresce.
- **Horas lançadas e diferença:** a soma dos lançamentos (P30). Diferença = lançadas − previstas; positiva quer dizer que passou do previsto. Sem horas previstas, a diferença fica "—".
- **Tempo em desenvolvimento (R17):** dias úteis em etapa de papel desenvolvimento × carga diária do dev da tarefa. Exemplo: 2,5 dias úteis × 8 h = 20 h. Aparece ao lado das horas lançadas e nunca soma com elas: serve para quando o dev esquece de lançar (ver 10.3).
- **Tarefa arquivada (P26):** sai da *Situação* (atraso, próximas datas, pausa), da capacidade (F5) e do tempo por etapa. Continua em *Horas* e em *Por pessoa* com as horas lançadas; as horas previstas dela não entram no total previsto (ver 10.5).
- **Bug de produção (P25):** é o card criado ligado a uma tarefa original. Conta na aba *Qualidade* como indicador do projeto. Não é volta para desenvolvimento e não entra no retrabalho de ninguém.
- **Por pessoa:** cada tarefa conta para o dev dela. Previstas = horas previstas das tarefas em que a pessoa é dev (o total Dev + QA, P27; ver 10.4). Lançadas = o que ela lançou no projeto, em qualquer tarefa, inclusive como QA.

### O que cada aba mostra

| Aba | O que mostra | Regra |
|---|---|---|
| Situação | Quantas tarefas em cada etapa, as atrasadas, as próximas datas previstas, as tarefas em pausa com o motivo e as que entraram em desenvolvimento sem data prevista. | (P15, P28, P53) |
| Tempo por etapa | Quanto tempo as tarefas passaram em cada papel de etapa, somado e em média por tarefa. Mostra onde o card para: em code review, em teste, em pausa. | (P11, P53) |
| Horas | Previstas × lançadas por tarefa, com a diferença e, ao lado, o tempo em desenvolvimento estimado pelo quadro. | (P27, P30, P53, R17) |
| Por pessoa | As mesmas contas somadas por dev, dentro do projeto. | (P53) |
| Qualidade | Os bugs achados em produção, cada um ligado à tarefa original, e o total. | (P25) |
| Minhas tarefas e horas | As tarefas da pessoa, com data prevista, atraso e as horas que ela lançou. | (P53) |

### Fica para depois da v1 (P54, P55)

Painel de produtividade por dev (entrega no prazo, voltas por dev), indicador de retrabalho consolidado por origem e motivo, histórico de desenvolvimento em PDF para o cliente, relatório de fechamento para pagamento e notas de versão. Os dados já ficam gravados desde a fatia 1: cada movimento com origem e motivo, as horas, as sprints (as "fases" do histórico, P51) e os pacotes.

## 07. Avisos e documentos gerados

- **Sem data prevista:** "Sem data prevista: falta o dev", "… faltam as horas" ou "… ainda não entrou em desenvolvimento" (P28).
- **Tempo em desenvolvimento:** a marca "estimado pelo quadro; não substitui o lançamento de horas" (R17).
- Nenhum aviso no sistema e nenhum e-mail saem deste bloco (P22 lista os únicos avisos de Projetos).
- Nenhum documento gerado na fatia 1: histórico em PDF, relatório de fechamento e notas de versão são depois da v1 (P55).

## 08. Exportação

Não se aplica na fatia 1: nenhuma regra pede exportação, e o histórico de desenvolvimento em PDF fica para depois da v1 (P55).

## 09. Permissionamento

| O quê | Administrador | Gestor de projetos | Desenvolvedor |
|---|---|---|---|
| Linha do tempo de um card | todos os projetos | nos projetos que gere | nos projetos em que está alocado (P5) |
| Ver na auditoria, a partir do card | sim | não | não |
| Abas *Situação*, *Tempo por etapa*, *Horas*, *Por pessoa*, *Qualidade* | todos os projetos | nos projetos que gere | não: nos indicadores o dev não vê nada que não seja dele (P54, decisão de 06/10) |
| Aba *Minhas tarefas e horas* | sim | sim | sim |
| `status_do_projeto` pela IA | todos os projetos | nos projetos que gere | não: recebe "negado" e usa `minhas_tarefas` (G4) |
| `minhas_tarefas` pela IA | sim | sim | sim |

A IA de cada pessoa tem exatamente estas permissões (G4). O Comercial não entra em Projetos (C14): o assistente dele recebe "negado".

## 10. Pontos a validar

Respondidos por Lucas Scholz em 06/10/2026 e já aplicados nas seções acima, no F8 e na `necessidade.md` (IA14).

1. **Unidade do tempo por etapa.** OK: dias úteis com uma casa decimal.
2. **O que o dev vê.** **Nos indicadores, nada que não seja dele**: só *Minhas tarefas e horas*. Sem *Situação*, *Tempo por etapa* e *Qualidade*; `status_do_projeto` pela IA fica para gestor e Administrador. O quadro (F3) e a linha do tempo dos cards dos projetos dele continuam visíveis, porque são o trabalho, não indicador.
3. **Carga no tempo em desenvolvimento.** OK: a carga atual do dev atual.
4. **Previsto por pessoa.** OK: fica com o dev; a tela diz isso.
5. **Tarefa arquivada.** OK: sai do tempo por etapa e das horas previstas; as lançadas ficam.
6. **Atraso de tarefa pronta.** OK: data prevista e data de pronto lado a lado, sem "atraso".
7. **Botão *Histórico* do card.** OK: abre a linha do tempo; o Administrador tem *Ver na auditoria*. F1 ajustado.
8. **Soma por pessoa entre projetos.** OK: por pessoa dentro de cada projeto.
9. **Contagem de voltas no card.** OK: não mostrar no resumo.
