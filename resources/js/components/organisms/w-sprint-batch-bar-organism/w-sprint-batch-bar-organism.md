# WSprintBatchBarOrganism

Organismo · `organisms/w-sprint-batch-bar-organism/w-sprint-batch-bar-organism.tsx` · story `Organisms/Sprint Batch Bar`

## O que faz

A barra do _Montar sprint_ do quadro (D-GER-35), que aparece enquanto o modo de seleção está ligado. Fica presa no pé da área do conteúdo (`sticky`, sem cobrir a barra lateral) e mostra o resumo das marcadas no Backlog, pelo `batchSummaryText()` de `lib/quadro.ts`: "3 selecionadas · 52 h previstas (1 sem horas)". O total é só referência (P49): nada olha a capacidade.

Botões: _Cancelar_ (sai do modo sem gravar) e _Iniciar sprint_ (sem sprint aberta) ou _Pôr na Sprint N_ (com sprint aberta), desabilitado sem nenhuma marcada. O botão principal só abre o `WStartSprintDialogOrganism`; quem grava é a página.

## Props

| Prop        | Tipo                                   | Obrigatória | Para quê                    |
| ----------- | -------------------------------------- | ----------- | --------------------------- |
| `project`   | `Pick<BoardProject, 'open_sprint'>`    | sim         | o rótulo do botão principal |
| `selected`  | `Pick<BoardCard, 'estimated_hours'>[]` | sim         | as marcadas, para o resumo  |
| `onCancel`  | `() => void`                           | sim         | sair do modo                |
| `onConfirm` | `() => void`                           | sim         | abrir a confirmação         |
| `className` | `string`                               | não         |                             |

## Cuidados

- `role="region"` com o nome "Montar sprint", e o resumo em `role="status"`: o leitor de tela ouve a contagem mudar a cada marca.
- No celular os botões empilham, o principal em cima.
