# WProjectDefaultsTabOrganism

Organismo · `organisms/w-project-defaults-tab-organism/w-project-defaults-tab-organism.tsx` · aparece em `Pages/Configuração do projeto`

## O que faz

A aba Padrões (F2 §02, §04; P15, P46): a caixa **Pedir motivo na pausa** e o campo **Duração da sprint** (inteiro de dias úteis, maior que 0), num formulário com **Salvar padrões** (habilitado só com mudança). Escreve em `PUT /projetos/{id}/padroes`; vale dali para a frente (P8).

## Props

| Prop        | Tipo              | Para quê           |
| ----------- | ----------------- | ------------------ |
| `projectId` | `number`          | a rota             |
| `defaults`  | `ProjectDefaults` | `project.defaults` |

## Cuidados

Só quem gere vê a aba (F2 §09); o servidor confere `manage` de qualquer jeito.
