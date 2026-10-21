# WMyTasksTabOrganism

Organismo · `organisms/w-my-tasks-tab-organism/w-my-tasks-tab-organism.tsx` · story `Organisms/Indicadores/Minhas tarefas e horas`

## O que faz

Aba _Minhas tarefas e horas_ (F7 §03, §05, §06; P53, P54): as tarefas abertas em que a pessoa é dev ou QA, atrasadas primeiro e depois pela data prevista: Chave, Título, Meu papel (Dev, QA, Dev e QA), Etapa, Data prevista (ou o que falta), Atraso ("No prazo", ou os dias úteis em destaque), Previstas, Lançadas por mim e Tempo em desenvolvimento (só onde sou dev). É a única aba do dev nos indicadores. Vazia: "Nenhuma tarefa sua neste projeto".

## Props

| Prop   | Tipo         | Obrigatória | Para quê                                   |
| ------ | ------------ | ----------- | ------------------------------------------ |
| `data` | `MyTasksTab` | sim         | as linhas, como o `EffortIndicators` manda |
