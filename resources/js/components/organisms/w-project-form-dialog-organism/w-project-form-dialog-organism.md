# WProjectFormDialogOrganism

Organismo · `organisms/w-project-form-dialog-organism/w-project-form-dialog-organism.tsx` · sem story (aparece em `Pages/Projetos`)

## O que faz

Modal de criar e editar projeto (F2 §02): Cliente (Select do shadcn, só na criação: na edição aparece só leitura, P1), Gestor responsável, Prefixo (só leitura depois da primeira tarefa, P3), Nome, Tipo (Projeto de entrega / Produto contínuo, que esconde e limpa a Entrega, P2), Início e Entrega (`WDatePickerMolecule`). O `useForm` vive na tela. Exporta `ProjectFormData`, `ProjectType` e `NamedOption`.

## Props

| Prop | Tipo | Para quê |
| --- | --- | --- |
| `open`, `onOpenChange` | | controle do modal |
| `form` | `InertiaFormProps<ProjectFormData>` | o `useForm` da tela |
| `editing` | `boolean` | título, botão e cliente só leitura |
| `clientName` | `string` | na edição, o cliente do projeto |
| `prefixLocked` | `boolean` | `row.prefix_locked`: depois da primeira tarefa |
| `clients`, `managers` | `NamedOption[]` | as listas do servidor (`formOptions`) |
| `onSubmit` | `(event) => void` | o submit da tela (que confirma a troca de responsável antes) |

## Exemplo

Ver `pages/w-projects-page/w-projects-page.tsx`.

## Cuidados

O Root do Select do Radix aceita `''` (mostra o placeholder); os itens, não. `id` e `aria-*` vão no gatilho. O tipo não vai ao servidor: é a Entrega preenchida ou vazia (P2).
