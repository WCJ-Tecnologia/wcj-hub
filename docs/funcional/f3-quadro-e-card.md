# F3. Quadro e card

**Documento funcional.** Versão 1.1, de 13/10/2026. **Aprovado por Lucas Scholz em 06/10/2026**, com os pontos a validar respondidos (seção 10); a versão 1.1 acrescenta as decisões de 13/10 sobre o card do quadro (seção 11, D-GER-32), decididas por ele. Rafael Garcia valida o uso no bugbash. Estrutura e fluxo em [`README.md`](README.md).

| | |
|---|---|
| **Objetivo** | Dar ao gestor e aos devs o quadro kanban de cada projeto, no lugar do Trello: criar o card em segundos, escrever nele o que for preciso, arrastar entre as colunas sem trava e guardar quem moveu, quando e por quê. |
| **Regras que alimentam** | Premissa de Projetos, P13 a P26, P39, P40, P52, P56 a P59; citadas: P7, P14, P16, P17, P45 (`docs/necessidade.md`). |
| **Tabelas do `banco.md`** | `tasks` (inclusive `parent_task_id` e `cover_attachment_id`, 13/10), `task_participants` (13/10), `task_stage_movements`, `task_priorities`, `task_types`, `task_acceptance_criteria`, `task_pull_requests`, `task_comments`, `task_attachments`, `validation_types` (origem do retrabalho), `rework_reasons`, `contacts` e `contact_channels` (card de suporte). |
| **Depende de** | [F1](f1-nucleo.md) (usuários, contatos) e [F2](f2-projeto-e-configuracao.md) (projeto, pessoas alocadas, colunas, áreas, tipos, motivos de retrabalho). |
| **Quem aprova** | Lucas Scholz (D-GER-31). Rafael Garcia, gestor de projetos (D-GER-02), participou com feedback e elaboração e valida o uso no bugbash. |

Neste documento, **"qualquer alocado"** quer dizer: o Administrador, o gestor do projeto e o Desenvolvedor alocado no projeto. Nada aqui trava o fluxo (premissa): as únicas recusas são de permissão e de dado inválido.

Fica fora deste bloco, só apontado: configurar colunas, áreas e listas ([F2](f2-projeto-e-configuracao.md)); marcar o teste cruzado e rodar o roteiro de QA ([F4](f4-teste-e-qualidade.md)); criar e encerrar sprint, lançar horas e calcular a data prevista ([F5](f5-sprint-e-horas.md)); pacote ([F6](f6-pacote.md)); indicadores e linha do tempo ([F7](f7-indicadores.md)); conectar a IA ([F8](f8-conexoes-de-ia.md)).

## 01. Fluxo funcional

**Abrir o quadro.** A pessoa abre *Projetos*, escolhe o projeto e cai no quadro. As colunas são as etapas do projeto, na ordem configurada em F2; padrão: `Backlog`, `To do`, `Doing`, `Paused`, `Code-review`, `In test`, `Done` e `Support` (P10). O dev só vê os projetos em que está alocado (P5).

**Criar o card.** No topo do `Backlog`, qualquer alocado escreve o título e tecla Enter. O card nasce no `Backlog`, com a próxima chave do projeto (ex.: `SGM-18`) e **nada mais**: sem tipo, área nem prioridade; quem for editar define dentro do card (P16, P17, P56, R4; decisão de 14/10, seção 11). O campo fica pronto para o próximo título, como no Trello. Ninguém cria card direto em outra coluna: o dev cria só no `Backlog`, e o gestor também começa por lá (P56). Projeto encerrado não recebe card novo (P7).

**Completar a ficha.** Clicar no card abre a ficha. Tudo além do título é opcional e vem recolhido: tipo, área, prioridade, dev, QA, participantes, horas previstas, card pai, capa, documento de requisição (Sendo / Quero / Para e informações adicionais) e critérios de aceite `CA_1…CA_n` (P17 a P20, P27, P57 a P59). No projeto de cliente, o gestor, ou a IA dele, escreve o card no modelo de requisição; no dia a dia do produto, título e duas linhas bastam. Ao ser definida como dev ou QA, a pessoa recebe aviso no sistema e por e-mail (P22).

**Pôr na sprint.** Arrastar o card do `Backlog` para o `To do` põe o card na sprint aberta. Só o gestor e o Administrador fazem isso; sem sprint aberta, abre o diálogo de criar sprint já preenchido (P45). O diálogo e a sprint estão em F5.

**Andar pelo quadro.** Do `To do` em diante, qualquer alocado arrasta o card para qualquer coluna (P13). O sistema não pede dev, horas, PR nem parecer para mover (premissa). Cada movimento fica gravado: quem, quando, de onde, para onde e por qual canal, tela ou qual IA (P14, R14). O dev cola os links dos PRs no card (P23), comenta e anexa prints (P24).

**Pausar.** Levar o card para a coluna de pausa (`Paused`) pede o motivo, quando o projeto exige; o gestor desliga essa exigência em F2 (P15). O tempo parado é contado à parte (F7).

**Devolver (retrabalho).** Levar o card de `Code-review`, `In test` ou `Done` de volta para `To do` ou `Doing` abre um diálogo com a origem (code review, teste cruzado, QA ou homologação) e o motivo, da lista geral, com texto quando é "Outro" (P39, P40). Confirmado, o card se move como qualquer outro, conta um ciclo de retrabalho, e o dev da tarefa recebe aviso no sistema e por e-mail (P22). A marca de teste cruzado é limpa e o roteiro volta para "a testar" (P34, P37; F4).

**Card de suporte.** Quando o cliente aciona a WCJ direto, o gestor clica em *Novo card de suporte* no topo da coluna `Support`, informa o título, quem pediu, por onde e quando, e confirma. O card nasce na coluna `Support`, do tipo Suporte e fora da sprint. Dali qualquer alocado o move. Se o gestor ligar o card à sprint (F5), ele continua na mesma coluna (P52).

**Bug de produção.** Achado um bug em produção de algo já entregue, cria-se um card novo e, na ficha, informa-se a tarefa de origem, do mesmo projeto. O card novo é outro card, não volta a original: conta como qualidade do projeto, não como retrabalho do dev (P25).

**Quebrar em cards filhos.** Uma tarefa grande vira um card pai: cada parte é um card normal do quadro (criado no `Backlog` como qualquer outro) que, na ficha, aponta para o pai. Um nível só. O filho tem dev, horas, sprint e histórico próprios e anda pelo quadro sozinho; o pai mostra no quadro quantos filhos estão a fazer, em andamento e concluídos, com uma barra de progresso (P57). Nada trava o pai pelos filhos nem os filhos pelo pai (premissa). Quem precisa acompanhar um card sem ser o dev nem o QA entra como participante (P58). Para o card ter imagem no quadro, qualquer alocado escolhe um anexo como capa (P59).

**Tirar do quadro.** O gestor ou o Administrador abre a ficha e escolhe *Excluir* (só card nunca usado) ou *Arquivar* (card com histórico, com motivo). Arquivados saem do quadro; *Desarquivar* devolve o card ao `Backlog` (P26).

## 02. Telas e campos

### Card (ficha)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Chave | só leitura: prefixo-número, ex.: `SGM-17` | | gerada ao criar | Sequencial por projeto; número de card excluído nunca é reaproveitado (P16). |
| Título | texto, até 200 | sim | | Único campo pedido ao criar (P17). |
| Tipo | lista do projeto: nasce com Melhoria, Correção de bug, Suporte, Mudança (GMUD) e Desenvolvimento, mais o que o gestor criar (decisão de 14/10, seção 11) | não | nenhum | Item desativado some da escolha e continua nos cards que já o usam (P18, G7). Escolher Suporte pede os campos do pedido (ver "Card de suporte" abaixo). Cada tipo tem a cor da etiqueta; nome e cor se editam no quadro, clicando na etiqueta, só neste projeto. |
| Área | lista do projeto | não | nenhuma | A lista nasce vazia e é do gestor (F2). Lista vazia: o campo diz "Nenhuma área no projeto" (P18). Cada área tem a cor da etiqueta; o gestor cria e edita nome e cor no quadro, clicando na etiqueta ou no "+ Área" do card (decisão de 14/10, seção 11). |
| Prioridade | lista fixa: Baixa, Normal, Alta, Urgente | não | nenhuma | (P19) Sem prioridade o card fica sem a aba colorida e vai para o fim da ordem por prioridade (14/10). |
| Dev | uma pessoa, entre as alocadas no projeto e ativas | não | vazio | Um só. Pode ficar vazio em qualquer coluna. Definir avisa a pessoa (P20, P22). Quem sai do projeto deixa o card sem dev (P5). |
| QA | uma pessoa, entre as alocadas no projeto e ativas | não | vazio | Um só, opcional. Definir avisa a pessoa (P20, P22). |
| Horas previstas | horas com decimais, maior que 0 | não | vazio | Total da tarefa, Dev + QA (P27). Quem define ou valida é o gestor, na montagem da sprint (F5). Gestor e Administrador mudam direto; o dev **propõe** um valor novo, que fica ao lado como "Proposta: 12 h, aguardando o gestor" até ser aprovado ou recusado (P27). Só a aprovação é reestimar e recalcula a data prevista (P28). |
| Sendo | texto, até 200 | não | | Documento de requisição, bloco recolhido (P17). |
| Quero | texto longo | não | | Idem. |
| Para | texto longo | não | | Idem. |
| Informações adicionais | texto longo | não | | Idem. Imagens vão como anexo. |
| Critérios de aceite | lista `CA_1…CA_n`, com subitens `CA_n.m` | não | nenhum | Ver tabela própria abaixo (P17). |
| PRs | lista de links, cada um até 500 caracteres | não | nenhum | Nenhum, um ou vários; o mesmo link duas vezes no card é recusado (P23). |
| Comentários | texto longo, com autor e data | não | | (P24) |
| Anexos | arquivos, com o nome original | não | | Download com o nome original (P24). A evidência do QA por critério é F4. |
| Tarefa de origem | um card do mesmo projeto, pela chave | não | vazio | Preenchida = bug de produção da tarefa indicada (P25). Não aceita o próprio card. |
| Card pai | um card do mesmo projeto, pela chave | não | vazio | Um nível só: a busca não oferece card que já tem pai nem card que tem filhos, e não aceita o próprio card (P57). Na ficha do filho, a chave e o título do pai com link. |
| Cards filhos | só leitura: lista com chave, título, coluna e dev | | | Só no card pai; com o resumo "2 a fazer · 1 em andamento · 3 concluídos" e a barra (P57). Filho arquivado não aparece nem conta. |
| Participantes | zero ou mais pessoas, entre as alocadas no projeto e ativas | não | nenhum | Além do dev e do QA. Qualquer alocado adiciona e remove, inclusive a si mesmo; sem aviso (P58). Quem sai do projeto sai da lista (P5). |
| Capa | um dos anexos de imagem do card | não | nenhuma | Escolhida na lista de anexos (*Usar como capa* / *Tirar a capa*). Remover o anexo tira a capa (P59). |
| Sprint | só leitura | | | Nome da sprint ou "Fora de sprint" (P45; F5). |
| Pacote | só leitura | | | Nome da versão ou vazio (P41; F6). |
| Data prevista | só leitura | | | Calculada na primeira entrada em `Doing`; vazia sem dev ou sem horas (P28; conta em F5). |
| Teste cruzado | só leitura | | | "Testado por Fulano em DD/MM/AAAA" ou vazio (P34; marcar é F4). |
| Situação | só leitura | | Ativo | Ativo, ou "Arquivado em DD/MM/AAAA: motivo" (P26). |
| Histórico | link | | | Abre a linha do tempo do card: movimentos, quem, quando, canal (P14; F7). |

### Critério de aceite (dentro da ficha)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Número | só leitura: `CA_3`, ou `CA_4.1` no subitem | | próximo do card, ou do pai | Gravado ao criar e estável: excluir não renumera. Critério novo entra no fim; subitem novo entra no fim do pai (P17, T9). Um nível de subitem, como nos modelos SGM. |
| Descrição | texto longo | sim | | O que o QA vai conferir. Pode citar outro card pela chave, ex.: "(SGM-21)". |
| Resultado | só leitura aqui: A testar, Aprovado, Reprovado | | A testar | Marcado pelo QA em F4 (P36). Editar a descrição de um critério já testado volta o resultado para A testar (P37). |

### Card de suporte (criar)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Título | texto, até 200 | sim | | (P52) |
| Quem pediu | um contato ativo do cliente do projeto | sim | | Sem contato cadastrado, o gestor cadastra na ficha do cliente (F1, N8). Contato anonimizado aparece como "removido a pedido do titular" (N12). |
| Por onde | lista fixa: WhatsApp, Telefone, E-mail, Reunião, Outro | sim | | (P52) |
| Quando | data e hora | sim | agora (proposta) | Quando o pedido chegou (P52). |
| Tipo | fixo: Suporte | | Suporte | (P18, P52) |
| Prioridade | lista fixa | sim | Normal | (P19) |

Os demais campos da ficha valem igual no card de suporte, depois de criado.

### Diálogo de pausa

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Motivo da pausa | texto, até 500 | sim, quando o projeto exige | | Com a exigência desligada em F2, o card vai para a pausa sem diálogo (P15). |

### Diálogo de retrabalho (volta para sprint ou desenvolvimento)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Foi engano | caixa de marcar: "Arrastei para a coluna errada; não é retrabalho" | não | desmarcada | Marcada, esconde origem e motivo; o movimento fica gravado sem eles e não conta retrabalho (P39). |
| Origem | lista fixa: Code review, Teste cruzado, QA, Homologação | sim, salvo engano | | (P39) |
| Motivo | lista geral: Ajuste visual, Regra de negócio, Erro / bug, Requisito incompleto ou mal documentado, Outro, e o que o Administrador cadastrar | sim, salvo engano | | Só itens ativos (P40, G7). |
| Texto do motivo | texto, até 500 | sim quando o motivo é "Outro" | | (P40) |

### Diálogo de arquivar

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Motivo | texto, até 500 | sim | | (P26) |

## 03. Listagem

**O quadro.** Uma coluna por etapa ativa do projeto, na ordem de F2. No título de cada coluna, o nome e a contagem de cards nela. Dentro da coluna, a ordem padrão é por prioridade (Urgente primeiro) e, na mesma prioridade, pela chave, do mais antigo para o mais novo. Arrastar um card o deixa exatamente onde foi solto, na mesma coluna ou em outra, como no Trello (decisão de Lucas Scholz em 14/10): o quadro guarda a posição e o seletor *Ordem: Prioridade | Manual* no alto do quadro passa sozinho para *Manual* (P13). Escolher *Prioridade* de novo volta à ordem por prioridade, sem apagar as posições guardadas. A escolha do seletor é de cada pessoa, lembrada no navegador. Na coluna de pronto aparecem só os cards da sprint aberta e da última encerrada; os mais antigos vêm com o filtro *Mostrar prontos antigos* (P13).

**O que aparece em cada card do quadro** (disposição decidida em 13/10 sobre a proposta de Lucas Varanda, seção 11):

| Zona | Item | Quando aparece | Regra |
|---|---|---|---|
| Topo | Prioridade | quando definida | Baixa, Normal, Alta, Urgente: a faixa colorida no topo do card com o nome da prioridade escrito nela (P19; decisão de 14/10, seção 11). Sem prioridade, o card é liso. |
| Cabeçalho | Tipo | quando definido | Como etiqueta colorida, sempre a primeira (P18; decisão de 14/10, seção 11). Sem tipo, o chip "+ Tipo" abre a lista. |
| Cabeçalho | Área | quando há área | Como etiqueta colorida (a "tag" do card), depois do tipo; uma por card (P18). |
| Cabeçalho | Chave e título | sempre | (P16) |
| Cabeçalho | Card pai | no card filho | A chave do pai, com link para a ficha dele (P57). |
| Cabeçalho | Menu ⋯ | sempre | *Mover para…* (mesmas regras do arrastar: pausa pede motivo, volta pede origem e motivo, sair do `Backlog` só gestor e Administrador), *Copiar link* (a URL da ficha), *Área*, *Prioridade* e *Abrir ficha*. |
| Corpo | Capa | quando escolhida | A imagem do anexo escolhido (P59). |
| Corpo | Horas previstas | quando há horas | Com a marca "reestimativa proposta" quando o dev propôs e o gestor ainda não decidiu (P27). |
| Rodapé | Pessoas | quando há alguém | Dev, QA e participantes, como avatares com o nome ao passar o mouse; dev e QA destacados. O botão *+* adiciona ou remove participante ali mesmo (P20, P58). |
| Rodapé | Data prevista | quando calculada | (P28; F5) |
| Rodapé | Marca de atraso | passou da data prevista sem chegar ao `Done`, descontado o tempo em pausa | A conta é a de F5 e F7 (P53, R16). |
| Rodapé | Nº de anexos | quando há anexo | Contagem (P24). |
| Rodapé | Nº de comentários | quando há comentário | Contagem (P24). |
| Rodapé | Progresso dos filhos | no card pai | "2 · 1 · 3" (a fazer, em andamento, concluídos) e a barra de progresso, pela coluna em que cada filho está; filho arquivado não conta (P57). |
| Marcas | Pausa | card numa etapa de pausa | Com o motivo, quando houver (P15). |
| Marcas | Retrabalho | o card já voltou ao menos uma vez | Com o número de voltas (P39). |
| Marcas | Suporte | card do tipo Suporte | Com quem pediu (P52). |

**O que fica fora do quadro:** os cards arquivados (P26) e, por padrão, os cards em `Done` de sprints já encerradas (ver 10.3). Os dois aparecem pelos filtros da seção 05. Com o filtro *Arquivados*, a lista mostra Chave, Título, Tipo, Arquivado em e Motivo, do mais recente para o mais antigo.

**Dentro da ficha:** critérios na ordem `CA_1, CA_2…`, com os subitens logo abaixo do pai; comentários do mais antigo para o mais novo, com autor e data; PRs e anexos na ordem em que entraram; e, no card original, a lista "Bugs de produção ligados", com chave e título (P25).

**Estado vazio.** Projeto sem nenhum card: "Nenhum card ainda. Escreva o título no topo do Backlog e tecle Enter." Coluna vazia: só a contagem 0. Ficha sem critérios: "Nenhum critério. *Adicionar critério*". Sem PRs: "Nenhum PR. *Adicionar link*". Sem comentários: "Nenhum comentário. *Comentar*". Sem anexos: "Nenhum anexo. *Anexar arquivo*". Arquivados vazio: "Nenhum card arquivado".

## 04. Ações

### No quadro

| Ação | Quem | O que faz | Confirmação / aviso |
|---|---|---|---|
| Criar card rápido | Qualquer alocado | Título + Enter (o campo abre pelo "+" ao lado do nome da coluna): card no `Backlog` só com o título, sem tipo, área nem prioridade (P17, P56, R4; 14/10). Pela IA: `escrever_tarefa`, só com o título ou já com requisição e critérios (IA14). | Projeto encerrado: o campo não aparece, e a IA recebe "Projeto encerrado não recebe card novo" (P7). |
| Novo card de suporte | Gestor do projeto, Administrador | Cria na coluna `Support`, tipo Suporte, fora da sprint, com quem pediu, por onde e quando (P52). | Projeto encerrado: mesma recusa (P7). |
| Arrastar e soltar (mover) | Qualquer alocado, a partir do `To do` e do `Support` | Leva o card para a coluna escolhida e grava o movimento (P13, P14). Na ficha, *Mover para…* faz o mesmo. Pela IA: `mover_tarefa`. | Pausa e retrabalho abrem os diálogos abaixo; o resto move direto. |
| Pôr na sprint | Gestor do projeto, Administrador | Arrastar do `Backlog` para o `To do`. O diálogo da sprint é F5 (P45). | Dev que tenta: "Só o gestor do projeto e o Administrador põem card na sprint" (P45). |
| Levar para o `Backlog` | Gestor do projeto, Administrador | Tira o card da sprint, se estiver numa, e o leva ao `Backlog`. Não conta retrabalho (P45; F5). | Dev que tenta: mesma recusa. |
| Reordenar na coluna | Qualquer alocado | Arrasta o card para cima ou para baixo e a posição fica guardada para todos (P13); soltar em outra coluna também guarda a posição em que caiu. Com *Ordem: Prioridade*, o soltar troca o seletor para *Manual* (14/10). | |
| Ir para a pausa | Qualquer alocado | Pede o motivo, quando o projeto exige (P15). | Cancelar o diálogo deixa o card onde estava. |
| Mover para `Done` | Qualquer alocado | Como qualquer movimento; se o roteiro tem item reprovado ou a testar, avisa e deixa seguir (P38; F4). | "O roteiro tem N itens reprovados e M a testar. Mover mesmo assim?" |
| Devolver (retrabalho) | Qualquer alocado | De `Code-review`, `In test` ou `Done` para `To do` ou `Doing`: pede origem e motivo; o card se move do mesmo jeito e conta um ciclo (P39, P40). Com *Foi engano* marcado, move sem origem nem motivo e não conta (P39). Limpa a marca de teste cruzado e volta o roteiro para A testar (P34, P37; F4). Avisa o dev (P22). Pela IA: `mover_tarefa`, com origem e motivo, ou com a marca de engano. | Cancelar o diálogo deixa o card onde estava. |

### Na ficha

| Ação | Quem | O que faz | Confirmação / aviso |
|---|---|---|---|
| Editar campos | Qualquer alocado | Título, tipo, área, prioridade, dev, QA, requisição, tarefa de origem (P17 a P20, P25). | Ao definir dev ou QA: "Fulano será avisado no sistema e por e-mail" (P22). Quem se define a si mesmo não recebe aviso. |
| Definir / limpar o card pai | Qualquer alocado | Aponta o card para um pai do mesmo projeto, ou limpa (P57). Também no quadro, arrastando um card para cima do outro com a tecla Alt (proposta, 11.4). | Card que tem filhos: "SGM-10 já é pai de outros cards; um nível só." Card que já tem pai: "SGM-12 já é filho de SGM-10." |
| Adicionar / remover participante | Qualquer alocado | Põe ou tira uma pessoa alocada na lista de participantes, na ficha ou pelo *+* do card (P58). | Sem aviso. Dev ou QA do card não entram como participante: já estão no card. |
| Usar como capa / tirar a capa | Qualquer alocado | Escolhe um anexo de imagem como capa do card, ou limpa (P59). | Só anexo de imagem mostra a opção. |
| Horas previstas: definir ou reestimar | Gestor do projeto, Administrador | Grava o valor; se a data prevista já existe, recalcula (P27, P28). | "A data prevista vai de DD/MM para DD/MM. Confirmar?" |
| Horas previstas: propor | Desenvolvedor alocado | Grava a proposta ao lado do valor atual; o card ganha a marca "reestimativa proposta" no quadro e na lista de tarefas da sprint (P27). | |
| Aprovar ou recusar a proposta | Gestor do projeto, Administrador | Aprovar passa a proposta a valer e recalcula a data prevista; recusar só limpa a proposta. O antes fica no histórico (P27, P28). | |
| Adicionar critério / subitem | Qualquer alocado | Novo `CA_n` no fim, ou `CA_n.m` no fim do pai (P17, T9). | |
| Editar critério | Qualquer alocado | Muda a descrição. | Já testado: "Este critério volta para A testar" (P37). |
| Excluir critério | Qualquer alocado | Só enquanto o critério, e cada subitem dele, está A testar (T1). Os subitens saem junto. Os outros critérios não mudam de número; o próximo nasce com o maior número + 1 (`banco.md`), então excluir o último libera o número dele (decisão de 20/10, spec `b3-ficha`). | "Excluir CA_3? Os outros critérios não mudam de número." Testado: o botão não aparece (ver 10.9). |
| Adicionar / remover PR | Qualquer alocado | Cola ou remove o link (P23). | Link repetido no card: "Este PR já está no card". |
| Comentar | Qualquer alocado | Adiciona comentário com autor e data (P24). Pela IA: `comentar_tarefa`. | |
| Editar / excluir comentário | O autor | Proposta: só o próprio comentário (ver 10.10). | Excluir: "Excluir o comentário?" |
| Anexar / remover anexo | Qualquer alocado | Envia ou remove o arquivo (P24). | Remover: "Remover o anexo? O arquivo será apagado." |
| Histórico | Qualquer alocado | Abre a linha do tempo do card (F7). | |
| Arquivar | Gestor do projeto, Administrador | Pede o motivo. O card sai do quadro e das contas de prazo, atraso e capacidade; as horas lançadas nele continuam nos indicadores de horas (P26). | "Arquivar SGM-17? Ele sai do quadro e volta pelo filtro Arquivados." |
| Desarquivar | Gestor do projeto, Administrador | Limpa o arquivamento, tira da sprint e leva ao `Backlog`. Não conta retrabalho (P26). | |
| Excluir card | Gestor do projeto, Administrador | Só o card nunca usado: nenhum movimento além da criação; fora de sprint; sem horas, comentários, anexos nem PRs; nenhum bug de produção nem card filho apontando para ele; sem teste cruzado marcado. Os critérios, os participantes e o movimento de criação saem junto; os avisos do card ficam, sem o link (§07); o card arquivado nunca usado também se exclui (P26). | "Excluir de verdade. Não dá para desfazer. A chave SGM-17 não volta a ser usada." Card com histórico: o botão não aparece; só *Arquivar*. |

### Pela IA (fatia 1)

As ferramentas chamam a mesma regra da tela e têm exatamente a permissão da pessoa (G4, IA5). Detalhe em F8.

| Ferramenta | Equivale a | Observação |
|---|---|---|
| `escrever_tarefa` | Criar card rápido / completar requisição e critérios | Cria no `Backlog`; não cria card de suporte. Não existe editar card pela IA na fatia 1. |
| `mover_tarefa` | Arrastar e soltar | Mesmas regras: pausa pede motivo, volta pede origem e motivo; dev não leva para nem tira do `Backlog`. |
| `comentar_tarefa` | Comentar | O comentário sai com o nome da pessoa; o canal fica no histórico. |
| `minhas_tarefas` | Filtro *Só os meus* | Cards em que a pessoa é dev, QA ou participante (P58), com filtro por prazo e projeto. |
| `status_do_projeto` | Abrir o quadro | Colunas com contagem, cards atrasados e próximas datas previstas. |

`lancar_horas` está em F5. A IA não exclui nem arquiva card (IA8).

## 05. Filtros e busca

| Onde | Busca | Filtros |
|---|---|---|
| Quadro | chave (ex.: "SGM-17") ou trecho do título | *Só os meus* (sou dev, QA ou participante, P58); Dev; QA; Tipo; Área; Prioridade; Sprint (padrão: todas); Pacote (para a homologação, F6); Card pai (os filhos de um card, P57); marcas: Atrasado, Em pausa, Com retrabalho, Suporte, Reestimativa proposta; *Mostrar prontos antigos* (prontos de sprints anteriores à última encerrada, P13); *Arquivados* (troca o quadro pela lista de arquivados); seletor *Ordem: Prioridade | Manual* (P13) |
| Card pai (na ficha) | chave ou título | só cards do mesmo projeto, sem pai e sem filhos (P57) |
| Tarefa de origem (na ficha) | chave ou título | só cards do mesmo projeto |
| Quem pediu (card de suporte) | nome ou e-mail | só contatos ativos do cliente do projeto |

Filtro sem resultado: "Nenhum card com esses filtros".

## 06. Indicadores

- **Contagem por coluna:** número de cards em cada coluna, respeitando os filtros (P10).
- **Marcas no card:** atraso, pausa, retrabalho com o número de voltas, suporte e reestimativa proposta (seção 03).
- **Progresso do card pai:** filhos a fazer, em andamento e concluídos, pela coluna de cada um, e a barra (P57; seção 03). Só referência: não trava nada.

Tempo por etapa, previsto × realizado, atraso somado e retrabalho por projeto estão em F7 (P53); capacidade da sprint, em F5 (P49).

## 07. Avisos e documentos gerados

São os únicos avisos de Projetos na fatia 1 (P22, R3). Cada um sai **no sistema** (na lista de avisos da pessoa, com link para o card) **e por e-mail**, pelo SMTP da WCJ. Vale igual quando a ação vem pela IA.

| Quando | Para quem | Conteúdo |
|---|---|---|
| A pessoa é definida como dev do card | A pessoa definida | "Você é o dev de SGM-17 — <título> (projeto <nome>), definido por Fulano." + link para o card. |
| A pessoa é definida como QA do card | A pessoa definida | "Você é o QA de SGM-17 — <título> (projeto <nome>), definido por Fulano." + link para o card. |
| O card volta para sprint ou desenvolvimento (retrabalho), pela tela, pela IA ou pela homologação do pacote (F6) | O dev do card; sem dev, ninguém | "SGM-17 — <título> voltou para <coluna>: origem <QA>, motivo <Ajuste visual / texto do Outro>, por Fulano." + link para o card. |

Card excluído (§04 "Excluir card"): os avisos dele ficam na lista da pessoa, com o mesmo texto e sem o link; o aviso é sempre da pessoa, e cada uma marca só os próprios como lidos (B3 PR 3).

Proposta: quem define a si mesmo como dev ou QA não recebe aviso (ver 10.5). Nenhum documento é gerado: o documento de requisição em Word ou PDF fica para depois da v1 (P55).

## 08. Exportação

Não se aplica na fatia 1: nenhuma regra pede exportar o quadro ou o card (P55 fica para depois da v1).

## 09. Permissionamento

| O quê | Administrador | Gestor de projetos | Desenvolvedor |
|---|---|---|---|
| Ver o quadro e as fichas | todos os projetos | projetos que gere | projetos em que está alocado (P5) |
| Criar card no `Backlog` | sim | nos projetos dele | nos projetos em que está alocado (P56) |
| Criar card de suporte | sim | nos projetos dele | não (P52; ver 10.8) |
| Editar a ficha, critérios, PRs, tarefa de origem | sim | sim | sim (premissa) |
| Definir card pai, participantes e capa | sim | sim | sim (P57 a P59; é editar a ficha) |
| Mudar horas previstas | sim | sim | só enquanto o card está no `Backlog` (proposta, 10.4) |
| Mover a partir do `To do` e do `Support`, pausar, devolver | sim | sim | sim (P13, P52) |
| Pôr na sprint, levar para o `Backlog` | sim | sim | não (P45) |
| Comentar, anexar | sim | sim | sim (P24) |
| Editar e excluir comentário | o próprio | o próprio | o próprio (proposta, 10.10) |
| Arquivar, desarquivar, excluir card | sim | sim | não (P26) |
| Ver arquivados e o histórico do card | sim | sim | sim, nos projetos em que está |

"Gestor de projetos" aqui é o gestor do projeto. Ser dev ou QA de um card exige estar alocado no projeto (F2), qualquer que seja o papel. Projeto encerrado: ninguém cria card, nem o Administrador, até o gestor ou o Administrador reabrir (P7; F2). O Comercial não entra em Projetos (C14). A IA de cada pessoa tem exatamente estas permissões, nem mais nem menos (G4).

## 10. Pontos a validar

Respondidos por Lucas Scholz em 06/10/2026 e já aplicados nas seções acima, na `necessidade.md` (P13, P24, P27, P39) e no `banco.md` (duas colunas aditivas em `tasks`: posição manual e reestimativa proposta).

1. **Ordem dos cards na coluna.** Padrão por prioridade; **ordem manual quando a pessoa quiser** (seletor no quadro, posição guardada; P13).
2. **Soltar do `Backlog` direto em `Doing`.** OK: entra na sprint e segue para a coluna escolhida, com os dois movimentos no histórico.
3. **`Done` sem fim.** A coluna de pronto mostra os cards da **sprint aberta e da última encerrada**; da penúltima para trás, só pelo filtro *Mostrar prontos antigos* (P13).
4. **Horas previstas.** O dev **pode reestimar: propõe, e o gestor ou o Administrador aprova** (P27). Gestor e Administrador mudam direto.
5. **Aviso para si mesmo.** OK: quem se define como dev ou QA não recebe aviso.
6. **Volta por engano.** **Não conta retrabalho**: a caixa *Foi engano* no diálogo grava o movimento sem origem nem motivo (P39).
7. **Trocar o tipo para ou de Suporte.** OK.
8. **Dev cria card de suporte?** OK: só gestor e Administrador (P52).
9. **Excluir critério "não testado".** OK: vale o resultado atual.
10. **Comentário.** OK: o autor edita e exclui o próprio; o antes fica no histórico (P24).
11. **Anexo.** OK: limite do template; qualquer alocado remove (P24).

## 11. Decisões de 13/10 sobre o card do quadro (versão 1.1)

Lucas Varanda propôs o desenho do card (prioridade fora do card; cabeçalho com tags, título, menu ⋯ e card pai; corpo com esforço e imagem; rodapé com pessoas, previsão, atraso, anexos, comentários, subtasks e barra). Lucas Scholz decidiu em 13/10 (D-GER-32); já aplicado nas seções 01 a 09, na `necessidade.md` (P57 a P59) e no `banco.md` (seção 7, aprovado por Lucas Scholz em 15/10).

1. **Tags de tipo (várias por card).** Não: a "tag" é a **Área** do projeto, uma por card (P18). O Tipo continua à parte. Tag múltipla pediria tabela N:N e se confundiria com etapa e papel.
2. **Card pai e subtasks.** **Card pai, um nível** (P57). A subtask é um card normal do quadro, com dev, horas, sprint e histórico; o pai mostra o progresso pela coluna dos filhos. Descartada a subtask dentro do card: seria um segundo checklist ao lado dos `CA_n` (P17).
3. **Pontuação de esforço.** Não: só **Horas previstas** (P27), que já alimentam a data prevista e a capacidade.
4. **Usuários no card.** Além de Dev e QA, **participantes** (P58). Premissas decididas aqui, de baixo risco: participante não recebe aviso (P22 segue com dois avisos só), não entra na capacidade nem em indicador por pessoa, e entra no *Só os meus* e em `minhas_tarefas`. Definir o pai arrastando com Alt é proposta de interação, não regra: o front decide se entra no B3.
5. **Imagem no corpo do card.** **O usuário escolhe** qual anexo é a capa (P59); sem escolha, sem imagem.
6. **Faltava na proposta** e está no card: chave junto do título (P16), tipo (P18), marcas de pausa (P15), retrabalho com o número de voltas (P39), suporte com quem pediu (P52) e reestimativa proposta (P27).
7. **Cor das etiquetas (14/10, ao ver o kit do quadro).** Tipo e área ganham cor, como no Trello: paleta fechada de 10 cores (cinza, azul, azul-claro, verde, verde-limão, amarelo, laranja, vermelho, rosa, roxo), escolhida no cadastro do tipo (lista geral, F1) e da área (F2); sem cor = cinza. No card e na ficha a ordem é sempre tipo primeiro, área depois. Banco: `task_types.color` e `project_areas.color` (`banco.md` seção 7, aprovado pelo Lucas em 15/10).
8. **Prioridade evidente (14/10).** A faixa colorida no topo do card passa a ter o nome da prioridade escrito nela (Urgente em vermelho, Alta em âmbar, Normal em azul, Baixa em verde), pela referência visual que o Lucas mandou; a pílula no canto inferior direito, testada antes no mesmo dia, saiu.
9. **Etiquetas editadas no quadro, por projeto (14/10, mais tarde).** Como no Trello: clicar na etiqueta de tipo ou de área (ou no "+ Área" do card sem área) abre a lista do projeto para escolher; o gestor e o Administrador editam nome e cor ali (lápis) e criam novos (*Novo tipo* / *Nova área*). Tipo passa a ser **do projeto**: todo projeto nasce com Melhoria, Correção de bug, Suporte, Mudança (GMUD) e **Desenvolvimento** (funcionalidade do zero, sem nada feito ainda); o que se cria ou edita não propaga a outros projetos. Nada é configurado em tela à parte. Banco: `task_types.project_id` (`banco.md` seção 7, aprovado pelo Lucas em 15/10); o card rápido nasce **sem tipo, sem área e sem prioridade** (P17; decisão final do Lucas em 14/10, depois de passar por Melhoria e Desenvolvimento): quem for editar define dentro do card. No banco, `task_type_id` e `task_priority_id` passam a aceitar nulo (`banco.md` seção 7).
12. **Card arquivado.** OK: ficha continua abrindo; só sai do quadro e das contas.
13. **Quadro de projeto encerrado.** OK: mover, comentar e anexar seguem (P7).
14. **Tipo do bug de produção.** OK: escolhido à mão na fatia 1.
15. **IA pondo card na sprint sem sprint aberta.** OK: o Service cria a sprint proposta e segue.
