# WClientShowPage

Tela · `pages/w-client-show-page/w-client-show-page.tsx` · story `Pages/Ficha do cliente`

## O que faz

A ficha do cliente (F1 §01; ADR-004 do B1): os dados (País, CNPJ / identificador, Situação) e a seção Contatos, com "Novo contato" e as ações de cada contato pelo `useContactActions`. `contacts` nulo esconde a seção (quem não vê os contatos deste cliente, F1 §09).

`Inertia::render('admin/clients/show')`; `resources/js/pages/admin/clients/show.tsx` só reexporta.

## Props

`client` (`ClientRow`), `contacts` (`ContactRow[] | null`), `can_create_contact` (`boolean`)

## Exemplo

Ver `hooks/use-contact-actions.tsx`.

## Cuidados

O contato nasce aqui: o cliente é fixo no cadastro.
