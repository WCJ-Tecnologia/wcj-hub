# WStatCardMolecule

Molécula · `molecules/w-stat-card-molecule/w-stat-card-molecule.tsx` · story `Molecules/Stat Card`

## O que faz

Indicador do dashboard: rótulo, número grande, ícone num quadrado colorido, dica opcional e barra de progresso opcional. É feito sobre o `Card` do shadcn.

O número segue o "número como prova" do Brand Board: Space Mono, azul de texto (`text-primary-text`), algarismos tabulares. O tom `primary` do ícone é o tint da marca (`bg-accent`), com o ícone azul.

## Props

| Prop        | Tipo                                                              | Padrão      | Obrigatória | Para quê                                                             |
| ----------- | ----------------------------------------------------------------- | ----------- | ----------- | -------------------------------------------------------------------- |
| `label`     | `string`                                                          |             | sim         | O que é medido                                                       |
| `value`     | `string \| number`                                                |             | sim         | O número (já formatado, se for `string`)                             |
| `hint`      | `string`                                                          |             | não         | Linha pequena abaixo ("2 inativos")                                  |
| `icon`      | `LucideIcon`                                                      |             | não         | Ícone do `lucide-react` (passe o componente, não o elemento)         |
| `tone`      | `'default' \| 'primary' \| 'pending' \| 'approved' \| 'rejected'` | `'default'` | não         | Cor do quadrado do ícone                                             |
| `progress`  | `number` (0–100)                                                  |             | não         | Barra: primária até 79, amarela de 80 a 99, vermelha a partir de 100 |
| `className` | `string`                                                          |             | não         |                                                                      |

## Exemplo

```tsx
import { UserCheck } from 'lucide-react';
import { WStatCardMolecule } from '@/components/molecules/w-stat-card-molecule/w-stat-card-molecule';

<WStatCardMolecule label="Usuários ativos" value={summary.users_active} icon={UserCheck} tone="approved" />;
```

## Cuidados

Número de controle (capacidade, previsão) é referência: a barra vermelha avisa, mas não trava nada (premissa de Projetos no `CLAUDE.md`).
