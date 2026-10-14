# WTaskCommentsSectionOrganism

Organismo · `organisms/w-task-comments-section-organism/w-task-comments-section-organism.tsx` · story `Organisms/Task Comments Section`

## O que faz

O conteúdo da seção _Comentários_ da ficha do card (F3 §02, §03, §04; P24, decisão 10.10). A página o envolve no `WCollapsibleSectionMolecule`, com `commentsSummary(comments)` no cabeçalho recolhido.

- Lista (`ul`) do mais antigo para o mais novo. Cada comentário: avatar com as iniciais (decorativo), nome do autor, data e hora (`dateTimeBr`), "(editado)" discreto quando `edited_at` (a data da edição no `title`), e o corpo com as quebras de linha.
- Com `comment.can.update` (só o autor, 10.10): _Editar_ troca o corpo por um campo em linha, com _Salvar_ e _Cancelar_ (Ctrl/Cmd+Enter salva, Esc cancela).
- Com `comment.can.delete`: _Excluir_ confirma pelo `WConfirmDialogMolecule` destrutivo, com o título "Excluir o comentário?" (`DELETE_COMMENT_TEXT`) e, na descrição, as primeiras ~80 letras do comentário entre aspas (`commentExcerpt`).
- Com `canComment`: o campo _Novo comentário_ no fim e o botão _Comentar_ (Ctrl/Cmd+Enter também envia). Vazio ao enviar: "Escreva o comentário.".
- Vazio: "Nenhum comentário." e, com `canComment`, o link _Comentar_, que põe o foco no campo (F3 §03).

## Props

| Prop         | Tipo                                 | Padrão  | Obrigatória | Para quê                                                                          |
| ------------ | ------------------------------------ | ------- | ----------- | --------------------------------------------------------------------------------- |
| `comments`   | `TaskComment[]`                      |         | sim         | Do mais antigo para o mais novo; `can` decide editar e excluir                    |
| `canComment` | `boolean`                            |         | sim         | `abilities.comment`: mostra o campo de novo comentário                            |
| `busy`       | `boolean`                            | `false` | não         | Uma gravação da ficha rodando: desabilita Comentar, Salvar e Excluir; `aria-busy` |
| `errors`     | `Record<string, string>`             |         | não         | Erro do servidor em `body`                                                        |
| `onAdd`      | `(body: string) => void`             |         | sim         | O comentário novo, sem espaço nas pontas                                          |
| `onUpdate`   | `(id: number, body: string) => void` |         | sim         | O corpo novo (o antes fica na auditoria, P24)                                     |
| `onDelete`   | `(id: number) => void`               |         | sim         | Depois da confirmação                                                             |

`commentsSummary(comments)` devolve "3 comentários · último em 13/10/2026 15:02" (ou "1 comentário · …"); sem comentário, "Nenhum comentário". `commentExcerpt(body, max = 80)` também é exportada.

## Exemplo

```tsx
import {
    commentsSummary,
    WTaskCommentsSectionOrganism,
} from '@/components/organisms/w-task-comments-section-organism/w-task-comments-section-organism';

<WCollapsibleSectionMolecule title="Comentários" summary={commentsSummary(task.comments)}>
    <WTaskCommentsSectionOrganism
        comments={task.comments}
        canComment={task.abilities.comment}
        busy={processing}
        errors={errors}
        onAdd={(body) => post(`${task.url}/comentarios`, { body })}
        onUpdate={(id, body) => patch(`${task.url}/comentarios/${id}`, { body })}
        onDelete={(id) => destroy(`${task.url}/comentarios/${id}`)}
    />
</WCollapsibleSectionMolecule>;
```

## Cuidados

- **Quando o campo limpa.** O _Novo comentário_ só limpa quando a página troca `comments` com um comentário a mais depois do _Comentar_; o editor só fecha quando o comentário volta com o corpo novo (ou some). Com erro do servidor, o texto fica. A troca de `comments` é detectada durante a renderização (padrão "ajustar estado quando a prop muda", no lugar de um `useEffect` em `comments.length`, que a regra `react-hooks/set-state-in-effect` recusa). Um comentário de outra pessoa que chegue por recarga não apaga o que se está escrevendo: só limpa quem mandou.
- **De quem é o `errors.body`.** Do editor em linha, se a última gravação foi a edição dele; senão, do campo novo.
- **Quem decide é o servidor.** `comment.can` vem da Policy; os botões escondidos são conveniência (inegociável 9).
