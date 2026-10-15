# WTimeEntryFormDialogOrganism

Organismo · `organisms/w-time-entry-form-dialog-organism/w-time-entry-form-dialog-organism.tsx` · story `Organisms/Time Entry Form Dialog`

## O que faz

_Lançar horas_ e _Corrigir lançamento_ do F5 §02 e §04 (P30). No lançamento novo: Tarefa (busca por chave ou título pelo `WTaskPickerMolecule`, com as tarefas em que sou dev ou QA primeiro), Data (padrão hoje) e Horas (decimais, "1,5" ou "1.5"). Na correção, a tarefa fica fixa e só data e horas mudam. A pessoa é sempre quem está logado (o gestor não lança pelo dev, F5 §10.14); não há descrição.

Recusas na tela e no servidor: "Informe horas maiores que 0 e até 24" e "A data não pode ser futura".

Grava sozinho: `POST /projetos/{projectId}/tarefas/{taskId}/horas` (o `project_id` vem da opção da tarefa) ou `PUT /horas/{id}`, e fecha no sucesso.

## Props

| Prop                   | Tipo                    | Obrigatória | Para quê                                 |
| ---------------------- | ----------------------- | ----------- | ---------------------------------------- |
| `open`, `onOpenChange` |                         | sim         | controle do diálogo                      |
| `entry`                | `TimeEntryRow \| null`  | sim         | o lançamento na correção; null no novo   |
| `tasks`                | `TimeEntryTaskOption[]` | sim         | as tarefas que posso escolher            |
| `today`                | `string` (`AAAA-MM-DD`) | sim         | hoje no fuso de São Paulo, a data padrão |

## Cuidados

- A ficha do card (B3) lança na mesma rota, com a tarefa fixa.
- Não confere a soma do dia (F5 §10.12).
