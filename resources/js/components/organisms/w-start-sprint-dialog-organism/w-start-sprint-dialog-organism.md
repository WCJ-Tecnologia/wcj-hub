# WStartSprintDialogOrganism

Organismo · `organisms/w-start-sprint-dialog-organism/w-start-sprint-dialog-organism.tsx` · story `Organisms/Start Sprint Dialog`

## O que faz

O passo de planejamento do _Montar sprint_ do quadro (D-GER-35; F5 §01 "Horas na montagem" e "Capacidade"; P27, P48, P49), aberto pela barra depois de marcar os cards no Backlog. Montar a sprint é planejar: o gestor decide ali quem faz cada card, as horas e a prioridade, olhando a capacidade de cada pessoa, e a sprint já começa arrumada.

- **Em cima, a sprint.** Sem sprint aberta (_Iniciar sprint_): os campos da `WSprintFieldsMolecule` com a `proposedSprint()` ("Sprint N", hoje, fim pela duração padrão em dias úteis). Com sprint aberta (_Pôr na Sprint N_): o nome, o período e os dias úteis dela. O período manda nos dias úteis e, com eles, na capacidade.
- **Cards do plano**, uma linha por card marcado (`WDataTableOrganism`, card empilhado no celular), começando pelo que o card já tem (`planItemOf`):
    - chave e título, só leitura;
    - **Dev** e **QA**: as pessoas ativas alocadas no projeto (o gestor alocado também, P5), com a carga diária ao lado do nome ("Ana Souza · 8 h/dia"), ou _Sem dev_ / _Sem QA_;
    - **Horas**: previstas, total dev + QA (P27), com decimal ("8,5"); vazio = sem horas;
    - **Prioridade**: escolhe uma das quatro (P19). O plano não tira a prioridade: card sem nenhuma mostra _Sem prioridade_ até alguém escolher;
    - **Tirar do plano** (X): o card sai do plano e da seleção do quadro e fica no Backlog (`onRemove`).
- **Rodapé** em `role="status"`: "3 cards · 52 h previstas · 1 sem dev · 1 sem horas" (`planSummaryText`).
- **Capacidade, ao vivo**: ao lado em tela larga (`xl`), em cima nas menores. É o `WCapacityPanelOrganism` em `variant="plan"`, alimentado pela `planCapacity()` de `lib/quadro.ts`. Por pessoa alocada mostra a carga diária, a capacidade (carga × dias úteis), as atribuídas (as horas do plano mais o que já falta dela na sprint aberta) e a disponível. Disponível negativo fica em destaque, só como aviso (P49). Troca de dev, de horas ou de data refaz a conta na hora.

Nada é obrigatório: card sem dev ou sem horas entra do mesmo jeito (P27, P49). Confirmar devolve o `SprintBatchPayload` com o plano de cada card, e a página manda para `POST /projetos/{id}/sprint-aberta/tarefas/lote`. O servidor grava tudo numa transação: cria a sprint, põe cada card no To do e aplica dev, QA, horas e prioridade, cada mudança auditada.

Erros do servidor (`errors`):

- `tasks` em cima, em `role="alert"`: o lote recusado, com as chaves;
- `tasks.{i}.developer_id`, `qa_id`, `estimated_hours` e `priority` na linha do card, na ordem de `cards`;
- `new_sprint.*` e `name` (nome repetido) nos campos da sprint.

Horas inválidas (zero, negativa, texto) são barradas na tela antes de mandar.

## Props

| Prop                   | Tipo                                    | Obrigatória | Para quê                                                   |
| ---------------------- | --------------------------------------- | ----------- | ---------------------------------------------------------- |
| `open`, `onOpenChange` |                                         | sim         | controle do diálogo (não fecha enquanto `loading`)         |
| `project`              | `BoardProject`                          | sim         | `open_sprint`, `sprints_count` e `sprint_days`             |
| `cards`                | `BoardCard[]`                           | sim         | os marcados, na ordem do payload                           |
| `planning`             | `SprintPlanning`                        | sim         | pessoas alocadas e a sprint aberta (`SprintPlanningQuery`) |
| `today`                | `string`                                | sim         | `AAAA-MM-DD` em São Paulo: o início proposto               |
| `onRemove`             | `(card: BoardCard) => void`             | sim         | tira o card do plano e da seleção                          |
| `loading`              | `boolean`                               | não         | spinner e trava enquanto grava                             |
| `errors`               | `Record<string, string>`                | não         | erros do servidor                                          |
| `onConfirm`            | `(payload: SprintBatchPayload) => void` | sim         | a intenção de gravar                                       |

## Cuidados

- A conta da capacidade é a mesma da `CapacityQuery` do servidor; se uma mudar, a outra muda junto. Há duas diferenças de propósito: entra toda pessoa alocada, mesmo sem card, e as horas do plano contam inteiras (o card ainda não tem lançamento).
- O diálogo não decide regra: a recusa (pessoa fora do projeto, card que saiu do Backlog, permissão) é do servidor.
- Desmonta ao fechar: a próxima abertura volta à proposta e ao que os cards têm.
