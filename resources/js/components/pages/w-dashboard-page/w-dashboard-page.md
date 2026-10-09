# WDashboardPage

Tela · `pages/w-dashboard-page/w-dashboard-page.tsx` · story `Pages/Dashboard`

## O que faz

Indicadores de usuários (`WStatCardMolecule`) e a atividade recente, com link para a auditoria.

O Laravel chama esta tela por `Inertia::render('admin/dashboard')`. O arquivo `resources/js/pages/admin/dashboard.tsx` só reexporta este componente (ADR-003). Mude a tela aqui, não lá.

## Props (vindas do controller)

`summary` (`{ users_active, users_total }`), `activity` (lista; vazia para quem não é administrador)

## Observações

Sem atividade, mostra um `WEmptyStateMolecule`.

## Story

As props de exemplo ficam em `.storybook/fixtures.ts`, e as props compartilhadas (usuário logado, marca, flash), em `parameters.inertia` da story. Formulários e navegação não saem do navegador: aparecem no painel **Actions**.
