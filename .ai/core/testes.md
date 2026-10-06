# Testes e verificação

Leia antes de dizer que terminou.

## 1. A escada de verificação

Nesta ordem, porque cada degrau é mais caro que o anterior. Leia a saída de cada um.

```
1. npx tsc --noEmit        tipos do front: segundos, pega a maior parte das besteiras
2. ./vendor/bin/pint --test  e  npx eslint .     estilo e lint
3. php artisan test        unitários, de feature e de arquitetura (suíte Arquitetura, seção 11)
4. composer test:banco     a mesma suíte no banco da produção, num container (seção 11)
5. npm run build           o build pega o que o dev server não pega
6. e2e dos fluxos críticos minutos, mas é o que prova que o usuário consegue usar
```

Sem Docker na máquina, o degrau 4 fica para o CI, que roda em todo PR.

- Todo projeto tem esses comandos como script (`composer` ou `npm`), para ninguém precisar lembrar. É deles que a skill `tlc-spec-driven` tira os comandos de verificação do `tasks.md` (Quick, Full, Build): o Build é a escada inteira, sem o degrau 4 quando a máquina não tem Docker (ele fica para o CI).
- O log de uma suíte verbosa não deve ocupar o contexto da sessão principal. Delegue a execução e receba veredito mais falhas.
- Nunca encadeie a saída da suíte em pipe (`| head`, `| tail`). O processo morre, a limpeza não roda, o dado de teste acumula e a próxima rodada falha em cascata.

> **A validar com Lucas Alencar:** ferramenta de e2e do padrão (proposta: Playwright), e se análise estática de PHP (Larastan) entra na escada.

## 2. O que precisa de teste

Teste unitário prova que a função está certa. Teste de feature e e2e provam que o usuário consegue usar. Com IA escrevendo rápido, o que quebra é a costura: a rota que mudou de nome, o campo que virou obrigatório, a permissão que passou a barrar quem não devia.

**Todo fluxo que, se quebrar, gera ligação de cliente tem teste:** login, criar, editar, excluir, permissão, geração de relatório.

Obrigatório em toda aplicação:

- **Teste de autorização por rota:** usuário sem permissão recebe 403, e usuário de outra empresa não lê nem grava o registro (escopo por ID).
- **Teste de regra de negócio no endpoint**, não só na função. Regra testada e não ligada ao fluxo é regra inexistente.
- **Todo caminho de escrita da mesma regra tem teste**, inclusive operação em massa e importação. É onde a guarda costuma faltar.
- **Limite de tentativas de login:** sequência de tentativas que termina em 429.
- **Teste de mutação nas regras de dinheiro, permissão e escopo por empresa** (abaixo).

Cobertura com critério: teste unitário em toda regra de negócio; controller e consulta se provam por teste de feature e e2e. Porcentagem de cobertura só como piso, nunca como meta.

### Teste de mutação

Teste que passa não prova que segura o comportamento. A IA escreve teste que não afirma nada, ou ajusta o teste até ficar verde. O teste de mutação altera o código de propósito (troca `>` por `>=`, apaga uma linha, inverte um `if`) e confere se algum teste falha. Se nenhum falha, aquele comportamento não está protegido.

- **Obrigatório** nas classes que decidem dinheiro (preço, desconto, saldo, comissão), permissão (Policy, Gate, ability) e escopo por empresa. Não no projeto inteiro: é lento, e o resto a escada cobre. É a mutação completa que a skill pede no caminho crítico; a leve, de 1 a 3 falhas, o Verifier faz em toda feature (seção 10).
- Ferramenta: **Infection** (`composer require --dev infection/infection`; precisa de PCOV ou Xdebug). Rode só nos arquivos da regra:

```bash
vendor/bin/infection --filter=app/Policies/EnterprisePolicy.php --show-mutations
```

- Todo mutante sobrevivente vira teste novo ou justificativa escrita no PR (mutante equivalente, que não muda comportamento observável). Nunca "ignorado".
- Roda ao fechar a feature que tocou essas classes, e o resultado vai na descrição do PR.

## 3. Quando rodar

```
durante o desenvolvimento   só o teste da parte que você está mexendo
terminou a feature          suíte completa, antes do PR
antes do deploy             suíte completa de novo (a branch mudou)
depois do deploy            verificação no ambiente real
```

Verde é o estado normal. Vermelho é parada de linha. Suíte que roda de vez em quando apodrece, e suíte que não roda no CI é passivo.

## 4. Ciclo de correção de bug

```
1. Reproduzir    escreva o teste que FALHA por causa do bug
2. Confirmar     rode e VEJA falhar (se passou, você não reproduziu)
3. Corrigir
4. Verificar     o teste passa E a suíte inteira continua verde
5. Commitar      fix: <o bug>, com o teste no mesmo commit
```

O passo 2 é o que ninguém faz e é o mais importante. O passo 4 completo pega a correção que quebra outra coisa, o padrão mais comum de regressão em código gerado rápido.

## 5. Armadilhas que já deram falso resultado

- **Teste de autorização com cookie herdado.** O cliente HTTP do teste guarda o cookie da resposta anterior, a chamada "anônima" sai autenticada e o teste passa por engano. Use contexto descartável, sem sessão herdada.
- **Espera por URL que também casa a tela de origem.** O teste segue antes de a ação terminar.
- **`sleep` ou espera fixa.** Espere por condição ou conteúdo visível. Nunca por rede ociosa.
- **Teste que falha uma vez a cada dez** é pior que teste nenhum. Conserte ou apague.
- **Limite de requisições do ambiente.** O ambiente de teste sobe com o limite afrouxado de propósito, e a automação respeita o limite do ambiente real.
- **Saída que depende de modelo de linguagem** fica fora da suíte bloqueante.

## 6. Como montar a suíte e2e

- Login uma vez por papel, sessão salva e reaproveitada. Teste que loga pela tela toda vez é lento e frágil.
- Guarda automática de erro de console: todo teste falha se sobrou erro de JavaScript, com uma lista curta de ruído conhecido filtrada.
- Banco compartilhado, execução em série. Paralelo contra o mesmo banco cria falha intermitente que consome dias.
- Um objeto de página por tela. O seletor mora num lugar só.
- Acessibilidade na suíte (axe, WCAG A e AA), sem desligar a regra de contraste.
- Limpeza ao final: o teste apaga o que criou.
- Teste nunca roda contra banco de produção.
- Comparação visual é opcional e em projeto separado. Imagem de referência gerada no Mac não bate no Linux do CI.

## 7. Refatoração e motores de cálculo

- Código que já está em produção só se refatora atrás de teste de caracterização. O aceite é reproduzir um número real da produção.
- Refatoração estrutural (mover, renomear, extrair) se prova com build e suíte verdes. Mudança de comportamento pede flag ou troca gradual. Os dois nunca no mesmo commit (`playbooks/manutencao.md`).
- Motor de cálculo passa por revisão adversarial do desenho antes do código: ordem de lista tratada como calendário, virada de meia-noite, dia sem dado.
- Motor recusa com motivo legível em vez de inventar número. A tela mostra o motivo, não um zero.

## 8. QA de sistema que não é nosso

- A suíte mora em repositório próprio e monta o próprio ambiente.
- Nela, teste vermelho é achado catalogado, não build quebrado. Isso fica declarado no README.
- Ambiente local sem backend serve para conferir layout, ícone, tamanho e cor. Não valida conteúdo, preço nem regra. O limite fica escrito.
- Ao assumir repositório alheio, a primeira tarefa é rodar a suíte e catalogar o que apodreceu.

## 9. Pronto de verdade

Antes de dizer "terminei":

- [ ] a escada rodou e eu li a saída;
- [ ] a suíte Arquitetura passou sem violação nova, e a dívida não cresceu (seção 11);
- [ ] a suíte roda verde no banco da produção (`composer test:banco` ou o job do CI);
- [ ] o fluxo novo tem teste, se é crítico;
- [ ] regra de dinheiro, permissão ou escopo que mudou passou pelo teste de mutação;
- [ ] o Verifier da skill deu PASS no `validation.md`, com evidência por critério e o sensor de mutação (seção 10);
- [ ] tela que mudou foi usada no navegador e o print ou vídeo vai no PR;
- [ ] as quatro perguntas por entidade têm resposta: criar a primeira, listar, editar, desfazer;
- [ ] a revisão de segurança dirigida foi feita (`seguranca.md`, seção 11);
- [ ] o diff foi lido linha a linha;
- [ ] o que não verifiquei está dito com todas as letras.

## 10. Quem fez não confere

O agente que implementou não dá nota para si mesmo: ele leu o próprio raciocínio e vai achar que está certo.

### O Verifier da skill

Depois da última tarefa, a skill `tlc-spec-driven` chama sozinha o Verifier, sem que ninguém peça: um subagente novo, **que não viu a implementação**. Na ferramenta sem subagente, é uma sessão nova. A skill aceita, nesse caso, que o próprio autor verifique; aqui isso não vale (`especificacao.md`, seção 5). O Verifier recebe só a spec, o diff da feature e os testes, e:

- prova cada critério com evidência: `arquivo:linha` mais a asserção. Critério sem evidência conta como não coberto;
- confere que o valor afirmado é o que a spec define (409, e não "algum erro"). Onde a spec não define, aponta a lacuna em vez de passar;
- injeta falhas de comportamento no código novo, numa cópia descartável, e confirma que algum teste quebra. Mutante que sobrevive vira tarefa de correção;
- escreve `.specs/features/<feature>/validation.md` com o veredito, e o `validate_state.py` recusa PASS sem evidência.

```
| IF o e-mail já existe THEN o sistema SHALL responder 409       | 409             | tests/Feature/UsuarioTest.php:42 - assertStatus(409)               | PASS |
| IF o e-mail já existe THEN o sistema SHALL manter os usuários | 1 (nenhum novo) | tests/Feature/UsuarioTest.php:43 - assertDatabaseCount('users', 1) | PASS |
```

- Não passe ao Verifier o resumo nem o raciocínio de quem implementou. É exatamente o viés que se quer evitar.
- Ele não conserta. As lacunas voltam como tarefas, com no máximo três voltas antes de chamar o humano.

### Revisão em camadas antes do humano

Antes de pedir revisão humana do PR, um subagente por preocupação, cada um com contexto zerado:

| Camada | O que pergunta |
|---|---|
| Segurança | As seis perguntas de `seguranca.md`, seção 11, arquivo por arquivo |
| Requisitos | A entrega cobre cada requisito da spec e cada regra do escopo? Cada um tem teste? |
| Regressão | Mexeu em algo fora da tarefa? Entrou padrão, dependência ou componente novo não combinado? |

Cada achado sai classificado como **bloqueante** (não abre PR), **deveria corrigir** (corrige ou justifica no PR) ou **detalhe** (a critério de quem fez). O resultado vai na descrição do PR. A camada automática não substitui a revisão humana: ela a deixa mais curta e focada em julgamento.

### Tela se prova usando

Toda feature que muda tela termina com o agente abrindo o navegador pelo Playwright e **usando** o fluxo que construiu, nos dois temas e em 375 px. O print (`page.screenshot`) ou o vídeo (`video: 'on'`) **vai no PR**. O modelo lê imagem melhor do que interpreta HTML, e quem revisa vê a tela sem subir o projeto.

## 11. Os quatro testes técnicos

Regra que só mora em texto não é cumprida, nem pelo gabarito: na primeira rodada, o próprio template tinha 8 violações das regras de forma que o padrão publica. Os quatro testes abaixo tiram a regra do texto e põem no CI. A rotina que instala, roda e relata é a skill `wcj-testes` (mesmo molde da `wcj-pentest`).

| Teste | O que prova | Quando é obrigatório |
|---|---|---|
| Arquitetura | o código segue as regras de forma (`stacks/laravel-inertia.md` §4 e §5): Service sem HTTP, toda rota autorizada ou pública declarada, controller fino, domínio sem mexer na tabela do outro | todo projeto |
| Banco de verdade | a suíte passa no mesmo motor e versão da produção (Testcontainers), não só no SQLite | todo projeto com banco próprio |
| Benchmark | a listagem ou o motor não piorou: consultas constantes (N+1), índice (EXPLAIN), tamanho da resposta, consultas contra a referência; o tempo é tendência | perfil de uso: tabela acima de ~100 mil registros em 12 meses, motor de cálculo, importação ou relatório pesado |
| Carga | o sistema aguenta a média e o pico medidos | perfil de uso: usuário externo ou mais de ~50 simultâneos, pico concentrado, SLA, mudança de infraestrutura (`playbooks/teste-carga-e-pentest.md`) |

O perfil de uso é declarado no escopo (`templates/escopo.md`, seção 10) e o `AGENTS.md` do projeto diz quais valem.

**O que o SQLite esconde.** Trava de linha (`lockForUpdate` é ignorado: corrida passa no teste e quebra na produção), migração que o MySQL recusa, collation, JSON, modo estrito. Teste de trava e de corrida só vale no banco de verdade: pula no SQLite com o motivo escrito (`Tests\Support\UsaBancoDeVerdade`) e roda no CI. Com `DB_TESTE=container` e sem o container, ele falha em vez de pular, e o job do CI confere a evidência (imagem e versão lidas do banco): verde sem prova não passa.

**Já aconteceu.** A rotina provou, com um teste determinístico, que dois administradores que se rebaixam ao mesmo tempo deixam o sistema sem administrador ativo: a checagem do "último admin" lê sem travar e grava depois.

### A régua não se afrouxa para ficar verde

- Violação que já existia quando a regra entrou vai para a dívida (`tests/Arch/divida.php`; no TypeScript, a baseline do dependency-cruiser e o `eslint.divida.mjs`), com o motivo. A dívida **só diminui**: o que foi corrigido sai da lista, item na dívida não pode crescer além do teto (arquivo ou método maior, mais usos do mesmo nome), item novo só com aprovação do dev sênior.
- Referência do benchmark, limites, listas de exceção, imagem do banco de teste e cenários de carga têm dono no `CODEOWNERS` (`templates/CODEOWNERS`).
- A IA não conserta teste vermelho mexendo na régua. Régua alterada vai justificada no PR.

### Quem faz

| Teste | Quem escreve | Quem roda | Quem aprova mudança na régua |
|---|---|---|---|
| Arquitetura | regras: dev sênior na abertura do projeto | CI, em todo PR | dev sênior |
| Banco de verdade | o dev da feature, junto com o código | CI; máquina do dev, se tiver Docker | — |
| Benchmark | o dev que cria a listagem ou o motor | CI | dev sênior |
| Carga | dev sênior ou tech lead | ele, na janela combinada, com a infra | ele assina; publicar ao cliente é decisão humana |

Dev júnior não muda regra, não põe item na dívida, não atualiza referência e não roda carga em homologação sozinho.

