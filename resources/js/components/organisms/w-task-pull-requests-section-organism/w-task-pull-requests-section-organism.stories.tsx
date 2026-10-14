import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { fn } from 'storybook/test';

import type { TaskPullRequest } from '@/types/quadro';

import { PULL_REQUESTS } from '../../../../../.storybook/fixtures-ficha';
import { CARLA } from '../../../../../.storybook/fixtures-quadro';
import { pullRequestsSummary, WTaskPullRequestsSectionOrganism } from './w-task-pull-requests-section-organism';

interface DemoProps {
    pullRequests: TaskPullRequest[];
    canEdit: boolean;
    busy: boolean;
    errors?: Record<string, string>;
    onAdd: (url: string) => void;
    onRemove: (id: number) => void;
}

/** Faz o papel da página: grava na lista local depois de 600 ms com `busy` ligado. */
function PullRequestsSectionDemo({ pullRequests: initial, canEdit, busy, errors, onAdd, onRemove }: DemoProps) {
    const [pullRequests, setPullRequests] = useState(initial);
    const [saving, setSaving] = useState(false);
    const [nextId, setNextId] = useState(100);

    const later = (apply: (list: TaskPullRequest[]) => TaskPullRequest[]) => {
        setSaving(true);
        setTimeout(() => {
            setPullRequests(apply);
            setSaving(false);
        }, 600);
    };

    return (
        <div className="space-y-3">
            <p className="font-mono text-xs text-muted-foreground">Resumo: {pullRequestsSummary(pullRequests)}</p>
            <WTaskPullRequestsSectionOrganism
                pullRequests={pullRequests}
                canEdit={canEdit}
                busy={busy || saving}
                errors={errors}
                onAdd={(url) => {
                    onAdd(url);
                    const id = nextId;
                    setNextId(id + 1);
                    later((list) => [...list, { id, url, added_by: CARLA, added_at: '2026-10-14 09:30' }]);
                }}
                onRemove={(id) => {
                    onRemove(id);
                    later((list) => list.filter((pr) => pr.id !== id));
                }}
            />
        </div>
    );
}

const meta = {
    title: 'Organisms/Task Pull Requests Section',
    component: PullRequestsSectionDemo,
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
        pullRequests: { control: 'object' },
        canEdit: { control: 'boolean' },
        busy: { control: 'boolean' },
        errors: { control: 'object' },
    },
    args: { pullRequests: PULL_REQUESTS, canEdit: true, busy: false, onAdd: fn(), onRemove: fn() },
} satisfies Meta<typeof PullRequestsSectionDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Dois PRs, na ordem em que entraram. Cole o link do #118 de novo para ver "Este PR já está no card". */
export const Completo: Story = {};

/** "Nenhum PR. *Adicionar link*" (F3 §03): o link põe o foco no campo. */
export const Vazio: Story = { args: { pullRequests: [] } };

/** Sem `abilities.update`: só os links. */
export const SoLeitura: Story = { args: { canEdit: false } };

/** Uma gravação da ficha rodando: Adicionar e Remover desabilitados. */
export const Gravando: Story = { args: { busy: true } };

/** O erro do servidor no campo (P23: o banco tem `unique (task_id, url)`). */
export const ComErro: Story = { args: { errors: { url: 'O link do PR não pode passar de 500 caracteres.' } } };

/** Link de GitLab e um link que não é de PR: o texto encurtado é domínio + caminho. */
export const OutrosLinks: Story = {
    args: {
        pullRequests: [
            ...PULL_REQUESTS,
            { id: 3, url: 'https://gitlab.com/wcj/sgm-api/-/merge_requests/7', added_by: CARLA, added_at: '2026-10-11 08:05' },
            { id: 4, url: 'https://www.github.com/WCJ-Tecnologia/sgm/commit/4f2a9c1', added_by: CARLA, added_at: '2026-10-11 08:06' },
        ],
    },
};
