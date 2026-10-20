# WPeopleTabOrganism

Organismo · `organisms/w-people-tab-organism/w-people-tab-organism.tsx` · story `Organisms/Indicadores/Por pessoa`

## O que faz

Aba _Por pessoa_ (F7 §03, §06, §10.4; P53): Pessoa, Carga diária, Tarefas como dev, Previstas (das tarefas em que é dev), Lançadas por ela (em qualquer tarefa do conjunto, inclusive como QA), Tempo em desenvolvimento, Atrasadas e Em pausa; por nome, com a linha _Sem dev_ por último. Atrasadas e Em pausa maiores que zero em destaque. Vazia: "Nenhuma tarefa com dev com esses filtros".

## Props

| Prop   | Tipo        | Obrigatória | Para quê                                   |
| ------ | ----------- | ----------- | ------------------------------------------ |
| `data` | `PeopleTab` | sim         | as linhas, como o `EffortIndicators` manda |
