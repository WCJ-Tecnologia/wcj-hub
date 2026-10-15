# WProjectSettingsPage

Página · `pages/w-project-settings-page/w-project-settings-page.tsx` · Inertia `projects/settings` · story `Pages/Configuração do projeto`

## O que faz

A tela `/projetos/{id}/configuracao` (F2 §01 a §05; escopo B2): cabeçalho com prefixo, cliente, gestor, situação e o link para o quadro, e as abas **Pessoas**, **Etapas**, **Áreas** e **Padrões**. A aba fica na URL (`?aba=pessoas|etapas|areas|padroes`). Quem gere (Administrador, responsável ou gestor alocado) vê as quatro e escreve; o dev alocado vê só Pessoas e Etapas, só leitura (F2 §09).

## Props

As do `ProjectSettingsController::show`, tipadas em `types/configuracao.ts`: `project`, `members`, `stages`, `areas` (null para quem não gere), `options` (null para quem não gere) e `filters`.

## Cuidados

Cada aba é um organismo (`w-project-members-tab-organism`, `w-board-stages-tab-organism`, `w-project-areas-tab-organism`, `w-project-defaults-tab-organism`) que faz as próprias escritas; a página só junta.
