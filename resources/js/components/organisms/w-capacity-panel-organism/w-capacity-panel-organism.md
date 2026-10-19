# WCapacityPanelOrganism

Organismo · `organisms/w-capacity-panel-organism/w-capacity-panel-organism.tsx` · story `Organisms/Capacity Panel`

## O que faz

Painel _Capacidade_ da tela de sprints (F5 §03; P48, P49, N3). Uma linha por dev com tarefa na sprint, na ordem que o servidor manda (maior "faltam" primeiro): Dev, Carga diária, Capacidade (carga × dias úteis da sprint inteira), Faltam (previstas − lançadas por tarefa, nunca abaixo de 0; tarefa em `Done` conta 0) e Diferença (capacidade − faltam). Diferença negativa em vermelho, com ícone e texto para leitor de tela: só aviso, nada trava (P49). Dev sem carga diária: "sem carga diária: capacidade não calculada". Abaixo, à parte: _Sem dev_ (tarefas e horas previstas delas) e _Sem horas previstas_ (tarefas). Vazio: "Nenhum dev com tarefa nesta sprint."

A conta é toda do `CapacityQuery`; o painel só mostra. O QA não entra (P49).

**`variant="plan"`** (D-GER-35): o painel do planejamento do _Montar sprint_ do quadro (`WStartSprintDialogOrganism`). Vem em lista compacta, para caber ao lado do plano, com _Atribuídas_ e _Disponível_ no lugar de _Faltam_ e _Diferença_ (os mesmos campos do `CapacityPanel`), e com toda pessoa alocada, mesmo sem card (a conta é a `planCapacity()` de `lib/quadro.ts`). Disponível negativo em destaque, só aviso.

## Props

| Prop       | Tipo                 | Obrigatória    | Para quê                                           |
| ---------- | -------------------- | -------------- | -------------------------------------------------- |
| `capacity` | `CapacityPanel`      | sim            | o painel, como o servidor manda                    |
| `variant`  | `'sprint' \| 'plan'` | não (`sprint`) | tabela da tela de sprints ou lista do planejamento |

## Cuidados

- Só para quem gere o projeto (F5 §09): para o dev a tela recebe `capacity = null` e não renderiza o painel.
