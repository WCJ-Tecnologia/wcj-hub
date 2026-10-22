import type { Meta, StoryObj } from '@storybook/react-vite';
import { Archive } from 'lucide-react';

import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';

import { ARCHIVED_CARDS } from '../../../../../.storybook/fixtures-quadro';
import { WArchivedCardsTableOrganism } from './w-archived-cards-table-organism';

const VAZIO = (
    <WEmptyStateMolecule
        icon={<Archive className="size-5" aria-hidden />}
        title="Nenhum card arquivado"
        description="Arquivar fica na ficha do card, com motivo."
    />
);

const meta = {
    title: 'Organisms/Archived Cards Table',
    component: WArchivedCardsTableOrganism,
    tags: ['autodocs'],
    args: { rows: ARCHIVED_CARDS, emptyState: VAZIO },
} satisfies Meta<typeof WArchivedCardsTableOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const Vazio: Story = { args: { rows: [] } };
