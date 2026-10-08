# WAuditLogsPage

Tela · `pages/w-audit-logs-page/w-audit-logs-page.tsx` · story `Pages/Logs de auditoria`

## O que faz

Histórico de auditoria com filtro por ação (Select do shadcn) e por período, tabela, paginação e modal com o antes/depois de cada registro.

O Laravel chama esta tela por `Inertia::render('admin/audit-logs/index')`. O arquivo `resources/js/pages/admin/audit-logs/index.tsx` só reexporta este componente (ADR-003). Mude a tela aqui, não lá.

## Props (vindas do controller)

`logs` (`Paginated<LogRow>`), `filters` (`action`, `from`, `to`), `actionOptions` (`{ value, label }[]`)

## Observações

Só leitura: auditoria nunca se edita nem se apaga (G3). "Todas as ações" usa o valor sentinela `all`, que vira `action` vazio no request.

## Story

As props de exemplo ficam em `.storybook/fixtures.ts`, e as props compartilhadas (usuário logado, marca, flash), em `parameters.inertia` da story. Formulários e navegação não saem do navegador: aparecem no painel **Actions**.
