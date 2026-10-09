import type { Meta, StoryObj } from '@storybook/react-vite';

import { WStatusPillAtom } from '@/components/atoms/w-status-pill-atom/w-status-pill-atom';
import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import { Badge } from '@/components/shadcn/badge';

import { USERS } from '../../../../../.storybook/fixtures';
import { WDataTableOrganism, type WColumn } from './w-data-table-organism';

type Row = (typeof USERS)[number];

const COLUMNS: WColumn<Row>[] = [
    { key: 'name', header: 'Nome', className: 'font-medium' },
    { key: 'role_label', header: 'Papel', cell: (row) => <Badge variant="info">{row.role_label}</Badge> },
    { key: 'email', header: 'E-mail', className: 'text-muted-foreground', hideOnMobile: true },
    { key: 'active', header: 'Status', cell: (row) => <WStatusPillAtom kind="user" status={row.active} /> },
];

interface DemoProps {
    caption: string;
    rows: number;
    withMobileCard: boolean;
}

function DataTableDemo({ caption, rows, withMobileCard }: DemoProps) {
    return (
        <div className="w-full max-w-4xl">
            <WDataTableOrganism
                caption={caption}
                columns={COLUMNS}
                rows={USERS.slice(0, rows)}
                rowKey={(row) => row.id}
                emptyState={<WEmptyStateMolecule title="Nenhum usuário encontrado" description="Ajuste a busca ou cadastre alguém." />}
                mobileCard={
                    withMobileCard
                        ? (row) => (
                              <div className="flex items-center justify-between">
                                  <span className="font-semibold">{row.name}</span>
                                  <WStatusPillAtom kind="user" status={row.active} size="sm" />
                              </div>
                          )
                        : undefined
                }
            />
        </div>
    );
}

const meta = {
    title: 'Organisms/Data Table',
    component: DataTableDemo,
    tags: ['autodocs'],
    argTypes: {
        caption: { control: 'text' },
        rows: { control: { type: 'range', min: 0, max: USERS.length } },
        withMobileCard: { control: 'boolean' },
    },
    args: { caption: 'Lista de usuários', rows: USERS.length, withMobileCard: false },
    parameters: {
        layout: 'padded',
        docs: {
            description: { component: 'Tabela no desktop (md+), lista de cards no celular. Diminua a janela para ver o modo celular.' },
        },
    },
} satisfies Meta<typeof DataTableDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const Vazia: Story = { args: { rows: 0 } };
export const CelularComCardProprio: Story = {
    args: { withMobileCard: true },
    globals: { viewport: { value: 'mobile2', isRotated: false } },
};
