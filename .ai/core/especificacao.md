# Especificação: o fluxo de feature

Toda feature e toda mudança que não é trivial passam pela skill `tlc-spec-driven`, do Tech Leads Club. O time a adotou inteira, sem alteração, em 29/09/2026. Ela especifica, desenha, quebra em tarefas, implementa com um commit por tarefa e, no fim, chama um verificador que não escreveu o código. Este arquivo diz como a WCJ a usa: o que ela substituiu no padrão e as regras da casa que valem sobre ela.

A skill mora em `.ai/skills/tlc-spec-driven/`, com versão e origem em `.ai/skills/README.md`. Cada ferramenta a lê da própria pasta: `.claude/skills`, `.cursor/skills` e `.codex/skills`.

## 1. Quando usar

| Pedido | Caminho |
|---|---|
| Trivial: texto, cor, espaçamento, rótulo. Não toca banco, rota, permissão nem regra de negócio | direto, sem a skill (inegociável 7) |
| Feature nova, mudança de regra, módulo novo, ajuste que toca banco, rota, permissão ou regra | a skill |
| Bug | `playbooks/bug.md`. Se a correção muda um comportamento combinado, vira feature na skill |
| Aplicação nova | `playbooks/aplicacao-nova.md`: necessidade, banco e escopo do sistema vêm antes; cada bloco do escopo é uma feature na skill |

A skill mede sozinha o tamanho da mudança (pequena, média, grande ou complexa) e pula o desenho e as tarefas quando não precisa. Especificar e verificar ela nunca pula.

Para chamar, diga "use a skill tlc-spec-driven para <a feature>" ou use os gatilhos dela: specify, design, tasks, implement, validate, pause work, resume work. Pedido em português também a aciona; na dúvida, cite o nome.

## 2. Formato dos documentos

Decisão do time em 29/09/2026: **a estrutura é a da skill, em inglês, e o texto é em português.** Título de seção, nome de campo e palavra-chave de critério ficam como a skill escreve, porque os validadores dela procuram exatamente essas palavras. O conteúdo, os nomes da tela e o vocabulário do produto ficam em português.

O critério de aceite é escrito em EARS, um por linha e sempre com SHALL:

| Padrão | Para quê | Exemplo |
|---|---|---|
| WHEN … THEN … SHALL | resposta a um evento | `WHEN o sócio clica em **Iniciar obra** THEN o sistema SHALL gravar a data real do início` |
| IF … THEN … SHALL | erro, recusa, entrada inválida | `IF a obra está entregue THEN o sistema SHALL recusar tarefa comum nova com "obra entregue só aceita tarefa de garantia"` |
| WHILE … SHALL | o que vale enquanto um estado dura | `WHILE o período está fechado o sistema SHALL recusar lançamento com data dentro dele` |
| WHERE … SHALL | o que só existe com um módulo ou flag ligado | `WHERE o módulo de impostos está ligado o sistema SHALL mostrar o imposto de cada linha do orçamento` |
| The system SHALL | regra que vale sempre | `The system SHALL mostrar valor em dinheiro sem abreviar` |

O "Dado" do antigo Dado/Quando/Então vira WHILE, quando é um estado, ou entra na frase do WHEN ou do IF. O que vale com o módulo desligado é IF ou WHILE: `IF o módulo de impostos está desligado THEN o sistema SHALL gravar o orçamento sem imposto`.

Regras de forma. Os validadores da skill dependem delas, e o `conferir-specs.mjs` reprova o que foge:

- **Critérios.** Cada story (`### P1`, `### P2`…) tem a lista numerada de critérios sob o título em inglês `**Acceptance Criteria**:`. Sozinho, o `validate_spec.py` 3.3.0 só confere a lista colada no título e para na primeira linha em branco. O modelo da própria skill e o Prettier põem linha em branco ali, e então nenhum critério é conferido. O `conferir-specs.mjs` resolve isso sem mudar o formato: manda ao validador uma cópia com o título e as linhas em branco da lista normalizados. Título traduzido não é lido, e story sem nenhum critério lido reprova.
- **Um critério por linha, e um comportamento por critério.** "Responder 403" e "não criar o registro" são dois critérios. O SHALL que cai na linha de baixo não é lido.
- **Valor exato.** O critério diz o resultado que o teste vai afirmar: "403", e não "403 ou 404". O que ainda não se sabe é premissa, não critério vago. O Verifier aponta o critério impreciso como lacuna.
- **ID de requisito** no formato `AREA-NN`: maiúsculas sem acento e números, um hífen só e o número no fim (`OBRA-01`, `ORCAMENTO-03`; `OBRA-ITEM-01` não passa). O validador confere o ID na tabela Requirement Traceability.
- **Títulos de seção** exatamente como no modelo. Na spec: Problem Statement, Out of Scope, Assumptions & Open Questions, User Stories, Requirement Traceability. Nas tarefas: Test Coverage Matrix, Gate Check Commands, Execution Plan, Task Breakdown.
- **Fases das tarefas.** No `tasks.md`, repita o `### Phase N` no Task Breakdown, antes das tarefas de cada fase. Com o título da fase só no Execution Plan, como no modelo da skill, o `validate_tasks.py` põe toda tarefa na última fase. Aí ele deixa de pegar dependência para a frente e reprova dependência legítima entre fases.
- **Palavras que os validadores procuram, em inglês e do jeito que estão:**
  - `**Open questions:** none`, quando não sobrou pergunta;
  - `The system SHALL`, no critério que vale sempre;
  - no `validation.md`, o veredito `PASS` ou `FAIL` em maiúsculas, num título `## Validation` ou numa linha `**Result**:`, com evidência `arquivo:linha`.
- **Aprovação.** Logo abaixo do título da spec vai a linha `**Approved by**: <nome>, <AAAA-MM-DD>`, preenchida quando a spec é aprovada. Premissa sem "y" em "Confirmed?" segura a aprovação. Quando a feature já passou da especificação (tem `design.md`, `tasks.md` ou `validation.md`), o `conferir-specs.mjs` reprova quatro coisas: a falta do `spec.md`, a spec sem aprovação, a premissa sem "y" e qualquer sobra do modelo (`[texto]` fora de código e de link).

## 3. Onde as coisas moram

Tudo em `.specs/`, na raiz do projeto, e **tudo no git**. Trabalho que não está no git some.

| Arquivo | O que é | Quem escreve |
|---|---|---|
| `.specs/features/<feature>/spec.md` | requisitos com ID, critérios em EARS, fora do escopo, premissas | a skill; o humano aprova |
| `context.md` | decisões das áreas cinzentas, quando houve discussão | a skill, com o humano |
| `design.md` | arquitetura, reuso, riscos | a skill; o humano aprova |
| `tasks.md` | tarefas atômicas, matriz de testes, comandos de verificação | a skill; o humano aprova |
| `validation.md` | relatório do Verifier: veredito, evidência por critério, mutações | o Verifier |
| `.specs/STATE.md` | decisões do projeto (`AD-NNN`) e o ponto de retomada (Handoff) | a skill |
| `.specs/LESSONS.md` e `.specs/lessons.json` | lições tiradas das falhas do Verifier | só o `lessons.py`, nunca à mão |

A pasta da feature tem nome minúsculo, sem acento e com hífen (`iniciar-obra`), como todo arquivo (`git.md`, seção 5).

O `validation.md`, as lições e o Handoff nascem depois do commit da última tarefa. Eles entram num commit `docs(<feature>): validação` antes do PR, e a árvore termina limpa.

Em branches paralelas, o Handoff do `STATE.md` é o da feature daquela branch; no merge, fica o de quem continua trabalhando. O `AD-NNN` é sequencial: em conflito no rebase, renumere o seu.

## 4. O que a skill substituiu

| Até a 0.5 | Agora |
|---|---|
| Critério de aceite em Dado/Quando/Então no `escopo.md` | EARS na `spec.md` da feature |
| Bloco do `escopo.md` com critério de aceite | o `escopo.md` descreve o sistema, e cada bloco vira `.specs/features/<bloco>/` |
| `docs/complementos/NN-*.md` | feature nova em `.specs/features/`; ideia que aparece no meio vai para "Deferred Ideas" |
| `docs/mudancas/<data>-<assunto>.md` | `.specs/features/<assunto>/` |
| Verificador com contexto zerado | o Verifier da skill (`validation.md`) |
| "Ponto de retomada" do `00-COMECE-AQUI.md` | Handoff do `.specs/STATE.md`; o COMECE-AQUI guarda o estado do produto |

O que já existe em `docs/complementos/` e `docs/mudancas/` fica como histórico. Trabalho em andamento termina no formato em que começou; o próximo começa em `.specs/`.

## 5. Regras da casa sobre a skill

Os 12 inegociáveis, as paradas e as regras abaixo valem mais que a skill. Onde eles se tocam:

1. **Aprovar a spec ou as tarefas não autoriza parada.** Modelar banco, migração destrutiva, autenticação, permissão e produção continuam esperando o humano (inegociável 5), mesmo com as tarefas aprovadas. O modelo de dados do `design.md` é proposta: a pessoa técnica aprova antes da tarefa que o cria. A própria skill já exige ordem explícita para push, deploy e banco de produção.
2. **Premissa só vale confirmada.** A skill aceita registrar uma dúvida como premissa, com valor padrão e motivo. Aqui ela só sai da spec com "y" em "Confirmed?", marcado por quem pode decidir (inegociável 2), e a spec aprovada leva o `**Approved by**` (seção 2). Premissa sem "y" é pergunta aberta e segura a aprovação.
3. **Decisão crítica tem ADR.** A skill só registra no `.specs/STATE.md` a decisão de projeto que sai do Design, e com parcimônia. A casa pede mais. Toda mudança crítica (`playbooks/manutencao.md`) tem ADR em `docs/adr/`. Aprovado o ADR, quem conduz a feature registra no `STATE.md` um `AD-NNN` que cita o arquivo, mesmo que a skill pule o Design. A implementação espera a aprovação.
4. **Sessão com dado de cliente não usa web.** A cadeia de pesquisa da skill passa por Context7 (passo 3) e por busca na web (passo 4). Com dado de cliente na sessão, os dois estão fora (inegociável 12): vá ao passo 5 e marque como incerto, ou pergunte.
5. **Antes de apresentar spec ou tarefas, rode também o conferidor.** A skill roda os validadores dela antes de pedir a aprovação. Rode junto o `node .ai/skills/conferir-specs.mjs`, que pega o formato que o validador deixa passar (seção 2). Erro achado só no CI já passou pela aprovação.
6. **O Verifier é sempre independente.** Na ferramenta sem subagente, ele roda numa sessão nova, que recebe só a spec, o diff e os testes. A skill aceita, sem subagente, que o próprio autor faça a verificação "com olhos frescos"; aqui isso não vale (`testes.md`, seção 10). Vale para mudança de qualquer tamanho que passou pela skill: a pequena também tem `spec.md`, curta, e termina com `validation.md`.
7. **Mutação.** O Verifier injeta de 1 a 3 falhas no código novo de toda feature. Nas regras de dinheiro, permissão e escopo por empresa, que a skill chama de caminho crítico (P0), vale a mutação completa. No PHP, é o Infection (`testes.md`, seção 2); em stack sem ferramenta adotada, cinco ou mais mutações à mão, cobrindo todos os ramos, como a skill manda.
8. **O rascunho do sensor fica em `/tmp`, e começa verde.** O Verifier muta o código numa worktree ou numa cópia descartável, nunca na árvore real e nunca com `git stash`. Use `/tmp/tlc-<feature>-<n>`: a trava nega apagar pasta fora do temporário (`rm -rf ../pasta`), e o sensor ficaria sem limpeza. A worktree nasce sem o que o git ignora (`vendor/`, `node_modules/`, client gerado). Ligue esses itens da árvore real, com `ln -s`, ou instale. Rode a suíte uma vez sem mutação: ela tem de passar ali antes de qualquer mutante. Suíte que quebra por ambiente mata todo mutante e não prova nada.
9. **O PASS do Verifier não encerra a feature.** Depois dele ainda vêm a revisão em camadas, o print ou vídeo do Playwright no PR, as quatro perguntas por entidade e a revisão de segurança dirigida (`testes.md`, seções 9 e 10).
10. **Commit.** Um por tarefa, no formato da skill (`tipo(escopo): descrição`, conferido pelo `check_commit.py`), que é o de `git.md`. O hook de `commit-msg` que a skill sugere não vai para `.git/hooks`, porque o `core.hooksPath` global o ignora (`git.md`, seção 7).
11. **Teste que já existia não sai pela revisão da skill.** A regra "todo teste aponta para um requisito" vale para o teste escrito na tarefa. Teste de caracterização, de arquitetura e de regressão de incidente fica.
12. **Push, PR, merge e deploy** acontecem quando o humano pede, como sempre (`git.md`).

## 6. Critérios de piso

Toda feature que grava dado tem, além do que a varredura da skill levantar:

```
1. IF um usuário sem a ability <manage_x> envia POST /admin/<recurso> THEN o sistema SHALL responder 403
2. IF um usuário sem a ability <manage_x> envia POST /admin/<recurso> THEN o sistema SHALL manter a mesma quantidade de <x>
3. IF um usuário só da empresa B abre, edita ou desativa pelo ID um <x> da empresa A THEN o sistema SHALL responder <o status do projeto>
4. IF um usuário só da empresa B edita ou desativa pelo ID um <x> da empresa A THEN o sistema SHALL manter o <x> sem mudança
5. IF <a situação que a RN-n proíbe> THEN o sistema SHALL recusar com "<mensagem na linguagem do usuário>"
6. IF <a situação que a RN-n proíbe> THEN o sistema SHALL manter <o que não pode mudar> sem mudança
7. WHEN um usuário com a ability <manage_x> cria um <x> THEN o sistema SHALL mostrar o <x> na listagem
8. WHEN um usuário com a ability <manage_x> cria um <x> THEN o sistema SHALL registrar a criação na auditoria
```

O `<o status do projeto>` é um só, 403 ou 404, o que o projeto já usa para registro de outra empresa. Critério que aceita os dois é impreciso.

Tela nova tem ainda os estados vazio, de erro e de carregando, e o print no PR.

Na varredura de dimensões implícitas da skill (validação, falha parcial, repetição, permissão, concorrência, ciclo de vida, observabilidade, dependência externa, transição de estado), dois pontos do padrão costumam faltar. Ciclo de vida inclui desativar e reativar, porque quase nada se exclui (`banco.md`, seção 4). E concorrência inclui a regra que lê e grava sem travar a linha (`banco.md`, seção 8).

## 7. Pré-requisito e CI

- **Python 3 no PATH como `python3`.** Os validadores da skill são scripts Python que só usam a biblioteca padrão, e a skill os chama por `python3`. O Mac e o CI têm; no Windows, confira `python3 --version`. Lá, o `python3` pode ser só o atalho da Microsoft Store, que não roda nada.
- **No CI:**
  - `node .ai/skills/conferir-specs.mjs` roda os validadores em cada feature do `.specs/`. A spec é conferida sempre, com as regras de forma da seção 2; as tarefas, quando existem; e o PASS do `validation.md`, quando a feature parece concluída pela regra da própria skill. Ele acha o Python como `python3`, `python` ou `py -3`, ou pelo que estiver em `PYTHON`, e só aceita a versão 3. Sem ela, sai com 2.
  - `node .ai/skills/instalar.mjs --check` confere a cópia de cada ferramenta; o `sync-standards.mjs --check` também confere.
  - No template, os dois rodam no job `js`: o `npm run test:skills` confere as cópias, que são um dos testes, e o `npm run specs:check` roda o portão. Projeto derivado acrescenta ao próprio CI o `conferir-specs.mjs` e o `instalar.mjs --check` no PR do sync.
  - O portão só sabe que a feature acabou quando o `tasks.md` está todo marcado ou quando o `validation.md` existe. Feature sem `tasks.md` depende da revisão do PR, que cobra o resumo do `validation.md` (`git.md`, seção 3).

## 8. Atualizar a skill

Só no ai-systems, por PR, conferindo o hash de cada arquivo e lendo os scripts (`.ai/skills/README.md`). O projeto derivado recebe a versão nova pelo sync.
