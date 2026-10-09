# WAnonymizeContactDialogOrganism

Organismo · `organisms/w-anonymize-contact-dialog-organism/w-anonymize-contact-dialog-organism.tsx` · sem story (aparece nas telas de contatos)

## O que faz

Anonimizar é irreversível (N12): a pessoa digita **ANONIMIZAR** para confirmar (F1 §04). O botão só libera com a palavra exata (`ANONYMIZE_WORD`, espelho de `AnonymizeContactRequest::WORD`); o servidor confere de novo.

## Props

| Prop | Tipo | Para quê |
| --- | --- | --- |
| `open`, `onOpenChange` | | controle do modal (não fecha enquanto envia) |
| `contactName` | `string` | o nome no título |
| `form` | `InertiaFormProps<{ confirmation: string }>` | o `useForm` do `useContactActions` |
| `onSubmit` | `(event) => void` | |

## Exemplo

Ver `hooks/use-contact-actions.tsx`.

## Cuidados

Exclusão de verdade e anonimização exigem aprovação humana no projeto (AGENTS.md).
