# Playbook: aplicação nova

## Quando usar

Sistema que começa do zero. Se o sistema já existe e vai ganhar módulo ou mudança, use `manutencao.md`.

## O que precisa existir antes

- Alguém que ouviu o cliente e sabe o que o sistema precisa fazer.
- Uma pessoa técnica disponível para modelar o banco (etapa 2).
- Acesso ao template da casa (`stacks/laravel-inertia.md`).

## O princípio

Banco de dados e camada visual são o que mais consome tempo. O banco é desenhado por pessoa antes de qualquer prompt; o visual vem pronto do template. O esforço da IA fica na estrutura de dados aprovada e no diferencial do negócio.

Duas portas de entrada:

- **Cliente com referência clara** (sistema antigo, processo definido): parte do dado. Modele o banco primeiro, depois escreva o escopo.
- **Cliente só com planilha ou conversa:** parte da regra de negócio. Escreva regras e fluxo primeiro, deixe a IA **propor** o modelo de dados, valide e ajuste com a pessoa técnica, e só então siga para o escopo.

Nos dois casos, a etapa 2 termina com uma pessoa técnica aprovando o banco.

## Etapas

| # | Etapa | Artefato | Quem | Parada |
|---|---|---|---|---|
| 1 | Entender a necessidade | `docs/necessidade.md`: para que serve, quem usa, o que fica de fora | Humano, IA ajuda a perguntar | |
| 2 | Modelar o banco | `docs/banco.txt`: todas as tabelas, colunas e valores de domínio | Pessoa técnica; IA só apoia em módulo pontual | **Sim** |
| 3 | Criar o repositório | Clone do template, origin trocado, `AGENTS.md` do projeto preenchido, `.env.example`, primeiro commit | Dev | |
| 4 | Gerar o prompt mestre | `docs/escopo.md`, gerado por IA de raciocínio a partir de banco, regras, níveis de acesso e autenticação. Descreve o sistema em blocos e inclui a lista do que **não** pode | IA | |
| 5 | Validar o escopo | Escopo lido inteiro, corrigido e aprovado | Humano | **Sim** |
| 6 | Desenvolver bloco a bloco | Cada bloco é uma feature na skill `tlc-spec-driven` (`core/especificacao.md`), com a spec aprovada antes do código. **Segurança é bloco obrigatório** (ver abaixo) | IA e dev | **Spec de cada bloco**, e design e tarefas quando existem |
| 7 | Verificar | O Verifier da skill com PASS em todo bloco, escada de `testes.md`, as 4 perguntas de porta de entrada, revisão de segurança dirigida | IA e dev | |
| 8 | Publicar | Checklist de `deploy.md` e `docs/DEPLOY.md` do projeto | Dev e infra | **Sim** |

### Etapa 1: entender

- Para que serve, quem usa (papéis), o que cada papel vê e faz, o que fica fora da primeira versão.
- Registre o vocabulário do produto: como o cliente chama as coisas.
- A ata com o cliente é fonte de requisito. Guarde em `docs/atas/`.

### Etapa 2: modelar o banco

- Regras em `core/banco.md`, seção 1 a 3.
- Todas as tabelas, todas as colunas, todos os valores de domínio (`user_types`: 1 admin, 2 supervisor, 3 operador).
- A IA pode ser consultada para módulo pontual: "preciso de log de auditoria, quais tabelas?". A pessoa técnica decide.
- Quem ainda não modela sozinho para aqui e chama a equipe técnica. Não é atalho pular.

### Etapa 3: repositório

- Siga `core/git.md`, seção 5. O template não recebe push de projeto.
- `AGENTS.md` do projeto a partir de `templates/AGENTS.projeto.md`. Preencha o que já sabe; o resto entra conforme aparece.
- Rode o sync do padrão. Confirme a trava de segredo antes do primeiro commit.
- Sem `.env` no git. `.env.example` com todas as variáveis e valores falsos, e integrações externas desligadas.
- O projeto sobe do zero em até 3 minutos, com dado de exemplo (`stacks/laravel-inertia.md`, seção 3). Confira cronometrando.

### Etapa 4: prompt mestre

Entrada para a IA:

- `docs/banco.txt`;
- regras de negócio de `docs/necessidade.md`;
- papéis, o que cada um acessa e como se autentica;
- a stack (do template) e o layout que já existe, para a IA reutilizar e não inventar componente;
- **o que não pode:** campo, tela, integração ou comportamento que a IA tende a inventar e não deve.

Saída: `docs/escopo.md` no formato de `templates/escopo.md`: contexto, fronteira do que já existe, regras que valem para tudo, blocos de trabalho com dados, telas, regras e permissão, travas, ordem de execução.

O critério de aceite não mora no escopo. Ele nasce na spec de cada bloco, em EARS, quando o bloco começa (etapa 6), e é a lista que o Verifier confere no fim.

### Etapa 5: validar

- Alguém lê o escopo inteiro. É longo e não tem atalho.
- Confira tabela a tabela contra o `banco.txt`. Campo que a IA acrescentou sem pedido sai.
- Todo bloco tem objetivo, dados, telas, regras e permissão. Bloco vago ("tela de relatórios", "o financeiro") volta para reescrita.
- Escopo aprovado é a referência. A IA não desenvolve nada que não esteja nele.

### Etapa 6: desenvolver

- Um bloco por vez, cada um como feature na skill `tlc-spec-driven`, em `.specs/features/<bloco>/` (`core/especificacao.md`). A spec, com requisitos, critérios em EARS, fora do escopo e premissas, é aprovada por humano antes do código. Os critérios de piso de `core/especificacao.md`, seção 6, entram em toda spec.
- Ordem sugerida: autenticação e papéis → cadastros base → o fluxo principal do negócio → relatórios.
- **Bloco de segurança**, obrigatório antes da primeira publicação: `auth` em todo grupo, Policy em toda rota com ID, `FormRequest` em toda entrada, limite de tentativas no login, headers, `.env` e `APP_DEBUG` de produção. Checklist em `core/seguranca.md`, seção 11.
- CRUD segue o gabarito de `stacks/ui.md`. Cinco cadastros, um jeito só.
- Necessidade nova no meio do caminho: feature nova em `.specs/features/`, com a spec aprovada. Nunca prompt solto.
- Cada bloco termina com o Verifier da skill dando PASS no `validation.md`, um commit por tarefa e árvore limpa.

### Etapa 7: verificar

- Escada completa (`core/testes.md`).
- O Verifier da skill com PASS em cada bloco: cada critério com evidência `arquivo:linha` e o sensor de mutação (`core/testes.md`, seção 10).
- Mutação completa nas regras de dinheiro, permissão e escopo por empresa (`core/especificacao.md`, seção 5).
- Revisão em camadas (segurança, requisitos, regressão) antes do humano. Print ou vídeo das telas no PR.
- Por entidade: dá para criar a primeira, listar, editar e desfazer?
- Revisão de segurança dirigida, pergunta por pergunta.
- Estado vazio, de erro e de carregando existem em toda tela.
- Nada de placeholder ou número chumbado.

### Etapa 8: publicar

- **Bugbash** antes da primeira entrega ao cliente: o time usa o sistema como o cliente, com os papéis reais (`core/principios.md`, seção 10).
- `docs/DEPLOY.md` escrito antes do primeiro deploy (modelo em `templates/DEPLOY.md`).
- HTTPS, backup e variáveis no servidor antes do primeiro usuário.
- Ambiente de demonstração é semeado por script, marcado como exemplo, e tem comando de limpeza.
- Credencial provisória de instalação tem prazo. Quem administra usuários é o cliente, com as travas de `core/seguranca.md`, seção 3.

## Pontos de parada

Etapas 2, 5 e 8; em cada bloco, a spec aprovada, e o design e as tarefas quando a skill os produz (etapa 6); mais tudo que está em `core/principios.md`, seção 5.

## Definição de pronto

- [ ] `docs/necessidade.md`, `docs/banco.txt`, `docs/escopo.md`, `docs/DEPLOY.md` e `.specs/` versionados.
- [ ] `AGENTS.md` do projeto preenchido, inclusive vocabulário e armadilhas.
- [ ] Escada de verificação verde, com a saída lida.
- [ ] Todo bloco com `validation.md` em PASS, e o `node .ai/skills/conferir-specs.mjs` verde.
- [ ] Setup do zero em até 3 minutos.
- [ ] Bloco de segurança feito e revisão dirigida respondida.
- [ ] Todo fluxo crítico com teste.
- [ ] Bugbash feito antes da primeira entrega.
- [ ] Publicado, usado no ambiente real, backup confirmado.

> **A validar com Lucas Alencar:** os modelos de `necessidade.md` e `escopo.md` foram escritos a partir do que ele mostrou na reunião; ele tem os prompts originais.
