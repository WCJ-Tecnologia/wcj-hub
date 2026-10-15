# WSprintFieldsMolecule

Molécula · `molecules/w-sprint-fields-molecule/w-sprint-fields-molecule.tsx` · story `Molecules/Sprint Fields`

## O que faz

Os campos da sprint do F5 §02, controlados: Nome (até 80), Objetivo (opcional, até 500), Início, Fim e Dias úteis (só leitura, seg–sex inclusive, sem feriado, P29, P48). Com `followStart`, o fim acompanha o início pela duração padrão do projeto (`addBusinessDays(início, sprintDays)`, P46) até a pessoa mexer no fim. Fim antes do início aparece na hora: "O fim não pode ser antes do início".

Mesmo visual do `WNewSprintDialogOrganism` do kit do quadro. Usada em _Nova sprint_ e _Editar sprint_ (`WSprintFormDialogOrganism`) e na próxima sprint do `WCloseSprintDialogOrganism`.

## Props

| Prop          | Tipo                                          | Padrão  | Obrigatória | Para quê                                        |
| ------------- | --------------------------------------------- | ------- | ----------- | ----------------------------------------------- |
| `value`       | `SprintFields`                                |         | sim         | `{ name, goal, starts_on, ends_on }`            |
| `onChange`    | `(value: SprintFields) => void`               |         | sim         | o valor inteiro, a cada mudança                 |
| `sprintDays`  | `number`                                      |         | sim         | duração padrão do projeto (P46)                 |
| `followStart` | `boolean`                                     | `true`  | não         | `false` na edição: o fim não acompanha o início |
| `errors`      | `Partial<Record<keyof SprintFields, string>>` |         | não         | erros do servidor por campo                     |
| `tried`       | `boolean`                                     | `false` | não         | mostra os obrigatórios vazios                   |
| `disabled`    | `boolean`                                     | `false` | não         | gravando                                        |

`sprintFieldErrors(value)` devolve os erros da tela (obrigatórios e fim antes do início), para o diálogo decidir se manda.

## Cuidados

- Nome repetido no projeto é o servidor que diz ("Já existe a Sprint 4 neste projeto").
- Datas em `AAAA-MM-DD`, sem passar pelo fuso do navegador (`lib/dias-uteis.ts`).
