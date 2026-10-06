# Playbook: manutenção e evolução

## Quando usar

Sistema que já existe vai ganhar funcionalidade, mudança de regra, módulo novo ou ajuste. Para bug, use `bug.md`. Para produção fora do ar, `incidente.md`.

## Atalho para ajuste trivial

Trivial é o que **não toca banco, rota, permissão nem regra de negócio**: texto, cor, espaçamento, rótulo, ordem de coluna. Nesse caso faça direto, sem a skill. Não gaste contexto lendo o projeto inteiro para mover um pixel.

Na dúvida, não é trivial.

## O que precisa existir antes

- `AGENTS.md` do projeto lido. Se não existe, criá-lo é a primeira tarefa (modelo em `templates/AGENTS.projeto.md`).
- `git fetch` feito e branch do ambiente identificada (`core/git.md`, seção 1).
- Se o repositório é novo para você ou é legado: a seção "Código legado" abaixo foi feita, antes de qualquer feature.

## O fluxo: a skill `tlc-spec-driven`

Toda mudança que não é trivial passa pela skill (`core/especificacao.md`). Ela especifica, desenha, quebra em tarefas, implementa e verifica, e mede sozinha quanto de cada fase a mudança pede. Os documentos ficam em `.specs/features/<assunto>/`. A feature é o PR; cada tarefa, um commit.

O que o padrão exige junto, fase a fase:

| Fase da skill | O que o padrão acrescenta | Parada |
|---|---|---|
| Specify | O pedido e o código afetado lidos, citando `arquivo:linha` do que vai mudar ("provavelmente está em X" não vale). Vocabulário e armadilhas do `AGENTS.md` do projeto conferidos. Ata ou pedido escrito do cliente é a fonte do requisito. O Out of Scope diz também o que **não** muda, para ninguém "aproveitar e arrumar". Faltou informação: pergunte; premissa só vale confirmada | **Spec aprovada** |
| Specify, se crítico | Mudança de schema, de autenticação ou permissão, de regra que afeta dinheiro ou estoque, de integração com terceiro, ou qualquer coisa com efeito em dado de produção: **pare e peça aprovação técnica**. A decisão vira ADR (`templates/adr.md`). Aprovado o ADR, registre no `.specs/STATE.md` um `AD-NNN` que cite o arquivo. O desenvolvimento só segue depois | **Sim** |
| Design | Componentes existentes a reutilizar (`stacks/ui.md`): não crie um segundo. Rotas novas ou alteradas, com a Policy de cada uma. Migrações, com a ordem de deploy deste projeto (`docs/DEPLOY.md`) | **Design aprovado**, quando existe |
| Tasks | Fases fixas quando a mudança toca a área: **segurança** (rota, permissão, entrada de dado, upload, sessão), **banco** (migração separada do código, com a ordem de deploy escrita) e **UI** (porta de entrada para tudo que foi criado). A ordem: banco → servidor → tela, cada tarefa com o seu teste; ajuda e documentação vão no commit da tarefa que muda o comportamento. Todo caminho de escrita da regra tem teste, inclusive operação em massa | **Tarefas aprovadas**, quando existem |
| Execute | Regra de negócio no servidor, no ponto que efetiva a ação; a tela só reflete. Mudou rótulo, botão ou fluxo: ajuda e documentação no mesmo commit | |
| Execute, no fim: o Verifier | O PASS do Verifier e, depois dele, as 4 perguntas por entidade (criar, listar, editar, desfazer), a revisão em camadas e o print ou vídeo da tela no PR (`core/testes.md`, seção 10). `AGENTS.md` do projeto atualizado se surgiu armadilha nova | |

A skill decide quando pula o desenho e as tarefas. Quando ela pula, as exigências dessas fases continuam valendo dentro da fase seguinte.

## Auditoria periódica de portas de entrada

A cada duas ou três fases de evolução, varra o sistema atrás dos sete cheiros:

1. serviço ou classe sem quem o chame;
2. rota sem link ou botão que chegue nela;
3. ação sem retorno visível;
4. lista sem forma de criar o primeiro item;
5. campo gravado sem tela que edite;
6. limite de linhas silencioso, sem busca nem paginação;
7. dado gravado que nenhuma tela lê.

Ausência proposital é escrita em comentário no código, no ponto onde alguém tentaria reintroduzir, e listada em `docs/AUDITORIA-PORTAS-DE-ENTRADA.md`.

## Código legado ou repositório novo para você

Nesta ordem, antes de qualquer feature:

1. **Instrumentar.** Build, lint e testes rodando do jeito que o código está, com retorno real. O que apodreceu fica catalogado. Sem sensor, o agente trabalha no escuro.
2. **Caçar ambiguidades.** Peça ao agente a lista de onde o mesmo problema é resolvido de jeitos diferentes: erro tratado de três formas, duas bibliotecas de teste, consulta no controller num lugar e no Service em outro, `new` direto ao lado de injeção. O agente lê os três jeitos e escolhe um ao acaso; muito do "a IA alucinou" é o projeto inconsistente. O jeito certo de cada caso vai para o `AGENTS.md` do projeto (seção "Um jeito só"), e os outros saem aos poucos, em PRs de refatoração estrutural, um de cada vez.
3. **Refatorar atrás de teste de caracterização** (`core/testes.md`, seção 7).

## Dois tipos de refatoração

| Tipo | O que é | Como se prova |
|---|---|---|
| Estrutural | Mover, renomear, extrair, dividir arquivo. Nenhuma lógica muda. | Build e suíte verdes antes e depois. Commit `refactor:` sozinho. |
| De comportamento | Muda o que o sistema faz ou como calcula. | Flag ou troca gradual: o antigo e o novo convivem, com teste de caracterização comparando os dois, até o novo ser aprovado. |

Nunca os dois no mesmo commit. Quem revisa não consegue separar o que mudou de lugar do que mudou de regra.

## Código trazido de outro projeto

- Entra com a lista de bugs conhecidos do original, para corrigir na adaptação.
- Entra com a lista explícita do que **não** pode atravessar: dado de cliente, credencial, marca.
- Código portado congela na data do porte. Ao voltar ao projeto de origem, liste o que entrou depois e decida item a item.

## Pontos de parada

Spec aprovada, e design e tarefas quando a skill os produz; mudança crítica, com ADR; mais `core/principios.md`, seção 5. Duas sessões de IA no mesmo repositório: pare e combine.

## Definição de pronto

- [ ] A spec aprovada diz o que muda e o que não muda, e foi respeitada.
- [ ] Mudança crítica tem ADR aprovado.
- [ ] Escada de verificação verde, com a saída lida.
- [ ] Teste cobre todo caminho de escrita da regra. Regra de dinheiro, permissão ou escopo passou pela mutação completa.
- [ ] O Verifier deu PASS no `validation.md` da feature, e o `node .ai/skills/conferir-specs.mjs` está verde.
- [ ] Tela que mudou tem print ou vídeo do Playwright no PR.
- [ ] Refatoração estrutural e mudança de comportamento em commits separados.
- [ ] Porta de entrada para tudo que foi criado.
- [ ] Documentação e ajuda atualizadas no mesmo commit.
- [ ] Feature pronta virou PR no mesmo dia.
