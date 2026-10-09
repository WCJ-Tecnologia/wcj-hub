# WEmptyStateMolecule

Molécula · `molecules/w-empty-state-molecule/w-empty-state-molecule.tsx` · story `Molecules/Empty State`

## O que faz

Estado vazio de listagem ou painel: ícone num círculo, título, explicação e, opcionalmente, a ação que resolve o vazio ("Novo usuário").

O círculo segue a regra de ícone com fundo da marca: ícone azul sobre o tint `#E9F1FE` (`bg-accent text-accent-foreground`). Sem padrão de nós aqui, por decisão (ADR-004).

**Use** como `emptyState` do `WDataTableOrganism` ou quando um painel não tem dados.

## Props

| Prop          | Tipo        | Padrão                    | Obrigatória | Para quê                                        |
| ------------- | ----------- | ------------------------- | ----------- | ----------------------------------------------- |
| `title`       | `string`    |                           | sim         | "Nenhum usuário encontrado"                     |
| `description` | `string`    |                           | sim         | O que fazer a seguir                            |
| `icon`        | `ReactNode` | ícone de caixa de entrada | não         | Ex.: `<Users className="size-5" aria-hidden />` |
| `action`      | `ReactNode` |                           | não         | Botão da ação principal                         |

## Exemplo

```tsx
import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';

<WEmptyStateMolecule
    icon={<Users className="size-5" aria-hidden />}
    title="Nenhum usuário encontrado"
    description="Cadastre as pessoas que vão acessar o sistema."
    action={newButton}
/>;
```

## Cuidados

O título é um `<h3>`. Numa página com só o `<h1>` antes, o axe aponta `heading-order` (boa prática, fora da régua WCAG do e2e).
