# WProjectAreasTabOrganism

Organismo · `organisms/w-project-areas-tab-organism/w-project-areas-tab-organism.tsx` · aparece em `Pages/Configuração do projeto`

## O que faz

A aba Áreas (F2 §02 a §04; P18, G2): tabela Nome e Situação, a chave "Mostrar desativados" (recarrega a aba com `show_inactive=1`) e as ações Editar, Desativar, Reativar e Excluir para quem gere. O formulário é o `WProjectAreaFormDialogOrganism`. Escreve em `POST /projetos/{id}/areas`, `PUT …/areas/{area}`, `POST …/areas/{area}/deactivate|reactivate` e `DELETE …/areas/{area}` (área usada por card volta com toast de erro, G2).

## Props

| Prop           | Tipo        | Para quê                              |
| -------------- | ----------- | ------------------------------------- |
| `projectId`    | `number`    | as rotas                              |
| `areas`        | `AreaRow[]` | as linhas, já filtradas pelo servidor |
| `showInactive` | `boolean`   | `filters.show_inactive`               |
| `canManage`    | `boolean`   | mostra os botões                      |
