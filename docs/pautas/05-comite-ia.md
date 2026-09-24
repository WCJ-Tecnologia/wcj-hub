# Pauta 05: Comitê de IA (decisões consolidadas)

| | |
|---|---|
| Com | Comitê de IA |
| Conduz | Lucas Scholz |
| Duração | 60 min |
| Quando | **Depois** das pautas 01 a 04 |
| Base | `docs/necessidade.md` atualizado com as 4 atas; resultado da prova de conceito MCP |
| Saída | Ata em `docs/atas/AAAA-MM-DD-comite.md`; `necessidade.md` sem nenhum [DECIDIR] aberto; liberação da etapa 2 (banco) |

Objetivo: fechar a etapa 1. Ao final, todo **[DECIDIR]** do documento tem resposta, e o Lucas Alencar pode começar o banco.

## Antes da reunião

- [ ] Atas das pautas 01 a 04 aprovadas e incorporadas ao `necessidade.md`.
- [ ] Resultado da prova de conceito MCP (R-IA-01).
- [ ] Resultado da enquete interna de ferramentas: assistente, plano e **onde cada pessoa usa** (app, celular, Codex, Claude Code).

## Roteiro

### 1. O que as reuniões trouxeram (10 min)
Um resumo por frente: o que foi confirmado, o que mudou, o que surgiu de novo.

### 2. Decisões pendentes (35 min)

**Interface de IA**
| # | Decisão | Base |
|---|---|---|
| 1 | Se o app ChatGPT só lê: ChatGPT entra na v1 só com leitura? | Resultado da prova de conceito + enquete |
| 2 | Dono da Interface de IA | — |
| 3 | `core/mcp.md` entra no padrão `ai-systems`? | Pauta 04 |

**Permissões (quem vê o quê)**
| # | Decisão | Base |
|---|---|---|
| 4 | ~~Gestor de projetos vê pipeline comercial?~~ **Resolvido: não** (D-PRJ-08) | Ata 24/09 |
| 5 | ~~Dev vê só os projetos em que está alocado?~~ **Resolvido: sim** (D-PRJ-03) | Ata 24/09 |
| 6 | Comercial vê conteúdo de chamados? | — |
| 7 | Comerciais veem os leads uns dos outros? (RN-CRM-05) | Pauta 03 |

**Regras**
| # | Decisão | Base |
|---|---|---|
| 8 | ~~Tarefa sem responsável sai do Backlog?~~ **Resolvido: não** (D-PRJ-02) | Ata 24/09 |
| 9 | Tratativa travada para edição depois de 24 h? (RN-CRM-03) | Pauta 03 |
| 10 | Chamado fechado reabre ou gera outro? (RN-CHM-06) | Pauta 02 |
| 10b | Quem aprova a revisão técnica quando o Tech Lead é o próprio dev da tarefa? (RN-PRJ-25) | Ata 24/09 |

**Escopo da v1**
| # | Decisão | Base |
|---|---|---|
| 11 | ~~Migrar histórico do Trello?~~ **Resolvido: começar do zero; migração depois** (D-PRJ-04) | Ata 24/09 |
| 12 | Canais de abertura de chamado: só portal ou também e-mail? | Pauta 02 |
| 13 | Notificação por e-mail (chamado ao cliente e ao responsável) entra? | Pauta 02 |
| 14 | Banco de horas / apontamento de tempo entra? | Pauta 02 |
| 15 | Integração automática CRM → DaaS (abrir auditoria) fica para depois? (RN-CRM-08) | Pauta 03 e 04 |
| 16 | Priorização final das candidatas F-PRJ (e das que surgirem em Chamados e CRM) | Pautas 01 a 03 |

**Projeto**
| # | Decisão | Base |
|---|---|---|
| 17 | Stack confirmada | Pauta 04 |
| 18 | Dono do Núcleo | — |
| 19 | Nome do sistema (hoje "WCJ Central", provisório) | — |
| 20 | Prazo da v1 e ordem das fatias (sugestão: núcleo → Projetos → Chamados → CRM; cada módulo com sua v1, D-PRJ-06) | — |
| 21 | Usar o Vitinho no desenvolvimento dos módulos, aproveitando a capacidade livre (sugestão do Rafael) | Ata 24/09 |
| 22 | Painel de produtividade por dev: quem vê (proposta RN-PRJ-22: gestor e Administrador; dev vê só o próprio) | Ata 24/09 |

### 3. Próximos passos (10 min)
- Liberar a etapa 2: Lucas Alencar aprova o `banco.txt` (parada obrigatória).
- Etapa 3: criar o repositório na organização WCJ a partir do template.
- Etapa 4: gerar o `escopo.md`.

### 4. Fechamento (5 min)
- Ler em voz alta as decisões.
- Registrar no topo do `necessidade.md` (tabela "Decisões registradas").

## O que precisa sair da reunião

- [ ] Zero [DECIDIR] aberto no `necessidade.md`.
- [ ] Etapa 1 encerrada e etapa 2 liberada, com prazo.
- [ ] Ordem das fatias e prazo da v1.
