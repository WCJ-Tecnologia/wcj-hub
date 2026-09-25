# Comece aqui: WCJ Hub

Documento de entrada. Quem abre este repositório, pessoa ou IA, lê isto antes de qualquer outra coisa.

## O produto em um parágrafo

Sistema interno da WCJ Tecnologia com três módulos sobre um núcleo comum: **Projetos** (substitui o Trello, com controle de tempo, num kanban sem trava no fluxo), **CRM** (leads, tratativas, compromissos) e **Chamados** (último, sem demanda ainda). Cada usuário interno conecta a **própria IA** (Claude, Codex; ChatGPT pelo GPT personalizado) para consultar e alterar dados com as mesmas permissões da tela. Projeto **100% IA**: executado por Lucas Scholz e Cauã com Claude, seguindo o padrão `ai-systems` (0.4.0) por inteiro.

## Estado atual

Atualizado em 2026-09-24.

| Etapa (playbook `aplicacao-nova`) | Estado | Onde |
|---|---|---|
| 1. Necessidade | **Reescrita na Sessão C (24/09)** com as regras aprovadas por Lucas Scholz na revisão 4: códigos G, N, P, C e IA, e a tabela de decisões só com decisões de projeto. As lacunas fechadas pela premissa foram confirmadas por Lucas Scholz em 24/09. Próximo: Comitê de IA. Chamados é rascunho (D-GER-12) | `docs/necessidade.md` |
| 2. Banco | **Atualizado na Sessão C (24/09)** com os códigos novos, o anexo da revisão 4 e a exclusão pelo padrão 0.4.0 (sem `deleted_at`). **Para o Lucas Alencar:** perguntas técnicas T6–T10 (seção 8) e diferença para o template 0.4.0 (seção 9). Depois, Comitê (D-GER-16) | `docs/banco.txt` |
| Revisão das regras | **Encerrada.** Incorporada à `necessidade.md` na Sessão C; o arquivo `docs/regras-para-revisao.md` foi apagado (o git guarda as revisões 1 a 4, até o commit `da44c03`) | `docs/necessidade.md` |
| 3. Repositório | Ainda não. Hoje este repo só tem `docs/`, com **backup privado na conta pessoal** (`lucas-scholz-91/wcj-hub`). Depois de banco e escopo validados, cria-se o repositório **na organização WCJ** com o template (etapa 3) | https://github.com/lucas-scholz-91/wcj-hub |
| Teste técnico da IA (MCP) | A fazer, por Lucas Scholz (D-GER-17) | `docs/necessidade.md`, "Assistentes de IA" |
| `core/mcp.md` no padrão | **Publicado** no `ai-systems` 0.3.0 (PR #4) | `~/Documents/Projetos/WCJ/ai-systems/.ai/core/mcp.md` |

## Hierarquia dos documentos

1. **Tabela "Decisões registradas"** no topo do `docs/necessidade.md`: vence qualquer texto mais abaixo e qualquer ata. Guarda só decisões de projeto.
2. **`docs/necessidade.md`**: o requisito consolidado. As regras de produto são as G, N, P, C e IA; em Projetos, a premissa (kanban sem trava) vale acima de todas.
3. **`docs/banco.txt`**: o modelo de dados; cada coluna aponta para a regra que a usa.
4. **`docs/atas/`**: o que foi dito em cada reunião (fonte, não resumo final).
5. **`docs/levantamento/`**: análises de apoio (Trello, modelos, fluxo de desenvolvimento).
6. **`docs/pautas/`**: histórico de preparação; não é requisito. Os códigos antigos citados lá (RN-…, D-PRJ-…) não valem mais.

## Decisões que foram revertidas (não usar)

Aparecem em atas, em pautas ou no histórico do git, mas **não valem mais**:

- Nome "WCJ Central" → **WCJ Hub** (D-GER-06).
- Integração com GitHub por webhook na v1 → **fora da v1, em avaliação** (D-GER-13). O PR entra só como link (P23).
- Migração de dados do DaaS (leads, oportunidades, contratos) → **sem migração** (C15). As funções vêm; os dados, não.
- Ordem Núcleo → Projetos → Chamados → CRM → **Núcleo → Projetos → CRM → Chamados** (D-GER-12).
- Fluxo travado (PR obrigatório, só o Tech Lead move) e depois "travas opcionais por projeto" → **kanban sem trava**: qualquer pessoa alocada move a partir do `To do`, e não há opção de exigir PR (premissa, P13, P23).
- Tarefa sem responsável não sai do Backlog → **tarefa sem dev, sem QA ou sem horas entra na sprint** (P20, P27).
- Capacidade da sprint recusa o que estoura → **só referência para o gestor** (P49).
- Sprint opcional por projeto → **sprint obrigatória**, única saída do Backlog, uma aberta por projeto (P45).
- Projeto "fora do cronograma" e "quantas sprints cabem" → **prazo do projeto pelo sistema fora da v1**; a data de entrega é só informação (P2, P9).
- Aviso por e-mail ao atribuir → **só no sistema** (P22).
- Qualquer um cria card → **só o gestor e o Administrador** (P56).
- Excluir card por `deleted_at` (anexo da revisão 4) → **padrão 0.4.0**: card nunca usado se exclui; com histórico, se arquiva (P26). Lead se desativa (C16).
- Dev vê o próprio painel de produtividade → **só gestor e Administrador** (P54).
- Horas previstas informadas pelo dev, por pessoa, e várias pessoas por função na tarefa → **1 dev + QA; horas da tarefa inteira, opcionais ao criar, definidas ou validadas pelo gestor na sprint** (P20, P27).
- Tarefa usada e abandonada fica no Backlog → **arquivada com motivo** (P26).
- Preço da auditoria no sizing → **é o valor do lead**, obrigatório para ganhar (C2, C12).
- "Tipos de contrato" → **tipos de serviço**; contrato fora da v1 (N10).

## Próximas sessões

**Sessão A — `docs/banco.txt` (etapa 2). FEITA em 24/09.** Proposta em `docs/banco.txt`, atualizada na Sessão C.

**Sessão B — `core/mcp.md` no padrão. FEITA:** publicado no `ai-systems` 0.3.0 (PR #4).

**Sessão C — reescrever `necessidade.md` e `banco.txt` a partir das regras aprovadas. FEITA em 24/09** (commits `070f138` e `357a3f2`). Prompt usado:

> No repositório `~/Documents/Projetos/WCJ/wcj-hub` (faça `git fetch` antes), leia `docs/00-COMECE-AQUI.md` e `docs/regras-para-revisao.md` (revisão 4), que tem as regras aprovadas pelo Lucas Scholz. Respeite a premissa do topo do arquivo: Projetos v1 é kanban simples, sem trava no fluxo; não crie regra nem estrutura de banco que trave. Etapa 1: reescreva `docs/necessidade.md` a partir dele. As regras de cada módulo passam a ser as do arquivo, com os mesmos códigos (G, N, P, C, IA), e substituem os antigos (RN-…, D-PRJ-…, D-CRM-…, D-NUC-…, D-IA-… de produto). A tabela de decisões fica só com as decisões de projeto (D-GER, D-IA-04, D-IA-07, D-CRM-04, D-CHM). Chamados continua como rascunho. Siga a estrutura do template `~/Documents/Projetos/WCJ/ai-systems/.ai/templates/necessidade.md`. Etapa 2: atualize `docs/banco.txt`. Troque as referências pelos códigos novos e aplique o anexo "O que muda no banco.txt" do arquivo de regras. Não decida as perguntas técnicas T6–T11, que são do Lucas Alencar. Não invente regra: o que faltar, pergunte. No fim, apague `docs/regras-para-revisao.md` (o git guarda) para não haver duas fontes, e atualize este arquivo. Um commit por etapa.

**Próximos passos, nesta ordem:**

1. Lucas Alencar revisa o `banco.txt`: T6–T10 (seção 8) e seção 9. Aprovado, as seções 7 e 8 saem do arquivo.
2. Comitê de IA valida necessidade e banco (D-GER-16).
3. Lucas Scholz faz a prova de conceito do MCP (D-GER-17), antes da etapa 4.
4. Etapa 3: repositório na organização WCJ, a partir do template `ai-systems` 0.4.0.
