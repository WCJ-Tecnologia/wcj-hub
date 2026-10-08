# WUsersPage

Tela · `pages/w-users-page/w-users-page.tsx` · story `Pages/Usuários`

## O que faz

Lista de usuários com busca, filtro de papel (Select do shadcn), tabela, paginação, modal de criar/editar e confirmações de excluir e de enviar senha temporária.

O Laravel chama esta tela por `Inertia::render('admin/users/index')`. O arquivo `resources/js/pages/admin/users/index.tsx` só reexporta este componente (ADR-003). Mude a tela aqui, não lá.

## Props (vindas do controller)

`users` (`Paginated<UserRow>`), `filters` (`{ search, role_id }`), `roles` (`{ id, label }[]`)

## Observações

Botões de gerir só aparecem com `abilities.manage_users`; excluir com `delete_records`; enviar senha com `reset_passwords`. O filtro "Todos os papéis" usa o valor sentinela `all`, que vira `role_id` ausente no request.

## Story

As props de exemplo ficam em `.storybook/fixtures.ts`, e as props compartilhadas (usuário logado, marca, flash), em `parameters.inertia` da story. Formulários e navegação não saem do navegador: aparecem no painel **Actions**.
