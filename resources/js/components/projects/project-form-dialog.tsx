import type { InertiaFormProps } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { FormField } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';

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

/**
 * Modal de criar/editar projeto (F2 §02). O `useForm` vive na página. Na edição, o cliente
 * aparece só leitura (P1) e o prefixo também, depois da primeira tarefa (P3).
 */
export function ProjectFormDialog({
    open,
    onOpenChange,
    form,
    editing,
    clientName,
    prefixLocked,
    clients,
    managers,
    onSubmit,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    form: InertiaFormProps<ProjectFormData>;
    editing: boolean;
    /** Na edição: o cliente do projeto, que não muda. */
    clientName?: string;
    prefixLocked: boolean;
    clients: NamedOption[];
    managers: NamedOption[];
    onSubmit: (event: FormEvent) => void;
}) {
    const setType = (type: ProjectType) => {
        form.setData((data) => ({ ...data, type, due_on: type === 'continuous' ? '' : data.due_on }));
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
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
                        <FormField
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
                                    value={form.data.client_id}
                                    onChange={(e) => form.setData('client_id', e.target.value === '' ? '' : Number(e.target.value))}
                                >
                                    <option value="">Escolha o cliente</option>
                                    {clients.map((client) => (
                                        <option key={client.id} value={client.id}>
                                            {client.name}
                                        </option>
                                    ))}
                                </Select>
                            )}
                        </FormField>

                        <FormField label="Gestor responsável" htmlFor="manager_id" required error={form.errors.manager_id}>
                            <Select
                                value={form.data.manager_id}
                                onChange={(e) => form.setData('manager_id', e.target.value === '' ? '' : Number(e.target.value))}
                            >
                                <option value="">Escolha o gestor</option>
                                {managers.map((manager) => (
                                    <option key={manager.id} value={manager.id}>
                                        {manager.name}
                                    </option>
                                ))}
                            </Select>
                        </FormField>

                        <div className="grid gap-4 sm:grid-cols-[10rem_1fr]">
                            <FormField
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
                            </FormField>
                            <FormField label="Nome" htmlFor="name" required error={form.errors.name}>
                                <Input value={form.data.name} maxLength={160} onChange={(e) => form.setData('name', e.target.value)} />
                            </FormField>
                        </div>

                        <FormField label="Tipo" htmlFor="type" required error={form.errors.type}>
                            <Select value={form.data.type} onChange={(e) => setType(e.target.value as ProjectType)}>
                                <option value="delivery">Projeto de entrega</option>
                                <option value="continuous">Produto contínuo</option>
                            </Select>
                        </FormField>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <FormField label="Início" htmlFor="starts_on" required error={form.errors.starts_on}>
                                <Input
                                    type="date"
                                    value={form.data.starts_on}
                                    onChange={(e) => form.setData('starts_on', e.target.value)}
                                />
                            </FormField>
                            {form.data.type === 'delivery' && (
                                <FormField
                                    label="Entrega"
                                    htmlFor="due_on"
                                    required
                                    error={form.errors.due_on}
                                    hint="Só informativa: nenhuma conta usa esta data."
                                >
                                    <Input
                                        type="date"
                                        min={form.data.starts_on || undefined}
                                        value={form.data.due_on}
                                        onChange={(e) => form.setData('due_on', e.target.value)}
                                    />
                                </FormField>
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
