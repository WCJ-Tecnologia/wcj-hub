# WChildProgressMolecule

Molécula · `molecules/w-child-progress-molecule/w-child-progress-molecule.tsx` · story `Molecules/Child Progress`

## O que faz

No card pai (P57), o progresso dos filhos (F3 §03, rodapé):

- "2 · 1 · 3" em Space Mono = a fazer · em andamento · concluídos, pela coluna em que cada filho está; o tooltip e um `sr-only` dizem o que é cada número ("2 a fazer · 1 em andamento · 3 concluídos");
- o percentual concluído à direita e a barra (`role="progressbar"`, `aria-label="Progresso dos cards filhos"`, `aria-valuenow`), fundo `bg-muted` e preenchimento `bg-primary`.

O percentual vem de `childProgressPercent()` (`lib/quadro.ts`): concluídos ÷ total, arredondado; sem filhos, 0.

## Props

| Prop        | Tipo                    | Padrão | Obrigatória | Para quê                                                 |
| ----------- | ----------------------- | ------ | ----------- | -------------------------------------------------------- |
| `progress`  | `ChildProgress \| null` |        | sim         | `card.children`; null (card que não é pai) não renderiza |
| `className` | `string`                |        | não         |                                                          |

A prop se chama `progress`, não `children`, para não se confundir com os filhos do React.

## Exemplo

```tsx
import { WChildProgressMolecule } from '@/components/molecules/w-child-progress-molecule/w-child-progress-molecule';

<WChildProgressMolecule progress={card.children} />;
```

## Cuidados

- Filho arquivado não conta: a conta é do servidor (P57).
- Nada trava o pai pelos filhos (premissa): a barra é referência; pai com filhos a fazer pode ir para o Done.
