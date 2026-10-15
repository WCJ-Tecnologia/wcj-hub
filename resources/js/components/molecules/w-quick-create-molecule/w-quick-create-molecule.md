# WQuickCreateMolecule

Molécula · `molecules/w-quick-create-molecule/w-quick-create-molecule.tsx` · story `Molecules/Quick Create`

## O que faz

O "título + Enter" do topo do Backlog (F3 §01, P17, R4). Campo com o placeholder "Novo card: título e Enter" e `aria-label="Título do novo card"`:

- Enter com texto chama `onCreate(título sem espaços nas pontas)`, limpa o campo e deixa o foco nele para o próximo título, como no Trello;
- Enter sem texto não faz nada; Esc limpa;
- até 200 caracteres (`QUICK_CREATE_MAX`, o mesmo limite do diálogo de suporte);
- `busy`: o ícone `+` vira spinner, o campo fica `readOnly` + `aria-busy` e o Enter espera;
- `error`: a mensagem do servidor abaixo, com `role="alert"`, ligada ao campo por `aria-describedby`.

## Props

| Prop        | Tipo                      | Padrão  | Obrigatória | Para quê                                             |
| ----------- | ------------------------- | ------- | ----------- | ---------------------------------------------------- |
| `onCreate`  | `(title: string) => void` |         | sim         | A página manda `POST /projetos/{id}/cards { title }` |
| `busy`      | `boolean`                 | `false` | não         | O card anterior ainda está sendo criado              |
| `error`     | `string`                  |         | não         | Erro do servidor (`errors.title`)                    |
| `className` | `string`                  |         | não         |                                                      |

## Exemplo

```tsx
<WQuickCreateMolecule onCreate={createCard} busy={creating} error={createError} />
```

## Cuidados

- **Quem decide se aparece é a coluna**: só no Backlog (`role === 'queue'`) e com `abilities.create_card` (alocado, projeto aberto, P7, P56).
- `readOnly`, não `disabled`, enquanto cria: campo desabilitado perde o foco e o próximo título não teria onde ir. Se algo tirou o foco durante a criação, ele volta ao campo quando `busy` cai.
- O campo limpa no Enter, antes da resposta (como no Trello). Se o servidor recusar, o título digitado não volta; a mensagem aparece em `error`.

## `autoFocus` e `onCancel` (14/10)

O campo passou a abrir pelo "+" do cabeçalho da coluna: `autoFocus` foca ao aparecer, e `onCancel` é chamado com Esc no campo vazio ou ao sair dele vazio, para a coluna esconder o campo de novo. Com texto, Esc só limpa, como antes.
