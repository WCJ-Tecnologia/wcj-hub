# Pauta 01: Projetos (validação do Trello)

| | |
|---|---|
| Com | Rafael Garcia (gestor de projetos) |
| Conduz | Lucas Scholz |
| Duração | 60 min |
| Base | `docs/necessidade.md` (Módulo Projetos) e `docs/levantamento/trello-wpay.md` |
| Saída | Ata em `docs/atas/AAAA-MM-DD-projetos-rafael.md` e seção Projetos atualizada |

## Antes da reunião

- [ ] Rafael libera acesso (ou envia o export JSON) de **todos os quadros ativos** do Trello.
- [ ] Rafael escolhe **2 tarefas reais** que deram trabalho (uma que voltou do QA mais de uma vez e uma que ficou parada em impedimento) para percorrer na reunião.
- [ ] Lucas envia ao Rafael o `trello-wpay.md` com um dia de antecedência.

## Roteiro

### 1. Contexto (5 min)
- O que é o sistema, os três módulos e o núcleo comum.
- O que se espera do Rafael: validar o fluxo real e dizer o que falta, não desenhar tela.

### 2. Validar o que o quadro Wpay mostra (15 min)

Para cada ponto: confirma, corrige ou explica.

| # | O que o quadro mostra | Pergunta |
|---|---|---|
| 1 | Fluxo `Backlog → Esteira → Doing → Technical Review → Test QA → Finished → Produção x.y.z` | É o fluxo de todos os projetos ou só do Wpay? |
| 2 | `Esteira` entre Backlog e Doing | Esteira é o quê: fila da sprint, fila por dev, fila priorizada? |
| 3 | 46 devoluções do QA contra 21 aprovações em 2 meses | O número bate com a percepção? Qual é o motivo mais comum de devolução? |
| 4 | Revisão técnica pulada em 31% das entradas no QA | É regra (ex.: correção pequena) ou esquecimento? Quem faz a revisão técnica? |
| 5 | Normalmente 2 pessoas por card | São dev + QA? Existe papel de QA separado do dev? |
| 6 | Só 1 card com prazo em 116 | Prazo não existe ou é controlado em outro lugar? A equipe **quer** prazo? |
| 7 | Versão de produção vira lista (`Produção 1.0.9 10/07/2026`) | Quem cria a versão e decide o que entra nela? Existe nota de versão enviada ao cliente? |
| 8 | Etiquetas misturam tipo (melhoria, bug) e área (Âncora, FDIC) | As áreas mudam por projeto? Quais tipos existem de verdade? |
| 9 | Checklists "Testes", "Âncora", "Financiador", "Fornecedor" | Existe roteiro de teste padrão ou cada um monta o seu? |
| 10 | Lista `Sustentação` e etiqueta `CONFIGURAÇÃO GMUD` | Chamado de cliente entra no quadro de projeto? GMUD tem aprovação formal? |
| 11 | Lista `Base de dados` | O que fica guardado ali? |
| 12 | PR do GitHub anexado à mão | Todo card de desenvolvimento tem PR? Quem anexa? |

### 3. Percorrer as 2 tarefas reais (10 min)
- Do pedido até produção: quem fez o quê, onde ficou parada, o que foi feito fora do Trello (WhatsApp, planilha, conversa).

### 4. O que o Trello não faz (15 min)
Perguntas comuns das reuniões:
1. O que você faz hoje fora do Trello porque ele não atende?
2. Qual informação você precisa e demora para achar?
3. O que você esquece de fazer e só descobre quando dá problema?
4. O que você pediria para a sua IA fazer nesse módulo, se ela tivesse acesso?
5. O que **não** pode existir, ou seja, o que atrapalharia se o sistema fizesse?

Depois, validar as candidatas (priorizar: **v1**, **depois** ou **não**):

| # | Candidata | Prioridade |
|---|---|---|
| F-PRJ-01 | Indicador de retrabalho (devoluções do QA por tarefa, dev e projeto) | |
| F-PRJ-02 | Tempo por etapa e tempo até pronto | |
| F-PRJ-03 | Versão como registro, com notas de versão geradas | |
| F-PRJ-04 | Roteiro de teste padrão por tipo e perfil do produto | |
| F-PRJ-05 | Modelo de tarefa: história de usuário + Dado/Quando/Então | |
| F-PRJ-06 | Vínculo automático com PR do GitHub | |
| F-PRJ-07 | Evidência de teste presa ao item do roteiro | |
| F-PRJ-08 | Chamado do cliente vira tarefa ligada | |
| F-PRJ-09 | Prazo e atraso por projeto e por pessoa | |
| F-PRJ-10 | Painel de impedimentos (o que está parado, há quanto tempo, por quê) | |

### 5. Permissões e migração (10 min)
- Dev vê só os projetos em que está alocado? (RN-PRJ-04)
- Tarefa sem responsável pode sair do Backlog? (RN-PRJ-02)
- Gestor de projetos vê o pipeline comercial e valores de proposta?
- Migrar histórico do Trello ou começar do zero com os projetos ativos?
- Como a equipe chama as coisas: "card", "tarefa", "demanda", "história"?

### 6. Fechamento (5 min)
- Ler em voz alta as decisões e pendências.
- Combinar quem valida a ata e em quanto tempo.

## O que precisa sair da reunião

- [ ] Fluxo de etapas confirmado (vale para todos os projetos ou por projeto).
- [ ] Papéis dentro do projeto (dev, revisor, QA) confirmados.
- [ ] Candidatas F-PRJ priorizadas.
- [ ] Posição sobre prazo, migração do Trello e visibilidade entre projetos.
- [ ] Vocabulário da equipe.
