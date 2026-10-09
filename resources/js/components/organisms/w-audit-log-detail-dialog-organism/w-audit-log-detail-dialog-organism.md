# WAuditLogDetailDialogOrganism

Organismo · `organisms/w-audit-log-detail-dialog-organism/w-audit-log-detail-dialog-organism.tsx` · sem story (aparece em `Pages/Auditoria`)

## O que faz

O detalhe da linha (Auditoria AC 4): cada campo alterado com o antes e o depois, numa tabela com `scope="row"` no nome do campo; a tentativa negada ganha o aviso "nada foi alterado". Rodapé com e-mail e IP.

## Props

| Prop | Tipo | Para quê |
| --- | --- | --- |
| `log` | `AuditLogRow \| null` | a linha aberta; nulo fecha |
| `onClose` | `() => void` | |

## Exemplo

Ver `pages/w-audit-logs-page/w-audit-logs-page.tsx`.

## Cuidados

Os valores já vêm na linguagem da tela (rótulo e texto) do `AuditLogPresenter`.
