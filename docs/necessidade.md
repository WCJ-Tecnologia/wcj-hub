# Necessidade: WCJ Central (nome provisório)

Etapa 1 do `playbooks/aplicacao-nova.md` (padrão `ai-systems` v0.2.1). Entrada para o modelo de banco (etapa 2) e para o escopo (etapa 4).

**Situação:** rascunho para o Comitê de IA. Itens marcados **[LEVANTAR]** dependem do dono do módulo; itens marcados **[DECIDIR]** dependem do comitê. Regras marcadas *(proposta)* ainda não foram validadas por quem usa.

### Decisões registradas

| # | Data | Decisão |
|---|---|---|
| D-GER-01 | 24/09/2026 | Pessoa técnica que aprova o banco (etapa 2): **Lucas Alencar**. |
| D-GER-02 | 24/09/2026 | Gestor de projetos: **Rafael Garcia**. Projetos feitos 100% com IA são geridos por **Lucas Scholz**. |
| D-GER-03 | 24/09/2026 | O funil de prospecção do DaaS **migra para o CRM deste sistema**. O DaaS fica só com a parte de auditoria. |
| D-IA-01 a 03 | 24/09/2026 | Ver [Interface de IA (MCP)](#interface-de-ia-mcp). |
| D-PRJ-01 a 10 | 24/09/2026 | Reunião de Projetos com Rafael Garcia. Ver [ata](atas/2026-09-24-projetos-rafael.md). |
| D-PRJ-12 | 24/09/2026 | O QA testa no **ambiente local dele**; o **Tech Lead faz o merge** (Lucas Alencar, via Lucas Scholz). |
| D-PRJ-11 | 24/09/2026 | Colunas padrão do quadro, propostas por Lucas Alencar: `Backlog`, `To do`, `Doing`, `Paused`, `Code-review`, `In test`, `Done`. Fluxo de desenvolvimento em 11 passos, também dele. Ver [`levantamento/fluxo-desenvolvimento.md`](levantamento/fluxo-desenvolvimento.md). |
| D-GER-04 | 24/09/2026 | Dono do módulo Chamados (sustentação): **Alaor Rodrigues**. |
| D-IA-04 | 24/09/2026 | Todos os usuários internos têm plano **Pro ou superior** (Claude e/ou ChatGPT). Nenhum usa plano gratuito. |

**Porta de entrada do playbook:** "cliente só com conversa". Não existe sistema anterior com modelo de dados aproveitável (Trello é ferramenta genérica). Logo: regras e fluxo primeiro, IA propõe o modelo de dados, pessoa técnica valida na etapa 2.

---

## Para que serve

Um sistema único para a operação interna da WCJ, com três módulos sobre uma base comum:

| Módulo | Substitui | Problema de hoje |
|---|---|---|
| **Projetos** | Trello (cerca de R$ 500,00 por mês, segundo a ata de 24/09) | No quadro Wpay: retrabalho do QA invisível (2 em 3 entregas voltam), versões controladas como listas criadas à mão, prazo quase não usado, chamado misturado com projeto. Rafael: sem tempo por etapa nem previsto × realizado de horas; cronologia de entregas para o cliente montada à mão. |
| **CRM** | Funil de prospecção do DaaS (D-GER-03) + [LEVANTAR] planilha / WhatsApp | Lead e tratativa não ficam registrados num lugar só; follow-up depende de alguém lembrar. |
| **Chamados** | [LEVANTAR] WhatsApp / e-mail direto com o técnico | Cliente de sustentação e de projeto em andamento não tem canal formal; não há prazo de atendimento medido nem histórico por cliente. |

**Base comum (núcleo):** usuários, papéis, clientes (empresas), contatos e contratos. É o que liga os três módulos: o lead ganho no CRM vira cliente; o cliente tem contratos; o contrato tem projetos e dá direito a abrir chamados.

**Interface de IA:** cada usuário interno conecta o próprio assistente (Claude ou ChatGPT) ao sistema para consultar e alterar dados dos módulos, com as mesmas permissões que tem na tela. Detalhado no bloco [Interface de IA (MCP)](#interface-de-ia-mcp).

---

## Quem usa

| Papel | Quem é | O que faz no sistema | O que não pode ver ou fazer |
|---|---|---|---|
| Administrador | Sócios | Tudo; gerencia usuários, papéis, clientes e contratos | — |
| Gestor de projetos | Rafael Garcia; Lucas Scholz nos projetos 100% IA | Cria e acompanha projetos, distribui tarefas, vê chamados dos clientes dos seus projetos | **Pipeline comercial e valores de proposta** (D-PRJ-08) |
| Tech Lead | **Definido por projeto** (D-PRJ-10): é uma função no projeto, não um papel fixo do usuário | Aprova o PR no `Code-review` **dos projetos em que é Tech Lead** | Aprovar code review em projeto em que não é Tech Lead |
| Desenvolvedor / técnico | Devs (PJ) | Trabalha nas tarefas atribuídas, atende chamados atribuídos | CRM; contratos e valores; projetos em que não está alocado [DECIDIR] |
| Comercial | Equipe comercial | Cadastra leads, registra tratativas, agenda follow-ups, converte lead em cliente | Tarefas internas de projeto; conteúdo dos chamados [DECIDIR] |
| Suporte / sustentação | Alaor Rodrigues (dono); [LEVANTAR] quem mais faz a triagem | Recebe, classifica e distribui chamados; acompanha prazo | CRM |
| Contato do cliente (externo) | Pessoa indicada pelo cliente | Abre e acompanha os chamados **da própria empresa** | Qualquer dado de outro cliente; CRM; projetos internos; **não conecta IA na v1** |

Regra geral: o que o papel não vê na tela, a IA dele também não vê.

---

## Núcleo (base comum)

### Fluxo principal

1. Administrador cadastra usuários internos e define o papel de cada um.
2. Cliente nasce de duas formas: cadastro direto ou conversão de lead ganho no CRM.
3. Cliente recebe contratos (tipo: projeto, sustentação, auditoria, outro [LEVANTAR]).
4. Contatos do cliente são cadastrados e, se autorizados, recebem acesso ao portal de chamados.

### Regras de negócio

- **RN-NUC-01** Cliente é identificado pelo CNPJ. Dois clientes com o mesmo CNPJ são recusados.
- **RN-NUC-02** Cliente, contato e usuário não são excluídos depois de usados. São desativados (`deactivated_at`, `deactivated_by`), conforme `core/banco.md` seção 4.
- **RN-NUC-03** Contato externo só enxerga dados do próprio cliente. Tentativa de acessar outro cliente por ID é negada e registrada.
- **RN-NUC-04** Toda alteração em registro de qualquer módulo grava trilha de auditoria: quem, quando, antes/depois e **canal** (`tela`, `ia:claude`, `ia:chatgpt`).
- **RN-NUC-05** Usuário desativado perde acesso imediatamente, inclusive os tokens de IA dele.

---

## Módulo Projetos

Base: quadro Trello "Wpay" ([`levantamento/trello-wpay.md`](levantamento/trello-wpay.md)), reunião com Rafael Garcia em 24/09/2026 ([ata](atas/2026-09-24-projetos-rafael.md)) e modelos enviados por ele ([`levantamento/modelos-rafael.md`](levantamento/modelos-rafael.md)).

**Escopo da v1 (D-PRJ-05):** "as funcionalidades do Trello com a adição do controle do tempo". Ou seja: quadro por projeto com etapas configuráveis, tarefas, responsáveis, checklist, anexos e comentários, **mais** tempo por etapa e horas previstas × realizadas.

### Fluxo principal

1. Gestor cria o projeto vinculado a um cliente e a um contrato, com **prefixo** (ex.: `SGM`) e **etapas próprias** (D-PRJ-01). Modelo padrão (D-PRJ-11): `Backlog → To do (sprint) → Doing → Code-review → In test → Done`, mais `Paused` e, com o módulo Chamados, `Chamados`. Detalhe em [`levantamento/fluxo-desenvolvimento.md`](levantamento/fluxo-desenvolvimento.md).
2. Gestor (ou a IA dele) escreve a tarefa no **modelo de requisição**: Sendo / Quero / Para + critérios de aceite `CA_1…CA_n`. Cada critério vira item do roteiro de teste.
3. Gestor atribui pessoas **por função** (back-end, front-end, QA) e define quais validações a tarefa exige: **code review**, **teste cruzado** e/ou **QA** (RN-PRJ-09).
4. Tarefa sem responsável não sai do Backlog (D-PRJ-02). Quem é atribuído é notificado.
5. O dev analisa a tarefa e **informa as horas previstas** (por tarefa ou por item). O sistema converte em **data prevista** considerando dias úteis, feriados e a carga diária do dev.
6. A tarefa entra em `To do` (a sprint; no Trello, `Esteira`) e segue as etapas. Cada mudança de etapa é registrada com data e hora. Em `Doing` o dev desenvolve e faz commits e pushes na branch. Ao terminar, **abre o PR para a `main`** e a tarefa vai para `Code-review`, onde o **Tech Lead** do projeto aprova (D-PRJ-09).
7. Em `In test` acontecem, nesta ordem, o **teste cruzado** (outro dev, nunca o autor) e o **QA**, que executa o roteiro, marca os itens e anexa a evidência em cada um. O dev lança as **horas realizadas**.
8. Aprovado no QA, o **Tech Lead faz o merge** e a tarefa vai para `Done`. Reprovado no code review, no teste cruzado ou no QA, volta para `Doing` com motivo de lista fechada.
9. Tarefas em `Done` entram num **pacote (versão)**. O pacote é liberado em **homologação**, homologado e publicado em produção; aí está finalizado. A data é definida por projeto; em projeto de cliente, geralmente pelo cliente.
10. Chamado de cliente ligado ao projeto cai na coluna `Chamados` do quadro, como tarefa ligada ao chamado.
11. O gestor gera para o cliente o **histórico de desenvolvimento** (linha do tempo) e o **relatório de fechamento** do período.

### Regras de negócio

- **RN-PRJ-01** Todo projeto pertence a um cliente e a um contrato. Projeto interno ou produto da WCJ (ex.: Wpay) usa o cliente "WCJ".
- **RN-PRJ-02** Tarefa sem responsável não sai do `Backlog` (D-PRJ-02).
- **RN-PRJ-03** Projeto encerrado não recebe tarefa nova; reabrir exige Gestor ou Administrador.
- **RN-PRJ-04** Dev só vê projetos em que está alocado (D-PRJ-03).
- **RN-PRJ-05** Todo projeto tem um gestor responsável. Gestor vê e edita os projetos que gere; Administrador vê todos.
- **RN-PRJ-06** Projeto tem **modalidade**: `tradicional` ou `100% IA` (D-GER-02). [LEVANTAR com Lucas Scholz] se a modalidade 100% IA muda o fluxo, por exemplo com etapas fixas espelhando o playbook `aplicacao-nova` e as paradas obrigatórias como marcos do projeto.
- **RN-PRJ-07** **Tipo** e **área** da tarefa são dois campos separados, cada um com lista fechada. As áreas são cadastradas por projeto (no Wpay: Admin, Geral, FDIC, Âncora, Fornecedores, Comercial).
- **RN-PRJ-08** Toda devolução para `Doing` exige **motivo de lista fechada** e conta um ciclo de retrabalho, com a **origem**: code review, teste cruzado, QA ou homologação. Motivos iniciais [validar com Rafael]: ajuste visual, regra de negócio, erro/bug, **requisito incompleto ou mal documentado**. O último mede a qualidade da documentação, apontada pelo Rafael como a principal causa de retrabalho.
- **RN-PRJ-09** A tarefa indica se **exige code review**, se **exige teste cruzado** e se **exige QA**. Validação não exigida é pulada sem registrar exceção (ex.: Swagger tem code review e não tem QA). Pular etapa exigida fica registrado.
- **RN-PRJ-10** Tarefa só entra num pacote (versão) se estiver `Done`, ou seja, com merge feito. O pacote tem estados: `em montagem → em homologação → homologado → em produção`. Pacote em homologação ou depois não recebe tarefa nova; reprovação na homologação devolve as tarefas afetadas para `Doing` e conta retrabalho de origem **homologação**.
- **RN-PRJ-11** Mudança de etapa sempre grava quem, quando, de onde e para onde (RN-NUC-04). É daí que saem tempo por etapa e retrabalho.
- **RN-PRJ-12** Ir para `Impedimento/Pausa` exige motivo; o tempo parado é contado à parte.
- **RN-PRJ-13** As etapas são **configuráveis por projeto** (D-PRJ-01). O sistema precisa saber o **papel** de cada etapa (fila, desenvolvimento, revisão, teste, pronto, pausa, chamados) para calcular os indicadores, independentemente do nome.
- **RN-PRJ-14** Pessoa é atribuída à tarefa **com uma função** (back-end, front-end, QA, outra). Pode haver mais de uma pessoa por função.
- **RN-PRJ-15** Horas previstas e realizadas são registradas **por pessoa**. Horas são decimais; não há arredondamento para dia.
- **RN-PRJ-16** A data prevista é calculada em **dias úteis**: horas previstas ÷ carga diária do dev, pulando fins de semana e feriados do calendário. Ex.: 16 h = 2 dias úteis para 8 h/dia e 4 dias úteis para 4 h/dia.
- **RN-PRJ-17** Cada usuário interno tem **carga diária** cadastrada (ex.: 8 h ou 4 h). Mudança de carga vale dali para a frente; não recalcula o passado.
- **RN-PRJ-18** Existe um **calendário de feriados** (nacionais + locais) mantido pelo Administrador.
- **RN-PRJ-19** Tarefa tem **chave** `<prefixo do projeto>-<número>` (ex.: `SGM-17`). O número é sequencial por projeto, gerado por sequência do banco, e nunca é reutilizado.
- **RN-PRJ-20** Critério de aceite da tarefa gera item no roteiro de teste. Editar o critério atualiza o item enquanto ele não foi testado; depois de testado, a alteração reabre o item e fica no histórico.
- **RN-PRJ-21** Atribuir pessoa a uma tarefa **notifica** a pessoa. [LEVANTAR canal: no sistema, e-mail]
- **RN-PRJ-23** Tarefa que exige code review só entra em `Code-review` com **PR vinculado** (PR para a `main`). Vai para `In test` quando o Tech Lead aprova; PR recusado devolve a tarefa para `Doing` com retrabalho de origem **code review** (D-PRJ-09).
- **RN-PRJ-24** Todo projeto com code review tem **um Tech Lead** definido pelo gestor (D-PRJ-10). A mesma pessoa pode ser Tech Lead num projeto e dev em outro. Só o Tech Lead do projeto (ou o Administrador) move a tarefa de `Code-review` para `In test`. Trocar o Tech Lead vale dali para a frente e fica no histórico.
- **RN-PRJ-25** O Tech Lead não aprova a revisão de tarefa em que ele próprio é o dev. [DECIDIR] quem aprova nesse caso: outro Tech Lead indicado pelo gestor, ou o gestor.
- **RN-PRJ-26** O teste cruzado é feito por um dev **diferente do autor** da tarefa, alocado no projeto.
- **RN-PRJ-27** A tarefa só vai para `Done` com o merge registrado. Quem faz o merge é o **Tech Lead** do projeto (D-PRJ-12); só ele (ou o Administrador) move de `In test` para `Done`.
- **RN-PRJ-28** O QA testa a branch do PR **no próprio ambiente local** (D-PRJ-12). O sistema não cria nem exige ambiente por PR. Como as tarefas só se encontram depois do merge, a **homologação do pacote** é o primeiro teste integrado (RN-PRJ-10).
- **RN-PRJ-22** Indicadores de produtividade por dev (prazo, retrabalho, previsto × realizado) são vistos pelo gestor e pelo Administrador. O dev vê **apenas os próprios**. Não existe ranking público.

### Funcionalidades além do Trello e prioridade

Legenda: **v1** = entra na primeira versão; **v1+** = logo depois da v1; **?** = a priorizar. A prioridade segue D-PRJ-05 (Trello + controle do tempo).

| # | Funcionalidade | Origem | Prioridade |
|---|---|---|---|
| F-PRJ-02 | **Tempo por etapa em linha do tempo**, por tarefa, e tempo total até pronto | Trello Wpay + Rafael | **v1** |
| F-PRJ-11 | **Horas previstas × realizadas** por tarefa, pessoa e projeto | Rafael | **v1** |
| F-PRJ-12 | **Data prevista em dias úteis** pela carga diária do dev e pelo calendário de feriados | Rafael | **v1** |
| F-PRJ-09 | **Prazo e atraso** visíveis por projeto e por pessoa | Rafael | **v1** |
| F-PRJ-18 | **Atribuição por função** (back, front, QA) | Rafael | **v1** |
| F-PRJ-19 | **Notificação** ao atribuir | Rafael | **v1** |
| F-PRJ-20 | **Chave por projeto** (`SGM-17`) | Modelos SGM | **v1** |
| F-PRJ-05 | **Modelo de tarefa** = documento de requisição (Sendo/Quero/Para + `CA_n`) | Modelos SGM | **v1** |
| F-PRJ-14 | **Critério de aceite vira item do roteiro de teste** | Rafael | **v1** |
| F-PRJ-08 | **Coluna "Chamados"** no quadro: chamado do cliente cai como tarefa ligada | Rafael | v1 do módulo Chamados (D-PRJ-06) |
| F-PRJ-01 | **Indicador de retrabalho** com motivo da devolução | Trello Wpay | v1+ (os dados já são gravados na v1 por RN-PRJ-08/11) |
| F-PRJ-17 | **Painel de produtividade por dev** (prazo, retrabalho, previsto × realizado) | Rafael | v1+ (idem) |
| F-PRJ-03 | **Versão** como registro, com notas de versão | Trello Wpay + Rafael | v1+ |
| F-PRJ-15 | **Histórico de desenvolvimento** em linha do tempo, em PDF para o cliente (modelo Tecsolution) | Rafael | v1+ |
| F-PRJ-16 | **Relatório de fechamento** do período para aprovação do cliente antes do pagamento | Rafael | v1+ (aguarda modelo, P-01) |
| F-PRJ-13 | **Gerar o documento de requisição** (Word/PDF padrão WCJ) a partir da tarefa e anexar | Rafael | v1+ |
| F-PRJ-04 | **Roteiro de teste padrão** por tipo e perfil do produto, incluindo bloco CRUD reutilizável | Trello Wpay + Modelos SGM | ? |
| F-PRJ-06 | **Vínculo com PR do GitHub**: link do PR na tarefa (manual ou pela IA) na v1; status do PR (aberto, aprovado, recusado) lido do GitHub depois | Trello Wpay + D-PRJ-09 | **v1** (link) / v1+ (status automático) |
| F-PRJ-07 | **Evidência de teste** presa ao item do roteiro | Trello Wpay | ? |
| F-PRJ-10 | **Painel de impedimentos** | Trello Wpay | ? |

Pela IA (acrescenta às ferramentas do bloco MCP): leitura: `linha_do_tempo_da_tarefa`, `horas_do_projeto`, `retrabalho_do_projeto`, `tarefas_paradas`, `notas_da_versao`; escrita: `escrever_tarefa` (cria a tarefa no modelo de requisição, com os critérios), `lancar_horas`, `anexar_pr`, `devolver_para_desenvolvimento` (exige motivo). Caso de uso citado pelo Rafael: a IA avança as tarefas e escreve a documentação enquanto o dev trabalha, e a partir do escopo escreve as tarefas detalhadas.

### A levantar

- [ ] Os outros quadros do Trello, em especial um de projeto de cliente com prazo (ex.: gestão de multas), para comparar com o Wpay.
- [ ] Modelo do relatório de fechamento para pagamento (P-01, P-02).
- [ ] Fase × sprint × versão: a "fase" do histórico Tecsolution é um nível acima da versão? ([`modelos-rafael.md`](levantamento/modelos-rafael.md))
- [ ] Horas previstas por **tarefa** ou por **item do roteiro**? (Rafael citou as duas formas)
- [ ] Canal de notificação (RN-PRJ-21).
- [ ] Lista de motivos de devolução (RN-PRJ-08).

---

## Módulo CRM

### Fluxo principal *(proposta)*

1. Comercial cadastra o lead (empresa, contato, origem).
2. Lead avança pelas etapas do funil [LEVANTAR etapas; referência: funil do DaaS].
3. Cada contato com o lead vira uma **tratativa**: data, canal, resumo, próximo passo.
4. Tratativa com próximo passo gera um **follow-up** com data e responsável.
5. Comercial vê, todo dia, os follow-ups vencidos e os do dia.
6. Lead é ganho (vira cliente no núcleo, com contrato) ou perdido (com motivo).

### Regras de negócio *(proposta)*

- **RN-CRM-01** Lead ganho converte para cliente sem redigitar dado. Se o CNPJ já existe como cliente, vincula em vez de duplicar.
- **RN-CRM-02** Lead perdido exige motivo de uma lista fechada [LEVANTAR motivos].
- **RN-CRM-03** Tratativa não é editada depois de 24 h; correção vira nova tratativa. [DECIDIR]
- **RN-CRM-04** Todo lead ativo tem um follow-up futuro. Lead ativo sem follow-up aparece em alerta para o responsável.
- **RN-CRM-05** Comercial vê os próprios leads; Administrador vê todos. [DECIDIR se comerciais veem os leads uns dos outros]

### A levantar

- [ ] Etapas do funil e o critério para passar de uma para outra.
- [ ] Campos mínimos do lead e da tratativa.
- [ ] Origens de lead (indicação, LinkedIn, grupos, site, evento…).

### Migração do funil do DaaS (D-GER-03)

O funil de prospecção vive hoje no DaaS (`wcj-ui`, tabelas `lead` e `lead_evento`, kanban em `/console/comercial/prospeccao`). Ele vem para cá; no DaaS fica só a auditoria.

- **RN-CRM-06** Leads e eventos do DaaS entram por **importação única e idempotente**: casa por CNPJ antes de nome e não sobrescreve campo já preenchido (`core/banco.md` seção 9). Evento do DaaS vira tratativa. Roda primeiro em modo simulado.
- **RN-CRM-07** Os campos de dimensionamento que hoje travam o "ganhar" no DaaS (setor, 5 respostas de sizing, preço) viram campos do lead com oportunidade de **auditoria**. [LEVANTAR com Lucas Scholz] se continuam obrigatórios para ganhar.
- **RN-CRM-08** Hoje "ganhar lead" no DaaS cria a auditoria em pré-venda. Aqui, lead ganho com contrato de auditoria **não cria nada no DaaS na v1**: a auditoria é aberta no DaaS à mão, com o CNPJ do cliente. [DECIDIR] se a integração automática CRM → DaaS entra numa versão seguinte.
- **RN-CRM-09** Depois da importação conferida (contagem de leads e eventos batendo com a origem), o funil sai do DaaS. A remoção no DaaS é trabalho de manutenção **naquele repositório** (`playbooks/manutencao.md`), com PR próprio: tira a tela, a rota e as RPCs de escrita e mantém as tabelas como histórico somente leitura até a pessoa técnica liberar a exclusão.
- [ ] [LEVANTAR] O DaaS também tem um CRM pós-auditoria (anterior ao funil). Confirmar se ele fica no DaaS, como parte da auditoria, ou se vem junto.
- [ ] O assistente de voz do funil do DaaS (F2) é IA embutida e **não vem** na v1 (D-IA-02).

---

## Módulo Chamados

### Fluxo principal *(proposta)*

1. Contato do cliente abre chamado no portal: sistema/contrato, tipo, descrição, anexos.
2. Suporte faz a triagem: confirma tipo e prioridade e atribui um responsável.
3. Responsável atende, registra interações e muda o status.
4. Cliente acompanha pelo portal e responde quando solicitado.
5. Chamado é resolvido; cliente confirma ou o chamado fecha sozinho após [LEVANTAR] dias.

### Regras de negócio *(proposta)*

- **RN-CHM-01** Chamado só pode ser aberto contra contrato ativo do próprio cliente.
- **RN-CHM-02** Número do chamado é sequencial por sequência do banco, nunca `max + 1` (`core/banco.md` seção 8).
- **RN-CHM-03** Prazo de atendimento (SLA) vem do contrato e da prioridade [LEVANTAR tabela de SLA por tipo de contrato].
- **RN-CHM-04** Chamado com prazo estourado aparece em destaque para Suporte e Administrador.
- **RN-CHM-05** Comentário interno (entre a equipe) nunca aparece para o cliente.
- **RN-CHM-06** Chamado fechado não é reaberto; o cliente abre outro, vinculado ao anterior. [DECIDIR]

### A levantar

- [ ] Tipos de chamado (incidente, dúvida, melhoria, acesso…).
- [ ] Prioridades e o que define cada uma.
- [ ] SLA por contrato; se existe banco de horas de sustentação a descontar.
- [ ] Quais sistemas vendidos entram na v1 (lista de contratos de sustentação ativos).
- [ ] **[DECIDIR]** Canais de abertura na v1: só portal (proposta) ou também e-mail/WhatsApp.

---

## Interface de IA (MCP)

### Decisões do comitê (24/09/2026)

| # | Decisão |
|---|---|
| D-IA-01 | Na v1, **somente usuários internos** conectam IA. Contato do cliente não conecta. |
| D-IA-02 | **Sem IA embutida** na v1. O sistema não chama nenhum modelo por conta própria; toda IA é a do usuário, paga pelo usuário. |
| D-IA-03 | Assistentes homologados: **Claude** e **ChatGPT**. |

### Disponibilidade nos planos (D-IA-04: todos em Pro ou superior)

Levantamento de 24/09/2026:

| Assistente | Conector MCP remoto no plano Pro+ | Escrita | Situação |
|---|---|---|---|
| Claude (Pro, Max) | Sim: "Customize → Connectors", com OAuth e registro dinâmico de cliente | Sim | Atende |
| ChatGPT (Plus, Pro), app web | Sim, pelo **modo desenvolvedor** | **Incerto** | **Risco** |
| Claude Code (CLI/IDE/desktop) | Sim, conector local do usuário, HTTP + OAuth | Sim | Atende |
| Codex (CLI/IDE/app) | Sim: `codex mcp add <nome> --url <URL>` + `codex mcp login <nome>` (HTTP + OAuth) | Sim | Atende |

O R-IA-01 é do **app ChatGPT**, não do Codex. No Codex e no Claude Code o conector é configurado pelo próprio usuário no cliente dele e não passa pela regra de conector do workspace do ChatGPT. Só que esses dois são agentes de código: servem para quem já trabalha no terminal ou na IDE (devs), não para o comercial no dia a dia.

**Risco R-IA-01:** a documentação de desenvolvedor da OpenAI diz que o modo desenvolvedor dá suporte completo de leitura e escrita no Plus e no Pro. Fontes de terceiros afirmam que a central de ajuda restringe a escrita em conector próprio a Business, Enterprise e Edu, deixando Plus e Pro só com leitura. As fontes divergem, e a central de ajuda não pôde ser consultada diretamente.

**Mitigação: prova de conceito antes da etapa 4.** Um servidor MCP mínimo, publicado em HTTPS público, com OAuth, uma ferramenta de leitura e uma de escrita, testado num Claude Pro e num ChatGPT Plus/Pro reais. Resultado possível:
- escrita funciona nos dois: segue como está;
- ChatGPT só lê: o ChatGPT entra na v1 **só com escopos de leitura** e a escrita fica para Claude, até a OpenAI liberar ou a WCJ ter um workspace Business. [DECIDIR] no comitê, com o resultado em mãos.

Observação: os dois assistentes acessam o servidor MCP **a partir da nuvem deles**, não da máquina do usuário. O servidor precisa estar na internet pública com HTTPS. Isso vale também para homologação: dev local testa por túnel ou por ambiente de homologação publicado.

### Como funciona

- O sistema publica um **servidor MCP remoto** (HTTP). Os dois assistentes suportam conector MCP remoto.
- O usuário conecta o assistente pela tela de perfil. A autenticação é **OAuth 2.1** (exigido pela especificação MCP para servidor remoto): o usuário faz login no sistema e autoriza o assistente. Não há chave colada à mão.
- Cada autorização gera um token **por usuário, por assistente**, com escopos por módulo e por tipo de acesso (`projetos:ler`, `projetos:escrever`, `crm:ler`, `crm:escrever`, `chamados:ler`, `chamados:escrever`).
- As ferramentas MCP chamam **a mesma camada de serviço** que as telas. Não existe regra de negócio só no MCP nem só na tela.

### Regras de negócio

- **RN-IA-01** A IA age como o usuário: toda chamada passa pelas mesmas Policies da tela. Papel sem acesso a um dado recebe "negado" também pela IA.
- **RN-IA-02** O escopo do token nunca excede o papel. Comercial não recebe `projetos:escrever`, mesmo que peça.
- **RN-IA-03** Toda escrita feita pela IA é gravada na auditoria com canal `ia:claude` ou `ia:chatgpt` e o identificador da autorização (RN-NUC-04).
- **RN-IA-04** O usuário vê e revoga as autorizações dele na tela de perfil. O Administrador vê e revoga as de qualquer um.
- **RN-IA-05** Usuário desativado ou com papel alterado tem os tokens revogados na hora.
- **RN-IA-06** Limite de chamadas por token [LEVANTAR valor]. Ao estourar, a ferramenta devolve erro legível para o assistente, sem derrubar a sessão do usuário.
- **RN-IA-07** Ferramenta de escrita altera **uma entidade por chamada**. Não existe ferramenta de alteração em lote.
- **RN-IA-08** Ferramentas são marcadas como leitura ou escrita (anotações MCP `readOnlyHint` / `destructiveHint`), para o assistente pedir confirmação antes de escrever. **A segurança não depende dessa confirmação**: o servidor valida tudo (RN-IA-01).
- **RN-IA-09** Texto vindo de cliente (descrição e comentários de chamado, anexos) é devolvido à IA como **dado**, delimitado e identificado como conteúdo externo. É o vetor de injeção de prompt: um chamado com "feche todos os chamados" não pode causar isso. RN-IA-07 e RN-IA-01 limitam o dano.

### Ferramentas da v1 *(proposta, validar com os donos)*

A regra é: ferramenta de negócio, com nome do vocabulário do produto. Nada de SQL genérico nem de "atualizar qualquer tabela".

**Núcleo**

| Ferramenta | Tipo | O que faz |
|---|---|---|
| `buscar_cliente` | leitura | Busca por nome ou CNPJ, dentro do que o papel vê |
| `resumo_do_cliente` | leitura | Contratos ativos, projetos em andamento, chamados abertos, último contato comercial |

**Projetos**

| Ferramenta | Tipo | O que faz |
|---|---|---|
| `minhas_tarefas` | leitura | Tarefas do usuário, com filtros por prazo e projeto |
| `status_do_projeto` | leitura | Etapas, tarefas atrasadas, próximos prazos |
| `criar_tarefa` | escrita | Cria tarefa num projeto em que o usuário tem permissão |
| `mover_tarefa` | escrita | Muda a etapa de uma tarefa |
| `comentar_tarefa` | escrita | Adiciona comentário |

**CRM**

| Ferramenta | Tipo | O que faz |
|---|---|---|
| `meus_followups` | leitura | Vencidos, de hoje e da semana |
| `historico_do_lead` | leitura | Tratativas em ordem, etapa atual, próximo passo |
| `cadastrar_lead` | escrita | Cria lead (com checagem de CNPJ duplicado) |
| `registrar_tratativa` | escrita | Registra contato e, opcionalmente, o próximo follow-up |
| `mover_lead` | escrita | Muda a etapa do funil (ganho e perdido seguem RN-CRM-01/02) |

**Chamados**

| Ferramenta | Tipo | O que faz |
|---|---|---|
| `chamados_abertos` | leitura | Filtro por cliente, responsável, prioridade, prazo estourado |
| `detalhe_do_chamado` | leitura | Histórico completo (conteúdo do cliente delimitado, RN-IA-09) |
| `responder_chamado` | escrita | Resposta visível ao cliente |
| `comentar_internamente` | escrita | Comentário só da equipe (RN-CHM-05) |
| `mudar_status_do_chamado` | escrita | Muda status/responsável conforme o papel |

### Cenários de aceite de referência (formato do escopo)

- **Dado** um Comercial sem acesso ao projeto P, **quando** o assistente dele chama `status_do_projeto(P)`, **então** recebe "negado" e a tentativa fica na auditoria.
- **Dado** um Dev com token só de leitura, **quando** o assistente chama `mover_tarefa`, **então** recebe "escopo insuficiente" e nada muda.
- **Dado** um usuário desativado agora, **quando** o assistente dele chama qualquer ferramenta, **então** recebe "não autorizado".
- **Dado** um chamado cuja descrição contém instruções ao assistente, **quando** o técnico pede um resumo pela IA, **então** o conteúdo volta delimitado como dado do cliente e nenhuma escrita acontece sem pedido do técnico.
- **Dado** uma tarefa movida pela IA, **quando** o Gestor abre o histórico, **então** vê quem moveu, quando, de onde para onde e o canal `ia:claude` ou `ia:chatgpt`.

### A levantar

- [ ] Perguntas e ações que cada papel faria pela IA, uma lista curta por dono de módulo. Ela confirma ou corta as ferramentas acima.
- [ ] Enquete interna de ferramentas (em andamento): para cada pessoa, **qual assistente** (Claude, ChatGPT, outro), **qual plano** e **onde usa** (app web/desktop, celular, Claude Code, Codex). A superfície decide quem cai no R-IA-01: o comercial que usa o app ChatGPT é afetado; o dev que usa Codex, não.

---

## Integrações e dados externos

| Integração | Direção | v1? |
|---|---|---|
| Claude e ChatGPT via MCP | entrada (consulta e escrita pelo usuário) | **Sim** |
| Trello | importação | **Não na v1** (D-PRJ-04): começa do zero com os projetos ativos |
| Funil de prospecção do DaaS | importação única (RN-CRM-06) | **Sim** (D-GER-03) |
| DaaS: abrir auditoria a partir de lead ganho | saída | Não (RN-CRM-08) |
| E-mail (notificação de chamado ao cliente e ao responsável) | saída | [DECIDIR] |
| WhatsApp | — | Não (ver abaixo) |

---

## Fora da primeira versão

- IA embutida no sistema (sugestão de follow-up, triagem automática, resumo automático), inclusive o assistente de voz do funil do DaaS. D-IA-02.
- Criação automática de auditoria no DaaS quando o lead é ganho. RN-CRM-08.
- Contato do cliente conectando a própria IA. D-IA-01.
- Assistentes além de Claude e ChatGPT. D-IA-03.
- Abertura de chamado por WhatsApp.
- Faturamento, cobrança, emissão de nota.
- Apontamento de horas / timesheet [DECIDIR se banco de horas da sustentação entra].
- Aplicativo móvel nativo (a v1 é web responsiva).

## O que não pode

- Não existe ferramenta MCP de exclusão. A IA não exclui nem desativa nada na v1.
- Não existe ferramenta MCP de alteração em lote.
- Não existe ferramenta MCP que execute consulta livre (SQL, filtro arbitrário sobre qualquer tabela).
- O sistema não chama nenhum modelo de IA por conta própria e não guarda chave de API de provedor de IA.
- Não há login por chave de API colada; conexão de assistente é só por OAuth.
- Contato do cliente não vê comentário interno, nome de outro cliente nem dado do CRM.
- Não envia mensagem ao cliente pelo WhatsApp.
- Não há aprovação em dois níveis em nenhum módulo, salvo se levantado com o dono.
- Não há gamificação, ranking de vendedor nem painel de metas na v1.

## Vocabulário

| Termo | Significa |
|---|---|
| Cliente | Empresa com pelo menos um contrato com a WCJ |
| Contato | Pessoa de um cliente ou de um lead |
| Contrato | Vínculo comercial que dá origem a projeto e/ou direito a chamado |
| Lead | Empresa em prospecção, ainda sem contrato |
| Tratativa | Registro de um contato comercial com um lead |
| Follow-up | Próximo contato agendado, com data e responsável |
| Projeto | Trabalho com início e fim para um cliente |
| Tarefa | Item de trabalho dentro de um projeto (o "card" do Trello; a equipe também diz "task" e "demanda"). Termo do sistema: **tarefa** (D-PRJ-07) |
| To do / Esteira | A sprint: tarefas prontas para desenvolver (`Esteira` era o nome no Trello) |
| Teste cruzado | Teste da tarefa feito por outro dev, antes do QA |
| Pacote | Conjunto de tarefas em `Done` que vai junto para homologação e produção; o mesmo que versão |
| Homologação | Validação do pacote antes da produção |
| Chave | Identificador da tarefa no projeto: prefixo + número (`SGM-17`) |
| Critério de aceite (`CA_n`) | Item verificável do documento de requisição; vira item do roteiro de teste |
| Carga diária | Horas por dia que o dev trabalha na WCJ (ex.: 8 h ou 4 h) |
| Versão | Pacote de tarefas prontas publicado em produção, com número e data (hoje: lista "Produção 1.0.9 10/07/2026") |
| Retrabalho | Devolução de uma tarefa do QA para desenvolvimento |
| GMUD | Gestão de mudança: alteração de configuração ou ambiente em produção |
| Chamado | Solicitação de um cliente sobre um sistema ou contrato |
| SLA | Prazo de atendimento do chamado, definido pelo contrato e pela prioridade |
| Assistente / IA | Claude ou ChatGPT do próprio usuário, conectado pelo MCP |
| Conector | A autorização de um assistente para acessar o sistema em nome do usuário |

## Restrições

- Stack: template da casa (`stacks/laravel-inertia.md`). [DECIDIR confirmação no comitê]
- Pacote MCP do lado do servidor: avaliar `laravel/mcp` (oficial) na prova de conceito do R-IA-01.
- Servidor MCP em HTTPS público desde a homologação (os assistentes chamam da nuvem deles).
- Uso principal: desktop no escritório; chamados e follow-ups também pelo celular [LEVANTAR].
- Volume: [LEVANTAR] nº de usuários internos, clientes com contrato ativo, chamados/mês, leads/mês.
- Segurança: fatia de segurança obrigatória (`core/seguranca.md` seção 11) **incluindo o servidor MCP e o OAuth**.
- Prazo: [DECIDIR].

## Donos e pessoa técnica

| Parte | Dono (ouve o usuário, valida esta seção) |
|---|---|
| Núcleo | [DECIDIR] |
| Projetos | Rafael Garcia (modalidade 100% IA: Lucas Scholz) |
| CRM | Lucas Scholz |
| Chamados | Alaor Rodrigues (D-GER-04) |
| Interface de IA | [DECIDIR] |
| **Pessoa técnica que aprova o banco (etapa 2)** | **Lucas Alencar** (D-GER-01) |

## Reuniões de levantamento (planejadas)

Objetivo comum: o que as ferramentas de hoje **não fazem** e o sistema pode incluir. Pautas detalhadas em [`pautas/`](pautas/README.md). Cada reunião vira uma ata em `docs/atas/` e atualiza a seção do módulo correspondente.

| Reunião | Com | Foco | Traz para a reunião |
|---|---|---|---|
| Chamados | Alaor Rodrigues | Canal atual, tipos, prioridade, SLA por contrato, banco de horas, triagem | Lista de contratos de sustentação ativos; 3 chamados reais recentes, do pedido ao fechamento |
| Projetos | Rafael Garcia | O que o Trello não resolve; etapas; visão por cliente e por pessoa; prazos | Acesso aos quadros do Trello (export JSON) |
| Banco + CRM + IA | Lucas Alencar | Viabilidade do núcleo comum, migração do funil do DaaS, prova de conceito MCP (R-IA-01) | Esquema atual de `lead`/`lead_evento` do DaaS |

Perguntas que valem para as três:
1. O que você faz hoje fora da ferramenta (planilha, WhatsApp, caderno) porque ela não atende?
2. Qual informação você precisa e demora para achar?
3. O que você esquece de fazer e só descobre quando dá problema?
4. O que você pediria para a sua IA fazer nesse módulo, se ela tivesse acesso?
5. O que **não** pode existir, ou seja, o que atrapalharia se o sistema fizesse?

## Fontes

- 24/09/2026: conversa de concepção (Lucas Scholz), incluindo as decisões D-GER-01 a 03 e D-IA-01 a 04.
- 24/09/2026: disponibilidade de conector MCP: Claude Help Center ("Get started with custom connectors using remote MCP") e OpenAI Developers ("ChatGPT Developer mode"); divergência sobre escrita no Plus/Pro registrada em R-IA-01. Codex: OpenAI Developers ("Model Context Protocol", Codex).
- 24/09/2026: export JSON do quadro Trello "Wpay" → `levantamento/trello-wpay.md`. Demais quadros pendentes.
- 24/09/2026: reunião de Projetos com Rafael Garcia → `atas/2026-09-24-projetos-rafael.md`.
- 24/09/2026: fluxo de desenvolvimento e colunas do quadro enviados por Lucas Alencar → `levantamento/fluxo-desenvolvimento.md`.
- 24/09/2026: documentos de requisição SGM-13/17/22 e "Histórico de Desenvolvimento" Tecsolution → `levantamento/modelos-rafael.md` (originais fora do git).
- Funil de prospecção do DaaS: repositório `wcj-ui`, tabelas `lead` e `lead_evento`.
- Atas do Comitê de IA: `docs/atas/`.
