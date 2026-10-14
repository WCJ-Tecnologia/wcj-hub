# WTagAtom

Átomo · `atoms/w-tag-atom/w-tag-atom.tsx` · story `Atoms/Tag`

## O que faz

A etiqueta colorida do card (F3 §03): o **tipo** e a **área**, com a cor escolhida no cadastro de cada lista (decisão de Lucas Scholz em 14/10, como no Trello). No card e na ficha a ordem é sempre tipo primeiro, área depois.

- Paleta fixa de 10 cores (`TagColor`, `lib/tags.ts`), por token (`--tag-<cor>` / `--tag-<cor>-bg`, claro e escuro, contraste AA conferido); `null` = cinza neutro.
- A cor nunca comunica sozinha: o texto está sempre na etiqueta, e `kind` põe "Tipo: " / "Área: " em `sr-only`.

## Props

| Prop        | Tipo                | Padrão    | Para quê                      |
| ----------- | ------------------- | --------- | ----------------------------- |
| `label`     | `string`            |           | O texto                       |
| `color`     | `TagColor \| null`  |           | A cor; `null` = cinza         |
| `kind`      | `'type' \| 'area'`  |           | Prefixo para o leitor de tela |
| `size`      | `'sm' \| 'default'` | `default` | `sm` no card do quadro        |
| `className` | `string`            |           |                               |

A raiz leva `data-slot="w-tag-atom"` e `data-color`.

## Exemplo

```tsx
<WTagAtom label={card.type.label} color={card.type.color} kind="type" size="sm" />;
{
    card.area && <WTagAtom label={card.area.name} color={card.area.color} kind="area" size="sm" />;
}
```

## Cuidados

- Cor nova na paleta: entra em `design/tokens.json` (claro e escuro), no `TagColor` e nos mapas de `lib/tags.ts`; nunca classe solta.
- A escolha da cor é do cadastro (lista geral de tipos, F1; áreas do projeto, F2), pelo `WTagColorPickerMolecule`. As colunas `task_types.color` e `project_areas.color` são proposta, pendentes da conferência do Lucas Alencar.
