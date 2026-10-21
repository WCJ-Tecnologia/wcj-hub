import type { Meta, StoryObj } from '@storybook/react-vite';

import { QUALITY } from '../../../../../.storybook/fixtures-indicadores';
import { WQualityTabOrganism } from './w-quality-tab-organism';

const meta = {
    title: 'Organisms/Indicadores/Qualidade',
    component: WQualityTabOrganism,
    tags: ['autodocs'],
    args: { data: QUALITY },
} satisfies Meta<typeof WQualityTabOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

export const DoisBugs: Story = {};
export const Vazia: Story = { args: { data: { total: 0, rows: [] } } };
