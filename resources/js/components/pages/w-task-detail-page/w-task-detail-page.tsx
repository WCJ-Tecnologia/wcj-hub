/**
 * Rotas que esta tela chama (confirmadas no B3, PR 2 e PR 3; `routes/web.php`, grupo do quadro).
 * `abilities.archive` serve para arquivar e desarquivar; `abilities.delete` só vem no card nunca usado.
 * `{chave}` = `SGM-17`. Toda escrita volta com a ficha inteira (`task`) e, na ficha, o Inertia
 * troca as props; a tela não guarda cópia otimista (cada seção é pequena e o retorno é rápido).
 *
 * - `GET    /projetos/{id}/cards/{chave}`                       esta tela
 * - `PATCH  /projetos/{id}/cards/{chave}`                       `TaskPatchPayload`: título, tipo, área, prioridade, dev, QA,
 *                                                                requisição, tarefa de origem, card pai, capa, horas (gestor)
 * - `POST   /projetos/{id}/cards/{chave}/mover`                 `MovePayload` (P13, P15, P39, P45, R5), o mesmo do quadro
 * - `POST   /projetos/{id}/cards/{chave}/participantes`         `{ user_id, add }` (P58), o mesmo do quadro
 * - `POST   /projetos/{id}/cards/{chave}/horas/propor`          `{ hours }` (P27, dev)
 * - `POST   /projetos/{id}/cards/{chave}/horas/aprovar`         (P27, gestor)
 * - `POST   /projetos/{id}/cards/{chave}/horas/recusar`         (P27, gestor)
 * - `POST   /projetos/{id}/cards/{chave}/criterios`             `{ description, parent_id }` (P17, T9)
 * - `PATCH  /projetos/{id}/cards/{chave}/criterios/{cid}`       `{ description }` (P37)
 * - `DELETE /projetos/{id}/cards/{chave}/criterios/{cid}`       (T1)
 * - `POST   /projetos/{id}/cards/{chave}/prs`                   `{ url }` (P23)
 * - `DELETE /projetos/{id}/cards/{chave}/prs/{pid}`
 * - `POST   /projetos/{id}/cards/{chave}/comentarios`           `{ body }` (P24)
 * - `PATCH  /projetos/{id}/cards/{chave}/comentarios/{cid}`     `{ body }`
 * - `DELETE /projetos/{id}/cards/{chave}/comentarios/{cid}`
 * - `POST   /projetos/{id}/cards/{chave}/anexos`                multipart `files[]` (P24)
 * - `DELETE /projetos/{id}/cards/{chave}/anexos/{aid}`          (P24, P59: era a capa → sem capa)
 * - `POST   /projetos/{id}/cards/{chave}/arquivar`              `{ archive_reason }` (P26)
 * - `POST   /projetos/{id}/cards/{chave}/desarquivar`           (P26)
 * - `DELETE /projetos/{id}/cards/{chave}`                       só o nunca usado (P26); o servidor redireciona ao quadro
 * - `GET    /projetos/{id}/cards/{chave}/anexos/{aid}`          download com o nome original (imagem abre na página)
 * - `POST   /projetos/{id}/tipos` e `PUT /projetos/{id}/tipos/{tid}`  `{ name, color }`: tipo do projeto, criar e editar pela etiqueta (gestor; 14/10)
 * - `POST   /projetos/{id}/areas` e `PUT /projetos/{id}/areas/{aid}`  `{ name, color }`: área do projeto, as mesmas rotas da configuração (B2)
 */
import { Head, router, usePage } from '@inertiajs/react';
import { type ReactNode, useState } from 'react';
import { toast } from 'sonner';

import { WCollapsibleSectionMolecule } from '@/components/molecules/w-collapsible-section-molecule/w-collapsible-section-molecule';
import { WConfirmDialogMolecule } from '@/components/molecules/w-confirm-dialog-molecule/w-confirm-dialog-molecule';
import { WArchiveDialogOrganism, type ArchivePayload } from '@/components/organisms/w-archive-dialog-organism/w-archive-dialog-organism';
import {
    attachmentsSummary,
    WTaskAttachmentsSectionOrganism,
} from '@/components/organisms/w-task-attachments-section-organism/w-task-attachments-section-organism';
import {
    attributesSummary,
    WTaskAttributesSectionOrganism,
} from '@/components/organisms/w-task-attributes-section-organism/w-task-attributes-section-organism';
import {
    commentsSummary,
    WTaskCommentsSectionOrganism,
} from '@/components/organisms/w-task-comments-section-organism/w-task-comments-section-organism';
import {
    criteriaSummary,
    WTaskCriteriaSectionOrganism,
} from '@/components/organisms/w-task-criteria-section-organism/w-task-criteria-section-organism';
import { WTaskDetailHeaderOrganism } from '@/components/organisms/w-task-detail-header-organism/w-task-detail-header-organism';
import {
    hoursSummary,
    WTaskHoursSectionOrganism,
} from '@/components/organisms/w-task-hours-section-organism/w-task-hours-section-organism';
import {
    originSummary,
    WTaskOriginSectionOrganism,
} from '@/components/organisms/w-task-origin-section-organism/w-task-origin-section-organism';
import {
    parentSummary,
    WTaskParentSectionOrganism,
} from '@/components/organisms/w-task-parent-section-organism/w-task-parent-section-organism';
import {
    peopleSummary,
    WTaskPeopleSectionOrganism,
} from '@/components/organisms/w-task-people-section-organism/w-task-people-section-organism';
import {
    pullRequestsSummary,
    WTaskPullRequestsSectionOrganism,
} from '@/components/organisms/w-task-pull-requests-section-organism/w-task-pull-requests-section-organism';
import {
    requisitionSummary,
    WTaskRequisitionSectionOrganism,
} from '@/components/organisms/w-task-requisition-section-organism/w-task-requisition-section-organism';
import { WAppLayoutTemplate } from '@/components/templates/w-app-layout-template/w-app-layout-template';
import { useBoardMove } from '@/hooks/use-board-move';
import { countCriteria, deleteTaskText, detailToBoardCard } from '@/lib/ficha';
import type { SharedProps } from '@/types';
import type {
    BoardAbilities,
    BoardProject,
    BoardStage,
    MoveIntent,
    MovePayload,
    TaskDetail,
    TaskDetailOptions,
    TaskPatchPayload,
} from '@/types/quadro';

export interface WTaskDetailPageProps {
    project: BoardProject;
    /** A ficha, como o `TaskPresenter::detail()` manda. */
    task: TaskDetail;
    options: TaskDetailOptions;
    /** Hoje, `AAAA-MM-DD` no fuso de São Paulo (início proposto da *Nova sprint*, R5). */
    today: string;
}

/** As seções da ficha, na ordem da tela; cada uma começa recolhida (R4) e abre sozinha quando o servidor devolve erro nela. */
type Section =
    'requisition' | 'criteria' | 'pull_requests' | 'comments' | 'attachments' | 'attributes' | 'people' | 'hours' | 'parent' | 'origin';

/** Em que seção cada campo do payload mora: o erro do servidor abre a seção certa. */
const FIELD_SECTION: Record<string, Section> = {
    story_as: 'requisition',
    story_want: 'requisition',
    story_so_that: 'requisition',
    description: 'requisition',
    type_id: 'attributes',
    area_id: 'attributes',
    priority: 'attributes',
    developer_id: 'people',
    qa_id: 'people',
    user_id: 'people',
    estimated_hours: 'hours',
    hours: 'hours',
    parent_task_id: 'parent',
    origin_task_id: 'origin',
    url: 'pull_requests',
    body: 'comments',
    file: 'attachments',
    cover_attachment_id: 'attachments',
};

type RequestPayload = NonNullable<Parameters<typeof router.post>[1]>;
type Visit = Parameters<typeof router.post>[2];

/**
 * A ficha do card (Inertia: `projects/task`, `/projetos/{id}/cards/{chave}`; F3 §02 a §04, F5 §02):
 * o cabeçalho sempre visível e as seções recolhidas (R4), cada uma um organismo que só devolve
 * intenção. Uma escrita por vez (`pending`): o Inertia cancelaria a anterior.
 */
export function WTaskDetailPage({ project, task, options, today }: WTaskDetailPageProps) {
    const currentUserId = usePage<SharedProps>().props.auth.user?.id ?? null;
    const base = `/projetos/${project.id}/cards/${task.key}`;

    const [pending, setPending] = useState<string | null>(null);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [open, setOpen] = useState<Partial<Record<Section, boolean>>>({});
    const [archiving, setArchiving] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const busy = pending !== null;
    const openSection = (section: Section) => setOpen((state) => ({ ...state, [section]: true }));

    /** Toda escrita da ficha passa aqui: trava, limpa os erros, e no erro abre a seção do campo. */
    const visit = (name: string, extra?: Visit): Visit => ({
        preserveScroll: true,
        ...extra,
        onStart: () => {
            setPending(name);
            setErrors({});
        },
        onError: (serverErrors) => {
            setErrors(serverErrors);
            const section = Object.keys(serverErrors)
                .map((field) => FIELD_SECTION[field])
                .find(Boolean);
            if (section) openSection(section);
            else toast.error(Object.values(serverErrors)[0] ?? `Não foi possível salvar ${task.key}`);
            extra?.onError?.(serverErrors);
        },
        onFinish: (event) => {
            setPending(null);
            extra?.onFinish?.(event);
        },
    });

    const patch = (payload: TaskPatchPayload, name = 'patch') => router.patch(base, { ...payload }, visit(name));
    const post = (path: string, data: RequestPayload = {}, name = path, extra?: Visit) =>
        router.post(`${base}${path}`, data, visit(name, extra));
    const destroy = (path: string, name = path, extra?: Visit) => router.delete(`${base}${path}`, visit(name, extra));

    // *Mover para…*: os mesmos diálogos do quadro (nova sprint, pausa, retrabalho, aviso do Done).
    const boardAbilities: BoardAbilities = {
        create_card: false,
        create_support: false,
        manage_sprint: task.abilities.manage_sprint,
        move: task.abilities.move,
        archive: task.abilities.archive,
        manage_types: task.abilities.manage_types,
        manage_areas: task.abilities.manage_areas,
    };
    const move = useBoardMove({
        project,
        abilities: boardAbilities,
        options,
        today,
        processing: pending === 'move',
        errors: pending === null ? errors : undefined,
        onDenied: (reason) => toast.error(reason),
        onCommit: (_intent: MoveIntent, payload: MovePayload) =>
            post('/mover', { ...payload }, 'move', {
                onSuccess: () => move.cancel(),
                onError: (serverErrors) => {
                    if (!move.step) toast.error(Object.values(serverErrors)[0] ?? `Não foi possível mover ${task.key}`);
                },
            }),
    });
    const card = detailToBoardCard(task);
    const startMove = (to: BoardStage) => move.start({ card, from: task.stage, to, index: null, sequence: null });

    const archive = (payload: ArchivePayload) => post('/arquivar', { ...payload }, 'archive', { onSuccess: () => setArchiving(false) });

    const section = (id: Section, title: string, summary: string, count: number | undefined, children: ReactNode) => (
        <WCollapsibleSectionMolecule
            title={title}
            summary={summary}
            count={count}
            open={open[id] ?? false}
            onOpenChange={(next) => setOpen((state) => ({ ...state, [id]: next }))}
        >
            {children}
        </WCollapsibleSectionMolecule>
    );

    return (
        <WAppLayoutTemplate
            eyebrow={`Projetos · ${project.name}`}
            title={task.key}
            subtitle={`Quadro de ${project.key_prefix}`}
            width="wide"
        >
            <Head title={`${task.key} · ${task.title}`} />

            <div className="grid gap-4">
                <WTaskDetailHeaderOrganism
                    detail={task}
                    stages={options.stages}
                    busy={busy}
                    errors={errors}
                    onRenameTitle={(title) => patch({ title }, 'title')}
                    onMove={startMove}
                    onArchive={() => setArchiving(true)}
                    onUnarchive={() => post('/desarquivar', {}, 'unarchive')}
                    onDelete={() => setDeleting(true)}
                    types={options.types}
                    areas={options.areas}
                    onChangeType={(type) => patch({ type_id: type.id }, 'attributes')}
                    onCreateType={(payload) => router.post(`/projetos/${project.id}/tipos`, { ...payload }, visit('type'))}
                    onUpdateType={(type, payload) =>
                        router.put(`/projetos/${project.id}/tipos/${type.id}`, { ...payload }, visit('type'))
                    }
                    onChangeArea={(area) => patch({ area_id: area?.id ?? null }, 'attributes')}
                    onCreateArea={(payload) => router.post(`/projetos/${project.id}/areas`, { ...payload }, visit('area'))}
                    onUpdateArea={(area, payload) =>
                        router.put(`/projetos/${project.id}/areas/${area.id}`, { ...payload }, visit('area'))
                    }
                />

                <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,22rem)] lg:items-start">
                    <div className="grid min-w-0 gap-3">
                        {section(
                            'requisition',
                            'Requisição',
                            requisitionSummary(task.requisition),
                            undefined,
                            <WTaskRequisitionSectionOrganism
                                requisition={task.requisition}
                                canEdit={task.abilities.update}
                                busy={busy}
                                errors={errors}
                                onSave={(payload) => patch(payload, 'requisition')}
                            />,
                        )}
                        {section(
                            'criteria',
                            'Critérios de aceite',
                            criteriaSummary(task.criteria),
                            countCriteria(task.criteria),
                            <WTaskCriteriaSectionOrganism
                                criteria={task.criteria}
                                canEdit={task.abilities.update}
                                busy={busy}
                                errors={errors}
                                onAdd={(payload) => post('/criterios', { ...payload }, 'criteria')}
                                onUpdate={(id, description) => router.patch(`${base}/criterios/${id}`, { description }, visit('criteria'))}
                                onDelete={(id) => destroy(`/criterios/${id}`, 'criteria')}
                            />,
                        )}
                        {section(
                            'pull_requests',
                            'PRs',
                            pullRequestsSummary(task.pull_requests),
                            task.pull_requests.length,
                            <WTaskPullRequestsSectionOrganism
                                pullRequests={task.pull_requests}
                                canEdit={task.abilities.update}
                                busy={busy}
                                errors={errors}
                                onAdd={(url) => post('/prs', { url }, 'pull_requests')}
                                onRemove={(id) => destroy(`/prs/${id}`, 'pull_requests')}
                            />,
                        )}
                        {section(
                            'comments',
                            'Comentários',
                            commentsSummary(task.comments),
                            task.comments.length,
                            <WTaskCommentsSectionOrganism
                                comments={task.comments}
                                canComment={task.abilities.comment}
                                busy={busy}
                                errors={errors}
                                onAdd={(body) => post('/comentarios', { body }, 'comments')}
                                onUpdate={(id, body) => router.patch(`${base}/comentarios/${id}`, { body }, visit('comments'))}
                                onDelete={(id) => destroy(`/comentarios/${id}`, 'comments')}
                            />,
                        )}
                        {section(
                            'attachments',
                            'Anexos',
                            attachmentsSummary(task.attachments, task.cover_attachment_id),
                            task.attachments.length,
                            <WTaskAttachmentsSectionOrganism
                                attachments={task.attachments}
                                coverId={task.cover_attachment_id}
                                canAttach={task.abilities.attach}
                                canEdit={task.abilities.update}
                                busy={busy}
                                errors={errors}
                                onUpload={(files) => post('/anexos', { files }, 'attachments', { forceFormData: true })}
                                onRemove={(id) => destroy(`/anexos/${id}`, 'attachments')}
                                onSetCover={(id) => patch({ cover_attachment_id: id }, 'cover')}
                            />,
                        )}
                    </div>

                    <div className="grid min-w-0 gap-3">
                        {section(
                            'attributes',
                            'Tipo, área e prioridade',
                            attributesSummary(task.type, task.area, task.priority),
                            undefined,
                            <WTaskAttributesSectionOrganism
                                type={task.type}
                                area={task.area}
                                priority={task.priority}
                                support={task.support}
                                types={options.types}
                                areas={options.areas}
                                canEdit={task.abilities.update}
                                busy={busy}
                                errors={errors}
                                onChange={(changes) => patch(changes, 'attributes')}
                            />,
                        )}
                        {section(
                            'people',
                            'Pessoas',
                            peopleSummary(task.developer, task.qa, task.participants),
                            undefined,
                            <WTaskPeopleSectionOrganism
                                taskKey={task.key}
                                developer={task.developer}
                                qa={task.qa}
                                participants={task.participants}
                                members={options.members}
                                currentUserId={currentUserId}
                                canEdit={task.abilities.update}
                                busy={busy}
                                errors={errors}
                                onChangeDeveloper={(person) => patch({ developer_id: person?.id ?? null }, 'people')}
                                onChangeQa={(person) => patch({ qa_id: person?.id ?? null }, 'people')}
                                onToggleParticipant={(person, add) => post('/participantes', { user_id: person.id, add }, 'people')}
                            />,
                        )}
                        {section(
                            'hours',
                            'Horas previstas',
                            hoursSummary(task),
                            undefined,
                            <WTaskHoursSectionOrganism
                                detail={task}
                                abilities={task.abilities}
                                busy={busy}
                                errors={errors}
                                onSetHours={(hours) => patch({ estimated_hours: hours }, 'hours')}
                                onPropose={(hours) => post('/horas/propor', { hours }, 'hours')}
                                onApprove={() => post('/horas/aprovar', {}, 'hours')}
                                onReject={() => post('/horas/recusar', {}, 'hours')}
                            />,
                        )}
                        {section(
                            'parent',
                            'Card pai e cards filhos',
                            parentSummary(task.parent, task.children),
                            task.children.length > 0 ? task.children.length : undefined,
                            <WTaskParentSectionOrganism
                                taskKey={task.key}
                                parent={task.parent}
                                children={task.children}
                                cards={options.cards}
                                canEdit={task.abilities.update}
                                busy={busy}
                                errors={errors}
                                onChangeParent={(parent) => patch({ parent_task_id: parent?.id ?? null }, 'parent')}
                            />,
                        )}
                        {section(
                            'origin',
                            'Tarefa de origem',
                            originSummary(task.origin_task, task.production_bugs),
                            task.production_bugs.length > 0 ? task.production_bugs.length : undefined,
                            <WTaskOriginSectionOrganism
                                originTask={task.origin_task}
                                productionBugs={task.production_bugs}
                                cards={options.cards}
                                canEdit={task.abilities.update}
                                busy={busy}
                                errors={errors}
                                onChangeOrigin={(origin) => patch({ origin_task_id: origin?.id ?? null }, 'origin')}
                            />,
                        )}
                    </div>
                </div>
            </div>

            {move.dialogs}

            {task.abilities.archive && (
                <WArchiveDialogOrganism
                    open={archiving}
                    onOpenChange={(next) => !next && pending !== 'archive' && setArchiving(false)}
                    card={card}
                    loading={pending === 'archive'}
                    errors={errors}
                    onConfirm={archive}
                />
            )}

            {task.abilities.delete && (
                <WConfirmDialogMolecule
                    open={deleting}
                    onOpenChange={(next) => !next && pending !== 'delete' && setDeleting(false)}
                    title={`Excluir ${task.key}?`}
                    description={deleteTaskText(task.key)}
                    confirmLabel="Excluir de verdade"
                    destructive
                    loading={pending === 'delete'}
                    onConfirm={() => destroy('', 'delete')}
                />
            )}
        </WAppLayoutTemplate>
    );
}
