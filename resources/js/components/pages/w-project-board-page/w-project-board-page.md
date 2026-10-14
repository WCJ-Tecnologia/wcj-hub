# WProjectBoardPage

Tela · `pages/w-project-board-page/w-project-board-page.tsx` · Inertia `projects/board` (`resources/js/pages/projects/board.tsx`) · story `Pages/Project Board`

## O que faz

O quadro do projeto em `/projetos/{id}/quadro` (F3 §01, §03 a §05), como o controller do B3 vai montar (**proposta do kit; o Cauã ajusta**):

- `WAppLayoutTemplate` largo, eyebrow "Projetos", título = nome do projeto, subtítulo "SGM · Sprint 3 aberta" ou "SGM · sem sprint aberta"; à direita, _Sprints_ e _Configuração_ desabilitados com a dica "Em breve (B5)" / "Em breve (B2)";
- `WBoardFiltersOrganism`, `WKanbanBoardOrganism` e, com _Arquivados_ ligado, o vazio "Nenhum card arquivado" no lugar do quadro;
- os diálogos do movimento pelo `useBoardMove` (`move.dialogs`) e o `WSupportCardDialogOrganism`;
- recusa do movimento (`onDenied`) em `toast.error(motivo)`.

**Rotas** (comentário no topo do arquivo; o B3 confirma):

| Ação             | Chamada                                                                     |
| ---------------- | --------------------------------------------------------------------------- |
| Filtros          | `GET /projetos/{id}/quadro?search=&mine=1&…` (`only: ['cards', 'filters']`) |
| Criar rápido     | `POST /projetos/{id}/cards { title }`                                       |
| Card de suporte  | `POST /projetos/{id}/cards/suporte` (`SupportCardPayload`)                  |
| Mover            | `POST /projetos/{id}/cards/{chave}/mover` (`MovePayload`)                   |
| Prioridade, Área | `PATCH /projetos/{id}/cards/{chave} { priority }` / `{ area_id }`           |
| Participante     | `POST /projetos/{id}/cards/{chave}/participantes { user_id, add }`          |

**Mover é otimista.** No `onCommit` do `useBoardMove`, o card muda de coluna (e de posição, na ordem manual) no estado local antes do `router.post`; no `onError` volta, e o erro aparece no diálogo aberto (`errors` do hook) ou, sem diálogo, em toast. Com diálogo, ele fica aberto com spinner (`processing`) até o `onSuccess` chamar `move.cancel()`. Prioridade, Área e participante também são otimistas, com o mesmo desfazer.

**Ordem** (_Prioridade | Manual_) é de cada pessoa: `localStorage` com a chave `hub.quadro.ordem`, em `try/catch`.

**Filtros**: o estado local responde na hora (`applyFilters`), e a visita ao servidor vai junto (a busca espera 300 ms). Quando o servidor manda `cards` ou `filters` novos, a cópia local é trocada.

## Props

| Prop        | Tipo             | Para quê                                                    |
| ----------- | ---------------- | ----------------------------------------------------------- |
| `project`   | `BoardProject`   | Nome, prefixo, encerrado, sprint aberta, regras da pausa    |
| `stages`    | `BoardStage[]`   | Etapas ativas, na ordem de F2                               |
| `cards`     | `BoardCard[]`    | Os cards, já filtrados pelo servidor                        |
| `abilities` | `BoardAbilities` | Decididas pelas Policies; a tela só esconde                 |
| `options`   | `BoardOptions`   | Áreas, tipos, alocados, motivos e origens, contatos, canais |
| `filters`   | `BoardFilters`   | Os filtros da URL                                           |
| `today`     | `string`         | `AAAA-MM-DD` em São Paulo (início proposto da sprint)       |

## Cuidados

- O "agora" do card de suporte é calculado no clique de _Novo card de suporte_, em America/Sao_Paulo (não no desenho, para a tela ser pura).
- A lista de arquivados (Chave, Título, Tipo, Arquivado em, Motivo; F3 §03) e _Mostrar prontos antigos_ dependem do servidor: o kit só mostra o vazio e manda o filtro.
- No Storybook, o `router` de escrita do mock simula sucesso (`onStart`, depois `onSuccess` e `onFinish` em 400 ms), então os diálogos mostram o spinner e fecham.
