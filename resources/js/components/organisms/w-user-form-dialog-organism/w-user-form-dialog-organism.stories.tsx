import { useForm } from '@inertiajs/react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { useEffect } from 'react';

import { ROLES } from '../../../../../.storybook/fixtures';
import { ROLE, WUserFormDialogOrganism, type UserFormData } from './w-user-form-dialog-organism';

interface DemoProps {
    editing: boolean;
    deactivatedNote: string;
    nameError: string;
}

function UserFormDialogDemo({ editing, deactivatedNote, nameError }: DemoProps) {
    const form = useForm<UserFormData>({
        role_id: ROLE.DEVELOPER,
        name: editing ? 'Carla Dias' : '',
        email: editing ? 'carla@exemplo.wcj.test' : '',
        daily_hours: editing ? '6' : '',
        active: !deactivatedNote,
    });
    const { setError, clearErrors } = form;

    useEffect(() => {
        if (nameError) setError('name', nameError);
        else clearErrors();
        // eslint-disable-next-line react-hooks/exhaustive-deps -- reage só ao controle da story
    }, [nameError]);

    return (
        <WUserFormDialogOrganism
            open
            onOpenChange={() => undefined}
            form={form}
            editing={editing}
            deactivatedNote={deactivatedNote || null}
            roles={ROLES}
            onSubmit={(event) => {
                event.preventDefault();
                form.post('/admin/users');
            }}
        />
    );
}

const meta = {
    title: 'Organisms/User Form Dialog',
    component: UserFormDialogDemo,
    tags: ['autodocs'],
    argTypes: {
        editing: { control: 'boolean' },
        deactivatedNote: { control: 'text' },
        nameError: { control: 'text' },
    },
    args: { editing: false, deactivatedNote: '', nameError: '' },
    parameters: {
        layout: 'fullscreen',
        docs: { description: { component: 'Modal de criar/editar usuário. O `useForm` vive na página; aqui ficam só os campos.' } },
    },
} satisfies Meta<typeof UserFormDialogDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Novo: Story = {};
export const Editando: Story = { args: { editing: true } };
export const Desativado: Story = { args: { editing: true, deactivatedNote: 'Desativado em 02/10/2026 14:10, por Ana Souza.' } };
export const ComErro: Story = { args: { nameError: 'O nome é obrigatório.' } };
