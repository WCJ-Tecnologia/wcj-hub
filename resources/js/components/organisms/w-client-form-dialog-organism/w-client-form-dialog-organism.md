# WClientFormDialogOrganism

Organismo · `organisms/w-client-form-dialog-organism/w-client-form-dialog-organism.tsx` · sem story (aparece em `Pages/Clientes`)

## O que faz

Modal de criar e editar cliente (F1 §02): Nome, País (Select do shadcn) e o documento, que muda com o país: **CNPJ** alfanumérico (`AlphanumericCnpjInput`) no Brasil, **Identificador fiscal** livre fora dele (N5). O `useForm` vive na tela. Desativar e reativar são ações da linha: a situação aqui é só leitura. Exporta `BRASIL`, `Country`, `ClientFormData` e `documentLabel()`.

## Props

| Prop | Tipo | Para quê |
| --- | --- | --- |
| `open`, `onOpenChange` | | controle do modal |
| `form` | `InertiaFormProps<ClientFormData>` | o `useForm` da tela |
| `editing` | `boolean` | título e botão |
| `situation` | `string \| null` | "Ativo" / "Desativado em…" (`lib/situation.ts`) |
| `countries` | `Country[]` | a lista do servidor (`App\Support\Paises`) |
| `onSubmit` | `(event) => void` | o submit da tela |

## Exemplo

Ver `pages/w-clients-page/w-clients-page.tsx`.

## Cuidados

O gatilho do Select leva `id`, `aria-invalid` e `aria-describedby`: o Root do Radix não é elemento.
