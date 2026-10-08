# WPageHeaderMolecule

Molécula · `molecules/w-page-header-molecule/w-page-header-molecule.tsx` · story `Molecules/Page Header`

## O que faz

Topo de toda página do backoffice: eyebrow (grupo), o **único `<h1>` da tela**, subtítulo e as ações à direita.

Normalmente você **não usa direto**: passe `eyebrow`, `title`, `subtitle` e `actions` para o `WAppLayoutTemplate`, que monta este cabeçalho.

## Props

| Prop        | Tipo        | Obrigatória | Para quê                             |
| ----------- | ----------- | ----------- | ------------------------------------ |
| `eyebrow`   | `string`    | não         | Grupo do menu ("Cadastros", "Conta") |
| `title`     | `string`    | sim         | O `<h1>`                             |
| `subtitle`  | `string`    | não         | Uma frase sobre a tela               |
| `actions`   | `ReactNode` | não         | Botões ("Novo usuário")              |
| `className` | `string`    | não         |                                      |
