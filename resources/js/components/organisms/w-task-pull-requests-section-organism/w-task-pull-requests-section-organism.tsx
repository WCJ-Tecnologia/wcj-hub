import { ExternalLink, Loader2, Plus, X } from 'lucide-react';
import { type FormEvent, useId, useRef, useState } from 'react';

import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Button } from '@/components/shadcn/button';
import { Input } from '@/components/shadcn/input';
import { dateTimeBr, hasPullRequest, PR_ALREADY_TEXT } from '@/lib/ficha';
import type { TaskPullRequest } from '@/types/quadro';

export const PULL_REQUEST_URL_MAX = 500;

export interface WTaskPullRequestsSectionOrganismProps {
    pullRequests: TaskPullRequest[];
    canEdit: boolean;
    busy?: boolean;
    /** Erros do servidor: `url`. */
    errors?: Record<string, string>;
    onAdd: (url: string) => void;
    onRemove: (id: number) => void;
}

/** Linha de resumo da seção recolhida: "2 PRs", "1 PR" ou "Nenhum PR". */
export function pullRequestsSummary(pullRequests: TaskPullRequest[]): string {
    if (pullRequests.length === 0) return 'Nenhum PR';
    return pullRequests.length === 1 ? '1 PR' : `${pullRequests.length} PRs`;
}

/**
 * O link encurtado para a lista: "github.com/WCJ-Tecnologia/sgm · #118". Reconhece o PR do GitHub
 * (`/pull/118`), o merge request do GitLab (`/-/merge_requests/7`) e o do Bitbucket (`/pull-requests/7`);
 * outro link sai como domínio + caminho. Link que não se lê como URL sai como veio.
 */
export function shortPullRequestUrl(url: string): string {
    let parsed: URL;
    try {
        parsed = new URL(url.trim());
    } catch {
        return url;
    }
    const host = parsed.hostname.replace(/^www\./, '');
    const path = parsed.pathname.replace(/\/+$/, '');
    const match = path.match(/^(.*?)(?:\/-\/merge_requests|\/pull-requests|\/pull)\/(\d+)$/);
    return match ? `${host}${match[1]} · #${match[2]}` : `${host}${path}`;
}

const URL_REQUIRED = 'Informe o link do PR.';
const URL_SCHEME = 'Informe o link completo, começando com https://';

/**
 * Seção *PRs* da ficha (F3 §02, §03, §04; P23): os links na ordem em que entraram, com quem colou e
 * quando, e o campo *Link do PR* para quem edita. O link repetido é barrado aqui com a frase do F3, mas
 * quem recusa de verdade é o servidor (`unique (task_id, url)`). Remover não pede confirmação.
 */
export function WTaskPullRequestsSectionOrganism({
    pullRequests,
    canEdit,
    busy = false,
    errors,
    onAdd,
    onRemove,
}: WTaskPullRequestsSectionOrganismProps) {
    const fieldId = useId();
    const inputRef = useRef<HTMLInputElement>(null);
    const [url, setUrl] = useState('');
    const [tried, setTried] = useState(false);
    const [pendingCount, setPendingCount] = useState<number | null>(null);
    const [lastAction, setLastAction] = useState<string | null>(null);

    // Limpa o campo quando a página troca `pullRequests` com o PR novo (ajuste de estado na renderização).
    const [seen, setSeen] = useState(pullRequests);
    if (seen !== pullRequests) {
        setSeen(pullRequests);
        if (pendingCount !== null && pullRequests.length > pendingCount) {
            setUrl('');
            setTried(false);
            setPendingCount(null);
        }
    }

    const value = url.trim();
    const localError = !tried
        ? undefined
        : value === ''
          ? URL_REQUIRED
          : hasPullRequest(pullRequests, value)
            ? PR_ALREADY_TEXT
            : !/^https?:\/\//i.test(value)
              ? URL_SCHEME
              : undefined;
    const error = localError ?? errors?.url;

    const spinning = (key: string) => busy && lastAction === key;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setTried(true);
        if (busy || value === '' || hasPullRequest(pullRequests, value) || !/^https?:\/\//i.test(value)) return;
        setLastAction('add');
        setPendingCount(pullRequests.length);
        onAdd(value);
    };

    return (
        <div data-slot="w-task-pull-requests-section-organism" aria-busy={busy || undefined} className="space-y-3">
            {pullRequests.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    Nenhum PR.{' '}
                    {canEdit && (
                        <Button variant="link" className="h-auto p-0" onClick={() => inputRef.current?.focus()}>
                            Adicionar link
                        </Button>
                    )}
                </p>
            ) : (
                <ul aria-label="PRs do card" className="divide-y divide-border">
                    {pullRequests.map((pr) => (
                        <li key={pr.id} className="flex items-start gap-2 py-2">
                            <div className="min-w-0 flex-1">
                                <a
                                    href={pr.url}
                                    target="_blank"
                                    rel="noreferrer"
                                    title={pr.url}
                                    className="inline-flex max-w-full items-center gap-1.5 text-sm font-medium text-primary-text underline-offset-4 hover:underline"
                                >
                                    <ExternalLink className="size-3.5 shrink-0" aria-hidden />
                                    <span className="truncate">{shortPullRequestUrl(pr.url)}</span>
                                    <span className="sr-only">(abre em nova aba)</span>
                                </a>
                                <p className="text-xs text-muted-foreground">
                                    por {pr.added_by?.name ?? '—'} em {dateTimeBr(pr.added_at)}
                                </p>
                            </div>
                            {canEdit && (
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="size-8 shrink-0 text-muted-foreground"
                                    aria-label={`Remover ${pr.url}`}
                                    disabled={busy}
                                    onClick={() => {
                                        setLastAction(`remove-${pr.id}`);
                                        onRemove(pr.id);
                                    }}
                                >
                                    {spinning(`remove-${pr.id}`) ? <Loader2 className="animate-spin" aria-hidden /> : <X aria-hidden />}
                                </Button>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            {canEdit && (
                <form noValidate onSubmit={submit} className="flex items-start gap-2">
                    <WFormFieldMolecule label="Link do PR" htmlFor={fieldId} error={error} className="min-w-0 flex-1">
                        <Input
                            ref={inputRef}
                            value={url}
                            maxLength={PULL_REQUEST_URL_MAX}
                            inputMode="url"
                            autoComplete="off"
                            placeholder="https://github.com/…/pull/123"
                            onChange={(event) => setUrl(event.target.value)}
                        />
                    </WFormFieldMolecule>
                    <Button type="submit" variant="outline" className="mt-5 h-10" disabled={busy}>
                        {spinning('add') ? <Loader2 className="animate-spin" aria-hidden /> : <Plus aria-hidden />}
                        Adicionar
                    </Button>
                </form>
            )}
        </div>
    );
}
