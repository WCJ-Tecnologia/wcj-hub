# WRowActionsMolecule

Molécula · `molecules/w-row-actions-molecule/w-row-actions-molecule.tsx` · story `Molecules/Row Actions`

## O que faz

Botões de ação de uma linha de listagem: editar, excluir, enviar senha etc.

- **Desktop** (`labeled={false}`): botões só com ícone e `aria-label`.
- **Celular** (`labeled={true}`, dentro do `mobileCard`): botões com contorno e texto.

A ação que a pessoa não pode executar some (`hidden`). Não aparece desabilitada.

## Props

| Prop        | Tipo           | Padrão  | Obrigatória |
| ----------- | -------------- | ------- | ----------- |
| `actions`   | `WRowAction[]` |         | sim         |
| `labeled`   | `boolean`      | `false` | não         |
| `className` | `string`       |         | não         |

### `WRowAction`

| Campo         | Tipo         | Obrigatório | Para quê                                                |
| ------------- | ------------ | ----------- | ------------------------------------------------------- |
| `label`       | `string`     | sim         | Texto no celular e `aria-label` padrão                  |
| `ariaLabel`   | `string`     | não         | `aria-label` com o nome do registro: "Editar Ana Souza" |
| `icon`        | `LucideIcon` | sim         | Ícone do `lucide-react`                                 |
| `onClick`     | `() => void` | sim         |                                                         |
| `destructive` | `boolean`    | não         | Ícone vermelho                                          |
| `hidden`      | `boolean`    | não         | Esconde a ação (ex.: `!abilities.delete_records`)       |

## Exemplo

```tsx
import { Pencil, Trash2 } from 'lucide-react';
import { WRowActionsMolecule } from '@/components/molecules/w-row-actions-molecule/w-row-actions-molecule';

<WRowActionsMolecule
    actions={[
        { label: 'Editar', ariaLabel: `Editar ${row.name}`, icon: Pencil, onClick: () => openEdit(row) },
        {
            label: 'Excluir',
            ariaLabel: `Excluir ${row.name}`,
            icon: Trash2,
            onClick: () => setConfirm(row),
            destructive: true,
            hidden: !canDelete,
        },
    ]}
/>;
```

## Cuidados

`hidden` só esconde o botão. Quem decide é a Policy no servidor (AGENTS.md, inegociável 8). Ação destrutiva abre um `WConfirmDialogMolecule`.
