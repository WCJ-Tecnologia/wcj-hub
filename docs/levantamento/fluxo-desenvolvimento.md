# Levantamento: fluxo de desenvolvimento e colunas do quadro

Recebidos em 24/09/2026, depois da reunião de Projetos. As duas entradas são do **Lucas Alencar** (pessoa técnica, D-GER-01), repassadas por Lucas Scholz.

## Entradas

**A. Fluxo normal de uma tarefa**:

1. Dev pega a demanda
2. Desenvolve
3. Faz os commits e os pushes na branch
4. Abre PR para a `main`
5. Code review
6. Teste cruzado (outro dev)
7. QA
8. Merge
9. Libera em homologação
10. Homologa o pacote
11. Finalizado

**B. Colunas do quadro** (mensagem de 24/09, 13:55, "esse fluxo tá ótimo"): `To do`, `Doing`, `Paused`, `Code-review`, `In test`, `Done`, "talvez só um backlog".

## Como A e B se encaixam

As colunas (B) são o que aparece no quadro. Os passos (A) são o que acontece dentro de cada coluna. Os passos 9 e 10 não são da tarefa: são do **pacote** (versão), que junta várias tarefas prontas.

| Passo (A) | Coluna (B) | Equivalente no Trello Wpay | O que o sistema registra |
|---|---|---|---|
| — | `Backlog` | `Backlog` | Tarefa criada, ainda fora da sprint |
| — | `To do` | `Esteira` (sprint) | Tarefa na sprint, com responsável (D-PRJ-02) |
| 1–3. Pega, desenvolve, commita | `Doing` | `Doing` | Início do desenvolvimento; horas realizadas |
| 4. Abre PR | `Doing` → `Code-review` | `Doing` → `Technical Review` | **Link do PR obrigatório** para mover (RN-PRJ-23) |
| 5. Code review | `Code-review` | `Technical Review` | Aprovação do Tech Lead do projeto (RN-PRJ-24) |
| 6. Teste cruzado | `In test` | `Test QA` | Aprovação de **outro dev**, que não seja o autor |
| 7. QA | `In test` | `Test QA` | Roteiro de teste executado **no ambiente local do QA**, com evidência por item |
| 8. Merge | `In test` → `Done` | `Test QA` → `Finished` | Merge feito na `main` **pelo Tech Lead** |
| 9–10. Homologação do pacote | Fora do quadro: **versão** | Listas `Produção x.y.z` | Estados da versão |
| 11. Finalizado | — | — | Versão em produção |
| (qualquer momento) | `Paused` | `Impedimento/Pausa` | Motivo obrigatório; tempo parado contado à parte |

A retirada de uma tarefa de `Code-review` ou de `In test` de volta para `Doing` é **retrabalho**, registrado com a origem: code review, teste cruzado ou QA.

## Perguntas abertas (para a pauta 04, com Lucas Alencar)

1. ~~Em qual ambiente o QA testa, antes do merge?~~ **Respondido 24/09: no ambiente local do próprio QA**, com a branch do PR. A v1 não precisa de ambiente por PR. A integração entre tarefas só é testada junto na homologação do pacote.
2. ~~Quem faz o merge?~~ **Respondido 24/09: o Tech Lead** do projeto.
3. ~~Teste cruzado vale para toda tarefa?~~ **24/09: liga/desliga por tarefa** (D-PRJ-15); validar com Lucas Alencar.
4. ~~Quem homologa o pacote?~~ **24/09: a equipe WCJ, testando tudo junto; o cliente não participa** (D-PRJ-13).
5. ~~Nomes das colunas?~~ **24/09: padrão em inglês, editável por projeto** (D-PRJ-14).
