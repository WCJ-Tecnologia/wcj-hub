# Regras de negócio do WCJ Hub — revisão 4

Versão 4, de 24/09/2026. É a versão para a reescrita da `necessidade.md` e do `banco.txt`, feita na **Sessão C** (prompt no `00-COMECE-AQUI.md`). As revisões anteriores estão nos commits `3f8080a`, `0e1125f` e `b53d9d2`.

## Premissa (vale acima de qualquer regra de Projetos)

A gestão de projetos da v1 é um **kanban simples, como o Trello de hoje**. O time é pequeno e respeita o fluxo, então **o sistema não trava o fluxo**:

- **Administrador e gestor** mexem em tudo, a qualquer momento: criam, editam e excluem card; põem e tiram card da sprint; montam pacote; mudam a configuração do projeto.
- **O dev** só não exclui card e não mexe na sprint, no pacote nem na configuração. Todo o resto ele faz.
- Número de controle, como horas × capacidade da sprint, é **referência para o gestor, nunca impeditivo**.
- Regra nova só entra se for simples. Na dúvida, fica a opção mais aberta.

## O que foi decidido pela premissa (vete o que não quiser)

As 17 lacunas da revisão 3 foram fechadas assim, sem rodada de perguntas:

1. Tarefa sem dev ou sem horas **pode** entrar na sprint. Ela só fica sem data prevista e aparece à parte na conta de capacidade (a P21 e a trava da P47 saíram).
2. **Uma sprint em andamento por projeto.** O gestor encerra e decide se o que não terminou vai para a próxima sprint ou volta ao Backlog.
3. O gestor e o Admin põem e tiram card da sprint quando quiserem. Isso inclui card de suporte, bug urgente e tarefa devolvida pela homologação.
4. O card de suporte segue o mesmo quadro, com as validações que o gestor deixar ligadas.
5. A pausa não empurra a data prevista. **Tarefa atrasada** = passou da data prevista sem chegar em `Done`.
6. **Qualquer pessoa alocada cria card** no Backlog, como no Trello.
7. Horas realizadas: cada um lança e corrige as próprias; o gestor e o Admin corrigem qualquer uma.
8. O pacote é **opcional**. O gestor e o Admin montam o pacote e mudam o estado dele, sem trava.
9. O gestor encerra o projeto quando quiser, mesmo com tarefa aberta. Tirar um dev do projeto deixa as tarefas dele sem dev.
10. Aviso no sistema só na **atribuição**.
11. **Listas gerais** (tipos de tarefa, tipos de serviço, funil, origens, motivos de perda, tipos de compromisso): quem edita é o Administrador. **Listas do projeto** (etapas, áreas, motivos de retrabalho, estados do pacote): quem edita é o gestor.
12. O Administrador define a carga diária e desativa e reativa usuário, cliente e contato, sem trava. O gestor também cadastra contato dos clientes dos projetos dele.
13. O follow-up sai de "vencido" quando é marcado como feito ou quando alguém registra uma tratativa no lead.
14. O Administrador exclui lead e troca o dono. Lead perdido pode voltar ao funil.
15. LGPD: a pedido do titular, o Administrador **anonimiza** o contato. O histórico fica, sem os dados pessoais.

**Fixa** = vale para todo projeto. **Padrão** = vem ligada e o gestor muda por projeto.

---

## 0. Vale para todos os módulos

- **G1** O sistema traz um padrão pronto que o gestor ajusta por projeto. Só é fixo o que protege segurança e auditoria.
- **G2** Usuário, cliente e contato já usados não são excluídos: são desativados. Card de projeto e lead podem ser excluídos por quem tem permissão (P26, C16). Tudo fica na auditoria. *(revisado)*
- **G3** Toda alteração, em qualquer módulo, fica na auditoria: quem, quando, o antes e o depois, e por onde (tela ou qual IA). Tentativa negada também fica. A auditoria nunca é apagada.
- **G4** O que o papel não vê na tela, a IA dele também não vê nem altera.
- **G5** Dado sem regra que o use não entra no sistema. Volta quando alguém pedir, com o motivo.
- **G6** Na v1 não existe: aprovação em dois níveis, ranking ou metas, mensagem ao cliente por WhatsApp, aplicativo nativo (é web responsiva).
- **G7** Listas gerais: quem edita é o Administrador. Listas do projeto: quem edita é o gestor do projeto. *(novo)*

## 1. Quem usa (papéis da v1)

| Papel | Quem é | Vê e faz | Não vê |
|---|---|---|---|
| Administrador | Sócios, inclusive o Wilson | Tudo | — |
| Gestor de projetos | Rafael Garcia; Lucas Scholz nos projetos 100% IA | Tudo nos projetos que gere | Pipeline comercial e valores de proposta |
| Desenvolvedor | Devs PJ | Projetos em que está alocado e todas as tarefas deles: cria card, move, edita, comenta, anexa, lança horas, puxa teste cruzado | CRM; painel de produtividade. Não exclui card e não mexe em sprint, pacote nem configuração |
| Comercial | Ninguém no início | Todos os leads; edita os próprios; converte lead em cliente | Tarefas internas de projeto |

- O papel Comercial fica pronto para quando houver comercial. Hoje o Wilson é sócio e entra como Administrador.
- **Tech Lead** não é papel: é uma função dada por projeto (P6).
- **Suporte** e **contato do cliente** entram com o módulo Chamados.

## 2. Núcleo

- **N1** Usuário é convidado pelo Administrador, com senha provisória trocada no primeiro acesso.
- **N2** Cada usuário tem um papel, entre os 4 fixos, atribuído pelo Administrador. Não se cria papel novo.
- **N3** Carga diária (ex.: 8 h ou 4 h): obrigatória para o Desenvolvedor, opcional para os outros papéis, definida pelo Administrador. Só entra no time da sprint quem tem carga. Mudar a carga vale dali para a frente.
- **N4** Usuário desativado perde o acesso na hora, inclusive as conexões de IA.
- **N5** Cliente brasileiro é identificado pelo CNPJ; CNPJ repetido é recusado. Empresa estrangeira: país + identificador fiscal do país, opcional. Pessoa física não é cliente.
- **N6** Cliente nasce por cadastro direto ou por lead ganho, sempre sem contrato.
- **N7** A própria WCJ é um cliente: é a dona dos projetos internos e dos produtos (ex.: Wpay).
- **N8** Contato é um cadastro só, seja pessoa de cliente ou de lead: nome, e-mail e telefone. Quando o lead vira cliente, o contato não é redigitado. O sistema avisa quando já existe contato com o mesmo e-mail, sem recusar. O gestor também cadastra contato dos clientes dos projetos dele. *(revisado)*
- **N9** Contato não tem login na v1.
- **N10** Contrato fica fora da v1. Fica a lista de **tipos de serviço** (projeto, sustentação, auditoria, alocação de dev, infraestrutura), que o lead usa para dizer o que está sendo vendido.
- **N11** O Administrador desativa e reativa usuário, cliente e contato, sem trava por ter algo aberto. *(novo)*
- **N12** LGPD: a pedido do titular, o Administrador anonimiza o contato. Nome, e-mail e telefone viram "removido a pedido do titular", e o histórico continua sem os dados pessoais. *(novo)*

## 3. Projetos

### 3.1 Projeto

- **P1** Todo projeto pertence a um cliente e tem um gestor responsável.
- **P2** Dois tipos de projeto:
  - **projeto de entrega**: tem data de início e data de entrega. A data de entrega é informação para o gestor, que acompanha o prazo por conta própria na v1;
  - **produto contínuo** (ex.: Wpay): só tem data de início, fica em melhoria contínua, e o prazo é o de cada sprint.
- **P3** Cada projeto tem um prefixo (ex.: `SGM`), único no sistema, que não muda depois da primeira tarefa.
- **P4** Modalidade tradicional ou 100% IA. Na v1 é só uma marcação.
- **P5** O dev vê só os projetos em que está alocado; o gestor, os que gere; o Administrador, todos. O gestor põe e tira pessoas do projeto a qualquer momento; as tarefas de quem saiu ficam sem dev. *(revisado)*
- **P6** O Tech Lead é definido por projeto, um ou mais.
- **P7** O gestor encerra o projeto quando quiser, mesmo com tarefa aberta. Projeto encerrado não recebe tarefa nova; o gestor ou o Administrador reabre. *(revisado)*
- **P8** Mudar a configuração do projeto vale dali para a frente e nunca apaga o que já foi registrado.
- ~~**P9**~~ Prazo do projeto pelo sistema: fora da v1.

### 3.2 Quadro e etapas

- **P10** Cada projeto tem o próprio quadro. Padrão: `Backlog`, `To do`, `Doing`, `Paused`, `Code-review`, `In test`, `Done` e `Support`, em inglês. **Padrão.**
- **P11** O gestor renomeia, cria, remove e reordena etapas. Toda etapa tem um papel (fila, sprint, desenvolvimento, code review, teste, pronto, pausa ou suporte); os indicadores leem o papel, não o nome.
- **P12** Para remover uma etapa que tem tarefas, é preciso dizer para onde elas vão.
- **P13** Qualquer pessoa alocada move o card entre as etapas, a partir do `To do`. Pôr e tirar da sprint é do gestor e do Administrador (P45). *(revisado)*
- **P14** Toda mudança de etapa grava quem, quando, de onde e para onde. **Fixa.**
- **P15** Ir para `Paused` pede motivo, e o tempo parado conta à parte. **Padrão** (pode desligar o motivo).

### 3.3 Tarefa

- **P16** Chave `<prefixo>-<número>` (ex.: `SGM-17`), sequencial por projeto, nunca reaproveitada.
- **P17** A tarefa é escrita no modelo de requisição: Sendo / Quero / Para + critérios de aceite `CA_1…CA_n`.
- **P18** Tipo (melhoria, correção de bug, suporte, mudança/GMUD) e área (lista do projeto, que nasce vazia).
- **P19** Prioridade: baixa, normal, alta ou urgente.
- **P20** A tarefa tem **um dev** e, quando a validação de QA está ligada, **um QA**.
- ~~**P21**~~ Saiu: tarefa sem dev pode entrar na sprint (premissa).
- **P22** Quem é atribuído à tarefa recebe aviso **no sistema**. É o único aviso da v1; sem e-mail.
- **P23** A tarefa aceita nenhum, um ou vários PRs, colados como link.
- **P24** A tarefa tem anexos e comentários.
- **P25** Bug achado em produção vira tarefa nova, ligada à original. Conta como indicador de qualidade do projeto, não como retrabalho do dev.
- **P26** O gestor e o Administrador **excluem** qualquer card, a qualquer momento; a exclusão fica na auditoria. Card abandonado também pode ser **arquivado**, com motivo; desarquivar devolve ao Backlog. O dev não exclui nem arquiva. *(revisado)*
- **P56** Qualquer pessoa alocada cria card no Backlog. *(novo)*

### 3.4 Horas e prazo

- **P27** Horas previstas: **um total só, Dev + QA**, opcionais ao criar. Na montagem da sprint, o gestor, com o diretor de tecnologia, define ou valida as horas. Tarefa sem horas pode entrar na sprint: só fica sem data prevista. Reestimar fica no histórico. *(revisado)*
- **P28** A **data prevista** é calculada quando a tarefa entra em `Doing` pela primeira vez: horas previstas ÷ carga diária do dev, em dias úteis de segunda a sexta. Só muda se o gestor reestimar; nem retrabalho nem pausa empurram a data. *(revisado)*
  Exemplo: 16 h com um dev de 8 h/dia = 2 dias úteis; com um dev de 4 h/dia = 4 dias úteis.
- **P29** Feriados não contam na v1.
- **P30** Horas realizadas: quem trabalhou lança à mão, por dia, em horas decimais, e corrige as próprias. O gestor e o Administrador corrigem qualquer uma. Não há cronômetro. *(revisado)*
- **P31** As horas do projeto não são comparadas com horas de contrato.

### 3.5 Validações: code review, teste cruzado, QA

- **P32** Code review, teste cruzado e QA vêm **ligados** em toda tarefa. Cada um desliga por projeto e por tarefa. **Padrão.**
- **P33** Code review: na prática quem aprova é o Tech Lead do projeto; o sistema não trava.
- **P34** Teste cruzado: qualquer dev alocado puxa, menos o dev da tarefa. *(revisado)*
- **P35** Em `In test`, a ordem normal é teste cruzado e depois QA.
- **P36** O QA testa no próprio ambiente local. Executa o roteiro, em que cada critério de aceite é um item: marca aprovado ou reprovado e anexa a evidência no item.
- **P37** Critério editado depois de testado volta para "a testar". Depois de um retrabalho, o roteiro inteiro volta para "a testar".
- **P38** Aprovada no QA, a tarefa recebe o merge (normalmente pelo Tech Lead) e vai para `Done`.

### 3.6 Retrabalho

- **P39** Toda volta para desenvolvimento conta um ciclo de retrabalho, com **origem** (code review, teste cruzado, QA ou homologação) e **motivo**.
- **P40** Os motivos são uma lista editável por projeto, sempre com "outro" com texto. Padrão: ajuste visual, regra de negócio, erro/bug, requisito incompleto ou mal documentado, outro.

### 3.7 Pacote (versão)

- **P41** O pacote é a versão (ex.: `1.0.9`) e é **opcional**. O gestor e o Administrador montam o pacote com tarefas prontas e mudam o estado dele. *(revisado)*
- **P42** Estados do pacote: em montagem → em homologação → homologado → em produção. O gestor pode renomear, criar e remover estados no projeto. *(revisado)*
- **P43** A homologação é da equipe WCJ, testando tudo junto; o cliente não participa. Reprovação devolve as tarefas afetadas com retrabalho de origem homologação.
- **P44** A data planejada de liberação é definida por projeto, em geral pelo cliente. A data da versão é quando ela entra em produção.

### 3.8 Sprint

- **P45** A sprint é **obrigatória**: é por ela que o card sai do Backlog. O gestor e o Administrador criam a sprint (nome, objetivo, início e fim) e põem ou tiram card dela a qualquer momento; o dev não. **Uma sprint em andamento por projeto.** *(revisado)*
- **P46** Duração padrão de 10 dias úteis (2 semanas), ajustável por projeto. **Padrão.**
- **P47** A sprint tem um time, formado por quem tem carga diária (N3). *(revisado)*
- **P48** Capacidade de cada pessoa na sprint = carga diária × dias disponíveis. O gestor reduz os dias por férias ou folga.
- **P49** O sistema mostra, por dev, as horas que faltam das tarefas dele na sprint (previstas − já lançadas) contra a capacidade dele. É **só referência** para o gestor: nunca impede nada. Tarefa sem dev ou sem horas aparece à parte. A capacidade do QA não entra na conta na v1. *(revisado)*
- **P50** O gestor encerra a sprint. O que não terminou vai para a próxima sprint ou volta ao Backlog, como o gestor decidir; fica o histórico. *(revisado)*
- **P51** A sprint é a "Fase" do Histórico de Desenvolvimento entregue ao cliente.

### 3.9 Suporte

- **P52** Enquanto o módulo Chamados não existe, quando o cliente aciona a WCJ direto (WhatsApp, telefone, e-mail, reunião ou outro), o gestor registra um **card de suporte** na coluna `Support`, com quem pediu (contato), por onde e quando o pedido chegou. Se quiser, põe o card na sprint em andamento. O card segue o mesmo quadro, com as validações que o gestor deixar ligadas. *(revisado)*

### 3.10 Indicadores

- **P53** Na v1: tempo por etapa, horas previstas × realizadas, data prevista e atraso das tarefas, somados por projeto e por pessoa. Tarefa atrasada = passou da data prevista sem chegar em `Done`. *(revisado)*
- **P54** Depois da v1, com os dados já gravados desde a v1: painel de produtividade por dev e indicador de retrabalho. Só o gestor e o Administrador veem.
- **P55** Depois da v1: histórico de desenvolvimento em PDF, relatório de fechamento, notas de versão e geração do documento de requisição.

**Fora da v1 em Projetos:** feriados; restrição de etapa por função; vínculo automático com o GitHub; migração do Trello; contrato no projeto; prazo do projeto pelo sistema; aviso por e-mail.

## 4. CRM

- **C1** Um funil só. Etapas padrão, as do DaaS: Prospecção → Contato → Reunião → Proposta → Ganhou / Perdido. São editáveis, mas sempre existe uma etapa de ganho e uma de perda. O lead anda livre entre as etapas.
- **C2** O lead tem: empresa (nome, país, CNPJ ou identificador), contato(s), origem, tipo de serviço, **valor** e dono. O valor é obrigatório para ganhar. Não tem previsão de fechamento. Origens: lista editável que nasce com Indicação, Tráfego e Site.
- **C3** O mesmo CNPJ pode ter vários leads. Ao cadastrar, o sistema **avisa** se já existe cliente ou lead aberto com esse CNPJ e mostra o dono; não recusa.
- **C4** Quem tem papel Comercial vê todos os leads e edita só os próprios. O Administrador edita todos.
- **C5** Tratativa é cada conversa com o lead: data, canal, resumo, próximo passo e, opcional, com quem falou (um contato do lead). Pode ser editada a qualquer momento; toda edição fica no histórico.
- **C6** Situação atual do lead: texto curto, sempre o mais recente, atualizado a cada tratativa. As versões antigas ficam no histórico.
- **C7** Compromisso: follow-up ou reunião (lista editável), data e hora, duração (na reunião), responsável, observação, local ou link. É sempre ligado a um lead ou a um cliente. É criado, remarcado ou cancelado pela tela ou pela IA.
- **C8** Todo lead ativo tem um follow-up futuro; sem isso, aparece em alerta para o dono. O follow-up sai de "vencido" quando é marcado como feito ou quando se registra uma tratativa no lead. *(revisado)*
- **C9** Lembretes, sem IA embutida:
  - ao criar, remarcar ou cancelar um compromisso, o responsável recebe um convite de calendário por e-mail. Os participantes, ele mesmo chama pelo Teams;
  - todo dia de manhã, um e-mail de resumo com os compromissos do dia e os follow-ups vencidos;
  - no Hub, a lista do dia e dos vencidos.
- **C10** Lead ganho vira cliente sem redigitar nada; se o CNPJ já é cliente, só vincula. O cliente nasce sem contrato.
- **C11** Lead perdido exige motivo de uma lista editável: preço, momento, concorrente, sem resposta, sem perfil, outro (com texto). Lead perdido pode voltar ao funil. *(revisado)*
- **C12** Lead de auditoria só é ganho com o sizing preenchido: setor (texto) e as 5 perguntas do DaaS. O preço da auditoria é o valor do lead (C2).
- **C13** Ganhar um lead de auditoria não cria nada no DaaS; a auditoria é aberta lá à mão.
- **C14** O gestor de projetos não vê o pipeline comercial; o dev não vê o CRM. **Fixa.**
- **C15** O CRM começa do zero, sem migração do DaaS.
- **C16** O Administrador exclui lead e troca o dono dele; fica na auditoria. *(novo)*

**Fora da v1 no CRM:** oportunidades pós-auditoria; integração CRM → DaaS; assistente de voz embutido; contrato gerado pelo lead ganho; previsão de fechamento.

## 5. Interface de IA

- **IA1** Só usuários internos conectam IA na v1.
- **IA2** O sistema não chama IA por conta própria: toda IA é a do usuário, paga por ele.
- **IA3** Quem usa o quê:
  - devs: Claude (app ou Claude Code) ou Codex, lendo e escrevendo;
  - ChatGPT Pro: só lê;
  - ChatGPT Plus: não conecta;
  - comercial (hoje o Wilson): pelo GPT "WCJ Hub" no ChatGPT, com áudio, entregue junto com a v1 do CRM.
- **IA4** A conexão é só por login (OAuth), nunca por chave colada. Cada usuário tem uma autorização por assistente, com permissão por módulo (ler ou escrever).
- **IA5** A IA age como o usuário: tem as mesmas permissões da tela, e a permissão da conexão nunca passa do papel. **Fixa.**
- **IA6** O usuário vê e revoga as próprias conexões; o Administrador, as de todos. Reconectar revoga a conexão anterior. Usuário desativado ou com papel trocado perde as conexões na hora. **Fixa.**
- **IA7** Limite por conexão: 60 leituras e 20 escritas por minuto, ajustáveis. Quando estoura, a IA recebe um erro legível.
- **IA8** Cada escrita altera uma coisa só. Não existe alteração em lote, exclusão pela IA nem consulta livre (SQL). **Fixa.**
- **IA9** As ferramentas são marcadas como leitura ou escrita, para o assistente pedir confirmação antes de escrever. A segurança não depende disso: o servidor confere tudo.
- **IA10** Texto vindo de cliente volta para a IA como dado, marcado como conteúdo externo.
- **IA11** Toda escrita feita pela IA fica na auditoria com o canal (`ia:claude`, `ia:chatgpt`, `ia:codex`, `ia:gpt`).
- **IA12** Toda chamada da IA, inclusive de leitura, fica registrada por 180 dias (só ids e filtros), e o Administrador consulta numa tela.
- **IA13** O resumo do cliente pela IA mostra só o que o papel vê.
- **IA14** Ferramentas da v1:
  - Núcleo: buscar cliente, resumo do cliente;
  - Projetos, leitura: minhas tarefas, status do projeto, linha do tempo da tarefa, horas do projeto, retrabalho do projeto, tarefas paradas, notas da versão;
  - Projetos, escrita: escrever tarefa no modelo de requisição, mover e comentar tarefa, lançar horas, anexar PR, devolver para desenvolvimento com motivo;
  - CRM: meus follow-ups, minha agenda, histórico do lead, cadastrar lead, registrar tratativa, mover lead, agendar compromisso, atualizar situação.

  A lista é validada por quem desenvolve o Hub (Lucas Scholz e Cauã).

## 6. Chamados

Fora desta revisão. É o último módulo: ainda não tem demanda, e a decisão entre construir no Hub, adotar o GLPI ou fazer em duas fases está aberta. As regras em rascunho continuam na `necessidade.md`. Na v1, o que existe é o card de suporte do quadro (P52).

---

## Anexo: o que muda no `banco.txt` (para a Sessão C)

**Antes de tudo, a premissa:** onde o banco tiver estrutura que só existe para travar o fluxo de Projetos, simplifique.

- **Códigos:** as referências RN-…, D-PRJ-…, D-CRM-…, D-NUC-… e D-IA-… de produto passam a ser os códigos deste arquivo (G, N, P, C, IA). Ficam as decisões de projeto (D-GER, D-IA-04, D-IA-07, D-CRM-04, D-CHM). A seção 7 do banco (mapa das respostas) sai.
- **users.daily_hours:** passa a aceitar vazio; obrigatória para o Desenvolvedor (N3). Só entra em sprint_members quem tem carga.
- **contract_types → service_types**, e leads.contract_type_id → service_type_id (N10).
- **contacts:** índice em email para o aviso de duplicado (N8); a pergunta T11 deixa de ser pergunta. A anonimização (N12) também limpa os dados pessoais do contato no `changes` de audit_logs.
- **projects:**
  - saem enforce_sprint_capacity (P49 é só referência) e require_assignee_out_of_backlog (a P21 saiu);
  - saem os comentários de "sprints que cabem" e "fora do cronograma";
  - due_on fica, só informativo (P2).
- **tasks:**
  - estimated_hours passa a aceitar vazio (P27);
  - continua 1 developer_id + 1 qa_id (P20);
  - a exclusão pelo gestor ou pelo Admin (P26) vira exclusão lógica (deleted_at), para não perder horas e histórico. archived_at e archive_reason continuam.
- **sprints:**
  - entra closed_at: o gestor encerra (P50);
  - só uma sprint em andamento por projeto (Service);
  - a tarefa sai do Backlog só pela sprint (P45);
  - sem nenhuma trava de capacidade ou de dev (P49).
- **task_validations:** sai a trava de "puxar" teste cruzado (escolha T2); fica só o registro de quem validou.
- **packages:** saem do Service as travas "só tarefa em Done" e "em homologação não recebe tarefa" (P41, P42).
- **leads:**
  - entra value (money, aceita vazio), obrigatório para ganhar (C2);
  - exclusão lógica pelo Administrador (C16).
- **lead_audit_sizings:** sai price, porque o preço é o valor do lead (C12).
- **lead_sources:** semente Indicação, Tráfego, Site (C2).
- **lead_interactions:** entra contact_id fk contacts, aceita vazio (C5).
- **appointments:** nada muda. O completed_at que já existe cobre o "feito" da C8.
- **rework_reasons:** sai "[validar com Rafael]" (P40).
- **notifications:** só o aviso de atribuição em Projetos (P22). E-mail só nos lembretes do CRM (C9).
