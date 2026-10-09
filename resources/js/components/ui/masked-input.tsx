import * as React from 'react';

import { Input } from '@/components/ui/input';
import {
    cnpjFromTyped,
    maskAlphanumericCnpj,
    maskCnpj,
    maskCpf,
    maskDocument,
    maskPhone,
    moneyDisplay,
    moneyFromTyped,
    onlyDigits,
} from '@/lib/masks';
import { cn } from '@/lib/utils';

type BaseProps = Omit<React.ComponentProps<'input'>, 'value' | 'onChange'>;

interface MaskedProps extends BaseProps {
    /** Raw value: digits for documents/phones, decimal string for money. */
    value: string;
    onChange: (value: string) => void;
}

/**
 * Currency field. Displays "1.234,56" while the form keeps "1234.56".
 */
export function MoneyInput({ value, onChange, className, ...props }: MaskedProps) {
    return (
        <div className="relative">
            <span className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm font-medium text-muted-foreground">
                R$
            </span>
            <Input
                inputMode="decimal"
                placeholder="0,00"
                {...props}
                value={moneyDisplay(value)}
                onChange={(event) => onChange(moneyFromTyped(event.target.value))}
                className={cn('pl-10 text-right tabular-nums', className)}
            />
        </div>
    );
}

/**
 * CPF, CNPJ, or whichever fits the typed length.
 */
export function DocumentInput({
    value,
    onChange,
    variant = 'auto',
    className,
    ...props
}: MaskedProps & { variant?: 'cpf' | 'cnpj' | 'auto' }) {
    const limit = variant === 'cpf' ? 11 : 14;

    const mask = variant === 'cpf' ? maskCpf : variant === 'cnpj' ? maskCnpj : maskDocument;

    const placeholder = variant === 'cpf' ? '000.000.000-00' : variant === 'cnpj' ? '00.000.000/0000-00' : 'CPF ou CNPJ';

    return (
        <Input
            inputMode="numeric"
            placeholder={placeholder}
            {...props}
            value={mask(value)}
            onChange={(event) => onChange(onlyDigits(event.target.value).slice(0, limit))}
            className={cn('tabular-nums', className)}
        />
    );
}

/**
 * CNPJ alfanumérico (N5): aceita letras, mostra a máscara e guarda o valor cru em maiúsculas.
 */
export function AlphanumericCnpjInput({ value, onChange, className, ...props }: MaskedProps) {
    return (
        <Input
            autoCapitalize="characters"
            autoComplete="off"
            spellCheck={false}
            placeholder="00.000.000/0000-00"
            {...props}
            value={maskAlphanumericCnpj(value)}
            onChange={(event) => onChange(cnpjFromTyped(event.target.value))}
            className={cn('tabular-nums', className)}
        />
    );
}

export function PhoneInput({ value, onChange, className, ...props }: MaskedProps) {
    return (
        <Input
            inputMode="tel"
            placeholder="(00) 00000-0000"
            {...props}
            value={maskPhone(value)}
            onChange={(event) => onChange(onlyDigits(event.target.value).slice(0, 11))}
            className={cn('tabular-nums', className)}
        />
    );
}
