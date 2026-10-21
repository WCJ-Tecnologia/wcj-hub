# WTaskTimelineOrganism

Organismo · `organisms/w-task-timeline-organism/w-task-timeline-organism.tsx` · story `Organisms/Indicadores/Linha do tempo`

## O que faz

A linha do tempo do card (F7 §02, §03; P14, P15, P39, R14): um movimento por linha, do mais antigo para o mais recente, com data e hora (Brasília), quem moveu ("(desativado)" se a pessoa saiu), o canal (Tela, ou "IA: Claude"), de onde para onde (a primeira linha é "Criou o card em Backlog"; etapa removida com "(removida)"), quanto tempo o card ficou na etapa de destino (na última, "até agora"), a marca _Retrabalho_ com origem e motivo (e o texto do "Outro"), e o motivo da pausa ("Sem motivo" quando o projeto desligou). Só com a criação: "Nenhum movimento além da criação".

## Props

| Prop        | Tipo                | Obrigatória | Para quê                                        |
| ----------- | ------------------- | ----------- | ----------------------------------------------- |
| `movements` | `HistoryMovement[]` | sim         | os movimentos, como o `TaskHistoryScreen` manda |
