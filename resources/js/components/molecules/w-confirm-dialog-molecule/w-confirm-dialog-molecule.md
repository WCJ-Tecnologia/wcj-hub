# WConfirmDialogMolecule

Molécula · `molecules/w-confirm-dialog-molecule/w-confirm-dialog-molecule.tsx` · story `Molecules/Confirm Dialog`

## O que faz

Modal de confirmação para ação destrutiva ou irreversível: excluir, desativar, reenviar senha. É feito sobre o `Dialog` do shadcn (Radix), com trava de foco, ESC e retorno de foco. **Nunca use o `window.confirm`.**

Enquanto `loading` está ligado, os botões ficam desabilitados e o modal não fecha.

## Props

| Prop           | Tipo                      | Padrão        | Obrigatória | Para quê                                       |
| -------------- | ------------------------- | ------------- | ----------- | ---------------------------------------------- |
| `open`         | `boolean`                 |               | sim         |                                                |
| `onOpenChange` | `(open: boolean) => void` |               | sim         |                                                |
| `title`        | `string`                  |               | sim         | "Excluir usuário"                              |
| `description`  | `ReactNode`               |               | não         | O que será afetado, **com o nome do registro** |
| `confirmLabel` | `string`                  | `'Confirmar'` | não         | Verbo da ação: "Excluir", "Enviar agora"       |
| `cancelLabel`  | `string`                  | `'Cancelar'`  | não         |                                                |
| `destructive`  | `boolean`                 | `false`       | não         | Botão de confirmar vermelho                    |
| `loading`      | `boolean`                 | `false`       | não         | Spinner e bloqueio durante o request           |
| `onConfirm`    | `() => void`              |               | sim         | Dispara o request                              |
| `children`     | `ReactNode`               |               | não         | Conteúdo extra (aviso, lista do que muda)      |

## Exemplo

Um estado com o registro escolhido; `null` fecha.

```tsx
const [confirmDelete, setConfirmDelete] = useState<UserRow | null>(null);
const [busy, setBusy] = useState(false);

<WConfirmDialogMolecule
    open={confirmDelete !== null}
    onOpenChange={(next) => !next && setConfirmDelete(null)}
    title="Excluir usuário"
    description={
        <>
            Excluir <strong>{confirmDelete?.name}</strong>?
        </>
    }
    confirmLabel="Excluir"
    destructive
    loading={busy}
    onConfirm={() =>
        confirmDelete &&
        router.delete(`/admin/users/${confirmDelete.id}`, {
            onStart: () => setBusy(true),
            onFinish: () => {
                setBusy(false);
                setConfirmDelete(null);
            },
        })
    }
/>;
```

## Cuidados

Exclusão de verdade exige aprovação humana neste projeto (`CLAUDE.md`). O comum é desativar ou arquivar.
