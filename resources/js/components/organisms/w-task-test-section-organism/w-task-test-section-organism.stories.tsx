import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { fn, userEvent, within } from 'storybook/test';

import type { AcceptanceCriterion, CriterionResult, TaskAttachment, TaskDetail } from '@/types/quadro';

import { ATTACHMENTS, CRITERIA } from '../../../../../.storybook/fixtures-ficha';
import { ANA, CARLA, COVER_URL } from '../../../../../.storybook/fixtures-quadro';
import { type ReturnOrigin, testSummary, WTaskTestSectionOrganism } from './w-task-test-section-organism';

interface DemoProps {
    criteria: AcceptanceCriterion[];
    crossTested: TaskDetail['cross_tested'];
    attachments: TaskAttachment[];
    canCrossTest: boolean;
    canUndoCrossTest: boolean;
    canRunQa: boolean;
    canAttach: boolean;
    canEdit: boolean;
    canReturn: boolean;
    busy: boolean;
    errors?: Record<string, string>;
    onMarkCrossTest: () => void;
    onUndoCrossTest: () => void;
    onSetResult: (criterionId: number, result: CriterionResult) => void;
    onUploadEvidence: (criterionId: number, files: File[]) => void;
    onRemoveEvidence: (attachmentId: number) => void;
    onReturn: (origin: ReturnOrigin) => void;
    onNewCriterion: () => void;
}

/** Quem marca nesta demonstração: Carla, às 15:10 de hoje no exemplo. */
const NOW = '2026-10-13 15:10';

/**
 * Faz o papel da página e do servidor: grava depois de 600 ms com `busy` ligado (marcar e desfazer
 * o teste cruzado, o resultado com a última marcação, a evidência como anexo do item).
 */
function TestSectionDemo({
    criteria: initialCriteria,
    crossTested: initialCross,
    attachments: initialAttachments,
    busy,
    onMarkCrossTest,
    onUndoCrossTest,
    onSetResult,
    onUploadEvidence,
    onRemoveEvidence,
    ...rest
}: DemoProps) {
    const [criteria, setCriteria] = useState(initialCriteria);
    const [crossTested, setCrossTested] = useState(initialCross);
    const [attachments, setAttachments] = useState(initialAttachments);
    const [saving, setSaving] = useState(false);
    const [nextId, setNextId] = useState(100);

    const later = (apply: () => void) => {
        setSaving(true);
        setTimeout(() => {
            apply();
            setSaving(false);
        }, 600);
    };
    const mark = (item: AcceptanceCriterion, id: number, result: CriterionResult): AcceptanceCriterion =>
        item.id === id
            ? { ...item, result, marked: { by: CARLA, at: NOW } }
            : { ...item, children: item.children.map((child) => mark(child, id, result)) };

    return (
        <div className="space-y-3">
            <p className="font-mono text-xs text-muted-foreground">Resumo: {testSummary(crossTested, criteria)}</p>
            <WTaskTestSectionOrganism
                {...rest}
                criteria={criteria}
                crossTested={crossTested}
                attachments={attachments}
                busy={busy || saving}
                onMarkCrossTest={() => {
                    onMarkCrossTest();
                    later(() => setCrossTested({ by: CARLA, at: NOW }));
                }}
                onUndoCrossTest={() => {
                    onUndoCrossTest();
                    later(() => setCrossTested(null));
                }}
                onSetResult={(id, result) => {
                    onSetResult(id, result);
                    later(() => setCriteria((list) => list.map((item) => mark(item, id, result))));
                }}
                onUploadEvidence={(criterionId, files) => {
                    onUploadEvidence(criterionId, files);
                    const id = nextId;
                    setNextId(id + files.length);
                    later(() =>
                        setAttachments((list) => [
                            ...list,
                            ...files.map((file, index) => ({
                                id: id + index,
                                name: file.name,
                                size: file.size,
                                is_image: file.type.startsWith('image/'),
                                url: COVER_URL,
                                uploaded_by: CARLA,
                                uploaded_at: NOW,
                                criterion_id: criterionId,
                            })),
                        ]),
                    );
                }}
                onRemoveEvidence={(id) => {
                    onRemoveEvidence(id);
                    later(() => setAttachments((list) => list.filter((item) => item.id !== id)));
                }}
            />
        </div>
    );
}

const meta = {
    title: 'Organisms/Task Test Section',
    component: TestSectionDemo,
    tags: ['autodocs'],
    parameters: { layout: 'padded' },
    decorators: [
        (Story) => (
            <div className="w-[44rem] max-w-full">
                <Story />
            </div>
        ),
    ],
    argTypes: {
        criteria: { control: 'object' },
        crossTested: { control: 'object' },
        canCrossTest: { control: 'boolean' },
        canUndoCrossTest: { control: 'boolean' },
        canRunQa: { control: 'boolean' },
        canAttach: { control: 'boolean' },
        canEdit: { control: 'boolean' },
        canReturn: { control: 'boolean' },
        busy: { control: 'boolean' },
        errors: { control: 'object' },
    },
    args: {
        criteria: CRITERIA,
        crossTested: null,
        attachments: ATTACHMENTS,
        canCrossTest: true,
        canUndoCrossTest: true,
        canRunQa: true,
        canAttach: true,
        canEdit: true,
        canReturn: true,
        busy: false,
        onMarkCrossTest: fn(),
        onUndoCrossTest: fn(),
        onSetResult: fn(),
        onUploadEvidence: fn(),
        onRemoveEvidence: fn(),
        onReturn: fn(),
        onNewCriterion: fn(),
    },
} satisfies Meta<typeof TestSectionDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

/** QA em In test: teste cruzado pendente, CA_2 e CA_2.2 reprovados (em destaque), CA_2.2 com a evidência. */
export const EmTeste: Story = {};

/** Teste cruzado feito pela Ana: *Desfazer a marca* no lugar do *Marquei*. */
export const TesteCruzadoFeito: Story = { args: { crossTested: { by: ANA, at: '2026-10-12 16:40' } } };

/** O dev da própria tarefa: sem *Marquei: testado por mim* (P34); o roteiro ele marca (F4 §10.1). */
export const DevDaTarefa: Story = { args: { canCrossTest: false, canUndoCrossTest: false } };

/** "Esta tarefa não tem critérios de aceite. *Novo critério*" (F4 §03). */
export const SemCriterio: Story = { args: { criteria: [], attachments: [] } };

/** Card em Doing ou Backlog: voltar não é retrabalho, o atalho *Devolver* não aparece. */
export const ForaDoTeste: Story = { args: { canReturn: false } };

/** Quem só vê: o resultado em pílula, sem botões. */
export const SoLeitura: Story = {
    args: { canCrossTest: false, canUndoCrossTest: false, canRunQa: false, canAttach: false, canEdit: false, canReturn: false },
};

/** Uma gravação da ficha rodando: tudo desabilitado. */
export const Gravando: Story = { args: { busy: true } };

/** As evidências de CA_2.2 abertas, com o *Excluir* de cada arquivo. */
export const EvidenciasAbertas: Story = {
    play: async ({ canvasElement }) => {
        await userEvent.click(within(canvasElement).getByRole('button', { name: '1 evidência' }));
    },
};

/** *Desfazer a marca* pede confirmação: "Desfazer a marca de teste cruzado de Ana Souza?". */
export const DesfazendoAMarca: Story = {
    args: { crossTested: { by: ANA, at: '2026-10-12 16:40' } },
    play: async ({ canvasElement }) => {
        await userEvent.click(within(canvasElement).getByRole('button', { name: 'Desfazer a marca' }));
    },
};
