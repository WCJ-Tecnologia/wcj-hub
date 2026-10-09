import type { Meta, StoryObj } from '@storybook/react-vite';

import type { Paginated } from '@/types';

import { WPaginationMolecule } from './w-pagination-molecule';

/** Monta o paginator como o Laravel devolve (`->paginate(15)`), com reticências depois de 8 páginas. */
function paginator(current: number, last: number, perPage = 15): Paginated<unknown> {
    const page = (n: number) => ({ url: `/admin/users?page=${n}`, label: String(n), active: n === current });
    const numbers =
        last <= 8
            ? Array.from({ length: last }, (_, i) => page(i + 1))
            : [
                  page(1),
                  page(2),
                  { url: null, label: '...', active: false },
                  page(current),
                  { url: null, label: '...', active: false },
                  page(last),
              ];
    const total = last * perPage - 3;

    return {
        data: [],
        current_page: current,
        last_page: last,
        per_page: perPage,
        total,
        from: (current - 1) * perPage + 1,
        to: Math.min(current * perPage, total),
        links: [
            { url: current > 1 ? `/admin/users?page=${current - 1}` : null, label: '&laquo; Previous', active: false },
            ...numbers,
            { url: current < last ? `/admin/users?page=${current + 1}` : null, label: 'Next &raquo;', active: false },
        ],
    };
}

interface DemoProps {
    current: number;
    last: number;
}

function PaginationDemo({ current, last }: DemoProps) {
    return (
        <div className="w-[44rem]">
            <WPaginationMolecule meta={paginator(Math.min(current, last), last)} />
        </div>
    );
}

const meta = {
    title: 'Molecules/Pagination',
    component: PaginationDemo,
    tags: ['autodocs'],
    argTypes: {
        current: { control: { type: 'number', min: 1 } },
        last: { control: { type: 'number', min: 1 } },
    },
    args: { current: 1, last: 4 },
    parameters: { docs: { description: { component: 'Recebe o `meta` do paginator do Laravel. Com uma página só, não renderiza nada.' } } },
} satisfies Meta<typeof PaginationDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

export const PrimeiraPagina: Story = {};
export const NoMeio: Story = { args: { current: 7, last: 20 } };
export const UmaPaginaSo: Story = { args: { current: 1, last: 1 } };
