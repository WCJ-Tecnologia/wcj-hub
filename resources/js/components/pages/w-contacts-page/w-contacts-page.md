# WContactsPage

Tela · `pages/w-contacts-page/w-contacts-page.tsx` · story `Pages/Contatos`

## O que faz

Administração › Contatos (F1 §03): achar uma pessoa sem saber a empresa. Busca, filtro de cliente (Select do shadcn), "Mostrar desativados", tabela com a coluna Cliente, paginação e as ações do `useContactActions` (editar, desativar, reativar, anonimizar, excluir, histórico). O contato não nasce aqui (estado vazio manda para Clientes).

`Inertia::render('admin/contacts/index')`; `resources/js/pages/admin/contacts/index.tsx` só reexporta.

## Props

`contacts` (`Paginated<ContactRow>`), `filters` (`{ search, client_id, show_inactive }`), `clients` (`{ id, name }[]`: os que a pessoa vê)

## Exemplo

Ver `hooks/use-contact-actions.tsx`.

## Cuidados

O filtro "Todos os clientes" usa o valor sentinela `all`.
