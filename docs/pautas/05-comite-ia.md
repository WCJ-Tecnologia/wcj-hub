# Pauta 05: Comitê de IA (decisões consolidadas)

| | |
|---|---|
| Com | Comitê de IA |
| Conduz | Lucas Scholz |
| Duração | 60 min |
| Quando | **Depois** das pautas 01, 03 e 04 (a 02, Chamados, foi adiada: D-GER-12) |
| Base | `docs/necessidade.md` atualizado com as 4 atas; resultado da prova de conceito MCP |
| Saída | Ata em `docs/atas/AAAA-MM-DD-comite.md`; `necessidade.md` sem nenhum [DECIDIR] aberto; liberação da etapa 2 (banco) |

Objetivo: fechar a etapa 1 de **Núcleo, Projetos e CRM**. Ao final, todo **[DECIDIR]** desses módulos tem resposta, e o Lucas Alencar pode começar o banco. Chamados tem etapa 1 própria, depois (D-GER-12).

## Antes da reunião

- [ ] Atas das pautas 01, 03 e 04 aprovadas e incorporadas ao `necessidade.md`.
- [ ] Resultado da prova de conceito MCP (validação técnica).
- [ ] Resultado da enquete interna de ferramentas: assistente, plano e **onde cada pessoa usa** (app, celular, Codex, Claude Code).

## Roteiro

### 1. O que as reuniões trouxeram (10 min)
Um resumo por frente: o que foi confirmado, o que mudou, o que surgiu de novo.

### 2. Decisões pendentes (35 min)

**Interface de IA**
| # | Decisão | Base |
|---|---|---|
| 1 | ~~Acesso de quem usa ChatGPT~~ **Resolvido (D-IA-06):** devs por Codex ou Claude; comercial por GPT personalizado com Actions, junto com a v1 do CRM | — |
| 2 | ~~Dono da Interface de IA~~ **Resolvido: Lucas Scholz (produto) + Lucas Alencar (técnico)** (D-GER-07) | — |
| 3 | ~~`core/mcp.md` no padrão?~~ **Resolvido: sim, principalmente para sistemas 100% IA** (D-GER-09) | — |

**Permissões (quem vê o quê)**
| # | Decisão | Base |
|---|---|---|
| 4 | ~~Gestor de projetos vê pipeline comercial?~~ **Resolvido: não** (D-PRJ-08) | Ata 24/09 |
| 5 | ~~Dev vê só os projetos em que está alocado?~~ **Resolvido: sim** (D-PRJ-03) | Ata 24/09 |
| 6 | ~~Comercial vê conteúdo de chamados?~~ **Resolvido: não; vê só quantidade e status** | — |
| 7 | ~~Comerciais veem os leads uns dos outros?~~ **Resolvido: veem todos, editam os próprios** (D-CRM-01) | — |

**Regras**
| # | Decisão | Base |
|---|---|---|
| 8 | ~~Tarefa sem responsável sai do Backlog?~~ **Resolvido: não** (D-PRJ-02) | Ata 24/09 |
| 9 | ~~Tratativa travada depois de 24 h?~~ **Resolvido: não; edição livre com histórico** (D-CRM-01) | — |
| 10 | Chamado fechado reabre ou gera outro? (RN-CHM-06) | **Adiado com o módulo Chamados** (D-GER-12) |

**Escopo da v1**
| # | Decisão | Base |
|---|---|---|
| 11 | ~~Migrar histórico do Trello?~~ **Resolvido: começar do zero; migração depois** (D-PRJ-04) | Ata 24/09 |
| 12 | ~~Canais de abertura~~ **Respondido: e-mail** (D-CHM-02) | Ata Alaor 24/09 |
| 13 | Notificação de chamado por e-mail | **Adiado com o módulo Chamados** |
| 14 | ~~Banco de horas~~ **Respondido: sim, com hora extra** (D-CHM-04) | Ata Alaor 24/09 |
| 15 | ~~Integração CRM → DaaS~~ **Resolvido: depois da v1** (D-CRM-01) | — |
| 15b | **Chamados: construir no Hub, GLPI ou em duas fases?** (D-CHM-01). Pode esperar Chamados entrar na fila | Ata Alaor 24/09 |
| 16 | Priorização final das candidatas F-PRJ (e das que surgirem em Chamados e CRM) | Pautas 01 a 03 |

**Projeto**
| # | Decisão | Base |
|---|---|---|
| 17 | ~~Stack~~ **Resolvido: Laravel + Inertia** (D-GER-09) | — |
| 18 | ~~Dono do Núcleo~~ **Resolvido: Lucas Scholz** (D-GER-07) | — |
| 19 | ~~Nome do sistema~~ **Resolvido: WCJ Hub** (D-GER-06) | — |
| 20 | ~~Prazo e ordem~~ **Resolvido: o mais rápido possível; Núcleo → Projetos → CRM → Chamados** (D-GER-08, D-GER-12) | — |
| 22 | ~~Painel de produtividade~~ **Resolvido: só gestor e Administrador** (RN-PRJ-22) | — |

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
