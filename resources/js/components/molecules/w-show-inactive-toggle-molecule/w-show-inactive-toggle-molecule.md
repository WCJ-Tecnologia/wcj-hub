# WShowInactiveToggleMolecule

Molécula · `molecules/w-show-inactive-toggle-molecule/w-show-inactive-toggle-molecule.tsx` · sem story (aparece nas telas)

## O que faz

A chave **Mostrar desativados** das listas do Núcleo (F1 §05). Só a caixa liga e desliga: o texto não é `<label>`, para um clique perto do filtro não trocar a lista sem querer (pedido do Cauã, 07/10). O texto segue sendo o nome acessível da caixa, por `aria-labelledby`: `getByLabel('Mostrar desativados')` acha a caixa.

## Props

| Prop | Tipo | Para quê |
| --- | --- | --- |
| `checked` | `boolean` | `filters.show_inactive` |
| `onChange` | `(checked: boolean) => void` | a visita com `show_inactive: 1` ou ausente |

## Exemplo

```tsx
<WShowInactiveToggleMolecule checked={filters.show_inactive} onChange={(checked) => visit({ show_inactive: checked ? 1 : undefined })} />
```

## Cuidados

Sobre o `Checkbox` do shadcn.
