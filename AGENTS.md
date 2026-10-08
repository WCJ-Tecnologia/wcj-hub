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

## O que é

Sistema interno da WCJ Tecnologia, para até 14 usuários, que substitui o Trello na gestão de projetos e dá a cada usuário uma conexão da própria IA (Claude, Codex, ChatGPT) com as mesmas permissões da tela. A **fatia 1** (D-GER-24) é Núcleo + Projetos + servidor MCP com 6 ferramentas. CRM é v2; Chamados vem depois. Entrada e ordem de leitura: `docs/00-COMECE-AQUI.md`.

**Não assuma:**

- **Não há multiempresa.** É single-tenant: sem `enterprise_id`, sem escopo por empresa. O escopo de acesso é papel + alocação no projeto (`project_members`). O template tinha `enterprises`, `scopedEnterpriseIds()` e `canAccessEnterprise()`: saíram no B0 (seção 9 do `banco.txt`).
- **O kanban não trava.** O sistema nunca recusa mover card, pôr na sprint, encerrar sprint ou montar pacote por falta de dev, horas, PR, parecer ou capacidade. Número de controle (capacidade, data prevista) é referência. Recusa só por permissão, dado inválido e integridade do banco (premissa da `necessidade.md`).
- **Não há CRM, contrato, chamado, tipo de serviço nem GitHub por webhook** na fatia 1. As tabelas do CRM (seção 3 do `banco.txt`) são proposta da v2: não crie.
- **A IA do sistema não existe.** O Hub não chama IA nem guarda chave de provedor (IA2): é a IA do usuário que chama o Hub, pelo MCP.
- **Dinheiro não existe** na fatia 1.
- O "Tech Lead" não é papel nem fica no sistema; o "Comercial" existe como papel, sem uso na fatia 1.

## Estado atual

Atualizado em 2026-10-06.

| Bloco | Estado |
|---|---|
| B0 repositório, 27 migrações, sementes, CI em MySQL 8, tema da marca | na `main` (PR #1) |
| B1 Núcleo: usuários, clientes, contatos, auditoria | branch `feat/b1-nucleo` (Cauã), Verifier PASS em 06/10; PR a abrir |
| B2 a B10 | não começados; ordem e divisão em `docs/plano-de-desenvolvimento.md` |

## Vocabulário do produto

Completo na `docs/necessidade.md`, seção "Vocabulário". O que mais aparece no código:

| Termo na tela | Significa | No código / banco | Nunca chame de |
|---|---|---|---|
| Cliente | Empresa atendida, inclusive a própria WCJ (N7) | `clients` | empresa, enterprise |
| Contato | Pessoa de um cliente (N8) | `contacts` | usuário |
| Papel | Administrador, Gestor de projetos, Desenvolvedor, Comercial (N2) | `roles`, `RoleEnum` | perfil, tipo de usuário (`user_types` saiu) |
| Carga diária | Horas por dia da pessoa (N3) | `users.daily_hours` | jornada |
| Projeto de entrega / produto contínuo | Com ou sem data de entrega (P2) | `projects.due_on` nulo = contínuo | — |
| Tarefa, card | Item do quadro | `tasks` | ticket, chamado |
| Chave | `SGM-17` = prefixo + número (P16) | `projects.key_prefix` + `tasks.number`, sem coluna gravada | id |
| Etapa | Coluna do quadro, com papel fixo (P11) | `board_stages` + `stage_roles` | status |
| Backlog, To do | Fila fora da sprint; sprint não começada | etapas de papel `queue` e `sprint` | Esteira (nome antigo do Trello) |
| Sprint | Período que agrupa tarefas; "Fase" no histórico (P51) | `sprints`, aberta = `closed_at` nulo | ciclo |
| Pacote, versão | Tarefas que vão juntas para homologação e produção (P41) | `packages`, estado pelas duas datas | release |
| Critério de aceite (`CA_n`) | Item do requisito e do roteiro de QA (P17, P36) | `task_acceptance_criteria` | checklist |
| Teste cruzado | Teste por outro dev antes do QA (P34) | `tasks.cross_tested_by/at` | validação, parecer |
| Retrabalho | Volta para desenvolvimento com origem e motivo (P39) | movimento com `rework_origin_id` | bug |
| Card de suporte | Pedido do cliente registrado pelo gestor (P52) | `task_types.slug = support` | chamado |
| Arquivar | Tirar do quadro um card com histórico, com motivo (P26) | `tasks.archived_at` + `archive_reason` | excluir |
| Desativar | Tirar de uso um cadastro com histórico (G2) | `deactivated_at/by` | excluir, inativar por booleano |
| Conector, conexão de IA | Autorização de um assistente em nome do usuário (IA4) | `ai_authorizations` | integração, API key |

## Stack e onde as coisas moram

Laravel 12 + Inertia 2 + React 19 + TypeScript, do template `ai-systems` 0.6.0 (`.ai/stacks/laravel-inertia.md`). O que difere do template:

- **Por domínio** (ADR-001): `app/Platform/` (o Núcleo: usuários, papéis, clientes, contatos, auditoria, conexões de IA) e `app/Domains/Projetos/`, cada um com `Controllers`, `Models`, `Policies`, `Requests`, `Services` e `Mcp`. Infraestrutura fica onde o template põe (`app/Http/Middleware`, `app/Console`, `app/Mail`, `app/Providers`, `app/Rules`, `app/Support`). O Platform não usa nada de `App\Domains\` (teste A9): dado de módulo que o Núcleo precisa entra por interface em `app/Platform/Contracts/`, com a implementação real no módulo e a ligação no `AppServiceProvider` (ex.: `ClientManagement`). Lista geral de um módulo (tipos de tarefa, motivos de retrabalho) é Model e Service do módulo.
- **MySQL 8 em tudo, inclusive nos testes** (ADR-002). Nunca SQLite.
- **Auditoria** em `audit_logs` com `ai_authorization_id` (o canal: nulo = tela), `outcome` (`success`/`denied`) e `changes` (`{"campo": [antes, depois]}`); gravada pelo `Platform\Services\AuditService`.
- **Morph map** (`AppServiceProvider::registerMorphMap`, `enforceMorphMap`): `audit_logs.auditable_type` guarda o alias (`user`, `task`…), não o nome da classe.
- **Passport 13** instalado (tabelas e chaves), com as rotas `/oauth/*` desligadas até o B8 (`Passport::ignoreRoutes`). `laravel/mcp` entra no B8.
- **Parâmetros** em `config/hub.php` + `.env` (T5); não há tabela de configurações.
- **Tema** da marca WCJ: `design/tokens.json` (a partir de `docs/brand/tokens.wcj.json`), fontes em `public/fonts` (`resources/css/fonts.css`). Cor, fonte e raio só por token.

## Como rodar

```bash
git clone git@github.com:WCJ-Tecnologia/wcj-hub.git && cd wcj-hub
composer setup                       # sobe o MySQL (docker compose), instala, migra, semeia, dado de exemplo, build
php artisan app:create-admin voce@wcjtecnologia.com.br   # imprime a senha uma vez; troca no 1º acesso
composer dev                         # servidor, fila, logs e Vite

composer lint && php artisan test    # MySQL 8 (wcj_hub_test); precisa do docker compose up -d mysql
npm run typecheck && npm run lint && npm run build
npm run e2e:prepare && npm run e2e:serve   # em outro terminal: npx playwright test
npm run test:guard
php artisan app:limpar-exemplo       # apaga o dado de exemplo ("[Exemplo] …", @exemplo.wcj.test)
```

Sem Docker: um MySQL 8 local com os bancos `wcj_hub`, `wcj_hub_test` e `wcj_hub_e2e` e o usuário do `.env` (ver o `mysql_init` do `docker-compose.yml`).

## Ambientes

| Ambiente | URL | Branch | Deploy |
|---|---|---|---|
| produção | `https://hub.wcjtecnologia.com.br` (a criar, B10) | `main` | `docs/DEPLOY.md` |
| homologação | a definir com Alaor Rodrigues (B10) | `main` | `docs/DEPLOY.md` |

## Autorização

Papéis em `roles` (`RoleEnum`, ids fixos 1 a 4); quadro completo em `docs/escopo.md` §4.

| Papel | Vê e faz |
|---|---|
| Administrador (`admin`) | Tudo: usuários, clientes, contatos, auditoria, listas gerais, qualquer projeto, conexões de IA de todos |
| Gestor de projetos (`project_manager`) | Tudo nos projetos que gere (`projects.manager_id` ou alocado com esse papel); contatos dos clientes desses projetos |
| Desenvolvedor (`developer`) | Projetos em que está alocado: cria card no Backlog, move a partir do To do, edita, comenta, anexa, lança as próprias horas, teste cruzado. Não exclui nem arquiva card; não mexe em sprint, pacote nem configuração |
| Comercial (`sales`) | Nada na fatia 1 (o papel existe para a v2) |

- Regra na Policy, `authorize()` em todo método; `abilities` no front só esconde botão. Ability nova: `User`, `HandleInertiaRequests`, `types/index.d.ts` e Policy no mesmo commit.
- Policy fora de `app/Policies`: registrar com `Gate::policy()` no `AppServiceProvider`.
- A IA conectada age como o usuário dono do token: mesmas Policies; escopo do token nunca passa do papel (IA5).
- Policies do Núcleo (B1): `UserPolicy`, `ClientPolicy`, `ContactPolicy`, `AuditLogPolicy`. As de Projetos nascem a partir do B2.
- Toda negação de `$this->authorize()` vira linha `outcome = denied` na auditoria, pelo `authorize()` do `App\Http\Controllers\Controller`. FormRequest que valida payload autoriza antes de validar, pelo trait `AuthorizesBeforeValidation` (mesmo caminho auditado), e a ação não chama `authorize()` de novo: sem isso, quem não pode recebe o erro de validação (que revela dado) e a tentativa não vira `denied`. Ability nova entra em `AuditActionEnum::forAbility()`; sem mapeamento, a negação grava o nome cru da ability e a tela mostra "Outra ação".
- "Gestor do cliente" (o gestor gere algum projeto do cliente) vem do contrato `App\Platform\Contracts\ClientManagement` (ADR-001): o Núcleo não lê `projects`. No B1 a ligação é a implementação vazia `App\Platform\Services\NoClientManagement` (ninguém gere cliente algum); o B2 entrega a real em `Projetos\Services\ProjectClientManagement` e troca a ligação no `AppServiceProvider`. Teste de gestor usa o dublê `tests/Support/ClientManagementFalso.php`.

## Regras de domínio

As regras são as G, N, P e IA da `docs/necessidade.md`; o funcional de cada bloco (`docs/funcional/`) manda em tela, campo, ação e permissão; o `docs/escopo.md`, em tabela, rota, Service e ordem. Antes de tocar num fluxo:

1. **Premissa de Projetos:** nenhuma regra nem coluna existe só para travar o fluxo (acima).
2. **Mesmo projeto (R15):** etapa, sprint, pacote, área, tarefa de origem, dev e QA de uma tarefa são do projeto dela. É **asserção no Service**, em toda gravação, coberta por teste. Não há FK composta.
3. **Chave da tarefa (T4):** número pelo contador atômico `UPDATE projects SET last_task_number = LAST_INSERT_ID(last_task_number + 1)`, na transação da criação. Nunca `max + 1`.
4. **Uma sprint aberta por projeto (P45), uma etapa ativa de `queue`, `sprint` e `support` por projeto (P11), uma conexão ativa por usuário e assistente (IA4):** as duas últimas o banco trava (colunas geradas + unique); a primeira é do Service, com a linha do projeto travada.
5. **Excluir quase nunca (G2, banco.md §4):** cadastro com histórico se desativa (`Deactivatable`), card com histórico se arquiva; só o nunca usado se exclui, com `deleteIfUnused()`. "Já usado" = alguma FK aponta para a linha (`isReferenced()` lê o catálogo do MySQL).
6. **Auditoria (G3):** toda escrita e toda negação em `audit_logs`, na mesma transação, com o canal. Nunca editar nem apagar, exceto a anonimização do contato (N12). Senha e token nunca em `changes`.
7. **Datas:** `datetime` em UTC (nunca `TIMESTAMP`); data de negócio (`date`) no fuso America/Sao_Paulo; dias úteis = segunda a sexta, sem feriado (P29).

## Módulos e flags

Nenhum módulo ligável na fatia 1. CRM (v2) e Chamados entram como `app/Domains/Crm` e `app/Domains/Chamados`, com migração aditiva.

## Testes técnicos

Pelo perfil de uso (`docs/escopo.md` §10): **arquitetura** sim (`tests/Arch`: A1 a A7, A9 "domínio não lê tabela de outro", A5 `RotasAutorizadasTest`); **banco de verdade** sim, em tudo (ADR-002); **benchmark** só no quadro (B3) e nos indicadores (B7), contagem de consultas com 500 cards; **carga** não. Teste de concorrência onde há contador ou "um só aberto" (`tests/Support/Concorrencia.php`). A régua (dívida, limites) só muda com aprovação do Lucas Scholz.

## O que exige aprovação humana neste projeto

Além do padrão (inegociável 5):

- **Toda migração.** As 27 tabelas vieram no B0, exatamente como o `docs/banco.txt`. Coluna ou tabela que não está lá: pare e pergunte (o bloco não cria migração por conta própria).
- **Policy nova ou alterada**, e qualquer mudança em `abilities`.
- **Exclusão de verdade** (qualquer `delete` de registro de negócio) e a anonimização do contato.
- **OAuth e MCP:** escopos do Passport, ferramentas MCP, o que a IA pode escrever (`users.ai_write_allowed`, `ai_assistants.write_allowed`), limites do `config/hub.php`.
- Trilho completo (spec na skill + Verifier) em B1, B8, B9 e em toda Policy, exclusão de verdade e migração que apaga ou reescreve.

## Trilhos

Do `docs/plano-de-desenvolvimento.md` §5:

| | Trilho completo | Trilho leve |
|---|---|---|
| Quando | B1, B8, B9; em qualquer bloco: Policy nova, exclusão de verdade, migração que apaga ou reescreve | B2 a B7 |
| Spec | `.specs/features/<bloco>/` pela skill `tlc-spec-driven`, aprovada pelo Lucas Scholz antes do código | Na descrição do PR: objetivo, regras (códigos), o que não entra, premissas decididas |
| Verificação | Verifier em agente separado (PASS com evidência), mutação nas regras de permissão e exclusão, revisão em camadas | Teste por regra + uso no navegador com print; teste cruzado pelo outro dev |
| Critérios de piso (`.ai/core/especificacao.md` §6) | Sim | Sim (o "escopo por empresa" do piso vira "usuário não alocado no projeto") |

Dúvida de regra: Lucas Scholz, antes da spec. Branch `feat/b<n>-<nome>` a partir de `origin/main`.

## Um jeito só

- Papel: `RoleEnum` / `roles.slug`. Nunca o rótulo, nunca `user_type`.
- Ativo: `->active()` / `isActive()` do `Deactivatable`. Nunca `whereNull('deactivated_at')` à mão.
- Auditoria: `AuditService::log()`. Nunca `AuditLog::create()` fora dele (os testes podem). Negação: `AuditService::denied()`, chamado só pelo trait `App\Http\Concerns\AuditsDeniedAbilities` (o `authorize()` do Controller base e o dos FormRequests). Limpar dado pessoal (anonimização): `AuditService::scrubPersonalData()`.
- Desativar e reativar: ação própria na linha (`POST …/deactivate`, `…/reactivate`), nunca uma caixa "ativo" no formulário (D3 do B1).
- CNPJ: `App\Rules\AlphanumericCnpj` (alfanumérico, gravado sem máscara e em maiúscula). País: `App\Support\Paises`.
- Domínio fechado: compare pelo id da constante do Model (`StageRole::QUEUE`, `TaskPriority::NORMAL`, `ContactChannel::WHATSAPP`) ou pelo slug; os ids são os da semente.
- Dado de exemplo: só pelo `ExemploSeeder`, marcado por `App\Support\Exemplo`.

## Armadilhas que já custaram tempo

- `datetimes()` do Laravel cria `created_at`/`updated_at` **nulos**; o `banco.txt` pede not null. As migrações do Hub declaram `dateTime('created_at')` e `dateTime('updated_at')` à mão (as do framework e do Passport seguem com `datetimes()`).
- `task_acceptance_criteria.parent_key` é **VIRTUAL**, não STORED: com STORED o MySQL recusa a FK com cascade em `parent_id` (erro 1215). Coluna usada em CHECK não aceita FK com cascade ou set null (erro 3823).
- Coluna gerada (`single_role_key`, `active_assistant_id`, `parent_key`) nunca entra em `insert`/`update`: o MySQL recusa.
- `users` não tem `remember_token`: `Auth::attempt` sem "lembrar"; `$rememberTokenName = ''` no `User`.
- `users.password` é not null: o usuário convidado nasce já com a senha provisória (`TemporaryPasswordService::issue` grava a linha).
- Model fora de `App\Models` precisa de `newFactory()`, e entra no morph map; senão a factory não resolve e o `auditable_type` falha.
- O `.gitignore` antigo do repositório (só `docs/`) ignorava `*.json`; os JSON de `docs/brand/` entraram no B0.
- A trava global de segredo barra `.env.testing` e `.env.e2e.example` pelo nome: falso positivo (chave de teste e o MySQL local); commit com `--no-verify` documentado.

## Para ir mais fundo

| Assunto | Onde |
|---|---|
| Entrada e ordem de leitura | `docs/00-COMECE-AQUI.md` |
| Requisito e regras (G, N, P, IA) | `docs/necessidade.md` |
| Funcional por bloco | `docs/funcional/` |
| Escopo (blocos B0 a B10) | `docs/escopo.md` |
| Plano, rito do PR, trilhos | `docs/plano-de-desenvolvimento.md` |
| Banco | `docs/banco.txt` |
| Marca | `docs/brand/README.md` |
| Deploy | `docs/DEPLOY.md` |
| Decisões | `docs/adr/` e `.specs/STATE.md` |
| Specs das features, retomada e lições | `.specs/` (`.ai/core/especificacao.md`) |
