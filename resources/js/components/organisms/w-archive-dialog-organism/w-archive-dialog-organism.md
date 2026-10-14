# WArchiveDialogOrganism

Organismo · `organisms/w-archive-dialog-organism/w-archive-dialog-organism.tsx` · story `Organisms/Archive Dialog`

## O que faz

Diálogo _Arquivar_ do F3 §02 (P26): "Arquivar SGM-17?", "Ele sai do quadro e volta pelo filtro Arquivados.", com o campo Motivo (obrigatório, até 500). Devolve `{ archive_reason }` por `onConfirm`. O botão "Arquivar" é o `default` (azul), não o vermelho: arquivar é reversível (_Desarquivar_ devolve ao Backlog).

## Props

| Prop                   | Tipo                                | Padrão  | Obrigatória | Para quê                             |
| ---------------------- | ----------------------------------- | ------- | ----------- | ------------------------------------ |
| `open`, `onOpenChange` |                                     |         | sim         | controle do diálogo                  |
| `card`                 | `BoardCard`                         |         | sim         | a chave no título                    |
| `loading`              | `boolean`                           | `false` | não         | spinner, campo travado, não fecha    |
| `errors`               | `Record<string, string>`            |         | não         | erro do servidor em `archive_reason` |
| `onConfirm`            | `(payload: ArchivePayload) => void` |         | sim         | `{ archive_reason: string }`         |

## Exemplo

```tsx
<WArchiveDialogOrganism
    open={archiving !== null}
    onOpenChange={(next) => !next && setArchiving(null)}
    card={archiving}
    loading={form.processing}
    errors={form.errors}
    onConfirm={(payload) => router.post(`${archiving.url}/arquivar`, payload)}
/>
```

## Cuidados

- Só o gestor do projeto e o Administrador arquivam (`abilities.archive`, P26): a tela esconde o botão; a `TaskPolicy` decide.
- Card nunca usado se _exclui_ (G2), com o `WConfirmDialogMolecule` destrutivo; este diálogo é só para o card com histórico.
