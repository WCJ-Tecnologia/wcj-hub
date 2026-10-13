# F6. Pacote (versão)

**Documento funcional.** Versão 1.0, de 06/10/2026. **Aprovado por Lucas Scholz em 06/10/2026**, com os pontos a validar respondidos (seção 10). Rafael Garcia valida o uso no bugbash. Estrutura e fluxo em [`README.md`](README.md).

| | |
|---|---|
| **Objetivo** | Trocar as listas "Produção 1.0.9 10/07/2026" do Trello por um pacote de verdade: o gestor junta as tarefas que sobem juntas, acompanha a homologação, marca a entrada em produção, e a reprovação na homologação vira retrabalho contado. |
| **Regras que alimentam** | P41, P42, P43, P44, P39 (origem do retrabalho) e a premissa de Projetos (`docs/necessidade.md`). |
| **Tabelas do `banco.md`** | `packages`, `tasks` (pacote), `task_stage_movements` (o movimento de retrabalho); lê `rework_reasons` e `validation_types` (a origem Homologação). |
| **Depende de** | [F3](f3-quadro-e-card.md): quadro, card e volta para desenvolvimento. [F2](f2-projeto-e-configuracao.md): etapas e motivos de retrabalho. |
| **Quem aprova** | Lucas Scholz (D-GER-31). Rafael Garcia, gestor de projetos (D-GER-02), participou com feedback e elaboração e valida o uso no bugbash. |

## 01. Fluxo funcional

O pacote é **opcional** (P41). Projeto que sobe quando convém, sem versão, não precisa usar.

1. **Criar.** No projeto, aba *Pacotes*, o gestor clica em *Novo pacote*, informa o nome da versão ("1.0.9") e, se já souber, a data planejada de liberação, que em geral vem do cliente (P44). O pacote nasce **em montagem** (P42).
2. **Montar.** O gestor põe as tarefas pelo próprio pacote (*Adicionar tarefas*) ou pelo card, no campo *Pacote*. Vale tarefa de qualquer etapa, a qualquer momento: o normal é juntar tarefas prontas, mas o sistema não confere (P41, premissa). Tirar é igual.
3. **Homologar.** Quando a equipe começa a testar tudo junto, o gestor marca *Em homologação* (P42). A homologação é da equipe WCJ; o cliente não participa (P43). Como o QA testa cada tarefa no próprio ambiente (P36), este é o primeiro teste das tarefas juntas.
4. **Reprovar, se falhar.** O gestor clica em *Reprovar na homologação*, marca as tarefas afetadas e escolhe o motivo. Elas voltam para desenvolvimento como retrabalho de origem Homologação, e o dev de cada uma é avisado (P39, P43, P22). As tarefas **continuam no pacote**: se o gestor quiser que alguma fique para a próxima versão, ele a tira (P43).
5. **Subir.** Quando a versão entra em produção, o gestor marca *Em produção*. Essa é a data da versão (P44).
6. **Corrigir.** Voltar o estado é limpar a data marcada (P42). O antes fica no histórico (G3).

Falha encontrada depois que o pacote está em produção não é reprovação: vira tarefa nova, ligada à original (P25, F3).

## 02. Telas e campos

### Pacote (criar e editar)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Projeto | fixo: o projeto de onde se abriu | sim | | Todo pacote é de um projeto (P41). |
| Nome | texto, até 40, único no projeto | sim | | A versão, ex.: "1.0.9". Proposta: texto livre, sem formato obrigatório (ver 10.1). Repetido no mesmo projeto é recusado. |
| Data planejada de liberação | data | não | | Definida por projeto, em geral pelo cliente (P44). Só informação: nada recusa nem calcula atraso com ela. |
| Início da homologação | data e hora, preenchida só pela ação *Em homologação* | | vazio | Preenchida = em homologação (P42). |
| Entrada em produção | data e hora, preenchida só pela ação *Em produção* | | vazio | Preenchida = em produção. É a data da versão (P44). |
| Estado | só leitura | | Em montagem | Deduzido das duas datas, como na tabela abaixo (P42). |

| Início da homologação | Entrada em produção | Estado |
|---|---|---|
| vazia | vazia | Em montagem |
| preenchida | vazia | Em homologação |
| preenchida ou vazia | preenchida | Em produção |

Não existem estados cadastráveis nem configuração de estados por projeto (P42).

### Marcar data (diálogo de *Em homologação* e de *Em produção*)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Data e hora | data e hora | sim | agora | Proposta: o gestor pode informar o momento real, se marcar depois (ver 10.2). |

### Adicionar tarefas (diálogo, dentro do pacote)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Tarefas | lista das tarefas do projeto, de qualquer etapa, com busca por chave ou título; escolha de uma ou várias | sim | | Cada linha mostra chave, título, etapa atual e o pacote atual, se houver (P41). Tarefa arquivada não aparece (P26). Uma tarefa fica em um pacote só: a que estava em outro passa para este. |

### Pacote na ficha do card

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Pacote | lista dos pacotes do projeto, com o estado ao lado; os em produção por último | não | nenhum | O gestor e o Administrador escolhem, trocam e limpam; o dev vê, sem editar (P41, premissa). |

### Reprovar na homologação (diálogo)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Tarefas | caixas de marcar com as tarefas do pacote | sim, ao menos uma | nenhuma marcada | Só as afetadas (P43). |
| Volta para | lista das etapas do projeto de papel Sprint ou Desenvolvimento | sim | a etapa de papel Sprint (`To do`): o dev leva para `Doing` quando estiver fazendo (decisão de 06/10) | É a volta para desenvolvimento da P39. |
| Origem | fixa | | Homologação | (P39, P43) |
| Motivo | lista geral de motivos de retrabalho ativos ([F2](f2-projeto-e-configuracao.md)) | sim | | Proposta: um motivo para todas as tarefas marcadas (ver 10.4) (P40). |
| Texto | texto, até 500 | sim quando o motivo pede texto, como o "Outro" | | (P40) |

## 03. Listagem

| Tela | Colunas | Ordem padrão | Observação |
|---|---|---|---|
| Pacotes do projeto | Nome, Estado, Data planejada, Em homologação desde, Em produção em, Tarefas | Em montagem e em homologação primeiro; depois os em produção, do mais recente para o mais antigo | Tarefas = quantas estão no pacote. Clicar abre a ficha do pacote. |
| Tarefas do pacote (na ficha do pacote) | Chave, Título, Tipo, Etapa atual, Dev | Chave | Tarefa arquivada que está no pacote aparece em cinza, marcada "arquivada" (P26). Clicar abre o card (F3). |

Estado vazio: Pacotes, "Nenhum pacote neste projeto. *Novo pacote*" (para o dev, sem botão); Tarefas do pacote, "Nenhuma tarefa neste pacote. *Adicionar tarefas*" (para o dev, sem botão).

## 04. Ações

| Ação | Quem | O que faz | Confirmação / aviso |
|---|---|---|---|
| Novo pacote | Administrador; gestor do projeto | Cria o pacote em montagem (P41, P42). | Nome repetido: recusa com "Já existe o pacote 1.0.9 neste projeto". |
| Editar | Administrador; gestor do projeto | Nome e data planejada (P44). | Idem. |
| Adicionar tarefas | Administrador; gestor do projeto | Liga as tarefas escolhidas ao pacote, de qualquer etapa e em qualquer estado do pacote (P41). A que estava em outro pacote sai de lá; a troca fica no histórico (G3). | Dentro do diálogo, sem recusar: "SGM-17 está no pacote 1.0.8 e passa para este." |
| Pacote, no card | Administrador; gestor do projeto | A mesma ação, tarefa por tarefa, pela ficha do card (P41). | Idem. |
| Tirar do pacote | Administrador; gestor do projeto | A tarefa fica sem pacote. Não muda etapa, sprint, horas nem retrabalho (P43). | |
| Em homologação | Administrador; gestor do projeto | Grava o início da homologação; o pacote passa a em homologação (P42). | Diálogo da data (seção 02). |
| Em produção | Administrador; gestor do projeto | Grava a entrada em produção, que é a data da versão (P44). Sem trava: vale sem ter passado por homologação e com tarefas fora de Pronto (P41, premissa, ver 10.3). | Diálogo da data, com "Marcar o pacote 1.0.9 como em produção em DD/MM/AAAA?" |
| Voltar para homologação | Administrador; gestor do projeto; aparece em produção | Limpa a entrada em produção (P42). | "A data de produção é apagada; o histórico guarda. Continuar?" |
| Voltar para montagem | Administrador; gestor do projeto; aparece em homologação | Limpa o início da homologação (P42). Do estado em produção, volta-se primeiro para homologação. | "A data de homologação é apagada; o histórico guarda. Continuar?" |
| Reprovar na homologação | Administrador; gestor do projeto; aparece em homologação (ver 10.6) | Para cada tarefa marcada, grava a volta para a etapa escolhida como retrabalho de origem Homologação, com o motivo, em nome de quem reprovou (P14, P39, P43). Como em toda volta para desenvolvimento: avisa o dev da tarefa no sistema e por e-mail (P22), limpa a marca de teste cruzado (P34) e o roteiro volta para "a testar" (P37); detalhes no [F3](f3-quadro-e-card.md) e no [F4](f4-teste-e-qualidade.md). As tarefas continuam no pacote, e o pacote continua em homologação (P43). | "N tarefas voltam para To do como retrabalho de homologação. Continuar?" |
| Excluir | Administrador; gestor do projeto | Só pacote sem tarefa e ainda em montagem (G2, ver 10.8). | "Excluir de verdade. Não dá para desfazer." |
| Histórico | Administrador; gestor do projeto | Auditoria filtrada no pacote: quem criou, pôs e tirou tarefas, marcou e limpou cada data (G3, F1). | |

**Volta de uma tarefa pelo card.** A reprovação também pode ser registrada tarefa por tarefa: qualquer pessoa alocada arrasta o card de volta para desenvolvimento e escolhe a origem Homologação e o motivo (P13, P39, F3). O efeito é o mesmo, e a tarefa continua no pacote.

**Pela IA.** Nenhuma das 6 ferramentas da fatia 1 cria pacote, põe ou tira tarefa nem muda o estado (IA14). A volta de uma tarefa com origem Homologação sai pela `mover_tarefa`, que pede origem e motivo quando a volta é retrabalho (P39). A `notas_da_versao` (lista das tarefas do pacote para redigir as notas) fica para depois da fatia 1 (IA14, P55).

## 05. Filtros e busca

| Tela | Busca | Filtros |
|---|---|---|
| Pacotes do projeto | nome | Estado (todos por padrão) |
| Tarefas do pacote | chave, título | Etapa atual; Tipo |

## 06. Indicadores

- **Tarefas do pacote:** quantas tarefas estão no pacote, na lista e na ficha.
- **Prontas:** na ficha, quantas dessas tarefas estão em etapa de papel Pronto (ex.: "8 de 10 prontas"). É referência para o gestor e nunca impede marcar estado (P41, premissa).
- **Planejado × real:** a data planejada e a entrada em produção aparecem lado a lado; a v1 não calcula atraso de versão (P44).
- O retrabalho de origem Homologação fica gravado desde a fatia 1 (P39); o indicador de retrabalho é depois da v1 (P54, [F7](f7-indicadores.md)).

## 07. Avisos e documentos gerados

- **Reprovação:** o dev de cada tarefa devolvida recebe o aviso no sistema e o e-mail da volta para desenvolvimento, os mesmos do F3 (P22). Tarefa sem dev não avisa ninguém.
- Criar pacote, pôr ou tirar tarefa e marcar ou limpar data não geram aviso: os da P22 são os únicos de Projetos.
- **Recusa:** nome de pacote repetido no projeto.
- **Notas de versão:** não são geradas na fatia 1 (P55). A lista de tarefas do pacote, na tela, é a base para quem redige à mão.

## 08. Exportação

Não se aplica na fatia 1: nenhuma regra pede exportação do pacote; notas de versão e histórico em PDF ficam para depois da v1 (P55).

## 09. Permissionamento

| O quê | Administrador | Gestor de projetos | Desenvolvedor |
|---|---|---|---|
| Ver a lista de pacotes e a ficha | todos os projetos | nos que gere | nos em que está alocado (P5) |
| Ver o pacote no card | sim | sim | sim |
| Criar, editar e excluir pacote | sim | nos que gere (P41) | não (premissa) |
| Pôr e tirar tarefa (pelo pacote ou pelo card) | sim | nos que gere (P41) | não (premissa) |
| Marcar e limpar as datas | sim | nos que gere (P41, P42) | não |
| Reprovar na homologação pelo pacote | sim | nos que gere (P43) | não |
| Devolver um card com origem Homologação, pelo card ou pela IA | sim | sim | sim, como qualquer volta para desenvolvimento (P13, P39) |
| Histórico do pacote | sim | nos que gere | não |

A IA de cada pessoa tem exatamente estas permissões (G4). O Comercial não entra em Projetos (C14).

## 10. Pontos a validar

Respondidos por Lucas Scholz em 06/10/2026; as propostas valem como escritas nas seções acima.

1. **Formato do nome.** OK: texto livre até 40 caracteres, único no projeto.
2. **Data das marcações.** OK: vem com agora, aceita outra data e hora.
3. **Produção sem homologação.** OK: permitido; a data de homologação fica vazia.
4. **Um motivo para a reprovação inteira.** OK.
5. **Para onde a reprovação devolve.** `To do` por padrão (F4, item 8).
6. **Reprovar só em homologação.** OK: em produção, a falha vira bug de produção (P25).
7. **Tarefa em pacote já em produção.** OK: permitido, sem aviso.
8. **Excluir pacote.** OK: só sem tarefa e em montagem.
9. **Tarefa arquivada no pacote.** OK: continua no pacote, em cinza.
10. **Filtro por pacote no quadro.** OK: entrou nos filtros do quadro, no F3 (seção 05).
