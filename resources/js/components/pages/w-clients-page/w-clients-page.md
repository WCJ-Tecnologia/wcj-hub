# WClientsPage

Tela · `pages/w-clients-page/w-clients-page.tsx` · story `Pages/Clientes`

## O que faz

Administração › Clientes (F1 §03): busca, filtro de país (Select do shadcn), "Mostrar desativados", tabela, paginação, modal de criar/editar e as confirmações de desativar (com a contagem de projetos abertos, F1 §04) e excluir. Os botões da linha vêm decididos pela `ClientPolicy` (`row.can`); `can.create` decide o "Novo cliente".

O Laravel chama esta tela por `Inertia::render('admin/clients/index')`; `resources/js/pages/admin/clients/index.tsx` só reexporta.

## Props

`clients` (`Paginated<ClientRow>`), `filters` (`{ search, country, show_inactive }`), `countries` (`Country[]`), `can` (`{ create }`)

## Exemplo

Estados de lista pelo `WListBodyMolecule`; clique duplo pelo `useRowAction`.

## Cuidados

O filtro "Todos os países" usa o valor sentinela `all`, que vira `country` ausente no request.
