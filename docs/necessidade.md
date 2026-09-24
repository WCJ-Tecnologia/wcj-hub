# Necessidade: WCJ Hub

Etapa 1 do `playbooks/aplicacao-nova.md` (padrão `ai-systems` v0.2.1). Entrada para o modelo de banco (etapa 2) e para o escopo (etapa 4).

**Situação:** rascunho para o Comitê de IA. Nome definido: **WCJ Hub** (D-GER-06). Itens marcados **[LEVANTAR]** dependem do dono do módulo; itens marcados **[DECIDIR]** dependem do comitê. Regras marcadas *(proposta)* ainda não foram validadas por quem usa.

### Decisões registradas

| # | Data | Decisão |
|---|---|---|
| D-GER-01 | 24/09/2026 | Pessoa técnica que aprova o banco (etapa 2): **Lucas Alencar**. |
| D-GER-02 | 24/09/2026 | Gestor de projetos: **Rafael Garcia**. Projetos feitos 100% com IA são geridos por **Lucas Scholz**. |
| D-GER-03 | 24/09/2026 | O funil de prospecção do DaaS **migra para o CRM deste sistema**. O DaaS fica só com a parte de auditoria. |
| D-IA-01 a 03 | 24/09/2026 | Ver [Interface de IA (MCP)](#interface-de-ia-mcp). |
| D-PRJ-01 a 10 | 24/09/2026 | Reunião de Projetos com Rafael Garcia. Ver [ata](atas/2026-09-24-projetos-rafael.md). |
| D-PRJ-13 | 24/09/2026 | O cliente **não participa** da homologação do pacote: a equipe WCJ testa tudo junto. |
| D-PRJ-14 | 24/09/2026 | Colunas padrão em **inglês**, sempre editáveis por projeto. |
| D-PRJ-15 | 24/09/2026 | Teste cruzado **ligado por padrão** em toda tarefa; quem não quiser desmarca na tarefa. |
| D-PRJ-16 | 24/09/2026 | Lucas Alencar: "não fecha tanto o fluxo". Uma tarefa pode ter **vários PRs**. Por padrão, qualquer pessoa alocada move a tarefa; travas de PR e de função são **opcionais** por projeto. |
| D-PRJ-17 | 24/09/2026 | Enquanto o módulo Chamados não existe, o **gestor do projeto registra à mão um card de suporte** quando o cliente o aciona direto (WhatsApp, telefone, e-mail). O card vai para a coluna `Suporte` do quadro. |
| D-PRJ-12 | 24/09/2026 | O QA testa no **ambiente local dele**; o **Tech Lead faz o merge** (Lucas Alencar, via Lucas Scholz). |
| D-PRJ-11 | 24/09/2026 | Colunas padrão do quadro, propostas por Lucas Alencar: `Backlog`, `To do`, `Doing`, `Paused`, `Code-review`, `In test`, `Done`. Fluxo de desenvolvimento em 11 passos, também dele. Ver [`levantamento/fluxo-desenvolvimento.md`](levantamento/fluxo-desenvolvimento.md). |
| D-CRM-01 | 24/09/2026 | Funil do CRM: etapas do DaaS como padrão, editáveis. Motivos de perda editáveis. Tratativa editável com histórico. Comercial vê todos os leads e edita os próprios. Sizing obrigatório para ganhar auditoria. CRM → DaaS automático depois da v1. |
| D-CRM-02 | 24/09/2026 | O CRM pós-auditoria do DaaS (oportunidades e contratos) **vem junto** com o funil. |
| D-CHM-01 | 24/09/2026 | **[DECIDIR]** Chamados: construir no Hub, adotar o GLPI (sugestão do Alaor) ou em duas fases. Ver Módulo Chamados. |
| D-CHM-02 | 24/09/2026 | Canal inicial de chamado: **e-mail** (endereço próprio). Triagem por uma função de **despachante**, com filas N1/N2/N3. |
| D-CHM-03 | 24/09/2026 | SLA e prioridade **contrato a contrato**; prioridade definida pela WCJ na triagem. No início, chamados só no nível de projeto. |
| D-CHM-04 | 24/09/2026 | **Banco de horas**: contrato com horas contratadas; excedente contabilizado como hora extra. Chamado resolvido fecha sozinho sem resposta do cliente. |
| D-GER-04 | 24/09/2026 | Dono do módulo Chamados (sustentação): **Alaor Rodrigues**. |
| D-GER-06 | 24/09/2026 | Nome do sistema: **WCJ Hub**. |
| D-GER-07 | 24/09/2026 | Donos: **Núcleo** = Lucas Scholz; **Interface de IA** = Lucas Scholz (produto) + Lucas Alencar (técnico). |
| D-GER-08 | 24/09/2026 | Prazo: o mais rápido possível. Ordem das entregas: **Núcleo → Projetos → CRM → Chamados** (revisada em D-GER-12). |
| D-GER-12 | 24/09/2026 | **Prioridade: Projetos, depois CRM, por último Chamados.** Chamados (ITSM) fica para o fim porque ainda não há demanda. O levantamento com o Alaor não tem data. |
| D-GER-13 | 24/09/2026 | Integração com o GitHub (webhooks de PR) **fora da v1, em avaliação** por Lucas Scholz. |
| D-GER-09 | 24/09/2026 | Stack: template da casa (Laravel + Inertia). As regras do padrão `ai-systems` (incluindo o futuro `core/mcp.md`) valem **principalmente para sistemas 100% IA**, como o WCJ Hub; projetos com time mais sênior têm liberdade de stack e template. |
| D-GER-10 | 24/09/2026 | Volume: até **14 usuários internos**, **6 clientes ativos**, cerca de **10 leads por mês**. |
| D-GER-11 | 24/09/2026 | Tipos de contrato: projeto, sustentação, auditoria, alocação de dev, infraestrutura; o Administrador cadastra mais. |
| D-GER-14 | 24/09/2026 | Hospedagem: **na mesma VPS do sistema de auditoria (DaaS)**, com subdomínio próprio e HTTPS. |
| D-GER-05 | 24/09/2026 | **Padrão que se ajusta:** o sistema traz um padrão pronto e cada projeto pode ser adaptado. Fluxo travado torna a ferramenta descartável. Vale para os três módulos: só é fixo o que protege segurança, histórico e indicadores. |
| D-IA-04 | 24/09/2026 | Todos os usuários internos têm plano **Pro ou superior** (Claude e/ou ChatGPT). Nenhum usa plano gratuito. |
| D-IA-04b | 24/09/2026 | Conferido no ChatGPT: escrita por conector só no **Business**; **Pro só lê**; **Plus não conecta** (R-IA-01 confirmado). |
| D-IA-06 | 24/09/2026 | Quem usa o quê: **devs** conectam por **Codex ou Claude** (MCP, leitura e escrita). **Comercial (Wilson)** usa ChatGPT: acesso por um **GPT personalizado "WCJ Hub" com Actions**, entregue junto com a v1 do CRM. |

**Porta de entrada do playbook:** "cliente só com conversa". Não existe sistema anterior com modelo de dados aproveitável (Trello é ferramenta genérica). Logo: regras e fluxo primeiro, IA propõe o modelo de dados, pessoa técnica valida na etapa 2.

---

## Para que serve

Um sistema único para a operação interna da WCJ, com três módulos sobre uma base comum:

| Módulo | Substitui | Problema de hoje |
|---|---|---|
| **Projetos** | Trello (cerca de R$ 500,00 por mês, segundo a ata de 24/09) | No quadro Wpay: retrabalho do QA invisível (2 em 3 entregas voltam), versões controladas como listas criadas à mão, prazo quase não usado, chamado misturado com projeto. Rafael: sem tempo por etapa nem previsto × realizado de horas; cronologia de entregas para o cliente montada à mão. |
| **CRM** | Funil de prospecção do DaaS (D-GER-03) + [LEVANTAR] planilha / WhatsApp | Lead e tratativa não ficam registrados num lugar só; follow-up depende de alguém lembrar. |
| **Chamados** (último, D-GER-12) | Nada formal; GLPI em avaliação (D-CHM-01) | Cliente de sustentação e de projeto em andamento não tem canal formal; não há prazo de atendimento medido nem histórico por cliente. |

**Base comum (núcleo):** usuários, papéis, clientes (empresas), contatos e contratos. É o que liga os três módulos: o lead ganho no CRM vira cliente; o cliente tem contratos; o contrato tem projetos e dá direito a abrir chamados.

**Princípio de desenho (D-GER-05):** padrão pronto, ajustável por projeto. Só é fixo o que protege segurança, histórico e indicadores.

**Interface de IA:** cada usuário interno conecta o próprio assistente (Claude ou ChatGPT) ao sistema para consultar e alterar dados dos módulos, com as mesmas permissões que tem na tela. Detalhado no bloco [Interface de IA (MCP)](#interface-de-ia-mcp).

---

## Quem usa

| Papel | Quem é | O que faz no sistema | O que não pode ver ou fazer |
|---|---|---|---|
| Administrador | Sócios | Tudo; gerencia usuários, papéis, clientes e contratos | — |
| Gestor de projetos | Rafael Garcia; Lucas Scholz nos projetos 100% IA | Cria e acompanha projetos, distribui tarefas, vê chamados dos clientes dos seus projetos | **Pipeline comercial e valores de proposta** (D-PRJ-08) |
| Tech Lead | **Definido por projeto** (D-PRJ-10): é uma função no projeto, não um papel fixo do usuário | Aprova o PR no `Code-review` **dos projetos em que é Tech Lead** | Aprovar code review em projeto em que não é Tech Lead |
| Desenvolvedor / técnico | Devs (PJ) | Trabalha nas tarefas atribuídas, atende chamados atribuídos | CRM; contratos e valores; projetos em que não está alocado (D-PRJ-03) |
| Comercial | Equipe comercial | Cadastra leads, registra tratativas, agenda follow-ups, converte lead em cliente | Tarefas internas de projeto; **conteúdo dos chamados** (na ficha do cliente vê só a quantidade de chamados abertos e o status) |
| Suporte / sustentação | Alaor Rodrigues (dono); função **despachante** + filas N1/N2/N3 (D-CHM-02) | Recebe, classifica e distribui chamados; acompanha prazo | CRM |
| Contato do cliente (externo) | Pessoa indicada pelo cliente | Abre e acompanha os chamados **da própria empresa** | Qualquer dado de outro cliente; CRM; projetos internos; **não conecta IA na v1** |

Regra geral: o que o papel não vê na tela, a IA dele também não vê.

---

## Núcleo (base comum)

### Fluxo principal

1. Administrador cadastra usuários internos e define o papel de cada um.
2. Cliente nasce de duas formas: cadastro direto ou conversão de lead ganho no CRM.
3. Cliente recebe contratos. Tipo de contrato é **lista cadastrável** pelo Administrador (D-GER-11); padrão inicial: **projeto, sustentação, auditoria, alocação de dev, infraestrutura**. O Administrador cadastra novos tipos conforme a empresa cresce.
4. Contatos do cliente são cadastrados e, se autorizados, recebem acesso ao portal de chamados.

### Regras de negócio

- **RN-NUC-01** Cliente é identificado pelo CNPJ. Dois clientes com o mesmo CNPJ são recusados.
- **RN-NUC-02** Cliente, contato e usuário não são excluídos depois de usados. São desativados (`deactivated_at`, `deactivated_by`), conforme `core/banco.md` seção 4.
- **RN-NUC-03** Contato externo só enxerga dados do próprio cliente. Tentativa de acessar outro cliente por ID é negada e registrada.
- **RN-NUC-04** Toda alteração em registro de qualquer módulo grava trilha de auditoria: quem, quando, antes/depois e **canal** (`tela`, `ia:claude`, `ia:chatgpt`, `ia:codex`, `ia:gpt`).
- **RN-NUC-05** Usuário desativado perde acesso imediatamente, inclusive os tokens de IA dele.

---

## Módulo Projetos

Base: quadro Trello "Wpay" ([`levantamento/trello-wpay.md`](levantamento/trello-wpay.md)), reunião com Rafael Garcia em 24/09/2026 ([ata](atas/2026-09-24-projetos-rafael.md)) e modelos enviados por ele ([`levantamento/modelos-rafael.md`](levantamento/modelos-rafael.md)).

**Escopo da v1 (D-PRJ-05):** "as funcionalidades do Trello com a adição do controle do tempo". Ou seja: quadro por projeto com etapas configuráveis, tarefas, responsáveis, checklist, anexos e comentários, **mais** tempo por etapa e horas previstas × realizadas.

### Fluxo principal

1. Gestor cria o projeto vinculado a um cliente e a um contrato, com **prefixo** (ex.: `SGM`) e **etapas próprias** (D-PRJ-01). Modelo padrão (D-PRJ-11): `Backlog → To do (sprint) → Doing → Code-review → In test → Done`, mais `Paused` e `Suporte` (D-PRJ-17). Detalhe em [`levantamento/fluxo-desenvolvimento.md`](levantamento/fluxo-desenvolvimento.md).
2. Gestor (ou a IA dele) escreve a tarefa no **modelo de requisição**: Sendo / Quero / Para + critérios de aceite `CA_1…CA_n`. Cada critério vira item do roteiro de teste.
3. Gestor atribui pessoas **por função** (back-end, front-end, QA) e define quais validações a tarefa exige: **code review**, **teste cruzado** e/ou **QA** (RN-PRJ-09).
4. Tarefa sem responsável não sai do Backlog (D-PRJ-02). Quem é atribuído é notificado.
5. O dev analisa a tarefa e **informa as horas previstas** (por tarefa ou por item). O sistema converte em **data prevista** considerando dias úteis, feriados e a carga diária do dev.
6. A tarefa entra em `To do` (a sprint; no Trello, `Esteira`) e segue as etapas. Cada mudança de etapa é registrada com data e hora. Em `Doing` o dev desenvolve e faz commits e pushes na branch. Ao terminar, **abre um ou mais PRs para a `main`** e a tarefa vai para `Code-review`, onde normalmente o **Tech Lead** do projeto revisa (D-PRJ-09, D-PRJ-16).
7. Em `In test` acontecem, nesta ordem, o **teste cruzado** (outro dev, nunca o autor) e o **QA**, que executa o roteiro, marca os itens e anexa a evidência em cada um. O dev lança as **horas realizadas**.
8. Aprovado no QA, é feito o merge (normalmente pelo Tech Lead) e a tarefa vai para `Done`. Reprovado no code review, no teste cruzado ou no QA, volta para `Doing` com motivo de lista fechada.
9. Tarefas em `Done` entram num **pacote (versão)**. O pacote é liberado em **homologação**, homologado e publicado em produção; aí está finalizado. A data é definida por projeto; em projeto de cliente, geralmente pelo cliente.
10. Quando o cliente aciona a WCJ direto (WhatsApp, telefone, e-mail), o gestor registra um **card de suporte** na coluna `Suporte` do quadro (D-PRJ-17). Quando o módulo Chamados existir, os chamados de projeto passam a cair nessa mesma coluna.
11. O gestor gera para o cliente o **histórico de desenvolvimento** (linha do tempo) e o **relatório de fechamento** do período.

### Princípio: padrão que se ajusta (D-GER-05)

O sistema entrega um **padrão pronto** e o gestor **ajusta por projeto** quando precisar. Fluxo travado vira ferramenta descartável: o projeto que não cabe no padrão volta para a planilha. Por isso a maior parte das regras abaixo são **padrões configuráveis**. Só ficam fixas as regras que protegem segurança, histórico e os números dos indicadores.

O que mantém os indicadores funcionando mesmo com o fluxo mudando: cada etapa tem um **papel** (RN-PRJ-13). O gestor renomeia, cria ou remove etapas à vontade; tempo por etapa e retrabalho são calculados pelo papel, não pelo nome.

### Regras fixas (valem para todo projeto)

- **RN-PRJ-01** Todo projeto pertence a um cliente e a um contrato. Projeto interno ou produto da WCJ (ex.: Wpay) usa o cliente "WCJ".
- **RN-PRJ-03** Projeto encerrado não recebe tarefa nova; reabrir exige Gestor ou Administrador.
- **RN-PRJ-04** Dev só vê projetos em que está alocado (D-PRJ-03).
- **RN-PRJ-05** Todo projeto tem um gestor responsável. Gestor vê e edita os projetos que gere; Administrador vê todos.
- **RN-PRJ-11** Mudança de etapa sempre grava quem, quando, de onde e para onde (RN-NUC-04). É daí que saem tempo por etapa e retrabalho.
- **RN-PRJ-13** Toda etapa tem um **papel**: fila, sprint, desenvolvimento, code review, teste, pronto, pausa, chamados. Nome, ordem e quantidade de etapas são livres (D-PRJ-01); o papel é obrigatório.
- **RN-PRJ-08** Toda volta para uma etapa de desenvolvimento conta um ciclo de retrabalho, com **origem** (code review, teste cruzado, QA, homologação) e **motivo**. A lista de motivos é editável por projeto e sempre tem "outro" com texto. Motivos iniciais [validar com Rafael]: ajuste visual, regra de negócio, erro/bug, **requisito incompleto ou mal documentado** (a principal causa de retrabalho, segundo o Rafael).
- **RN-PRJ-14** Pessoa é atribuída à tarefa **com uma função** (back-end, front-end, QA, outra). Pode haver mais de uma pessoa por função. A lista de funções é editável.
- **RN-PRJ-15** Horas previstas e realizadas são registradas **por pessoa**. Horas são decimais; não há arredondamento para dia.
- **RN-PRJ-16** A data prevista é calculada em **dias úteis**: horas previstas ÷ carga diária do dev, pulando fins de semana e feriados do calendário. Ex.: 16 h = 2 dias úteis para 8 h/dia e 4 dias úteis para 4 h/dia.
- **RN-PRJ-17** Cada usuário interno tem **carga diária** cadastrada (ex.: 8 h ou 4 h). Mudança de carga vale dali para a frente; não recalcula o passado.
- **RN-PRJ-18** Existe um **calendário de feriados** (nacionais + locais) mantido pelo Administrador.
- **RN-PRJ-19** Tarefa tem **chave** `<prefixo do projeto>-<número>` (ex.: `SGM-17`). O número é sequencial por projeto, gerado por sequência do banco, e nunca é reutilizado.
- **RN-PRJ-20** Critério de aceite da tarefa gera item no roteiro de teste. Editar o critério atualiza o item enquanto ele não foi testado; depois de testado, a alteração reabre o item e fica no histórico.
- **RN-PRJ-21** Atribuir pessoa a uma tarefa **notifica** a pessoa, no sistema e por e-mail.
- **RN-PRJ-22** Indicadores de produtividade por dev (prazo, retrabalho, previsto × realizado) são vistos **só pelo gestor e pelo Administrador**. O dev não tem acesso a esse painel.
- **RN-PRJ-26** Quando o teste cruzado está ligado na tarefa, ele é feito por um dev **diferente do autor**, alocado no projeto.
- **RN-PRJ-29** Mudar a configuração de um projeto vale **dali para a frente**, fica no histórico e **nunca apaga** registro já feito. Remover uma etapa com tarefas dentro exige escolher para qual etapa elas vão.
- **RN-PRJ-32** **Card de suporte** (D-PRJ-17): tarefa do tipo `suporte`, criada pelo gestor (ou pela IA dele) quando o cliente aciona a WCJ direto. Além dos campos da tarefa, registra **quem pediu** (contato do cliente), **por onde** (WhatsApp, telefone, e-mail, reunião, outro) e **quando o pedido chegou**. As horas apontadas nele ficam separadas por contrato, para o banco de horas usar depois (RN-CHM-08). Quando o módulo Chamados entrar, esses cards podem ser vinculados ou convertidos em chamado, sem perder o histórico.

### Padrões configuráveis por projeto

O projeto nasce com o padrão abaixo. O gestor muda o que precisar nas configurações do projeto; o que é por tarefa, muda na própria tarefa.

| # | Configuração | Padrão | O que pode mudar |
|---|---|---|---|
| RN-PRJ-30 | **Etapas do quadro** | `Backlog`, `To do`, `Doing`, `Paused`, `Code-review`, `In test`, `Done` (D-PRJ-11), em inglês (D-PRJ-14) | Renomear, criar, remover, reordenar; nomes em qualquer idioma |
| RN-PRJ-09 | **Validações da tarefa** | Code review, teste cruzado e QA **ligados** (D-PRJ-15) | Cada validação liga/desliga **por projeto** (padrão) e **por tarefa** (D-PRJ-15). Ex.: Swagger sem QA |
| RN-PRJ-02 | Responsável obrigatório para sair do `Backlog` | Ligado (D-PRJ-02) | Desligar |
| RN-PRJ-23 | **PRs da tarefa** | A tarefa aceita **nenhum, um ou vários PRs** vinculados (D-PRJ-16): demanda grande pode ter vários. Exigir PR para entrar em `Code-review`: **desligado** | Ligar a exigência de ao menos um PR |
| RN-PRJ-24 | **Tech Lead** do projeto (D-PRJ-10) | Um Tech Lead definido pelo gestor. A mesma pessoa pode ser Tech Lead num projeto e dev em outro | Trocar a pessoa; ter mais de um |
| RN-PRJ-27 | Quem pode mover a tarefa entre etapas | **Qualquer pessoa alocada no projeto** (D-PRJ-16). Na prática, o Tech Lead aprova o code review e faz o merge (D-PRJ-12), mas o sistema não trava | Restringir etapas a funções. Ex.: só o Tech Lead tira de `Code-review` e move para `Done` |
| RN-PRJ-25 | Quem aprova quando o Tech Lead é o próprio dev da tarefa | Só se aplica se a restrição da RN-PRJ-27 estiver ligada; aí, o gestor aprova | Indicar outro Tech Lead |
| RN-PRJ-12 | Motivo obrigatório ao ir para `Paused` | Ligado; tempo parado contado à parte | Desligar |
| RN-PRJ-10 | **Estados do pacote (versão)** | `em montagem → em homologação → homologado → em produção`. Só tarefa em `Done` entra. Pacote em homologação não recebe tarefa nova; reprovação devolve as tarefas afetadas com retrabalho de origem homologação. **Homologação feita pela equipe WCJ, testando tudo junto; o cliente não participa** (D-PRJ-13) | Renomear, criar ou remover estados |
| RN-PRJ-07 | **Tipo** e **área** da tarefa (campos separados, lista fechada) | Tipos: melhoria, correção de bug, **suporte**, mudança (GMUD). Áreas: nenhuma, o gestor cadastra (no Wpay: Admin, Geral, FDIC, Âncora, Fornecedores, Comercial) | Editar as duas listas |
| RN-PRJ-06 | **Modalidade** do projeto | `tradicional` (D-GER-02) | `100% IA`. Na v1 é só uma marcação no projeto, com o mesmo fluxo aberto |
| RN-PRJ-31 | Coluna `Suporte` | **Ligada desde a v1** (D-PRJ-17): recebe os cards de suporte registrados pelo gestor. Com o módulo Chamados, recebe também os chamados de projeto (F-PRJ-08) | Renomear, desligar |

Observação (D-PRJ-12): o QA testa a branch do PR **no próprio ambiente local**; o sistema não cria nem exige ambiente por PR. Como as tarefas só se encontram depois do merge, a **homologação do pacote** é o primeiro teste integrado.

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
| F-PRJ-08 | **Coluna "Suporte"** no quadro: na v1, card de suporte registrado pelo gestor; depois, chamado do cliente cai como tarefa ligada | Rafael + D-PRJ-17 | **v1** (card manual) / com o módulo Chamados (automático) |
| F-PRJ-01 | **Indicador de retrabalho** com motivo da devolução | Trello Wpay | v1+ (os dados já são gravados na v1 por RN-PRJ-08/11) |
| F-PRJ-17 | **Painel de produtividade por dev** (prazo, retrabalho, previsto × realizado) | Rafael | v1+ (idem) |
| F-PRJ-03 | **Versão** como registro, com notas de versão | Trello Wpay + Rafael | v1+ |
| F-PRJ-15 | **Histórico de desenvolvimento** em linha do tempo, em PDF para o cliente (modelo Tecsolution) | Rafael | v1+ |
| F-PRJ-16 | **Relatório de fechamento** do período para aprovação do cliente antes do pagamento | Rafael | v1+ (aguarda modelo, P-01) |
| F-PRJ-13 | **Gerar o documento de requisição** (Word/PDF padrão WCJ) a partir da tarefa e anexar | Rafael | v1+ |
| F-PRJ-04 | **Roteiro de teste padrão** por tipo e perfil do produto, incluindo bloco CRUD reutilizável | Trello Wpay + Modelos SGM | ? |
| F-PRJ-06 | **Vínculo com PRs do GitHub** (um ou vários por tarefa): link colado na tarefa, à mão ou pela IA. Vínculo automático por webhook **em avaliação** (D-GER-13) | Trello Wpay | **v1** (link) / em avaliação (automático) |
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
2. Lead avança pelas etapas do funil. Padrão = etapas do funil do DaaS: `Prospecção → Contato → Reunião → Proposta → Ganhou / Perdido`. **Editáveis** como no quadro de projetos: criar, renomear, remover, reordenar (D-CRM-01).
3. Cada contato com o lead vira uma **tratativa**: data, canal, resumo, próximo passo.
4. Tratativa com próximo passo gera um **follow-up** com data e responsável.
5. Comercial vê, todo dia, os follow-ups vencidos e os do dia.
6. Lead é ganho (vira cliente no núcleo, com contrato) ou perdido (com motivo).

### Regras de negócio *(proposta)*

- **RN-CRM-01** Lead ganho converte para cliente sem redigitar dado. Se o CNPJ já existe como cliente, vincula em vez de duplicar.
- **RN-CRM-02** Lead perdido exige motivo de uma lista. Padrão: preço, momento, concorrente, sem resposta, sem perfil, outro. A lista é **editável**: dá para criar motivos depois.
- **RN-CRM-03** Tratativa é **editável a qualquer momento**; toda edição fica no histórico (D-GER-05).
- **RN-CRM-04** Todo lead ativo tem um follow-up futuro. Lead ativo sem follow-up aparece em alerta para o responsável.
- **RN-CRM-05** Todo comercial **vê todos os leads** e **edita só os próprios**; Administrador edita todos. Evita duas pessoas prospectando a mesma empresa sem saber.

### A levantar

- [ ] Etapas do funil e o critério para passar de uma para outra.
- [ ] Campos mínimos do lead e da tratativa.
- [ ] Origens de lead (indicação, LinkedIn, grupos, site, evento…).

### Migração do funil do DaaS (D-GER-03)

O funil de prospecção vive hoje no DaaS (`wcj-ui`, tabelas `lead` e `lead_evento`, kanban em `/console/comercial/prospeccao`). Ele vem para cá; no DaaS fica só a auditoria.

- **RN-CRM-06** Leads e eventos do DaaS entram por **importação única e idempotente**: casa por CNPJ antes de nome e não sobrescreve campo já preenchido (`core/banco.md` seção 9). Evento do DaaS vira tratativa. Roda primeiro em modo simulado.
- **RN-CRM-07** Os campos de dimensionamento que hoje travam o "ganhar" no DaaS (setor, 5 respostas de sizing, preço) viram campos do lead com oportunidade de **auditoria**. Continuam **obrigatórios para ganhar** oportunidade de auditoria.
- **RN-CRM-08** Hoje "ganhar lead" no DaaS cria a auditoria em pré-venda. Aqui, lead ganho com contrato de auditoria **não cria nada no DaaS na v1**: a auditoria é aberta no DaaS à mão, com o CNPJ do cliente. A integração automática CRM → DaaS fica **para depois da v1**.
- **RN-CRM-09** Depois da importação conferida (contagem de leads e eventos batendo com a origem), o funil sai do DaaS. A remoção no DaaS é trabalho de manutenção **naquele repositório** (`playbooks/manutencao.md`), com PR próprio: tira a tela, a rota e as RPCs de escrita e mantém as tabelas como histórico somente leitura até a pessoa técnica liberar a exclusão.
- **RN-CRM-10** O **CRM pós-auditoria do DaaS também vem** (D-CRM-02): oportunidades (`opportunity`: origem roadmap ou avulsa, tipo projeto ou recorrente, etapas sugerida → proposta → ganha/perdida), contratos (`contract`, ativo/encerrado) e ajustes de contrato (`contract_adjustment`). Os contratos entram no **Núcleo**; as oportunidades entram no CRM. Mesma importação única e idempotente da RN-CRM-06.
- [ ] [LEVANTAR com Lucas Alencar] Hoje a auditoria do DaaS gera oportunidades a partir do roadmap. Depois da migração, como essas sugestões chegam ao Hub: à mão na v1, integração depois?
- [ ] O assistente de voz do funil do DaaS (F2) é IA embutida e **não vem** na v1 (D-IA-02).

---

## Módulo Chamados

**Último módulo (D-GER-12):** ainda não há volume de chamados. Vira necessidade quando entrarem contratos de **sustentação de infraestrutura**. Base: reunião com Alaor Rodrigues em 24/09/2026 ([ata](atas/2026-09-24-chamados-alaor.md)). O Núcleo já nasce com o que Chamados vai precisar (clientes, contatos, contratos).

### Construir no Hub ou adotar o GLPI (D-CHM-01, [DECIDIR])

O Alaor recomenda o **GLPI**, ferramenta de ITSM open source, sem licença, hospedada pela própria WCJ. Ela já traz o que dá mais trabalho num módulo de chamados: entrada por e-mail, filas N1/N2/N3, catálogo de serviços, SLA, regras de distribuição e inventário de máquinas.

| | A. Construir no Hub | B. GLPI integrado ao Hub | C. Em duas fases (recomendada) |
|---|---|---|---|
| O que é | Módulo próprio, como Projetos e CRM | GLPI hospedado pela WCJ; Hub e GLPI conversam pela API do GLPI | **Agora (v1 de Projetos):** o gestor registra à mão o card de suporte na coluna `Suporte` do quadro (D-PRJ-17). **Depois:** entrada por e-mail. **Quando entrar sustentação de infra:** avaliar o GLPI para o ITSM completo |
| A favor | Tudo num lugar só: mesmo login, mesma auditoria, IA pelo mesmo MCP, chamado vira tarefa sem integração | ITSM completo pronto e maduro; inventário; comunidade grande; Alaor já conhece | Resolve o pedido do Rafael (coluna de suporte no quadro) já na v1, sem construir ITSM antes de ter demanda; decide o GLPI com demanda real |
| Contra | ITSM é trabalhoso (filas, SLA, regras, e-mail); construir antes de ter volume | Mais um sistema para hospedar, atualizar e proteger; clientes e contratos duplicados (sincronizar do Núcleo); a IA do Hub não enxerga o GLPI sem integração própria; cliente com dois portais | Se o GLPI for adotado depois, o chamado de projeto do Hub precisa conversar com ele |

Pontos de atenção se o GLPI entrar:
- **Segurança:** servidor exposto à internet, atualização de versão em dia, backup e HTTPS, pelo mesmo checklist do padrão (`core/seguranca.md`, `core/deploy.md`).
- **Fonte única de cliente e contrato:** o Núcleo do Hub. O GLPI recebe as entidades por sincronização, nunca é cadastrado à mão em paralelo (`core/banco.md` seção 9).
- **IA:** as ferramentas MCP de Chamados passariam a chamar a API do GLPI, com as mesmas regras RN-IA.

### Fluxo principal *(proposta, vale para a opção A ou para a primeira fase da C)*

1. Cliente manda **e-mail** para o endereço de suporte (D-CHM-02; `ticket@` ou `suporte@wcjtecnologia.com.br`, a definir). O e-mail vira chamado, ligado ao cliente pelo endereço do remetente.
2. O **despachante** faz a triagem: confirma cliente, contrato e projeto, define **tipo, área e prioridade** (a prioridade é definida pela WCJ, não pelo cliente) e coloca na **fila** certa.
3. **N1** tenta resolver. Se não resolve, escala para **N2 / N3**. Chamado de desenvolvimento ligado a projeto cai na coluna `Suporte` do quadro do projeto como tarefa (F-PRJ-08).
4. O responsável atende, registra as interações e **aponta as horas**.
5. Chamado resolvido: o cliente confirma, ou o chamado **fecha sozinho** se o cliente não responder no prazo.

### Regras de negócio *(proposta)*

- **RN-CHM-01** Chamado é ligado a cliente e contrato. E-mail de remetente desconhecido entra numa fila de **não identificados** para o despachante vincular; não é descartado.
- **RN-CHM-02** Número do chamado é sequencial por sequência do banco, nunca `max + 1` (`core/banco.md` seção 8).
- **RN-CHM-03** **SLA e prioridade são definidos contrato a contrato** (D-CHM-03). A prioridade do chamado é definida pela WCJ na triagem. [LEVANTAR] tabela de SLA de cada contrato de sustentação.
- **RN-CHM-04** Chamado com prazo estourado aparece em destaque para o despachante e o Administrador.
- **RN-CHM-05** Comentário interno (entre a equipe) nunca aparece para o cliente.
- **RN-CHM-06** Chamado fechado não é reaberto; o cliente abre outro, vinculado ao anterior. [DECIDIR]
- **RN-CHM-07** **Filas** configuráveis. Padrão: `N1`, `N2`, `N3`. Função **despachante** atribuída a pessoas (D-CHM-02). Como no resto do Hub, filas, tipos e áreas são editáveis (D-GER-05).
- **RN-CHM-08** **Banco de horas** (D-CHM-04): contrato pode ter horas contratadas por período. As horas apontadas nos chamados descontam do saldo; o que passar é **hora extra**, contabilizada à parte para cobrança.
- **RN-CHM-09** Chamado resolvido sem resposta do cliente **fecha sozinho** após N dias, configurável por contrato. [LEVANTAR] padrão de N (P-03 da ata).
- **RN-CHM-10** **Viagem** (deslocamento para atendimento no local) é registrada no chamado conforme o contrato. [confirmar] o que o contrato prevê (P-02 da ata).

### A levantar

- [ ] Decisão D-CHM-01 (construir, GLPI ou em duas fases), quando Chamados entrar na fila.
- [ ] Endereço do e-mail de suporte (P-01).
- [ ] Tipos de chamado e áreas.
- [ ] SLA de cada contrato de sustentação; quais contratos entram primeiro.
- [ ] Portal do cliente: entra junto do e-mail ou depois?
- [ ] Inventário de máquinas (GLPI Agent) é necessário para os contratos de sustentação de infraestrutura?

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
| ChatGPT Business / Enterprise, app | Sim | Sim | Atende (plano de empresa) |
| ChatGPT Pro, app | Sim, pelo modo desenvolvedor | **Não: só leitura** | Atende só leitura |
| ChatGPT Plus, app | **Não** | — | Não conecta |
| Claude Code (CLI/IDE/desktop) | Sim, conector local do usuário, HTTP + OAuth | Sim | Atende |
| Codex (CLI/IDE/app) | Sim: `codex mcp add <nome> --url <URL>` + `codex mcp login <nome>` (HTTP + OAuth) | Sim | Atende |

A restrição é do **app ChatGPT**, não do Codex. No Codex e no Claude Code o conector é configurado pelo próprio usuário no cliente dele e não passa pela regra de conector do workspace do ChatGPT; confirmar na prova de conceito. Codex e Claude Code servem para quem trabalha no terminal ou na IDE (devs), não para o uso do dia a dia do comercial.

**R-IA-01 confirmado (24/09/2026, conferido no próprio ChatGPT):** escrita por conector próprio só no **ChatGPT Business** (e superiores). O **Pro só lê**. O **Plus não conecta**.

**Consequência para a v1 (D-IA-05 e D-IA-06):**
- **Claude (Pro ou superior):** leitura e escrita. É o assistente de referência para quem precisa alterar dados pela IA.
- **ChatGPT Pro:** entra só com **escopos de leitura**. O servidor emite token só de leitura para esse cliente, não depende de o ChatGPT se limitar.
- **ChatGPT Plus:** sem conector. Quem usa Plus e precisa da IA no Hub usa Claude, ou fica sem IA no Hub.
- **ChatGPT Business:** só se a WCJ decidir assinar um workspace; fora da v1.
- **Codex e Claude Code (devs):** leitura e escrita pelo MCP. O Codex vem com o ChatGPT Plus/Pro e, desde 07/2026, também roda dentro do app do ChatGPT para computador (Configurações → Integrações → Servidores MCP). Confirmar na prova de conceito.
- **GPT personalizado "WCJ Hub" (comercial):** para quem usa o ChatGPT no dia a dia, como o Wilson. É um GPT com **Actions** que chama uma API REST do Hub, com **login OAuth de cada usuário** (o mesmo servidor de autenticação do MCP). Funciona no ChatGPT web e no celular a partir do Plus. Regras:
  - a API das Actions chama a **mesma camada de serviço** do MCP e da tela; as regras RN-IA-01 a 09 valem igual, com canal `ia:gpt` na auditoria;
  - escopo inicial: **CRM** (leitura e escrita) e **Núcleo** (leitura), as ferramentas de CRM do bloco MCP;
  - toda ação de escrita marcada como "consequencial", para o ChatGPT pedir confirmação antes;
  - entregue **junto com a v1 do CRM** (último módulo da ordem), não antes.

A enquete interna de ferramentas diz quantas pessoas ficam em cada caso.

**Prova de conceito antes da etapa 4** (validação técnica): servidor MCP mínimo, HTTPS público, OAuth, uma ferramenta de leitura e uma de escrita, testado no **Claude (app)**, no **Codex dentro do app do ChatGPT (plano Plus)**, no **Codex CLI**, no **Claude Code** e no **ChatGPT Pro (só leitura)**. Em paralelo, um **GPT com uma Action de leitura e uma de escrita**, com OAuth, no plano do Wilson.

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
| Claude, Codex e ChatGPT Pro via MCP | entrada (consulta e escrita pelo usuário; ChatGPT Pro só consulta) | **Sim** |
| GPT personalizado "WCJ Hub" (Actions + API REST) | entrada (comercial) | **Sim, com a v1 do CRM** |
| GitHub (webhooks de PR) | entrada (eventos) | **Em avaliação** (D-GER-13) |
| Trello | importação | **Não na v1** (D-PRJ-04): começa do zero com os projetos ativos |
| Funil de prospecção do DaaS | importação única (RN-CRM-06) | **Sim** (D-GER-03) |
| DaaS: abrir auditoria a partir de lead ganho | saída | Não (RN-CRM-08) |
| E-mail de suporte | entrada (e-mail vira chamado) e saída (avisos ao cliente) | Com o módulo Chamados (D-CHM-02) |
| GLPI | entrada e saída pela API | Em avaliação (D-CHM-01) |
| WhatsApp | — | Não (ver abaixo) |

---

## Fora da primeira versão

- IA embutida no sistema (sugestão de follow-up, triagem automática, resumo automático), inclusive o assistente de voz do funil do DaaS. D-IA-02.
- Criação automática de auditoria no DaaS quando o lead é ganho. RN-CRM-08.
- Contato do cliente conectando a própria IA. D-IA-01.
- Assistentes além de Claude e ChatGPT. D-IA-03.
- Abertura de chamado por WhatsApp.
- Faturamento, cobrança, emissão de nota.
- Banco de horas da sustentação: entra com o módulo Chamados (D-CHM-04), não na v1 de Projetos.
- Aplicativo móvel nativo (a v1 é web responsiva).

## O que não pode

- Não pode haver regra de fluxo que o gestor não consiga ajustar no próprio projeto, a não ser as regras fixas de segurança, histórico e indicadores (D-GER-05).

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

- Stack: template da casa (`stacks/laravel-inertia.md`), confirmada (D-GER-09).
- Pacote MCP do lado do servidor: avaliar `laravel/mcp` (oficial) na prova de conceito.
- Servidor MCP em HTTPS público desde a homologação (os assistentes chamam da nuvem deles).
- Hospedagem (D-GER-14): **VPS do DaaS** (`auditoria.wcjtecnologia.com.br`), que já roda o app do DaaS e o Supabase em Docker atrás do Nginx. O Hub entra como **mais um conjunto de containers**, com subdomínio próprio (ex.: `hub.wcjtecnologia.com.br`), **banco de dados separado** do Supabase do DaaS, backup próprio e entrada no Nginx. [LEVANTAR com Lucas Alencar] se a VPS tem memória e disco para os dois, e se o banco do Hub fica numa instância Postgres própria ou num banco separado da instância existente.
- Uso principal: computador. Web responsiva; quais telas e funções serão realmente usadas no celular será validado depois.
- Volume (D-GER-10): até 14 usuários internos, 6 clientes ativos, cerca de 10 leads por mês. [LEVANTAR com Alaor] chamados por mês.
- Segurança: fatia de segurança obrigatória (`core/seguranca.md` seção 11) **incluindo o servidor MCP e o OAuth**.
- Prazo: o mais rápido possível (D-GER-08).

## Donos e pessoa técnica

| Parte | Dono (ouve o usuário, valida esta seção) |
|---|---|
| Núcleo | Lucas Scholz (D-GER-07) |
| Projetos | Rafael Garcia (modalidade 100% IA: Lucas Scholz) |
| CRM | Lucas Scholz |
| Chamados | Alaor Rodrigues (D-GER-04) |
| Interface de IA | Lucas Scholz (produto) + Lucas Alencar (técnico) (D-GER-07) |
| **Pessoa técnica que aprova o banco (etapa 2)** | **Lucas Alencar** (D-GER-01) |

## Reuniões de levantamento (planejadas)

Objetivo comum: o que as ferramentas de hoje **não fazem** e o sistema pode incluir. Pautas detalhadas em [`pautas/`](pautas/README.md). Cada reunião vira uma ata em `docs/atas/` e atualiza a seção do módulo correspondente.

| Reunião | Com | Foco | Traz para a reunião |
|---|---|---|---|
| Chamados | Alaor Rodrigues | Canal atual, tipos, prioridade, SLA por contrato, banco de horas, triagem | Lista de contratos de sustentação ativos; 3 chamados reais recentes, do pedido ao fechamento |
| Projetos | Rafael Garcia | O que o Trello não resolve; etapas; visão por cliente e por pessoa; prazos | Acesso aos quadros do Trello (export JSON) |
| Banco + CRM + IA | Lucas Alencar | Viabilidade do núcleo comum, migração do funil do DaaS, prova de conceito MCP | Esquema atual de `lead`/`lead_evento` do DaaS |

Perguntas que valem para as três:
1. O que você faz hoje fora da ferramenta (planilha, WhatsApp, caderno) porque ela não atende?
2. Qual informação você precisa e demora para achar?
3. O que você esquece de fazer e só descobre quando dá problema?
4. O que você pediria para a sua IA fazer nesse módulo, se ela tivesse acesso?
5. O que **não** pode existir, ou seja, o que atrapalharia se o sistema fizesse?

## Fontes

- 24/09/2026: conversa de concepção (Lucas Scholz), incluindo as decisões D-GER-01 a 03 e D-IA-01 a 04.
- 24/09/2026: disponibilidade de conector MCP: Claude Help Center ("Get started with custom connectors using remote MCP") e OpenAI Developers ("ChatGPT Developer mode"); divergência sobre escrita no Plus/Pro resolvida em 24/09 conferindo no próprio ChatGPT (R-IA-01). Codex: OpenAI Developers ("Model Context Protocol", Codex).
- 24/09/2026: export JSON do quadro Trello "Wpay" → `levantamento/trello-wpay.md`. Demais quadros pendentes.
- 24/09/2026: reunião de Projetos com Rafael Garcia → `atas/2026-09-24-projetos-rafael.md`.
- 24/09/2026: reunião de Chamados com Alaor Rodrigues → `atas/2026-09-24-chamados-alaor.md` (inclui resumo do vídeo de referência do GLPI).
- 24/09/2026: fluxo de desenvolvimento e colunas do quadro enviados por Lucas Alencar → `levantamento/fluxo-desenvolvimento.md`.
- 24/09/2026: documentos de requisição SGM-13/17/22 e "Histórico de Desenvolvimento" Tecsolution → `levantamento/modelos-rafael.md` (originais fora do git).
- Funil de prospecção do DaaS: repositório `wcj-ui`, tabelas `lead` e `lead_evento`.
- Atas do Comitê de IA: `docs/atas/`.
