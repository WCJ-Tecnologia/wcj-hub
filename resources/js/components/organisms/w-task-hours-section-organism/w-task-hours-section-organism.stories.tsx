import {
    ABILITIES_FICHA_DEV,
    ABILITIES_FICHA_GESTOR,
    ABILITIES_FICHA_LEITURA,
    TASK_COMPLETO,
    TASK_MINIMO,
} from '../../../../../.storybook/fixtures-ficha';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { action } from 'storybook/actions';

import { previewDueOn } from '@/lib/ficha';

import { WTaskHoursSectionOrganism, type WTaskHoursSectionOrganismProps, hoursSummary } from './w-task-hours-section-organism';

type Detail = WTaskHoursSectionOrganismProps['detail'];

const pick = (task: typeof TASK_COMPLETO): Detail => ({
    key: task.key,
    estimated_hours: task.estimated_hours,
    proposed_hours: task.proposed_hours,
    proposed_by: task.proposed_by,
    logged_hours: task.logged_hours,
    due_on: task.due_on,
    development_started_on: task.development_started_on,
    developer_daily_hours: task.developer_daily_hours,
});

/** O SGM-17 sem a proposta pendente do Bruno. */
const COMPLETO_SEM_PROPOSTA: Detail = { ...pick(TASK_COMPLETO), proposed_hours: null, proposed_by: null };

/**
 * A seção com estado local: definir, reestimar, propor, aprovar e recusar mudam as horas na hora,
 * como depois da resposta do servidor (a data prevista é refeita pela prévia da lib, no lugar do
 * `DueDateService`). As intenções aparecem no painel Actions; o resumo do cabeçalho fica em cima.
 */
function HoursSectionDemo(props: WTaskHoursSectionOrganismProps) {
    const [detail, setDetail] = useState<Detail>(props.detail);

    const withHours = (value: number | null): Detail => ({
        ...detail,
        estimated_hours: value,
        due_on: value === null ? null : (previewDueOn(detail, value) ?? detail.due_on),
    });

    return (
        <div className="space-y-3">
            <p className="font-mono text-xs text-muted-foreground">Resumo: {hoursSummary(detail)}</p>
            <WTaskHoursSectionOrganism
                {...props}
                detail={detail}
                onSetHours={(value) => {
                    action('onSetHours')(value);
                    setDetail(withHours(value));
                }}
                onPropose={(value) => {
                    action('onPropose')(value);
                    setDetail({ ...detail, proposed_hours: value, proposed_by: { id: 3, name: 'Bruno Lima' } });
                }}
                onApprove={() => {
                    action('onApprove')();
                    setDetail({ ...withHours(detail.proposed_hours), proposed_hours: null, proposed_by: null });
                }}
                onReject={() => {
                    action('onReject')();
                    setDetail({ ...detail, proposed_hours: null, proposed_by: null });
                }}
            />
        </div>
    );
}

const meta = {
    title: 'Organisms/Task Hours Section',
    component: HoursSectionDemo,
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
        detail: { control: 'object' },
        abilities: { control: 'object' },
        busy: { control: 'boolean' },
        errors: { control: 'object' },
        onSetHours: { control: false },
        onPropose: { control: false },
        onApprove: { control: false },
        onReject: { control: false },
    },
    args: {
        detail: pick(TASK_COMPLETO),
        abilities: ABILITIES_FICHA_GESTOR,
        busy: false,
        onSetHours: () => {},
        onPropose: () => {},
        onApprove: () => {},
        onReject: () => {},
    },
} satisfies Meta<typeof HoursSectionDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Gestor no SGM-17: 16 h, 11,5 h lançadas, data 09/10, e a proposta de 24 h do Bruno para aprovar ou recusar. */
export const Completo: Story = {};

/** O mesmo card visto pelo dev: a proposta dele aguardando o gestor, sem botão. */
export const Dev: Story = { args: { abilities: ABILITIES_FICHA_DEV } };

/** Dev sem proposta pendente: *Propor outro valor*. */
export const DevSemProposta: Story = { args: { detail: COMPLETO_SEM_PROPOSTA, abilities: ABILITIES_FICHA_DEV } };

/** Card só com título: "—" nas previstas, na data e no que falta; *Definir*. */
export const Vazio: Story = { args: { detail: pick(TASK_MINIMO) } };

/** O mesmo vazio, para quem procura pelo nome do F5. */
export const SemHoras: Story = { args: { detail: pick(TASK_MINIMO) } };

/** Sem permissão de horas: só os números. */
export const SoLeitura: Story = { args: { abilities: ABILITIES_FICHA_LEITURA } };

/** Gravação em curso: os botões desabilitados e `aria-busy` no bloco. */
export const Gravando: Story = { args: { busy: true } };

/** O servidor recusou o valor: o campo abre com o erro. */
export const ComErro: Story = {
    args: { detail: COMPLETO_SEM_PROPOSTA, errors: { estimated_hours: 'As horas previstas precisam ser maiores que zero.' } },
};

/**
 * Entrou em Doing na quinta 08/10, dev de 8 h/dia, 16 h → data 09/10. *Reestimar* para 40 h:
 * 5 dias úteis (08, 09, 12, 13 e 14/10), e a confirmação diz
 * "A data prevista vai de 09/10 para 14/10. Confirmar?".
 */
export const ReestimarMudaData: Story = {
    args: {
        detail: { ...COMPLETO_SEM_PROPOSTA, development_started_on: '2026-10-08', developer_daily_hours: 8, due_on: '2026-10-09' },
    },
};
