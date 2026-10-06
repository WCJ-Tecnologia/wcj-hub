/**
 * Input masks are display-only: form state always holds the raw value the
 * backend expects — digits for documents/phones, a decimal string for money.
 */

export function onlyDigits(value: string): string {
    return value.replace(/\D/g, '');
}

/** 000.000.000-00 */
export function maskCpf(value: string): string {
    const digits = onlyDigits(value).slice(0, 11);

    let out = digits.slice(0, 3);
    if (digits.length > 3) out += '.' + digits.slice(3, 6);
    if (digits.length > 6) out += '.' + digits.slice(6, 9);
    if (digits.length > 9) out += '-' + digits.slice(9, 11);

    return out;
}

/** 00.000.000/0000-00 */
export function maskCnpj(value: string): string {
    const digits = onlyDigits(value).slice(0, 14);

    let out = digits.slice(0, 2);
    if (digits.length > 2) out += '.' + digits.slice(2, 5);
    if (digits.length > 5) out += '.' + digits.slice(5, 8);
    if (digits.length > 8) out += '/' + digits.slice(8, 12);
    if (digits.length > 12) out += '-' + digits.slice(12, 14);

    return out;
}

/** Picks CPF or CNPJ by length — useful where the user may be PF or PJ. */
export function maskDocument(value: string): string {
    const digits = onlyDigits(value);

    return digits.length > 11 ? maskCnpj(digits) : maskCpf(digits);
}

/** (00) 0000-0000 for landlines, (00) 00000-0000 for mobiles */
export function maskPhone(value: string): string {
    const digits = onlyDigits(value).slice(0, 11);

    if (digits.length === 0) return '';

    let out = '(' + digits.slice(0, 2);
    if (digits.length < 3) return out;

    out += ') ';

    if (digits.length <= 10) {
        out += digits.slice(2, 6);
        if (digits.length > 6) out += '-' + digits.slice(6, 10);
    } else {
        out += digits.slice(2, 7) + '-' + digits.slice(7, 11);
    }

    return out;
}

const MONEY_FORMAT = new Intl.NumberFormat('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

/** "1234.56" -> "1.234,56" */
export function moneyDisplay(raw: string | number | null | undefined): string {
    if (raw === null || raw === undefined || raw === '') return '';

    const value = typeof raw === 'number' ? raw : Number(raw);

    return Number.isNaN(value) ? '' : MONEY_FORMAT.format(value);
}

/**
 * Cents-first typing, the pattern Brazilian users expect: digits fill from the
 * right, so "12345" becomes 123,45. Returns the decimal string for the backend.
 */
export function moneyFromTyped(text: string): string {
    const digits = onlyDigits(text).slice(0, 12);

    if (digits === '') return '';

    return (Number(digits) / 100).toFixed(2);
}

export function formatDocument(value: string | null | undefined): string {
    return value ? maskDocument(value) : '';
}

export function formatPhone(value: string | null | undefined): string {
    return value ? maskPhone(value) : '';
}

export function isValidCpf(value: string): boolean {
    const cpf = onlyDigits(value);

    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;

    for (const position of [9, 10]) {
        let sum = 0;
        for (let i = 0; i < position; i++) {
            sum += Number(cpf[i]) * (position + 1 - i);
        }

        if (Number(cpf[position]) !== ((10 * sum) % 11) % 10) return false;
    }

    return true;
}

export function isValidCnpj(value: string): boolean {
    const cnpj = onlyDigits(value);

    if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) return false;

    const weightSets = [
        [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
        [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
    ];

    for (const [index, weights] of weightSets.entries()) {
        const position = 12 + index;
        const sum = weights.reduce((total, weight, i) => total + Number(cnpj[i]) * weight, 0);
        const remainder = sum % 11;
        const digit = remainder < 2 ? 0 : 11 - remainder;

        if (Number(cnpj[position]) !== digit) return false;
    }

    return true;
}
