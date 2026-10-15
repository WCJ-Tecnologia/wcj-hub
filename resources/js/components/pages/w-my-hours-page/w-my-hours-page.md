# WMyHoursPage

Página · `pages/w-my-hours-page/w-my-hours-page.tsx` · Inertia `time-entries/index` · rota `/minhas-horas` · story `Pages/Minhas horas`

## O que faz

_Minhas horas_ (F5 §02 a §05; P30): os lançamentos de quem está logado no período, com Data, Projeto, Tarefa (chave e título) e Horas, do mais recente para o mais antigo; o total por dia e o total do período. Período padrão: a semana atual, de segunda a domingo; atalhos _Semana atual_, _Semana anterior_ e _Mês atual_; filtro por projeto e busca por chave ou título. _Lançar horas_ e _Corrigir_ abrem o `WTimeEntryFormDialogOrganism`; _Excluir_ pede "Excluir 2,5 h de 06/10 em SGM-17?".

Porta de entrada: _Minhas horas_ no menu principal, grupo Projetos, para todos menos o Comercial (C14).

## Props

Do `TimeEntryController@index`: `entries`, `totals`, `projects`, `tasks`, `filters`, `today` (ver `types/horas.ts`).

## Cuidados

- As contas de semana e mês são em UTC sobre `AAAA-MM-DD`, como o `lib/dias-uteis.ts`, para não cair no dia anterior.
- O `can` de cada linha é da `TimeEntryPolicy`: quem saiu do projeto não corrige mais o próprio lançamento ali.
