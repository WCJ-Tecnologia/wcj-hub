# WCollapsibleSectionMolecule

Molécula · `molecules/w-collapsible-section-molecule/w-collapsible-section-molecule.tsx` · story `Molecules/Collapsible Section`

## O que faz

A seção recolhível da ficha do card (F3 §01, R4: tudo além do título é opcional e vem recolhido). Um `section` com o cabeçalho como botão (`aria-expanded`, `aria-controls`), a contagem em Space Mono, uma linha de resumo que diz o que há dentro sem abrir, e as ações à direita, **fora** do botão (um botão dentro de outro não é válido).

O conteúdo fica no DOM com `hidden` quando recolhido: o estado dos formulários dentro dele sobrevive ao recolher.

## Props

| Prop           | Tipo                      | Padrão  | Para quê                                                 |
| -------------- | ------------------------- | ------- | -------------------------------------------------------- |
| `title`        | `string`                  |         | O nome da seção (h2)                                     |
| `summary`      | `ReactNode`               |         | Resumo numa linha: "Melhoria · Portal do cidadão · Alta" |
| `count`        | `number`                  |         | Contagem ao lado do título                               |
| `actions`      | `ReactNode`               |         | Botões à direita (ex.: _Editar_)                         |
| `defaultOpen`  | `boolean`                 | `false` | Começa aberta (não controlada)                           |
| `open`         | `boolean`                 |         | Controlado; com `onOpenChange`                           |
| `onOpenChange` | `(open: boolean) => void` |         | Avisa a troca (nos dois modos)                           |
| `children`     | `ReactNode`               |         | O conteúdo                                               |

A raiz leva `data-slot="w-collapsible-section-molecule"` e `data-open` quando aberta.

## Exemplo

```tsx
<WCollapsibleSectionMolecule title="Critérios de aceite" count={4} summary="1 aprovado · 1 reprovado · 2 a testar">
    <WTaskCriteriaSectionOrganism … />
</WCollapsibleSectionMolecule>
```

## Cuidados

- A seção começa recolhida por regra (R4). Quem quiser abrir uma por padrão (ex.: a seção que acabou de receber um erro do servidor) usa `open` controlado.
- O `summary` é texto de uma linha, truncado: não ponha botão nem link nele (ficaria dentro do botão do cabeçalho).
