# WSupportCardDialogOrganism

Organismo · `organisms/w-support-card-dialog-organism/w-support-card-dialog-organism.tsx` · story `Organisms/Support Card Dialog`

## O que faz

Diálogo _Novo card de suporte_ do F3 §02 (P52), aberto pelo botão no topo da coluna Support. Campos: Título (até 200), Quem pediu (contatos ativos do cliente), Por onde (canais), Quando (data pelo `WDatePickerMolecule` + hora em `Input type="time"`, padrão "agora") e Prioridade (as 4 do `PRIORITIES`, padrão Normal). Nota fixa: "Tipo: Suporte. O card nasce na coluna Support, fora da sprint."

Sem contato ativo no cliente, o campo Quem pediu vira o aviso "Nenhum contato ativo neste cliente. Cadastre na ficha do cliente." e o botão de criar fica desabilitado.

## Props

| Prop                   | Tipo                                    | Padrão      | Obrigatória | Para quê                                                                                          |
| ---------------------- | --------------------------------------- | ----------- | ----------- | ------------------------------------------------------------------------------------------------- |
| `open`, `onOpenChange` |                                         |             | sim         | controle do diálogo                                                                               |
| `contacts`             | `BoardOptions['contacts']`              |             | sim         | quem pediu (P52); anonimizado vem como "removido a pedido do titular" (N12)                       |
| `channels`             | `BoardOptions['channels']`              |             | sim         | por onde                                                                                          |
| `now`                  | `{ date: 'AAAA-MM-DD'; time: 'HH:MM' }` |             | sim         | o "agora" proposto, no fuso de São Paulo; a tela calcula                                          |
| `stageName`            | `string`                                | `'Support'` | não         | nome da coluna de suporte no texto                                                                |
| `loading`              | `boolean`                               | `false`     | não         | spinner, campos travados, não fecha                                                               |
| `errors`               | `Record<string, string>`                |             | não         | `title`, `requester_contact_id`, `contact_channel_id`, `requested_at`, `priority`                 |
| `onConfirm`            | `(payload: SupportCardPayload) => void` |             | sim         | `{ title, requester_contact_id, contact_channel_id, requested_at: 'AAAA-MM-DD HH:MM', priority }` |

## Exemplo

```tsx
<WSupportCardDialogOrganism
    open={supportOpen}
    onOpenChange={setSupportOpen}
    contacts={options.contacts}
    channels={options.channels}
    now={nowInSaoPaulo()}
    loading={form.processing}
    errors={form.errors}
    onConfirm={(payload) => router.post(`/projetos/${project.id}/cards/suporte`, payload)}
/>
```

## Cuidados

- Botão só para `abilities.create_support` (gestor do projeto e Administrador, projeto aberto, P7, P52).
- O erro do servidor em `requested_at` aparece sob a data; a hora só tem a validação de formato da tela.
- O "agora" vem por prop, não de `new Date()` aqui dentro: a story fica fixa e o fuso é decisão da página.
