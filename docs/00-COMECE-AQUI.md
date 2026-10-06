# Comece aqui: WCJ Hub

Documento de entrada. Quem abre este repositório, pessoa ou IA, lê isto antes de qualquer outra coisa.

## O produto em um parágrafo

Sistema interno da WCJ Tecnologia com três módulos sobre um núcleo comum: **Projetos** (substitui o Trello, com controle de tempo, num kanban sem trava no fluxo), **CRM** (leads, tratativas, compromissos; **v2**) e **Chamados** (último, sem demanda ainda). Cada usuário interno conecta a **própria IA** (Claude, Codex; ChatGPT) para consultar e alterar dados com as mesmas permissões da tela. **Fatia 1** (D-GER-24): Núcleo + Projetos + MCP com 6 ferramentas. Desenvolvido por **Cauã e Vitinho, com IA**, supervisão de Lucas Scholz (D-GER-25), no padrão `ai-systems` 0.6.0 em trilho leve.

## Estado atual

Atualizado em 2026-10-06.

| Etapa (playbook `aplicacao-nova`) | Estado | Onde |
|---|---|---|
| 1. Necessidade | **Fatia 1 aplicada em 06/10** (D-GER-23 a D-GER-28): revisão de 01/10 aceita por inteiro (R1 a R17); CRM marcado como v2; P17, P22, P32, P34, P40, P42, P45, P47, P48, P53, P56 reescritas. Antes: Sessão C e D (24/09). Chamados é rascunho (D-GER-12) | `docs/necessidade.md` |
| 2. Banco | **APROVADO por Lucas Alencar em 06/10** (41 tabelas, D-GER-23). No mesmo dia virou a **fatia 1: 27 tabelas** (R7 a R15). A diferença para o aprovado está na seção 7, para ele conferir (em especial a R15: asserção no Service no lugar das FKs compostas). As colunas geradas foram testadas no MySQL 8.4 | `docs/banco.txt` |
| Revisão das regras | **Encerrada.** Incorporada à `necessidade.md` na Sessão C; o arquivo `docs/regras-para-revisao.md` foi apagado (o git guarda as revisões 1 a 4, até o commit `da44c03`) | `docs/necessidade.md` |
| Funcionais | **Escritos em 06/10**, versão 0.1 para aprovação: F1 a F8 em `docs/funcional/` (D-GER-28). **F1, F2 e F3 APROVADOS por Lucas Scholz em 06/10 (v1.0)**; F4 a F7 aguardam validação (Lucas Scholz está passando por eles; Rafael Garcia valida no uso); F8, Lucas Scholz. Cada um tem a seção 10 "Pontos a validar" | `docs/funcional/README.md` |
| 4. Escopo | **Escrito em 06/10**: blocos B0 a B10, apontando para os funcionais; aguarda validação inteira por uma pessoa (etapa 5) | `docs/escopo.md` |
| Marca no template | **`docs/brand/` criada em 06/10**: logo em vetor, ícones, cores, nós, fontes, Brand Board e o rascunho `tokens.wcj.json` para o Lucas Varanda aplicar no template (D-GER-29) | `docs/brand/README.md` |
| Plano de desenvolvimento | **Escrito em 06/10**: ondas, divisão Cauã × Vitinho, rito do PR, trilhos, checklist do Lucas antes de começar | `docs/plano-de-desenvolvimento.md` |
| 3. Repositório | **Na organização WCJ desde 29/09**, privado, só com `docs/`. O template `ai-systems` 0.6.0 entra no **B0** (etapa 3), próximo passo prático. O backup antigo na conta pessoal (`lucas-scholz-91/wcj-hub`) deixou de ser a fonte | https://github.com/WCJ-Tecnologia/wcj-hub |
| Teste técnico da IA (MCP) | A fazer, por Lucas Scholz (D-GER-17) | `docs/necessidade.md`, "Assistentes de IA" |
| `core/mcp.md` no padrão | **Publicado** no `ai-systems` 0.3.0 (PR #4) | `~/Documents/Projetos/WCJ/ai-systems/.ai/core/mcp.md` |

**Revisão de 01/10/2026** ([`docs/revisoes/2026-10-01-revisao-fable.md`](revisoes/2026-10-01-revisao-fable.md)): **aceita por inteiro por Lucas Scholz em 06/10** (R1 a R17) e aplicada na necessidade e no banco (D-GER-24 a D-GER-27). Fica como registro do porquê.

## Hierarquia dos documentos

1. **Tabela "Decisões registradas"** no topo do `docs/necessidade.md`: vence qualquer texto mais abaixo e qualquer ata. Guarda só decisões de projeto.
2. **`docs/necessidade.md`**: o requisito consolidado. As regras de produto são as G, N, P, C e IA; em Projetos, a premissa (kanban sem trava) vale acima de todas.
3. **`docs/banco.txt`**: o modelo de dados aprovado; cada coluna aponta para a regra que a usa.
4. **`docs/funcional/`**: como cada bloco funciona na tela, aprovado pelo dono do módulo (D-GER-28). Manda em tela, campo, ação e permissão.
5. **`docs/escopo.md`**: os blocos técnicos (tabelas, rotas, Services, ordem). Manda no técnico; não contraria o funcional.
6. **`docs/plano-de-desenvolvimento.md`**: quem faz o quê e o rito do PR.
7. **`docs/atas/`**: o que foi dito em cada reunião (fonte, não resumo final).
8. **`docs/levantamento/`**: análises de apoio (Trello, modelos, fluxo de desenvolvimento).
9. **`docs/pautas/`**: histórico de preparação; não é requisito. Os códigos antigos citados lá (RN-…, D-PRJ-…) não valem mais.
10. **`docs/revisoes/`**: revisões propostas, com tabela de decisão. A de 01/10 foi aplicada em 06/10.

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
- Aviso só no sistema (P22 de 24/09) → **no sistema e por e-mail** ao definir dev ou QA e ao devolver (R3, 06/10).
- Só o gestor e o Administrador criam card (P56 de 24/09) → **qualquer pessoa alocada cria card no `Backlog`** (R2, 06/10).
- Flags de validação por projeto e por tarefa e parecer por validação (P32 de 24/09) → **sem flags nem parecer**; teste cruzado é marca na tarefa (R8, 06/10).
- Time da sprint com dias disponíveis (P47, P48 de 24/09) → **capacidade = carga × dias úteis da sprint**, sem time cadastrado (R9, 06/10).
- Estados de pacote cadastráveis (P42 de 24/09) → **estado derivado de duas datas** (R10, 06/10).
- Motivos de retrabalho por projeto (P40 de 24/09) → **lista geral do Administrador** (R11, 06/10).
- Tabela de chamadas da IA com tela (IA12 de 24/09) → **log em arquivo** (R13, 06/10).
- Movimento do card apontando para a auditoria (T10) → **`moved_by` + canal na própria linha** (R14, 06/10).
- FKs compostas por projeto (T7) → **asserção no Service** (R15, 06/10; a conferir pelo Lucas Alencar).
- v1 com CRM e GPT do Wilson → **fatia 1 = Núcleo + Projetos + MCP**; CRM é v2 (R1, D-GER-24).
- Execução Lucas Scholz + Cauã com Claude (D-GER-17) → **Cauã e Vitinho com IA, supervisão do Lucas Scholz** (D-GER-25).
- Excluir card por `deleted_at` (anexo da revisão 4) → **padrão 0.4.0**: card nunca usado se exclui; com histórico, se arquiva (P26). Lead se desativa (C16).
- Dev vê o próprio painel de produtividade → **só gestor e Administrador** (P54).
- Horas previstas informadas pelo dev, por pessoa, e várias pessoas por função na tarefa → **1 dev + QA; horas da tarefa inteira, opcionais ao criar, definidas ou validadas pelo gestor na sprint** (P20, P27).
- Tarefa usada e abandonada fica no Backlog → **arquivada com motivo** (P26).
- Preço da auditoria no sizing → **é o valor do lead**, obrigatório para ganhar (C2, C12).
- "Tipos de contrato" → **tipos de serviço**; contrato fora da v1 (N10).
- Testes em SQLite, como no template → **MySQL 8 em tudo**, local e no CI (D-GER-22).
- Tech Lead por projeto e modalidade "100% IA" registrados no sistema → **não ficam no sistema**: a equipe combina e o gestor acompanha (P4 e P6 saíram na Sessão D).
- Tabelas de escopos da IA (`ai_scopes`, `ai_authorization_scopes`) → **escopos no Passport**, que já os guarda em cada token (Sessão D).

## Próximas sessões

**Sessão A — `docs/banco.txt` (etapa 2). FEITA em 24/09.** Proposta em `docs/banco.txt`, atualizada na Sessão C.

**Sessão B — `core/mcp.md` no padrão. FEITA:** publicado no `ai-systems` 0.3.0 (PR #4).

**Sessão C — reescrever `necessidade.md` e `banco.txt` a partir das regras aprovadas. FEITA em 24/09** (commits `070f138` e `357a3f2`). Prompt usado:

> No repositório `~/Documents/Projetos/WCJ/wcj-hub` (faça `git fetch` antes), leia `docs/00-COMECE-AQUI.md` e `docs/regras-para-revisao.md` (revisão 4), que tem as regras aprovadas pelo Lucas Scholz. Respeite a premissa do topo do arquivo: Projetos v1 é kanban simples, sem trava no fluxo; não crie regra nem estrutura de banco que trave. Etapa 1: reescreva `docs/necessidade.md` a partir dele. As regras de cada módulo passam a ser as do arquivo, com os mesmos códigos (G, N, P, C, IA), e substituem os antigos (RN-…, D-PRJ-…, D-CRM-…, D-NUC-…, D-IA-… de produto). A tabela de decisões fica só com as decisões de projeto (D-GER, D-IA-04, D-IA-07, D-CRM-04, D-CHM). Chamados continua como rascunho. Siga a estrutura do template `~/Documents/Projetos/WCJ/ai-systems/.ai/templates/necessidade.md`. Etapa 2: atualize `docs/banco.txt`. Troque as referências pelos códigos novos e aplique o anexo "O que muda no banco.txt" do arquivo de regras. Não decida as perguntas técnicas T6–T11, que são do Lucas Alencar. Não invente regra: o que faltar, pergunte. No fim, apague `docs/regras-para-revisao.md` (o git guarda) para não haver duas fontes, e atualize este arquivo. Um commit por etapa.

**Sessão D — revisão do `banco.txt` pelo Lucas Scholz. FEITA em 24/09.** De 44 para 41 tabelas; o que saiu, o que entrou e o que ficou como está, cada item com o motivo, está no `banco.txt`, seção 7, "Sessão D". Prompt usado:

> No repositório `~/Documents/Projetos/WCJ/wcj-hub` (faça `git fetch` antes), leia `docs/00-COMECE-AQUI.md`, as regras de `docs/necessidade.md` (a premissa de Projetos e as regras G, N, P, C e IA) e `docs/banco.txt`. Vou revisar o banco antes de ele ir para o Lucas Alencar. Conduza a revisão por domínio, na ordem Platform, Projetos, CRM. Para cada tabela, em linguagem simples, diga para que serve, quais regras a usam e o que chama atenção: coluna sem regra que a use, trava que contradiga a premissa, dado repetido. Mostre um domínio por vez e espere a minha resposta antes de seguir. Não reabra o que já está decidido (tabela de decisões da `necessidade.md` e T1–T11 do banco), a não ser que eu peça. No fim, aplique no `banco.txt` o que eu mudar, atualize este arquivo e faça um commit.

**Próximos passos, nesta ordem** (detalhe em `docs/plano-de-desenvolvimento.md`, seção 8):

1. Rafael Garcia aprova F2 a F7 e responde os "pontos a validar"; Lucas Scholz aprova F1 e F8 (D-GER-28).
2. Lucas Alencar confere o `banco.txt` §7 (a R15 em especial). Não bloqueia o B0.
3. Lucas Scholz valida o `docs/escopo.md` inteiro (etapa 5) e assina.
4. **B0** (etapa 3): template `ai-systems` 0.6.0 neste repositório, por domínio, 27 migrações, sementes, CI em MySQL 8, `AGENTS.md`, ADR-001 e ADR-002. Prompt abaixo.
5. Prova de conceito do MCP (D-GER-17), em paralelo ao B0.
6. Reunião de partida com Cauã e Vitinho; onda 1 (B1 ∥ B2).

**Sessão E — B0, repositório e base.** Prompt pronto:

> No repositório `~/Documents/Projetos/WCJ/wcj-hub` (faça `git fetch` antes; a `main` está só com `docs/`), leia `docs/00-COMECE-AQUI.md`, `docs/escopo.md` (bloco B0 e seções 2, 3 e 6), `docs/banco.txt` (inteiro, inclusive a seção 9) e `docs/plano-de-desenvolvimento.md`. Aplique o template `ai-systems` 0.6.0 mantendo o histórico (receita que funcionou no projeto-oficinas: `git -C ../ai-systems archive HEAD | tar -x -C .`, depois `node scripts/new-project.mjs "WCJ Hub"`; o `.gitattributes` do template tem `/.github export-ignore`, então copie o `ci.yml` à mão; o archive sobrescreve `.gitignore` e `README.md`: reponha o que é do projeto). Reorganize por domínio (`app/Platform/`, `app/Domains/Projetos/`) e registre a ADR-001. Aplique a seção 9 do `banco.txt` (roles no lugar de user_types; saem enterprises e o escopo por empresa; audit_logs com ai_authorization_id, outcome e changes) e confira o que a 0.6.0 mudou em relação ao que a seção 9 descreve, anotando no ADR. Crie as 27 migrações do `banco.txt` na ordem das dependências, exatamente como escritas (colunas geradas, checks, uniques, `datetime` em tudo, `parent_key` VIRTUAL), mais Passport e notifications ajustados para datetime. Sementes dos domínios fechados, do cliente WCJ e do admin. Testes em MySQL 8 local e no CI (ADR-002), nunca SQLite. `composer setup` do zero em até 3 minutos, cronometrado. Preencha o `AGENTS.md` do projeto (vocabulário da necessidade.md, trilhos do plano, armadilhas da seção 9). Nada de tela nem de regra de negócio: isso é B1 em diante. Abra o PR, com a saída da escada e o tempo do setup na descrição.
