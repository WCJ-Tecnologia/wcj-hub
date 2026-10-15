import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';

import type { SprintFields } from '@/types/sprints';

import { WSprintFieldsMolecule } from './w-sprint-fields-molecule';

function Demo({
    initial,
    followStart,
    tried,
    errors,
}: {
    initial: SprintFields;
    followStart: boolean;
    tried: boolean;
    errors?: Record<string, string>;
}) {
    const [value, setValue] = useState(initial);
    return (
        <div className="max-w-lg">
            <WSprintFieldsMolecule
                value={value}
                onChange={setValue}
                sprintDays={10}
                followStart={followStart}
                tried={tried}
                errors={errors}
            />
        </div>
    );
}

const meta = {
    title: 'Molecules/Sprint Fields',
    component: Demo,
    tags: ['autodocs'],
    // Terça 06/10/2026 com 10 dias úteis: fim na segunda 19/10/2026 (F5 §01).
    args: { initial: { name: 'Sprint 5', goal: '', starts_on: '2026-10-06', ends_on: '2026-10-19' }, followStart: true, tried: false },
} satisfies Meta<typeof Demo>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Proposta: Story = {};
export const Edicao: Story = {
    args: { followStart: false, initial: { name: 'Sprint 4', goal: 'Login novo', starts_on: '2026-09-22', ends_on: '2026-10-05' } },
};
export const FimAntesDoInicio: Story = {
    args: { initial: { name: 'Sprint 5', goal: '', starts_on: '2026-10-06', ends_on: '2026-10-01' } },
};
export const NomeRepetido: Story = { args: { errors: { name: 'Já existe a Sprint 5 neste projeto' } } };
