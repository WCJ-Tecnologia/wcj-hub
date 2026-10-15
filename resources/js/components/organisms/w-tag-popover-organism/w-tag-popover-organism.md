# WTagPopoverOrganism

Organismo · `organisms/w-tag-popover-organism/w-tag-popover-organism.tsx` · story `Organisms/Tag Popover`

## O que faz

As etiquetas do card (**tipo** e **área**) ao estilo Trello (decisões de Lucas Scholz em 14/10): no card e na ficha, clicar na etiqueta abre um popover com a lista do projeto.

- **Escolher:** clicar numa opção troca a etiqueta do card (`onChange`); na área, "Nenhuma" limpa; o tipo não tem "Nenhum" (define-se uma vez). A atual tem o ✓. O card sem tipo ou sem área (o card rápido nasce assim, P17, 14/10) mostra o chip tracejado **"+ Tipo"** / **"+ Área"**.
- **Editar:** com `onUpdate` (gestor do projeto e Administrador; `abilities.manage_types` / `manage_areas`), cada linha tem o lápis: nome e cor no mesmo popover, com prévia.
- **Criar:** com `onCreate`, _Novo tipo_ / _Nova área_ no fim abre o mesmo formulário.
- **Do projeto, sem propagar:** tipos e áreas são do projeto. Todo projeto nasce com os tipos padrão (Melhoria, Correção de bug, Suporte, Mudança (GMUD), Desenvolvimento) e sem área; criar ou editar aqui muda só este projeto.
- `modal={false}`, como o menu do card, para não travar o dnd-kit nem esconder o quadro do leitor de tela. Salvar ou escolher fecha o popover.

## Props

| Prop       | Tipo                                         | Padrão    | Para quê                                          |
| ---------- | -------------------------------------------- | --------- | ------------------------------------------------- |
| `kind`     | `'type' \| 'area'`                           |           | Textos e regras (área aceita "Nenhuma")           |
| `options`  | `TagOption[]` (`{ id, name, color }`)        |           | `options.types` (com `label` → `name`) ou `areas` |
| `value`    | `TagOption \| null`                          |           | A etiqueta do card                                |
| `onChange` | `(option: TagOption \| null) => void`        |           | Trocar ou limpar; quem grava é a página           |
| `onCreate` | `(payload: { name, color }) => void`         |           | _Novo…_ (só com permissão)                        |
| `onUpdate` | `(option, payload: { name, color }) => void` |           | Lápis de editar (só com permissão)                |
| `busy`     | `boolean`                                    | `false`   | Gravando                                          |
| `errors`   | `Record<string, string>`                     |           | `name`, `color` do servidor                       |
| `size`     | `'sm' \| 'default'`                          | `default` | `sm` no card do quadro                            |

O gatilho leva `data-slot="w-tag-popover-organism"`, `data-kind` e `data-color`.

## Exemplo

```tsx
<WTagPopoverOrganism
    kind="type"
    options={types.map((type) => ({ id: type.id, name: type.label, color: type.color }))}
    value={{ id: card.type.id, name: card.type.label, color: card.type.color }}
    size="sm"
    onChange={(option) => option && onChangeType(card, typeById(option.id))}
    onCreate={onCreateType}
    onUpdate={onUpdateType}
/>
```

## Cuidados

- Rotas (proposta do kit, no topo de `w-project-board-page.tsx`): `POST /projetos/{id}/tipos` e `PATCH /projetos/{id}/tipos/{tid}`, `POST /projetos/{id}/areas` e `PATCH /projetos/{id}/areas/{aid}`, todas `{ name, color }`; a etiqueta do card segue no `PATCH …/cards/{chave} { type_id }` / `{ area_id }`. Nome repetido no projeto é recusado pelo servidor e chega em `errors.name`.
- Banco: `task_types` passa a ser por projeto (`project_id`, `color`, semeado com os 5 padrão ao criar o projeto) e `project_areas` ganha `color`: proposta no `docs/banco.md` §7, aprovado por Lucas Scholz em 15/10/2026. O código continua achando `support` e `improvement` pelo `slug`, agora por projeto.
