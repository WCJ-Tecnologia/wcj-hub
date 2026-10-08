# WFormFieldMolecule

Molécula · `molecules/w-form-field-molecule/w-form-field-molecule.tsx` · story `Molecules/Form Field`

## O que faz

Liga o rótulo, o controle, a mensagem de erro e a dica de um campo de formulário. Com `htmlFor` e **um único filho**, ele injeta no filho:

- `id` (= `htmlFor`, se o filho não tiver um);
- `aria-invalid` (verdadeiro quando há `error`);
- `aria-describedby` (aponta para o erro ou para a dica).

A tela não precisa repetir esses atributos.

## Props

| Prop        | Tipo        | Padrão | Obrigatória | Para quê                                                                            |
| ----------- | ----------- | ------ | ----------- | ----------------------------------------------------------------------------------- |
| `label`     | `string`    |        | sim         | Texto do rótulo                                                                     |
| `htmlFor`   | `string`    |        | não         | Id do controle; liga label e ARIA                                                   |
| `error`     | `string`    |        | não         | Ex.: `form.errors.name`. Tem prioridade sobre a dica e é anunciado (`role="alert"`) |
| `hint`      | `string`    |        | não         | Texto de ajuda abaixo do campo                                                      |
| `required`  | `boolean`   |        | não         | Mostra `*` vermelho (só visual; a regra é do servidor)                              |
| `className` | `string`    |        | não         |                                                                                     |
| `children`  | `ReactNode` |        | sim         | O controle (`Input`, `Textarea`…)                                                   |

## Exemplo

```tsx
import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Input } from '@/components/shadcn/input';

<WFormFieldMolecule label="Nome" htmlFor="name" required error={form.errors.name}>
    <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
</WFormFieldMolecule>;
```

## Com Select (Radix)

O `Select` raiz do Radix não é elemento HTML, então a injeção não chega ao botão. Passe `id`, `aria-invalid` e `aria-describedby` no `SelectTrigger`, como faz o `WUserFormDialogOrganism`:

```tsx
<WFormFieldMolecule label="Papel" htmlFor="role_id" required error={form.errors.role_id}>
    <Select value={String(form.data.role_id)} onValueChange={(v) => form.setData('role_id', Number(v))}>
        <SelectTrigger
            id="role_id"
            aria-invalid={Boolean(form.errors.role_id)}
            aria-describedby={form.errors.role_id ? 'role_id-error' : undefined}
        >
            <SelectValue />
        </SelectTrigger>
        <SelectContent>…</SelectContent>
    </Select>
</WFormFieldMolecule>
```
