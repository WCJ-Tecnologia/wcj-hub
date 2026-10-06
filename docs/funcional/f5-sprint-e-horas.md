# F5. Sprint, horas e data prevista

**Documento funcional.** Versão 1.0, de 06/10/2026. **Aprovado por Lucas Scholz em 06/10/2026**, com os pontos a validar respondidos (seção 10). Rafael Garcia valida o uso no bugbash. Estrutura e fluxo em [`README.md`](README.md).

| | |
|---|---|
| **Objetivo** | Organizar o trabalho do projeto em sprints, registrar as horas previstas e as realizadas de cada tarefa, calcular a data prevista e mostrar ao gestor a capacidade de cada dev na sprint, sempre como referência, nunca como trava. |
| **Regras que alimentam** | N3, P27 a P31, P45, P46, P48 a P51 (`docs/necessidade.md`), com R5 e R9 da revisão de 01/10. A P47 saiu (R9). |
| **Tabelas do `banco.txt`** | `sprints`, `tasks` (`sprint_id`, `estimated_hours`, `due_on`, `developer_id`), `time_entries`, `users` (`daily_hours`), `projects` (`sprint_days`). Leitura de `task_stage_movements` e `board_stages` (primeira entrada em desenvolvimento, etapa de pronto). |
| **Depende de** | [F1](f1-nucleo.md) (carga diária), [F2](f2-projeto-e-configuracao.md) (duração padrão da sprint, etapas do quadro) e [F3](f3-quadro-e-card.md) (quadro, card, mover). |
| **Quem aprova** | Rafael Garcia (gestor de projetos, D-GER-02). |

Fica fora deste bloco: mover card em geral e o diálogo de retrabalho (F3), e os indicadores somados por projeto e por pessoa, como previsto × realizado e atraso (F7).

## 01. Fluxo funcional

**Montar a sprint.** A sprint é obrigatória: é a única saída do `Backlog` (P45). O gestor arrasta o card do `Backlog` para o `To do`, e isso põe o card na sprint aberta do projeto (P45). Se não há sprint aberta, abre o diálogo *Nova sprint* já preenchido: nome "Sprint N", início hoje e fim pela duração padrão do projeto, em dias úteis de segunda a sexta (P46, R5). O objetivo é opcional (P45). O gestor confirma e, na mesma ação, a sprint é criada e o card entra nela. Se cancelar, o card fica no `Backlog`. Só existe uma sprint aberta por projeto (P45).

Exemplo do fim: início terça 06/10/2026 com duração de 10 dias úteis. Contando o dia de início, os 10 dias úteis vão até segunda 19/10/2026, que é o fim proposto (ver 10.1).

**Horas na montagem.** Com o diretor de tecnologia, o gestor define ou valida as horas previstas de cada tarefa (P27). Faz isso no card ou direto na lista de tarefas da tela da sprint. As horas são um total só por tarefa, dev + QA (P27). Tarefa sem horas entra na sprint do mesmo jeito; só fica sem data prevista (P27). Validar não deixa marca: o sistema guarda as horas, não um "validado" (G5).

**Capacidade.** Na tela da sprint, o painel *Capacidade* mostra, por dev com tarefa na sprint, as horas que faltam das tarefas dele contra a capacidade dele: carga diária × dias úteis da sprint (P48, P49). É referência para o gestor e nunca recusa nada: o gestor põe na sprint o que quiser, mesmo acima da capacidade (P49).

**Durante a sprint.** O dev leva o card para `Doing` (F3). Na primeira entrada em etapa de desenvolvimento, o sistema calcula a data prevista (P28). Quem trabalhou na tarefa lança as horas do dia, no card ou na tela *Minhas horas*, no menu principal (proposta) (P30). O gestor e o Administrador põem e tiram cards da sprint a qualquer momento; tirar devolve o card ao `Backlog` (P45).

**Encerrar.** O gestor clica em *Encerrar sprint* (P50). O diálogo lista as tarefas que não chegaram a `Done`. Para cada uma, ele escolhe "vai para a próxima sprint" (padrão) ou "volta ao `Backlog`". No mesmo diálogo vem a próxima sprint, já preenchida. Ao confirmar, a sprint atual fecha, a próxima nasce e as tarefas vão para onde o gestor escolheu (P50). As tarefas em `Done` ficam na sprint em que foram feitas (P51). Encerrar não espera nada: pode haver tarefa em `Doing` ou em teste (premissa).

**Lista de sprints.** Em *Projeto → Sprints*, o gestor vê as sprints do projeto, a aberta e as encerradas, e abre cada uma para ver as tarefas dela. Cada sprint é uma "Fase" do Histórico de Desenvolvimento entregue ao cliente: o nome e o período são o título e o período da fase (P51). O relatório em PDF fica para depois da v1 (P55).

## 02. Telas e campos

### Sprint (criar, editar, e o diálogo *Nova sprint* ao arrastar)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Nome | texto, até 80, único no projeto | sim | "Sprint N" (N = sprints que o projeto já teve + 1) | É o título da fase no Histórico (P51). Repetido no projeto é recusado. |
| Objetivo | texto, até 500 | não | | Opcional (P45, R5). |
| Início | data | sim | hoje | |
| Fim | data, igual ou depois do início | sim | início + duração padrão do projeto, em dias úteis seg–sex (P46) | A duração padrão é 10 dias úteis e se ajusta por projeto, em F2 (P46). Feriado não conta na v1 (P29). Fim antes do início é recusado. |
| Dias úteis | número, só leitura | | | Dias de segunda a sexta entre início e fim, inclusive. É o multiplicador da capacidade (P48). |
| Situação | só leitura: Aberta / Encerrada em DD/MM/AAAA | | Aberta | (P45, P50) |

### Encerrar sprint (diálogo)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Tarefas que não chegaram a `Done` | lista: Chave, Título, Etapa, Dev | | | Só leitura. Arquivadas não entram (P26). |
| Destino, em cada tarefa | escolha: Próxima sprint / `Backlog` | sim | Próxima sprint | (P50) |
| Criar a próxima sprint | sim / não | | sim | Sempre sim quando alguma tarefa vai para a próxima. Proposta: ver 10.4. |
| Próxima sprint: nome, objetivo, início, fim | os mesmos campos da sprint | nome, início e fim | "Sprint N+1"; início = 1º dia útil depois do fim da sprint que está sendo encerrada; fim = início + duração padrão, contando o início como 1º dia útil | O gestor edita as datas (P50, P46). |

### Horas e data prevista (na ficha do card)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Horas previstas | horas, com decimais, maior que 0 | não | vazio | Um total só, dev + QA (P27). Mudar depois que a data prevista existe é reestimar (P28): ver seção 04. Cada mudança fica no histórico do card, com o antes e o depois (P27, G3). |
| Data prevista | data, só leitura | | vazio | Calculada pelo sistema (P28), ver abaixo. |
| Horas lançadas | horas, só leitura | | 0 | Soma de todos os lançamentos da tarefa, de qualquer pessoa (P30). |
| Faltam | horas, só leitura | | | Previstas − lançadas, nunca abaixo de zero. Vazio quando não há horas previstas. |

**Como a data prevista é calculada (P28, P29).** Na primeira vez que a tarefa entra em etapa de desenvolvimento (`Doing` no padrão), o sistema divide as horas previstas pela carga diária do dev e conta esse número de dias úteis, de segunda a sexta, a partir do dia da entrada. Feriado não conta na v1 (P29). Exemplo da P28, com entrada em `Doing` na quinta 08/10/2026 e 16 h previstas:

- dev de 8 h/dia: 2 dias úteis (quinta e sexta), data prevista sexta 09/10/2026;
- dev de 4 h/dia: 4 dias úteis (08, 09, 12 e 13/10), data prevista terça 13/10/2026. O dia 12/10 é feriado nacional, mas conta como dia útil na v1 (P29).

A data só muda quando alguém reestima: o sistema refaz a conta com as horas novas, a partir da mesma primeira entrada em desenvolvimento (P28). Reestimar é do gestor e do Administrador; o dev propõe um valor novo na ficha do card e a data só muda quando a proposta é aprovada (P27; F3). Na tela da sprint, as tarefas com proposta pendente aparecem com a marca "reestimativa proposta", para o gestor decidir ali. Pausa e retrabalho não empurram a data (P28). Mudar a carga diária do dev não mexe nas datas já calculadas (N3). Sem dev, sem horas ou com dev sem carga diária, a data fica vazia (P28, N3); quando a tarefa passa a ter os dois, a data é calculada contando da primeira entrada em `Doing`, que pode já ter passado (P28).

### Lançar horas (no card e em *Minhas horas*)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Tarefa | fixa, quando aberto do card; busca por chave ou título, em *Minhas horas* | sim | | Tarefas dos projetos em que a pessoa está, sem as arquivadas. Em *Minhas horas*, sugere primeiro as tarefas em que ela é dev ou QA. |
| Pessoa | fixa: quem está lançando | sim | eu | Quem trabalhou lança (P30). |
| Data | data, até hoje | sim | hoje | O dia trabalhado (P30). Data futura é recusada (proposta, ver 10.12). |
| Horas | horas, com decimais (ex.: 1,5), maior que 0 e até 24 | sim | | Horas decimais, sem cronômetro (P30). Fora disso é recusado. |

Não há campo de descrição no lançamento. Quem quiser explicar o que fez comenta no card (F3).

## 03. Listagem

| Tela | Colunas | Ordem padrão | Observação |
|---|---|---|---|
| Sprints do projeto | Nome, Período (início a fim), Dias úteis, Objetivo, Situação, Tarefas (prontas de total) | A aberta no topo; depois as encerradas, da mais recente para a mais antiga | A encerrada mostra as tarefas que ficaram nela: as que chegaram a `Done` (P51). As que foram para a próxima sprint aparecem na próxima; a troca fica no histórico do card. |
| Tarefas da sprint (na tela da sprint) | Chave, Título, Etapa, Dev, QA, Horas previstas, Horas lançadas, Data prevista | Pela ordem das etapas no quadro e, dentro da etapa, pela chave | Na sprint aberta, o gestor e o Administrador editam as horas previstas direto na linha (P27). |
| Painel *Capacidade* (na tela da sprint) | Dev, Carga diária, Capacidade (carga × dias úteis), Horas que faltam, Diferença (capacidade − faltam) | Maior "faltam" primeiro | Diferença negativa em destaque, só como aviso visual (P49). Abaixo, à parte: "Sem dev" (tarefas e horas previstas delas) e "Sem horas previstas" (tarefas) (P49). Dev sem carga diária aparece com "sem carga diária: capacidade não calculada" (N3). |
| Horas da tarefa (no card) | Pessoa, Data, Horas | Data mais recente primeiro | Total no rodapé. |
| Minhas horas | Data, Projeto, Tarefa (chave e título), Horas | Data mais recente primeiro | Período padrão: a semana atual, de segunda a domingo. Total por dia e total do período. |

**Como o painel conta (P48, P49, R9).** Entra todo dev que é dev de alguma tarefa da sprint; o QA não entra (P49). Horas que faltam do dev = soma, nas tarefas dele na sprint, das horas previstas menos todas as horas já lançadas na tarefa, por qualquer pessoa, cada tarefa nunca abaixo de zero. Tarefa arquivada não conta (P26). Proposta: tarefa já em `Done` conta zero (ver 10.10). Capacidade = carga diária do dev × dias úteis da sprint inteira, do início ao fim (P48). Férias e folga ficam fora da v1 (P48).

Estados vazios: "Nenhuma sprint ainda. *Nova sprint*". "Nenhuma tarefa nesta sprint. *Abrir o quadro* para arrastar cards do `Backlog`." "Nenhuma hora lançada nesta tarefa. *Lançar horas*". "Nenhuma hora lançada nesta semana. *Lançar horas*". No painel *Capacidade* sem tarefas: "Nenhum dev com tarefa nesta sprint."

## 04. Ações

### Sprint

| Ação | Quem | O que faz | Confirmação / aviso |
|---|---|---|---|
| Arrastar do `Backlog` para o `To do` | Gestor do projeto, Administrador | Põe o card na sprint aberta (P45). Sem sprint aberta, abre o diálogo *Nova sprint* preenchido e, ao confirmar, cria a sprint e põe o card nela, numa ação só (R5). | Sem diálogo quando já há sprint aberta. |
| Nova sprint | Gestor do projeto, Administrador | Cria a sprint pela lista. O botão só aparece quando o projeto não tem sprint aberta (P45). | |
| Editar sprint | Gestor do projeto, Administrador | Nome, objetivo, início e fim, a qualquer momento, aberta ou encerrada. Mudar as datas muda os dias úteis e a capacidade. | Mesmas recusas da criação. |
| Tirar card da sprint | Gestor do projeto, Administrador | Arrastar o card para o `Backlog`, ou o botão *Tirar da sprint* no card. Devolve ao `Backlog`, fora de sprint (P45). Não conta como retrabalho (F3). | Nenhuma. |
| Pôr card de suporte na sprint | Gestor do projeto, Administrador | Liga o card à sprint aberta sem trocar de coluna (P52, F3). | |
| Encerrar sprint | Gestor do projeto, Administrador | Abre o diálogo da seção 02. Fecha a sprint, cria a próxima e manda cada tarefa não pronta para a próxima ou para o `Backlog` (P50). A volta ao `Backlog` não conta como retrabalho. | "Encerrar a Sprint 4? N tarefas prontas ficam nela; M vão para a Sprint 5 e K voltam ao `Backlog`." |
| Reabrir sprint | Gestor do projeto, Administrador | Volta a sprint encerrada a aberta (P50). Não traz de volta as tarefas que foram para a próxima: o gestor as move se quiser. Só quando não há outra sprint aberta no projeto (P45). | Com outra aberta: "Encerre a Sprint 5 antes de reabrir a Sprint 4." |
| Excluir sprint | Gestor do projeto, Administrador | Só a sprint aberta que nunca teve tarefa, ex.: criada por engano (G2). | "Excluir a Sprint 5? Não dá para desfazer." |

### Horas

| Ação | Quem | O que faz | Confirmação / aviso |
|---|---|---|---|
| Informar horas previstas | Qualquer pessoa alocada, enquanto a tarefa não tem data prevista; gestor e Administrador, sempre (P27) | Grava o total previsto. Se a tarefa já entrou em `Doing` e tem dev com carga, a data prevista é calculada na hora (P28). | |
| Reestimar | Gestor do projeto, Administrador (P28) | Muda as horas de tarefa que já tem data prevista. A data é refeita com as horas novas; o antes fica no histórico (P27, P28). | "A data prevista muda de DD/MM para DD/MM." |
| Lançar horas | Quem trabalhou: qualquer pessoa alocada, o gestor e o Administrador | Grava o lançamento do dia (P30). Pode haver mais de um lançamento no mesmo dia e na mesma tarefa. | Recusa: horas ≤ 0 ou > 24; data futura (ver 10.12). |
| Corrigir lançamento | A própria pessoa; o gestor nos projetos dele; o Administrador (P30) | Edita data e horas. O antes fica no histórico (G3). | Mesmas recusas. |
| Excluir lançamento | Os mesmos de corrigir | Apaga o lançamento feito por engano, ex.: na tarefa errada. Fica na auditoria (G3). Proposta: ver 10.13. | "Excluir 2,5 h de 06/10 em SGM-17?" |

As horas lançadas não são comparadas com horas de contrato (P31). Não há cronômetro (P30).

### Pela IA (fatia 1, IA14)

- **`lancar_horas`** (escrita): lança horas numa tarefa, informada pela chave ("SGM-17"), com data (padrão hoje) e horas. Mesmas regras e recusas da tela (P30); o lançamento fica no histórico com o canal da IA (IA11). Lança sempre em nome de quem conectou a IA. Corrigir e excluir lançamento é pela tela: não está nas 6 ferramentas.
- **`minhas_tarefas`** (leitura): responde "o que tenho na sprint?". Lista as tarefas do usuário, com filtro por projeto e por prazo, trazendo etapa, sprint, horas previstas, horas lançadas e data prevista (IA14).
- **`mover_tarefa`** (escrita): pela IA do gestor ou do Administrador, mover do `Backlog` para o `To do` põe na sprint aberta, como na tela (G4). Sem sprint aberta, a IA recebe um erro legível: "O projeto SGM não tem sprint aberta. Crie a sprint pela tela." (ver 10.15). Para o dev, a IA recebe "negado", como na tela (P45).
- **`status_do_projeto`** (leitura): mostra as próximas datas previstas (IA14).
- Criar, editar e encerrar sprint pela IA não estão nas 6 ferramentas da fatia 1.

## 05. Filtros e busca

| Tela | Busca | Filtros |
|---|---|---|
| Sprints do projeto | nome | Situação (todas por padrão) |
| Tarefas da sprint | chave e título | Dev; Etapa; filtros rápidos *Sem dev*, *Sem horas previstas* |
| Minhas horas | chave e título da tarefa | Período (padrão: semana atual; atalhos semana anterior e mês atual); Projeto |
| Horas da tarefa (no card) | | Pessoa |

## 06. Indicadores

- **Na sprint:** dias úteis da sprint; tarefas prontas de total; o painel *Capacidade* por dev, com os grupos à parte "Sem dev" e "Sem horas previstas" (P48, P49).
- **No card:** horas previstas, horas lançadas, faltam e data prevista (P27, P28, P30).
- **Em *Minhas horas*:** total por dia e total do período.

Previsto × realizado, tempo por etapa e atraso, somados por projeto e por pessoa, são de F7 (P53). A tarefa atrasada e o realizado calculado pelo tempo em desenvolvimento também (R16, R17).

## 07. Avisos e documentos gerados

- **Nenhum e-mail neste bloco:** os avisos de Projetos são só os da P22, em F3.
- **Capacidade acima:** só o destaque visual no painel. Não há janela nem bloqueio ao arrastar (P49).
- **Recusas na tela:** "Já existe a Sprint 4 neste projeto" (nome repetido); "O fim não pode ser antes do início"; "Informe horas maiores que 0 e até 24"; "A data não pode ser futura" (ver 10.12).
- **Sprint aberta por outra pessoa no mesmo instante:** se dois gestores arrastam ao mesmo tempo sem sprint aberta, a segunda confirmação não cria outra sprint (uma aberta por projeto, P45): o card entra na que acabou de ser criada e a tela avisa "O card entrou na Sprint 5, aberta agora por Fulano".
- Nenhum documento gerado. O Histórico de Desenvolvimento em PDF, com as sprints como fases, fica para depois da v1 (P51, P55).

## 08. Exportação

Não se aplica: nenhuma regra pede exportar sprints nem horas na fatia 1 (G5). O relatório por fase é depois da v1 (P55).

## 09. Permissionamento

| O quê | Administrador | Gestor de projetos | Desenvolvedor |
|---|---|---|---|
| Ver a lista de sprints e as tarefas de cada uma | sim | nos projetos dele | nos projetos em que está (P5) |
| Criar, editar, encerrar, reabrir e excluir sprint | sim | nos projetos dele | não (P45, P50) |
| Pôr e tirar card da sprint | sim | nos projetos dele | não (P45) |
| Ver o painel *Capacidade* | sim | nos projetos dele | não: é referência para o gestor (P49) |
| Informar horas previstas (tarefa sem data prevista) | sim | sim | sim, nos projetos em que está |
| Reestimar (tarefa com data prevista) | sim | nos projetos dele | propõe; o gestor ou o Administrador aprova (P27; F3) |
| Lançar as próprias horas | sim | sim | sim, nos projetos em que está (P30) |
| Corrigir e excluir as próprias horas | sim | sim | sim (P30) |
| Corrigir e excluir horas de outra pessoa | sim | nos projetos dele | não (P30) |
| Ver as horas lançadas por todos na tarefa | sim | nos projetos dele | nos projetos em que está |
| *Minhas horas* | sim | sim | sim |

A IA de cada pessoa tem exatamente estas permissões, nem mais nem menos (G4). O Comercial não entra em Projetos (C14).

## 10. Pontos a validar

Respondidos por Lucas Scholz em 06/10/2026 e já aplicados nas seções acima, na `necessidade.md` (P27, P46, P50) e no `banco.txt`.

1. **Fim da sprint.** Confirmado: o gestor informa o início e o sistema calcula o fim, 10 dias úteis contando o início como o 1º (P46). Editável.
2. **Nome "Sprint N".** OK.
3. **Início da próxima sprint.** **1º dia útil depois do fim da anterior**, por padrão; o gestor edita (P50).
4. **Próxima sprint quando nada passa para ela.** OK: cria mesmo assim, com opção de não criar.
5. **Card de suporte no encerramento.** OK: fica na coluna `Support` nos dois casos.
6. **Reabrir sprint.** **Pode**, gestor e Administrador (P50). Premissa decidida junto: só quando não há outra sprint aberta no projeto (P45), e reabrir não traz de volta as tarefas que foram para a próxima.
7. **Quem reestima.** Decidido no F3: o dev propõe, o gestor ou o Administrador aprova (P27).
8. **Troca de dev.** OK: não refaz a data prevista.
9. **Conta da data prevista.** OK: dia da entrada conta como 1º; fração arredonda para cima; fim de semana conta da segunda.
10. **Tarefa em `Done` no "faltam".** OK: conta zero.
11. **Capacidade da sprint inteira.** OK: dias úteis da sprint inteira.
12. **Lançamento com data futura e soma do dia.** OK: recusa data futura; não confere a soma do dia.
13. **Excluir lançamento.** OK: corrigir inclui excluir, auditado.
14. **Gestor lançar horas pelo dev.** OK: não; só corrige.
15. **IA sem sprint aberta.** Decidido no F3: o Service cria a sprint proposta e segue.
16. **Arrastar do `Backlog` direto para `Doing`.** Decidido no F3: entra na sprint e vai para a coluna escolhida, com os dois movimentos no histórico.
17. **Dev ver a capacidade.** OK: não vê o painel; vê as tarefas e as horas da sprint.
