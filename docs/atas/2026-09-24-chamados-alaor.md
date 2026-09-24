# Ata: Chamados (sustentação)

| | |
|---|---|
| Data | 24/09/2026, 10 min (chamada) |
| Participantes | Alaor Rodrigues (dono do módulo Chamados), Lucas Scholz |
| Pauta | [`pautas/02-chamados-alaor.md`](../pautas/02-chamados-alaor.md) (conversa curta, sem percorrer a pauta inteira) |
| Material recebido | Vídeo de referência sugerido pelo Alaor: "ANETI — Treinamento Do Zero ao ITSM Básico com GLPI" (YouTube, 2h53). Resumo abaixo. |

Fonte: transcrição automática. Trechos ambíguos marcados como **[confirmar]**. O fim da chamada tratou de agenda de outro assunto e não entra nesta ata.

## Confirmado

| # | Assunto | O que foi dito |
|---|---|---|
| 1 | Canais | Em ferramenta de ITSM o canal é indiferente: todos criam, atualizam e fecham chamado. Para começar, o **canal principal é e-mail**, com um endereço próprio (sugestões: `ticket@wcjtecnologia.com.br` ou `suporte@wcjtecnologia.com.br`). |
| 2 | Organização dos chamados | Por **cliente, prioridade e área**. |
| 3 | Triagem | Precisa de uma função de **despachante** (dispatcher): recebe, filtra e coloca o chamado na fila certa. Se resolve no **primeiro nível (N1)**, fecha; se não, escala para **N2 / N3**. O que importa é a função, não quem ocupa. |
| 4 | Esforço | ITSM é trabalhoso: na empresa anterior do Alaor eram 3 a 4 pessoas só no service desk. |
| 5 | Ferramenta | Alaor recomenda o **GLPI**: open source, sem licença, hospedado pela própria empresa (ou nuvem paga do fornecedor), fácil de customizar, comunidade grande. Ele usa desde 2011. Ferramentas pagas (Jira Service Management e similares) custam caro. |
| 6 | Uso inicial na WCJ | Lucas: no início, chamados seriam **só no nível de projeto**. |
| 7 | Prioridade | Quem define é a **WCJ, ao tratar o chamado**, não o cliente. |
| 8 | SLA e prioridade | Definidos **contrato a contrato**. |
| 9 | Banco de horas | Há contrato com **horas contratadas**; o que passar é **hora extra** e precisa ser contabilizado. |
| 10 | Viagem | "Viagem a gente tem que personalizar". **[confirmar]** se é deslocamento para atendimento no local, cobrado por contrato. |
| 11 | Fechamento automático | Chamado resolvido **fecha sozinho** se o cliente não responder. Prazo: **[LEVANTAR]**. |
| 12 | Processo | Hoje não existe processo de chamados. Lucas vai desenhar um fluxo básico, rodar uma v1 e aprimorar. |
| 13 | Demanda | Hoje não há volume de chamados: a WCJ entrega projeto, analisa e corrige. **Sustentação de infraestrutura muda o contexto**: quando esses contratos entrarem, o módulo passa a ser necessário. Confirma Chamados como último módulo (D-GER-12). |

## Vídeo de referência (GLPI)

Treinamento da ANETI, apresentado por Danilo Santos (Servicedesk Brasil). Sem legenda disponível; resumo feito pelos quadros do vídeo. Agenda do treinamento:

1. Instalação em Docker
2. Configuração geral
3. **Entidades** (organizacional e funcional)
4. **Perfis**
5. **Grupos** (filas de atendimento)
6. **Categorias de chamado** (catálogo de serviços)
7. **SLA** (acordos de nível de serviço)
8. **Regras e automação**
9. **Inventário** com o GLPI Agent

Como os conceitos do GLPI se encaixam no que já está no `necessidade.md`:

| GLPI | WCJ Hub |
|---|---|
| Entidade | Cliente (Núcleo) |
| Perfil | Papel (Quem usa) |
| Grupo / fila de atendimento | Filas N1 / N2 / N3 (item 3 acima) |
| Categoria (catálogo de serviços) | Tipo de chamado + área |
| SLA | SLA por contrato e prioridade (RN-CHM-03) |
| Regras e automação | Distribuição automática para filas; e-mail que vira chamado |
| Inventário (GLPI Agent) | **Não previsto** no Hub. Relevante para sustentação de infraestrutura. |

## Decisão em aberto

| # | Decisão | Com |
|---|---|---|
| D-CHM-01 | **Construir o módulo Chamados no WCJ Hub ou adotar o GLPI** (hospedado pela WCJ) integrado ao Hub. Ver as opções no `necessidade.md`, seção Módulo Chamados. | Comitê de IA, quando Chamados entrar na fila |

## Pendências

| # | Pendência | Com |
|---|---|---|
| P-01 | Definir o endereço de e-mail do suporte (`ticket@` ou `suporte@`) | Lucas Scholz |
| P-02 | Confirmar o que é "viagem" no contrato (item 10) | Lucas Scholz / Alaor |
| P-03 | Prazo para o fechamento automático de chamado resolvido | Alaor |
| P-04 | Lucas desenha o fluxo básico de chamados e valida com o Alaor | Lucas Scholz → Alaor |
| P-05 | Mandar esta ata ao Alaor para validação | Lucas Scholz |
