# Comece aqui: WCJ Hub

Documento de entrada. Quem abre este repositório, pessoa ou IA, lê isto antes de qualquer outra coisa.

## O produto em um parágrafo

Sistema interno da WCJ Tecnologia com três módulos sobre um núcleo comum: **Projetos** (substitui o Trello, com controle de tempo), **CRM** (leads, tratativas, compromissos) e **Chamados** (último, sem demanda ainda). Cada usuário interno conecta a **própria IA** (Claude, Codex; ChatGPT pelo GPT personalizado) para consultar e alterar dados com as mesmas permissões da tela. Projeto **100% IA**: executado por Lucas Scholz e Cauã com Claude, seguindo o padrão `ai-systems` por inteiro.

## Estado atual

Atualizado em 2026-09-24.

| Etapa (playbook `aplicacao-nova`) | Estado | Onde |
|---|---|---|
| 1. Necessidade | **Fechada para Núcleo, Projetos e CRM**; aguarda validação no Comitê de IA. Chamados é rascunho (D-GER-12) | `docs/necessidade.md` |
| 2. Banco | **Proposta da IA pronta (24/09)** para Núcleo, Projetos e CRM. Todas as dúvidas (seção 7) respondidas pelo Lucas Scholz em 24/09, inclusive sprint com time e capacidade por pessoa (D-PRJ-23, D-PRJ-24). Aguarda revisão do Lucas Alencar; depois, Comitê (D-GER-16) | `docs/banco.txt` |
| 3. Repositório | Ainda não. Hoje este repo só tem `docs/`, com **backup privado na conta pessoal** (`lucas-scholz-91/wcj-hub`). Depois de banco e escopo validados, cria-se o repositório **na organização WCJ** com o template (etapa 3) | https://github.com/lucas-scholz-91/wcj-hub |
| Teste técnico da IA (MCP) | A fazer, por Lucas Scholz (D-GER-17) | `docs/necessidade.md`, Interface de IA |
| `core/mcp.md` no padrão | A rascunhar, em **outra sessão**, no repositório `ai-systems` (D-GER-16) | `~/Documents/Projetos/WCJ/ai-systems` |

## Hierarquia dos documentos

1. **Tabela "Decisões registradas"** no topo do `docs/necessidade.md`: vence qualquer texto mais abaixo e qualquer ata.
2. **`docs/necessidade.md`**: o requisito consolidado.
3. **`docs/atas/`**: o que foi dito em cada reunião (fonte, não resumo final).
4. **`docs/levantamento/`**: análises de apoio (Trello, modelos, fluxo de desenvolvimento).
5. **`docs/pautas/`**: histórico de preparação; não é requisito.

## Decisões que foram revertidas (não usar)

Aparecem em atas ou no histórico do git, mas **não valem mais**:

- Nome "WCJ Central" → **WCJ Hub** (D-GER-06).
- Integração com GitHub por webhook na v1 → **fora da v1, em avaliação** (D-GER-13). PR entra só como link.
- Migração de dados do DaaS (leads, oportunidades, contratos) → **sem migração** (D-CRM-03). As funções vêm; os dados, não.
- Ordem Núcleo → Projetos → Chamados → CRM → **Núcleo → Projetos → CRM → Chamados** (D-GER-12).
- Fluxo travado (PR obrigatório, só Tech Lead move) → **fluxo aberto, travas opcionais** (D-GER-05, D-PRJ-16).
- Dev vê o próprio painel de produtividade → **só gestor e Administrador** (RN-PRJ-22).

## Próximas sessões

**Sessão A — `docs/banco.txt` (etapa 2). FEITA em 24/09**: proposta em `docs/banco.txt`; falta a revisão do Lucas Alencar. Prompt usado:

> Leia `docs/00-COMECE-AQUI.md` e `docs/necessidade.md` do repositório `~/Documents/Projetos/WCJ/wcj-hub`, e as regras de banco em `~/Documents/Projetos/WCJ/ai-systems/.ai/core/banco.md` e `.ai/playbooks/aplicacao-nova.md` (etapa 2). Proponha o `docs/banco.txt` do WCJ Hub para **Núcleo (Platform), Projetos e CRM**, com todas as tabelas, colunas, tipos, chaves, índices e valores de domínio, seguindo a estrutura por domínio (D-GER-15). Deixe Chamados de fora, mas garanta que o Núcleo comporte o módulo depois. Liste no fim as dúvidas para o Lucas Alencar. Não crie código nem migração.

**Sessão B — `core/mcp.md` no padrão.** Modelo sugerido: Opus, esforço médio. Prompt:

> No repositório `~/Documents/Projetos/WCJ/ai-systems` (compartilhado: faça `git fetch` e confira `origin/main` antes), rascunhe `.ai/core/mcp.md`: regras da casa para sistemas que expõem servidor MCP às IAs dos usuários. Base: a seção "Interface de IA (MCP)" de `~/Documents/Projetos/WCJ/wcj-hub/docs/necessidade.md` (RN-IA-01 a 09, D-IA-01 a 07). Generalize para qualquer sistema, sem citar o WCJ Hub nas regras. Siga o tom e o formato dos outros arquivos de `.ai/core/`. Branch nova e PR; revisor: Lucas Alencar. Vale principalmente para sistemas 100% IA (D-GER-09).
