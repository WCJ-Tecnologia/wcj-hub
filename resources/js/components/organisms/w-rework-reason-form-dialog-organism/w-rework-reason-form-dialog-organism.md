# WReworkReasonFormDialogOrganism

Organismo · `organisms/w-rework-reason-form-dialog-organism/w-rework-reason-form-dialog-organism.tsx` · sem story (aparece em `Pages/Listas`)

## O que faz

Modal de criar e editar motivo de retrabalho (F2 §02; P40, G7): Nome (até 80, único; a recusa vem do servidor com o nome existente) e a caixa **Pede texto**. Com `fixed` (o "Outro") a caixa fica marcada e desabilitada. Exporta `ReworkReasonFormData`.

## Props

| Prop                   | Tipo                                     | Para quê                                                  |
| ---------------------- | ---------------------------------------- | --------------------------------------------------------- |
| `open`, `onOpenChange` |                                          | controle                                                  |
| `form`                 | `InertiaFormProps<ReworkReasonFormData>` | o `useForm` da tela                                       |
| `editing`              | `boolean`                                | título e botão                                            |
| `fixed`                | `boolean`                                | `row.fixed`: o "Outro"                                    |
| `situation`            | `string \| null`                         | `situation(row)`                                          |
| `onSubmit`             | `(event) => void`                        | a tela faz o `POST` ou o `PUT` em `/admin/rework-reasons` |
