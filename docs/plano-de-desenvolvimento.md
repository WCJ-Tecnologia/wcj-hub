# Plano de desenvolvimento: WCJ Hub, fatia 1

Escrito em 06/10/2026 para Cauã e Vitinho, que desenvolvem, e para Lucas Scholz, que supervisiona (D-GER-25). Diz quem faz o quê, em que ordem, por qual trilho e como um PR anda até o merge. O **que** construir está no [`escopo.md`](escopo.md) (blocos B0 a B10) e nos [funcionais](funcional/README.md) (F1 a F8); as regras, na [`necessidade.md`](necessidade.md); o banco, no [`banco.md`](banco.md).

## 1. Quem faz o quê

| Pessoa | Papel neste projeto |
|---|---|
| **Cauã** | Desenvolve blocos (com IA: Claude Code ou Codex, no padrão `ai-systems`). Faz o teste cruzado dos PRs do Vitinho. |
| **Vitinho** | Desenvolve blocos, do mesmo jeito. Faz o teste cruzado dos PRs do Cauã. |
| **Lucas Varanda** | Front-end. Tema da marca e fontes no template (`docs/brand/`), shell e os componentes que o Hub pede e o template não tem (kanban, ficha do card, roteiro de QA, linha do tempo), propostos antes de B3 e B5. Revisa o front dos PRs dos blocos (D-GER-29). |
| **Lucas Scholz** | Dono do produto. Aprova os funcionais (F1 a F8, D-GER-31), a spec de todo bloco e todo PR; faz o merge; faz o B0 com IA e a prova de conceito do MCP; responde dúvida de regra. |
| **Rafael Garcia** | Gestor de projetos. Participou dos funcionais F2 a F7 com feedback e elaboração (a aprovação é do Lucas Scholz, D-GER-31); faz o bugbash no quadro Wpay; homologa. |
| **Lucas Alencar** | Confere a diferença do banco depois da aprovação (`banco.md` §7, R15) e revisa o que tocar em `core/mcp.md`. Não desenvolve. |
| **Alaor Rodrigues** | VPS, banco de produção, DNS, backup (B10). |

**Ponto de contato único:** Cauã, Vitinho e Lucas Varanda acionam só o Lucas Scholz, para qualquer dúvida ou trava (`escopo.md` §7). Ele decide ou chama Lucas Alencar e Alaor Rodrigues quando precisar.

Regra do padrão: **quem fez não confere** (`core/testes.md` §10). Spec, PR e teste cruzado sempre passam por outra pessoa, ou pelo Verifier da skill no trilho completo.

## 2. Ordem e divisão

Dois trilhos em paralelo. Um bloco começa quando o funcional dele está aprovado e o bloco de que depende está na `main`.

| Onda | Cauã | Vitinho | Lucas Scholz | Entra na `main` |
|---|---|---|---|---|
| 0 | Prepara a máquina (seção 4); lê os docs; sobe o B0 local | Idem | **B0** repositório, migrações, sementes, CI em MySQL 8 (com IA) ∥ **prova de conceito do MCP**. Lucas Varanda: tema da marca + fontes sobre o B0 (`docs/brand/`) | B0, tema |
| 1 | **B1** Núcleo (trilho completo) | **B2** Projeto e configuração (leve) | Aprova as specs; revisa | B1, B2 |
| 2 | **B3** Quadro e card (leve, com piso completo em excluir e arquivar) | **B5** Sprint, horas e data prevista (leve); integração "arrastar para o To do" fecha quando o B3 entrar | Revisa; configura SMTP Zoho; alinha VPS com o Alaor. Lucas Varanda entrega antes: componentes de kanban, ficha do card e diálogo de sprint | B3, B5 |
| 3 | **B4** Teste e QA, depois **B6** Pacote (leves) | **B7** Indicadores (leve, funções puras com teste) | Revisa; prepara homologação (B10 parcial) | B4, B6, B7 |
| 4 | **B8** Conexões de IA (trilho completo), com o Lucas | **B9** Segurança + `docs/DEPLOY.md` | Testa o MCP com cada assistente | B8, B9 |
| 5 | Correções do bugbash | Correções do bugbash | **B10**: homologação pública → bugbash com o Rafael no quadro Wpay → produção | Produção |

Com B0 a B3 em homologação, o Rafael já começa a usar o quadro Wpay para valer; o resto entra por cima. O Hub passa a gerir o próprio desenvolvimento assim que o B3 entrar (projeto "WCJ Hub", cliente WCJ): até lá, o acompanhamento é pelos PRs abertos no GitHub.

Tamanho estimado de cada bloco, para combinar prazo na primeira reunião (estimativa, não medida; conta dev com IA no padrão): B0 2 a 3 dias; B1 3 a 4; B2 3 a 4; B3 6 a 8; B4 2 a 3; B5 4 a 5; B6 2; B7 3 a 4; B8 5 a 7 (fora a prova de conceito); B9 2; B10 2 a 3. Em paralelo, a fatia 1 inteira fica entre 5 e 7 semanas úteis.

## 3. O rito de um bloco, do começo ao merge

1. **Ler, nesta ordem:** `docs/00-COMECE-AQUI.md`, o `AGENTS.md` do projeto, o funcional do bloco, o bloco no `escopo.md`, as regras citadas na `necessidade.md` e as tabelas do bloco no `banco.md` (com as "Regras do Service"). Dúvida de regra: pergunta ao Lucas Scholz **antes** de escrever a spec, no PR ou no grupo. Nunca decidir regra de negócio sozinho; premissa de baixo risco (ex.: ordem de uma lista) pode ser decidida e dita no PR.
2. **Classificar o trilho** (seção 5). Na dúvida, completo.
3. **Branch** `feat/b<n>-<nome>` a partir de `origin/main` atualizada (`git fetch` antes). Um bloco, uma branch, um PR. Bloco grande (B3) pode sair em 2 ou 3 PRs, na ordem do funcional (quadro e card rápido → ficha completa → arquivar, suporte e avisos), cada um usável sozinho.
4. **Spec.** Trilho completo: a skill `tlc-spec-driven` (`.ai/core/especificacao.md`) em `.specs/features/b<n>-<nome>/`, com os critérios em EARS, os critérios de piso (§6) e a varredura de dimensões implícitas; `validate_spec.py` verde; **Lucas Scholz aprova a spec antes do código** (comentário no PR em rascunho ou no grupo). Trilho leve: a spec curta vai na descrição do PR (modelo na seção 6), e o código pode começar junto.
5. **Código**, com IA, no padrão: FormRequest → `authorize()` → Service; auditoria em toda escrita; a asserção "mesmo projeto" em todo Service de Projetos; migração nenhuma (todas vieram no B0; se o bloco precisar de coluna que não está no `banco.md`, para e pergunta).
6. **Testes** em MySQL 8: um teste de feature por critério da spec; teste de Policy (quem não pode, 403 e nada muda); teste do Service para cada regra do `banco.md`; teste de concorrência onde há contador ou "um só aberto" (T1, T4, P45); função pura com teste para cada cálculo (data prevista, capacidade, indicadores).
7. **Escada local** (`core/testes.md` §1): `composer lint`, `php artisan test`, `npm run typecheck && npm run lint && npm run build`, testes de arquitetura, e2e de smoke, `npm run test:guard`. Tudo verde antes do push.
8. **Uso no navegador:** a tela nova usada de ponta a ponta com os papéis que a tocam (admin, gestor, dev); estados vazio, erro e carregando conferidos; **print ou vídeo curto no PR**.
9. **PR** pronto, com a descrição da seção 6. CI verde.
10. **Teste cruzado** pelo outro dev: sobe a branch, usa a tela seguindo o funcional, comenta no PR o que achou. No trilho completo, antes disso roda o **Verifier** da skill em agente com contexto zerado (`validation.md` com PASS e evidência `arquivo:linha`).
11. **Revisão do Lucas Scholz** contra o funcional e a spec. Correções na mesma branch.
12. **Merge** pelo Lucas Scholz (`squash` ou `merge`, sem reescrever a `main`). Branch apagada. Com o deploy automático ligado (B10), merge = homologação.

Um commit por tarefa, mensagem em Conventional Commits em português (`feat(projetos): card rápido no Backlog`). Nunca `--no-verify` para pular a trava de segredo, exceto falso positivo documentado no PR.

## 4. Máquina de quem desenvolve

- macOS ou Linux. PHP 8.3+, Composer 2, Node 20+, MySQL 8 local (Homebrew ou Docker), git, `gh` autenticado na organização `WCJ-Tecnologia`, Claude Code ou Codex com a assinatura própria (devs PJ pagam a própria IA).
- `git clone git@github.com:WCJ-Tecnologia/wcj-hub.git && cd wcj-hub && composer setup` deve deixar o sistema rodando, com dado de exemplo, em até 3 minutos (meta do padrão; o B0 garante). O `.env` nunca vai para o git; o `.env.example` tem tudo com valor falso.
- Banco de teste MySQL 8 separado (`wcj_hub_test`), apontado no `phpunit.xml` (T6). Sem SQLite.
- Para o MCP (B8): o servidor precisa estar em HTTPS público para o assistente chamar da nuvem; local, usar túnel (ex.: `cloudflared`) ou a homologação.
- Trava de segredo global instalada (`secret-guard` da WCJ) antes do primeiro commit.

## 5. Trilho leve e trilho completo (D-GER-25, R6)

| | Trilho completo | Trilho leve |
|---|---|---|
| Quando | B1 (usuários, papéis, desativação, anonimização), B8 (OAuth, MCP), B9 (segurança), e em qualquer bloco: Policy nova, exclusão de verdade, migração que apaga ou reescreve | B2 a B7: quadro, card, comentário, anexo, horas, sprint, pacote, indicadores |
| Spec | `.specs/features/<bloco>/` pela skill, aprovada pelo Lucas antes do código | Na descrição do PR: objetivo, regras aplicadas (códigos), o que não entra, premissas decididas |
| Verificação | Verifier em agente separado (PASS com evidência), sensor de mutação nas regras de permissão e exclusão, revisão em camadas | Testes por regra + uso no navegador com print; teste cruzado pelo outro dev |
| Critérios de piso (`especificacao.md` §6) | Sim | Sim: permissão (403 e nada muda), regra recusada, auditoria, estados da tela |

Vale a lição do sistema de obras (01/10): o trilho completo em tudo pesou mais do que protegeu. Mas permissão e exclusão nunca vão pelo leve.

## 6. Modelo de descrição do PR

```
## Bloco
B<n> <nome>: PR <k> de <m>. Funcional: docs/funcional/f<n>-<nome>.md. Trilho: leve | completo (.specs/features/<bloco>/).

## O que entra
- <tela ou ação>, conforme F<n> §0<x>.
- ...

## Regras aplicadas
P13, P14, P15 (motivo da pausa), ... Onde: Projetos\Services\TaskService::move.

## Premissas que eu decidi (baixo risco; o Lucas veta no PR)
- <ex.: a lista de etapas na configuração ordena por posição; empate por nome>

## O que NÃO entra neste PR
- <o que fica para o próximo PR do bloco, ou para outro bloco>

## Testes
- Feature: <lista>. Policy: <lista>. Concorrência: <se houver>. Função pura: <se houver>.
- Escada local verde em <data>. CI verde.

## Prints
<tela vazia, tela com dado, ação principal, erro>

## Como testar (para o teste cruzado)
1. ...
```

## 7. Definição de pronto de um bloco

- [ ] Tudo o que o funcional descreve nas seções 01 a 09 existe e foi usado no navegador pelos papéis que o tocam.
- [ ] Nenhum campo, tela ou regra além do funcional e do `banco.md`.
- [ ] Policies com teste (quem não pode: 403 e nada muda); auditoria gravada em toda escrita e negação; canal (tela ou IA) certo.
- [ ] Testes em MySQL 8 verdes no CI; função pura com teste para cada cálculo.
- [ ] Trilho completo: `validation.md` em PASS pelo Verifier; `conferir-specs.mjs` verde.
- [ ] Teste cruzado feito pelo outro dev e revisão do Lucas respondida.
- [ ] `AGENTS.md` do projeto atualizado se o bloco criou vocabulário, armadilha ou "um jeito só".
- [ ] Os "pontos a validar" do funcional que o Lucas Scholz respondeu (com o feedback do Rafael, em Projetos) estão refletidos (ou anotados como pendentes no PR).

## 8. Antes de começar: checklist do Lucas Scholz

- [x] Funcionais F2 a F7 aprovados por Lucas Scholz em 06/10, com o feedback do Rafael (D-GER-31). O PDF para o Rafael sai quando for útil no bugbash.
- [ ] Mandar o `banco.md` §7 para o Lucas Alencar (a R15 em especial).
- [ ] Rodar o B0 (prompt pronto no `00-COMECE-AQUI.md`) e publicar o repositório com o template; conferir `composer setup` em máquina limpa.
- [ ] Prova de conceito do MCP (D-GER-17): servidor mínimo, OAuth, uma ferramenta de leitura e uma de escrita, em Claude app, Claude Code, Codex e ChatGPT Pro. Registrar o resultado em `docs/atas/` e fechar os "Em aberto" da `necessidade.md`.
- [ ] Credenciais do SMTP Zoho num cofre (não no repo) para o B3.
- [ ] Reunião de 30 minutos com Cauã e Vitinho: este plano, o rito, a divisão da onda 1, o canal de dúvidas, o prazo combinado.
- [ ] Passar ao Lucas Varanda o `docs/brand/README.md` (a lista do que fazer está lá, na ordem) e combinar quando o tema entra em relação ao B0.
- [ ] Alinhar com o Alaor a VPS (D-GER-19): espaço, MySQL 8 em container, backup, DNS `hub.wcjtecnologia.com.br`.
- [ ] Combinar com o Rafael a data do bugbash no quadro Wpay (depois de B0 a B3 em homologação).

## 9. Riscos e como tratar

| Risco | Tratamento |
|---|---|
| ~~Funcional do Rafael demora e trava a onda 1~~ | Caiu: os funcionais foram aprovados pelo Lucas Scholz em 06/10 (D-GER-31). O que resta do Rafael é o bugbash. |
| Dois PRs em paralelo conflitam (`AGENTS.md`, rotas, `types/index.d.ts`) | Quem entra depois faz `git merge origin/main` na branch, roda a escada e empurra. Nunca `rebase` em branch já compartilhada. |
| IA inventa campo ou trava | O escopo §6 lista o que não pode; o revisor confere tabela a tabela contra o `banco.md`; teste de arquitetura recusa `timestamps()` e migração fora do padrão. |
| MCP não funciona como o esperado em algum assistente | A prova de conceito vem antes do B8; o produto não depende do B8 para substituir o Trello. |
| Template 0.6.0 difere do que a seção 9 do `banco.md` descreve | O B0 confere e registra no ADR-001; o `sync-standards` do padrão é rodado no B0 e a cada versão nova do `ai-systems`. |
| Equipe volta ao Trello por fricção na tela | R4 e R5 existem para isso: card com título + Enter e sprint sem cerimônia. Bugbash com o Rafael antes de desligar o Trello. |
