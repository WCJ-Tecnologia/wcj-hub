import type { Meta, StoryObj } from '@storybook/react-vite';
import { fn } from 'storybook/test';

import { PEOPLE_OPTIONS } from '../../../../../.storybook/fixtures-configuracao';
import { WAllocatePeopleDialogOrganism } from './w-allocate-people-dialog-organism';

const meta = {
    title: 'Organisms/Alocar pessoas',
    component: WAllocatePeopleDialogOrganism,
    tags: ['autodocs'],
    args: { open: true, onOpenChange: fn(), people: PEOPLE_OPTIONS, loading: false, onConfirm: fn() },
    parameters: { layout: 'fullscreen' },
} satisfies Meta<typeof WAllocatePeopleDialogOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const ComErro: Story = { args: { error: 'Eduardo Prado já está no projeto.' } };
export const Enviando: Story = { args: { loading: true } };
export const NinguemParaAlocar: Story = { args: { people: [] } };
