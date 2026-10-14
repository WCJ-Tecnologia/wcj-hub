# WPageContainerAtom

Átomo · `atoms/w-page-container-atom/w-page-container-atom.tsx` · sem story (só estrutura)

## O que faz

Define a largura e o respiro da página. A largura é decisão de sistema, não escolha de cada tela.

| `width`   | Largura máxima   | Para quê                           |
| --------- | ---------------- | ---------------------------------- |
| `narrow`  | `max-w-3xl`      | Formulário, perfil                 |
| `default` | `max-w-7xl`      | Listagem                           |
| `wide`    | `max-w-[100rem]` | Painel denso (quadro, indicadores) |

Normalmente você **não usa direto**: o `WAppLayoutTemplate` recebe `width` e repassa para cá.

## Props

| Prop        | Tipo                              | Padrão      | Obrigatória |
| ----------- | --------------------------------- | ----------- | ----------- |
| `width`     | `'narrow' \| 'default' \| 'wide'` | `'default'` | não         |
| `className` | `string`                          |             | não         |
| `children`  | `ReactNode`                       |             | sim         |

`full` (14/10): sem largura máxima, para a tela que rola na horizontal (o quadro kanban); o gutter continua, e o quadro o desfaz com margem negativa para cortar na borda da janela.
