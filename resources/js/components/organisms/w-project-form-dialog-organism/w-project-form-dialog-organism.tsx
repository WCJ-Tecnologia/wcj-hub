import type { InertiaFormProps } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import type { FormEvent } from 'react';

import { WDatePickerMolecule } from '@/components/molecules/w-date-picker-molecule/w-date-picker-molecule';
import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Button } from '@/components/shadcn/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/shadcn/dialog';
import { Input } from '@/components/shadcn/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/shadcn/select';

export type ProjectType = 'delivery' | 'continuous';

export interface ProjectFormData {
    client_id: number | '';
    manager_id: number | '';
    key_prefix: string;
    name: string;
    /** Não é gravado à parte: é a entrega preenchida ou vazia (P2). */
    type: ProjectType;
    starts_on: string;
    due_on: string;
}

export interface NamedOption {
    id: number;
    name: string;
}

export interface WProjectFormDialogOrganismProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** O `useForm<ProjectFormData>` da tela. */
    form: InertiaFormProps<ProjectFormData>;
    editing: boolean;
    /** Na edição: o cliente do projeto, que não muda (P1). */
    clientName?: string;
    /** Depois da primeira tarefa o prefixo fica só leitura (P3). */
    prefixLocked: boolean;
    clients: NamedOption[];
    managers: NamedOption[];
    onSubmit: (event: FormEvent) => void;
    /** "Novo cliente" ao lado do Cliente, só na criação (N6, F2 §02): a tela abre o cadastro sem fechar este. */
    onNewClient?: () => void;
}

/** O Radix Select não aceita item com valor vazio; o Root aceita `''` e mostra o placeholder. */
const selectValue = (value: number | '') => (value === '' ? '' : String(value));

/**
 * Modal de criar/editar projeto (F2 §02). O `useForm` vive na tela. Na edição, o cliente
 * aparece só leitura (P1) e o prefixo também, depois da primeira tarefa (P3).
 */
export function WProjectFormDialogOrganism({
    open,
    onOpenChange,
    form,
    editing,
    clientName,
    prefixLocked,
    clients,
    managers,
    onSubmit,
    onNewClient,
}: WProjectFormDialogOrganismProps) {
    const setType = (type: ProjectType) => {
        form.setData((data) => ({ ...data, type, due_on: type === 'continuous' ? '' : data.due_on }));
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent data-slot="w-project-form-dialog-organism">
                <DialogHeader>
                    <DialogTitle>{editing ? 'Editar projeto' : 'Novo projeto'}</DialogTitle>
                    <DialogDescription>
                        {editing
                            ? 'As mudanças valem dali para a frente e não apagam o que já foi registrado.'
                            : 'O projeto nasce aberto, com o quadro padrão de 8 etapas e sem ninguém alocado.'}
                    </DialogDescription>
                </DialogHeader>
                <DialogBody>
                    <form id="project-form" onSubmit={onSubmit} className="space-y-4">
                        {/* O botão fica fora do campo: dentro, o WFormFieldMolecule daria ao invólucro o id do Select (dois #client_id). */}
                        <div className="flex items-start gap-2">
                            <WFormFieldMolecule
                                className="min-w-0 flex-1"
                                label="Cliente"
                                htmlFor="client_id"
                                required={!editing}
                                error={form.errors.client_id}
                                hint={editing ? 'O cliente é definido na criação e não muda.' : undefined}
                            >
                                {editing ? (
                                    <Input value={clientName ?? ''} readOnly disabled />
                                ) : (
                                    <Select
                                        value={selectValue(form.data.client_id)}
                                        onValueChange={(value) => form.setData('client_id', value === '' ? '' : Number(value))}
                                    >
                                        {/* O Root do Radix não é elemento: id e aria vão no trigger, que é o botão focável. */}
                                        <SelectTrigger
                                            id="client_id"
                                            aria-invalid={Boolean(form.errors.client_id)}
                                            aria-describedby={form.errors.client_id ? 'client_id-error' : undefined}
                                        >
                                            <SelectValue placeholder="Escolha o cliente" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {clients.map((client) => (
                                                <SelectItem key={client.id} value={String(client.id)}>
                                                    {client.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
                            </WFormFieldMolecule>
                            {!editing && onNewClient && (
                                <Button type="button" variant="outline" onClick={onNewClient} className="mt-[1.625rem] shrink-0">
                                    <Plus className="size-4" aria-hidden /> Novo cliente
                                </Button>
                            )}
                        </div>

                        <WFormFieldMolecule label="Gestor responsável" htmlFor="manager_id" required error={form.errors.manager_id}>
                            <Select
                                value={selectValue(form.data.manager_id)}
                                onValueChange={(value) => form.setData('manager_id', value === '' ? '' : Number(value))}
                            >
                                <SelectTrigger
                                    id="manager_id"
                                    aria-invalid={Boolean(form.errors.manager_id)}
                                    aria-describedby={form.errors.manager_id ? 'manager_id-error' : undefined}
                                >
                                    <SelectValue placeholder="Escolha o gestor" />
                                </SelectTrigger>
                                <SelectContent>
                                    {managers.map((manager) => (
                                        <SelectItem key={manager.id} value={String(manager.id)}>
                                            {manager.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </WFormFieldMolecule>

                        <div className="grid gap-4 sm:grid-cols-[10rem_1fr]">
                            <WFormFieldMolecule
                                label="Prefixo"
                                htmlFor="key_prefix"
                                required
                                error={form.errors.key_prefix}
                                hint={prefixLocked ? 'Não muda depois da primeira tarefa.' : 'Ex.: SGM, que vira SGM-17.'}
                            >
                                <Input
                                    value={form.data.key_prefix}
                                    maxLength={10}
                                    readOnly={prefixLocked}
                                    disabled={prefixLocked}
                                    className="font-mono uppercase"
                                    onChange={(e) => form.setData('key_prefix', e.target.value.toUpperCase())}
                                />
                            </WFormFieldMolecule>
                            <WFormFieldMolecule label="Nome" htmlFor="project-name" required error={form.errors.name}>
                                <Input value={form.data.name} maxLength={160} onChange={(e) => form.setData('name', e.target.value)} />
                            </WFormFieldMolecule>
                        </div>

                        <WFormFieldMolecule label="Tipo" htmlFor="type" required error={form.errors.type}>
                            <Select value={form.data.type} onValueChange={(value) => setType(value as ProjectType)}>
                                <SelectTrigger id="type">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="delivery">Projeto de entrega</SelectItem>
                                    <SelectItem value="continuous">Produto contínuo</SelectItem>
                                </SelectContent>
                            </Select>
                        </WFormFieldMolecule>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <WFormFieldMolecule label="Início" htmlFor="starts_on" required error={form.errors.starts_on}>
                                <WDatePickerMolecule
                                    value={form.data.starts_on || null}
                                    clearable={false}
                                    onChange={(value) => form.setData('starts_on', value ?? '')}
                                />
                            </WFormFieldMolecule>
                            {form.data.type === 'delivery' && (
                                <WFormFieldMolecule
                                    label="Entrega"
                                    htmlFor="due_on"
                                    required
                                    error={form.errors.due_on}
                                    hint="Só informativa: nenhuma conta usa esta data."
                                >
                                    <WDatePickerMolecule
                                        value={form.data.due_on || null}
                                        onChange={(value) => form.setData('due_on', value ?? '')}
                                    />
                                </WFormFieldMolecule>
                            )}
                        </div>
                    </form>
                </DialogBody>
                <DialogFooter>
                    <Button variant="outline" onClick={() => onOpenChange(false)} disabled={form.processing}>
                        Cancelar
                    </Button>
                    <Button type="submit" form="project-form" disabled={form.processing}>
                        {editing ? 'Salvar alterações' : 'Criar projeto'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
