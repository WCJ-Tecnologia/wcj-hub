# WProjectMembersTabOrganism

Organismo · `organisms/w-project-members-tab-organism/w-project-members-tab-organism.tsx` · aparece em `Pages/Configuração do projeto`

## O que faz

A aba Pessoas da configuração do projeto (F2 §02 a §04; P5): tabela Nome, Papel, Carga diária, Situação (usuário desativado continua, em cinza, N11) e a ação **Tirar do projeto**, com a confirmação que diz quantas tarefas ficam sem dev e sem QA. O botão **Alocar pessoas** abre o `WAllocatePeopleDialogOrganism`. Escreve em `POST /projetos/{id}/pessoas` (`user_ids[]`) e `DELETE /projetos/{id}/pessoas/{user}`.

## Props

| Prop        | Tipo             | Para quê                                  |
| ----------- | ---------------- | ----------------------------------------- |
| `projectId` | `number`         | as rotas                                  |
| `members`   | `MemberRow[]`    | as linhas do Presenter                    |
| `people`    | `PersonOption[]` | `options.people`; `[]` para quem não gere |
| `canManage` | `boolean`        | `project.can.manage`: mostra os botões    |

## Cuidados

A trava de clique duplo das ações de linha é o `useRowAction`; a alocação usa um `useForm` próprio com `transform`, porque a lista escolhida vive no diálogo.
