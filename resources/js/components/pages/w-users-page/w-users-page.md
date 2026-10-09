# WUsersPage

Tela · `pages/w-users-page/w-users-page.tsx` · story `Pages/Usuários`

## O que faz

Administração › Usuários (F1 §03): busca, filtro de papel (Select do shadcn), "Mostrar desativados", tabela (`WUserTableOrganism`), paginação, modal de convidar/editar (`WUserFormDialogOrganism`, com "IA pode escrever") e as confirmações de reenviar convite, desativar, excluir e desconectar a IA (IA4/IA6: mudar papel ou a marca da IA avisa antes). Estados de lista pelo `WListBodyMolecule`; clique duplo pelo `useRowAction`.

O Laravel chama esta tela por `Inertia::render('admin/users/index')`. O arquivo `resources/js/pages/admin/users/index.tsx` só reexporta este componente (ADR-003). Mude a tela aqui, não lá.

## Props (vindas do controller)

`users` (`Paginated<UserRow>`), `filters` (`{ search, role_id, show_inactive }`), `roles` (`{ id, label }[]`)

## Observações

"Convidar usuário" só com `abilities.manage_users`; cada botão da linha vem decidido pela `UserPolicy` no servidor (`row.can`); Histórico com `view_audit_logs`. O filtro "Todos os papéis" usa o valor sentinela `all`, que vira `role_id` ausente no request.

## Story

As props de exemplo ficam em `.storybook/fixtures.ts`, e as props compartilhadas (usuário logado, marca, flash), em `parameters.inertia` da story. Formulários e navegação não saem do navegador: aparecem no painel **Actions**.
