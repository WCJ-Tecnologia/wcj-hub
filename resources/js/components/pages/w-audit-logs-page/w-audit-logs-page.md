# WAuditLogsPage

Tela · `pages/w-audit-logs-page/w-audit-logs-page.tsx` · story `Pages/Auditoria`

## O que faz

Administração › Auditoria (G3; F1 §03, §05): quem alterou o quê, quando e por onde, e toda tentativa negada. Filtros no `WAuditLogFiltersOrganism` (busca, De/Até com date picker, Quem, Canal, Ação, Tipo de registro, Resultado), lista no `WAuditLogTableOrganism` (linha negada em destaque), paginação e o detalhe no `WAuditLogDetailDialogOrganism`. Quando a tela veio do Histórico de uma ficha, o aviso "Histórico de …" aparece com "Ver toda a auditoria". Só ver e filtrar: não existe editar, apagar nem exportar.

O Laravel chama esta tela por `Inertia::render('admin/audit-logs/index')`. O arquivo `resources/js/pages/admin/audit-logs/index.tsx` só reexporta este componente (ADR-003).

## Props (vindas do controller)

`logs` (`Paginated<AuditLogRow>`), `filters` (`AuditFilters`), `options` (`AuditOptions`), `record` (`string | null`)

## Observações

Estados de lista pelo `WListBodyMolecule`. "Limpar filtros" no estado vazio volta ao padrão de 7 dias.

## Story

As props de exemplo ficam em `.storybook/fixtures.ts` (`LOGS`, `AUDIT_FILTERS`, `AUDIT_OPTIONS`).
