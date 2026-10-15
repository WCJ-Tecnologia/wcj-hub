import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { fn, userEvent, within } from 'storybook/test';

import type { AcceptanceCriterion } from '@/types/quadro';

import { CRITERIA } from '../../../../../.storybook/fixtures-ficha';
import { criteriaSummary, WTaskCriteriaSectionOrganism } from './w-task-criteria-section-organism';

interface DemoProps {
    criteria: AcceptanceCriterion[];
    canEdit: boolean;
    busy: boolean;
    errors?: Record<string, string>;
    onAdd: (payload: { description: string; parent_id: number | null }) => void;
    onUpdate: (id: number, description: string) => void;
    onDelete: (id: number) => void;
}

/** O maior `m` entre os números (`CA_4` → 4, `CA_2.3` → 3): o número não volta (T9). */
const lastNumber = (numbers: string[]) => numbers.reduce((max, number) => Math.max(max, Number(number.split(/[_.]/).pop())), 0);

/**
 * Faz o papel da página: grava na lista local depois de 600 ms com `busy` ligado, como o servidor faria
 * (critério novo no fim, subitem no fim do pai, editar volta para A testar, excluir não renumera).
 */
function CriteriaSectionDemo({ criteria: initial, canEdit, busy, errors, onAdd, onUpdate, onDelete }: DemoProps) {
    const [criteria, setCriteria] = useState(initial);
    const [saving, setSaving] = useState(false);
    const [nextId, setNextId] = useState(100);

    const later = (apply: (list: AcceptanceCriterion[]) => AcceptanceCriterion[]) => {
        setSaving(true);
        setTimeout(() => {
            setCriteria(apply);
            setSaving(false);
        }, 600);
    };

    return (
        <div className="space-y-3">
            <p className="font-mono text-xs text-muted-foreground">Resumo: {criteriaSummary(criteria)}</p>
            <WTaskCriteriaSectionOrganism
                criteria={criteria}
                canEdit={canEdit}
                busy={busy || saving}
                errors={errors}
                onAdd={(payload) => {
                    onAdd(payload);
                    const id = nextId;
                    setNextId(id + 1);
                    later((list) => {
                        if (payload.parent_id === null) {
                            const number = `CA_${lastNumber(list.map((item) => item.number)) + 1}`;
                            return [...list, { id, number, description: payload.description, result: 'pending', children: [] }];
                        }
                        return list.map((item) =>
                            item.id === payload.parent_id
                                ? {
                                      ...item,
                                      children: [
                                          ...item.children,
                                          {
                                              id,
                                              number: `${item.number}.${lastNumber(item.children.map((child) => child.number)) + 1}`,
                                              description: payload.description,
                                              result: 'pending',
                                              children: [],
                                          },
                                      ],
                                  }
                                : item,
                        );
                    });
                }}
                onUpdate={(id, description) => {
                    onUpdate(id, description);
                    const edit = (item: AcceptanceCriterion): AcceptanceCriterion =>
                        item.id === id ? { ...item, description, result: 'pending' } : { ...item, children: item.children.map(edit) };
                    later((list) => list.map(edit));
                }}
                onDelete={(id) => {
                    onDelete(id);
                    later((list) =>
                        list
                            .filter((item) => item.id !== id)
                            .map((item) => ({ ...item, children: item.children.filter((child) => child.id !== id) })),
                    );
                }}
            />
        </div>
    );
}

const meta = {
    title: 'Organisms/Task Criteria Section',
    component: CriteriaSectionDemo,
    tags: ['autodocs'],
    parameters: { layout: 'padded' },
    decorators: [
        (Story) => (
            <div className="w-[40rem] max-w-full">
                <Story />
            </div>
        ),
    ],
    argTypes: {
        criteria: { control: 'object' },
        canEdit: { control: 'boolean' },
        busy: { control: 'boolean' },
        errors: { control: 'object' },
    },
    args: { criteria: CRITERIA, canEdit: true, busy: false, onAdd: fn(), onUpdate: fn(), onDelete: fn() },
} satisfies Meta<typeof CriteriaSectionDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Gestor: CA_1 aprovado, CA_2 reprovado com subitens, CA_3 e CA_5 a testar (CA_4 foi excluído e não volta). */
export const Completo: Story = {};

/** "Nenhum critério. *Adicionar critério*" (F3 §03). */
export const Vazio: Story = { args: { criteria: [] } };

/** Sem `abilities.update`: só a lista e os resultados. */
export const SoLeitura: Story = { args: { canEdit: false } };

/** Uma gravação da ficha rodando: Excluir, Salvar e Adicionar desabilitados. */
export const Gravando: Story = { args: { busy: true } };

/** Editando CA_2 (reprovado): o aviso "Este critério volta para A testar" (P37) e o erro do servidor. */
export const ComErro: Story = {
    args: { errors: { description: 'A descrição não pode passar de 5000 caracteres.' } },
    play: async ({ canvasElement }) => {
        await userEvent.click(within(canvasElement).getByRole('button', { name: 'Editar CA_2' }));
    },
};

/** Editando CA_1 (aprovado): o aviso de que volta para A testar, acima do Salvar (P37). */
export const EditandoCriterioTestado: Story = {
    play: async ({ canvasElement }) => {
        await userEvent.click(within(canvasElement).getByRole('button', { name: 'Editar CA_1' }));
    },
};

/** Subitem novo no fim de CA_3 (T9): o campo abre logo abaixo dos subitens dele. */
export const AdicionandoSubitem: Story = {
    play: async ({ canvasElement }) => {
        await userEvent.click(within(canvasElement).getByRole('button', { name: 'Subitem em CA_3' }));
    },
};
