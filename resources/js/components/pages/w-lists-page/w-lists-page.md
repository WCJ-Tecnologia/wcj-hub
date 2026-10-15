# WListsPage

Página · `pages/w-lists-page/w-lists-page.tsx` · Inertia `admin/lists/index` · story `Pages/Listas`

## O que faz

Administração → Listas (`/admin/listas`; F2 §02 a §04; G7, P40): os motivos de retrabalho, só do Administrador. Tabela Nome (com a etiqueta "Fixo" no Outro), Pede texto e Situação; o Outro vem por último. Novo, Editar, Desativar, Reativar e Excluir (só o nunca usado; o usado volta com toast, G2). O "Mostrar desativados" recarrega com `show_inactive=1`.

## Props

`reworkReasons` (`ReworkReasonRow[]`), `filters.show_inactive`, `can.create`, do `ReworkReasonController::index`.

## Cuidados

Os tipos de tarefa não estão aqui: passaram a ser do projeto em 14/10 e entram com a migração do B3 (F2 §03).
