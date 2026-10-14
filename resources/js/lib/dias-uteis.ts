/**
 * Dias úteis de segunda a sexta, sem feriado (P29), sobre datas de negócio `AAAA-MM-DD`.
 *
 * Tudo em UTC (`Date.UTC` e `getUTC*`): a data nunca passa pelo fuso do navegador, que
 * leria meia-noite UTC e poderia cair no dia anterior (o mesmo cuidado do `dateBr`).
 */

const DAY_MS = 24 * 60 * 60 * 1000;

/** `AAAA-MM-DD` → milissegundos da meia-noite UTC; null se não for uma data válida. */
function toUtc(iso: string): number | null {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso);
    if (!match) return null;
    const [year, month, day] = [Number(match[1]), Number(match[2]), Number(match[3])];
    const ms = Date.UTC(year, month - 1, day);
    const back = new Date(ms);
    // 2026-02-30 vira 02/03 no Date.UTC: aqui é inválida.
    return back.getUTCFullYear() === year && back.getUTCMonth() === month - 1 && back.getUTCDate() === day ? ms : null;
}

/** Milissegundos UTC → `AAAA-MM-DD`. */
function fromUtc(ms: number): string {
    const date = new Date(ms);
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${date.getUTCFullYear()}-${pad(date.getUTCMonth() + 1)}-${pad(date.getUTCDate())}`;
}

/** Segunda a sexta. */
function isBusinessDay(ms: number): boolean {
    const weekday = new Date(ms).getUTCDay();
    return weekday !== 0 && weekday !== 6;
}

/**
 * O último dos `days` dias úteis contando a partir de `startIso`, inclusive (P46, F5 §02).
 * Início num dia útil conta como o 1º; início no sábado ou no domingo começa a contar na
 * segunda. Ex.: terça 2026-10-06 com 10 dias → segunda 2026-10-19.
 *
 * `days` menor que 1 devolve o próprio início; data inválida devolve `startIso` sem mexer.
 */
export function addBusinessDays(startIso: string, days: number): string {
    const start = toUtc(startIso);
    if (start === null || days < 1) return startIso;
    let current = start;
    let counted = isBusinessDay(current) ? 1 : 0;
    while (counted < days) {
        current += DAY_MS;
        if (isBusinessDay(current)) counted++;
    }
    return fromUtc(current);
}

/**
 * Quantos dias de segunda a sexta há entre `startIso` e `endIso`, os dois inclusive (F5 §02,
 * multiplicador da capacidade, P48). Fim antes do início ou data inválida: 0.
 */
export function businessDaysBetween(startIso: string, endIso: string): number {
    const start = toUtc(startIso);
    const end = toUtc(endIso);
    if (start === null || end === null || end < start) return 0;
    const total = Math.round((end - start) / DAY_MS) + 1;
    const fullWeeks = Math.floor(total / 7);
    let count = fullWeeks * 5;
    for (let ms = start + fullWeeks * 7 * DAY_MS; ms <= end; ms += DAY_MS) {
        if (isBusinessDay(ms)) count++;
    }
    return count;
}
