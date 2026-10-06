# F3. Quadro e card

**Documento funcional.** Versão 0.1, de 06/10/2026, para aprovação. Estrutura e fluxo em [`README.md`](README.md).

| | |
|---|---|
| **Objetivo** | Dar ao gestor e aos devs o quadro kanban de cada projeto, no lugar do Trello: criar o card em segundos, escrever nele o que for preciso, arrastar entre as colunas sem trava e guardar quem moveu, quando e por quê. |
| **Regras que alimentam** | Premissa de Projetos, P13 a P26, P39, P40, P52, P56; citadas: P7, P14, P16, P17, P45 (`docs/necessidade.md`). |
| **Tabelas do `banco.txt`** | `tasks`, `task_stage_movements`, `task_priorities`, `task_types`, `task_acceptance_criteria`, `task_pull_requests`, `task_comments`, `task_attachments`, `validation_types` (origem do retrabalho), `rework_reasons`, `contacts` e `contact_channels` (card de suporte). |
| **Depende de** | [F1](f1-nucleo.md) (usuários, contatos) e [F2](f2-projeto-e-configuracao.md) (projeto, pessoas alocadas, colunas, áreas, tipos, motivos de retrabalho). |
| **Quem aprova** | Rafael Garcia (dono de Projetos, D-GER-02). |

Neste documento, **"qualquer alocado"** quer dizer: o Administrador, o gestor do projeto e o Desenvolvedor alocado no projeto. Nada aqui trava o fluxo (premissa): as únicas recusas são de permissão e de dado inválido.

Fica fora deste bloco, só apontado: configurar colunas, áreas e listas ([F2](f2-projeto-e-configuracao.md)); marcar o teste cruzado e rodar o roteiro de QA ([F4](f4-teste-e-qualidade.md)); criar e encerrar sprint, lançar horas e calcular a data prevista ([F5](f5-sprint-e-horas.md)); pacote ([F6](f6-pacote.md)); indicadores e linha do tempo ([F7](f7-indicadores.md)); conectar a IA ([F8](f8-conexoes-de-ia.md)).

## 01. Fluxo funcional

**Abrir o quadro.** A pessoa abre *Projetos*, escolhe o projeto e cai no quadro. As colunas são as etapas do projeto, na ordem configurada em F2; padrão: `Backlog`, `To do`, `Doing`, `Paused`, `Code-review`, `In test`, `Done` e `Support` (P10). O dev só vê os projetos em que está alocado (P5).

**Criar o card.** No topo do `Backlog`, qualquer alocado escreve o título e tecla Enter. O card nasce no `Backlog`, com a próxima chave do projeto (ex.: `SGM-18`), tipo Melhoria e prioridade Normal (P16, P17, P56, R4). O campo fica pronto para o próximo título, como no Trello. Ninguém cria card direto em outra coluna: o dev cria só no `Backlog`, e o gestor também começa por lá (P56). Projeto encerrado não recebe card novo (P7).

**Completar a ficha.** Clicar no card abre a ficha. Tudo além do título é opcional e vem recolhido: tipo, área, prioridade, dev, QA, horas previstas, documento de requisição (Sendo / Quero / Para e informações adicionais) e critérios de aceite `CA_1…CA_n` (P17 a P20, P27). No projeto de cliente, o gestor, ou a IA dele, escreve o card no modelo de requisição; no dia a dia do produto, título e duas linhas bastam. Ao ser definida como dev ou QA, a pessoa recebe aviso no sistema e por e-mail (P22).

**Pôr na sprint.** Arrastar o card do `Backlog` para o `To do` põe o card na sprint aberta. Só o gestor e o Administrador fazem isso; sem sprint aberta, abre o diálogo de criar sprint já preenchido (P45). O diálogo e a sprint estão em F5.

**Andar pelo quadro.** Do `To do` em diante, qualquer alocado arrasta o card para qualquer coluna (P13). O sistema não pede dev, horas, PR nem parecer para mover (premissa). Cada movimento fica gravado: quem, quando, de onde, para onde e por qual canal, tela ou qual IA (P14, R14). O dev cola os links dos PRs no card (P23), comenta e anexa prints (P24).

**Pausar.** Levar o card para a coluna de pausa (`Paused`) pede o motivo, quando o projeto exige; o gestor desliga essa exigência em F2 (P15). O tempo parado é contado à parte (F7).

**Devolver (retrabalho).** Levar o card de `Code-review`, `In test` ou `Done` de volta para `To do` ou `Doing` abre um diálogo com a origem (code review, teste cruzado, QA ou homologação) e o motivo, da lista geral, com texto quando é "Outro" (P39, P40). Confirmado, o card se move como qualquer outro, conta um ciclo de retrabalho, e o dev da tarefa recebe aviso no sistema e por e-mail (P22). A marca de teste cruzado é limpa e o roteiro volta para "a testar" (P34, P37; F4).

**Card de suporte.** Quando o cliente aciona a WCJ direto, o gestor clica em *Novo card de suporte* no topo da coluna `Support`, informa o título, quem pediu, por onde e quando, e confirma. O card nasce na coluna `Support`, do tipo Suporte e fora da sprint. Dali qualquer alocado o move. Se o gestor ligar o card à sprint (F5), ele continua na mesma coluna (P52).

**Bug de produção.** Achado um bug em produção de algo já entregue, cria-se um card novo e, na ficha, informa-se a tarefa de origem, do mesmo projeto. O card novo é outro card, não volta a original: conta como qualidade do projeto, não como retrabalho do dev (P25).

**Tirar do quadro.** O gestor ou o Administrador abre a ficha e escolhe *Excluir* (só card nunca usado) ou *Arquivar* (card com histórico, com motivo). Arquivados saem do quadro; *Desarquivar* devolve o card ao `Backlog` (P26).

## 02. Telas e campos

### Card (ficha)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Chave | só leitura: prefixo-número, ex.: `SGM-17` | | gerada ao criar | Sequencial por projeto; número de card excluído nunca é reaproveitado (P16). |
| Título | texto, até 200 | sim | | Único campo pedido ao criar (P17). |
| Tipo | lista geral: Melhoria, Correção de bug, Suporte, Mudança (GMUD) e o que o Administrador cadastrar | sim | Melhoria | Item desativado some da escolha e continua nos cards que já o usam (P18, G7). Escolher Suporte pede os campos do pedido (ver "Card de suporte" abaixo). |
| Área | lista do projeto | não | nenhuma | A lista nasce vazia e é do gestor (F2). Lista vazia: o campo diz "Nenhuma área no projeto" (P18). |
| Prioridade | lista fixa: Baixa, Normal, Alta, Urgente | sim | Normal | (P19) |
| Dev | uma pessoa, entre as alocadas no projeto e ativas | não | vazio | Um só. Pode ficar vazio em qualquer coluna. Definir avisa a pessoa (P20, P22). Quem sai do projeto deixa o card sem dev (P5). |
| QA | uma pessoa, entre as alocadas no projeto e ativas | não | vazio | Um só, opcional. Definir avisa a pessoa (P20, P22). |
| Horas previstas | horas com decimais, maior que 0 | não | vazio | Total da tarefa, Dev + QA (P27). Quem define ou valida é o gestor, na montagem da sprint (F5). Mudar o valor é reestimar e recalcula a data prevista (P28; ver 10.4). |
| Sendo | texto, até 200 | não | | Documento de requisição, bloco recolhido (P17). |
| Quero | texto longo | não | | Idem. |
| Para | texto longo | não | | Idem. |
| Informações adicionais | texto longo | não | | Idem. Imagens vão como anexo. |
| Critérios de aceite | lista `CA_1…CA_n`, com subitens `CA_n.m` | não | nenhum | Ver tabela própria abaixo (P17). |
| PRs | lista de links, cada um até 500 caracteres | não | nenhum | Nenhum, um ou vários; o mesmo link duas vezes no card é recusado (P23). |
| Comentários | texto longo, com autor e data | não | | (P24) |
| Anexos | arquivos, com o nome original | não | | Download com o nome original (P24). A evidência do QA por critério é F4. |
| Tarefa de origem | um card do mesmo projeto, pela chave | não | vazio | Preenchida = bug de produção da tarefa indicada (P25). Não aceita o próprio card. |
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
| Origem | lista fixa: Code review, Teste cruzado, QA, Homologação | sim | | (P39) |
| Motivo | lista geral: Ajuste visual, Regra de negócio, Erro / bug, Requisito incompleto ou mal documentado, Outro, e o que o Administrador cadastrar | sim | | Só itens ativos (P40, G7). |
| Texto do motivo | texto, até 500 | sim quando o motivo é "Outro" | | (P40) |

### Diálogo de arquivar

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Motivo | texto, até 500 | sim | | (P26) |

## 03. Listagem

**O quadro.** Uma coluna por etapa ativa do projeto, na ordem de F2. No título de cada coluna, o nome e a contagem de cards nela. Dentro da coluna, os cards ficam por prioridade (Urgente primeiro) e, na mesma prioridade, pela chave, do mais antigo para o mais novo (ver 10.1).

**O que aparece em cada card do quadro:**

| Item | Quando aparece | Regra |
|---|---|---|
| Chave e título | sempre | (P16) |
| Tipo | sempre | (P18) |
| Prioridade | sempre | (P19) |
| Dev | quando há dev | Nome da pessoa (P20). |
| Data prevista | quando calculada | (P28; F5) |
| Marca de atraso | passou da data prevista sem chegar ao `Done`, descontado o tempo em pausa | A conta é a de F5 e F7 (P53, R16). |
| Marca de pausa | card numa etapa de pausa | Com o motivo, quando houver (P15). |
| Marca de retrabalho | o card já voltou ao menos uma vez | Com o número de voltas (P39). |
| Marca de suporte | card do tipo Suporte | Com quem pediu (P52). |

**O que fica fora do quadro:** os cards arquivados (P26) e, por padrão, os cards em `Done` de sprints já encerradas (ver 10.3). Os dois aparecem pelos filtros da seção 05. Com o filtro *Arquivados*, a lista mostra Chave, Título, Tipo, Arquivado em e Motivo, do mais recente para o mais antigo.

**Dentro da ficha:** critérios na ordem `CA_1, CA_2…`, com os subitens logo abaixo do pai; comentários do mais antigo para o mais novo, com autor e data; PRs e anexos na ordem em que entraram; e, no card original, a lista "Bugs de produção ligados", com chave e título (P25).

**Estado vazio.** Projeto sem nenhum card: "Nenhum card ainda. Escreva o título no topo do Backlog e tecle Enter." Coluna vazia: só a contagem 0. Ficha sem critérios: "Nenhum critério. *Adicionar critério*". Sem PRs: "Nenhum PR. *Adicionar link*". Sem comentários: "Nenhum comentário. *Comentar*". Sem anexos: "Nenhum anexo. *Anexar arquivo*". Arquivados vazio: "Nenhum card arquivado".

## 04. Ações

### No quadro

| Ação | Quem | O que faz | Confirmação / aviso |
|---|---|---|---|
| Criar card rápido | Qualquer alocado | Título + Enter no topo do `Backlog`: card no `Backlog`, tipo Melhoria, prioridade Normal (P17, P56, R4). Pela IA: `escrever_tarefa`, só com o título ou já com requisição e critérios (IA14). | Projeto encerrado: o campo não aparece, e a IA recebe "Projeto encerrado não recebe card novo" (P7). |
| Novo card de suporte | Gestor do projeto, Administrador | Cria na coluna `Support`, tipo Suporte, fora da sprint, com quem pediu, por onde e quando (P52). | Projeto encerrado: mesma recusa (P7). |
| Arrastar e soltar (mover) | Qualquer alocado, a partir do `To do` e do `Support` | Leva o card para a coluna escolhida e grava o movimento (P13, P14). Na ficha, *Mover para…* faz o mesmo. Pela IA: `mover_tarefa`. | Pausa e retrabalho abrem os diálogos abaixo; o resto move direto. |
| Pôr na sprint | Gestor do projeto, Administrador | Arrastar do `Backlog` para o `To do`. O diálogo da sprint é F5 (P45). | Dev que tenta: "Só o gestor do projeto e o Administrador põem card na sprint" (P45). |
| Levar para o `Backlog` | Gestor do projeto, Administrador | Tira o card da sprint, se estiver numa, e o leva ao `Backlog`. Não conta retrabalho (P45; F5). | Dev que tenta: mesma recusa. |
| Ir para a pausa | Qualquer alocado | Pede o motivo, quando o projeto exige (P15). | Cancelar o diálogo deixa o card onde estava. |
| Devolver (retrabalho) | Qualquer alocado | De `Code-review`, `In test` ou `Done` para `To do` ou `Doing`: pede origem e motivo; o card se move do mesmo jeito e conta um ciclo (P39, P40). Limpa a marca de teste cruzado e volta o roteiro para A testar (P34, P37; F4). Avisa o dev (P22). Pela IA: `mover_tarefa`, com origem e motivo. | Cancelar o diálogo deixa o card onde estava (ver 10.6). |

### Na ficha

| Ação | Quem | O que faz | Confirmação / aviso |
|---|---|---|---|
| Editar campos | Qualquer alocado | Título, tipo, área, prioridade, dev, QA, requisição, tarefa de origem (P17 a P20, P25). Horas previstas: ver 10.4. | Ao definir dev ou QA: "Fulano será avisado no sistema e por e-mail" (P22). |
| Adicionar critério / subitem | Qualquer alocado | Novo `CA_n` no fim, ou `CA_n.m` no fim do pai (P17, T9). | |
| Editar critério | Qualquer alocado | Muda a descrição. | Já testado: "Este critério volta para A testar" (P37). |
| Excluir critério | Qualquer alocado | Só enquanto o critério, e cada subitem dele, está A testar (T1). Os subitens saem junto. O número não volta a ser usado. | "Excluir CA_3? O número não será reaproveitado." Testado: o botão não aparece (ver 10.9). |
| Adicionar / remover PR | Qualquer alocado | Cola ou remove o link (P23). | Link repetido no card: "Este PR já está no card". |
| Comentar | Qualquer alocado | Adiciona comentário com autor e data (P24). Pela IA: `comentar_tarefa`. | |
| Editar / excluir comentário | O autor | Proposta: só o próprio comentário (ver 10.10). | Excluir: "Excluir o comentário?" |
| Anexar / remover anexo | Qualquer alocado | Envia ou remove o arquivo (P24). | Remover: "Remover o anexo? O arquivo será apagado." |
| Histórico | Qualquer alocado | Abre a linha do tempo do card (F7). | |
| Arquivar | Gestor do projeto, Administrador | Pede o motivo. O card sai do quadro e das contas de prazo, atraso e capacidade; as horas lançadas nele continuam nos indicadores de horas (P26). | "Arquivar SGM-17? Ele sai do quadro e volta pelo filtro Arquivados." |
| Desarquivar | Gestor do projeto, Administrador | Limpa o arquivamento, tira da sprint e leva ao `Backlog`. Não conta retrabalho (P26). | |
| Excluir card | Gestor do projeto, Administrador | Só o card nunca usado: nenhum movimento além da criação; sem horas, comentários, anexos nem PRs; nenhum bug de produção apontando para ele; sem teste cruzado marcado. Os critérios saem junto (P26). | "Excluir de verdade. Não dá para desfazer. A chave SGM-17 não volta a ser usada." Card com histórico: o botão não aparece; só *Arquivar*. |

### Pela IA (fatia 1)

As ferramentas chamam a mesma regra da tela e têm exatamente a permissão da pessoa (G4, IA5). Detalhe em F8.

| Ferramenta | Equivale a | Observação |
|---|---|---|
| `escrever_tarefa` | Criar card rápido / completar requisição e critérios | Cria no `Backlog`; não cria card de suporte. Não existe editar card pela IA na fatia 1. |
| `mover_tarefa` | Arrastar e soltar | Mesmas regras: pausa pede motivo, volta pede origem e motivo; dev não leva para nem tira do `Backlog`. |
| `comentar_tarefa` | Comentar | O comentário sai com o nome da pessoa; o canal fica no histórico. |
| `minhas_tarefas` | Filtro *Só os meus* | Cards em que a pessoa é dev ou QA, com filtro por prazo e projeto. |
| `status_do_projeto` | Abrir o quadro | Colunas com contagem, cards atrasados e próximas datas previstas. |

`lancar_horas` está em F5. A IA não exclui nem arquiva card (IA8).

## 05. Filtros e busca

| Onde | Busca | Filtros |
|---|---|---|
| Quadro | chave (ex.: "SGM-17") ou trecho do título | *Só os meus* (sou dev ou QA); Dev; QA; Tipo; Área; Prioridade; Sprint (padrão: todas, sem os prontos de sprints encerradas); marcas: Atrasado, Em pausa, Com retrabalho, Suporte; *Mostrar prontos de sprints encerradas*; *Arquivados* (troca o quadro pela lista de arquivados) |
| Tarefa de origem (na ficha) | chave ou título | só cards do mesmo projeto |
| Quem pediu (card de suporte) | nome ou e-mail | só contatos ativos do cliente do projeto |

Filtro sem resultado: "Nenhum card com esses filtros".

## 06. Indicadores

- **Contagem por coluna:** número de cards em cada coluna, respeitando os filtros (P10).
- **Marcas no card:** atraso, pausa, retrabalho com o número de voltas e suporte (seção 03).

Tempo por etapa, previsto × realizado, atraso somado e retrabalho por projeto estão em F7 (P53); capacidade da sprint, em F5 (P49).

## 07. Avisos e documentos gerados

São os únicos avisos de Projetos na fatia 1 (P22, R3). Cada um sai **no sistema** (na lista de avisos da pessoa, com link para o card) **e por e-mail**, pelo SMTP da WCJ. Vale igual quando a ação vem pela IA.

| Quando | Para quem | Conteúdo |
|---|---|---|
| A pessoa é definida como dev do card | A pessoa definida | "Você é o dev de SGM-17 — <título> (projeto <nome>), definido por Fulano." + link para o card. |
| A pessoa é definida como QA do card | A pessoa definida | "Você é o QA de SGM-17 — <título> (projeto <nome>), definido por Fulano." + link para o card. |
| O card volta para sprint ou desenvolvimento (retrabalho), pela tela, pela IA ou pela homologação do pacote (F6) | O dev do card; sem dev, ninguém | "SGM-17 — <título> voltou para <coluna>: origem <QA>, motivo <Ajuste visual / texto do Outro>, por Fulano." + link para o card. |

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
| Mudar horas previstas | sim | sim | só enquanto o card está no `Backlog` (proposta, 10.4) |
| Mover a partir do `To do` e do `Support`, pausar, devolver | sim | sim | sim (P13, P52) |
| Pôr na sprint, levar para o `Backlog` | sim | sim | não (P45) |
| Comentar, anexar | sim | sim | sim (P24) |
| Editar e excluir comentário | o próprio | o próprio | o próprio (proposta, 10.10) |
| Arquivar, desarquivar, excluir card | sim | sim | não (P26) |
| Ver arquivados e o histórico do card | sim | sim | sim, nos projetos em que está |

"Gestor de projetos" aqui é o gestor do projeto. Ser dev ou QA de um card exige estar alocado no projeto (F2), qualquer que seja o papel. Projeto encerrado: ninguém cria card, nem o Administrador, até o gestor ou o Administrador reabrir (P7; F2). O Comercial não entra em Projetos (C14). A IA de cada pessoa tem exatamente estas permissões, nem mais nem menos (G4).

## 10. Pontos a validar

1. **Ordem dos cards na coluna.** O Trello deixa arrastar para cima e para baixo; nenhuma regra pede isso, e o banco não guarda posição dentro da coluna. Proposta: prioridade (Urgente primeiro) e, dentro dela, a chave mais antiga primeiro. Ordem manual voltaria com um campo novo, se alguém pedir (G5).
2. **Soltar um card do `Backlog` direto em `Doing`.** No Wpay, 10 de 21 saídas do `Backlog` pularam a Esteira. Proposta: o gestor solta onde quiser; o sistema põe o card na sprint (vai ao `To do`) e em seguida o move para a coluna escolhida, e o histórico mostra os dois movimentos.
3. **`Done` sem fim.** Com o tempo, a coluna `Done` acumula centenas de cards. Proposta: por padrão o quadro esconde os prontos de sprints encerradas; o filtro *Mostrar prontos de sprints encerradas* traz de volta.
4. **Quem muda as horas previstas.** P27 deixa as horas opcionais ao criar, e P28 diz que a data prevista só muda "se o gestor reestimar". Proposta: qualquer alocado preenche enquanto o card está no `Backlog`; dali em diante, só o gestor e o Administrador. Confirmar se o dev pode reestimar depois de começar.
5. **Aviso para si mesmo.** Quando a pessoa se define como dev ou QA, recebe e-mail sobre o que ela mesma fez. Proposta: não avisar; o aviso existe para quem não sabia (item 20 da ata).
6. **Volta por engano.** P39 diz que toda volta conta retrabalho, e o movimento não se apaga (P14). Proposta: cancelar o diálogo desfaz o arraste; confirmada a volta, ela conta, mesmo que tenha sido engano.
7. **Trocar o tipo para ou de Suporte.** O card de suporte nasce pela coluna `Support`. Proposta: num card comum, escolher Suporte pede quem pediu, por onde e quando, sem trocar de coluna; trocar de Suporte para outro tipo apaga esses três campos (o antes fica no histórico).
8. **Dev cria card de suporte?** P52 diz "o gestor registra"; P56 deixa qualquer alocado criar card, mas no `Backlog`. Proposta: card de suporte só pelo gestor e pelo Administrador, como escrito na P52. Se o dev atende cliente direto, abrir para qualquer alocado.
9. **Excluir critério "não testado".** O banco diz "só enquanto não testado". Depois de um retrabalho todos voltam para A testar (P37). Proposta: vale o resultado atual; com A testar, o critério pode ser excluído, mesmo que já tenha sido testado antes.
10. **Comentário: editar e excluir.** P24 não diz. Proposta: o autor edita e exclui o próprio comentário; o antes fica no histórico (G3). Gestor e Administrador não mexem no comentário dos outros.
11. **Anexo: tamanho e tipos.** Nenhuma regra limita. Proposta: o limite do template `ai-systems`, e qualquer alocado remove anexo.
12. **Card arquivado.** Proposta: a ficha continua abrindo pelo filtro *Arquivados* e pela busca da chave, e nada nela fica bloqueado; ele só não aparece no quadro nem nas contas (P26).
13. **Quadro de projeto encerrado.** P7 só proíbe card novo. Proposta: mover, comentar e anexar seguem funcionando, para fechar o que ficou aberto.
14. **Tipo do bug de produção.** Seria natural o card com tarefa de origem já nascer como Correção de bug, mas esse item da lista não tem marca que o código reconheça. Proposta: na fatia 1 a pessoa escolhe o tipo à mão; pré-preencher pede um ajuste pequeno na semente da lista.
15. **IA pondo card na sprint sem sprint aberta.** Na tela, o diálogo propõe criar a sprint (P45). Proposta: pela IA, o Service cria a sprint com os mesmos valores propostos e segue, como a tela faria com *Confirmar* (detalhe em F5 e F8).
