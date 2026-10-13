# F4. Teste cruzado e roteiro de QA

**Documento funcional.** Versão 1.0, de 06/10/2026. **Aprovado por Lucas Scholz em 06/10/2026**, com os pontos a validar respondidos (seção 10). Rafael Garcia valida o uso no bugbash. Estrutura e fluxo em [`README.md`](README.md).

| | |
|---|---|
| **Objetivo** | Registrar na tarefa quem fez o teste cruzado e quando, dar ao QA o roteiro de teste montado a partir dos critérios de aceite, item a item, com resultado e evidência, e mostrar a cada QA as tarefas que esperam o teste dele. |
| **Regras que alimentam** | P32 a P39 (`docs/necessidade.md`), com P20, P22 e P24 no que tocam o teste. |
| **Tabelas do `banco.md`** | `tasks` (`cross_tested_by`, `cross_tested_at`, `qa_id`, `developer_id`), `task_acceptance_criteria`, `test_results`, `task_attachments` (evidência). Leitura de `board_stages` (etapa de teste) e `validation_types` (origem do retrabalho). |
| **Depende de** | [F3](f3-quadro-e-card.md): card, critérios de aceite, mover card e o diálogo de volta para desenvolvimento. |
| **Quem aprova** | Lucas Scholz (D-GER-31). Rafael Garcia, gestor de projetos (D-GER-02), participou com feedback e elaboração e valida o uso no bugbash. |

Fica fora deste bloco: criar e editar critérios (F3), mover card em geral (F3), pacote e homologação (F6) e os indicadores de retrabalho (F7).

## 01. Fluxo funcional

**Code review.** O dev cola o link do PR no card (F3) e leva o card para `Code-review`. Quem aprova, na prática, é o Tech Lead do projeto, no próprio GitHub (P33). O sistema guarda só o PR: não tem parecer de code review nem botão de aprovar (P32). Se o Tech Lead pedir ajuste, quem leva o card de volta para desenvolvimento informa a origem "Code review" e o motivo, no diálogo de F3 (P39).

**Teste cruzado.** Com o card em `In test`, a ordem normal é o teste cruzado primeiro e o QA depois (P35). Qualquer dev alocado no projeto, menos o dev da tarefa, testa e, na seção *Teste* da ficha do card, clica em *Marquei: testado por mim*. O card passa a mostrar "Teste cruzado: Fulano, em DD/MM/AAAA às HH:MM" (P34). Se encontrou problema, ele clica em *Devolver para desenvolvimento*, que abre o diálogo de F3 com a origem "Teste cruzado" já escolhida (P39). A ordem não é travada: o QA pode começar antes do teste cruzado (premissa).

**Roteiro de QA.** O QA abre *Projetos → Para testar* ("minhas tarefas para testar") e escolhe a tarefa, ou abre o card direto no quadro. Na seção *Teste*, o roteiro lista cada critério de aceite como um item, na ordem CA_1, CA_2…, com os subitens logo abaixo do pai (P36). O QA testa no ambiente local dele (P36). Em cada item, marca *Aprovado* ou *Reprovado* e anexa a evidência ali mesmo, em geral um print (P36).

**Aprovada.** Com o roteiro aprovado, a tarefa recebe o merge, normalmente feito pelo Tech Lead fora do sistema, e alguém do projeto move o card para `Done` (P38). É um movimento normal de F3: o sistema não move sozinho nem confere o roteiro.

**Reprovada.** O QA clica em *Devolver para desenvolvimento* na seção *Teste* (ou arrasta o card no quadro). Abre o diálogo de F3 com a origem "QA" já escolhida; ele escolhe o motivo e confirma (P39, P40). Na volta, o sistema limpa a marca do teste cruzado (P34), põe o roteiro inteiro de novo em "A testar" (P37) e avisa o dev no sistema e por e-mail (P22). O dev corrige, e o card passa de novo por code review, teste cruzado e QA.

**Tarefa sem critério de aceite.** O card não tem roteiro. O QA testa pelo título e pela descrição e segue o fluxo do mesmo jeito; se quiser deixar registro, comenta no card (P24). Ver 10.6.

## 02. Telas e campos

### Seção *Teste* da ficha do card: teste cruzado

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Testado por | pessoa, só leitura | | vazio ("Ainda não marcado") | Preenchido com quem clicou em *Marquei: testado por mim*. Qualquer pessoa alocada, menos o dev da tarefa (P34). |
| Quando | data e hora, só leitura | | vazio | Gravado no clique (P34). |

Os dois se preenchem juntos e se limpam juntos. A volta para desenvolvimento limpa os dois (P34).

### Seção *Teste* da ficha do card: roteiro

Uma linha por critério de aceite. O texto do critério vem de F3 e aqui não se edita.

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Item | só leitura: CA_1, CA_4.1… | | | Número do critério, estável: excluir um critério não renumera os outros (F3). |
| Critério | texto, só leitura | | | Editar o texto é em F3; editar um critério já testado o volta para "A testar" (P37). |
| Resultado | escolha: A testar, Aprovado, Reprovado | sim | A testar | (P36, P37) |
| Evidências | arquivos | não | | Anexados no item. Aparecem também na lista de anexos do card, com a indicação do item (P24, P36). Mesmas regras de arquivo dos anexos de F3. |
| Última marcação | só leitura: Fulano, DD/MM/AAAA às HH:MM | | | Vem do histórico do item, que guarda cada marcação com o antes e o depois (G3). |

No topo do roteiro, uma linha de resumo: "Roteiro: 7 itens · 4 aprovados · 1 reprovado · 2 a testar", e a situação do teste cruzado.

### *Projetos → Para testar* (lista)

Só leitura. Colunas na seção 03; ao clicar numa linha, abre o card na seção *Teste*.

## 03. Listagem

| Tela | Colunas | Ordem padrão | Observação |
|---|---|---|---|
| Roteiro (no card) | Item, Critério, Resultado, Evidências (quantidade, com os arquivos ao clicar), Última marcação | CA_1, CA_2…, subitens logo abaixo do pai; critério novo entra no fim (P36) | Item reprovado em destaque. |
| Para testar | Chave (ex.: "SGM-17"), Título, Projeto, Etapa, Dev, Teste cruzado (Fulano em DD/MM ou "pendente"), Roteiro (ex.: "4 de 7 aprovados · 1 reprovado"), Em teste desde | Há mais tempo em teste primeiro | Mostra as tarefas em que eu sou o QA e que estão em etapa de teste (`In test` no quadro padrão), em todos os projetos em que estou alocado. "Em teste desde" é a última entrada na etapa, lida do histórico de movimentos (P14). |

Estado vazio do roteiro: "Esta tarefa não tem critérios de aceite. *Novo critério*" (o botão abre a criação de critério de F3). Estado vazio de *Para testar*: "Nenhuma tarefa esperando o seu teste." Esta lista não tem botão de criar: ela só reúne cards que já existem.

## 04. Ações

| Ação | Quem | O que faz | Confirmação / aviso |
|---|---|---|---|
| Marquei: testado por mim | Pessoa alocada no projeto, menos o dev da tarefa; gestor e Administrador também (premissa) | Grava quem e quando (P34). Tarefa sem dev: qualquer pessoa alocada marca. O botão aparece em qualquer etapa; o normal é em `In test` (P35). | Para o dev da tarefa o botão não aparece (P34). |
| Desfazer a marca | Quem marcou, gestor e Administrador | Limpa quem e quando, para corrigir um clique errado. Fica no histórico (G3). Proposta: ver 10.2. | "Desfazer a marca de teste cruzado de Fulano?" |
| Marcar item: Aprovado, Reprovado ou A testar | Qualquer pessoa alocada; o normal é o QA da tarefa | Grava o resultado do item. Quem marcou e quando ficam no histórico do item (G3). Marcar um item reprovado não move o card (premissa). | Nenhuma. |
| Anexar evidência no item | Qualquer pessoa alocada | Prende o arquivo ao item e à lista de anexos do card (P24, P36). | Nenhuma. |
| Excluir evidência | Mesma regra de excluir anexo de F3 | Apaga o arquivo e o registro juntos. | "Excluir o arquivo X? Não dá para desfazer." |
| Devolver para desenvolvimento | Qualquer pessoa alocada (P13) | Atalho da seção *Teste*: abre o diálogo de volta de F3, com destino padrão no `To do` (o dev leva para `Doing` quando estiver de fato fazendo; P39) e a origem já escolhida: "Teste cruzado" quando chamado do bloco do teste cruzado, "QA" quando chamado do roteiro. Motivo obrigatório, da lista geral (P39, P40). Ao confirmar: conta um ciclo de retrabalho (P39), limpa o teste cruzado (P34), põe o roteiro inteiro em "A testar" (P37) e avisa o dev (P22). | O diálogo é o de F3. A origem pode ser trocada lá. |
| Mover para `Done` | Qualquer pessoa alocada (P13) | Movimento normal de F3, depois do merge (P38). | Nenhum aviso, mesmo com item reprovado ou a testar (proposta, ver 10.7). |
| Abrir a lista *Para testar* | Qualquer pessoa alocada em algum projeto | Ver seção 03. | |

O mesmo efeito do retrabalho vale para qualquer volta para desenvolvimento, de qualquer origem, inclusive code review e homologação do pacote (F6): limpa o teste cruzado e põe o roteiro em "A testar" (P34, P37).

**Pela IA (fatia 1, IA14).** `mover_tarefa` leva o card para `Done` ou de volta para desenvolvimento; na volta, pede origem e motivo, com o mesmo efeito acima (P39). `comentar_tarefa` registra o que o QA quiser dizer no card. `minhas_tarefas` lista as tarefas do usuário (ver 10.10). Marcar o teste cruzado, marcar item do roteiro e anexar evidência não estão nas 6 ferramentas da fatia 1: só pela tela.

## 05. Filtros e busca

| Tela | Busca | Filtros |
|---|---|---|
| Para testar | chave ("SGM-17") e título | Projeto (os projetos em que estou alocado). Filtro rápido *Teste cruzado pendente*: tarefas em etapa de teste nos meus projetos, das quais não sou o dev, sem marca de teste cruzado (proposta, ver 10.11). |
| Roteiro (no card) | não tem: a lista é curta | Nenhum. |

## 06. Indicadores

- **Resumo do roteiro**, no topo da seção *Teste* e na coluna Roteiro da lista: quantos itens no total, quantos aprovados, reprovados e a testar. Conta pai e subitens, cada um como um item.
- **Situação do teste cruzado**: marcado (por quem e quando) ou pendente.
- **Total da lista *Para testar***: "N tarefas esperando o seu teste".

Retrabalho por origem, por motivo, por dev e por projeto é de F7 (P53, P54). Este bloco só grava o dado: a origem e o motivo de cada volta (P39).

## 07. Avisos e documentos gerados

- **Volta para desenvolvimento:** o dev da tarefa recebe aviso no sistema e e-mail (P22). O texto do aviso está em F3.
- **Ser definido como QA da tarefa:** aviso no sistema e e-mail (P22), disparado em F3. É assim que a tarefa chega ao QA, além da lista *Para testar*.
- **Marcar o teste cruzado não avisa ninguém:** a P22 limita os avisos de Projetos a esses dois (ver 10.9).
- Nenhum documento gerado. O documento de requisição e o roteiro em PDF ficam para depois da v1 (P55).

## 08. Exportação

Não se aplica: nenhuma regra pede exportar o roteiro nem a lista (G5). As evidências se baixam uma a uma, como qualquer anexo (F3).

## 09. Permissionamento

| O quê | Administrador | Gestor de projetos | Desenvolvedor |
|---|---|---|---|
| Ver a seção *Teste* do card (teste cruzado e roteiro) | sim | nos projetos dele | nos projetos em que está alocado (P5) |
| Marcar o teste cruzado | sim | sim, nos projetos dele | sim, menos na tarefa em que é o dev (P34) |
| Desfazer a marca do teste cruzado | sim | sim | só a marca que ele mesmo fez |
| Marcar resultado de item do roteiro | sim | sim | sim, nos projetos em que está alocado (ver 10.1) |
| Anexar evidência no item | sim | sim | sim |
| Excluir evidência | como em F3 | como em F3 | como em F3 |
| Devolver para desenvolvimento | sim | sim | sim (P13) |
| Mover para `Done` com item reprovado ou a testar | sim, com aviso | sim, com aviso | sim, com aviso (P38) |
| Lista *Para testar* | as tarefas em que é QA | as tarefas em que é QA | as tarefas em que é QA |

A IA de cada pessoa tem exatamente estas permissões, nem mais nem menos (G4). O Comercial não entra em Projetos (C14).

## 10. Pontos a validar

Respondidos por Lucas Scholz em 06/10/2026 e já aplicados nas seções acima e na `necessidade.md` (P38, P39).

1. **Quem marca o roteiro.** **Qualquer alocado**; o histórico mostra quem.
2. **Desfazer a marca do teste cruzado.** OK: quem marcou, o gestor e o Administrador.
3. **Ordem teste cruzado → QA.** OK: sem aviso; a situação do teste cruzado aparece no topo do roteiro.
4. **Item pai e subitens.** OK: cada um marcado à parte.
5. **Evidência depois do retrabalho.** OK: as antigas ficam; o QA anexa a nova.
6. **Tarefa sem critério.** OK: sem registro além do movimento; quem quiser comenta.
7. **`Done` com item reprovado ou a testar.** **Aviso que deixa seguir**: "O roteiro tem N itens reprovados e M a testar. Mover mesmo assim?" (P38).
8. **Destino do atalho *Devolver*.** **`To do`**: o dev leva para `Doing` quando estiver de fato fazendo (P39). Vale para toda devolução, inclusive a reprovação na homologação (F6); quem devolve pode escolher outra etapa no diálogo.
9. **Aviso ao QA quando o teste cruzado é marcado.** OK: sem aviso; o QA usa a lista *Para testar*.
10. **`minhas_tarefas` pela IA.** OK: traz também as tarefas em que a pessoa é QA, com a etapa.
11. **Filtro *Teste cruzado pendente*.** **Fica.**
