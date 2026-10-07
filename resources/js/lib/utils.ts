import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function initials(name: string) {
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase() ?? '')
        .join('');
}

const BRL = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

/**
 * O único formatador de dinheiro. Nunca abrevia, nunca perde centavo, sinal
 * antes do símbolo: money(-1234.5) → "-R$ 1.234,50". Valor inválido vira
 * "R$ 0,00" para não vazar "NaN" em tela.
 */
export function money(value: number | string | null | undefined): string {
    if (value === null || value === undefined || value === '') return BRL.format(0);
    const number = typeof value === 'number' ? value : Number(value);
    return BRL.format(Number.isFinite(number) ? number : 0);
}

export function percent(value: number): string {
    return `${value.toLocaleString('pt-BR', { maximumFractionDigits: 1 })}%`;
}

/**
 * Data de negócio ("2026-10-07", coluna date) para a tela: "07/10/2026". Sem passar por Date,
 * que leria a data como meia-noite UTC e mostraria o dia anterior no fuso de Brasília.
 */
export function dateBr(value: string | null | undefined): string {
    if (!value) return '—';
    const [year, month, day] = value.split('-');
    return `${day}/${month}/${year}`;
}
