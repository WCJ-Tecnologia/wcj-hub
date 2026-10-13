# F2. Projeto e configuração do quadro

**Documento funcional.** Versão 1.0, de 06/10/2026. **Aprovado por Lucas Scholz em 06/10/2026**, com os pontos a validar respondidos (seção 10). Rafael Garcia valida o uso no bugbash. Estrutura e fluxo em [`README.md`](README.md).

| | |
|---|---|
| **Objetivo** | Dar ao gestor o cadastro do projeto, as pessoas que trabalham nele e a configuração do quadro (etapas, áreas e padrões); dar ao Administrador as duas listas gerais que todo projeto usa: tipos de tarefa e motivos de retrabalho. |
| **Regras que alimentam** | G1, G7, P1, P2, P3, P5, P7, P8, P10, P11, P12, P15, P18, P40, P46 e a premissa de Projetos (`docs/necessidade.md`). |
| **Tabelas do `banco.md`** | `projects`, `project_members`, `stage_roles`, `board_stages`, `project_areas`, `task_types`, `rework_reasons`. |
| **Depende de** | [F1](f1-nucleo.md): usuários (gestor, pessoas alocadas, carga diária) e clientes. |
| **Quem aprova** | Lucas Scholz (D-GER-31). Rafael Garcia, gestor de projetos (D-GER-02), participou com feedback e elaboração e valida o uso no bugbash. |
| **Fora daqui** | O quadro em uso e o card ([F3](f3-quadro-e-card.md)), teste e QA ([F4](f4-teste-e-qualidade.md)), sprint e horas ([F5](f5-sprint-e-horas.md)), pacote ([F6](f6-pacote.md)) e indicadores ([F7](f7-indicadores.md)). |

## 01. Fluxo funcional

**Criar o projeto.** O gestor ou o Administrador abre *Projetos*, clica em *Novo projeto*, escolhe cliente e gestor, informa prefixo, nome, tipo e datas e confirma (P1, P2, P3). Projeto interno ou produto da WCJ (ex.: Wpay) tem a própria WCJ como cliente (N7). O projeto nasce aberto, com o quadro padrão de 8 etapas (P10), sem áreas (P18), com o motivo da pausa ligado e sprint de 10 dias úteis (P15, P46), e sem ninguém alocado.

**Alocar as pessoas.** Na aba *Pessoas*, o gestor clica em *Alocar pessoas* e escolhe quem trabalha no projeto. Só quem está alocado vê o projeto, mexe no quadro e pode ser dev ou QA de uma tarefa (P5). O gestor tira alguém a qualquer momento: as tarefas em que a pessoa era dev ficam sem dev, e as em que era QA ficam sem QA (P5).

**Ajustar o quadro.** Na aba *Etapas*, o gestor renomeia, cria, reordena e remove etapas (P11). Cada etapa tem um papel, e é o papel que os indicadores leem, não o nome (P11). Remover etapa com tarefas pede o destino delas (P12). Na aba *Áreas*, o gestor cadastra as áreas do produto, se quiser usar (P18). Na aba *Padrões*, liga ou desliga o motivo da pausa e muda a duração da sprint (P15, P46). Toda mudança vale dali para a frente e não apaga nada do que já foi registrado (P8).

**Encerrar e reabrir.** Quando quiser, mesmo com tarefa aberta, o gestor encerra o projeto (P7). O projeto encerrado continua visível, mas não recebe tarefa nova; o gestor ou o Administrador reabre (P7).

**Listas gerais.** O Administrador mantém, em *Administração → Tipos de tarefa* e *Administração → Motivos de retrabalho*, as duas listas que valem para todos os projetos (G7, P18, P40).

## 02. Telas e campos

### Projeto (criar e editar, aba *Dados*)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Cliente | lista dos clientes ativos (F1) | sim | | Todo projeto é de um cliente e o cliente é definido só na criação: não se troca depois (P1). Na edição aparece só leitura. Ao lado, o botão *Novo cliente* abre o cadastro de cliente (campos de F1) sem sair do formulário; ao salvar, o cliente novo já fica escolhido (N6, decisão de 06/10). Cliente desativado sai da lista; o projeto que já é dele continua (N11). |
| Gestor responsável | lista dos usuários ativos com papel Gestor de projetos ou Administrador | sim | quem está criando, se for Gestor de projetos | O responsável pelo projeto (P1). Outros gestores entram pela alocação: toda pessoa alocada com papel Gestor de projetos gere o projeto como ele (P1, P5). |
| Prefixo | texto, até 10, único no sistema | sim | | Forma a chave das tarefas: `SGM` → `SGM-17` (P3, P16). Proposta: letras e números, sem espaço, gravado em maiúsculas (ver 10.2). Repetido é recusado, sem diferença de maiúscula e minúscula. Fica só leitura depois que a primeira tarefa do projeto é criada, mesmo que ela seja excluída depois, porque o número nunca é reaproveitado (P3, P16). |
| Nome | texto, até 160 | sim | | Ex.: "Wpay", "Gestão de multas". |
| Tipo | escolha: Projeto de entrega / Produto contínuo | sim | Projeto de entrega | Não é guardado à parte: é a Entrega preenchida ou vazia (P2). Escolher Produto contínuo esconde e limpa a Entrega. |
| Início | data | sim | hoje | (P2) |
| Entrega | data, igual ou posterior ao Início | sim no projeto de entrega; não existe no produto contínuo | | Só informativa: nenhuma conta usa na v1, e o gestor acompanha o prazo por conta própria (P2, P9). |
| Situação | só leitura: Aberto / Encerrado em DD/MM/AAAA | | Aberto | (P7). Quem encerrou e quem reabriu ficam no histórico. |

### Pessoas (aba *Pessoas*)

Um campo só, **Pessoas**, obrigatório: lista dos usuários ativos com papel Desenvolvedor, Gestor de projetos ou Administrador que ainda não estão no projeto, com escolha de uma ou várias. Cada pessoa entra uma vez só; o Comercial não aparece (C14). Não há função no projeto (back-end, front-end, QA) nem Tech Lead (P6 saiu): o dev e o QA de cada tarefa se escolhem no card (P20, F3).

### Etapa (aba *Etapas*)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Nome | texto, até 60 | sim | | Livre. Os nomes padrão são em inglês (P10). |
| Papel | lista fixa: Fila, Sprint, Desenvolvimento, Code review, Teste, Pronto, Pausa, Suporte | sim | Desenvolvimento | O que a etapa significa para o sistema e para os indicadores (P11). Na etapa nova, só os papéis que podem se repetir: Desenvolvimento, Code review, Teste, Pronto e Pausa. Fila, Sprint e Suporte existem uma vez só por projeto, sempre só leitura (P11). Nas demais, o papel fica só leitura depois que algum card passou pela etapa (P8, P11). |
| Posição | ordem no quadro, por arrastar | | no fim | (P11) |

Quadro com que todo projeto nasce (P10), na ordem:

| Etapa | Papel | Pode remover? |
|---|---|---|
| Backlog | Fila | não: é para onde o card volta ao sair da sprint (P11, P45) |
| To do | Sprint | não: é para onde o card vai ao entrar na sprint (P11, P45) |
| Doing | Desenvolvimento | sim |
| Paused | Pausa | sim |
| Code-review | Code review | sim |
| In test | Teste | sim |
| Done | Pronto | sim |
| Support | Suporte | não: é onde nasce o card de suporte (P11, P52) |

Ao remover etapa com tarefas, o diálogo pede **Mover as tarefas para**: lista das outras etapas ativas do projeto, obrigatória (P12).

### Área (aba *Áreas*)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Nome | texto, até 60, único no projeto | sim | | Ex.: "Admin", "Âncora", "Fornecedores". Repetido no mesmo projeto é recusado, sem diferença de acento e caixa: acaba o "ANCORA" e "ÂNCORA" do Trello (P18). |
| Situação | só leitura: Ativa / Desativada em DD/MM/AAAA por Fulano | | Ativa | |

### Padrões do projeto (aba *Padrões*)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Pedir motivo na pausa | sim / não | sim | sim | Ligado: levar o card para etapa de papel Pausa pede o motivo. Desligado: não pede. O tempo parado é contado à parte nos dois casos (P15, F3, F7). |
| Duração da sprint | número inteiro de dias úteis, maior que 0 | sim | 10 (2 semanas) | Usada para propor o fim da sprint nova (P45, P46, F5). Mudar não altera a sprint aberta (P8). |

### Tipo de tarefa (*Administração → Tipos de tarefa*)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Nome | texto, até 60, único | sim | | Repetido é recusado (G7). |
| Situação | só leitura: Ativo / Desativado em DD/MM/AAAA por Fulano | | Ativo | |

A lista nasce com Melhoria, Correção de bug, Suporte e Mudança (GMUD) (P18). Dois têm comportamento próprio: **Melhoria** é o tipo do card criado só com o título (P17, F3); **Suporte** é o tipo do card de suporte, que nasce na etapa de papel Suporte, fora da sprint, e pede quem pediu, por onde e quando (P52, F3). Os dois se renomeiam, mas não se desativam nem se excluem, porque o sistema depende deles (ver 10.6). Tipo novo se comporta como um card comum.

### Motivo de retrabalho (*Administração → Motivos de retrabalho*)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Nome | texto, até 80, único | sim | | Repetido é recusado (G7). |
| Pede texto | sim / não | sim | não | Ligado: quem escolhe este motivo escreve o texto, como no "Outro" (P40). |
| Situação | só leitura: Ativo / Desativado em DD/MM/AAAA por Fulano | | Ativo | |

A lista nasce com Ajuste visual, Regra de negócio, Erro / bug, Requisito incompleto ou mal documentado e Outro, este com texto (P40). O "Outro" existe sempre: não se desativa, não se exclui e não deixa de pedir texto (P40). A lista é usada em toda volta para desenvolvimento (P39, F3), na reprovação da homologação (F6) e pela IA em `mover_tarefa`.

## 03. Listagem

| Tela | Colunas | Ordem padrão | Observação |
|---|---|---|---|
| Projetos | Prefixo, Nome, Cliente, Gestor, Tipo, Início, Entrega, Situação | Nome | Cada pessoa vê só os projetos que pode ver (P5, seção 09). Encerrados ficam escondidos; o filtro *Mostrar encerrados* os traz, em cinza (P7). Clicar abre o quadro (F3); a configuração fica no botão *Configurar*. |
| Pessoas do projeto | Nome, Papel, Carga diária, Situação | Nome | Usuário desativado continua na lista, em cinza: desativar não tira do projeto (N11, F1). |
| Etapas | Posição, Nome, Papel, Cards na etapa | Posição (a do quadro) | Etapas removidas não aparecem. Cards na etapa = quantos estão nela hoje, para o gestor saber o que vai mover ao remover. |
| Áreas | Nome, Situação | Nome | Desativadas escondidas; *Mostrar desativadas* as traz, em cinza. |
| Tipos de tarefa | Nome, Situação | Nome | Idem. |
| Motivos de retrabalho | Nome, Pede texto, Situação | Nome, com o "Outro" por último | Idem. |

Estado vazio: Projetos, "Nenhum projeto ainda. *Novo projeto*" (para o dev, sem botão: "Você ainda não está alocado em nenhum projeto"); Pessoas, "Ninguém alocado ainda. *Alocar pessoas*"; Áreas, "Nenhuma área. *Nova área*"; Tipos de tarefa, "Nenhum tipo ativo. *Novo tipo*"; Motivos, "Nenhum motivo ativo. *Novo motivo*". A lista de etapas nunca fica vazia: Fila, Sprint e Suporte existem sempre (P11).

## 04. Ações

### Projeto

| Ação | Quem | O que faz | Confirmação / aviso |
|---|---|---|---|
| Novo projeto | Administrador; Gestor de projetos | Cria o projeto com o quadro padrão, sem áreas e com os padrões (P10, P15, P18, P46). | Prefixo repetido: recusa com "O prefixo SGM já é do projeto X" (P3). |
| Novo cliente (no formulário do projeto) | Administrador; gestor | Cria o cliente com os campos de F1 e o deixa escolhido no projeto (N6). | CNPJ repetido: mesma recusa de F1 (N5). |
| Editar | Administrador; gestor do projeto | Gestor responsável, nome, tipo e datas; o prefixo, só até a primeira tarefa (P3). O cliente não se edita (P1). Vale dali para a frente (P8). | Ao trocar o responsável: "Fulano deixa de ser o responsável e só continua gerindo se estiver alocado. Continuar?" (P1, P5) |
| Encerrar | Administrador; gestor do projeto | Marca o projeto como encerrado. Tarefas, sprint e pacotes ficam como estão; ninguém cria tarefa nova, nem pela tela nem pela IA (P7). | "O projeto tem N cards fora da etapa de papel Pronto. Eles continuam no quadro. Encerrar?" |
| Reabrir | Administrador; gestor do projeto | Volta a aberto e a receber tarefas (P7). | |
| Excluir | Administrador | Só projeto nunca usado: sem tarefa, sprint nem pacote (G2). Saem junto o quadro, as áreas e as alocações. | "Excluir de verdade. Não dá para desfazer." |
| Histórico | Administrador; gestor do projeto | Abre a auditoria filtrada no projeto e na configuração dele (F1, G3). | |

### Pessoas

| Ação | Quem | O que faz | Confirmação / aviso |
|---|---|---|---|
| Alocar pessoas | Administrador; gestor do projeto | Põe uma ou várias pessoas no projeto. Elas passam a vê-lo na hora, também pela IA (P5, G4). | |
| Tirar do projeto | Administrador; gestor do projeto | A pessoa deixa de ver o projeto na hora, também pela IA. Na mesma operação, as tarefas em que ela era dev ficam sem dev, e as em que era QA, sem QA (P5). Horas lançadas, comentários, movimentos e a marca de teste cruzado dela continuam (P8). Se voltar a ser alocada, as tarefas não voltam para ela. | Com tarefas: "Fulano é dev de N e QA de M tarefas deste projeto. Elas ficam sem dev e sem QA. Continuar?" Sem tarefas: "Tirar Fulano do projeto?" |

### Etapas

| Ação | Quem | O que faz | Confirmação / aviso |
|---|---|---|---|
| Nova etapa | Administrador; gestor do projeto | Cria a etapa no fim do quadro, com nome e papel (P11). | |
| Editar | Administrador; gestor do projeto | Renomeia; troca o papel enquanto a etapa não teve card (P11). Os indicadores não mudam com o nome, porque leem o papel (P11). | |
| Reordenar | Administrador; gestor do projeto | Arrasta a etapa para outra posição. Vale para todas, inclusive Fila, Sprint e Suporte (P11). | |
| Remover | Administrador; gestor do projeto | Tira a etapa do quadro; ela continua nos movimentos antigos, para o histórico e os indicadores (P8, P12). Os cards que estão nela vão para a etapa escolhida, cada um com o movimento gravado: quem, quando, de onde e para onde (P12, P14). Essa mudança não conta como retrabalho (ver 10.4). Se o destino for a etapa de Fila, os cards também saem da sprint, como em *Tirar da sprint* (P45, F5). Cards arquivados que estavam nela não se mexem; ao serem desarquivados, vão para o Backlog (P26). As etapas de Fila, Sprint e Suporte não têm o botão (P11). | Sem cards: "Remover a etapa X?" Com cards: "N cards estão em X. Para onde vão?", com o destino obrigatório (P12). |

### Áreas e padrões

| Ação | Quem | O que faz | Confirmação / aviso |
|---|---|---|---|
| Nova área | Administrador; gestor do projeto | Cria a área no projeto (P18, G7). | Nome repetido no projeto: recusa com "Já existe a área X neste projeto". |
| Editar | Administrador; gestor do projeto | Renomeia. O nome novo aparece em todos os cards da área. | Idem. |
| Desativar / Reativar | Administrador; gestor do projeto | Desativada sai da lista de escolha do card; os cards que já têm a área continuam com ela (P8). | |
| Excluir | Administrador; gestor do projeto | Só área que nenhum card usa (G2). | "Excluir de verdade. Não dá para desfazer." |
| Salvar padrões | Administrador; gestor do projeto | Vale dali para a frente: desligar o motivo da pausa não apaga os motivos já gravados; mudar a duração não muda a sprint aberta (P8). | |

### Tipos de tarefa e motivos de retrabalho

| Ação | Quem | O que faz | Confirmação / aviso |
|---|---|---|---|
| Novo | Administrador | Cria o item na lista geral (G7). | Nome repetido: recusa. |
| Editar | Administrador | Renomeia; no motivo, liga ou desliga o "pede texto" (menos no "Outro", P40). Cards e movimentos antigos passam a mostrar o nome novo. | |
| Desativar / Reativar | Administrador | Sai da lista de escolha; o que já usa continua (P8). Melhoria, Suporte e Outro não têm o botão. | |
| Excluir | Administrador | Só o que nunca foi usado (G2). Melhoria, Suporte e Outro não têm o botão. | "Excluir de verdade. Não dá para desfazer." |

### Pela IA

Nenhuma das 6 ferramentas da fatia 1 cria projeto, aloca pessoa ou muda configuração (IA14). `status_do_projeto` lê o projeto com as etapas configuradas aqui, só para quem vê o projeto (P5, G4). `escrever_tarefa` em projeto encerrado recebe "projeto encerrado: reabra para criar tarefas" (P7). `mover_tarefa` oferece os motivos de retrabalho ativos desta lista geral (P39, P40).

## 05. Filtros e busca

| Tela | Busca | Filtros |
|---|---|---|
| Projetos | prefixo, nome, cliente | Cliente; Gestor; Tipo; Situação (abertos por padrão) |
| Pessoas do projeto, Etapas | nome, nas pessoas | nenhum: as listas são curtas |
| Áreas, Tipos de tarefa, Motivos de retrabalho | nome | Situação (ativos por padrão) |

## 06. Indicadores

Não se aplica: a configuração não tem número próprio. Tempo por etapa, horas e atraso do projeto estão no [F7](f7-indicadores.md); a capacidade da sprint, no [F5](f5-sprint-e-horas.md). A contagem de cards por etapa (seção 03) só ajuda a remover etapa.

## 07. Avisos e documentos gerados

- Nenhum aviso no sistema nem e-mail neste bloco. Os únicos avisos de Projetos são os da P22 (definir dev ou QA; volta para desenvolvimento), no [F3](f3-quadro-e-card.md). Alocar ou tirar alguém do projeto não avisa ninguém.
- Recusas: prefixo repetido (P3); Entrega antes do Início; área repetida no projeto; tipo ou motivo repetido. Confirmações: as da seção 04 (trocar gestor, encerrar, tirar pessoa, remover etapa, excluir).
- Nenhum documento gerado.

## 08. Exportação

Não se aplica na fatia 1: nenhuma regra pede exportação de projeto ou de configuração (G5).

## 09. Permissionamento

| O quê | Administrador | Gestor de projetos | Desenvolvedor |
|---|---|---|---|
| Ver o projeto na lista e abrir | todos | os que gere: é o responsável ou está alocado (P1, P5) | só os em que está alocado (P5) |
| Criar projeto | sim | sim | não |
| Editar dados, trocar o responsável | sim | nos que gere | não |
| Encerrar e reabrir | sim | nos que gere (P7) | não |
| Excluir projeto nunca usado | sim | não | não |
| Ver dados, pessoas e etapas do projeto | sim | nos que gere | só leitura, nos em que está alocado |
| Alocar e tirar pessoas | sim | nos que gere (P5) | não |
| Etapas, áreas e padrões | sim | nos que gere (G7, P11) | não (premissa) |
| Ser dev ou QA de tarefa | sim, se alocado | sim, se alocado: alocado quer dizer que vai executar (P5) | sim |
| Tipos de tarefa e motivos de retrabalho: usar no card | sim | sim | sim |
| Tipos de tarefa e motivos de retrabalho: editar | sim | não (G7) | não |
| Histórico do projeto | sim | nos que gere | não |

"Gere" = é o gestor responsável do projeto ou está alocado nele com papel Gestor de projetos (P1, P5). Em todos os funcionais, "gestor do projeto" quer dizer isso. A IA de cada pessoa tem exatamente estas permissões (G4). O Comercial não entra em Projetos (C14).

## 10. Pontos a validar

Respondidos por Lucas Scholz em 06/10/2026 e já aplicados nas seções acima, na `necessidade.md` (P1, P5) e no `banco.md`.

1. **Quem pode ser gestor.** Sim: qualquer usuário ativo com papel Gestor de projetos ou Administrador, sócios inclusive.
2. **Formato do prefixo.** OK: até 10 letras, números e hífen, sem espaço, em maiúsculas; repetido é recusado sem olhar a caixa.
3. **Gestor e alocação.** **Pode haver mais de um gestor por projeto.** O projeto tem um gestor responsável (P1) e toda pessoa com papel Gestor de projetos alocada no projeto gere como ele (mesma visão e mesmos poderes). Gestor pode ser dev ou QA de tarefa: alocado quer dizer que vai executar (P5). ~~Gestor alocado = dev~~ não vale.
4. **Remover etapa não é retrabalho.** OK.
5. **Quadro sem algum papel.** OK: Fila, Sprint, Suporte e Desenvolvimento existem sempre (P11); Pronto e Teste podem ser removidos, e os indicadores desse papel param de contar dali em diante.
6. **Melhoria, Suporte e Outro fixos.** OK: renomeiam, não se desativam nem se excluem (P18, P40).
7. **Trocar o cliente do projeto.** **Não existe.** O projeto só existe por causa do cliente: definido na criação, não muda (P1). Errou o cliente num projeto nunca usado: exclui e cria de novo.
8. **Projeto encerrado.** OK: só a criação de tarefa para; o resto segue para fechar o que ficou aberto (P7).
9. **Excluir projeto.** OK: só o Administrador, e só sem tarefa, sprint nem pacote (G2).
10. **Etapa removida não volta.** OK: sem restaurar na v1; cria-se outra com o mesmo papel.
