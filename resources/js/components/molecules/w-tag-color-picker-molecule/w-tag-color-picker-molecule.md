# WTagColorPickerMolecule

Molécula · `molecules/w-tag-color-picker-molecule/w-tag-color-picker-molecule.tsx` · story `Molecules/Tag Color Picker`

## O que faz

O seletor de cor da etiqueta, para os cadastros onde a cor se define (decisão de 14/10, como no Trello): a lista geral de tipos de card (F1, Administrador) e as áreas do projeto (F2, gestor). Mostra "Sem cor" (tracejado) mais as 10 cores de `lib/tags.ts` como grupo de rádio: setas trocam, Espaço/Enter escolhe, cada bolinha tem o nome da cor (`aria-label` e `title`); a escolhida ganha o anel e o ✓.

É só a escolha: quem grava é o formulário (`color` no payload de tipo e de área).

## Props

| Prop       | Tipo                                | Padrão            | Para quê              |
| ---------- | ----------------------------------- | ----------------- | --------------------- |
| `value`    | `TagColor \| null`                  |                   | A cor atual           |
| `onChange` | `(color: TagColor \| null) => void` |                   | Escolha nova          |
| `label`    | `string`                            | `Cor da etiqueta` | `aria-label` do grupo |
| `id`       | `string`                            |                   | Id do grupo           |
| `disabled` | `boolean`                           | `false`           |                       |

A raiz leva `data-slot="w-tag-color-picker-molecule"`.

## Exemplo

```tsx
<WFormFieldMolecule label="Cor da etiqueta" htmlFor={`${id}-color`}>
    <WTagColorPickerMolecule id={`${id}-color`} value={form.data.color} onChange={(color) => form.setData('color', color)} />
</WFormFieldMolecule>
```

## Cuidados

- Entra nos formulários de tipo (B2 passo 2, listas gerais) e de área (B2 passo 2, configuração do projeto), que ainda não existem: o kit entrega só a peça.
- As colunas `task_types.color` e `project_areas.color` (varchar(10), null) são proposta no `docs/banco.md`, pendentes da conferência do Lucas Alencar.
