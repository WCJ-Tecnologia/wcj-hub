# WTaskDetailHeaderOrganism

Organismo · `organisms/w-task-detail-header-organism/w-task-detail-header-organism.tsx` · story `Organisms/Task Detail Header`

## O que faz

O topo da ficha do card (F3 §02): o que fica sempre visível, acima das seções recolhidas.

| Zona      | O que mostra                                                                                                                                                           | Regra                   |
| --------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------- |
| Linha 1   | Chave (`WTaskKeyAtom`), Tipo em Space Mono, Área como etiqueta, "↳ SGM-10" com link quando é card filho                                                                | P16, P18, P57           |
| Título    | h2 com o lápis _Editar título_ → campo com Salvar/Cancelar (Enter salva, Esc cancela); vazio é recusado na tela                                                        | P17                     |
| Marcas    | `WTaskMarksMolecule`: atraso, pausa com motivo, voltas, suporte, reestimativa proposta                                                                                 | P15, P27, P39…          |
| Capa      | A imagem do anexo escolhido, quando há                                                                                                                                 | P59                     |
| Fatos     | `dl` só leitura: Etapa, Sprint ("Fora de sprint"), Pacote, Data prevista (vermelha se atrasado), Teste cruzado, Situação ("Ativo" / "Arquivado em DD/MM/AAAA: motivo") | P28, P34, P45, P41, P26 |
| À direita | _Histórico_ (link para `history_url`; sem ela, desabilitado com a dica "Em breve (B7)") e o menu ⋯                                                                     | F7                      |

**Menu ⋯** (`aria-label="Ações de SGM-17"`): _Copiar link_ (URL absoluta + toast); _Mover para…_ (as etapas menos a atual; `onMove(etapa)`, só com `abilities.move`); _Arquivar_ (só com `abilities.archive`, card ativo) ou _Desarquivar_ (card arquivado); _Excluir_ (só com `abilities.delete`, que o servidor manda `true` só no card nunca usado, P26). A confirmação de cada uma é da página.

## Props

| Prop            | Tipo                       | Para quê                                       |
| --------------- | -------------------------- | ---------------------------------------------- |
| `detail`        | `TaskDetail`               | A ficha                                        |
| `stages`        | `BoardStage[]`             | Etapas ativas, para o _Mover para…_            |
| `busy`          | `boolean`                  | Gravando: spinner no Salvar, menu desabilitado |
| `errors`        | `Record<string, string>`   | `title` do servidor                            |
| `onRenameTitle` | `(title: string) => void`  | Título novo, já sem espaços nas pontas         |
| `onMove`        | `(to: BoardStage) => void` | A página passa pelo `useBoardMove`             |
| `onArchive`     | `() => void`               | Abre o diálogo de arquivar (página)            |
| `onUnarchive`   | `() => void`               | Desarquivar                                    |
| `onDelete`      | `() => void`               | Abre a confirmação de excluir (página)         |

A raiz leva `data-slot="w-task-detail-header-organism"`.

## Cuidados

- A página põe a chave no h1 do `WAppLayoutTemplate` e o título aqui, no h2: por isso o título é editável neste bloco, não no cabeçalho da página.
- Os fatos são só leitura por regra (F3 §02): sprint e pacote mudam em F5 e F6; teste cruzado, em F4; a data prevista é conta do servidor.
