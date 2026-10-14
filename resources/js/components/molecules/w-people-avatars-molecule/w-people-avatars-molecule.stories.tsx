import { ANA, BRUNO, CARLA, DIEGO, MEMBERS, RAFAEL } from '../../../../../.storybook/fixtures-quadro';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { action } from 'storybook/actions';

import type { PersonRef } from '@/types/quadro';

import { WPeopleAvatarsMolecule, type WPeopleAvatarsMoleculeProps } from './w-people-avatars-molecule';

const meta = {
    title: 'Molecules/People Avatars',
    component: WPeopleAvatarsMolecule,
    tags: ['autodocs'],
    argTypes: {
        size: { control: 'inline-radio', options: ['sm', 'default'] },
        max: { control: { type: 'number', min: 1, max: 8 } },
        developer: { control: 'object' },
        qa: { control: 'object' },
        participants: { control: 'object' },
        members: { control: false },
        onToggleParticipant: { control: false },
    },
    args: { developer: BRUNO, qa: null, participants: [], size: 'default' },
} satisfies Meta<typeof WPeopleAvatarsMolecule>;

export default meta;
type Story = StoryObj<typeof meta>;

export const SoDev: Story = {};

export const DevQaParticipantes: Story = { args: { developer: BRUNO, qa: CARLA, participants: [RAFAEL, DIEGO, ANA] } };

const MAIS: PersonRef[] = [RAFAEL, DIEGO, ANA, { id: 6, name: 'Fernanda Costa' }, { id: 7, name: 'Gustavo Reis' }];

export const SeteComMais: Story = { args: { developer: BRUNO, qa: CARLA, participants: MAIS } };

/** O *+* com estado de verdade: marcar e desmarcar muda os avatares. */
function ComBotaoDemo(props: WPeopleAvatarsMoleculeProps) {
    const [participants, setParticipants] = useState<PersonRef[]>(props.participants);
    return (
        <WPeopleAvatarsMolecule
            {...props}
            participants={participants}
            members={MEMBERS}
            onToggleParticipant={(person, checked) => {
                action('onToggleParticipant')(person, checked);
                setParticipants((current) => (checked ? [...current, person] : current.filter((item) => item.id !== person.id)));
            }}
        />
    );
}

export const ComBotaoMais: Story = {
    args: { developer: BRUNO, qa: CARLA, participants: [RAFAEL], size: 'sm' },
    render: (args) => <ComBotaoDemo {...args} />,
};

export const NinguemComBotao: Story = {
    args: { developer: null, qa: null, participants: [], size: 'sm' },
    render: (args) => <ComBotaoDemo {...args} />,
};
