# WSituationTabOrganism

Organismo · `organisms/w-situation-tab-organism/w-situation-tab-organism.tsx` · story `Organisms/Indicadores/Situação`

## O que faz

Aba _Situação_ dos indicadores do projeto (F7 §03, §06; P15, P28, P53): o projeto inteiro, fora do arquivo, sem filtro, o mesmo que `status_do_projeto` devolve pela IA (mais as pausas). Quatro blocos e um painel: _Tarefas por etapa_ (contagem por etapa ativa, na ordem do quadro); _Atrasadas_ (maior atraso primeiro, com o tempo em pausa descontado, R16); _Próximas datas previstas_ (abertas com data ainda não vencida); _Em pausa_ (motivo, desde quando, quem pausou); _Sem data prevista_ (só as que já entraram em desenvolvimento, com o que falta). Cada bloco vazio diz a frase do F7 §03. A chave abre o card.

## Props

| Prop   | Tipo           | Obrigatória | Para quê                                       |
| ------ | -------------- | ----------- | ---------------------------------------------- |
| `data` | `SituationTab` | sim         | o conteúdo, como o `SituationIndicators` manda |

## Cuidados

- Só para quem gere o projeto e o Administrador (F7 §09): o dev nem recebe a aba.
- A conta do atraso é do servidor (`DelayQuery`); aqui só se escreve.
