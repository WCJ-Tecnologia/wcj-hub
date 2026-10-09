# WAuditLogFiltersOrganism

Organismo · `organisms/w-audit-log-filters-organism/w-audit-log-filters-organism.tsx` · sem story (aparece em `Pages/Auditoria`)

## O que faz

Os filtros da auditoria (F1 §05): busca pelo registro, **De** e **Até** (`WDatePickerMolecule`), e os selects Quem, Canal, Ação, Tipo de registro e Resultado (Select do shadcn, sentinela `all` = sem filtro). Exporta `AuditFilters`, `AuditOptions`, `AUDIT_URL` e `filterParams()`.

"De" e "Até" têm estado local (CR-1-07 e CR-2-10 do B1): a data escolhida vale na hora, e os selects e a busca levam o período **dos campos**, não o do servidor; trocar um select logo depois de escolher a data, com a visita da data ainda em voo, sai com o período novo. Quando o servidor responde com outro período (Limpar filtros, Histórico de uma ficha), os campos acompanham.

## Props

| Prop | Tipo | Para quê |
| --- | --- | --- |
| `filters` | `AuditFilters` | como o controller devolve |
| `options` | `AuditOptions` | as listas dos selects |

## Exemplo

Ver `pages/w-audit-logs-page/w-audit-logs-page.tsx`.

## Cuidados

`from` vai sempre na query, vazio quando apagado: sem ele o servidor volta ao padrão de 7 dias. Trocar o tipo apaga o `id` (sai do Histórico de uma ficha).
