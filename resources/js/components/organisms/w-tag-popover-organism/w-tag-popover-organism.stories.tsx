import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { action } from 'storybook/actions';

import { BOARD_OPTIONS } from '../../../../../.storybook/fixtures-quadro';
import { type TagOption, WTagPopoverOrganism, type WTagPopoverOrganismProps } from './w-tag-popover-organism';

const TYPES: TagOption[] = BOARD_OPTIONS.types.map((type) => ({ id: type.id, name: type.label, color: type.color }));

/** Com estado local: escolher, criar e editar mudam a lista e a etiqueta na hora. */
function TagPopoverDemo(props: WTagPopoverOrganismProps) {
    const [options, setOptions] = useState<TagOption[]>(props.options);
    const [value, setValue] = useState<TagOption | null>(props.value);
    return (
        <WTagPopoverOrganism
            {...props}
            options={options}
            value={value}
            onChange={(option) => {
                action('onChange')(option);
                setValue(option);
            }}
            onCreate={
                props.onCreate &&
                ((payload) => {
                    action('onCreate')(payload);
                    const created = { id: Date.now(), ...payload };
                    setOptions((list) => [...list, created]);
                    setValue(created);
                })
            }
            onUpdate={
                props.onUpdate &&
                ((option, payload) => {
                    action('onUpdate')(option, payload);
                    const updated = { ...option, ...payload };
                    setOptions((list) => list.map((item) => (item.id === option.id ? updated : item)));
                    if (value?.id === option.id) setValue(updated);
                })
            }
            onDeactivate={
                props.onDeactivate &&
                ((option) => {
                    action('onDeactivate')(option);
                    // Sai da lista de escolha; o card que já a tem continua com ela (G7).
                    setOptions((list) => list.filter((item) => item.id !== option.id));
                })
            }
        />
    );
}

const meta = {
    title: 'Organisms/Tag Popover',
    component: WTagPopoverOrganism,
    render: (args) => <TagPopoverDemo {...args} />,
    tags: ['autodocs'],
    argTypes: {
        kind: { control: 'radio', options: ['type', 'area'] },
        busy: { control: 'boolean' },
        size: { control: 'radio', options: ['sm', 'default'] },
        options: { control: false },
        value: { control: false },
    },
    args: {
        kind: 'area',
        options: BOARD_OPTIONS.areas,
        value: BOARD_OPTIONS.areas[2],
        onChange: () => undefined,
        onCreate: () => undefined,
        onUpdate: () => undefined,
        onDeactivate: () => undefined,
    },
    decorators: [
        (Story) => (
            <div className="flex h-96 w-80 items-start">
                <Story />
            </div>
        ),
    ],
} satisfies Meta<typeof WTagPopoverOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Área, gestor: escolhe, edita (lápis) e cria (*Nova área*). */
export const AreaGestor: Story = {};
/** Tipo, gestor: os 5 padrão do projeto, mais o que o projeto criou; sem "Nenhum" (o tipo é obrigatório). */
export const TipoGestor: Story = { args: { kind: 'type', options: TYPES, value: TYPES[0] } };
/** Card sem área: o chip "+ Área". */
export const SemArea: Story = { args: { value: null, size: 'sm' } };
/** Dev: só escolhe; sem lápis nem *Novo…*. */
export const SoEscolhe: Story = { args: { onCreate: undefined, onUpdate: undefined, onDeactivate: undefined } };
/** Projeto sem área e sem permissão: "Nenhuma área no projeto". */
export const ProjetoSemArea: Story = { args: { options: [], value: null, onCreate: undefined, onUpdate: undefined } };
export const Gravando: Story = { args: { busy: true } };
export const ComErro: Story = { args: { errors: { name: 'Já existe a área Financeiro neste projeto' } } };
