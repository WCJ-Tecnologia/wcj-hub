# WContactFormDialogOrganism

Organismo · `organisms/w-contact-form-dialog-organism/w-contact-form-dialog-organism.tsx` · sem story (aparece nas telas de contatos)

## O que faz

Modal de criar e editar contato (F1 §02), o mesmo na lista geral e na ficha do cliente. O cliente é fixo (o de onde se abriu) e aparece como texto; a recusa do servidor por cliente desativado chega fora dos campos (`errors.client`). Exporta `ContactFormData`.

## Props

| Prop | Tipo | Para quê |
| --- | --- | --- |
| `open`, `onOpenChange` | | controle do modal |
| `form` | `InertiaFormProps<ContactFormData>` | o `useForm` do `useContactActions` |
| `editing` | `boolean` | título e botão |
| `clientName` | `string` | o cliente fixo |
| `situation` | `string \| null` | só leitura |
| `onSubmit` | `(event) => void` | |

## Exemplo

Ver `hooks/use-contact-actions.tsx`.

## Cuidados

Telefone sem máscara: o cliente pode ser de fora e o "+" fica.
