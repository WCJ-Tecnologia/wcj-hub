# WBoardStageFormDialogOrganism

Organismo · `organisms/w-board-stage-form-dialog-organism/w-board-stage-form-dialog-organism.tsx` · story `Organisms/Etapa do quadro`

## O que faz

Modal de criar e editar etapa (F2 §02, aba Etapas): Nome (até 60) e Papel. Na etapa nova, só os papéis que podem se repetir (Desenvolvimento, Code review, Teste, Pronto, Pausa). Em Fila, Sprint e Suporte o papel é texto só leitura; nas demais, o Select fica desabilitado quando `editing.role_locked` (algum card já passou pela etapa, P8, P11). O `useForm` vive na aba. Exporta `StageFormData`.

## Props

| Prop                   | Tipo                              | Para quê                         |
| ---------------------- | --------------------------------- | -------------------------------- |
| `open`, `onOpenChange` |                                   | controle do modal                |
| `form`                 | `InertiaFormProps<StageFormData>` | o `useForm` da aba               |
| `editing`              | `StageRow \| null`                | a etapa em edição; `null` = nova |
| `roles`                | `StageRoleOption[]`               | `options.stage_roles`            |
| `onSubmit`             | `(event) => void`                 | a aba faz o `POST` ou o `PUT`    |

## Cuidados

Com o papel congelado, a aba não manda `stage_role_id` (o servidor recusaria a troca com toast); com `''` o campo também não vai.
