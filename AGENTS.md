<!-- wcj-ai:inicio — bloco gerado pelo padrão WCJ. Não edite aqui: mude no repositório do padrão. -->
# Padrão WCJ de desenvolvimento com IA

Você está trabalhando num projeto da WCJ Tecnologia. Estas regras valem para qualquer stack, qualquer ferramenta de IA e qualquer pessoa. A stack do projeto e o que é específico dele estão depois deste bloco, na seção "Stack e onde as coisas moram".

## Os 12 inegociáveis

1. **Evidência antes de afirmação.** Não diga "funciona", "corrigi" ou "está no ar" sem ter rodado o comando e visto a saída. Se não rodou, diga "implementei, mas não verifiquei".
2. **Não invente.** Regra de negócio, campo, tela, rota ou permissão que não está no escopo não existe. Faltou informação, pare e pergunte.
3. **Leia antes de escrever.** Reutilize o componente, o layout e o padrão que o projeto já tem. Não crie um segundo jeito de fazer a mesma coisa.
4. **Fatie.** Uma tarefa por vez, com entrega que uma pessoa consegue revisar. Uma feature, um PR, um assunto.
5. **Pare e chame um humano** antes de: modelar ou remodelar banco; migração destrutiva; mexer em autenticação ou permissão; tocar em produção (banco, servidor, DNS, firewall); desligar trava de escrita; rodar teste de carga ou varredura contra ambiente que não é o seu; enviar código de cliente a serviço externo; marcar achado de segurança como corrigido; decidir de que lado vale uma regra quando tela e servidor divergem.
6. **Segredo nunca vai para o código, para o git nem para documento.** Runbook cita o nome da variável e onde buscar o valor, nunca o valor.
7. **Bom senso de custo.** Ajuste trivial (texto, cor, espaçamento) não passa pelo fluxo completo. Trivial é o que não toca banco, rota, permissão nem regra de negócio.
8. **Autorização em duas camadas e escopo por ID.** Grupo de rotas com `auth` por padrão, rota pública é exceção declarada. Toda rota que recebe ID de empresa, loja, cliente ou pedido valida o vínculo com a sessão antes de ler ou gravar.
9. **Regra de negócio vale no servidor**, no mesmo ponto que efetiva a ação. Validação na tela é conveniência.
10. **`git fetch` e confira a branch que é o ambiente antes de editar.** Se o servidor ou o banco tem algo que o seu clone não conhece, o clone está desatualizado. Não apague.
11. **Funcionalidade sem porta de entrada não existe.** Ao fechar uma feature, confira por entidade: dá para criar a primeira, listar, editar e desfazer?
12. **Você nunca lê, digita nem pede segredo, nem monta chamada com credencial.** Monte o caminho e deixe o humano colar o valor; sistema externo se acessa por ferramenta que guarda a credencial. Segredo que passou por chat, log ou ticket está vazado e precisa ser trocado. Sessão com dado de cliente não navega na web.

## O que ler conforme a tarefa

| Vai fazer o quê | Leia antes |
|---|---|
| Criar aplicação do zero | `.ai/playbooks/aplicacao-nova.md` |
| Mudar ou evoluir sistema existente | `.ai/playbooks/manutencao.md` |
| Feature nova ou mudança que não é trivial | A skill `tlc-spec-driven`, com as regras da casa em `.ai/core/especificacao.md` |
| Corrigir bug | `.ai/playbooks/bug.md` |
| Produção fora do ar ou dado perdido | `.ai/playbooks/incidente.md` |
| Teste de carga, stress ou pentest | `.ai/playbooks/teste-carga-e-pentest.md` |
| Teste de arquitetura, banco de verdade ou benchmark | `.ai/core/testes.md`, seção 11 |
| Mexer em tabela, migração ou consulta | `.ai/core/banco.md` |
| Mexer em rota, login, permissão, upload, header | `.ai/core/seguranca.md` |
| Listagem, cache, fila, consulta pesada | `.ai/core/performance.md` |
| Criar ou mudar tela | `.ai/stacks/ui.md` (o método vale para qualquer stack; caminhos e componentes são os do template Laravel + Inertia) |
| Escrever código | A seção "Stack e onde as coisas moram", depois deste bloco. Projeto Laravel + Inertia + React: `.ai/stacks/laravel-inertia.md` |
| Commit, branch, PR | `.ai/core/git.md` |
| Dizer que terminou | `.ai/core/testes.md` (seção 10: o Verifier da skill e a revisão em camadas) |
| Sessão com dado de cliente, credencial ou serviço externo | `.ai/core/seguranca.md`, seção 12 |
| Publicar servidor MCP ou API para a IA do usuário | `.ai/core/mcp.md` |
| Publicar | `.ai/core/deploy.md` |
| Uma trava de agente bloqueou ou pediu confirmação | `.ai/adapters/README.md`. Não contorne: explique ao humano o que queria fazer |

Feature e mudança que não é trivial seguem a skill `tlc-spec-driven`. Valem mais que ela os 12 inegociáveis, as paradas e as regras da casa de `.ai/core/especificacao.md`, seção 5. Na prática:

- aprovar a spec ou as tarefas não autoriza o que o inegociável 5 manda parar;
- decisão crítica tem ADR em `docs/adr/`;
- sessão com dado de cliente não usa a busca na web nem o Context7 da skill;
- o Verifier nunca é quem implementou;
- o rascunho do sensor de mutação fica em `/tmp`;
- `.specs/` vai inteiro para o git.

Os princípios com o porquê de cada um estão em `.ai/core/principios.md`.
<!-- wcj-ai:fim -->

# WCJ Hub

<!-- Parte do projeto. O bloco padrão WCJ fica acima, gerado pelo sync. Aqui vai só o que é específico deste sistema. Apague as instruções entre <> ao preencher. -->

## O que é

<Uma ou duas frases: para quem, para quê, o que substitui.>

**Não assuma:** <o que a IA tende a supor e está errado aqui. Ex.: "não há multiempresa", "não emite nota fiscal, só anexa", "o admin é do franqueado, não da franqueadora".>

## Estado atual

<Fase, o que está no ar, o que está em construção. Uma tabela curta. Data da última atualização.>

## Vocabulário do produto

| Termo na tela | Significa | Nunca chame de |
|---|---|---|
| <fatura> | <documento anexado pelo usuário> | <NF> |

## Stack e onde as coisas moram

<Primeira linha: a stack (ex.: "Laravel + Inertia + React", "Next + Prisma"). O bloco padrão acima é neutro de stack e manda o agente ler esta seção.
Laravel + Inertia derivado do template: só o que difere dele; se não difere, "Segue o template `.ai/stacks/laravel-inertia.md`."
Outra stack, sem guia em `.ai/stacks/`: onde ficam rotas, telas, acesso a banco e migrações, e o que vale no lugar de cada seção do guia Laravel.>

## Como rodar

```bash
<comandos exatos, do zero, em máquina limpa>
```

## Ambientes

| Ambiente | URL | Branch | Deploy |
|---|---|---|---|
| produção | | `main` | `docs/DEPLOY.md` |
| homologação | | | |

## Autorização

<Papéis, o que cada um vê e faz, e onde a regra mora (Policy X, ability Y).>

## Regras de domínio

<As regras de negócio que a IA precisa saber antes de tocar em qualquer fluxo. Numeradas. Ex.: "RN-01 pedido só fecha com fornecedor vinculado à janela (servidor, `CartService::close`)".>

## Módulos e flags

<O que é ligável por instalação ou cliente, e onde a flag é cumprida (na gravação, não no menu).>

## Testes técnicos

<Quais valem, pelo perfil de uso do escopo (seção 10): arquitetura sim; banco de verdade <sim/não>; benchmark <sim/não, e das telas X e Y>; carga <sim/não, e o cenário>. A régua (dívida, referência, limites) tem dono no `.github/CODEOWNERS`.>

## O que exige aprovação humana neste projeto

<Além do padrão: ex. "qualquer mudança em `PricingService`", "migração na tabela `orders`".>

## Um jeito só

<Onde o código tem mais de um jeito de fazer a mesma coisa, qual é o certo. Sai da caça a ambiguidades de `.ai/playbooks/manutencao.md`. Ex.: "erro de validação: sempre FormRequest; os `Validator::make` em `ReportController` são legado a remover".>

## Armadilhas que já custaram tempo

<Uma linha por armadilha, a mais recente no topo. Ex.: "a data do pedido é gravada em UTC; filtrar por dia sem fuso perde os pedidos depois das 21h".>

## Para ir mais fundo

| Assunto | Onde |
|---|---|
| Entrada e ordem de leitura | `docs/00-COMECE-AQUI.md` |
| Escopo | `docs/escopo.md` |
| Banco | `docs/banco.txt` |
| Deploy | `docs/DEPLOY.md` |
| Decisões | `docs/adr/` e `.specs/STATE.md` |
| Specs das features, retomada e lições | `.specs/` (`.ai/core/especificacao.md`) |
