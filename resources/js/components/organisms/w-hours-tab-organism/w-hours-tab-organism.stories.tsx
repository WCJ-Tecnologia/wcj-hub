import type { Meta, StoryObj } from '@storybook/react-vite';

import { HOURS } from '../../../../../.storybook/fixtures-indicadores';
import { WHoursTabOrganism } from './w-hours-tab-organism';

const meta = {
    title: 'Organisms/Indicadores/Horas',
    component: WHoursTabOrganism,
    tags: ['autodocs'],
    args: { data: HOURS },
} satisfies Meta<typeof WHoursTabOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

export const ComArquivadaESemDev: Story = {};
export const Vazia: Story = { args: { data: { rows: [], totals: { estimated: 0, logged: 0, difference: 0, development_hours: 0 } } } };
