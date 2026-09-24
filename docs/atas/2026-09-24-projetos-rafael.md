# Ata: Projetos (validação do Trello)

| | |
|---|---|
| Data | 24/09/2026, 32 min (chamada) |
| Participantes | Rafael Garcia (gestor de projetos), Lucas Scholz |
| Pauta | [`pautas/01-projetos-rafael.md`](../pautas/01-projetos-rafael.md) |
| Material recebido | 3 documentos de requisição (SGM-13, SGM-17, SGM-22) e o "Histórico de Desenvolvimento" da plataforma Tecsolution (PDF). Análise em [`levantamento/modelos-rafael.md`](../levantamento/modelos-rafael.md). Originais fora do git. |

Fonte: transcrição automática da chamada. Trechos ambíguos na transcrição estão marcados como **[confirmar]**.

## Confirmado

| # | Assunto | O que foi dito |
|---|---|---|
| 1 | Fluxo de etapas | **Personalizado por projeto.** |
| 2 | `Esteira` | É a **sprint**: o que está pronto para desenvolver. |
| 3 | Devoluções do QA (46 × 21) | Rafael atribui boa parte a ajustes visuais pequenos ("pixels"). O indicador precisa separar devolução trivial de devolução séria. |
| 4 | Causa de retrabalho | "Muita coisa volta quando a gente documenta mal alguma demanda." Projeto com tudo refinado antes (ex.: projeto de multas, futuro PTA) volta menos. |
| 5 | Revisão técnica pulada | Não é esquecimento. Há tarefa **sem nada para testar em tela** (ex.: Swagger): tem revisão técnica e não tem QA. A tarefa define quais validações se aplicam. |
| 6 | 2 pessoas por card | São **back-end + front-end**. QA e gestor não se colocam no card, mas acompanham tudo. Atribuir pessoas **por função** (back, front, QA) foi aprovado, principalmente para projeto de cliente. |
| 7 | Prazo | O quadro Wpay não segue o modelo de gestão. Projetos de cliente (ex.: gestão de multas) têm início e fim. **Prazo tem que existir.** |
| 8 | Horas | Trello pago (cerca de R$ 500,00 por mês, informado na reunião) só registra início e fim. **Não compara previsto × realizado.** Rafael quer lançar a previsão e acompanhar o realizado. |
| 9 | Estimativa pelo dev | O dev analisa o documento e o card e **informa as horas por item** ("isso aqui eu demoro 8 horas"). |
| 10 | Horas → dias | Hoje o gestor converte horas em dias à mão, e feriados atrapalham. A conversão precisa considerar **dias úteis, feriados e a disponibilidade de cada dev**. Exemplo citado: tarefa de 16 h = 2 dias para dev de 8 h/dia e 4 dias para dev de 4 h/dia. |
| 11 | Versão (release) | A data da versão é **definida por projeto**. Projeto de cliente: fecha a sprint, valida e planeja a subida, e muitas vezes é o cliente que decide quando. Projeto interno: acumula demandas e sobe quando convém. |
| 12 | Cronologia de entregas | Rafael montou à mão a linha do tempo da Tecsolution, entrando em cada lista de produção. Quer isso **gerado pelo sistema**, em formato de linha do tempo, para apresentar ao cliente. Modelo enviado. |
| 13 | Chamado dentro do projeto | Aprovado: uma **coluna "Chamados"** no quadro do projeto. O cliente abre o chamado no portal, escolhe o projeto (ex.: PDV, KDS) e a categoria "desenvolvimento", e o chamado aparece como card no quadro, com notificação. Exemplo citado: Portal de Compras em produção. |
| 14 | IA avançando cards e escrevendo documentação | Aprovado ("automatizado seria bem top"). Hoje a documentação só é feita em projeto de cliente, porque é trabalhosa. |
| 15 | Documento de requisição | Quer preencher os requisitos e o sistema **gerar o documento padrão WCJ** e anexar à tarefa. Modelos enviados (SGM-13/17/22). |
| 16 | Requisito → teste | Cada requisito (critério de aceite) inserido **vira item do checklist de teste** automaticamente, como caixa de marcar para o QA. |
| 17 | PR | O dev abre o PR; a ideia é a IA anexar o link do PR à tarefa e outra pessoa aprovar. Fluxo atual (confirmado depois por Lucas Scholz): o dev termina a tarefa e abre PR para o **Tech Lead** aprovar. |
| 18 | O que fica fora do Trello | **Apresentações para cliente** (histórico de versões e entregas). |
| 19 | Produtividade por dev | Quer ver, por dev, entrega no prazo e retrabalho. Exemplos citados: um dev costuma entregar antes do prazo; outro volta do QA em média 7 vezes. |
| 20 | O que se esquece | Problema identificado numa demanda era avisado a outra pessoa, que esquecia, e isso gerou vários chamados. Pediu **notificação ao atribuir** uma tarefa. |
| 21 | O que pediria à IA | Criar cards, automatizar relatório para o cliente. |
| 22 | Fechamento para pagamento | Relatório de **tudo o que foi entregue** num período, com clareza e detalhe, para o cliente (ex.: PTA) aprovar antes de pagar e a WCJ emitir nota. Rafael vai procurar um modelo. |

## Decisões

| # | Decisão | Afeta |
|---|---|---|
| D-PRJ-01 | Fluxo de etapas configurável por projeto. | RN-PRJ-13 |
| D-PRJ-02 | Nenhuma tarefa sai do Backlog sem responsável. | RN-PRJ-02 |
| D-PRJ-03 | Dev vê só os projetos em que está alocado. | RN-PRJ-04 |
| D-PRJ-04 | Não migrar o histórico do Trello na v1: começar do zero com os projetos ativos. A migração pode vir depois. | Integrações |
| D-PRJ-05 | Prioridade da v1 de Projetos, nas palavras do Rafael: **"as funcionalidades do Trello com a adição do controle do tempo"** (tempo por etapa + horas previstas × realizadas). | Priorização F-PRJ |
| D-PRJ-06 | Cada módulo tem a sua v1 (Projetos, Chamados, CRM). | Ordem das fatias |
| D-PRJ-07 | Nomenclatura: indiferente para a equipe. O sistema usa **tarefa**. | Vocabulário |

## Definido depois da reunião

Por Lucas Scholz e Lucas Alencar, no mesmo dia. Registrado aqui para ciência do Rafael.

| # | Decisão | Afeta |
|---|---|---|
| D-PRJ-08 | Gestor de projetos **não vê** pipeline comercial nem valores de proposta (confirmado por Lucas Scholz após a reunião). | Quem usa |
| D-PRJ-09 | Revisão técnica = aprovação do PR pelo Tech Lead (fluxo atual, confirmado por Lucas Scholz após a reunião). | RN-PRJ-23, F-PRJ-06 |
| D-PRJ-10 | O Tech Lead **varia por projeto** (confirmado por Lucas Scholz após a reunião). | RN-PRJ-24, RN-PRJ-25 |
| D-PRJ-11 | Colunas padrão do quadro (Lucas Alencar): `Backlog`, `To do`, `Doing`, `Paused`, `Code-review`, `In test`, `Done`. Fluxo da tarefa: desenvolve → abre PR → code review → teste cruzado (outro dev) → QA → merge → entra no pacote → homologação do pacote → produção. | Fluxo padrão |
| D-PRJ-12 | O QA testa no ambiente local dele; o Tech Lead faz o merge. | Fluxo padrão |
| D-PRJ-13 | O cliente não participa da homologação do pacote: a equipe WCJ testa tudo junto. | Pacote (versão) |
| D-PRJ-14 | Colunas padrão em inglês, sempre editáveis por projeto. | Fluxo padrão |
| D-PRJ-15 | Teste cruzado ligado por padrão em toda tarefa; quem não quiser desmarca. | Validações |
| D-PRJ-16 | Fluxo aberto: uma tarefa pode ter vários PRs; qualquer pessoa alocada move a tarefa; travas de PR e de função são opcionais por projeto. | Fluxo padrão |

## Pendências

| # | Pendência | Com |
|---|---|---|
| P-01 | Enviar modelo do relatório de fechamento para pagamento | Rafael |
| P-02 | Enviar a lista de pontos que o relatório de fechamento precisa ter | Rafael |
| P-03 | ~~Gestor de projetos vê o pipeline comercial?~~ **Resolvido 24/09 (Lucas Scholz): não vê.** | — |
| P-04 | ~~Fluxo atual de PR e merge dos devs~~ **Resolvido 24/09 (Lucas Scholz): o dev termina a tarefa e abre PR para o Tech Lead aprovar.** | — |
| P-05 | Mandar esta ata ao Rafael para validação | Lucas Scholz |

## Sugestão registrada

Rafael sugeriu usar o **Vitinho** para desenvolver parte dos três módulos, aproveitando a capacidade livre da equipe enquanto os projetos novos não entram. **[DECIDIR no comitê]**
