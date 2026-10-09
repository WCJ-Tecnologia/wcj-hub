import type { Meta, StoryObj } from '@storybook/react-vite';

import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from './table';

const ROWS = [
    { name: 'Ana Souza', role: 'Administrador', email: 'ana@exemplo.wcj.test' },
    { name: 'Bruno Lima', role: 'Desenvolvedor', email: 'bruno@exemplo.wcj.test' },
    { name: 'Carla Dias', role: 'Desenvolvedor', email: 'carla@exemplo.wcj.test' },
];

interface DemoProps {
    caption: string;
    rows: number;
}

/** Primitivo cru. Para listagem de tela, use o WDataTableOrganism (cards no celular, estado vazio). */
function TableDemo({ caption, rows }: DemoProps) {
    return (
        <Table className="w-[36rem]">
            <TableCaption>{caption}</TableCaption>
            <TableHeader>
                <TableRow>
                    <TableHead scope="col">Nome</TableHead>
                    <TableHead scope="col">Papel</TableHead>
                    <TableHead scope="col">E-mail</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {ROWS.slice(0, rows).map((row) => (
                    <TableRow key={row.email}>
                        <TableCell className="font-medium">{row.name}</TableCell>
                        <TableCell>{row.role}</TableCell>
                        <TableCell className="text-muted-foreground">{row.email}</TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}

const meta = {
    title: 'Shadcn/Table',
    component: TableDemo,
    tags: ['autodocs'],
    argTypes: { caption: { control: 'text' }, rows: { control: { type: 'range', min: 0, max: 3 } } },
    args: { caption: 'Lista de usuários', rows: 3 },
} satisfies Meta<typeof TableDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
