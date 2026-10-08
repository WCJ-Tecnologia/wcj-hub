import { CalendarIcon, X } from 'lucide-react';
import { useState } from 'react';
import { ptBR } from 'react-day-picker/locale';

import { Button } from '@/components/shadcn/button';
import { Calendar } from '@/components/shadcn/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/shadcn/popover';
import { cn } from '@/lib/utils';

export interface WDatePickerMoleculeProps {
    /** Data de negócio em `AAAA-MM-DD` (o formato do `<input type="date">` e do servidor); `null` ou `''` = vazio. */
    value: string | null | undefined;
    /** Recebe `AAAA-MM-DD`, ou `null` ao limpar. */
    onChange: (value: string | null) => void;
    /** Texto com o campo vazio. */
    placeholder?: string;
    /** aria-label do botão (use quando não houver <label> ligado por `id`). */
    label?: string;
    id?: string;
    disabled?: boolean;
    /** Mostra o botão de limpar quando há data. */
    clearable?: boolean;
    className?: string;
}

const DISPLAY = new Intl.DateTimeFormat('pt-BR');

/** `AAAA-MM-DD` → Date local (meia-noite no fuso do navegador, sem conversão de UTC). */
function parse(value: string | null | undefined): Date | undefined {
    const match = value ? /^(\d{4})-(\d{2})-(\d{2})$/.exec(value) : null;
    return match ? new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3])) : undefined;
}

/** Date local → `AAAA-MM-DD`. */
function serialize(date: Date): string {
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

/**
 * Date picker do shadcn (Popover + Calendar) em português, que lê e devolve
 * `AAAA-MM-DD`: troca o `<input type="date">` sem mudar o que vai ao servidor.
 */
export function WDatePickerMolecule({
    value,
    onChange,
    placeholder = 'dd/mm/aaaa',
    label,
    id,
    disabled,
    clearable = true,
    className,
}: WDatePickerMoleculeProps) {
    const [open, setOpen] = useState(false);
    const selected = parse(value);

    return (
        <div data-slot="w-date-picker-molecule" className={cn('relative', className)}>
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        id={id}
                        type="button"
                        variant="outline"
                        disabled={disabled}
                        aria-label={label ? `${label}: ${selected ? DISPLAY.format(selected) : 'sem data'}` : undefined}
                        className={cn(
                            'h-10 w-full justify-start gap-2 bg-card px-3 text-base font-normal md:text-sm',
                            clearable && selected && 'pr-10',
                            !selected && 'text-muted-foreground',
                        )}
                    >
                        <CalendarIcon className="text-muted-foreground" aria-hidden />
                        {selected ? DISPLAY.format(selected) : placeholder}
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-auto p-0" align="start">
                    <Calendar
                        mode="single"
                        locale={ptBR}
                        selected={selected}
                        defaultMonth={selected}
                        onSelect={(date) => {
                            onChange(date ? serialize(date) : null);
                            setOpen(false);
                        }}
                        autoFocus
                    />
                </PopoverContent>
            </Popover>

            {clearable && selected && !disabled && (
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={() => onChange(null)}
                    aria-label={label ? `Limpar ${label}` : 'Limpar data'}
                    className="absolute top-1/2 right-1.5 size-7 -translate-y-1/2 text-muted-foreground"
                >
                    <X aria-hidden />
                </Button>
            )}
        </div>
    );
}
