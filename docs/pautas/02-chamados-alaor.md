# Pauta 02: Chamados (sustentação)

| | |
|---|---|
| Com | Alaor Rodrigues (dono do módulo Chamados) |
| Conduz | Lucas Scholz |
| Duração | 60 min |
| Base | `docs/necessidade.md` (Módulo Chamados) |
| Saída | Ata em `docs/atas/AAAA-MM-DD-chamados-alaor.md` e seção Chamados atualizada |

## Antes da reunião

- [ ] Alaor traz a **lista de contratos de sustentação ativos**: cliente, sistema, o que o contrato cobre, se tem SLA ou banco de horas.
- [ ] Alaor escolhe **3 chamados reais recentes** para percorrer do pedido ao fechamento: um urgente, um simples e um que demorou ou deu problema.
- [ ] Alaor estima o volume: chamados por mês, quantos clientes abrem, quantas pessoas atendem.

## Roteiro

### 1. Contexto (5 min)
- O que é o sistema, os três módulos e o núcleo comum.
- Hoje, no quadro de projetos do Wpay, sustentação aparece misturada com desenvolvimento (lista `Sustentação`, etiqueta `CONFIGURAÇÃO GMUD`). O módulo separa isso.

### 2. Como funciona hoje (15 min)
Percorrer os 3 chamados reais:
- Por onde o cliente pediu (WhatsApp, e-mail, ligação, direto com o dev)?
- Quem recebeu, quem decidiu a prioridade, quem atendeu?
- Onde ficou registrado? O cliente soube do andamento?
- O que foi feito fora de qualquer ferramenta?
- Quanto tempo levou, e esse tempo estava combinado com o cliente?

### 3. Validar a proposta (20 min)

| # | Proposta no documento | Pergunta |
|---|---|---|
| 1 | Cliente abre chamado pelo **portal** (v1) | Portal resolve ou o cliente vai continuar mandando WhatsApp? E-mail entra na v1? |
| 2 | Tipos de chamado | Quais existem de verdade? (incidente, dúvida, melhoria, pedido de acesso, mudança/GMUD…) |
| 3 | Prioridade | Quantos níveis? O que define cada um? Quem define: cliente ou suporte? |
| 4 | SLA vem do contrato + prioridade (RN-CHM-03) | Existe SLA escrito nos contratos? Qual prazo: primeira resposta, solução ou os dois? Conta horário comercial ou 24h? |
| 5 | Banco de horas | Algum contrato desconta horas? Precisa apontar tempo por chamado? |
| 6 | Triagem | Só o Alaor faz triagem ou mais alguém? |
| 7 | Comentário interno nunca aparece ao cliente (RN-CHM-05) | Confirma? |
| 8 | Chamado resolvido fecha sozinho após N dias sem resposta | Quantos dias? |
| 9 | Chamado fechado não reabre; o cliente abre outro, ligado ao anterior (RN-CHM-06) | Confirma ou prefere reabrir? |
| 10 | Chamado que exige desenvolvimento vira tarefa no projeto (F-PRJ-08) | Quem decide que vira tarefa? O cliente vê o andamento da tarefa? |
| 11 | GMUD | Existe aprovação formal de mudança em produção? Quem aprova, o cliente participa? |
| 12 | Quem do cliente abre chamado | Qualquer pessoa da empresa ou só contatos autorizados? Um contato pode ver os chamados dos colegas? |

### 4. O que falta hoje (15 min)
Perguntas comuns das reuniões:
1. O que você faz hoje fora da ferramenta porque ela não atende?
2. Qual informação você precisa e demora para achar?
3. O que você esquece de fazer e só descobre quando dá problema?
4. O que você pediria para a sua IA fazer nesse módulo, se ela tivesse acesso?
5. O que **não** pode existir, ou seja, o que atrapalharia se o sistema fizesse?

Pontos para provocar, se não surgirem:
- Relatório mensal por cliente (chamados abertos, fechados, prazo cumprido) para mandar junto com a fatura ou o status.
- Base de soluções: chamado repetido com resposta conhecida.
- Aviso de prazo estourando antes de estourar.
- Visão do técnico: "meus chamados", ordenados pelo prazo.

### 5. Fechamento (5 min)
- Ler em voz alta as decisões e pendências.
- Combinar quem valida a ata e em quanto tempo.

## O que precisa sair da reunião

- [ ] Lista de tipos e prioridades de chamado.
- [ ] Regra de SLA (ou decisão de não ter SLA na v1).
- [ ] Posição sobre banco de horas e apontamento de tempo.
- [ ] Canais de abertura da v1.
- [ ] Quem faz triagem e quem atende.
- [ ] Lista de contratos e sistemas que entram na v1.
- [ ] Volume estimado.
