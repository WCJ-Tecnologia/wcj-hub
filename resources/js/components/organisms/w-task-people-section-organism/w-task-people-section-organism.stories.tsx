import { TASK_COMPLETO } from '../../../../../.storybook/fixtures-ficha';
import { BRUNO, CARLA, DIEGO, MEMBERS, RAFAEL } from '../../../../../.storybook/fixtures-quadro';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { action } from 'storybook/actions';

import type { PersonRef } from '@/types/quadro';

import { WTaskPeopleSectionOrganism, type WTaskPeopleSectionOrganismProps, peopleSummary } from './w-task-people-section-organism';

/**
 * A seção com estado local: Dev, QA e participantes mudam na hora, como depois da resposta do
 * servidor. As intenções aparecem no painel Actions; o resumo do cabeçalho recolhido fica em cima.
 * `currentUserId` = Rafael Garcia (o gestor): defini-lo como dev ou QA não pede confirmação.
 */
function PeopleSectionDemo(props: WTaskPeopleSectionOrganismProps) {
    const [developer, setDeveloper] = useState<PersonRef | null>(props.developer);
    const [qa, setQa] = useState<PersonRef | null>(props.qa);
    const [participants, setParticipants] = useState<PersonRef[]>(props.participants);

    return (
        <div className="space-y-3">
            <p className="font-mono text-xs text-muted-foreground">Resumo: {peopleSummary(developer, qa, participants)}</p>
            <WTaskPeopleSectionOrganism
                {...props}
                developer={developer}
                qa={qa}
                participants={participants}
                onChangeDeveloper={(person) => {
                    action('onChangeDeveloper')(person?.name ?? null);
                    setDeveloper(person);
                    // Quem vira dev não fica também como participante (F3 §04).
                    if (person) setParticipants((list) => list.filter((item) => item.id !== person.id));
                }}
                onChangeQa={(person) => {
                    action('onChangeQa')(person?.name ?? null);
                    setQa(person);
                    if (person) setParticipants((list) => list.filter((item) => item.id !== person.id));
                }}
                onToggleParticipant={(person, add) => {
                    action('onToggleParticipant')(person.name, add);
                    setParticipants((list) => (add ? [...list, person] : list.filter((item) => item.id !== person.id)));
                }}
            />
        </div>
    );
}

const meta = {
    title: 'Organisms/Task People Section',
    component: PeopleSectionDemo,
    tags: ['autodocs'],
    parameters: { layout: 'padded' },
    decorators: [
        (Story) => (
            <div className="w-[40rem] max-w-full">
                <Story />
            </div>
        ),
    ],
    argTypes: {
        taskKey: { control: 'text' },
        developer: { control: 'object' },
        qa: { control: 'object' },
        participants: { control: 'object' },
        members: { control: 'object' },
        currentUserId: { control: 'number' },
        canEdit: { control: 'boolean' },
        busy: { control: 'boolean' },
        errors: { control: 'object' },
        onChangeDeveloper: { control: false },
        onChangeQa: { control: false },
        onToggleParticipant: { control: false },
    },
    args: {
        taskKey: TASK_COMPLETO.key,
        developer: BRUNO,
        qa: CARLA,
        participants: [RAFAEL, DIEGO],
        members: MEMBERS,
        currentUserId: RAFAEL.id,
        canEdit: true,
        busy: false,
        onChangeDeveloper: () => {},
        onChangeQa: () => {},
        onToggleParticipant: () => {},
    },
} satisfies Meta<typeof PeopleSectionDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

/** SGM-17 visto pelo gestor: Bruno dev, Carla QA, Rafael e Diego participantes. */
export const Completo: Story = {};

/** Card sem ninguém: "Ninguém" nos dois campos e "Nenhum participante." */
export const Vazio: Story = { args: { developer: null, qa: null, participants: [] } };

/** Sem `update`: os nomes em texto, sem campo nem botão. */
export const SoLeitura: Story = { args: { canEdit: false } };

/** Gravação em curso: campos e botões desabilitados, `aria-busy` no bloco. */
export const Gravando: Story = { args: { busy: true } };

/** O servidor recusou o dev (ex.: a pessoa saiu do projeto enquanto a ficha estava aberta, P5). */
export const ComErro: Story = {
    args: {
        errors: {
            developer_id: 'Bruno Lima não está mais alocado no projeto.',
            user_id: 'Diego Prado não está mais alocado no projeto.',
        },
    },
};

/** Projeto sem ninguém alocado: só "Ninguém" e o aviso desabilitado no Select. */
export const SemPessoasAlocadas: Story = { args: { developer: null, qa: null, participants: [], members: [] } };
