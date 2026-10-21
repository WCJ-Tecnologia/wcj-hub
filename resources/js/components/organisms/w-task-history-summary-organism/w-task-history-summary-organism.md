# WTaskHistorySummaryOrganism

Organismo · `organisms/w-task-history-summary-organism/w-task-history-summary-organism.tsx` · story `Organisms/Indicadores/Resumo do histórico`

## O que faz

O resumo no topo da linha do tempo (F7 §02): etapa atual e desde quando; data prevista ou "Sem data prevista: falta …"; atraso descontada a pausa ou "No prazo" (não aparece em card pronto nem arquivado, F7 §10.6); concluída em (só no pronto); horas previstas e lançadas; tempo em desenvolvimento estimado pelo quadro (R17), com os dias úteis; tempo por papel de etapa (P11); bug de produção de / bugs de produção ligados (P25); arquivado, com data, quem e motivo (P26).

## Props

| Prop      | Tipo             | Obrigatória | Para quê                                   |
| --------- | ---------------- | ----------- | ------------------------------------------ |
| `summary` | `HistorySummary` | sim         | o resumo, como o `TaskHistoryScreen` manda |
