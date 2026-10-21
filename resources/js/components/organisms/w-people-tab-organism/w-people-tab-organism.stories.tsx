import type { Meta, StoryObj } from '@storybook/react-vite';

import { PEOPLE } from '../../../../../.storybook/fixtures-indicadores';
import { WPeopleTabOrganism } from './w-people-tab-organism';

const meta = {
    title: 'Organisms/Indicadores/Por pessoa',
    component: WPeopleTabOrganism,
    tags: ['autodocs'],
    args: { data: PEOPLE },
} satisfies Meta<typeof WPeopleTabOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

export const ComSemDev: Story = {};
export const Vazia: Story = { args: { data: { rows: [] } } };
