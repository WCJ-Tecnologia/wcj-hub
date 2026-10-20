import { Check, ChevronLeft, Loader2, Pencil, Plus } from 'lucide-react';
import { type FormEvent, useId, useState } from 'react';

import { WTagAtom } from '@/components/atoms/w-tag-atom/w-tag-atom';
import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { WTagColorPickerMolecule } from '@/components/molecules/w-tag-color-picker-molecule/w-tag-color-picker-molecule';
import { Button } from '@/components/shadcn/button';
import { Input } from '@/components/shadcn/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/shadcn/popover';
import { tagClasses } from '@/lib/tags';
import { cn } from '@/lib/utils';
import type { TagColor } from '@/types/quadro';

/** Limite do nome do tipo e da área (F2 §02). */
export const TAG_NAME_MAX = 60;

/** Tipo (`task_types` do projeto) ou área (`project_areas`): as duas etiquetas do card. */
export type TagKind = 'type' | 'area';

/** Uma opção da lista, no mesmo formato para tipo e área (o tipo vem com `label`; a página converte). */
export interface TagOption {
    id: number;
    name: string;
    color: TagColor | null;
}

export interface TagPayload {
    name: string;
    color: TagColor | null;
}

export interface WTagPopoverOrganismProps {
    kind: TagKind;
    /** As opções ativas do projeto (`options.types` ou `options.areas`, P18). */
    options: TagOption[];
    /** A etiqueta do card; null só na área (o tipo é obrigatório). */
    value: TagOption | null;
    /** Escolher outra; na área, "Nenhuma" manda null. Quem grava é a página (`PATCH …/cards/{chave}`). */
    onChange: (option: TagOption | null) => void;
    /** Com eles (gestor e Administrador, `manage_types` / `manage_areas`), a lista ganha *Novo…* e o lápis de editar. */
    onCreate?: (payload: TagPayload) => void;
    onUpdate?: (option: TagOption, payload: TagPayload) => void;
    /**
     * Com ele, o formulário de editar ganha *Desativar*: a etiqueta sai da lista de escolha e os
     * cards que já a têm continuam com ela (G7). Quem grava é a página (`POST …/deactivate`);
     * reativar fica na configuração do projeto. Os tipos de nome interno (Melhoria, Suporte,
     * Desenvolvimento) o servidor recusa.
     */
    onDeactivate?: (option: TagOption) => void;
    /** Gravando: botões desabilitados, spinner no Salvar. */
    busy?: boolean;
    /** Erros do servidor: `name`, `color`. */
    errors?: Record<string, string>;
    /** Tamanho da etiqueta-gatilho. */
    size?: 'sm' | 'default';
    className?: string;
}

const TEXT: Record<
    TagKind,
    { heading: string; set: string; change: string; create: string; edit: string; empty: string; none: string | null; srPrefix: string }
> = {
    type: {
        heading: 'Tipo',
        set: 'Definir o tipo',
        change: 'Trocar ou editar o tipo',
        create: 'Novo tipo',
        edit: 'Editar tipo',
        empty: 'Nenhum tipo no projeto',
        none: null,
        srPrefix: 'Tipo: ',
    },
    area: {
        heading: 'Área',
        set: 'Definir a área',
        change: 'Trocar ou editar a área',
        create: 'Nova área',
        edit: 'Editar área',
        empty: 'Nenhuma área no projeto',
        none: 'Nenhuma',
        srPrefix: 'Área: ',
    },
};

type View = { kind: 'list' } | { kind: 'create' } | { kind: 'edit'; option: TagOption };

/**
 * A etiqueta do card ao estilo Trello (decisão de 14/10): o tipo e a área do card abrem, no clique, a
 * lista do projeto para escolher; com permissão, o lápis edita nome e cor ali mesmo e *Novo tipo* /
 * *Nova área* cria outro. O card sem área mostra o chip "+ Área". Tipos e áreas são **do projeto**:
 * o projeto nasce com os tipos padrão (Melhoria, Correção de bug, Suporte, Mudança (GMUD),
 * Desenvolvimento) e sem área; o que se cria ou edita fica só nele.
 */
export function WTagPopoverOrganism({
    kind,
    options,
    value,
    onChange,
    onCreate,
    onUpdate,
    onDeactivate,
    busy = false,
    errors,
    size = 'default',
    className,
}: WTagPopoverOrganismProps) {
    const [open, setOpen] = useState(false);
    const [view, setView] = useState<View>({ kind: 'list' });
    const text = TEXT[kind];
    const canManage = Boolean(onCreate || onUpdate);
    const triggerLabel = value ? `${text.srPrefix}${value.name}. ${text.change}` : text.set;

    const close = () => {
        setOpen(false);
        setView({ kind: 'list' });
    };

    const choose = (option: TagOption | null) => {
        close();
        if ((option?.id ?? null) !== (value?.id ?? null)) onChange(option);
    };

    const sizeClasses = size === 'sm' ? 'px-1.5 py-px text-2xs' : 'px-2 py-0.5 text-xs';

    return (
        // modal={false}: não esconde o quadro do leitor de tela nem trava o ponteiro onde o dnd-kit escuta.
        <Popover
            open={open}
            onOpenChange={(next) => {
                if (busy && !next) return;
                setOpen(next);
                if (!next) setView({ kind: 'list' });
            }}
            modal={false}
        >
            <PopoverTrigger asChild>
                {value ? (
                    <button
                        type="button"
                        data-slot="w-tag-popover-organism"
                        data-kind={kind}
                        data-color={value.color ?? 'none'}
                        aria-label={triggerLabel}
                        title={text.change}
                        draggable={false}
                        className={cn(
                            'inline-flex max-w-full cursor-pointer items-center rounded-md border border-transparent font-medium whitespace-nowrap outline-none hover:brightness-95 focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:hover:brightness-125',
                            sizeClasses,
                            tagClasses(value.color),
                            className,
                        )}
                    >
                        <span className="truncate">{value.name}</span>
                    </button>
                ) : (
                    <button
                        type="button"
                        data-slot="w-tag-popover-organism"
                        data-kind={kind}
                        data-color="none"
                        aria-label={triggerLabel}
                        title={text.set}
                        draggable={false}
                        className={cn(
                            'inline-flex cursor-pointer items-center gap-0.5 rounded-md border border-dashed border-border font-medium whitespace-nowrap text-muted-foreground outline-none hover:bg-accent hover:text-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50',
                            sizeClasses,
                            className,
                        )}
                    >
                        <Plus className="size-3" aria-hidden /> {text.heading}
                    </button>
                )}
            </PopoverTrigger>
            <PopoverContent align="start" className="w-72 p-2" aria-label={`${text.heading} do card`}>
                {view.kind === 'list' ? (
                    <TagList
                        kind={kind}
                        options={options}
                        value={value}
                        canManage={canManage}
                        busy={busy}
                        onChoose={choose}
                        onEdit={onUpdate ? (option) => setView({ kind: 'edit', option }) : undefined}
                        onNew={onCreate ? () => setView({ kind: 'create' }) : undefined}
                    />
                ) : (
                    <TagForm
                        key={view.kind === 'edit' ? view.option.id : 'new'}
                        kind={kind}
                        option={view.kind === 'edit' ? view.option : null}
                        busy={busy}
                        errors={errors}
                        onBack={() => setView({ kind: 'list' })}
                        onSave={(payload) => {
                            if (view.kind === 'edit') onUpdate?.(view.option, payload);
                            else onCreate?.(payload);
                            close();
                        }}
                        onDeactivate={
                            view.kind === 'edit' && onDeactivate
                                ? () => {
                                      onDeactivate(view.option);
                                      close();
                                  }
                                : undefined
                        }
                    />
                )}
            </PopoverContent>
        </Popover>
    );
}

interface ListProps {
    kind: TagKind;
    options: TagOption[];
    value: TagOption | null;
    canManage: boolean;
    busy: boolean;
    onChoose: (option: TagOption | null) => void;
    onEdit?: (option: TagOption) => void;
    onNew?: () => void;
}

function TagList({ kind, options, value, canManage, busy, onChoose, onEdit, onNew }: ListProps) {
    const text = TEXT[kind];
    const row = (option: TagOption | null) => {
        const selected = (option?.id ?? null) === (value?.id ?? null);
        return (
            <li key={option?.id ?? 'none'} className="flex items-center gap-1">
                <button
                    type="button"
                    aria-pressed={selected}
                    disabled={busy}
                    onClick={() => onChoose(option)}
                    className="flex min-w-0 flex-1 items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm outline-none hover:bg-accent hover:text-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:opacity-50"
                >
                    {option ? (
                        <WTagAtom label={option.name} color={option.color} className="min-w-0" />
                    ) : (
                        <span className="text-muted-foreground">{text.none}</span>
                    )}
                    {selected && <Check className="ml-auto size-4 shrink-0 text-primary-text" aria-hidden />}
                    {selected && <span className="sr-only">(a atual)</span>}
                </button>
                {option && onEdit && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="size-7 shrink-0 text-muted-foreground"
                        aria-label={`Editar ${option.name}`}
                        disabled={busy}
                        onClick={() => onEdit(option)}
                    >
                        <Pencil aria-hidden />
                    </Button>
                )}
            </li>
        );
    };

    return (
        <div className="grid gap-1">
            <p className="px-2 py-1 font-mono text-xs font-medium tracking-wide text-muted-foreground uppercase">{text.heading}</p>
            {options.length === 0 && !canManage && <p className="px-2 py-1.5 text-sm text-muted-foreground">{text.empty}</p>}
            <ul aria-label={`${text.heading}s do projeto`} className="grid max-h-64 gap-0.5 overflow-y-auto">
                {options.map((option) => row(option))}
                {text.none && (options.length > 0 || value) && row(null)}
            </ul>
            {onNew && (
                <Button type="button" variant="ghost" size="sm" className="justify-start" disabled={busy} onClick={onNew}>
                    <Plus aria-hidden /> {text.create}
                </Button>
            )}
        </div>
    );
}

interface FormProps {
    kind: TagKind;
    option: TagOption | null;
    busy: boolean;
    errors?: Record<string, string>;
    onBack: () => void;
    onSave: (payload: TagPayload) => void;
    /** Só ao editar, com permissão: tira a etiqueta da lista de escolha. */
    onDeactivate?: () => void;
}

function TagForm({ kind, option, busy, errors, onBack, onSave, onDeactivate }: FormProps) {
    const id = useId();
    const text = TEXT[kind];
    const [name, setName] = useState(option?.name ?? '');
    const [color, setColor] = useState<TagColor | null>(option?.color ?? null);
    const [tried, setTried] = useState(false);
    const error = errors?.name ?? (tried && name.trim() === '' ? 'Informe o nome.' : undefined);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setTried(true);
        if (name.trim() === '' || busy) return;
        onSave({ name: name.trim(), color });
    };

    return (
        <form onSubmit={submit} noValidate className="grid gap-3 p-1">
            <div className="flex items-center gap-1">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="size-7"
                    aria-label="Voltar à lista"
                    onClick={onBack}
                    disabled={busy}
                >
                    <ChevronLeft aria-hidden />
                </Button>
                <p className="font-mono text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    {option ? text.edit : text.create}
                </p>
            </div>
            <WFormFieldMolecule label="Nome" htmlFor={`${id}-name`} required error={error}>
                <Input value={name} maxLength={TAG_NAME_MAX} autoFocus disabled={busy} onChange={(event) => setName(event.target.value)} />
            </WFormFieldMolecule>
            <WFormFieldMolecule label="Cor da etiqueta" htmlFor={`${id}-color`} error={errors?.color}>
                <WTagColorPickerMolecule id={`${id}-color`} value={color} onChange={setColor} disabled={busy} />
            </WFormFieldMolecule>
            <div className="flex items-center gap-2">
                <WTagAtom label={name.trim() || 'Prévia'} color={color} />
                {onDeactivate && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="ml-auto text-destructive hover:text-destructive"
                        disabled={busy}
                        onClick={onDeactivate}
                        title="Sai da lista de escolha; os cards que já têm a etiqueta continuam com ela"
                    >
                        Desativar
                    </Button>
                )}
                <Button type="submit" size="sm" className={onDeactivate ? undefined : 'ml-auto'} disabled={busy}>
                    {busy && <Loader2 className="size-4 animate-spin" aria-hidden />}
                    Salvar
                </Button>
            </div>
        </form>
    );
}
