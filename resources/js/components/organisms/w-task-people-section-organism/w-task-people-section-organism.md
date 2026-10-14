# WTaskPeopleSectionOrganism

Organismo · `organisms/w-task-people-section-organism/w-task-people-section-organism.tsx` · story `Organisms/Task People Section`

## O que faz

O conteúdo da seção _Pessoas_ da ficha do card (F3 §02, §04; P20, P22, P58, P5). A página o põe dentro do `WCollapsibleSectionMolecule`, com o resumo de `peopleSummary()` no cabeçalho recolhido.

- **Dev** e **QA**: um `Select` do shadcn cada, dentro do `WFormFieldMolecule`, com "Ninguém" (valor `'none'`) e as pessoas alocadas e ativas (`members`, P20). Projeto sem ninguém alocado: item desabilitado "Nenhuma pessoa alocada no projeto".
- Escolher **outra pessoa** (diferente da atual e de quem está logado) abre o `WConfirmDialogMolecule` "Definir Bruno Lima como dev de SGM-17?" / "Bruno Lima será avisado no sistema e por e-mail" (`assignNoticeText`, P22), botão _Definir_; o callback só sai no _Definir_. **A si mesmo** ou **"Ninguém"**: grava direto, sem diálogo (F3 §04, §10.5).
- O `Select` fica sempre no valor da prop: ele não "pula" para a pessoa escolhida antes do _Definir_ nem antes da página gravar.
- **Participantes** (P58): lista (`ul/li`) com avatar, nome e _Remover_ (`aria-label` "Remover Fulano dos participantes"); _Adicionar participante_ abre um Popover com as pessoas alocadas em caixas de marcar, o dev e o QA marcados e desabilitados com "Dev · já está no card". Sem aviso e sem confirmação.
- Sem `canEdit`: os três em texto (`dl`), sem campo nem botão.
- `busy`: tudo desabilitado, `aria-busy` no bloco, `Loader2` no controle que disparou.
- Erros do servidor: `developer_id` e `qa_id` embaixo do campo; `user_id` (participante) embaixo da lista.

`peopleSummary(developer, qa, participants)`: "Dev Bruno Lima · QA Carla Mendes · 2 participantes", ou "Ninguém no card".

## Props

| Prop                  | Tipo                                        | Padrão  | Obrigatória | Para quê                                                |
| --------------------- | ------------------------------------------- | ------- | ----------- | ------------------------------------------------------- |
| `taskKey`             | `string`                                    |         | sim         | A chave no título da confirmação                        |
| `developer`           | `PersonRef \| null`                         |         | sim         | `detail.developer`                                      |
| `qa`                  | `PersonRef \| null`                         |         | sim         | `detail.qa`                                             |
| `participants`        | `PersonRef[]`                               |         | sim         | `detail.participants`                                   |
| `members`             | `PersonRef[]`                               |         | sim         | `options.members`: alocadas e ativas                    |
| `currentUserId`       | `number \| null`                            |         | sim         | `auth.user.id`: quem se define a si mesmo não é avisado |
| `canEdit`             | `boolean`                                   |         | sim         | `abilities.update`                                      |
| `busy`                | `boolean`                                   | `false` | não         | Uma gravação da ficha está rodando                      |
| `errors`              | `Record<string, string>`                    |         | não         | `developer_id`, `qa_id`, `user_id`                      |
| `onChangeDeveloper`   | `(person: PersonRef \| null) => void`       |         | sim         | `null` = "Ninguém"                                      |
| `onChangeQa`          | `(person: PersonRef \| null) => void`       |         | sim         | `null` = "Ninguém"                                      |
| `onToggleParticipant` | `(person: PersonRef, add: boolean) => void` |         | sim         | Pôr (`true`) ou tirar (`false`) participante            |

## Exemplo

```tsx
<WCollapsibleSectionMolecule title="Pessoas" summary={peopleSummary(detail.developer, detail.qa, detail.participants)}>
    <WTaskPeopleSectionOrganism
        taskKey={detail.key}
        developer={detail.developer}
        qa={detail.qa}
        participants={detail.participants}
        members={options.members}
        currentUserId={auth.user?.id ?? null}
        canEdit={detail.abilities.update}
        busy={processing}
        errors={errors}
        onChangeDeveloper={(person) => router.patch(detail.url, { developer_id: person?.id ?? null })}
        onChangeQa={(person) => router.patch(detail.url, { qa_id: person?.id ?? null })}
        onToggleParticipant={(person, add) =>
            add
                ? router.post(`${detail.url}/participantes`, { user_id: person.id })
                : router.delete(`${detail.url}/participantes/${person.id}`)
        }
    />
</WCollapsibleSectionMolecule>
```

## Cuidados

- Quem manda o aviso (no sistema e por e-mail) é o servidor (P22, R3); a confirmação só conta à pessoa que ele vai sair.
- Se o dev ou o QA atual não estiver em `members` (saiu do projeto e o servidor ainda não limpou, P5), ele entra nas opções para o `Select` mostrar o nome.
- Quem vira dev ou QA não fica também como participante (F3 §04): limpar a lista é do servidor.
- O Popover de participantes é o mesmo desenho do _+_ do `WPeopleAvatarsMolecule`; aqui com um botão com texto, porque a lista já mostra quem está.
