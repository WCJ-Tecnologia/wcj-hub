# Necessidade: WCJ Hub

Etapa 1 do `playbooks/aplicacao-nova.md` (padrão `ai-systems` 0.6.0). Serve de entrada para o modelo de banco (etapa 2, `docs/banco.txt`) e para o escopo (etapa 4).

**Situação:** as regras são as aprovadas por Lucas Scholz na revisão das regras de 24/09/2026 (revisão 4), reescritas aqui na Sessão C. As lacunas fechadas pela premissa nessa reescrita e a exclusão pelo padrão 0.4.0 foram confirmadas por ele no mesmo dia. Na revisão do banco (Sessão D, 24/09), ele tirou a P4 e a P6 e completou N5, P11, P25 e P42. O documento aguarda o Comitê de IA. Chamados continua em rascunho (D-GER-12). **[LEVANTAR]** marca o que depende do dono do módulo. **[DECIDIR]** marca o que depende do comitê ou de quem estiver indicado.

**06/10/2026:** o `banco.txt` foi aprovado por Lucas Alencar (D-GER-23) e Lucas Scholz aceitou a revisão de 01/10 por inteiro (R1 a R17, D-GER-24 a D-GER-27). A primeira entrega é a **fatia 1: Núcleo + Projetos + MCP com 6 ferramentas**; o CRM e o GPT do Wilson são a **v2** e têm a própria rodada de necessidade e banco antes de construir. As regras de CRM (seção 3) ficam aqui como escritas em 24/09, sem valer como requisito da fatia 1. O que cada bloco faz na tela está nos documentos funcionais (`docs/funcional/`); o lado técnico, no `docs/escopo.md`; quem faz o quê e em que ordem, no `docs/plano-de-desenvolvimento.md`.

**Porta de entrada do playbook:** "cliente só com conversa". Não existe sistema anterior com modelo de dados aproveitável (o Trello é ferramenta genérica). A ordem é: regras e fluxo primeiro; depois a IA propõe o modelo de dados; por fim a pessoa técnica valida, na etapa 2.

## Premissa (vale acima de qualquer regra de Projetos)

A gestão de projetos da v1 é um **kanban simples, como o Trello de hoje**. O time é pequeno e respeita o fluxo, então **o sistema não trava o fluxo**:

- **Administrador e gestor** mexem em tudo, a qualquer momento: criam, editam, arquivam e excluem card; põem e tiram card da sprint; montam pacote; mudam a configuração do projeto.
- **O dev** cria card no `Backlog` e executa o que está no quadro (P56, revertida em 06/10). Ele não exclui nem arquiva card, e não mexe na sprint, no pacote nem na configuração.
- Número de controle, como horas × capacidade da sprint, é **referência para o gestor, nunca impeditivo**.
- Regra nova só entra se for simples. Na dúvida, fica a opção mais aberta.

**Fixa** = vale para todo projeto. **Padrão** = vem ligada e o gestor muda por projeto.

## Decisões registradas

Esta tabela guarda só as **decisões de projeto**: quem decide, prazo, stack, hospedagem e processo. As decisões de produto viraram as regras G, N, P, C e IA (seção [Regras de negócio](#regras-de-negócio)) e valem pelo texto de lá. Os códigos antigos (RN-…, D-PRJ-…, D-CRM-…, D-NUC-… e os D-IA-… de produto) ficam no histórico do git, até o commit `da44c03`.

| # | Data | Decisão |
|---|---|---|
| D-GER-01 | 24/09/2026 | Pessoa técnica que aprova o banco (etapa 2): **Lucas Alencar**. |
| D-GER-02 | 24/09/2026 | Gestor de projetos: **Rafael Garcia**. Projetos feitos 100% com IA são geridos por **Lucas Scholz**. |
| D-GER-03 | 24/09/2026 | O funil de prospecção do DaaS passa para o CRM deste sistema. O DaaS fica só com a parte de auditoria. |
| D-GER-04 | 24/09/2026 | Dono do módulo Chamados (sustentação): **Alaor Rodrigues**. |
| D-GER-05 | 24/09/2026 | **Padrão que se ajusta:** o sistema traz um padrão pronto e cada projeto pode ser adaptado. Fluxo travado torna a ferramenta descartável. Hoje isso está na regra G1 e na premissa de Projetos. |
| D-GER-06 | 24/09/2026 | Nome do sistema: **WCJ Hub**. |
| D-GER-07 | 24/09/2026 | Donos: **Núcleo** = Lucas Scholz; **Interface de IA** = Lucas Scholz (produto) + Lucas Alencar (técnico). |
| D-GER-08 | 24/09/2026 | Prazo: o mais rápido possível. Ordem das entregas revisada em D-GER-12. |
| D-GER-09 | 24/09/2026 | Stack: template da casa (Laravel + Inertia). As regras do padrão `ai-systems`, incluindo o `core/mcp.md` (publicado na 0.3.0), valem **principalmente para sistemas 100% IA**, como o WCJ Hub. Projetos com time mais sênior têm liberdade de stack e de template. |
| D-GER-10 | 24/09/2026 | Volume: até **14 usuários internos**, **6 clientes ativos**, cerca de **10 leads por mês**. |
| D-GER-11 | 24/09/2026 | Tipos de serviço (antes chamados de tipos de contrato, N10): projeto, sustentação, auditoria, alocação de dev, infraestrutura. O Administrador cadastra mais (G7). |
| D-GER-12 | 24/09/2026 | **Prioridade: Núcleo, Projetos, CRM e, por último, Chamados.** Chamados (ITSM) fica para o fim porque ainda não há demanda. O levantamento com o Alaor não tem data. |
| D-GER-13 | 24/09/2026 | Integração com o GitHub (webhooks de PR) **fora da v1, em avaliação** por Lucas Scholz. |
| D-GER-14 | 24/09/2026 | Hospedagem: **na mesma VPS do sistema de auditoria (DaaS)**, com subdomínio próprio e HTTPS. |
| D-GER-15 | 24/09/2026 | Código **organizado por domínio desde o início** (`stacks/laravel-inertia.md`, "Quando o projeto cresce: por domínio"), sem esperar chegar a 5 módulos. Registrar em ADR na etapa 3. |
| D-GER-16 | 24/09/2026 | Etapa 2 (banco): a IA propõe o `banco.txt` a partir deste documento, Lucas Alencar revisa e aprova, e a validação final é no Comitê. Trilha de auditoria em **tabela única** para todos os módulos. O rascunho do `core/mcp.md` para o `ai-systems` é feito pela IA e revisado por Lucas Alencar (publicado na 0.3.0). |
| D-GER-17 | 24/09/2026 | **Execução:** Lucas Scholz e Cauã, com Claude. O WCJ Hub é um projeto **100% IA** (D-GER-02, D-GER-09): o padrão `ai-systems` vale por inteiro. Lucas Scholz faz também o **teste técnico da IA**. |
| D-GER-18 | 24/09/2026 | Validações técnicas aprovadas por Lucas Scholz, revisáveis se precisar: estrutura por domínio (D-GER-15), limite de 60/20 por minuto e `laravel/mcp` + Passport (D-IA-07). |
| D-GER-19 | 24/09/2026 | Capacidade da VPS e separação do banco do Hub: definir com **Alaor Rodrigues** na hora de subir o ambiente de teste. |
| D-GER-20 | 24/09/2026 | **Banco enxuto:** toda tabela e coluna do `banco.txt` existe porque uma regra deste documento lê ou grava o dado. Campo sem regra não entra; volta quando alguém pedir, com o motivo (G5). Quem fez e quando fica na auditoria (D-GER-16), não em colunas `*_by`. |
| D-GER-21 | 24/09/2026 | Banco de dados: **MySQL 8**, o mesmo do template da casa. |
| D-GER-22 | 24/09/2026 | **Testes também em MySQL 8**, local e no CI; nada de SQLite, embora o template use SQLite nos testes. O teste roda o mesmo banco da produção (T6 do `banco.txt`). |
| D-GER-23 | 06/10/2026 | **`banco.txt` aprovado por Lucas Alencar** (41 tabelas, D-GER-01). Os cortes da fatia 1 (D-GER-27) foram aplicados depois da aprovação; ele confere só a diferença, listada na seção 7 do `banco.txt`. |
| D-GER-24 | 06/10/2026 | **v1 fatiada** (R1). Fatia 1 = Núcleo mínimo (usuários, papéis, clientes, contatos), Projetos inteiro e o servidor MCP com 6 ferramentas (R12). CRM e GPT "WCJ Hub" = **v2**, com rodada própria de necessidade e banco. O funil do DaaS continua no ar até lá: a D-GER-03 muda só no prazo. |
| D-GER-25 | 06/10/2026 | **Execução:** Cauã e Vitinho desenvolvem, com IA (por inteiro ou em parte); Lucas Scholz supervisiona, aprova funcional, spec e PR, e faz o teste técnico do MCP. Substitui a execução da D-GER-17. O padrão `ai-systems` (0.6.0, skill `tlc-spec-driven`) continua valendo, no **trilho leve** (R6): trilho completo só em autenticação e papéis, Policies, OAuth e MCP, auditoria, desativação e exclusão; o resto com spec no PR, teste e uso no navegador. |
| D-GER-26 | 06/10/2026 | Regras de uso revertidas (R2 a R5): qualquer pessoa alocada cria card no `Backlog` (P56); e-mail ao definir dev ou QA e ao devolver para desenvolvimento (P22); o card nasce só com título (P17); arrastar do `Backlog` para o `To do` põe na sprint aberta, sem cerimônia, e o objetivo da sprint é opcional (P45). |
| D-GER-27 | 06/10/2026 | Cortes da fatia 1 no banco (R7 a R11, R13 a R15): saem CRM, `service_types`, `task_validations` e as flags `requires_*`, `sprint_members`, `package_statuses` e `ai_tool_calls`; motivos de retrabalho viram lista geral; o movimento do card guarda quem moveu e o canal; a integridade por projeto é conferida no Service, não por FK composta (Lucas Alencar confere). 41 → 27 tabelas. Indicadores: o atraso desconta a pausa e o realizado tem fallback no tempo em desenvolvimento (R16, R17). |
| D-GER-29 | 06/10/2026 | **Lucas Varanda** (front-end) adapta o template à marca WCJ e desenha os componentes que o Hub pede e o template não tem. A marca entra no Hub inteira, só por `docs/brand/` e `design/tokens.json`; no mestre da marca (`wcj-master-brand-brain/marca/`) é que se muda. |
| D-GER-28 | 06/10/2026 | **Documento funcional por bloco** (`docs/funcional/`), no modelo dos funcionais da PTA: aprovado pelo dono do módulo (Projetos: Rafael Garcia; Núcleo e IA: Lucas Scholz) antes da spec. O `docs/escopo.md` aponta para eles, e a spec de cada bloco nasce do funcional aprovado. |
| D-IA-04 | 24/09/2026 | Todos os usuários internos têm plano **Pro ou superior** (Claude e/ou ChatGPT). Nenhum usa plano gratuito. |
| D-IA-07 | 24/09/2026 | Limite por conexão: leitura 60/min, escrita 20/min, ajustáveis (IA7). Servidor MCP com `laravel/mcp`; login da IA por OAuth 2.1 com Passport. |
| D-CRM-04 | 24/09/2026 | Quando o contrato entrar (fora da v1, N10), o Hub é a **única fonte de contratos** e o DaaS consulta o Hub. Oportunidade do roadmap da auditoria é cadastrada à mão, também depois da v1. O funil sai do DaaS quando o CRM do Hub estiver no ar. |
| D-CHM-01 | 24/09/2026 | **[DECIDIR]** Chamados: construir no Hub, adotar o GLPI (sugestão do Alaor) ou fazer em duas fases. Ver [Chamados](#chamados-rascunho). |
| D-CHM-02 | 24/09/2026 | Canal inicial de chamado: **e-mail** (endereço próprio). Triagem por uma função de **despachante**, com filas N1/N2/N3. |
| D-CHM-03 | 24/09/2026 | SLA e prioridade **contrato a contrato**; a prioridade é definida pela WCJ na triagem. No início, chamados só no nível de projeto. |
| D-CHM-04 | 24/09/2026 | **Banco de horas**: contrato com horas contratadas; o excedente é contabilizado como hora extra. Chamado resolvido fecha sozinho sem resposta do cliente. |

---

## Para que serve

Um sistema único para a operação interna da WCJ, com três módulos sobre uma base comum:

| Módulo | Substitui | Problema de hoje |
|---|---|---|
| **Projetos** | Trello (cerca de R$ 500,00 por mês, segundo a ata de 24/09) | No quadro Wpay, o retrabalho do QA fica invisível (2 em 3 entregas voltam), as versões são controladas em listas criadas à mão, o prazo quase não é usado e chamado se mistura com projeto. O Rafael não tem tempo por etapa nem previsto × realizado de horas, e monta à mão a cronologia de entregas para o cliente. |
| **CRM** | Funil de prospecção do DaaS (D-GER-03) + [LEVANTAR] planilha / WhatsApp | Lead e tratativa não ficam registrados num lugar só; o follow-up depende de alguém lembrar. |
| **Chamados** (último, D-GER-12) | Nada formal; GLPI em avaliação (D-CHM-01) | O cliente de sustentação e o de projeto em andamento não têm canal formal. Não se mede prazo de atendimento nem existe histórico por cliente. |

**Base comum (Núcleo):** usuários, papéis, clientes e contatos (a lista de tipos de serviço entra com o CRM, N10). É o que liga os módulos: o lead ganho no CRM vira cliente, e o cliente tem projetos. O contrato fica fora da v1 (N10).

**Princípio de desenho:** padrão pronto, ajustável por projeto (G1), e em Projetos um kanban sem trava (premissa).

**Interface de IA:** cada usuário interno conecta o próprio assistente (Claude, Codex ou ChatGPT) ao sistema para consultar e alterar dados dos módulos, com as mesmas permissões da tela (IA1–IA14).

## Quem usa

| Papel | Quem é | O que faz no sistema | O que não pode ver ou fazer |
|---|---|---|---|
| Administrador | Sócios, inclusive o Wilson | Tudo | — |
| Gestor de projetos | Rafael Garcia; Lucas Scholz nos projetos 100% IA | Tudo nos projetos que gere; cria cliente ao criar projeto e edita os clientes dos projetos dele (N6) | Pipeline comercial e valores de proposta (C14) |
| Desenvolvedor | Devs PJ | Nos projetos em que está alocado: cria card no `Backlog`, move, edita, comenta, anexa, lança horas e faz teste cruzado | CRM; painel de produtividade (P54). Não exclui nem arquiva card e não mexe em sprint, pacote nem configuração (P26, P45, premissa) |
| Comercial | Ninguém no início | Vê todos os leads, edita os próprios e converte lead em cliente | Tarefas internas de projeto |

- O papel Comercial fica pronto para quando houver comercial. Hoje o Wilson é sócio e entra como Administrador.
- **Tech Lead** não é papel nem fica no sistema: a equipe combina no dia a dia, e o gestor acompanha (P6 saiu).
- **Suporte** e **contato do cliente** entram com o módulo Chamados.
- O que o papel não vê na tela, a IA dele também não vê nem altera (G4).

## Fluxo principal

### Núcleo

1. O Administrador convida os usuários, atribui o papel e define a carga diária (N1–N3).
2. O cliente nasce de duas formas: cadastro direto pelo Administrador ou lead ganho no CRM (C10). Nos dois casos, nasce sem contrato (N6).
3. Os contatos são cadastrados pelo Administrador, pelo gestor (nos clientes dos projetos dele) ou, na v2, pelo CRM, junto com o lead (N8). O contato não tem login na v1 (N9).
4. O Administrador desativa e reativa usuário, cliente e contato, e anonimiza o contato a pedido do titular (N11, N12).

### Projetos

Base: quadro Trello "Wpay" ([`levantamento/trello-wpay.md`](levantamento/trello-wpay.md)), reunião com Rafael Garcia em 24/09/2026 ([ata](atas/2026-09-24-projetos-rafael.md)), modelos enviados por ele ([`levantamento/modelos-rafael.md`](levantamento/modelos-rafael.md)) e fluxo de desenvolvimento do Lucas Alencar ([`levantamento/fluxo-desenvolvimento.md`](levantamento/fluxo-desenvolvimento.md)). Escopo da v1: as funcionalidades do Trello mais o controle do tempo, num kanban sem trava (premissa).

1. O gestor cria o projeto: cliente, prefixo, tipo (projeto de entrega ou produto contínuo) e datas. Aloca as pessoas (P1–P5). O quadro nasce com as etapas padrão (P10).
2. Qualquer pessoa alocada cria o card: título e Enter bastam (P17, P56). O gestor, ou a IA dele, escreve os cards de projeto de cliente no modelo de requisição, com os critérios de aceite. Pode já pôr horas, dev e QA; nada disso é obrigatório (P20, P27). O card nasce no `Backlog`.
3. O gestor arrasta os cards do `Backlog` para o `To do`: isso os põe na sprint aberta; sem sprint aberta, o sistema propõe criar uma, já preenchida, e segue (P45, P46). Com o diretor de tecnologia, define ou valida as horas (P27). A capacidade por dev, carga diária × dias úteis da sprint, aparece como referência (P48, P49).
4. O dev leva o card para `Doing`, e o sistema calcula a data prevista (P28). O dev trabalha na branch, cola os PRs no card (P23) e passa para `Code-review`, onde na prática o Tech Lead aprova (P33).
5. Em `In test`, primeiro o teste cruzado, feito por qualquer dev alocado menos o dev da tarefa, que marca na tarefa que testou; depois o QA, que executa o roteiro de critérios e anexa a evidência em cada item (P34–P36). Quem trabalhou lança as horas (P30).
6. Aprovada no QA, a tarefa recebe o merge, normalmente do Tech Lead, e vai para `Done` (P38). Reprovada em qualquer validação, volta para desenvolvimento com origem e motivo (P39).
7. Se o projeto usa pacote, o gestor junta as tarefas prontas numa versão, que passa por homologação e produção (P41–P44).
8. O gestor encerra a sprint. O que não terminou vai para a próxima sprint, criada nesse momento, ou volta ao `Backlog` (P50).
9. Quando o cliente aciona a WCJ direto, o gestor registra um card de suporte na coluna `Support` (P52).
10. O gestor acompanha tempo por etapa, horas previstas × realizadas e atraso, por projeto e por pessoa (P53).

### CRM (v2, fora da fatia 1)

1. O comercial, ou o Administrador, cadastra o lead: empresa, contatos, origem, tipo de serviço, valor e dono. O sistema avisa se o CNPJ já é cliente ou tem lead aberto (C2, C3).
2. O lead anda livre pelas etapas do funil (C1).
3. Cada conversa vira uma tratativa, e a situação atual do lead é atualizada junto (C5, C6).
4. O próximo passo vira um compromisso, follow-up ou reunião. Todo lead ativo tem um follow-up futuro (C7, C8).
5. O responsável recebe o convite no calendário, o resumo do dia por e-mail e a lista no Hub (C9).
6. O lead é ganho e vira cliente sem contrato (C10, C12, C13) ou é perdido, com motivo (C11).

#### Uso pelo comercial com IA

O Wilson usa o ChatGPT e gosta de mandar **áudio**. Ele conversa com o **GPT personalizado "WCJ Hub"** (IA3), e o GPT faz no Hub:

| O Wilson diz (exemplo) | O GPT faz |
|---|---|
| "Falei com a Fulana da Empresa X, ela pediu a proposta até sexta" | `registrar_tratativa` + atualiza a situação atual + `agendar_compromisso` (follow-up na sexta) |
| "Passa a Empresa X para Proposta" | `mover_lead` |
| "Marca reunião com a Empresa Y terça às 10h" | `agendar_compromisso` (reunião); o convite chega no calendário dele |
| "O que eu tenho hoje?" / "Quem eu preciso cobrar?" | `minha_agenda` e `meus_followups` |
| "Como está a conversa com a Empresa X?" | `historico_do_lead` (com a situação atual no topo) |

- **Áudio:** o ChatGPT transcreve a fala do próprio usuário (ditado no campo de mensagem, no celular e no computador). O Hub recebe só o texto e não precisa de IA embutida (IA2).
- **O lembrete não vem pelo chat:** o ChatGPT não inicia conversa sozinho. O lembrete chega pelo calendário e pelo e-mail (C9), e o Wilson pergunta a agenda ao GPT quando quiser.

### Chamados (rascunho)

**Último módulo (D-GER-12):** ainda não há volume de chamados. Vira necessidade quando entrarem contratos de **sustentação de infraestrutura**. Base: reunião com Alaor Rodrigues em 24/09/2026 ([ata](atas/2026-09-24-chamados-alaor.md)). Na v1, o que existe é o card de suporte do quadro (P52).

#### Construir no Hub ou adotar o GLPI (D-CHM-01, [DECIDIR])

O Alaor recomenda o **GLPI**, ferramenta de ITSM open source, sem licença, hospedada pela própria WCJ. Ela já traz o que dá mais trabalho num módulo de chamados: entrada por e-mail, filas N1/N2/N3, catálogo de serviços, SLA, regras de distribuição e inventário de máquinas.

| | A. Construir no Hub | B. GLPI integrado ao Hub | C. Em duas fases (recomendada) |
|---|---|---|---|
| O que é | Módulo próprio, como Projetos e CRM | GLPI hospedado pela WCJ; Hub e GLPI conversam pela API do GLPI | **Agora (v1 de Projetos):** o gestor registra à mão o card de suporte na coluna `Support` do quadro (P52). **Depois:** entrada por e-mail. **Quando entrar sustentação de infra:** avaliar o GLPI para o ITSM completo |
| A favor | Tudo num lugar só: mesmo login, mesma auditoria, IA pelo mesmo MCP, chamado vira tarefa sem integração | ITSM completo, pronto e maduro; inventário; comunidade grande; o Alaor já conhece | Resolve o pedido do Rafael (coluna de suporte no quadro) já na v1, sem construir ITSM antes de ter demanda; decide o GLPI com demanda real |
| Contra | ITSM é trabalhoso (filas, SLA, regras, e-mail); seria construído antes de ter volume | Mais um sistema para hospedar, atualizar e proteger; clientes e contratos duplicados (sincronizar do Núcleo); a IA do Hub não enxerga o GLPI sem integração própria; cliente com dois portais | Se o GLPI for adotado depois, o chamado de projeto do Hub precisa conversar com ele |

Pontos de atenção se o GLPI entrar:
- **Segurança:** servidor exposto à internet, versão atualizada, backup e HTTPS, pelo mesmo checklist do padrão (`core/seguranca.md`, `core/deploy.md`).
- **Fonte única de cliente e contrato:** o Núcleo do Hub. O GLPI recebe as entidades por sincronização e nunca é cadastrado à mão em paralelo (`core/banco.md` seção 9).
- **IA:** as ferramentas MCP de Chamados passariam a chamar a API do GLPI, com as mesmas regras IA.

#### Fluxo proposto (vale para a opção A ou para a primeira fase da C)

1. O cliente manda **e-mail** para o endereço de suporte (D-CHM-02; `ticket@` ou `suporte@wcjtecnologia.com.br`, a definir). O e-mail vira chamado, ligado ao cliente pelo endereço do remetente.
2. O **despachante** faz a triagem: confirma cliente, contrato e projeto, define **tipo, área e prioridade** (a prioridade é definida pela WCJ, não pelo cliente) e coloca o chamado na **fila** certa.
3. O **N1** tenta resolver. Se não resolve, escala para **N2 / N3**. Chamado de desenvolvimento ligado a projeto cai como tarefa na coluna `Support` do quadro do projeto (P52).
4. O responsável atende, registra as interações e **aponta as horas**.
5. Chamado resolvido: o cliente confirma, ou o chamado **fecha sozinho** se o cliente não responder no prazo.

## Regras de negócio

Numeradas por módulo: G (todos), N (Núcleo), P (Projetos), C (CRM), IA (Interface de IA). Em Projetos, a premissa vale acima de todas.

### 0. Vale para todos os módulos

- **G1** O sistema traz um padrão pronto que o gestor ajusta por projeto. Só é fixo o que protege segurança e auditoria.
- **G2** Usuário, cliente, contato e lead já usados não são excluídos: são desativados, com quem e quando, e podem ser reativados. Card de projeto já usado é arquivado (P26). Só se exclui de verdade o que nunca foi usado, e só quem tem permissão exclui. Tudo fica na auditoria.
- **G3** Toda alteração, em qualquer módulo, fica na auditoria: quem, quando, o antes e o depois, e por onde (tela ou qual IA). Tentativa negada também fica. A auditoria nunca é apagada. A tela geral da auditoria é só do Administrador (decisão de 06/10); os demais veem o histórico de cada registro pela ficha.
- **G4** O que o papel não vê na tela, a IA dele também não vê nem altera.
- **G5** Dado sem regra que o use não entra no sistema. Volta quando alguém pedir, com o motivo.
- **G6** Na v1 não existem: aprovação em dois níveis, ranking ou metas, mensagem ao cliente por WhatsApp e aplicativo nativo (a v1 é web responsiva).
- **G7** Listas gerais (tipos de tarefa, motivos de retrabalho e, na v2, tipos de serviço, funil, origens, motivos de perda e tipos de compromisso): quem edita é o Administrador. Listas do projeto (etapas, áreas): quem edita é o gestor do projeto.

### 1. Núcleo

- **N1** O usuário é convidado pelo Administrador, por **e-mail**, com senha provisória trocada no primeiro acesso (decisão de 06/10: o convite vai por e-mail, não é mostrado na tela).
- **N2** Cada usuário tem um papel, entre os 4 fixos, atribuído pelo Administrador. Não se cria papel novo.
- **N3** Carga diária (ex.: 8 h ou 4 h): obrigatória para o Desenvolvedor, opcional para os outros papéis, definida pelo Administrador. Sem carga, a tarefa do dev fica sem data prevista e sem capacidade. Mudar a carga vale dali para a frente.
- **N4** Usuário desativado perde o acesso na hora, inclusive as conexões de IA.
- **N5** Cliente brasileiro é identificado pelo CNPJ, numérico ou alfanumérico (o CNPJ com letras vale desde julho de 2026), e CNPJ repetido é recusado. Empresa estrangeira: país + identificador fiscal do país, opcional. Pessoa física não é cliente.
- **N6** O cliente nasce por cadastro direto do Administrador, pelo **gestor ao criar um projeto** (botão *Novo cliente* no formulário do projeto, decisão de 06/10) ou, na v2, por lead ganho; sempre sem contrato. O gestor edita os clientes dos projetos dele; desativar e excluir é do Administrador.
- **N7** A própria WCJ é um cliente: é a dona dos projetos internos e dos produtos (ex.: Wpay). Já vem semeada na instalação, com o CNPJ 38.614.817/0001-22, e não se desativa.
- **N8** O contato é um cadastro só, seja pessoa de cliente ou de lead: nome, e-mail e telefone. Quando o lead vira cliente, o contato não é redigitado. O sistema avisa quando já existe contato com o mesmo e-mail, sem recusar. O gestor também cadastra contato dos clientes dos projetos dele. O contato é **opcional**: nenhum cadastro o exige; ele existe para a equipe lembrar com quem fala na empresa (decisão de 06/10). Veem contatos o Administrador e o gestor, nos clientes dos projetos dele; o dev vê só o nome em "quem pediu" no card de suporte (P52).
- **N9** O contato não tem login na v1.
- **N10** O contrato fica fora da v1. A lista de **tipos de serviço** (projeto, sustentação, auditoria, alocação de dev, infraestrutura), que o lead usa para dizer o que está sendo vendido, entra com o CRM, na v2: nada da fatia 1 a lê (R7).
- **N11** O Administrador desativa e reativa usuário, cliente e contato. Ter algo aberto não impede.
- **N12** LGPD: a pedido do titular, o Administrador anonimiza o contato. Nome, e-mail e telefone viram "removido a pedido do titular", e o histórico continua, sem os dados pessoais.

### 2. Projetos

#### 2.1 Projeto

- **P1** Todo projeto pertence a um cliente e tem um gestor responsável. O cliente é definido na criação e não muda: sem cliente não há projeto (decisão de 06/10). Pode haver mais de um gestor por projeto: além do responsável, toda pessoa com papel Gestor de projetos alocada no projeto gere o projeto como ele (decisão de 06/10).
- **P2** Dois tipos de projeto:
  - **projeto de entrega**: tem data de início e data de entrega. A data de entrega é informação para o gestor, que acompanha o prazo por conta própria na v1;
  - **produto contínuo** (ex.: Wpay): só tem data de início e fica em melhoria contínua. O prazo é o de cada sprint.
- **P3** Cada projeto tem um prefixo (ex.: `SGM`), único no sistema, que não muda depois da primeira tarefa.
- ~~**P4**~~ Saiu na revisão do banco (Sessão D): a modalidade (tradicional ou 100% IA) não fica no sistema; a equipe combina e o gestor acompanha.
- **P5** O dev vê só os projetos em que está alocado; o gestor, os que gere e os em que está alocado; o Administrador, todos. Gestor alocado num projeto tem ali a visão e os poderes de gestor (P1) e também pode ser dev ou QA de tarefa: alocado quer dizer que vai executar. O gestor põe e tira pessoas do projeto a qualquer momento. As tarefas de quem saiu ficam sem dev e, se a pessoa era o QA, sem QA.
- ~~**P6**~~ Saiu na revisão do banco (Sessão D): o Tech Lead não fica no sistema; a equipe define no dia a dia e o gestor acompanha. P33 e P38 descrevem a prática, sem registro.
- **P7** O gestor encerra o projeto quando quiser, mesmo com tarefa aberta. Projeto encerrado não recebe tarefa nova; o gestor ou o Administrador reabre.
- **P8** Mudar a configuração do projeto vale dali para a frente e nunca apaga o que já foi registrado.
- ~~**P9**~~ Saiu: o prazo do projeto pelo sistema fica fora da v1.

#### 2.2 Quadro e etapas

- **P10** Cada projeto tem o próprio quadro. Padrão: `Backlog`, `To do`, `Doing`, `Paused`, `Code-review`, `In test`, `Done` e `Support`, em inglês. **Padrão.**
- **P11** O gestor renomeia, cria, remove e reordena etapas. Toda etapa tem um papel (fila, sprint, desenvolvimento, code review, teste, pronto, pausa ou suporte); os indicadores leem o papel, não o nome. As etapas de fila (`Backlog`), sprint (`To do`) e suporte (`Support`) existem sempre, uma de cada, porque o sistema leva o card para elas sozinho: o gestor renomeia e reordena, mas não remove nem duplica. Também existe sempre pelo menos uma etapa de desenvolvimento (`Doing`), para onde a reprovação na homologação devolve o card (P43).
- **P12** Para remover uma etapa que tem tarefas, é preciso dizer para onde elas vão.
- **P13** Qualquer pessoa alocada move o card entre as etapas, a partir do `To do`. Pôr e tirar da sprint é do gestor e do Administrador (P45).
- **P14** Toda mudança de etapa grava quem, quando, de onde e para onde. **Fixa.**
- **P15** Ir para `Paused` pede motivo, e o tempo parado conta à parte. **Padrão** (o gestor pode desligar o motivo).

#### 2.3 Tarefa

- **P16** Chave `<prefixo>-<número>` (ex.: `SGM-17`), sequencial por projeto, nunca reaproveitada.
- **P17** Criar o card pede só o título: ele nasce no `Backlog` com tipo Melhoria e prioridade Normal, e o resto fica recolhido e opcional (R4). A tarefa de projeto de cliente é escrita, quando houver, no modelo de requisição: Sendo / Quero / Para + critérios de aceite `CA_1…CA_n`.
- **P18** Tipo (melhoria, correção de bug, suporte, mudança/GMUD; lista geral editável, G7) e área (lista do projeto, que nasce vazia). Melhoria e Suporte se renomeiam, mas não se desativam: o sistema depende deles (P17, P52).
- **P19** Prioridade: baixa, normal, alta ou urgente.
- **P20** A tarefa tem **um dev** e **um QA**. Os dois podem ficar vazios, como as horas (P27).
- ~~**P21**~~ Saiu: a tarefa sem dev pode entrar na sprint (premissa).
- **P22** Quem é definido como dev ou QA da tarefa recebe aviso **no sistema e por e-mail**; quando a tarefa volta para desenvolvimento (P39), o dev dela também recebe os dois (R3). São os únicos avisos de Projetos na v1.
- **P23** A tarefa aceita nenhum, um ou vários PRs, colados como link.
- **P24** A tarefa tem anexos e comentários.
- **P25** Bug achado em produção vira tarefa nova, ligada à original, do mesmo projeto. Conta como indicador de qualidade do projeto, não como retrabalho do dev.
- **P26** O gestor e o Administrador tiram qualquer card do quadro, a qualquer momento:
  - o card **nunca usado** (não saiu do `Backlog` e não tem horas, comentários, anexos nem PRs) pode ser **excluído** de verdade;
  - o card com histórico é **arquivado**, com motivo. Sai do quadro e das contas de prazo, atraso e capacidade; as horas lançadas nele continuam nos indicadores de horas. Desarquivar devolve ao `Backlog`.

  O dev não exclui nem arquiva. Tudo fica na auditoria (G3).
- **P56** Qualquer pessoa alocada no projeto cria card, que nasce no `Backlog` (revertida em 06/10, R2). Pôr na sprint (P45), excluir e arquivar (P26) continuam só do gestor e do Administrador. Vale também pela IA (G4).

#### 2.4 Horas e prazo

- **P27** Horas previstas: **um total só, Dev + QA**, opcionais ao criar. Na montagem da sprint, o gestor, com o diretor de tecnologia, define ou valida as horas. Tarefa sem horas pode entrar na sprint: só fica sem data prevista. Reestimar fica no histórico.
- **P28** A **data prevista** é calculada quando a tarefa entra em `Doing` pela primeira vez: horas previstas ÷ carga diária do dev, em dias úteis de segunda a sexta. Só muda se o gestor reestimar; nem retrabalho nem pausa empurram a data. Sem dev ou sem horas, a tarefa fica sem data prevista até ter os dois, e a conta parte da primeira entrada em `Doing`.
  Exemplo: 16 h com um dev de 8 h/dia = 2 dias úteis; com um dev de 4 h/dia = 4 dias úteis.
- **P29** Feriados não contam na v1.
- **P30** Horas realizadas: quem trabalhou lança à mão, por dia, em horas decimais, e corrige as próprias. O gestor e o Administrador corrigem qualquer uma. Não há cronômetro.
- **P31** As horas do projeto não são comparadas com horas de contrato.

#### 2.5 Validações: code review, teste cruzado, QA

- **P32** Code review, teste cruzado e QA são a prática de toda tarefa (P33 a P36); o sistema não tem flag para ligar ou desligar nem parecer por validação (R8). O que fica registrado: o PR (P23), quem fez o teste cruzado e quando (P34) e o roteiro por critério (P36).
- **P33** Code review: na prática, quem aprova é o Tech Lead do projeto; o sistema não trava.
- **P34** Teste cruzado: qualquer dev alocado faz, menos o dev da tarefa, e marca na tarefa que testou (quem e quando). A volta para desenvolvimento (P39) limpa a marca.
- **P35** Em `In test`, a ordem normal é teste cruzado e depois QA.
- **P36** O QA testa no próprio ambiente local. Executa o roteiro, em que cada critério de aceite é um item: marca aprovado ou reprovado e anexa a evidência no item.
- **P37** Critério editado depois de testado volta para "a testar". Depois de um retrabalho, o roteiro inteiro volta para "a testar".
- **P38** Aprovada no QA, a tarefa recebe o merge (normalmente pelo Tech Lead) e vai para `Done`.

#### 2.6 Retrabalho

- **P39** Toda volta para desenvolvimento conta um ciclo de retrabalho, com **origem** (code review, teste cruzado, QA ou homologação) e **motivo**.
- **P40** Os motivos são uma lista geral, editada pelo Administrador (G7, R11), sempre com "outro" com texto. Nasce com: ajuste visual, regra de negócio, erro/bug, requisito incompleto ou mal documentado, outro.

#### 2.7 Pacote (versão)

- **P41** O pacote é a versão (ex.: `1.0.9`) e é **opcional**. O gestor e o Administrador juntam tarefas no pacote, em geral as prontas, sem exigência de etapa, e marcam o estado dele (P42).
- **P42** O estado do pacote se deduz de duas datas: início da homologação e entrada em produção (P44). Sem as duas, está em montagem; com a primeira, em homologação; com a segunda, em produção. Não há estados cadastráveis (R10).
- **P43** A homologação é da equipe WCJ, testando tudo junto; o cliente não participa. Reprovação devolve as tarefas afetadas com retrabalho de origem homologação. A reprovação não tira a tarefa do pacote sozinha; o gestor tira, se quiser.
- **P44** A data planejada de liberação é definida por projeto, em geral pelo cliente. A data da versão é quando ela entra em produção.

Como o QA testa no ambiente local (P36) e as tarefas só se encontram depois do merge, a homologação do pacote é o primeiro teste integrado.

#### 2.8 Sprint

- **P45** A sprint é **obrigatória**: é por ela que o card sai do `Backlog`. Arrastar o card do `Backlog` para o `To do` é pô-lo na sprint aberta; sem sprint aberta, o sistema propõe criar uma, já preenchida (nome "Sprint N", início hoje, fim pela duração padrão), e segue (R5). O objetivo da sprint é opcional. Só o gestor e o Administrador põem ou tiram card da sprint; o dev não. Tirar devolve ao `Backlog`. **Uma sprint em andamento por projeto**: é a sprint aberta, ainda não encerrada.
- **P46** Duração padrão de 10 dias úteis (2 semanas), ajustável por projeto. **Padrão.**
- ~~**P47**~~ Saiu (R9): a sprint não tem time cadastrado. O time é quem tem tarefa nela.
- **P48** Capacidade de cada dev na sprint = carga diária (N3) × dias úteis da sprint, de segunda a sexta entre o início e o fim. Férias e folga ficam fora da v1 (R9).
- **P49** O sistema mostra, por dev, as horas que faltam das tarefas dele na sprint (previstas − já lançadas) contra a capacidade dele. É **só referência** para o gestor: nunca impede nada. Tarefa sem dev ou sem horas aparece à parte. A capacidade do QA não entra na conta na v1.
- **P50** O gestor encerra a sprint. O que não terminou vai para a próxima sprint, criada nesse momento, ou volta ao `Backlog`, como o gestor decidir; fica o histórico.
- **P51** A sprint é a "Fase" do Histórico de Desenvolvimento entregue ao cliente.

#### 2.9 Suporte

- **P52** Enquanto o módulo Chamados não existe, quando o cliente aciona a WCJ direto (WhatsApp, telefone, e-mail, reunião ou outro), o gestor registra um **card de suporte** na coluna `Support`, com quem pediu (contato), por onde e quando o pedido chegou. O card segue o mesmo quadro e a mesma prática de code review, teste cruzado e QA (P32). Ele nasce fora da sprint, e dali qualquer pessoa alocada o move, como a partir do `To do`. Se quiser, o gestor põe o card na sprint em andamento; isso só liga o card à sprint, sem trocar de coluna. Tirar da sprint ou encerrá-la também só desliga o card de suporte: ele fica na coluna em que está, sem ir ao `Backlog` (exceção à P45 e à P50).

#### 2.10 Indicadores

- **P53** Na v1: tempo por etapa, horas previstas × realizadas, data prevista e atraso das tarefas, somados por projeto e por pessoa. Tarefa atrasada = passou da data prevista sem chegar em `Done`, descontado o tempo em etapa de pausa, que é medido à parte (P15, R16). Ao lado das horas lançadas (P30), o realizado mostra também o tempo em etapa de desenvolvimento calculado dos movimentos, em dias úteis × carga do dev, para quando o dev esquece de lançar (R17).
- **P54** Depois da v1, com os dados já gravados desde a v1: painel de produtividade por dev e indicador de retrabalho. Só o gestor e o Administrador veem. Os dados de retrabalho ficam gravados desde a fatia 1; a ferramenta `retrabalho_do_projeto` entra depois dela (IA14).
- **P55** Depois da v1: histórico de desenvolvimento em PDF, relatório de fechamento, notas de versão e geração do documento de requisição. A ferramenta `notas_da_versao`, que lista as tarefas do pacote para quem for redigir as notas, entra depois da fatia 1 (IA14); até lá, a lista está na ficha do pacote.

### 3. CRM (v2: fora da fatia 1)

As regras abaixo são as de 24/09. Valem como ponto de partida da v2 e serão revisadas na rodada própria de necessidade e banco do CRM (D-GER-24). Nada daqui entra na fatia 1; o funil do DaaS continua no ar até lá.

- **C1** Um funil só. As etapas padrão são as do DaaS: Prospecção → Contato → Reunião → Proposta → Ganhou / Perdido. São editáveis, mas sempre existe uma etapa de ganho e uma de perda. O lead anda livre entre as etapas.
- **C2** O lead tem: empresa (nome, país, CNPJ ou identificador), contato(s), origem, tipo de serviço, **valor** e dono. O valor é obrigatório para ganhar. Não tem previsão de fechamento. Origens: lista editável que nasce com Indicação, Tráfego e Site.
- **C3** O mesmo CNPJ pode ter vários leads. Ao cadastrar, o sistema **avisa** se já existe cliente ou lead aberto com esse CNPJ e mostra o dono; não recusa.
- **C4** Quem tem papel Comercial vê todos os leads e edita só os próprios. O Administrador edita todos.
- **C5** Tratativa é cada conversa com o lead: data, canal, resumo, próximo passo e, opcional, com quem falou (um contato do lead). Pode ser editada a qualquer momento, e toda edição fica no histórico.
- **C6** Situação atual do lead: texto curto, sempre o mais recente, atualizado a cada tratativa. As versões antigas ficam no histórico.
- **C7** Compromisso: follow-up ou reunião (lista editável), data e hora, duração (na reunião), responsável, observação, local ou link. É sempre ligado a um lead ou a um cliente. É criado, remarcado ou cancelado pela tela ou pela IA.
- **C8** Todo lead ativo tem um follow-up futuro; sem isso, aparece em alerta para o dono. O follow-up sai de "vencido" quando é marcado como feito ou quando se registra uma tratativa no lead.
- **C9** Lembretes, sem IA embutida:
  - ao criar, remarcar ou cancelar um compromisso, o responsável recebe um convite de calendário por e-mail. Os participantes, ele mesmo chama pelo Teams;
  - todo dia de manhã, um e-mail de resumo com os compromissos do dia e os follow-ups vencidos;
  - no Hub, a lista do dia e dos vencidos.
- **C10** Lead ganho vira cliente sem redigitar nada; se o CNPJ já é cliente, só vincula. O cliente nasce sem contrato.
- **C11** Lead perdido exige motivo de uma lista editável: preço, momento, concorrente, sem resposta, sem perfil, outro (com texto). Lead perdido pode voltar ao funil.
- **C12** Lead de auditoria só é ganho com o sizing preenchido: setor (texto) e as 5 perguntas do DaaS. O preço da auditoria é o valor do lead (C2).
- **C13** Ganhar um lead de auditoria não cria nada no DaaS; a auditoria é aberta lá à mão.
- **C14** O gestor de projetos não vê o pipeline comercial; o dev não vê o CRM. **Fixa.**
- **C15** O CRM começa do zero, sem migração do DaaS.
- **C16** O Administrador desativa o lead (o "excluir" da tela), com quem e quando, e pode reativá-lo. Lead desativado sai das listas, da agenda e dos lembretes. O Administrador também troca o dono do lead. Tudo fica na auditoria.

### 4. Interface de IA

- **IA1** Só usuários internos conectam IA na v1.
- **IA2** O sistema não chama IA por conta própria: toda IA é a do usuário, paga por ele.
- **IA3** Quem usa o quê:
  - devs: Claude (app ou Claude Code) ou Codex, lendo e escrevendo;
  - ChatGPT Pro: só lê;
  - ChatGPT Plus: não conecta;
  - comercial (hoje o Wilson): pelo GPT "WCJ Hub" no ChatGPT, com áudio, entregue junto com o CRM (v2).
- **IA4** A conexão é só por login (OAuth), nunca por chave colada. Cada usuário tem uma autorização por assistente, com permissão por módulo (ler ou escrever).
- **IA5** A IA age como o usuário: tem as mesmas permissões da tela, e a permissão da conexão nunca passa do papel. **Fixa.**
- **IA6** O usuário vê e revoga as próprias conexões; o Administrador, as de todos. Reconectar revoga a conexão anterior. Usuário desativado ou com papel trocado perde as conexões na hora. **Fixa.**
- **IA7** Limite por conexão: 60 leituras e 20 escritas por minuto, ajustáveis. Quando estoura, a IA recebe um erro legível.
- **IA8** Cada escrita altera uma coisa só. Não existe alteração em lote, exclusão pela IA nem consulta livre (SQL). **Fixa.**
- **IA9** As ferramentas são marcadas como leitura ou escrita, para o assistente pedir confirmação antes de escrever. A segurança não depende disso: o servidor confere tudo.
- **IA10** Texto vindo de cliente volta para a IA como dado, marcado como conteúdo externo.
- **IA11** Toda escrita feita pela IA fica na auditoria com o canal (`ia:claude`, `ia:chatgpt`, `ia:codex`, `ia:gpt`).
- **IA12** Toda chamada da IA, inclusive de leitura, fica registrada por 180 dias (só ids e filtros) em log de arquivo do servidor, com rotação (R13). A tela de consulta do Administrador fica para quando alguém pedir; a escrita já está na auditoria (IA11).
- **IA13** O resumo do cliente pela IA mostra só o que o papel vê.
- **IA14** Ferramentas da fatia 1 (R12): seis. Cada ferramenta é Service + Policy + definição + teste, por isso só entram as de uso certo; as demais vêm com uso real, nas versões seguintes.

  | Módulo | Ferramenta | Tipo | O que faz |
  |---|---|---|---|
  | Projetos | `minhas_tarefas` | leitura | Tarefas do usuário, com filtro por prazo e projeto |
  | Projetos | `status_do_projeto` | leitura | Etapas, tarefas atrasadas e próximas datas previstas |
  | Projetos | `escrever_tarefa` | escrita | Cria o card no `Backlog`; só com título, ou com o modelo de requisição e os critérios (P17). Qualquer pessoa alocada (P56) |
  | Projetos | `mover_tarefa` | escrita | Muda a etapa (P13); pede origem e motivo quando a volta é retrabalho (P39) |
  | Projetos | `comentar_tarefa` | escrita | Adiciona comentário (P24) |
  | Projetos | `lancar_horas` | escrita | Lança horas realizadas (P30) |

  Depois da fatia 1, com uso real (lendo dados que a fatia 1 já grava): `buscar_cliente`, `resumo_do_cliente` (IA13), `linha_do_tempo_da_tarefa` (P53), `horas_do_projeto` (P53), `retrabalho_do_projeto` (P39, só gestor e Administrador, P54), `tarefas_paradas` (P15), `notas_da_versao` (P41, P55), `anexar_pr` (P23) e `devolver_para_desenvolvimento` (P39).

  Na v2, com o CRM: `meus_followups`, `minha_agenda`, `historico_do_lead`, `cadastrar_lead`, `registrar_tratativa`, `mover_lead`, `agendar_compromisso` e `atualizar_situacao_do_lead` (C3, C5 a C12), também pelo GPT "WCJ Hub".

  Decidido por Lucas Scholz em 24/09 e mantido em 06/10: o painel de retrabalho (P54) e o documento de notas de versão (P55) continuam depois da v1; os dados já ficam gravados desde a fatia 1.

  Caso de uso citado pelo Rafael: a IA do gestor escreve as tarefas detalhadas a partir do escopo (P56), e a do dev avança as tarefas e documenta enquanto ele trabalha.

#### Cenários de aceite de referência (formato do escopo)

- **Dado** um Comercial, que não tem acesso ao projeto P, **quando** o assistente dele chama `status_do_projeto(P)`, **então** recebe "negado" e a tentativa fica na auditoria (G3, G4).
- **Dado** um Dev com conexão só de leitura, **quando** o assistente chama `mover_tarefa`, **então** recebe "escopo insuficiente" e nada muda (IA4).
- **Dado** um Dev alocado no projeto, **quando** o assistente dele chama `escrever_tarefa` só com o título, **então** o card nasce no `Backlog` com tipo Melhoria e prioridade Normal (P56, P17); um Dev não alocado recebe "negado" (G4).
- **Dado** um usuário desativado agora, **quando** o assistente dele chama qualquer ferramenta, **então** recebe "não autorizado" (N4, IA6).
- **Dado** um texto de cliente que contém instruções ao assistente (com Chamados, a descrição de um chamado), **quando** o técnico pede um resumo pela IA, **então** o conteúdo volta delimitado como dado do cliente e nenhuma escrita acontece sem pedido do técnico (IA10).
- **Dado** uma tarefa movida pela IA, **quando** o gestor abre o histórico, **então** vê quem moveu, quando, de onde para onde e o canal `ia:claude` ou `ia:codex` (o ChatGPT só lê, IA3) (P14, IA11).

### 5. Chamados (rascunho)

Regras propostas, ainda não validadas com o Alaor, na forma da opção A ou da primeira fase da C (D-CHM-01).

- **RN-CHM-01** O chamado é ligado a cliente e contrato. E-mail de remetente desconhecido entra numa fila de **não identificados** para o despachante vincular; não é descartado.
- **RN-CHM-02** O número do chamado é sequencial por sequência do banco, nunca `max + 1` (`core/banco.md` seção 8).
- **RN-CHM-03** **SLA e prioridade são definidos contrato a contrato** (D-CHM-03). A prioridade do chamado é definida pela WCJ na triagem. [LEVANTAR] tabela de SLA de cada contrato de sustentação.
- **RN-CHM-04** Chamado com prazo estourado aparece em destaque para o despachante e o Administrador.
- **RN-CHM-05** Comentário interno (entre a equipe) nunca aparece para o cliente.
- **RN-CHM-06** Chamado fechado não é reaberto; o cliente abre outro, vinculado ao anterior. [DECIDIR]
- **RN-CHM-07** **Filas** configuráveis. Padrão: `N1`, `N2`, `N3`. A função **despachante** é atribuída a pessoas (D-CHM-02). Como no resto do Hub, filas, tipos e áreas são editáveis (G1).
- **RN-CHM-08** **Banco de horas** (D-CHM-04): o contrato pode ter horas contratadas por período. As horas apontadas nos chamados descontam do saldo; o que passar é **hora extra**, contabilizada à parte para cobrança.
- **RN-CHM-09** Chamado resolvido sem resposta do cliente **fecha sozinho** após N dias, configurável por contrato. [LEVANTAR] padrão de N (P-03 da ata).
- **RN-CHM-10** **Viagem** (deslocamento para atendimento no local) é registrada no chamado conforme o contrato. [LEVANTAR] o que o contrato prevê (P-02 da ata).

Ferramentas de IA previstas para Chamados, fora da v1: `chamados_abertos` (filtro por cliente, responsável, prioridade, prazo estourado), `detalhe_do_chamado` (conteúdo do cliente delimitado, IA10), `responder_chamado` (resposta visível ao cliente), `comentar_internamente` (RN-CHM-05) e `mudar_status_do_chamado`.

A levantar em Chamados:
- [ ] Decisão D-CHM-01 (construir, GLPI ou em duas fases), quando Chamados entrar na fila.
- [ ] Endereço do e-mail de suporte (P-01 da ata).
- [ ] Tipos de chamado e áreas.
- [ ] SLA de cada contrato de sustentação; quais contratos entram primeiro.
- [ ] Portal do cliente: entra junto do e-mail ou depois?
- [ ] Inventário de máquinas (GLPI Agent) é necessário para os contratos de sustentação de infraestrutura?
- [ ] Volume de chamados por mês (com o Alaor).

## Integrações e dados externos

| Integração | Direção | Na v1? |
|---|---|---|
| Claude, Codex e ChatGPT Pro via MCP | entrada (consulta e escrita pelo usuário; o ChatGPT Pro só consulta) | **Sim** (IA3) |
| GPT personalizado "WCJ Hub" (Actions + API REST) | entrada (comercial) | **v2, com o CRM** (IA3, D-GER-24) |
| GitHub (webhooks de PR) | entrada (eventos) | **Em avaliação** (D-GER-13). Na v1, o PR entra como link (P23) |
| Trello | importação | **Não**: começa do zero com os projetos ativos |
| Funil e CRM pós-auditoria do DaaS | importação | **Não** (C15) |
| DaaS consulta contratos no Hub | saída | Depois da v1 (N10, D-CRM-04) |
| DaaS: abrir auditoria a partir de lead ganho | saída | **Não** (C13) |
| E-mail interno | saída | **Sim**: convite de usuário com senha provisória (N1); Projetos avisa dev e QA (P22). SMTP Zoho da WCJ, como no sistema de obras. Convites `.ics` e resumo diário entram com o CRM (v2, C9) |
| E-mail de suporte | entrada (e-mail vira chamado) e saída (avisos ao cliente) | Com o módulo Chamados (D-CHM-02) |
| GLPI | entrada e saída pela API | Em avaliação (D-CHM-01) |
| WhatsApp | — | **Não** (G6) |

### Assistentes de IA: MCP e GPT "WCJ Hub"

**Como funciona**
- O sistema publica um **servidor MCP remoto** (HTTP). Os assistentes homologados suportam conector MCP remoto.
- O usuário conecta o assistente pela tela de perfil, por **OAuth 2.1** (obrigatório no padrão da casa, `core/mcp.md`): faz login no sistema e autoriza o assistente. Não há chave colada à mão (IA4).
- Cada autorização gera um token **por usuário e por assistente**, com escopos por módulo, de leitura ou de escrita (IA4). Os escopos ficam no Passport (definidos no código e gravados em cada token); os nomes técnicos estão no `banco.txt`, em `ai_authorizations`.
- As ferramentas MCP chamam **a mesma camada de serviço** que as telas. Não existe regra de negócio só no MCP nem só na tela.
- Os assistentes chamam o servidor **a partir da nuvem deles**, não da máquina do usuário. O servidor precisa estar na internet pública com HTTPS, inclusive na homologação: o dev local testa por túnel ou por ambiente de homologação publicado.

**Disponibilidade nos planos** (levantamento de 24/09/2026; todos os usuários têm plano Pro ou superior, D-IA-04)

| Assistente | Conector MCP remoto no plano Pro+ | Escrita | Situação |
|---|---|---|---|
| Claude (Pro, Max) | Sim: "Customize → Connectors", com OAuth e registro dinâmico de cliente | Sim | Atende |
| ChatGPT Business / Enterprise, app | Sim | Sim | Atende, mas é plano de empresa: fora da v1 |
| ChatGPT Pro, app | Sim, pelo modo desenvolvedor | **Não: só leitura** | Atende só leitura |
| ChatGPT Plus, app | **Não** | — | Não conecta |
| Claude Code (CLI/IDE/desktop) | Sim, conector local do usuário, HTTP + OAuth | Sim | Atende |
| Codex (CLI/IDE/app) | Sim: `codex mcp add <nome> --url <URL>` + `codex mcp login <nome>` (HTTP + OAuth) | Sim | Atende |

Conferido no próprio ChatGPT em 24/09/2026 (R-IA-01): escrita por conector próprio só no **Business** ou superior; o **Pro só lê**; o **Plus não conecta**. A restrição é do **app ChatGPT**, não do Codex. No Codex e no Claude Code o conector é configurado pelo próprio usuário e não passa pela regra de conector do workspace do ChatGPT; isso será confirmado na prova de conceito. Codex e Claude Code servem para quem trabalha no terminal ou na IDE (devs), não para o uso diário do comercial. O Codex também roda dentro do app do ChatGPT para computador (Configurações → Integrações → Servidores MCP) desde 07/2026.

Consequência para a v1 (IA3):
- **Claude (Pro ou superior):** leitura e escrita. É o assistente de referência para quem precisa alterar dados pela IA.
- **ChatGPT Pro:** entra só com escopos de leitura. O servidor emite token só de leitura para esse cliente; não depende de o ChatGPT se limitar.
- **ChatGPT Plus:** sem conector. Quem usa o Plus e precisa da IA no Hub usa o Claude, ou fica sem IA no Hub.
- **Codex e Claude Code (devs):** leitura e escrita pelo MCP.
- **GPT personalizado "WCJ Hub" (comercial):** um GPT com **Actions** que chama uma API REST do Hub, com **login OAuth de cada usuário** (o mesmo servidor de autenticação do MCP). Funciona no ChatGPT web e no celular a partir do Plus.
  - A API das Actions chama a **mesma camada de serviço** do MCP e da tela; as regras IA valem igual, com o canal `ia:gpt` na auditoria.
  - Escopo inicial: **CRM** (leitura e escrita) e **Núcleo** (leitura), com as ferramentas de CRM da IA14.
  - Toda ação de escrita é marcada como "consequencial", para o ChatGPT pedir confirmação antes (IA9).
  - É entregue **junto com o CRM (v2)**, não antes.

**Prova de conceito antes da etapa 4** (teste técnico, Lucas Scholz, D-GER-17): servidor MCP mínimo, HTTPS público, OAuth, uma ferramenta de leitura e uma de escrita. O teste é feito no **Claude (app)**, no **Codex dentro do app do ChatGPT (plano Plus)**, no **Codex CLI**, no **Claude Code** e no **ChatGPT Pro (só leitura)**. O **GPT com Actions**, no plano do Wilson, fica para a v2, com o CRM.

## Fora da primeira versão

- **CRM inteiro e o GPT "WCJ Hub"** (v2, D-GER-24), incluindo os tipos de serviço (N10), o convite `.ics` e o resumo diário (C9).
- **Contrato:** cadastro, contrato no projeto e consulta pelo DaaS (N10, D-CRM-04).
- **Projetos:** feriados (P29); restrição de etapa por função; vínculo automático com o GitHub (D-GER-13); migração do Trello; prazo do projeto pelo sistema (P9); time da sprint, férias e folga na capacidade (P47, P48); estados de pacote cadastráveis (P42); parecer por validação e flags de ligar e desligar (P32); tela das chamadas da IA (IA12); as ferramentas de IA além das 6 da fatia 1 (IA14).
- **Projetos, depois da v1 com os dados da v1:** painel de produtividade por dev e indicador de retrabalho (P54); histórico de desenvolvimento em PDF, relatório de fechamento, notas de versão e geração do documento de requisição (P55).
- **Ideias do levantamento sem prioridade:** roteiro de teste padrão por tipo e perfil de produto, com bloco CRUD reutilizável (Trello Wpay, modelos SGM); painel de impedimentos (Trello Wpay).
- **CRM:** oportunidades pós-auditoria; integração CRM → DaaS (C13); assistente de voz embutido (IA2); contrato gerado pelo lead ganho (C10); previsão de fechamento (C2).
- **IA:** IA embutida no sistema, como sugestão de follow-up, triagem ou resumo automático (IA2); contato do cliente conectando IA (IA1); ChatGPT Business; assistentes além de Claude, ChatGPT e Codex.
- **Geral (G6):** aprovação em dois níveis, ranking ou metas, mensagem ao cliente por WhatsApp, aplicativo nativo.
- **Chamados** inteiro (D-GER-12), incluindo o banco de horas (D-CHM-04), o login do contato (N9) e a abertura de chamado por WhatsApp.
- Faturamento, cobrança e emissão de nota.

## O que não pode

- Trava no fluxo de Projetos. O sistema não exige dev, horas, PR nem parecer de validação para mover card, e número de controle nunca recusa nada (premissa, P49). Só é fixo o que protege segurança e auditoria (G1).
- O dev excluir ou arquivar card, ou mexer em sprint, pacote e configuração (premissa, P26, P45). Criar card ele pode (P56).
- Construir o CRM, o GPT "WCJ Hub" ou ferramenta de IA fora das 6 da fatia 1 antes da hora (D-GER-24, IA14).
- Excluir de verdade registro já usado, e usar `deleted_at` (G2, `core/banco.md` §4).
- Apagar ou editar a auditoria. A única exceção é limpar os dados pessoais do contato anonimizado (G3, N12).
- Ferramenta de IA que exclua, altere em lote ou execute consulta livre, como SQL ou filtro arbitrário sobre qualquer tabela (IA8).
- O sistema chamar modelo de IA por conta própria ou guardar chave de API de provedor de IA (IA2).
- Conectar assistente por chave de API colada (IA4).
- O gestor ver o pipeline comercial ou o dev ver o CRM (C14).
- E-mail em Projetos além dos da P22 (definir dev ou QA; volta para desenvolvimento).
- Mensagem ao cliente pelo WhatsApp, aprovação em dois níveis, ranking, metas ou gamificação (G6).
- Com Chamados: o contato do cliente ver comentário interno, nome de outro cliente ou dado do CRM (RN-CHM-05).

## Vocabulário

| Termo | Significa |
|---|---|
| Cliente | Empresa atendida pela WCJ. Nasce sem contrato (N6). Inclui a própria WCJ, dona dos projetos internos (N7) |
| Contato | Pessoa de um cliente ou de um lead; um cadastro só (N8) |
| Tipo de serviço | O que está sendo vendido no lead: projeto, sustentação, auditoria, alocação de dev, infraestrutura (N10) |
| Contrato | Vínculo comercial que dá origem a projeto e/ou direito a chamado. Fora da v1 (N10) |
| Lead | Venda em andamento: empresa em prospecção ou cliente que pode comprar algo novo (C3) |
| Tratativa | Registro de uma conversa comercial com um lead (C5) |
| Situação atual | Texto curto que diz como está a conversa com o lead (C6) |
| Compromisso | Follow-up ou reunião, com data, hora e responsável (C7) |
| Follow-up | Próximo contato agendado com o lead (C8) |
| Projeto | Trabalho para um cliente. **Projeto de entrega**: com início e data de entrega. **Produto contínuo**: só início, em melhoria contínua por sprints (P2) |
| Tarefa / card | Item de trabalho dentro de um projeto (o "card" do Trello; a equipe também diz "task" e "demanda") |
| Chave | Identificador da tarefa no projeto: prefixo + número (`SGM-17`) (P16) |
| Critério de aceite (`CA_n`) | Item verificável do documento de requisição; é também o item do roteiro de teste (P17, P36) |
| Card de suporte | Tarefa do tipo suporte que o gestor registra quando o cliente aciona a WCJ direto (P52) |
| Backlog | Coluna dos cards fora da sprint (P45) |
| To do / Esteira | Coluna dos cards da sprint que ainda não começaram (`Esteira` era o nome no Trello) |
| Sprint | Período do projeto, com início e fim, que agrupa tarefas. No Histórico de Desenvolvimento aparece como "Fase" (P51) |
| Carga diária | Horas por dia que a pessoa trabalha na WCJ (ex.: 8 h ou 4 h) (N3) |
| Capacidade | Carga diária × dias úteis da sprint; só referência (P48, P49) |
| Teste cruzado | Teste da tarefa feito por outro dev, antes do QA (P34) |
| Retrabalho | Volta da tarefa para desenvolvimento, vinda do code review, do teste cruzado, do QA ou da homologação (P39) |
| Pacote / versão | Conjunto de tarefas prontas que vai junto para homologação e produção, com número e data (hoje: lista "Produção 1.0.9 10/07/2026") (P41) |
| Homologação | Teste do pacote pela equipe WCJ antes da produção (P43) |
| GMUD | Gestão de mudança: alteração de configuração ou ambiente em produção |
| Arquivar | Tirar do quadro um card com histórico, com motivo; volta ao `Backlog` ao desarquivar (P26) |
| Desativar | Tirar de uso um cadastro com histórico (usuário, cliente, contato, lead), guardando quem e quando (G2) |
| Chamado | Solicitação de um cliente sobre um sistema ou contrato (com o módulo Chamados) |
| SLA | Prazo de atendimento do chamado, definido pelo contrato e pela prioridade |
| Assistente / IA | Claude, ChatGPT ou Codex do próprio usuário, conectado pelo MCP (ou o GPT "WCJ Hub", pelas Actions) |
| Conector | A autorização de um assistente para acessar o sistema em nome do usuário (IA4) |
| Funcional | Documento por bloco que descreve tela, campos, ações e permissões em linguagem de quem usa; aprovado pelo dono do módulo antes da spec (D-GER-28) |
| Fatia 1 | Primeira entrega: Núcleo + Projetos + MCP com 6 ferramentas (D-GER-24). CRM é a v2 |

## Restrições

- Stack: template da casa (`stacks/laravel-inertia.md`), confirmada (D-GER-09). Banco MySQL 8 (D-GER-21).
- Estrutura do código (D-GER-15), por domínio desde o primeiro commit:
  - `app/Platform/`: o Núcleo, compartilhado por todos (usuários, papéis, clientes, contatos, auditoria, notificações, OAuth);
  - `app/Domains/Projetos/` e, depois, `app/Domains/Crm/` (v2) e `app/Domains/Chamados/`, cada um com `Controllers`, `Models`, `Policies`, `Requests`, `Services` e **`Mcp`** (as ferramentas de IA daquele domínio, chamando o `Service` do próprio domínio);
  - um domínio não lê nem grava a tabela de outro: chama o `Service` dele (ex.: o lead ganho cria o cliente pelo `Service` do Platform).
- Servidor MCP com `laravel/mcp` e OAuth com Passport (D-IA-07), confirmados na prova de conceito.
- Servidor MCP em HTTPS público desde a homologação (os assistentes chamam da nuvem deles).
- Hospedagem (D-GER-14): **VPS do DaaS** (`auditoria.wcjtecnologia.com.br`), que já roda o app do DaaS e o Supabase em Docker atrás do Nginx. O Hub entra como **mais um conjunto de containers**, com subdomínio próprio (ex.: `hub.wcjtecnologia.com.br`), **banco de dados separado** do Supabase do DaaS, backup próprio e entrada no Nginx. A capacidade da VPS e a forma de separar o banco são definidas com **Alaor Rodrigues** ao subir o ambiente de teste (D-GER-19).
- Uso principal: computador. Web responsiva; quais telas e funções serão usadas de fato no celular será validado depois.
- Volume (D-GER-10): até 14 usuários internos, 6 clientes ativos, cerca de 10 leads por mês.
- Segurança: fatia de segurança obrigatória (`core/seguranca.md` seção 11), **incluindo o servidor MCP e o OAuth** (`core/mcp.md`).
- Prazo: o mais rápido possível (D-GER-08).

## Donos e pessoa técnica

| Parte | Dono (ouve o usuário, valida esta seção) |
|---|---|
| Núcleo | Lucas Scholz (D-GER-07) |
| Projetos | Rafael Garcia (projetos 100% IA: Lucas Scholz, D-GER-02) |
| CRM | Lucas Scholz |
| Chamados | Alaor Rodrigues (D-GER-04) |
| Interface de IA | Lucas Scholz (produto) + Lucas Alencar (técnico) (D-GER-07) |
| **Pessoa técnica que aprova o banco (etapa 2)** | **Lucas Alencar** (D-GER-01) |
| **Execução** | Cauã e Vitinho, com IA; supervisão, aprovação de funcional, spec e PR por Lucas Scholz (D-GER-25) |
| **Front-end e marca no template** | Lucas Varanda (D-GER-29) |
| Infraestrutura (VPS) | Alaor Rodrigues (D-GER-19) |

## Em aberto

- [ ] **[DECIDIR]** Comitê: D-CHM-01, quando Chamados entrar na fila. Demais pontos de Chamados na seção do módulo.
- [x] Lucas Scholz revisou o `banco.txt` (Sessão D, 24/09).
- [x] Lucas Alencar aprovou o `banco.txt` em 06/10 (D-GER-23).
- [ ] Lucas Alencar confere a diferença da fatia 1 (seção 7 do `banco.txt`), em especial a R15 (asserção no Service no lugar das FKs compostas).
- [ ] Rafael Garcia aprova os funcionais de Projetos (`docs/funcional/`, D-GER-28); Lucas Scholz, os de Núcleo e IA.
- [ ] Prova de conceito: ver se Claude app e Claude Code (e Codex CLI e Codex no app) usam a mesma conexão ou uma cada. Como a conexão é uma por assistente (IA4, IA6), conectar um derrubaria o outro; se o dev precisar dos dois, `claude_code` volta a ser um assistente separado.
- [ ] Prova de conceito: ver **como cada assistente se identifica** no OAuth (URL de metadados do cliente, pré-cadastro ou registro dinâmico). No registro dinâmico, o nome é declarado pelo próprio cliente. Nesse caso, o canal `ia:claude`/`ia:chatgpt` (IA11) e o token só de leitura do ChatGPT Pro viram conveniência, e a garantia fica no escopo pelo papel (IA5). Ver `core/mcp.md`, seção 3.
- [ ] (v2) Prova de conceito: testar se o **modo de voz** do ChatGPT também chama as Actions do GPT, ou se só o ditado funciona.
- [ ] Enquete interna de ferramentas (em andamento): para cada pessoa, **qual assistente**, **qual plano** e **onde usa** (app web/desktop, celular, Claude Code, Codex).
- [ ] [LEVANTAR] Os outros quadros do Trello, em especial um de projeto de cliente com prazo (ex.: gestão de multas), para comparar com o Wpay.
- [ ] [LEVANTAR] Modelo do relatório de fechamento para pagamento (P55, depois da v1; P-01 e P-02 da ata de Projetos).

## Fontes

- 24/09/2026: conversa de concepção (Lucas Scholz).
- 24/09/2026: disponibilidade de conector MCP: Claude Help Center ("Get started with custom connectors using remote MCP") e OpenAI Developers ("ChatGPT Developer mode"). A divergência sobre escrita no Plus/Pro foi resolvida conferindo no próprio ChatGPT (R-IA-01). Codex: OpenAI Developers ("Model Context Protocol", Codex).
- 24/09/2026: export JSON do quadro Trello "Wpay" → `levantamento/trello-wpay.md`. Demais quadros pendentes.
- 24/09/2026: reunião de Projetos com Rafael Garcia → `atas/2026-09-24-projetos-rafael.md`.
- 24/09/2026: reunião de Chamados com Alaor Rodrigues → `atas/2026-09-24-chamados-alaor.md` (inclui resumo do vídeo de referência do GLPI).
- 24/09/2026: fluxo de desenvolvimento e colunas do quadro enviados por Lucas Alencar → `levantamento/fluxo-desenvolvimento.md`.
- 24/09/2026: documentos de requisição SGM-13/17/22 e "Histórico de Desenvolvimento" Tecsolution → `levantamento/modelos-rafael.md` (originais fora do git).
- 24/09/2026: revisão das regras com Lucas Scholz, em `docs/regras-para-revisao.md` (revisões 1 a 4, commits `3f8080a`, `0e1125f`, `b53d9d2`, `20aaa4c` e `da44c03`). O arquivo foi incorporado a este documento na Sessão C e apagado; o git guarda.
- 24/09/2026: revisão do `banco.txt` por Lucas Scholz, tabela por tabela (Sessão D). O que mudou está no `banco.txt`, seção 7, "Sessão D".
- Preparação das reuniões: `docs/pautas/` (histórico, não é requisito).
- Funil de prospecção e CRM pós-auditoria do DaaS: repositório `wcj-ui` (referência de funcionalidade; dados não migrados, C15).
- 01/10/2026: revisão independente (R1 a R17) em `docs/revisoes/2026-10-01-revisao-fable.md`, aceita por inteiro por Lucas Scholz em 06/10.
- 06/10/2026: aprovação do `banco.txt` por Lucas Alencar e decisões D-GER-23 a D-GER-28 (Lucas Scholz).
- Atas do Comitê de IA: `docs/atas/`.
