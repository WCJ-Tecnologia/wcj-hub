# WUserFormDialogOrganism

Organismo · `organisms/w-user-form-dialog-organism/w-user-form-dialog-organism.tsx` · story `Organisms/User Form Dialog`

## O que faz

O modal de **criar e editar usuário**, com os campos papel (Select do shadcn), nome, e-mail, carga diária e "Usuário ativo". Ele **não tem estado próprio**: o `useForm` vive na página (um só, reaproveitado para criar e editar), e o modal só desenha os campos e os erros que o servidor devolveu.

No modo edição, `deactivatedNote` mostra quem desativou e quando, ligado ao checkbox por `aria-describedby`.

## Props

| Prop              | Tipo                              | Obrigatória | Para quê                                   |
| ----------------- | --------------------------------- | ----------- | ------------------------------------------ |
| `open`            | `boolean`                         | sim         |                                            |
| `onOpenChange`    | `(open: boolean) => void`         | sim         |                                            |
| `form`            | `InertiaFormProps<UserFormData>`  | sim         | O `useForm<UserFormData>(...)` da página   |
| `editing`         | `boolean`                         | sim         | Troca título, descrição e o texto do botão |
| `roles`           | `{ id: number; label: string }[]` | sim         | Opções do papel, vindas do controller      |
| `onSubmit`        | `(event: FormEvent) => void`      | sim         | A página decide `post` ou `put`            |
| `deactivatedNote` | `string \| null`                  | não         | "Desativado em …, por …"                   |

### Exportados junto

- `ROLE`: ids fixos dos papéis, espelho do `RoleEnum` (1 admin, 2 gestor, 3 dev, 4 comercial).
- `UserFormData`: `{ role_id: number; name: string; email: string; daily_hours: string; active: boolean }`.

## Exemplo (o que a página faz)

```tsx
import {
    ROLE,
    WUserFormDialogOrganism,
    type UserFormData,
} from '@/components/organisms/w-user-form-dialog-organism/w-user-form-dialog-organism';

const EMPTY: UserFormData = { role_id: ROLE.DEVELOPER, name: '', email: '', daily_hours: '', active: true };
const form = useForm<UserFormData>(EMPTY);

const submit = (event: FormEvent) => {
    event.preventDefault();
    const options = { preserveScroll: true, onSuccess: () => setOpen(false) };
    if (editing) form.put(`/admin/users/${editing.id}`, options);
    else form.post('/admin/users', options);
};

<WUserFormDialogOrganism open={open} onOpenChange={setOpen} form={form} editing={editing !== null} roles={roles} onSubmit={submit} />;
```

## Cuidados

- O botão "Salvar" está no rodapé do modal, fora do `<form>`, e é ligado por `form="user-form"`. Não duplique esse id na página.
- O `Select` do Radix não aceita `value=""`: o papel é sempre um id (`String(role_id)`).
- Validação e permissão são do servidor (`UserPolicy`, FormRequest). O modal só mostra `form.errors`.
