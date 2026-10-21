# WTaskHistoryPage

Página · `pages/w-task-history-page/w-task-history-page.tsx` · story `Pages/Histórico do card` · Inertia `projects/task-history` · `/projetos/{id}/cards/{chave}/historico`

## O que faz

A linha do tempo do card (F7 §01 a §04): o `WTaskHistorySummaryOrganism` no topo e o `WTaskTimelineOrganism` embaixo. Abre pelo botão _Histórico_ da ficha (`history_url`); _Voltar à ficha_ e _Quadro_ nas ações; _Ver na auditoria_ só para o Administrador (`audit_url`), com o que a linha do tempo não mostra. Vê quem vê o card (F7 §09). Só leitura.

## Props

As de `TaskHistoryPageData` (`types/indicadores.ts`): `project`, `task`, `summary`, `movements`, `audit_url`.
