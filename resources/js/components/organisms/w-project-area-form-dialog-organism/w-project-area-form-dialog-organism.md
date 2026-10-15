# WProjectAreaFormDialogOrganism

Organismo · `organisms/w-project-area-form-dialog-organism/w-project-area-form-dialog-organism.tsx` · sem story (aparece em `Pages/Configuração do projeto`)

## O que faz

Modal de criar e editar área do projeto (F2 §02, aba Áreas; P18): Nome, até 60, único no projeto sem acento e caixa (recusa do servidor com o nome da área existente). Na edição mostra a situação só leitura; desativar e reativar são ações da linha. Exporta `AreaFormData`.

## Props

| Prop                   | Tipo                             | Para quê                               |
| ---------------------- | -------------------------------- | -------------------------------------- |
| `open`, `onOpenChange` |                                  | controle                               |
| `form`                 | `InertiaFormProps<AreaFormData>` | o `useForm` da aba                     |
| `editing`              | `boolean`                        | título e botão                         |
| `situation`            | `string \| null`                 | `situation(row)` de `lib/situation.ts` |
| `onSubmit`             | `(event) => void`                | a aba faz o `POST` ou o `PUT`          |

## Cuidados

A cor da etiqueta (`WTagColorPickerMolecule`) entra aqui quando a coluna `project_areas.color` existir (decisão de 14/10, migração do B3).
