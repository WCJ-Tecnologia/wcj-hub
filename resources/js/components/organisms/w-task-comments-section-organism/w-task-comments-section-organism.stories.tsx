import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { fn } from 'storybook/test';

import type { TaskComment } from '@/types/quadro';

import { COMMENTS } from '../../../../../.storybook/fixtures-ficha';
import { BRUNO } from '../../../../../.storybook/fixtures-quadro';
import { commentsSummary, WTaskCommentsSectionOrganism } from './w-task-comments-section-organism';

interface DemoProps {
    comments: TaskComment[];
    canComment: boolean;
    busy: boolean;
    errors?: Record<string, string>;
    onAdd: (body: string) => void;
    onUpdate: (id: number, body: string) => void;
    onDelete: (id: number) => void;
}

/** Faz o papel da página (quem comenta é o Bruno): grava na lista local depois de 600 ms com `busy` ligado. */
function CommentsSectionDemo({ comments: initial, canComment, busy, errors, onAdd, onUpdate, onDelete }: DemoProps) {
    const [comments, setComments] = useState(initial);
    const [saving, setSaving] = useState(false);
    const [nextId, setNextId] = useState(100);

    const later = (apply: (list: TaskComment[]) => TaskComment[]) => {
        setSaving(true);
        setTimeout(() => {
            setComments(apply);
            setSaving(false);
        }, 600);
    };

    return (
        <div className="space-y-3">
            <p className="font-mono text-xs text-muted-foreground">Resumo: {commentsSummary(comments)}</p>
            <WTaskCommentsSectionOrganism
                comments={comments}
                canComment={canComment}
                busy={busy || saving}
                errors={errors}
                onAdd={(body) => {
                    onAdd(body);
                    const id = nextId;
                    setNextId(id + 1);
                    later((list) => [
                        ...list,
                        { id, author: BRUNO, body, created_at: '2026-10-14 09:30', edited_at: null, can: { update: true, delete: true } },
                    ]);
                }}
                onUpdate={(id, body) => {
                    onUpdate(id, body);
                    later((list) =>
                        list.map((comment) => (comment.id === id ? { ...comment, body, edited_at: '2026-10-14 09:31' } : comment)),
                    );
                }}
                onDelete={(id) => {
                    onDelete(id);
                    later((list) => list.filter((comment) => comment.id !== id));
                }}
            />
        </div>
    );
}

const meta = {
    title: 'Organisms/Task Comments Section',
    component: CommentsSectionDemo,
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
        comments: { control: 'object' },
        canComment: { control: 'boolean' },
        busy: { control: 'boolean' },
        errors: { control: 'object' },
    },
    args: { comments: COMMENTS, canComment: true, busy: false, onAdd: fn(), onUpdate: fn(), onDelete: fn() },
} satisfies Meta<typeof CommentsSectionDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Três comentários; o do Bruno (editado) é dele: só esse tem *Editar* e *Excluir* (10.10). */
export const Completo: Story = {};

/** "Nenhum comentário. *Comentar*" (F3 §03): o link põe o foco no campo. */
export const Vazio: Story = { args: { comments: [] } };

/** Sem `abilities.comment` e sem ser autor de nada: só a lista. */
export const SoLeitura: Story = {
    args: { canComment: false, comments: COMMENTS.map((comment) => ({ ...comment, can: { update: false, delete: false } })) },
};

/** Uma gravação da ficha rodando: Comentar e Excluir desabilitados. */
export const Gravando: Story = { args: { busy: true } };

/** O erro do servidor no campo *Novo comentário*. */
export const ComErro: Story = { args: { errors: { body: 'O comentário não pode ficar vazio.' } } };
