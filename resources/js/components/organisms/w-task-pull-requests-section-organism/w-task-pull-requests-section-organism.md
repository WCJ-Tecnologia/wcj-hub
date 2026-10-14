# WTaskPullRequestsSectionOrganism

Organismo · `organisms/w-task-pull-requests-section-organism/w-task-pull-requests-section-organism.tsx` · story `Organisms/Task Pull Requests Section`

## O que faz

O conteúdo da seção _PRs_ da ficha do card (F3 §02, §03, §04; P23). A página o envolve no `WCollapsibleSectionMolecule`, com `pullRequestsSummary(pullRequests)` no cabeçalho recolhido.

- Lista (`ul`) na ordem em que os links entraram. Cada um: o link externo (`target="_blank"`, `rel="noreferrer"`, ícone `ExternalLink`, "(abre em nova aba)" para o leitor de tela) com o texto encurtado, por exemplo "github.com/WCJ-Tecnologia/sgm · #118"; o `href` e o `title` levam a URL inteira. Abaixo, "por Bruno Lima em 09/10/2026 10:12".
- Com `canEdit`: o botão _Remover_ (`X`, `aria-label="Remover <url>"`), **sem confirmação** (colar de novo é barato), e o campo _Link do PR_ (até 500 caracteres, `inputMode="url"`) com _Adicionar_.
- Antes de chamar `onAdd`, o campo confere, nesta ordem: vazio ("Informe o link do PR."); repetido no card, pelo `hasPullRequest` ("Este PR já está no card", `PR_ALREADY_TEXT`); sem `http://` ou `https://` ("Informe o link completo, começando com https://").
- Vazio: "Nenhum PR." e, com `canEdit`, o link _Adicionar link_, que põe o foco no campo (F3 §03).

`shortPullRequestUrl(url)` (exportada) encurta: PR do GitHub (`/pull/118`), merge request do GitLab (`/-/merge_requests/7`) e do Bitbucket (`/pull-requests/7`) viram "domínio/caminho · #n"; outro link vira domínio + caminho, sem `www.`; o que não se lê como URL sai como veio.

## Props

| Prop           | Tipo                     | Padrão  | Obrigatória | Para quê                                                                   |
| -------------- | ------------------------ | ------- | ----------- | -------------------------------------------------------------------------- |
| `pullRequests` | `TaskPullRequest[]`      |         | sim         | Na ordem em que entraram                                                   |
| `canEdit`      | `boolean`                |         | sim         | `abilities.update`: campo, _Adicionar_ e _Remover_                         |
| `busy`         | `boolean`                | `false` | não         | Uma gravação da ficha rodando: desabilita Adicionar e Remover; `aria-busy` |
| `errors`       | `Record<string, string>` |         | não         | Erro do servidor em `url`                                                  |
| `onAdd`        | `(url: string) => void`  |         | sim         | O link, sem espaço nas pontas                                              |
| `onRemove`     | `(id: number) => void`   |         | sim         | Remove na hora, sem confirmação                                            |

`pullRequestsSummary(pullRequests)` devolve "2 PRs", "1 PR" ou "Nenhum PR".

## Exemplo

```tsx
import {
    pullRequestsSummary,
    WTaskPullRequestsSectionOrganism,
} from '@/components/organisms/w-task-pull-requests-section-organism/w-task-pull-requests-section-organism';

<WCollapsibleSectionMolecule title="PRs" summary={pullRequestsSummary(task.pull_requests)}>
    <WTaskPullRequestsSectionOrganism
        pullRequests={task.pull_requests}
        canEdit={task.abilities.update}
        busy={processing}
        errors={errors}
        onAdd={(url) => post(`${task.url}/prs`, { url })}
        onRemove={(id) => destroy(`${task.url}/prs/${id}`)}
    />
</WCollapsibleSectionMolecule>;
```

## Cuidados

- **Quem recusa o repetido é o servidor** (`unique (task_id, url)`, P23). A checagem local só evita a ida ao servidor e usa a mesma normalização (`normalizeUrl`: espaço nas pontas e barra final).
- **Quando o campo limpa.** Só quando a página troca `pullRequests` com um PR a mais depois do _Adicionar_; com erro do servidor, o link fica no campo. A troca é detectada durante a renderização, sem `useEffect`.
- **Erro local antes do servidor.** Enquanto a pessoa corrige o campo, o erro local (recalculado a cada tecla) tem prioridade sobre o `errors.url`.
