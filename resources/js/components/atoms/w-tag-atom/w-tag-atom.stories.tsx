import type { Meta, StoryObj } from '@storybook/react-vite';

import { TAG_COLOR_LABEL, TAG_COLORS } from '@/lib/tags';

import { WTagAtom } from './w-tag-atom';

const meta = {
    title: 'Atoms/Tag',
    component: WTagAtom,
    tags: ['autodocs'],
    argTypes: {
        color: { control: 'select', options: [null, ...TAG_COLORS] },
        kind: { control: 'radio', options: ['type', 'area'] },
        size: { control: 'radio', options: ['sm', 'default'] },
    },
    args: { label: 'Melhoria', color: 'blue', kind: 'type' },
} satisfies Meta<typeof WTagAtom>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const SemCor: Story = { args: { label: 'Relatórios', color: null, kind: 'area' } };
/** As 10 cores da paleta, claro e escuro pela barra do Storybook. */
export const Paleta: Story = {
    render: () => (
        <div className="flex max-w-md flex-wrap gap-2">
            {TAG_COLORS.map((color) => (
                <WTagAtom key={color} label={TAG_COLOR_LABEL[color]} color={color} />
            ))}
        </div>
    ),
};
/** Como ficam no card: tipo primeiro, área depois, tamanho `sm`. */
export const NoCard: Story = {
    render: () => (
        <div className="flex gap-1.5">
            <WTagAtom label="Melhoria" color="blue" kind="type" size="sm" />
            <WTagAtom label="Portal do cidadão" color="sky" kind="area" size="sm" />
        </div>
    ),
};
