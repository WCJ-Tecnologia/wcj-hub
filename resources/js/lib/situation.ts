/** "em DD/MM/AAAA por Fulano" (F1 §02, Situação), o mesmo nos três cadastros. */
export const deactivation = (row: { deactivated_at: string | null; deactivated_by: string | null }) =>
    `em ${row.deactivated_at?.slice(0, 10) ?? '—'}${row.deactivated_by ? ` por ${row.deactivated_by}` : ''}`;

/**
 * A situação do registro no formulário de edição, a mesma nos três cadastros: "Anonimizado" (só o
 * contato tem), "Ativo" ou "Desativado em DD/MM/AAAA por Fulano".
 */
export const situation = (row: { active: boolean; anonymized?: boolean; deactivated_at: string | null; deactivated_by: string | null }) =>
    row.anonymized ? 'Anonimizado' : row.active ? 'Ativo' : `Desativado ${deactivation(row)}`;
