# Levantamento: quadro Trello "Wpay"

Fonte: export JSON do quadro, recebido em 24/09/2026. O JSON não é versionado (contém nomes, e-mails e conteúdo de cliente).

**Limite do export:** o Trello exporta só as últimas 1.000 ações. O histórico de movimentação cobre 20/07/2026 a 23/09/2026. Os cards cobrem de 11/2025 a 09/2026.

## Números

| Item | Valor |
|---|---|
| Cards | 116 (103 abertos, 13 arquivados) |
| Membros no quadro | 13 (2 pessoas fazem 90% das ações) |
| Cards com descrição | 104 (mediana de 230 caracteres) |
| Cards com responsável | 97; o normal é 2 pessoas por card |
| Cards com checklist | 80 (175 checklists, 549 itens, 489 concluídos) |
| Cards com anexo | 69 (90 prints PNG, links de PR do GitHub, 1 vídeo) |
| Cards com comentário | 75 (142 comentários, mediana de 200 caracteres) |
| **Cards com prazo** | **1** |
| Campos personalizados / Power-ups | nenhum |

## Fluxo real (listas, em ordem)

`Base de dados` · `Backlog` → `Esteira` → `Doing` → `Technical Review` → `Test QA` → `Finished - New Request` → `Produção x.y.z dd/mm/aa`

Listas laterais: `Impedimento/Pausa`, `Sustentação`. Arquivadas: `Test/QA`, `Done`, `Pending Approval`, `Finished - Correction Bug`, que mostram que o fluxo já mudou várias vezes.

## Movimentações observadas (245 em 2 meses)

| Movimento | Vezes |
|---|---|
| Doing → Technical Review | 52 |
| Technical Review → Test QA | 47 |
| **Test QA → Doing (volta do QA)** | **46** |
| Doing → Test QA (pulou a revisão técnica) | 21 |
| Test QA → Finished | 21 |
| Esteira → Doing | 12 |
| Backlog → Esteira | 11 |
| Backlog → Doing (pulou a esteira) | 10 |

## O que isso mostra

1. **O retrabalho no QA é o maior sinal.** Das 69 saídas de `Test QA`, 46 voltaram para `Doing` e 21 foram aprovadas: cerca de 2 em cada 3 entregas voltam. O Trello não mostra esse número; foi preciso reconstruir pelo histórico.
2. **A revisão técnica é pulada** em 21 de 68 entradas no QA vindas do dev (31%).
3. **Versão de produção é uma lista.** Cada deploy vira uma lista `Produção 1.0.9 10/07/2026` para onde os cards vão. Há 5 versões e 71 cards nelas. No sistema, isso é uma entidade **Versão**, com data e itens, e as notas de versão saem dela.
4. **Prazo não é usado.** Só 1 card tem data de entrega. O campo "concluído" do prazo (54 cards) é usado como marcador de pronto. Não dá para ver atraso.
5. **As etiquetas misturam duas coisas:**
   - **tipo:** MELHORIA (37), CORREÇÃO DE BUG (11), SUSTENTAÇÃO (2), CONFIGURAÇÃO GMUD (1);
   - **área do produto:** ADMIN, GERAL, FDIC (22 cada), ANCORA (17) + **ÂNCORA (5, duplicada)**, FORNECEDORES (13), COMERCIAL (2).
   A duplicata ANCORA/ÂNCORA é o custo do texto livre. No sistema, os dois são listas fechadas separadas.
6. **O checklist é roteiro de teste.** O nome mais comum é "Testes" (36), e vários são por perfil do produto (Âncora, Financiador, Fornecedor). 238 itens têm responsável e nenhum tem prazo.
7. **A descrição às vezes segue história de usuário** (Como / Quero / Para + Critério de aceite), mas sem padrão: poucos cards usam. Encaixa no Dado/Quando/Então do nosso padrão.
8. **O PR do GitHub é anexado à mão** como link (`wcj-tecnologia/wpay/pull/…`).
9. **Chamado de sustentação fica misturado** no quadro de projeto (lista `Sustentação`, etiqueta `SUSTENTAÇÃO`, `CONFIGURAÇÃO GMUD`).
10. **Evidência é print.** 90 PNGs anexados, quase certamente de teste e de bug.

## Perguntas para o Rafael

- `Esteira` é o quê: fila priorizada da sprint, fila do dev?
- Quem move para `Test QA` e quem move de volta para `Doing`: o QA ou o dev?
- Os 2 responsáveis por card são dev + QA? Existe papel de QA separado?
- Por que a revisão técnica é pulada: é regra (ex.: correção pequena) ou esquecimento?
- Prazo não é usado porque não existe, ou porque é controlado em outro lugar?
- `Base de dados` guarda o quê?
- Um quadro por produto/cliente é o padrão em todos os quadros?
- GMUD: existe processo formal de mudança em produção? Quem aprova?
