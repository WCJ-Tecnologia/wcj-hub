# WAuditLogTableOrganism

Organismo · `organisms/w-audit-log-table-organism/w-audit-log-table-organism.tsx` · sem story (aparece em `Pages/Auditoria`)

## O que faz

A lista da auditoria com as colunas do F1 §03 (Data e hora, Quem, Canal, Ação, Registro, Resultado, Ver detalhes). A tentativa negada fica em destaque pela linha inteira (`rowClassName`) e pelo `WStatusPillAtom kind="audit"`. No celular, o card inteiro é o botão do detalhe. Exporta `AuditLogRow` e `AuditChange`, como o `AuditLogPresenter` manda.

## Props

| Prop | Tipo | Para quê |
| --- | --- | --- |
| `rows` | `AuditLogRow[]` | `logs.data` |
| `onOpen` | `(row) => void` | abre o `WAuditLogDetailDialogOrganism` |
| `emptyState` | `ReactNode` | estado vazio |

## Exemplo

Ver `pages/w-audit-logs-page/w-audit-logs-page.tsx`.

## Cuidados

Só ver e filtrar: não existe editar, apagar nem exportar (G3).
